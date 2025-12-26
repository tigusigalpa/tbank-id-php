<?php

namespace Tigusigalpa\TBankID\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Tigusigalpa\TBankID\TBankIDClient;
use Tigusigalpa\TBankID\Exceptions\TBankIDException;

class TBankIDClientTest extends TestCase
{
    protected TBankIDClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new TBankIDClient(
            'test_client_id',
            'test_client_secret',
            'https://example.com/callback'
        );
    }

    public function testGetAuthorizationUrl(): void
    {
        $url = $this->client->getAuthorizationUrl(['openid', 'email'], 'test_state');
        
        $this->assertStringContainsString('https://id.tbank.ru/auth/authorize', $url);
        $this->assertStringContainsString('client_id=test_client_id', $url);
        $this->assertStringContainsString('redirect_uri=https%3A%2F%2Fexample.com%2Fcallback', $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('scope=openid+email', $url);
        $this->assertStringContainsString('state=test_state', $url);
    }

    public function testGetAccessToken(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'access_token' => 'test_access_token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'refresh_token' => 'test_refresh_token',
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);
        $this->client->setHttpClient($httpClient);

        $result = $this->client->getAccessToken('test_code');

        $this->assertEquals('test_access_token', $result['access_token']);
        $this->assertEquals('Bearer', $result['token_type']);
        $this->assertEquals(3600, $result['expires_in']);
        $this->assertEquals('test_refresh_token', $result['refresh_token']);
    }

    public function testGetUserInfo(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'sub' => 'user123',
                'email' => 'test@example.com',
                'email_verified' => true,
                'phone_number' => '+79001234567',
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);
        $this->client->setHttpClient($httpClient);

        $result = $this->client->getUserInfo('test_access_token');

        $this->assertEquals('user123', $result['sub']);
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertTrue($result['email_verified']);
        $this->assertEquals('+79001234567', $result['phone_number']);
    }

    public function testVerifyState(): void
    {
        $state = 'test_state_123';
        
        $this->assertTrue($this->client->verifyState($state, $state));
        $this->assertFalse($this->client->verifyState($state, 'different_state'));
    }

    public function testGetClientId(): void
    {
        $this->assertEquals('test_client_id', $this->client->getClientId());
    }

    public function testGetRedirectUri(): void
    {
        $this->assertEquals('https://example.com/callback', $this->client->getRedirectUri());
    }
}
