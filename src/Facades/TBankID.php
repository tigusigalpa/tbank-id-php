<?php

namespace Tigusigalpa\TBankID\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string getAuthorizationUrl(array $scopes = ['openid', 'email', 'phone'], ?string $state = null)
 * @method static array getAccessToken(string $code)
 * @method static array refreshAccessToken(string $refreshToken)
 * @method static array getUserInfo(string $accessToken)
 * @method static bool revokeToken(string $token, string $tokenTypeHint = 'access_token')
 * @method static bool verifyState(string $receivedState, string $expectedState)
 * @method static string getClientId()
 * @method static string getRedirectUri()
 *
 * @see \Tigusigalpa\TBankID\TBankIDClient
 */
class TBankID extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'tbank-id';
    }
}
