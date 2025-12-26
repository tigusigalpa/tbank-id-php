<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Tigusigalpa\TBankID\TBankIDClient;
use Tigusigalpa\TBankID\Exceptions\TBankIDException;

session_start();

$client = new TBankIDClient(
    'your_client_id',
    'your_client_secret',
    'https://your-domain.com/callback.php'
);

$refreshToken = $_SESSION['tbank_refresh_token'] ?? null;

if (!$refreshToken) {
    die('Refresh token не найден. Необходимо авторизоваться заново.');
}

try {
    $newTokenData = $client->refreshAccessToken($refreshToken);
    
    $_SESSION['tbank_access_token'] = $newTokenData['access_token'];
    $_SESSION['tbank_token_expires_at'] = time() + ($newTokenData['expires_in'] ?? 3600);
    
    if (isset($newTokenData['refresh_token'])) {
        $_SESSION['tbank_refresh_token'] = $newTokenData['refresh_token'];
    }
    
    $userInfo = $client->getUserInfo($newTokenData['access_token']);
    $_SESSION['tbank_user'] = $userInfo;
    
    echo 'Токен успешно обновлен!<br>';
    echo '<a href="index.php">Вернуться на главную</a>';
    
} catch (TBankIDException $e) {
    unset($_SESSION['tbank_access_token']);
    unset($_SESSION['tbank_refresh_token']);
    unset($_SESSION['tbank_token_expires_at']);
    unset($_SESSION['tbank_user']);
    
    die('Ошибка обновления токена: ' . htmlspecialchars($e->getMessage()) . 
        '<br><a href="index.php">Авторизоваться заново</a>');
}
