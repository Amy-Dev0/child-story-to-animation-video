<div class="container">
        <div class="left-section">
            <h2>Enter Text:</h2>
            <div class="options">
                <div class="option">
                    <img src="language.jpg" alt="Choose Language">
                    <label for="language">Choose Language:</label>
                    <select id="language">
                        <option value="en">English</option>
                        <option value="ar">Arabic</option>
                    </select>
                </div>
                <div class="option">
                    <img src="narrator.jpg" alt="Choose Narrator's Voice">
                    <label for="voice">Choose Narrator’s Voice:</label>
                    <select id="voice">
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div class="option">
                    <img src="r.jpg" alt="AI Generation">
                    <p>AI generation - Wait a few minutes</p>
                    <button class="generate-btn" onclick="generateStory()">Generate Story</button>
                </div>
            </div>
        </div>

        <style>
        body {
            font-family: "Arial", sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f8f8;
        }
        .navbar {
            background-color: #003a53;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            font-size: 16px;
        }
        .navbar a:hover {
            text-decoration: underline;
        }
        .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 90%;
            margin: 50px auto;
        }
        .left-section {
            width: 40%;
            text-align: left;
        }
        .left-section h2 {
            font-size: 28px;
            margin-bottom: 20px;
        }
        .options {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .option {
            text-align: left;
        }
        .option img {
            width: 60px;
            height: 60px;
        }
        .option label, .option select {
            font-size: 14px;
            color: gray;
            display: block;
            margin-top: 5px;
        }
        .notebook {
            width: 55%;
            height: 400px;
            background: url('notebook.png') no-repeat center center;
            background-size: cover;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .notebook textarea {
            width: 100%;
            height: 100%;
            border: none;
            background: transparent;
            font-size: 18px;
            line-height: 1.8;
            padding: 15px;
            resize: none;
            outline: none;
        }
        .generate-btn {
            background-color: #003a53;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            border-radius: 5px;
        }
        .generate-btn:hover {
            background-color: #002940;
        }
    </style>
     


     <div class="content"> 
    <div class="image-container"> 
        <img src="end.jpg" alt="Space Image"> 
    </div> 
    <h1 style="text-align: center;">At The End We Hope That You Liked Our Website</h1>  
</div> 

<style> 
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            background-color: #f8f8f8; 
        } 
        header { 
            background-color: #004080;  
            color: white; 
            padding: 10px;  
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        } 
        header h1 { 
            font-size: 1.8em;  
            margin: 0;  
        } 
        nav { 
            margin: 10px 0; 
        } 
        nav a { 
            color: white; 
            margin: 0 15px; 
            text-decoration: none; 
        } 
        .content { 
            text-align: right;  
            margin: 50px; 
        } 
        .content h1 { 
            font-size: 2em; 
            color: #000000;  
            margin: 40px 0;  
            font-weight: bold;  
        } 
        .footer { 
            padding: 20px; 
            background-color: #f8f8f8; 
            color: black; 
            border-top: 1px solid #004080; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        } 
        .footer h1 { 
            margin: 0; 
            font-size: 1.5em; 
        } 
        .footer p { 
            margin: 0; 
            font-size: 1em; 
        } 
        .footer nav { 
            margin-left: auto; 
        } 
        .footer nav a { 
            color: black; 
            margin: 0 10px; 
            text-decoration: none; 
        } 
        .image-container { 
            text-align: center; 
            margin: 20px 0; 
        } 
        .image-container img { 
            max-width: 100%; 
            height: auto; 
        } 
    </style> 