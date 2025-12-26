<?php

namespace Tigusigalpa\TBankID;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Tigusigalpa\TBankID\Exceptions\TBankIDException;

class TBankIDClient
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $redirectUri;
    protected Client $httpClient;
    protected string $baseUrl = 'https://id.tbank.ru';
    protected string $apiUrl = 'https://id.tbank.ru/api/v1';

    public function __construct(string $clientId, string $clientSecret, string $redirectUri)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        $this->httpClient = new Client([
            'timeout' => 30,
            'verify' => true,
        ]);
    }

    public function getAuthorizationUrl(array $scopes = ['openid', 'email', 'phone'], ?string $state = null): string
    {
        $state = $state ?? bin2hex(random_bytes(16));
        
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'state' => $state,
        ];

        return $this->baseUrl . '/auth/authorize?' . http_build_query($params);
    }

    public function getAccessToken(string $code): array
    {
        try {
            $response = $this->httpClient->post($this->baseUrl . '/auth/token', [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $this->redirectUri,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new TBankIDException('Failed to parse token response');
            }

            return $data;
        } catch (GuzzleException $e) {
            throw new TBankIDException('Failed to get access token: ' . $e->getMessage(), 0, $e);
        }
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        try {
            $response = $this->httpClient->post($this->baseUrl . '/auth/token', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new TBankIDException('Failed to parse refresh token response');
            }

            return $data;
        } catch (GuzzleException $e) {
            throw new TBankIDException('Failed to refresh access token: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getUserInfo(string $accessToken): array
    {
        try {
            $response = $this->httpClient->get($this->apiUrl . '/userinfo', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new TBankIDException('Failed to parse user info response');
            }

            return $data;
        } catch (GuzzleException $e) {
            throw new TBankIDException('Failed to get user info: ' . $e->getMessage(), 0, $e);
        }
    }

    public function revokeToken(string $token, string $tokenTypeHint = 'access_token'): bool
    {
        try {
            $response = $this->httpClient->post($this->baseUrl . '/auth/revoke', [
                'form_params' => [
                    'token' => $token,
                    'token_type_hint' => $tokenTypeHint,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            throw new TBankIDException('Failed to revoke token: ' . $e->getMessage(), 0, $e);
        }
    }

    public function verifyState(string $receivedState, string $expectedState): bool
    {
        return hash_equals($expectedState, $receivedState);
    }

    public function setHttpClient(Client $client): void
    {
        $this->httpClient = $client;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }
}
