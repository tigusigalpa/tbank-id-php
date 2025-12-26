<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tigusigalpa\TBankID\Facades\TBankID;
use Tigusigalpa\TBankID\Exceptions\TBankIDException;

class TBankAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->session()->has('tbank_access_token')) {
            return redirect()->route('tbank.login')
                ->with('error', 'Please authenticate with TBank ID');
        }

        $expiresAt = $request->session()->get('tbank_token_expires_at');
        
        if ($expiresAt && now()->isAfter($expiresAt)) {
            $refreshToken = $request->session()->get('tbank_refresh_token');
            
            if (!$refreshToken) {
                $request->session()->forget([
                    'tbank_access_token',
                    'tbank_refresh_token',
                    'tbank_token_expires_at',
                    'tbank_user',
                ]);
                
                return redirect()->route('tbank.login')
                    ->with('error', 'Session expired. Please login again.');
            }

            try {
                $newTokenData = TBankID::refreshAccessToken($refreshToken);
                
                $request->session()->put('tbank_access_token', $newTokenData['access_token']);
                $request->session()->put('tbank_token_expires_at', now()->addSeconds($newTokenData['expires_in'] ?? 3600));
                
                if (isset($newTokenData['refresh_token'])) {
                    $request->session()->put('tbank_refresh_token', $newTokenData['refresh_token']);
                }
            } catch (TBankIDException $e) {
                $request->session()->forget([
                    'tbank_access_token',
                    'tbank_refresh_token',
                    'tbank_token_expires_at',
                    'tbank_user',
                ]);
                
                return redirect()->route('tbank.login')
                    ->with('error', 'Session expired. Please login again.');
            }
        }

        return $next($request);
    }
}
