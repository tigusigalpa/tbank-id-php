<?php

namespace Tigusigalpa\TBankID;

use Tigusigalpa\TBankID\Models\TBankUser;

class TBankIDManager
{
    protected TBankIDClient $client;

    public function __construct(TBankIDClient $client)
    {
        $this->client = $client;
    }

    public function redirect(array $scopes = null, ?string $state = null): string
    {
        $scopes = $scopes ?? config('tbank-id.scopes', ['openid', 'email', 'phone']);
        return $this->client->getAuthorizationUrl($scopes, $state);
    }

    public function handleCallback(string $code): array
    {
        return $this->client->getAccessToken($code);
    }

    public function user(string $accessToken): TBankUser
    {
        $userData = $this->client->getUserInfo($accessToken);
        return new TBankUser($userData);
    }

    public function refresh(string $refreshToken): array
    {
        return $this->client->refreshAccessToken($refreshToken);
    }

    public function logout(string $token, string $tokenType = 'access_token'): bool
    {
        return $this->client->revokeToken($token, $tokenType);
    }

    public function getClient(): TBankIDClient
    {
        return $this->client;
    }
}
