<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MedQ - Live Board</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'DM Sans', Arial, sans-serif; background: #12201c; color: #f3efe6; margin: 0; padding: 32px 24px; }
        h1 { font-family: 'Fraunces', Georgia, serif; font-weight: 600; font-size: 40px; text-align: center; margin: 0 0 28px; }
        #board { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; }
        .doc { width: 330px; padding: 24px; text-align: center; background: #1b2e29; border: 1px solid #2c443d; border-radius: 16px; }
        .doc .name { font-family: 'Fraunces', Georgia, serif; font-size: 24px; font-weight: 600; }
        .doc .room { color: #9db8af; margin-bottom: 14px; }
        .doc .label { font-size: 12px; letter-spacing: 0.14em; color: #9db8af; }
        .doc .current { font-family: 'Fraunces', Georgia, serif; font-size: 96px; font-weight: 600; line-height: 1.1; color: #8fd6bd; }
        .doc .next { font-size: 26px; margin-top: 10px; }
        .doc .avg { font-size: 14px; color: #9db8af; margin-top: 12px; }
        #updated { text-align: center; margin-top: 28px; color: #9db8af; }
    </style>
</head>
