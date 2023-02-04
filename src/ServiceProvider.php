<?php

namespace Acelle\Baokim;

use Illuminate\Support\ServiceProvider as Base;
use Acelle\Library\Facades\Hook;
use Acelle\Library\Facades\Billing;
use Acelle\Baokim\Baokim;

class ServiceProvider extends Base
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Register views path
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'baokim');

        // Register routes file
        $this->loadRoutesFrom(__DIR__.'/../routes.php');

        // Register translation file
        $this->loadTranslationsFrom(storage_path('app/data/plugins/acelle/baokim/lang/'), 'baokim');

        // Register the translation file against Acelle translation management
        Hook::register('add_translation_file', function() {
            return [
                "id" => '#acelle/baokim_translation_file',
                "plugin_name" => "acelle/baokim",
                "file_title" => "Translation for acelle/baokim plugin",
                "translation_folder" => storage_path('app/data/plugins/acelle/baokim/lang/'),
                "file_name" => "messages.php",
                "master_translation_file" => realpath(__DIR__.'/../resources/lang/en/messages.php'),
            ];
        });

        // register payment
        $baokim = Baokim::initialize();
        if ($baokim->plugin->isActive()) {
            Billing::register(Baokim::GATEWAY, function() use ($baokim) {
                return $baokim->gateway;
            });
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }
}
