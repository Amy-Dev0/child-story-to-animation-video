import sys
import os
import json
import spacy
import requests
import subprocess
from PIL import Image
from io import BytesIO
import time
import re
import argparse
import tenacity
from dotenv import load_dotenv

# Force UTF-8 encoding for output
sys.stdout.reconfigure(encoding='utf-8')
sys.stderr.reconfigure(encoding='utf-8')

# Load environment variables from .env
load_dotenv()

try:
    import openai
except ImportError:
    print("Error: OpenAI library not found. Please install it using 'pip install openai'")
    sys.exit(1)

# API Keys from environment variables
OPENAI_API_KEY = os.getenv("OPENAI_API_KEY")
TOPMEDIA_API_KEY = os.getenv("TOPMEDIA_API_KEY")

if not OPENAI_API_KEY or not TOPMEDIA_API_KEY:
    print("Error: Missing OPENAI_API_KEY or TOPMEDIA_API_KEY in .env file.")
    sys.exit(1)

# Increase timeout to 60 seconds
openai.api_key = OPENAI_API_KEY
client = openai.OpenAI(api_key=OPENAI_API_KEY, timeout=500)


# Load spaCy models
try:
    nlp = spacy.load("en_core_web_sm")
except Exception as e:
    print(f"Error loading English spaCy model: {str(e)}")
    sys.exit(1)

try:
    nlp_arabic = spacy.load("xx_sent_ud_sm")
except Exception as e:
    print(f"Error loading Arabic spaCy model: {str(e)}")
    sys.exit(1)

# Define paths
BASE_DIR = "C:/xampp/htdocs/DremScribeAi"
FFMPEG_PATH = "C:/ffmpeg-2025-03-17-git-5b9356f18e-essentials_build/bin/ffmpeg.exe"
FFPROBE_PATH = "C:/ffmpeg-2025-03-17-git-5b9356f18e-essentials_build/bin/ffprobe.exe"

# Validate FFmpeg and FFprobe paths
if not os.path.exists(FFMPEG_PATH):
    print(json.dumps({"status": "error", "message": f"FFmpeg not found at {FFMPEG_PATH}."}))
    sys.exit(1)
if not os.path.exists(FFPROBE_PATH):
    print(json.dumps({"status": "error", "message": f"FFprobe not found at {FFPROBE_PATH}."}))
    sys.exit(1)

# Create directories
os.makedirs(os.path.join(BASE_DIR, "generated_videos"), exist_ok=True)
os.makedirs(os.path.join(BASE_DIR, "audio"), exist_ok=True)
os.makedirs(os.path.join(BASE_DIR, "videos"), exist_ok=True)

# Utility functions
def is_arabic(text):
    for char in text:
        if '\u0600' <= char <= '\u06FF':
            return True
    return False

def count_arabic_sentences(text):
    sentences = re.split(r'[.!؟]+', text)
    sentences = [s.strip() for s in sentences if s.strip()]
    return len(sentences)

def preprocess_text_for_scenes(text, language):
    is_arabic_lang = language.lower() == "ar"
    if not text:
        return text
    
    # Count the number of periods in the original text
    period_count = len(re.findall(r'\.', text))
    
    # If there are 3 or more periods, return the text unchanged
    if period_count >= 3:
        return text
    
    # Remove existing punctuation to standardize
    text = re.sub(r'[.!؟]', '', text)
    
    if is_arabic_lang:
        # Arabic transition words to split on (e.g., "ثم" = "then")
        transition_words = ['ثم', 'بعد', 'لذلك', 'في', 'عندما']
        # Split on transition words and add periods
        for word in transition_words:
            text = text.replace(f' {word} ', f'. {word} ')
        # Handle cases where transition words are at the start of the text
        text = text.strip()
        if text.startswith('.'):
            text = text[1:].strip()
    else:
        # English transition words to split on
        transition_words = ['he', 'she', 'they', 'then', 'and', 'but']
        # Split on transition words and add periods
        for word in transition_words:
            text = text.replace(f' {word} ', f'. {word} ')
        # Handle cases where transition words are at the start of the text
        text = text.strip()
        if text.startswith('.'):
            text = text[1:].strip()
    
    # Add a final period if missing
    if text and not text.endswith('.'):
        text += '.'
    
    return text

