import openai
import requests
import subprocess
import os
import time
import re
import spacy
import sys
import json
import argparse
from datetime import datetime
from dotenv import load_dotenv

# Force UTF-8 encoding for output to handle Arabic text
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

# Paths
BASE_DIR = "C:/xampp/htdocs/DreamScribeAi"
FFMPEG_PATH = "C:/ffmpeg-2025-03-17-git-5b9356f18e-essentials_build/bin/ffmpeg.exe"
FFPROBE_PATH = "C:/ffmpeg-2025-03-17-git-5b9356f18e-essentials_build/bin/ffprobe.exe"

# Validate FFmpeg and FFprobe paths
if not os.path.exists(FFMPEG_PATH):
    print(json.dumps({"status": "error", "message": f"FFmpeg not found at {FFMPEG_PATH}."}), file=sys.stdout)
    sys.exit(1)
if not os.path.exists(FFPROBE_PATH):
    print(json.dumps({"status": "error", "message": f"FFprobe not found at {FFPROBE_PATH}."}), file=sys.stdout)
    sys.exit(1)

# Set up OpenAI client with a timeout
client = openai.OpenAI(api_key=OPENAI_API_KEY, timeout=15)  # 15-second timeout for OpenAI requests

# Load spaCy models for English and Arabic
try:
    nlp_english = spacy.load("en_core_web_sm")
except Exception as e:
    print(json.dumps({"status": "error", "message": f"Error loading English spaCy model: {e}"}), file=sys.stdout)
    sys.exit(1)

try:
    nlp_arabic = spacy.load("xx_sent_ud_sm")
except Exception as e:
    print(json.dumps({"status": "error Editors", "message": f"Error loading Arabic spaCy model: {e}"}), file=sys.stdout)
    sys.exit(1)

# Create necessary directories with error handling
try:
    os.makedirs(os.path.join(BASE_DIR, "generated_videos"), exist_ok=True)
    os.makedirs(os.path.join(BASE_DIR, "audio"), exist_ok=True)
    os.makedirs(os.path.join(BASE_DIR, "videos"), exist_ok=True)
except PermissionError as e:
    print(json.dumps({"status": "error", "message": f"Permission denied while creating directories: {e}"}), file=sys.stdout)
    sys.exit(1)
except Exception as e:
    print(json.dumps({"status": "error", "message": f"Error creating directories: {e}"}), file=sys.stdout)
    sys.exit(1)

def log_time(step_name, start_time):
    """Log the time taken for a step."""
    end_time = time.time()
    duration = end_time - start_time
    print(f"Step '{step_name}' took {duration:.2f} seconds", file=sys.stderr)
    return end_time

def is_arabic(text):
    """Check if the text contains Arabic characters."""
    return bool(re.search(r'[\u0600-\u06FF]', text))

def extract_characters_and_themes(title, story, language):
    """Extract main characters and key themes using spaCy."""
    start_time = time.time()
    nlp = nlp_arabic if language.lower() == "arabic" else nlp_english
    text = f"{title}. {story}"
    doc = nlp(text)
    
    main_character = None
    for ent in doc.ents:
        if ent.label_ == "PERSON":
            main_character = ent.text
            break
    if not main_character:
        for token in doc:
            if token.pos_ == "PROPN":
                main_character = token.text
                break
    if not main_character:
        main_character = "a cute animated character"
    
    themes = []
    noun_counts = {}
    for token in doc:
        if token.pos_ == "NOUN" and token.text.lower() not in ["day", "time"]:
            noun_counts[token.text] = noun_counts.get(token.text, 0) + 1
    
    title_doc = nlp(title)
    title_nouns = {token.text.lower() for token in title_doc if token.pos_ == "NOUN"}
    for noun, count in noun_counts.items():
        if noun.lower() in title_nouns or count > 1:
            themes.append(noun)
    
    themes = themes[:2]
    if not themes:
        themes = ["magical setting"]
    
    print(f"Main character: {main_character}, Themes: {themes}", file=sys.stderr)
    log_time("Extract characters and themes", start_time)
    return main_character, themes

