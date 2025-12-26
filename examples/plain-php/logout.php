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

$accessToken = $_SESSION['tbank_access_token'] ?? null;

if ($accessToken) {
    try {
        $client->revokeToken($accessToken);
    } catch (TBankIDException $e) {
        error_log('Failed to revoke TBank token: ' . $e->getMessage());
    }
}

unset($_SESSION['tbank_access_token']);
unset($_SESSION['tbank_refresh_token']);
unset($_SESSION['tbank_token_expires_at']);
unset($_SESSION['tbank_user']);

session_destroy();

header('Location: index.php');
exit;
