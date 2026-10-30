<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application and verifies test database isolation.
     */
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        static::assertTestingEnvironmentSafe($app);

        return $app;
    }

    /**
     * Setup the test environment with pre-trait database safety validation.
     */
    protected function setUp(): void
    {
        if (! $this->app) {
            $this->refreshApplication();
        }

        static::assertTestingEnvironmentSafe($this->app);

        parent::setUp();
    }

    /**
     * Enforce strict isolation to prevent tests from ever targeting the development database.
     */
    public static function assertTestingEnvironmentSafe(Application $app): void
    {
        // 1. Guard against cached configuration in testing environment
        if ($app->configurationIsCached()) {
            throw new RuntimeException(
                'FATAL SAFETY VIOLATION: Configuration is cached while running tests! '.
                'Cached configuration overrides phpunit.xml / .env.testing and risks destroying development data. '.
                "Run 'php artisan config:clear' before running tests."
            );
        }

        // 2. Guard against non-testing environment
        if ($app->environment() !== 'testing') {
            throw new RuntimeException(
                "FATAL SAFETY VIOLATION: APP_ENV is '{$app->environment()}', expected 'testing'! ".
                'Aborting test execution immediately.'
            );
        }

        // 3. Guard against non-testing database targeting
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");

        if ($database !== 'testing') {
            throw new RuntimeException(
                "FATAL SAFETY VIOLATION: Tests are targeting database '{$database}' on connection '{$connection}' instead of 'testing'! ".
                'Aborting test execution immediately to protect application data.'
            );
        }
    }
}