def merge_short_scenes(scenes, min_length=10):
    merged_scenes = []
    current_scene = scenes[0] if scenes else ""
    for scene in scenes[1:]:
        if len(current_scene) < min_length or len(scene) < min_length:
            current_scene = f"{current_scene} {scene}".strip()
        else:
            merged_scenes.append(current_scene)
            current_scene = scene
    if current_scene:
        merged_scenes.append(current_scene)
    return merged_scenes

def generate_story(title, language, voice, user_story=""):
    start_time = time.time()
    is_arabic_lang = language.lower() == "ar"
    
    # Preprocess the user story to improve scene detection
    if user_story:
        user_story = preprocess_text_for_scenes(user_story, language)
    
    if is_arabic_lang:
        system_message = "أنت راوي قصص إبداعي للأطفال. اكتب باللغة العربية فقط."
        if user_story:
            # Analyze the user story to count scenes before sending to OpenAI
            doc = nlp_arabic(user_story)
            initial_scenes = [sent.text.strip() for sent in doc.sents if sent.text.strip()]
            initial_scene_count = len(initial_scenes)
            
            story_prompt = (
                f"إذا كانت القصة التالية ناقصة أو تحتوي على أخطاء، قم بإصلاحها واكملها لتصبح قصة أطفال قصيرة وكاملة بعنوان '{title}': '{user_story}'. "
                f"يجب أن تحتوي القصة على 3 مشاهد على الأقل (بداية، وسط، نهاية)، مع حدث أو مشكلة بسيطة تُحل في النهاية. "
                f"القصة المقدمة تحتوي على {initial_scene_count} مشاهد. إذا كان العدد أقل من 3، أضف مشاهد إضافية للوصول إلى 3 مشاهد على الأقل. "
                f"صحح أي أخطاء إملائية أو نحوية، وأنهِ القصة بمغزى موجز (مثال: 'تعلما أن اللطف يجلب السعادة.'). "
                f"يجب أن تُروى بصوت {voice} باللغة العربية، بنبرة بسيطة وإبداعية مناسبة للأطفال الصغار، ولا تتجاوز 500 حرف."
            )
        else:
            story_prompt = (
                f"اكتب قصة أطفال قصيرة وكاملة (3-5 جمل) بعنوان '{title}'. "
                f"يجب أن تحتوي القصة على 3 مشاهد على الأقل (بداية، وسط، نهاية)، مع حدث أو مشكلة بسيطة تُحل في النهاية. "
                f"أنهِ القصة بمغزى أو استنتاج موجز (مثال: 'تعلما أن اللطف يجلب السعادة.'). "
                f"يجب أن تُروى القصة بصوت {voice} باللغة العربية. "
                f"حافظ على النبرة بسيطة وإبداعية ومناسبة للأطفال الصغار. "
                f"يجب ألا تتجاوز القصة 500 حرف، شاملة المسافات وعلامات الترقيم."
            )
    else:
        system_message = "You are a creative storyteller for children. Write in English only."
        if user_story:
            # Analyze the user story to count scenes before sending to OpenAI
            doc = nlp(user_story)
            initial_scenes = [sent.text.strip() for sent in doc.sents if sent.text.strip()]
            initial_scene_count = len(initial_scenes)
            
            story_prompt = (
                f"If the following story is incomplete or has errors, fix and complete it into a short, complete children’s story titled '{title}': '{user_story}'. "
                f"The story must have at least 3 scenes (beginning, middle, end), with a simple conflict or event that gets resolved. "
                f"The provided story has {initial_scene_count} scenes. If it has fewer than 3 scenes, add more scenes to reach at least 3. "
                f"Correct any spelling or grammar errors. "
                f"End with a concise moral (e.g., 'They learned kindness brings joy.'). "
                f"Narrate it with a {voice} voice in English, keeping the tone simple, creative, and suitable for young children, within 500 characters."
            )
        else:
            story_prompt = (
                f"Generate a short, complete children's story (3-5 sentences) based on the title '{title}'. "
                f"The story must have at least 3 scenes (beginning, middle, end), with a simple conflict or event that gets resolved. "
                f"End with a concise moral or conclusion (e.g., 'They learned kindness brings joy.'). "
                f"It should be narrated by a {voice} in English. "
                f"Keep the tone simple, creative, and suitable for young children. "
                f"The entire story must be within 500 characters, including spaces and punctuation."
            )

    print(f"Starting story generation for title: {title}, language: {language}, voice: {voice}, user_story: {user_story}", file=sys.stderr)

    max_attempts = 5
    story = None
    for attempt in range(max_attempts):
        try:
            response = client.chat.completions.create(
                model="gpt-3.5-turbo",
                messages=[
                    {"role": "system", "content": system_message},
                    {"role": "user", "content": story_prompt}
                ],
                max_tokens=150,
                temperature=0.7
            )
            story = response.choices[0].message.content.strip()

            if is_arabic_lang and not is_arabic(story):
                print(f"Attempt {attempt + 1}: Story is not in Arabic. Retrying...", file=sys.stderr)
                continue
            if not is_arabic_lang and is_arabic(story):
                print(f"Attempt {attempt + 1}: Story is not in English. Retrying...", file=sys.stderr)
                continue

            if len(story) > 500:
                print(f"Attempt {attempt + 1}: Story exceeds 500 characters ({len(story)}). Trimming...", file=sys.stderr)
                story = story[:500]
                last_period = story.rfind('.')
                if last_period != -1:
                    story = story[:last_period + 1]
                else:
                    story = story[:497] + "..."

            if is_arabic_lang:
                sentence_count = count_arabic_sentences(story)
            else:
                doc = nlp(story)
                sentence_count = len([sent.text.strip() for sent in doc.sents if sent.text.strip()])

            if sentence_count >= 3:
                print(f"Generated story ({len(story)} characters, {sentence_count} sentences): {story}", file=sys.stderr)
                break
            else:
                print(f"Attempt {attempt + 1}: Story incomplete ({sentence_count} sentences). Retrying...", file=sys.stderr)
        except Exception as e:
            print(json.dumps({"status": "error", "message": f"Error generating story: {str(e)}"}))
            sys.exit(1)

    if not story:
        print(json.dumps({"status": "error", "message": "Failed to generate a complete story after multiple attempts."}))
        sys.exit(1)

    return story