def generate_story(title, language, voice):
    """Generate a complete children's story within 500 characters using OpenAI."""
    start_time = time.time()
    prompt = (f"Generate a short, complete children's story (3-5 sentences) based on the title '{title}'. "
              f"The story must have a clear beginning, middle, and end, with a simple conflict or event that gets resolved. "
              f"End with a concise moral or conclusion (e.g., 'They learned kindness brings joy.'). "
              f"It should be narrated by a {voice} in {language}. "
              f"Keep the tone simple, creative, and suitable for young children. "
              f"The entire story must be within 500 characters, including spaces and punctuation.")
    
    print(f"Starting story generation for title: {title}, language: {language}, voice: {voice}", file=sys.stderr)
    
    max_attempts = 3
    for attempt in range(max_attempts):
        try:
            response = client.chat.completions.create(
                model="gpt-3.5-turbo",
                messages=[
                    {"role": "system", "content": "You are a creative storyteller for children."},
                    {"role": "user", "content": prompt}
                ],
                max_tokens=100,
                temperature=0.7
            )
            story = response.choices[0].message.content.strip()
            
            if len(story) > 500:
                print(f"Attempt {attempt + 1}: Story exceeds 500 characters ({len(story)}). Trimming...", file=sys.stderr)
                story = story[:500]
                last_period = story.rfind('.')
                if last_period != -1:
                    story = story[:last_period + 1]
                else:
                    story = story[:497] + "..."
            
            nlp = nlp_arabic if is_arabic(story) else nlp_english
            doc = nlp(story)
            sentences = [sent.text.strip() for sent in doc.sents if sent.text.strip()]
            if len(sentences) >= 3:
                print(f"Generated story ({len(story)} characters): {story}", file=sys.stderr)
                log_time("Generate story", start_time)
                return story
            else:
                print(f"Attempt {attempt + 1}: Story incomplete ({len(sentences)} sentences). Retrying...", file=sys.stderr)
        except Exception as e:
            print(json.dumps({"status": "error", "message": f"Error generating story: {str(e)}"}), file=sys.stdout)
            sys.exit(1)
    
    print(json.dumps({"status": "error", "message": "Failed to generate a complete story after multiple attempts."}), file=sys.stdout)
    sys.exit(1)

def split_into_scenes(story_text, language):
    """Split the story into scenes using the appropriate spaCy model."""
    start_time = time.time()
    nlp = nlp_arabic if language.lower() == "arabic" else nlp_english
    doc = nlp(story_text)
    scenes = [sent.text.strip() for sent in doc.sents if sent.text.strip()]
    print(f"Number of scenes detected: {len(scenes)}", file=sys.stderr)
    print(f"Scenes: {scenes}", file=sys.stderr)
    log_time("Split into scenes", start_time)
    return scenes

def generate_image(scene, story_name, scene_index, main_character, themes):
    """Generate an image for a scene using DALL-E with an improved prompt."""
    start_time = time.time()
    style_prompt = "animated style"
    scene_summary = scene[:50].replace(".", "").replace("'", "").lower()
    prompt = f"{main_character} {scene_summary}, {style_prompt}"
    print(f"Image generation prompt for scene {scene_index}: {prompt}", file=sys.stderr)
    
    try:
        response = client.images.generate(
            model="dall-e-3",
            prompt=prompt,
            n=1,
            size="1024x1024"
        )
        image_url = response.data[0].url
        image_path = os.path.join(BASE_DIR, "generated_videos", story_name, f"scene_{scene_index}.png")
        os.makedirs(os.path.dirname(image_path), exist_ok=True)
        
        image_content = requests.get(image_url, timeout=15).content  # 15-second timeout for image download
        with open(image_path, "wb") as f:
            f.write(image_content)
        
        relative_path = os.path.join("generated_videos", story_name, f"scene_{scene_index}.png").replace("\\", "/")
        print(f"Generated image: {relative_path}", file=sys.stderr)
        log_time(f"Generate image for scene {scene_index}", start_time)
        return relative_path
    except Exception as e:
        print(f"WARNING: Failed to generate image for scene {scene_index}: {str(e)}", file=sys.stderr)
        placeholder_path = os.path.join(BASE_DIR, "generated_videos", "placeholder.png")
        image_path = os.path.join(BASE_DIR, "generated_videos", story_name, f"scene_{scene_index}.png")
        os.makedirs(os.path.dirname(image_path), exist_ok=True)
        try:
            from PIL import Image
            if not os.path.exists(placeholder_path):
                img = Image.new('RGB', (1024, 1024), color='gray')
                img.save(placeholder_path)
                print(f"Created placeholder image at: {placeholder_path}", file=sys.stderr)
            import shutil
            shutil.copyfile(placeholder_path, image_path)
            relative_path = os.path.join("generated_videos", story_name, f"scene_{scene_index}.png").replace("\\", "/")
            print(f"Used placeholder image: {relative_path}", file=sys.stderr)
            log_time(f"Generate placeholder image for scene {scene_index}", start_time)
            return relative_path
        except Exception as e:
            print(json.dumps({"status": "error", "message": f"Failed to create or use placeholder image: {str(e)}"}), file=sys.stdout)
            sys.exit(1)

