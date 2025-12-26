<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Tigusigalpa\TBankID\TBankIDClient;
use Tigusigalpa\TBankID\Models\TBankUser;

session_start();

$client = new TBankIDClient(
    'your_client_id',
    'your_client_secret',
    'https://your-domain.com/callback.php'
);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TBank ID - Авторизация</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .auth-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #FFDD2D;
            color: #000;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
        }
        .user-info {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .logout-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <h1>TBank ID - Пример авторизации</h1>
    
    <?php if (!isset($_SESSION['tbank_user'])): ?>
        <p>Для доступа к сервису необходимо авторизоваться через TBank ID.</p>
        <?php
            $state = bin2hex(random_bytes(16));
            $_SESSION['tbank_state'] = $state;
            $authUrl = $client->getAuthorizationUrl(['openid', 'email', 'phone'], $state);
        ?>
        <a href="<?= htmlspecialchars($authUrl) ?>" class="auth-button">
            Войти через TBank ID
        </a>
    <?php else: ?>
        <?php $user = new TBankUser($_SESSION['tbank_user']); ?>
        <div class="user-info">
            <h2>Добро пожаловать!</h2>
            <p><strong>ID:</strong> <?= htmlspecialchars($user->getId()) ?></p>
            
            <?php if ($user->getName()): ?>
                <p><strong>Имя:</strong> <?= htmlspecialchars($user->getName()) ?></p>
            <?php endif; ?>
            
            <?php if ($user->getEmail()): ?>
                <p><strong>Email:</strong> <?= htmlspecialchars($user->getEmail()) ?>
                <?php if ($user->isEmailVerified()): ?>
                    ✓ (подтвержден)
                <?php endif; ?>
                </p>
            <?php endif; ?>
            
            <?php if ($user->getPhone()): ?>
                <p><strong>Телефон:</strong> <?= htmlspecialchars($user->getPhone()) ?>
                <?php if ($user->isPhoneVerified()): ?>
                    ✓ (подтвержден)
                <?php endif; ?>
                </p>
            <?php endif; ?>
            
            <?php if ($user->getGivenName()): ?>
                <p><strong>Имя:</strong> <?= htmlspecialchars($user->getGivenName()) ?></p>
            <?php endif; ?>
            
            <?php if ($user->getFamilyName()): ?>
                <p><strong>Фамилия:</strong> <?= htmlspecialchars($user->getFamilyName()) ?></p>
            <?php endif; ?>
            
            <?php if ($user->getMiddleName()): ?>
                <p><strong>Отчество:</strong> <?= htmlspecialchars($user->getMiddleName()) ?></p>
            <?php endif; ?>
        </div>
        
        <a href="logout.php" class="logout-button">Выйти</a>
    <?php endif; ?>
</body>
</html>
