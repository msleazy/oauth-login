<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <style>
        body { font-family: sans-serif; display: flex; flex-direction: column;
               align-items: center; justify-content: center; height: 100vh; gap: 16px; }
        a { padding: 12px 28px; border-radius: 8px; color: white;
            text-decoration: none; font-size: 16px; }
        .discord { background: #5865F2; }
        .spotify { background: #1DB954; }
    </style>
</head>
<body>
    <h1>Iniciar sesión</h1>
    <a class="discord" href="{{ route('socialite.redirect', 'discord') }}">
        🎮 Iniciar sesión con Discord
    </a>
    <a class="spotify" href="{{ route('socialite.redirect', 'spotify') }}">
        🎵 Iniciar sesión con Spotify
    </a>
</body>
</html>
