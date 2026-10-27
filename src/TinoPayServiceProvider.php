<?php

namespace TinoPay\TinoPay;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TinoPayServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-tinopay')
            ->hasConfigFile('tinopay')
            ->hasRoute('api')
            ->hasViews('tinopay')
            ->hasMigration('create_tinopay_table');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(TinoPay::class);
    }
}
