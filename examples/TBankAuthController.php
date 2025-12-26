<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tigusigalpa\TBankID\Facades\TBankID;
use Tigusigalpa\TBankID\Exceptions\TBankIDException;
use Tigusigalpa\TBankID\Models\TBankUser;

class TBankAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $state = bin2hex(random_bytes(16));
        $request->session()->put('tbank_state', $state);
        
        $scopes = ['openid', 'email', 'phone'];
        $authUrl = TBankID::getAuthorizationUrl($scopes, $state);
        
        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        $code = $request->get('code');
        $state = $request->get('state');
        $error = $request->get('error');
        $sessionState = $request->session()->get('tbank_state');

        if ($error) {
            return redirect('/')->with('error', 'Authorization error: ' . $error);
        }

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
            $expiresIn = $tokenData['expires_in'] ?? 3600;
            
            $userInfo = TBankID::getUserInfo($accessToken);
            $user = new TBankUser($userInfo);
            
            $request->session()->put('tbank_access_token', $accessToken);
            $request->session()->put('tbank_refresh_token', $refreshToken);
            $request->session()->put('tbank_token_expires_at', now()->addSeconds($expiresIn));
            $request->session()->put('tbank_user', $userInfo);
            
            $dbUser = \App\Models\User::updateOrCreate(
                ['tbank_id' => $user->getId()],
                [
                    'email' => $user->getEmail(),
                    'phone' => $user->getPhone(),
                    'name' => $user->getName(),
                    'email_verified_at' => $user->isEmailVerified() ? now() : null,
                ]
            );
            
            auth()->login($dbUser);
            
            return redirect('/dashboard')->with('success', 'Successfully authenticated');
            
        } catch (TBankIDException $e) {
            \Log::error('TBank ID authentication error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect('/')->with('error', 'Authentication error. Please try again.');
        }
    }

    public function logout(Request $request)
    {
        $accessToken = $request->session()->get('tbank_access_token');
        
        if ($accessToken) {
            try {
                TBankID::revokeToken($accessToken);
            } catch (TBankIDException $e) {
                \Log::warning('Failed to revoke TBank token', [
                    'message' => $e->getMessage(),
                ]);
            }
        }
        
        $request->session()->forget([
            'tbank_access_token',
            'tbank_refresh_token',
            'tbank_token_expires_at',
            'tbank_user',
        ]);
        
        auth()->logout();
        
        return redirect('/')->with('success', 'Successfully logged out');
    }

    public function refresh(Request $request)
    {
        $refreshToken = $request->session()->get('tbank_refresh_token');
        
        if (!$refreshToken) {
            return redirect()->route('tbank.login');
        }

        try {
            $newTokenData = TBankID::refreshAccessToken($refreshToken);
            
            $request->session()->put('tbank_access_token', $newTokenData['access_token']);
            $request->session()->put('tbank_token_expires_at', now()->addSeconds($newTokenData['expires_in'] ?? 3600));
            
            if (isset($newTokenData['refresh_token'])) {
                $request->session()->put('tbank_refresh_token', $newTokenData['refresh_token']);
            }
            
            return response()->json(['success' => true]);
            
        } catch (TBankIDException $e) {
            $request->session()->forget([
                'tbank_access_token',
                'tbank_refresh_token',
                'tbank_token_expires_at',
                'tbank_user',
            ]);
            
            return response()->json(['success' => false, 'redirect' => route('tbank.login')], 401);
        }
    }
}
