# TBank ID PHP/Laravel

![TBank ID PHP](https://github.com/user-attachments/assets/e59c4b65-dcda-4d0b-8b98-4694a9bc3a82)

[![Latest Version](https://img.shields.io/github/v/release/tigusigalpa/tbank-id-php)](https://github.com/tigusigalpa/tbank-id-php/releases)
[![License](https://img.shields.io/github/license/tigusigalpa/tbank-id-php)](LICENSE)

PHP/Laravel package for TBank ID (T-ID) integration (OAuth2 authorization via TBank account, formerly Tinkoff Bank).

**🌐 Language:** English | [Русский](README.md)

## Features

- ✅ OAuth2 authorization via TBank ID
- ✅ User information retrieval
- ✅ Token refresh support
- ✅ Token revocation (logout)
- ✅ Laravel 8-12 support
- ✅ Simple and intuitive API
- ✅ Full type support
- ✅ Facade for convenient usage

## Requirements

- PHP 7.4 or higher
- Laravel 8.x, 9.x, 10.x, 11.x or 12.x
- Guzzle HTTP Client 7.x

## Installation

Install the package via Composer:

```bash
composer require tigusigalpa/tbank-id-php
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=tbank-id-config
```

## Configuration

### 1. Obtaining Credentials

To use TBank ID (T-ID) you need to:

1. Register at [TBank Developer Portal](https://developer.tbank.ru/)
2. Create an application and obtain `client_id` and `client_secret`

### 2. Environment Variables

Add the following variables to your `.env` file:

```env
TBANK_ID_CLIENT_ID=your_client_id
TBANK_ID_CLIENT_SECRET=your_client_secret
TBANK_ID_REDIRECT_URI=https://your-domain.com/auth/tbank/callback
```

### 3. Configuration File

Configuration file `config/tbank-id.php`:

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

## Usage

### Plain PHP Usage (without Laravel)

#### Installation via Composer

```bash
composer require tigusigalpa/tbank-id-php
```

#### Basic Example

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

// Authorization page (index.php)
if (!isset($_SESSION['tbank_user'])) {
    $state = bin2hex(random_bytes(16));
    $_SESSION['tbank_state'] = $state;
    
    $authUrl = $client->getAuthorizationUrl(['openid', 'email', 'phone'], $state);
    
    echo '<a href="' . htmlspecialchars($authUrl) . '">Login with TBank ID</a>';
    exit;
}

// User is authenticated
$user = new TBankUser($_SESSION['tbank_user']);
echo 'Hello, ' . htmlspecialchars($user->getName());
```

#### Callback Handling

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
    die('Error: authorization code not received');
}

if (!$client->verifyState($state, $sessionState)) {
    die('Error: security verification failed');
}

try {
    // Get token
    $tokenData = $client->getAccessToken($code);
    
    // Save tokens
    $_SESSION['tbank_access_token'] = $tokenData['access_token'];
    $_SESSION['tbank_refresh_token'] = $tokenData['refresh_token'] ?? null;
    
    // Get user information
    $userInfo = $client->getUserInfo($tokenData['access_token']);
    $_SESSION['tbank_user'] = $userInfo;
    
    // Clear state
    unset($_SESSION['tbank_state']);
    
    // Redirect to home
    header('Location: index.php');
    exit;
    
} catch (TBankIDException $e) {
    die('Authentication error: ' . $e->getMessage());
}
```

#### Logout

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
        // Log error but continue logout
        error_log('Failed to revoke token: ' . $e->getMessage());
    }
}

// Clear session
unset($_SESSION['tbank_access_token']);
unset($_SESSION['tbank_refresh_token']);
unset($_SESSION['tbank_user']);

header('Location: index.php');
exit;
```

#### Token Refresh

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
        
        echo 'Token successfully refreshed';
    } catch (TBankIDException $e) {
        echo 'Token refresh error: ' . $e->getMessage();
    }
}
```

#### Working with User Data

```php
<?php

use Tigusigalpa\TBankID\Models\TBankUser;

session_start();

if (isset($_SESSION['tbank_user'])) {
    $user = new TBankUser($_SESSION['tbank_user']);
    
    echo 'ID: ' . $user->getId() . '<br>';
    echo 'Email: ' . $user->getEmail() . '<br>';
    echo 'Phone: ' . $user->getPhone() . '<br>';
    echo 'Name: ' . $user->getName() . '<br>';
    echo 'Given Name: ' . $user->getGivenName() . '<br>';
    echo 'Family Name: ' . $user->getFamilyName() . '<br>';
    echo 'Middle Name: ' . $user->getMiddleName() . '<br>';
    
    if ($user->isEmailVerified()) {
        echo 'Email verified<br>';
    }
    
    if ($user->isPhoneVerified()) {
        echo 'Phone verified<br>';
    }
}
```

### Laravel Usage

#### 1. Create Routes

```php
// routes/web.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TBankAuthController;

Route::get('/auth/tbank', [TBankAuthController::class, 'redirect'])->name('tbank.login');
Route::get('/auth/tbank/callback', [TBankAuthController::class, 'callback'])->name('tbank.callback');
Route::post('/auth/tbank/logout', [TBankAuthController::class, 'logout'])->name('tbank.logout');
```

#### 2. Create Controller

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
            return redirect('/')->with('error', 'Authorization cancelled');
        }

        if (!TBankID::verifyState($state, $sessionState)) {
            return redirect('/')->with('error', 'Security verification failed');
        }

        try {
            $tokenData = TBankID::getAccessToken($code);
            
            $accessToken = $tokenData['access_token'];
            $refreshToken = $tokenData['refresh_token'] ?? null;
            
            $userInfo = TBankID::getUserInfo($accessToken);
            
            $request->session()->put('tbank_access_token', $accessToken);
            $request->session()->put('tbank_refresh_token', $refreshToken);
            $request->session()->put('tbank_user', $userInfo);
            
            return redirect('/dashboard')->with('success', 'Successfully authenticated');
            
        } catch (TBankIDException $e) {
            return redirect('/')->with('error', 'Authentication error: ' . $e->getMessage());
        }
    }

    public function logout(Request $request)
    {
        $accessToken = $request->session()->get('tbank_access_token');
        
        if ($accessToken) {
            try {
                TBankID::revokeToken($accessToken);
            } catch (TBankIDException $e) {
                // Log error but continue logout
            }
        }
        
        $request->session()->forget(['tbank_access_token', 'tbank_refresh_token', 'tbank_user']);
        
        return redirect('/')->with('success', 'Successfully logged out');
    }
}
```

### Using Dependency Injection

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

### Using TBankUser Model

```php
use Tigusigalpa\TBankID\Facades\TBankID;
use Tigusigalpa\TBankID\Models\TBankUser;

$accessToken = session('tbank_access_token');
$userInfo = TBankID::getUserInfo($accessToken);
$user = new TBankUser($userInfo);

// Get user data
$userId = $user->getId();
$email = $user->getEmail();
$phone = $user->getPhone();
$name = $user->getName();
$givenName = $user->getGivenName();
$familyName = $user->getFamilyName();
$middleName = $user->getMiddleName();

// Check verification status
$isEmailVerified = $user->isEmailVerified();
$isPhoneVerified = $user->isPhoneVerified();

// Get all data
$allData = $user->toArray();
```

### Refreshing Token

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
    // Handle error
}
```

### Middleware for Route Protection

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

Register middleware in `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    // ...
    'tbank.auth' => \App\Http\Middleware\TBankAuthenticated::class,
];
```

Use in routes:

```php
Route::middleware(['tbank.auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/profile', [ProfileController::class, 'show']);
});
```

## API Methods

### TBankIDClient

#### `getAuthorizationUrl(array $scopes = ['openid', 'email', 'phone'], ?string $state = null): string`

Generates URL for user authorization.

**Parameters:**

- `$scopes` - array of requested permissions (scopes)
- `$state` - string for CSRF protection (auto-generated if not provided)

**Returns:** URL for user redirect

#### `getAccessToken(string $code): array`

Exchanges authorization code for access token.

**Parameters:**

- `$code` - authorization code received after redirect

**Returns:** array with tokens:

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

Refreshes access token using refresh token.

**Parameters:**

- `$refreshToken` - refresh token

**Returns:** array with new tokens

#### `getUserInfo(string $accessToken): array`

Retrieves user information.

**Parameters:**

- `$accessToken` - valid access token

**Returns:** array with user data:

```php
[
    'sub' => 'user_id',
    'email' => 'user@example.com',
    'email_verified' => true,
    'phone_number' => '+79001234567',
    'phone_number_verified' => true,
    'name' => 'Ivan Ivanov',
    'given_name' => 'Ivan',
    'family_name' => 'Ivanov',
    'middle_name' => 'Ivanovich',
    'preferred_username' => 'ivan'
]
```

#### `revokeToken(string $token, string $tokenTypeHint = 'access_token'): bool`

Revokes token (logout).

**Parameters:**

- `$token` - token to revoke
- `$tokenTypeHint` - token type ('access_token' or 'refresh_token')

**Returns:** `true` on success

#### `verifyState(string $receivedState, string $expectedState): bool`

Verifies state parameter for CSRF protection.

**Parameters:**

- `$receivedState` - received state
- `$expectedState` - expected state

**Returns:** `true` if state matches

## Available Scopes

- `openid` - basic information (required)
- `email` - email address
- `phone` - phone number
- `profile` - user profile (name, surname, etc.)

## Error Handling

All methods may throw `TBankIDException`:

```php
use Tigusigalpa\TBankID\Exceptions\TBankIDException;

try {
    $tokenData = TBankID::getAccessToken($code);
} catch (TBankIDException $e) {
    Log::error('TBank ID Error: ' . $e->getMessage());
    return redirect('/')->with('error', 'Authentication error');
}
```

## Security

1. **Always use HTTPS** in production environment
2. **Verify state parameter** for CSRF protection
3. **Store tokens securely** (use encrypted session or database)
4. **Don't store client_secret** in public code
5. **Use refresh tokens** to update access tokens
6. **Revoke tokens** on user logout

## Testing

```bash
composer test
```

## TBank ID Documentation

- [Official Documentation](https://developer.tbank.ru/docs/products/TID/web/)
- [Link Authorization](https://developer.tbank.ru/docs/products/TID/web/auth/link-auth)
- [Token Retrieval](https://developer.tbank.ru/docs/products/TID/web/token)
- [API Methods](https://developer.tbank.ru/docs/api/t-id-informatsiya-o-polzovatele)

## Integration Examples

### Creating User in Database

```php
public function callback(Request $request)
{
    // ... get token and user info ...
    
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

### Using with API Resources

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

- OAuth2 authorization via TBank ID
- User information retrieval
- Token refresh support
- Token revocation (logout)
- Laravel 8-12 support
- Facade for convenient usage
- TBankUser model for user data handling
- Comprehensive documentation in Russian and English
- CSRF protection with state parameter verification
- Full type support and error handling
- Examples for plain PHP and Laravel

## Security

### Security Best Practices

1. **Always use HTTPS** in production environments
2. **Verify state parameter** to prevent CSRF attacks
3. **Store tokens securely** using encrypted sessions or secure database storage
4. **Never commit** `client_secret` to version control
5. **Use refresh tokens** to obtain new access tokens
6. **Revoke tokens** when users log out
7. **Keep dependencies updated** regularly
8. **Validate all user input** before processing
9. **Use environment variables** for sensitive configuration
10. **Enable rate limiting** on authentication endpoints

### Security Considerations

- Access tokens should be treated as sensitive data
- Refresh tokens should be stored securely and encrypted
- State parameter must be validated on callback to prevent CSRF
- Token expiration should be respected and tokens refreshed when needed
- Failed authentication attempts should be logged and monitored

### Reporting a Vulnerability

If you discover a security vulnerability within this package, please send an email to Igor Sazonov at
sovletig@gmail.com. All security vulnerabilities will be promptly addressed.

Please do not publicly disclose the issue until it has been addressed by the maintainers.

## Support

- GitHub
  Issues: [https://github.com/tigusigalpa/tbank-id-php/issues](https://github.com/tigusigalpa/tbank-id-php/issues)
- Email: sovletig@gmail.com
- Telegram: [@igoravel](https://t.me/igoravel)

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Author

**Igor Sazonov**

- GitHub: [@tigusigalpa](https://github.com/tigusigalpa)
- Email: sovletig@gmail.com
- Telegram: [@igoravel](https://t.me/igoravel)

## Contributing

Pull requests are welcome! For major changes, please open an issue first to discuss what you would like to change.

---

Made with ❤️ by [Igor Sazonov](https://github.com/tigusigalpa)
