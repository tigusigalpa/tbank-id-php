<?php

namespace Tigusigalpa\TBankID;

use Illuminate\Support\ServiceProvider;

class TBankIDServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/tbank-id.php',
            'tbank-id'
        );

        $this->app->singleton(TBankIDClient::class, function ($app) {
            $config = $app['config']['tbank-id'];

            return new TBankIDClient(
                $config['client_id'],
                $config['client_secret'],
                $config['redirect_uri']
            );
        });

        $this->app->alias(TBankIDClient::class, 'tbank-id');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/tbank-id.php' => config_path('tbank-id.php'),
            ], 'tbank-id-config');
        }
    }

    public function provides(): array
    {
        return [TBankIDClient::class, 'tbank-id'];
    }
}