# Retry decorator for image generation
@tenacity.retry(
    stop=tenacity.stop_after_attempt(5),
    wait=tenacity.wait_exponential(multiplier=1, min=4, max=10),
    retry=tenacity.retry_if_exception_type(Exception),
    before_sleep=tenacity.before_sleep_log(sys.stderr, "INFO")
)
def generate_image_with_retry(prompt, scene_index, story_folder):
    print(f"Attempting to generate image with prompt: {prompt}", file=sys.stderr)
    response = client.images.generate(
        model="dall-e-3",
        prompt=prompt,
        n=1,
        size="1024x1024"
    )
    image_url = response.data[0].url

    # Save the image
    img_response = requests.get(image_url)
    image = Image.open(BytesIO(img_response.content))
    image_path = os.path.join(story_folder, f"scene_{scene_index + 1}.png")
    image.save(image_path)
    return image_path

def main():
    start_time = time.time()
    parser = argparse.ArgumentParser(description="Generate a children's story and video.")
    parser.add_argument("story_title", nargs="?", help="Title of the story")
    parser.add_argument("story_text", nargs="?", help="Text of the story")
    parser.add_argument("language", nargs="?", help="Language of the story (en or ar)")
    parser.add_argument("narrator_voice", nargs="?", help="Narrator's voice (male or female)")
    parser.add_argument("--generate-from-title", action="store_true", help="Generate story from title only")
    parser.add_argument("--args-file", help="Path to a JSON file containing the arguments")
    args = parser.parse_args()

    if args.args_file:
        try:
            with open(args.args_file, 'r', encoding='utf-8') as f:
                json_args = json.load(f)
            args.story_title = json_args.get('story_title')
            args.story_text = json_args.get('story_text')
            args.language = json_args.get('language')
            args.narrator_voice = json_args.get('narrator_voice')
        except Exception as e:
            print(json.dumps({"status": "error", "message": f"Failed to read arguments from JSON file: {str(e)}"}))
            sys.exit(1)

    raw_args_log = "Arguments After Loading:\n"
    raw_args_log += f"story_title: {args.story_title}\n"
    raw_args_log += f"story_text: {args.story_text}\n"
    raw_args_log += f"language: {args.language}\n"
    raw_args_log += f"narrator_voice: {args.narrator_voice}\n"
    raw_args_log += f"generate-from-title: {args.generate_from_title}\n"
    print(raw_args_log, file=sys.stderr)

    if args.generate_from_title:
        if not all([args.story_title, args.language, args.narrator_voice]):
            print(json.dumps({"status": "error", "message": "Missing required arguments: title, language, voice"}))
            sys.exit(1)

        story = generate_story(args.story_title, args.language, args.narrator_voice)
        print(json.dumps({"status": "success", "story": story}))
        return

    if not all([args.story_title, args.story_text, args.language, args.narrator_voice]):
        print(json.dumps({"status": "error", "message": "Missing required arguments for video generation: story_title, story_text, language, narrator_voice"}))
        sys.exit(1)

    story_name = args.story_title.strip().replace(" ", "_")
    user_story_text = args.story_text
    language = args.language
    narrator_gender = args.narrator_voice

    # Pass user-provided story to generate_story for correction and completion
    story = generate_story(args.story_title, language, narrator_gender, user_story=user_story_text)

    if narrator_gender.lower() == "female":
        tts_speaker = "5a1d0197-d183-11ef-a695-e86f38d7ec1a"
    else:
        tts_speaker = "0012695e-3826-11ee-a861-00163e2ac61b"

    tts_lang = "ar" if is_arabic(story) else "en"
    style_prompt = "bright, colorful 2D animation with smooth lines and a whimsical style"

    # Preprocess the completed story for scene detection
    story = preprocess_text_for_scenes(story, language)

    if is_arabic(story):
        doc = nlp_arabic(story)
    else:
        doc = nlp(story)

    scenes = [sent.text.strip() for sent in doc.sents if sent.text.strip()]
    scenes = merge_short_scenes(scenes, min_length=10)
    print(f"Number of scenes detected: {len(scenes)}")
    scenes_str = [str(scene.encode('utf-8', 'replace'), 'utf-8') for scene in scenes]
    print("Scenes:", scenes_str)

    story_folder = os.path.join(BASE_DIR, f"generated_videos/{story_name}")
    os.makedirs(story_folder, exist_ok=True)

    image_files = []
    first_scene_image = None

    for i, scene in enumerate(scenes):
        print(f"Processing scene {i+1}: {scene}")
        prompt = f"{scene} Style: {style_prompt}."

        print(f"Generating image for scene {i+1}: {prompt}")
        
        scene_start_time = time.time()
        try:
            image_path = generate_image_with_retry(prompt, i, story_folder)
        except Exception as e:
            print(f"Failed to generate image for scene {i+1} after retries: {str(e)}", file=sys.stderr)
            default_image_path = os.path.join(BASE_DIR, "images/default_child_scene.png")
            if not os.path.exists(default_image_path):
                print(json.dumps({"status": "error", "message": "Failed to generate image and no default image available."}))
                sys.exit(1)
            image_path = os.path.join(story_folder, f"scene_{i+1}.png")
            with open(default_image_path, 'rb') as src, open(image_path, 'wb') as dst:
                dst.write(src.read())
            print(f"Using default image for scene {i+1}: {image_path}", file=sys.stderr)

        image_files.append(image_path)
        if i == 0:
            first_scene_image = f"generated_videos/{story_name}/scene_1.png"
            print(f"First scene image path set to: {first_scene_image}")

        print(f"Saved image: {image_path}")
        print(f"Image generation for scene {i+1} took {time.time() - scene_start_time} seconds")

    print("All images generated successfully!")

    audio_path = os.path.join(BASE_DIR, f"audio/{story_name}_audio.mp3")
    os.makedirs(os.path.join(BASE_DIR, "audio"), exist_ok=True)

    if len(story) > 500:
        print("Warning: The text will be truncated to meet the 500-character limit for TTS conversion.")
        tts_text = story[:500]
    else:
        tts_text = story

    tts_url = "https://api.topmediai.com/v1/text2speech"
    tts_payload = {
        "text": tts_text,
        "speaker": tts_speaker,
        "lang": tts_lang,
        "speed": 1.0,
        "pitch": 1.0,
        "volume": 1.0,
        "format": "mp3"
    }

    tts_headers = {
        "x-api-key": TOPMEDIA_API_KEY,
        "Content-Type": "application/json"
    }

    print("Converting text to speech using TopMedia...")
    tts_start_time = time.time()
    try:
        tts_response = requests.post(tts_url, headers=tts_headers, json=tts_payload)
        print("TTS Status Code:", tts_response.status_code)
        print("TTS Response JSON:", tts_response.json())

        tts_result = tts_response.json()
        audio_url = tts_result.get("data", {}).get("oss_url")
        if audio_url:
            audio_data = requests.get(audio_url)
            with open(audio_path, "wb") as f:
                f.write(audio_data.content)
            print(f"Audio narration saved: {audio_path}")
        else:
            print(json.dumps({"status": "error", "message": "Could not retrieve the audio URL from the response."}))
            sys.exit(1)
    except Exception as e:
        print(json.dumps({"status": "error", "message": f"Error during TTS conversion: {str(e)}"}))
        sys.exit(1)
    print(f"TTS conversion took {time.time() - tts_start_time} seconds")

    try:
        ffprobe_cmd = [
            FFPROBE_PATH, "-i", audio_path, "-show_entries", "format=duration", "-v", "quiet", "-of", "csv=p=0"
        ]
        audio_duration_output = subprocess.check_output(ffprobe_cmd, text=True, stderr=subprocess.STDOUT)
        audio_duration = float(audio_duration_output.strip())
        num_images = len(image_files)
        duration_per_image = audio_duration / num_images
        print(f"Audio Duration: {audio_duration} seconds, Duration per image: {duration_per_image} seconds")
    except subprocess.CalledProcessError as e:
        print(f"FFprobe error: {e.output}")
        print("Falling back to a default duration...")
        audio_duration = 10.0
        num_images = len(image_files)
        duration_per_image = audio_duration / num_images
    except Exception as e:
        print(f"Error calculating audio duration: {str(e)}")
        print("Falling back to a default duration...")
        audio_duration = 10.0
        num_images = len(image_files)
        duration_per_image = audio_duration / num_images

    video_path = os.path.join(BASE_DIR, f"videos/{story_name}_video.mp4")
    os.makedirs(os.path.join(BASE_DIR, "videos"), exist_ok=True)
    fps = 1 / duration_per_image
    ffmpeg_cmd_video = [
        FFMPEG_PATH, "-y", "-r", f"{fps}", "-i", os.path.join(story_folder, "scene_%d.png"),
        "-c:v", "libx264", "-vf", "fps=25,format=yuv420p", "-pix_fmt", "yuv420p", video_path
    ]
    print("Creating video from images...")
    ffmpeg_start_time = time.time()
    try:
        subprocess.run(ffmpeg_cmd_video, check=True)
        print(f"Video created successfully: {video_path}")
    except Exception as e:
        print(json.dumps({"status": "error", "message": f"Error creating video: {str(e)}"}))
        sys.exit(1)
    print(f"Video creation took {time.time() - ffmpeg_start_time} seconds")

    final_video_path = os.path.join(BASE_DIR, f"videos/{story_name}_final.mp4")
    ffmpeg_cmd_merge = [
        FFMPEG_PATH, "-y", "-i", video_path, "-i", audio_path, "-c:v", "copy",
        "-c:a", "aac", "-strict", "experimental", "-shortest", final_video_path
    ]
    print("Merging audio with video using ffmpeg...")
    ffmpeg_merge_start_time = time.time()
    try:
        result = subprocess.run(ffmpeg_cmd_merge, check=True, capture_output=True, text=True)
        print(f"Final video created: {final_video_path}")
        print(f"FFmpeg stdout: {result.stdout}")
        print(f"FFmpeg stderr: {result.stderr}")
    except subprocess.CalledProcessError as e:
        print(json.dumps({"status": "error", "message": f"Error merging audio with video: {e.stderr}"}))
        sys.exit(1)
    print(f"Video merging took {time.time() - ffmpeg_merge_start_time} seconds")

    print(f"audio/{story_name}_audio.mp3")
    print(f"videos/{story_name}_final.mp4")
    print(first_scene_image if first_scene_image else "No first scene image generated")

if __name__ == "__main__":
    main()