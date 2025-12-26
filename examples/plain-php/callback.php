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

$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;
$error = $_GET['error'] ?? null;
$errorDescription = $_GET['error_description'] ?? null;
$sessionState = $_SESSION['tbank_state'] ?? null;

if ($error) {
    die('Ошибка авторизации: ' . htmlspecialchars($error) . 
        ($errorDescription ? ' - ' . htmlspecialchars($errorDescription) : ''));
}

if (!$code) {
    die('Ошибка: код авторизации не получен');
}

if (!$client->verifyState($state, $sessionState)) {
    die('Ошибка: проверка безопасности не пройдена (CSRF)');
}

try {
    $tokenData = $client->getAccessToken($code);
    
    $_SESSION['tbank_access_token'] = $tokenData['access_token'];
    $_SESSION['tbank_refresh_token'] = $tokenData['refresh_token'] ?? null;
    $_SESSION['tbank_token_expires_at'] = time() + ($tokenData['expires_in'] ?? 3600);
    
    $userInfo = $client->getUserInfo($tokenData['access_token']);
    $_SESSION['tbank_user'] = $userInfo;
    
    unset($_SESSION['tbank_state']);
    
    header('Location: index.php');
    exit;
    
} catch (TBankIDException $e) {
    die('Ошибка авторизации: ' . htmlspecialchars($e->getMessage()));
}
