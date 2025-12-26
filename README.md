# TBank ID PHP/Laravel

![TBank ID PHP](https://github.com/user-attachments/assets/e59c4b65-dcda-4d0b-8b98-4694a9bc3a82)

[![Latest Version](https://img.shields.io/github/v/release/tigusigalpa/tbank-id-php)](https://github.com/tigusigalpa/tbank-id-php/releases)
[![License](https://img.shields.io/github/license/tigusigalpa/tbank-id-php)](LICENSE)

PHP/Laravel пакет для интеграции с TBank ID (T-ID) (OAuth2 авторизация через аккаунт в Т-Банке, бывший Тинькофф-Банк).

**🌐 Язык:** Русский | [English](README-en.md)

## Возможности

- ✅ OAuth2 авторизация через TBank ID
- ✅ Получение информации о пользователе
- ✅ Обновление токенов (refresh token)
- ✅ Отзыв токенов (logout)
- ✅ Поддержка Laravel 8-12
- ✅ Простой и понятный API
- ✅ Полная типизация
- ✅ Facade для удобного использования

## Требования

- PHP 7.4 или выше
- Laravel 8.x, 9.x, 10.x, 11.x или 12.x
- Guzzle HTTP Client 7.x

## Установка

Установите пакет через Composer:

```bash
composer require tigusigalpa/tbank-id-php
```

Опубликуйте конфигурационный файл:

```bash
php artisan vendor:publish --tag=tbank-id-config
```

## Настройка

### 1. Получение учетных данных

Для использования TBank ID (T-ID) вам необходимо:

1. Подать [заявку на подключение к TID](https://developer.tbank.ru/docs/intro/partner/tid#tid)
2. Получить `client_id` и `client_secret` через менеджера

### 2. Переменные окружения

Добавьте следующие переменные в ваш `.env` файл:

```env
TBANK_ID_CLIENT_ID=your_client_id
TBANK_ID_CLIENT_SECRET=your_client_secret
TBANK_ID_REDIRECT_URI=https://your-domain.com/auth/tbank/callback
```

### 3. Конфигурация

Файл конфигурации `config/tbank-id.php`:

```php
return [
    'client_id' => env('TBANK_ID_CLIENT_ID', ''),
    'client_secret' => env('TBANK_ID_CLIENT_SECRET', ''),
    'redirect_uri' => env('TBANK_ID_REDIRECT_URI', ''),
    'scopes' => [
        'openid',
        'email',
        'phone',
    ],
];
```

## Использование

### Использование с обычным PHP (без Laravel)

#### Установка через Composer

```bash
composer require tigusigalpa/tbank-id-php
```

#### Базовый пример

```php
<?php

require_once 'vendor/autoload.php';

use Tigusigalpa\TBankID\TBankIDClient;
use Tigusigalpa\TBankID\Models\TBankUser;
use Tigusigalpa\TBankID\Exceptions\TBankIDException;

session_start();

$client = new TBankIDClient(
    'your_client_id',
    'your_client_secret',
    'https://your-domain.com/callback.php'
);

// Страница авторизации (index.php)
if (!isset($_SESSION['tbank_user'])) {
    $state = bin2hex(random_bytes(16));
    $_SESSION['tbank_state'] = $state;
    
    $authUrl = $client->getAuthorizationUrl(['openid', 'email', 'phone'], $state);
    
    echo '<a href="' . htmlspecialchars($authUrl) . '">Войти через TBank ID</a>';
    exit;
}

// Пользователь авторизован
$user = new TBankUser($_SESSION['tbank_user']);
echo 'Привет, ' . htmlspecialchars($user->getName());
```

#### Обработка callback

```php
<?php
// callback.php

require_once 'vendor/autoload.php';

use Tigusigalpa\TBankID\TBankIDClient;
use Tigusigalpa\TBankID\Exceptions\TBankIDException;

session_start();

$client = new TBankIDClient(
    'your_client_id',
    'your_client_secret',
    'https://your-domain.com/callback.php'
);

$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;
$sessionState = $_SESSION['tbank_state'] ?? null;

if (!$code) {
    die('Ошибка: код авторизации не получен');
}

if (!$client->verifyState($state, $sessionState)) {
    die('Ошибка: проверка безопасности не пройдена');
}

try {
    // Получаем токен
    $tokenData = $client->getAccessToken($code);
    
    // Сохраняем токены
    $_SESSION['tbank_access_token'] = $tokenData['access_token'];
    $_SESSION['tbank_refresh_token'] = $tokenData['refresh_token'] ?? null;
    
    // Получаем информацию о пользователе
    $userInfo = $client->getUserInfo($tokenData['access_token']);
    $_SESSION['tbank_user'] = $userInfo;
    
    // Очищаем state
    unset($_SESSION['tbank_state']);
    
    // Перенаправляем на главную
    header('Location: index.php');
    exit;
    
} catch (TBankIDException $e) {
    die('Ошибка авторизации: ' . $e->getMessage());
}
```

#### Выход из системы

```php
<?php
// logout.php

require_once 'vendor/autoload.php';

use Tigusigalpa\TBankID\TBankIDClient;
use Tigusigalpa\TBankID\Exceptions\TBankIDException;

session_start();

$client = new TBankIDClient(
    'your_client_id',
    'your_client_secret',
    'https://your-domain.com/callback.php'
);

$accessToken = $_SESSION['tbank_access_token'] ?? null;

if ($accessToken) {
    try {
        $client->revokeToken($accessToken);
    } catch (TBankIDException $e) {
        // Логируем ошибку, но продолжаем выход
        error_log('Failed to revoke token: ' . $e->getMessage());
    }
}

// Очищаем сессию
unset($_SESSION['tbank_access_token']);
unset($_SESSION['tbank_refresh_token']);
unset($_SESSION['tbank_user']);

header('Location: index.php');
exit;
```

#### Обновление токена

```php
<?php

use Tigusigalpa\TBankID\TBankIDClient;
use Tigusigalpa\TBankID\Exceptions\TBankIDException;

session_start();

$client = new TBankIDClient(
    'your_client_id',
    'your_client_secret',
    'https://your-domain.com/callback.php'
);

$refreshToken = $_SESSION['tbank_refresh_token'] ?? null;

if ($refreshToken) {
    try {
        $newTokenData = $client->refreshAccessToken($refreshToken);
        
        $_SESSION['tbank_access_token'] = $newTokenData['access_token'];
        
        if (isset($newTokenData['refresh_token'])) {
            $_SESSION['tbank_refresh_token'] = $newTokenData['refresh_token'];
        }
        
        echo 'Токен успешно обновлен';
    } catch (TBankIDException $e) {
        echo 'Ошибка обновления токена: ' . $e->getMessage();
    }
}
```

#### Работа с данными пользователя

```php
<?php

use Tigusigalpa\TBankID\Models\TBankUser;

session_start();

if (isset($_SESSION['tbank_user'])) {
    $user = new TBankUser($_SESSION['tbank_user']);
    
    echo 'ID: ' . $user->getId() . '<br>';
    echo 'Email: ' . $user->getEmail() . '<br>';
    echo 'Телефон: ' . $user->getPhone() . '<br>';
    echo 'Имя: ' . $user->getName() . '<br>';
    echo 'Фамилия: ' . $user->getFamilyName() . '<br>';
    echo 'Отчество: ' . $user->getMiddleName() . '<br>';
    
    if ($user->isEmailVerified()) {
        echo 'Email подтвержден<br>';
    }
    
    if ($user->isPhoneVerified()) {
        echo 'Телефон подтвержден<br>';
    }
}
```

### Использование с Laravel

#### 1. Создайте маршруты

```php
// routes/web.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TBankAuthController;

Route::get('/auth/tbank', [TBankAuthController::class, 'redirect'])->name('tbank.login');
Route::get('/auth/tbank/callback', [TBankAuthController::class, 'callback'])->name('tbank.callback');
Route::post('/auth/tbank/logout', [TBankAuthController::class, 'logout'])->name('tbank.logout');
```

#### 2. Создайте контроллер

```php
// app/Http/Controllers/TBankAuthController.php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tigusigalpa\TBankID\Facades\TBankID;
use Tigusigalpa\TBankID\Exceptions\TBankIDException;

class TBankAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $state = bin2hex(random_bytes(16));
        $request->session()->put('tbank_state', $state);
        
        $authUrl = TBankID::getAuthorizationUrl(['openid', 'email', 'phone'], $state);
        
        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        $code = $request->get('code');
        $state = $request->get('state');
        $sessionState = $request->session()->get('tbank_state');

        if (!$code) {
            return redirect('/')->with('error', 'Авторизация отменена');
        }

        if (!TBankID::verifyState($state, $sessionState)) {
            return redirect('/')->with('error', 'Ошибка проверки безопасности');
        }

        try {
            $tokenData = TBankID::getAccessToken($code);
            
            $accessToken = $tokenData['access_token'];
            $refreshToken = $tokenData['refresh_token'] ?? null;
            
            $userInfo = TBankID::getUserInfo($accessToken);
            
            $request->session()->put('tbank_access_token', $accessToken);
            $request->session()->put('tbank_refresh_token', $refreshToken);
            $request->session()->put('tbank_user', $userInfo);
            
            return redirect('/dashboard')->with('success', 'Вы успешно авторизованы');
            
        } catch (TBankIDException $e) {
            return redirect('/')->with('error', 'Ошибка авторизации: ' . $e->getMessage());
        }
    }

    public function logout(Request $request)
    {
        $accessToken = $request->session()->get('tbank_access_token');
        
        if ($accessToken) {
            try {
                TBankID::revokeToken($accessToken);
            } catch (TBankIDException $e) {
                // Логируем ошибку, но продолжаем выход
            }
        }
        
        $request->session()->forget(['tbank_access_token', 'tbank_refresh_token', 'tbank_user']);
        
        return redirect('/')->with('success', 'Вы вышли из системы');
    }
}
```

### Использование с Dependency Injection

```php
use Tigusigalpa\TBankID\TBankIDClient;

class AuthService
{
    protected TBankIDClient $tbankClient;

    public function __construct(TBankIDClient $tbankClient)
    {
        $this->tbankClient = $tbankClient;
    }

    public function authenticate(string $code): array
    {
        return $this->tbankClient->getAccessToken($code);
    }
}
```

### Использование модели TBankUser

```php
use Tigusigalpa\TBankID\Facades\TBankID;
use Tigusigalpa\TBankID\Models\TBankUser;

$accessToken = session('tbank_access_token');
$userInfo = TBankID::getUserInfo($accessToken);
$user = new TBankUser($userInfo);

// Получение данных пользователя
$userId = $user->getId();
$email = $user->getEmail();
$phone = $user->getPhone();
$name = $user->getName();
$givenName = $user->getGivenName();
$familyName = $user->getFamilyName();
$middleName = $user->getMiddleName();

// Проверка верификации
$isEmailVerified = $user->isEmailVerified();
$isPhoneVerified = $user->isPhoneVerified();

// Получение всех данных
$allData = $user->toArray();
```

### Обновление токена

```php
use Tigusigalpa\TBankID\Facades\TBankID;

$refreshToken = session('tbank_refresh_token');

try {
    $newTokenData = TBankID::refreshAccessToken($refreshToken);
    
    session()->put('tbank_access_token', $newTokenData['access_token']);
    
    if (isset($newTokenData['refresh_token'])) {
        session()->put('tbank_refresh_token', $newTokenData['refresh_token']);
    }
} catch (\Exception $e) {
    // Обработка ошибки
}
```

### Middleware для защиты маршрутов

```php
// app/Http/Middleware/TBankAuthenticated.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TBankAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->session()->has('tbank_access_token')) {
            return redirect()->route('tbank.login');
        }

        return $next($request);
    }
}
```

Зарегистрируйте middleware в `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    // ...
    'tbank.auth' => \App\Http\Middleware\TBankAuthenticated::class,
];
```

Используйте в маршрутах:

```php
Route::middleware(['tbank.auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/profile', [ProfileController::class, 'show']);
});
```

## API Методы

### TBankIDClient

#### `getAuthorizationUrl(array $scopes = ['openid', 'email', 'phone'], ?string $state = null): string`

Генерирует URL для авторизации пользователя.

**Параметры:**

- `$scopes` - массив запрашиваемых разрешений (scopes)
- `$state` - строка для защиты от CSRF атак (генерируется автоматически, если не указана)

**Возвращает:** URL для редиректа пользователя

#### `getAccessToken(string $code): array`

Обменивает authorization code на access token.

**Параметры:**

- `$code` - код авторизации, полученный после редиректа

**Возвращает:** массив с токенами:

```php
[
    'access_token' => 'string',
    'token_type' => 'Bearer',
    'expires_in' => 3600,
    'refresh_token' => 'string',
    'scope' => 'openid email phone'
]
```

#### `refreshAccessToken(string $refreshToken): array`

Обновляет access token используя refresh token.

**Параметры:**

- `$refreshToken` - refresh token

**Возвращает:** массив с новыми токенами

#### `getUserInfo(string $accessToken): array`

Получает информацию о пользователе.

**Параметры:**

- `$accessToken` - действующий access token

**Возвращает:** массив с данными пользователя:

```php
[
    'sub' => 'user_id',
    'email' => 'user@example.com',
    'email_verified' => true,
    'phone_number' => '+79001234567',
    'phone_number_verified' => true,
    'name' => 'Иван Иванов',
    'given_name' => 'Иван',
    'family_name' => 'Иванов',
    'middle_name' => 'Иванович',
    'preferred_username' => 'ivan'
]
```

#### `revokeToken(string $token, string $tokenTypeHint = 'access_token'): bool`

Отзывает токен (выход из системы).

**Параметры:**

- `$token` - токен для отзыва
- `$tokenTypeHint` - тип токена ('access_token' или 'refresh_token')

**Возвращает:** `true` при успехе

#### `verifyState(string $receivedState, string $expectedState): bool`

Проверяет state параметр для защиты от CSRF.

**Параметры:**

- `$receivedState` - полученный state
- `$expectedState` - ожидаемый state

**Возвращает:** `true` если state совпадает

## Доступные Scopes

- `openid` - базовая информация (обязательный)
- `email` - адрес электронной почты
- `phone` - номер телефона
- `profile` - профиль пользователя (имя, фамилия и т.д.)

## Обработка ошибок

Все методы могут выбросить исключение `TBankIDException`:

```php
use Tigusigalpa\TBankID\Exceptions\TBankIDException;

try {
    $tokenData = TBankID::getAccessToken($code);
} catch (TBankIDException $e) {
    Log::error('TBank ID Error: ' . $e->getMessage());
    return redirect('/')->with('error', 'Ошибка авторизации');
}
```

## Безопасность

1. **Всегда используйте HTTPS** в production окружении
2. **Проверяйте state параметр** для защиты от CSRF атак
3. **Храните токены безопасно** (используйте encrypted session или database)
4. **Не храните client_secret** в публичном коде
5. **Используйте refresh tokens** для обновления access tokens
6. **Отзывайте токены** при выходе пользователя

## Тестирование

```bash
composer test
```

## Документация TBank ID

- [Официальная документация](https://developer.tbank.ru/docs/products/TID/web/)
- [Ссылочная авторизация](https://developer.tbank.ru/docs/products/TID/web/auth/link-auth)
- [Получение токена](https://developer.tbank.ru/docs/products/TID/web/token)
- [API методы](https://developer.tbank.ru/docs/api/t-id-informatsiya-o-polzovatele)

## Примеры интеграции

### Создание пользователя в базе данных

```php
public function callback(Request $request)
{
    // ... получение токена и информации о пользователе ...
    
    $user = User::updateOrCreate(
        ['tbank_id' => $userInfo['sub']],
        [
            'email' => $userInfo['email'] ?? null,
            'phone' => $userInfo['phone_number'] ?? null,
            'name' => $userInfo['name'] ?? null,
            'email_verified_at' => $userInfo['email_verified'] ? now() : null,
        ]
    );
    
    auth()->login($user);
    
    return redirect('/dashboard');
}
```

### Использование с API Resources

```php
use Illuminate\Http\Resources\Json\JsonResource;

class TBankUserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->getId(),
            'email' => $this->getEmail(),
            'phone' => $this->getPhone(),
            'name' => $this->getName(),
            'verified' => [
                'email' => $this->isEmailVerified(),
                'phone' => $this->isPhoneVerified(),
            ],
        ];
    }
}
```

## Changelog

### [1.0.0] - 2025-01-01

**Added**

- OAuth2 авторизация через TBank ID
- Получение информации о пользователе
- Обновление токенов (refresh token)
- Отзыв токенов (logout)
- Поддержка Laravel 8-12
- Facade для удобного использования
- Модель TBankUser для работы с данными пользователя
- Комплексная документация на русском и английском
- Защита от CSRF с проверкой state параметра
- Полная типизация и обработка ошибок
- Примеры для обычного PHP и Laravel

## Безопасность

### Рекомендации по безопасности

1. **Всегда используйте HTTPS** в production окружении
2. **Проверяйте state параметр** для защиты от CSRF атак
3. **Храните токены безопасно** (используйте encrypted session или database)
4. **Не храните client_secret** в публичном коде
5. **Используйте refresh tokens** для обновления access tokens
6. **Отзывайте токены** при выходе пользователя
7. **Обновляйте зависимости** регулярно
8. **Валидируйте пользовательский ввод** перед обработкой
9. **Используйте переменные окружения** для конфиденциальных данных
10. **Включите rate limiting** на эндпоинтах авторизации

### Важные замечания

- Access токены должны рассматриваться как конфиденциальные данные
- Refresh токены должны храниться безопасно и быть зашифрованы
- State параметр должен проверяться в callback для защиты от CSRF
- Время истечения токенов должно соблюдаться, токены обновляться при необходимости
- Неудачные попытки авторизации должны логироваться и мониториться

### Сообщение об уязвимостях

Если вы обнаружили уязвимость безопасности в этом пакете, пожалуйста, отправьте email на sovletig@gmail.com. Все
уязвимости будут оперативно устранены.

Пожалуйста, не раскрывайте проблему публично до тех пор, пока она не будет устранена разработчиками.

## Поддержка

- GitHub
  Issues: [https://github.com/tigusigalpa/tbank-id-php/issues](https://github.com/tigusigalpa/tbank-id-php/issues)
- Email: sovletig@gmail.com
- Telegram: [@igoravel](https://t.me/igoravel)

## Лицензия

MIT License. Подробности в файле [LICENSE](LICENSE).

## Автор

**Igor Sazonov**

- GitHub: [@tigusigalpa](https://github.com/tigusigalpa)
- Email: sovletig@gmail.com
- Telegram: [@igoravel](https://t.me/igoravel)

## Contributing

Pull requests приветствуются! Для крупных изменений, пожалуйста, сначала откройте issue для обсуждения.
