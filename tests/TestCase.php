<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests;

use Illuminate\Foundation\Application;
use Laravel\Sanctum\SanctumServiceProvider;
use NetCode\Bus\Laravel\BusServiceProvider;
use NetCode\Domain\Laravel\DomainServiceProvider;
use NetCode\Identity\Infrastructure\DataAccess\Models\UserModel;
use NetCode\Identity\Laravel\IdentityServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;

abstract class TestCase extends Orchestra
{
    /** @param Application $app */
    protected function getPackageProviders($app): array
    {
        return [
            SanctumServiceProvider::class,
            LaravelDataServiceProvider::class,
            DomainServiceProvider::class,
            BusServiceProvider::class,
            IdentityServiceProvider::class,
        ];
    }

    /** @param Application $app */
    protected function defineEnvironment($app): void
    {
        $config = $app['config'];

        $config->set('database.default', 'pgsql');
        $config->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => (int) env('DB_PORT', 5432),
            'database' => env('DB_DATABASE', 'testing'),
            'username' => env('DB_USERNAME', 'test'),
            'password' => env('DB_PASSWORD', 'test'),
            'charset' => 'utf8',
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ]);

        $config->set('auth.guards.sanctum', ['driver' => 'sanctum', 'provider' => 'users']);
        $config->set('auth.providers.users.model', UserModel::class);
        $config->set('sanctum.guard', []);
    }
}