def generate_audio(story_text, story_name, voice, language):
    """Generate audio narration for the entire story using TopMedia."""
    start_time = time.time()
    tts_speaker = "5a1d0197-d183-11ef-a695-e86f38d7ec1a" if voice.lower() == "female" else "0012695e-3826-11ee-a861-00163e2ac61b"
    tts_lang = "ar" if is_arabic(story_text) else "en"
    
    tts_text = story_text[:500] if len(story_text) > 500 else story_text
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
    
    headers = {
        "x-api-key": TOPMEDIA_API_KEY,
        "Content-Type": "application/json"
    }
    
    try:
        response = requests.post(tts_url, json=tts_payload, headers=headers, timeout=15)  # 15-second timeout
        if response.status_code == 200:
            tts_result = response.json()
            audio_url = tts_result['data']['oss_url']
            audio_content = requests.get(audio_url, timeout=15).content  # 15-second timeout for audio download
            audio_path = os.path.join(BASE_DIR, "audio", f"{story_name}_audio.mp3")
            os.makedirs(os.path.dirname(audio_path), exist_ok=True)
            
            with open(audio_path, "wb") as f:
                f.write(audio_content)
            
            relative_path = os.path.join("audio", f"{story_name}_audio.mp3").replace("\\", "/")
            print(f"Generated audio: {relative_path}", file=sys.stderr)
            log_time("Generate audio", start_time)
            return relative_path
        else:
            raise Exception(f"TTS API Error: HTTP {response.status_code} - {response.text}")
    except Exception as e:
        print(json.dumps({"status": "error", "message": f"Error generating audio: {str(e)}"}), file=sys.stdout)
        sys.exit(1)

def get_audio_duration(audio_path):
    """Get the duration of an audio file using ffprobe."""
    start_time = time.time()
    if not os.path.exists(audio_path):
        raise FileNotFoundError(f"Audio file not found: {audio_path}")
    
    try:
        ffprobe_cmd = [
            FFPROBE_PATH, "-i", audio_path, "-show_entries", "format=duration", "-v", "quiet", "-of", "csv=p=0"
        ]
        result = subprocess.run(
            ffprobe_cmd,
            capture_output=True,
            text=True,
            check=True,
            timeout=15  # 15-second timeout for ffprobe
        )
        duration = float(result.stdout.strip())
        print(f"Audio duration: {duration} seconds", file=sys.stderr)
        log_time("Get audio duration", start_time)
        return duration
    except subprocess.CalledProcessError as e:
        print(json.dumps({"status": "error", "message": f"FFprobe command failed: {e.stderr}"}), file=sys.stdout)
        sys.exit(1)
    except Exception as e:
        print(json.dumps({"status": "error", "message": f"Error getting audio duration: {str(e)}"}), file=sys.stdout)
        sys.exit(1)

