# OAuth 2.0 con Laravel Socialite — Discord & Spotify

> **Asignatura:** Seguridad en Aplicaciones Web · 2026  
> **Autor:** Morales Ramírez Mariano

Implementación práctica del flujo **Authorization Code de OAuth 2.0** en Laravel 12 utilizando Laravel Socialite con dos proveedores externos: **Discord** y **Spotify**.

---

## Descripción

Esta aplicación permite a los usuarios autenticarse mediante sus cuentas de Discord o Spotify, sin necesidad de crear credenciales adicionales. Implementa correctamente el protocolo OAuth 2.0 y OpenID Connect mediante el paquete Laravel Socialite junto con los providers de la comunidad `socialiteproviders/discord` y `socialiteproviders/spotify`.

---

## Requisitos

- PHP 8.2+
- Laravel 12
- Composer
- MariaDB / MySQL
- Cuenta de desarrollador en [Discord Developer Portal](https://discord.com/developers/applications)
- Cuenta de desarrollador en [Spotify for Developers](https://developer.spotify.com/dashboard)

---

## Instalación

### 1. Clonar el repositorio e instalar dependencias

```bash
git clone <url-del-repositorio>
cd <nombre-del-proyecto>
composer install
```

### 2. Instalar los providers de Socialite

```bash
composer require laravel/socialite
composer require socialiteproviders/discord
composer require socialiteproviders/spotify
```

### 3. Configurar la base de datos

Crear la base de datos y el usuario en MariaDB:

```sql
CREATE DATABASE oauth_login;
CREATE USER 'laraveluser'@'localhost' IDENTIFIED BY 'password123';
GRANT ALL PRIVILEGES ON oauth_login.* TO 'laraveluser'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4. Configurar las variables de entorno

Copiar el archivo de ejemplo y editarlo:

```bash
cp .env.example .env
php artisan key:generate
```

Añadir las siguientes variables al archivo `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=oauth_login
DB_USERNAME=laraveluser
DB_PASSWORD=password123

DISCORD_CLIENT_ID=tu_discord_client_id
DISCORD_CLIENT_SECRET=tu_discord_client_secret
DISCORD_REDIRECT_URI=http://localhost:8000/auth/discord/callback

SPOTIFY_CLIENT_ID=tu_spotify_client_id
SPOTIFY_CLIENT_SECRET=tu_spotify_client_secret
SPOTIFY_REDIRECT_URI=http://127.0.0.1:8000/auth/spotify/callback
```

### 5. Configurar los servicios en `config/services.php`

```php
'discord' => [
    'client_id'     => env('DISCORD_CLIENT_ID'),
    'client_secret' => env('DISCORD_CLIENT_SECRET'),
    'redirect'      => env('DISCORD_REDIRECT_URI'),
],

'spotify' => [
    'client_id'     => env('SPOTIFY_CLIENT_ID'),
    'client_secret' => env('SPOTIFY_CLIENT_SECRET'),
    'redirect'      => env('SPOTIFY_REDIRECT_URI'),
],
```

### 6. Ejecutar las migraciones

```bash
php artisan migrate
```

La migración crea la tabla `users` con los campos necesarios para autenticación social:

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | bigint | Clave primaria |
| `name` | string | Nombre del usuario |
| `email` | string (nullable, unique) | Correo electrónico |
| `provider` | string | `discord` o `spotify` |
| `provider_id` | string | ID del usuario en el proveedor |
| `avatar` | string (nullable) | URL del avatar |
| `timestamps` | — | `created_at` / `updated_at` |

### 7. Iniciar el servidor

```bash
php artisan serve
```

---

## Configuración de los Portales de Desarrolladores

### Discord

1. Ir a [Discord Developer Portal](https://discord.com/developers/applications) y crear una nueva aplicación.
2. En la sección **OAuth2**, copiar el **Client ID** y generar un **Client Secret**.
3. Registrar el Redirect URI: `http://localhost:8000/auth/discord/callback`

### Spotify

1. Ir a [Spotify for Developers Dashboard](https://developer.spotify.com/dashboard) y crear una nueva app.
2. Copiar el **Client ID** y el **Client Secret**.
3. Registrar el Redirect URI: `http://127.0.0.1:8000/auth/spotify/callback`

> **Importante:** El Redirect URI en el portal del proveedor debe coincidir exactamente con el valor definido en `.env`.

---

## Arquitectura del Proyecto

### Registro de Providers — `AppServiceProvider`

Los providers de Socialite se registran escuchando el evento `SocialiteWasCalled`:

```php
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Discord\DiscordExtendSocialite;
use SocialiteProviders\Spotify\SpotifyExtendSocialite;

protected $listen = [
    SocialiteWasCalled::class => [
        DiscordExtendSocialite::class,
        SpotifyExtendSocialite::class,
    ],
];
```

### Rutas — `routes/web.php`

```php
use App\Http\Controllers\Auth\SocialiteController;

Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])
    ->name('socialite.redirect');

Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])
    ->name('socialite.callback');

Route::get('/dashboard', function () {
    return 'Bienvenido, ' . auth()->user()->name;
})->middleware('auth');
```

### Controlador — `SocialiteController`

```php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function redirect(string $provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider)
    {
        $socialUser = Socialite::driver($provider)->user();

        $user = User::updateOrCreate(
            [
                'provider'    => $provider,
                'provider_id' => $socialUser->getId(),
            ],
            [
                'name'   => $socialUser->getName() ?? $socialUser->getNickname(),
                'email'  => $socialUser->getEmail(),
                'avatar' => $socialUser->getAvatar(),
            ]
        );

        Auth::login($user, remember: true);

        return redirect('/dashboard');
    }
}
```

### Modelo User — campos `$fillable`

```php
protected $fillable = [
    'name', 'email', 'provider', 'provider_id', 'avatar',
];
```

---

## Flujo OAuth 2.0 (Authorization Code)

```
Usuario → /auth/{provider}/redirect
        → Proveedor (Discord / Spotify): pantalla de consentimiento
        → /auth/{provider}/callback?code=...
        → Laravel intercambia code por access_token
        → Obtiene datos del usuario con el token
        → updateOrCreate en base de datos
        → Auth::login($user)
        → /dashboard
```

---

## Permisos Solicitados (Scopes)

### Discord
- Nombre de usuario y avatar
- Correo electrónico
- Cartel de perfil

### Spotify
- Nombre y nombre de usuario
- Imagen de perfil
- Seguidores y listas públicas

---

## Desafíos Técnicos Resueltos

| Problema | Solución |
|---|---|
| Tabla `sessions` faltante | Ejecutar `php artisan session:table && php artisan migrate` |
| Redirect URI no coincide | Sincronizar exactamente el URI entre `.env` y el portal del proveedor |
| Namespace incorrecto del controlador | Usar `App\Http\Controllers\Auth\SocialiteController` |

---

## Uso

1. Acceder a `http://localhost:8000`
2. Hacer clic en **"Iniciar sesión con Discord"** o **"Iniciar sesión con Spotify"**
3. Autorizar los permisos en la pantalla del proveedor
4. Ser redirigido al dashboard con el mensaje de bienvenida

---

## Tecnologías Utilizadas

- [Laravel 12](https://laravel.com/)
- [Laravel Socialite](https://laravel.com/docs/socialite)
- [socialiteproviders/discord](https://socialiteproviders.com/Discord/)
- [socialiteproviders/spotify](https://socialiteproviders.com/Spotify/)
- MariaDB
- OAuth 2.0 — Authorization Code Flow

---

## Licencia

Proyecto académico — Seguridad en Aplicaciones Web · 2026
