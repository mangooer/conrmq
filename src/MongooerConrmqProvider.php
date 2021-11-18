<?php

namespace Mongooer\Conrmq;

use Illuminate\Support\ServiceProvider;

class MongooerConrmqProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // 发布配置文件
        $this->publishes([
            __DIR__ . '/../config/mongooer_conrmq.php' => config_path('mongooer_conrmq.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('mongooerConMq', function ($app) {
            return new MqConnectionManager($app['config']);
        });
    }
}