def create_final_video(image_paths, audio_path, story_name):
    """Create a single video with all images, each displayed for an equal portion of the audio duration."""
    start_time = time.time()
    video_path = os.path.join(BASE_DIR, "videos", f"{story_name}_video.mp4")
    final_video_path = os.path.join(BASE_DIR, "videos", f"{story_name}_final.mp4")
    os.makedirs(os.path.dirname(video_path), exist_ok=True)
    
    try:
        audio_duration = get_audio_duration(audio_path)
        num_images = len(image_paths)
        if num_images == 0:
            raise Exception("No images were generated successfully. Cannot create video.")
        duration_per_image = audio_duration / num_images
        
        fps = 1 / duration_per_image
        image_pattern = os.path.join(BASE_DIR, "generated_videos", story_name, "scene_%d.png")
        
        for i in range(1, num_images + 1):
            image_file = os.path.join(BASE_DIR, "generated_videos", story_name, f"scene_{i}.png")
            if not os.path.exists(image_file):
                print(json.dumps({"status": "error", "message": f"Image not found: {image_file}"}), file=sys.stdout)
                sys.exit(1)
            else:
                print(f"Found image: {image_file}", file=sys.stderr)
        
        if not os.path.exists(audio_path):
            print(json.dumps({"status": "error", "message": f"Audio file not found: {audio_path}"}), file=sys.stdout)
            sys.exit(1)
        else:
            print(f"Found audio: {audio_path}", file=sys.stderr)
        
        ffmpeg_cmd_video = [
            FFMPEG_PATH, "-y", "-r", f"{fps}", "-i", image_pattern,
            "-c:v", "libx264", "-vf", "fps=25,format=yuv420p", "-pix_fmt", "yuv420p", video_path
        ]
        print(f"Running FFmpeg command: {' '.join(ffmpeg_cmd_video)}", file=sys.stderr)
        result = subprocess.run(
            ffmpeg_cmd_video,
            capture_output=True,
            text=True,
            check=True,
            timeout=60  # 1-minute timeout for FFmpeg
        )
        if result.stderr:
            print(f"FFmpeg (video) stderr: {result.stderr}", file=sys.stderr)
        
        ffmpeg_cmd_merge = [
            FFMPEG_PATH, "-y", "-i", video_path, "-i", audio_path, "-c:v", "copy",
            "-c:a", "aac", "-strict", "experimental", "-shortest", final_video_path
        ]
        print(f"Running FFmpeg merge command: {' '.join(ffmpeg_cmd_merge)}", file=sys.stderr)
        result = subprocess.run(
            ffmpeg_cmd_merge,
            capture_output=True,
            text=True,
            check=True,
            timeout=30  # 30-second timeout for FFmpeg merge
        )
        if result.stderr:
            print(f"FFmpeg (merge) stderr: {result.stderr}", file=sys.stderr)
        
        relative_path = os.path.join("videos", f"{story_name}_final.mp4").replace("\\", "/")
        print(f"Generated final video: {relative_path}", file=sys.stderr)
        log_time("Create final video", start_time)
        return relative_path
    except subprocess.CalledProcessError as e:
        print(json.dumps({"status": "error", "message": f"FFmpeg command failed: {e.stderr}"}), file=sys.stdout)
        sys.exit(1)
    except Exception as e:
        print(json.dumps({"status": "error", "message": f"Error creating final video: {str(e)}"}), file=sys.stdout)
        sys.exit(1)

def main():
    start_time = time.time()
    parser = argparse.ArgumentParser(description="Generate a children's story and video.")
    parser.add_argument("--title", required=True, help="Title of the story")
    parser.add_argument("--language", required=True, help="Language of the story (English or Arabic)")
    parser.add_argument("--voice", required=True, help="Narrator's voice (Female or Male)")
    args = parser.parse_args()

    title = args.title
    language = args.language.capitalize()
    voice = args.voice.capitalize()

    if language not in ["Arabic", "English"]:
        language = "English"
    if voice not in ["Female", "Male"]:
        voice = "Female"

    story = generate_story(title, language, voice)

    if is_arabic(story):
        language = "Arabic"

    main_character, themes = extract_characters_and_themes(title, story, language)

    scenes = split_into_scenes(story, language)

    story_name = re.sub(r'[^a-zA-Z0-9]', '_', title[:20]) or "story_default"
    print(f"Story name for file paths: {story_name}", file=sys.stderr)

    # Limit to 1 scene for image generation to speed up execution
    image_paths = []
    for i, scene in enumerate(scenes[:1], 1):  # Changed to limit to 1 scene
        image_path = generate_image(scene, story_name, i, main_character, themes)
        if image_path:
            image_paths.append(image_path)

    audio_path_absolute = generate_audio(story, story_name, voice, language)
    audio_path = os.path.join(BASE_DIR, audio_path_absolute)

    final_video_path = create_final_video(image_paths, audio_path, story_name)

    output = {
        "status": "success",
        "story": story,
        "video_paths": [final_video_path],
        "audio_paths": [audio_path_absolute],
        "thumbnail_path": image_paths[0] if image_paths else "",
        "language": language,
        "voice": voice
    }

    print(json.dumps(output), file=sys.stdout)
    log_time("Total execution", start_time)

if __name__ == "__main__":
    main()