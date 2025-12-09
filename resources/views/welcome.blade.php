<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HURTSSPACE</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #000;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            text-align: center;
            max-width: 600px;
        }

        h1 {
            font-size: 3rem;
            font-weight: 700;
            letter-spacing: -1px;
            margin-bottom: 2rem;
            text-transform: uppercase;
        }

        .question {
            font-size: 1.25rem;
            line-height: 1.6;
            margin-bottom: 3rem;
            color: #999;
        }

        .highlight {
            color: #fff;
            font-weight: 500;
        }

        .cta-button {
            display: inline-block;
            padding: 16px 48px;
            background-color: #fff;
            color: #000;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 600;
            border: 2px solid #fff;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .cta-button:hover {
            background-color: #000;
            color: #fff;
        }

        .emoji-rain {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
            z-index: 1;
        }

        .emoji {
            position: absolute;
            font-size: 2rem;
            opacity: 0.7;
            animation: fall linear infinite;
        }

        @keyframes fall {
            from {
                transform: translateY(-100px) rotate(0deg);
            }
            to {
                transform: translateY(100vh) rotate(360deg);
            }
        }

        .container {
            position: relative;
            z-index: 10;
        }

        footer {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 0.85rem;
            color: #666;
            z-index: 10;
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 2rem;
            }

            .question {
                font-size: 1rem;
            }

            .cta-button {
                padding: 14px 36px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="emoji-rain" id="emojiRain"></div>

    <div class="container">
        <h1>HURTSSPACE</h1>

        <p class="question">
            What are you looking for?<br>
            <span class="highlight">Hurtsspace web</span> right?<br>
            Just click this button
        </p>

        <a href="https://hurtsspace.com" class="cta-button">hurtsspace.com</a>
    </div>

    <footer>
        © <span id="year"></span> Hurtsspace. All rights reserved.
    </footer>

    <script>
        document.getElementById('year').textContent = new Date().getFullYear();

        // Emoji rain animation
        const emojiRain = document.getElementById('emojiRain');
        const emojis = ['😝', '🤫', '🤔'];

        function createEmoji() {
            const emoji = document.createElement('div');
            emoji.className = 'emoji';
            emoji.textContent = emojis[Math.floor(Math.random() * emojis.length)];
            emoji.style.left = Math.random() * 100 + '%';
            emoji.style.top = '-100px';
            emoji.style.animationDuration = (Math.random() * 3 + 4) + 's';
            emoji.style.animationDelay = '0s';

            emojiRain.appendChild(emoji);

            setTimeout(() => {
                emoji.remove();
            }, 8000);
        }

        // Create emoji every 400ms
        setInterval(createEmoji, 400);

        // Create initial emojis
        for (let i = 0; i < 15; i++) {
            setTimeout(createEmoji, i * 200);
        }
    </script>
</body>
</html>
