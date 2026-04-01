<?php

namespace Ahmed3bead\Settings;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/settings.php', 'settings');

        $this->app->bind('settings', function () {
            $settingsConfig = config(
                sprintf('settings.repositories.%s', config('settings.default'))
            );

            $cacheRepository = Cache::store(
                config('settings.cache.store')
            );

            $settingsRepository = new $settingsConfig['handler'];

            return new Settings($settingsRepository, $cacheRepository);
        });
    }

    /**
     * Boot registered package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/settings.php' => config_path('settings.php'),
            ], 'config');

            if (! $this->migrationExists()) {
                $this->publishes([
                    __DIR__.'/../database/migrations/create_settings_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_settings_table.php'),
                ], 'migrations');
            }
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return ['settings'];
    }

    /**
     * Determine whether the settings migration has already been published.
     */
    protected function migrationExists(): bool
    {
        return count(glob(database_path('migrations/*_create_settings_table.php'))) > 0;
    }
}
