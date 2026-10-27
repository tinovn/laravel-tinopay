<?php

namespace TinoPay\TinoPay\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use TinoPay\TinoPay\TinoPayServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [TinoPayServiceProvider::class];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('tinopay.webhook_token', 'secret-token');
        config()->set('tinopay.pattern', 'TP');
        config()->set('tinopay.url', 'https://pay.tino.test/');

        $migration = include __DIR__.'/../database/migrations/create_tinopay_table.php.stub';
        $migration->up();
    }
}
