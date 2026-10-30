<?php

namespace Tests\Unit;

use RuntimeException;
use Tests\TestCase;

class DatabaseSafetyGuardTest extends TestCase
{
    public function test_testing_environment_safety_guard_passes_in_test_environment(): void
    {
        // Calling this should not throw an exception under proper test environment
        TestCase::assertTestingEnvironmentSafe($this->app);
        $this->assertTrue(true);
    }

    public function test_guard_throws_exception_if_database_is_not_testing(): void
    {
        $connection = $this->app['config']->get('database.default');
        $originalDb = $this->app['config']->get("database.connections.{$connection}.database");

        try {
            $this->app['config']->set("database.connections.{$connection}.database", 'pabasa');

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage("FATAL SAFETY VIOLATION: Tests are targeting database 'pabasa'");

            TestCase::assertTestingEnvironmentSafe($this->app);
        } finally {
            $this->app['config']->set("database.connections.{$connection}.database", $originalDb);
        }
    }

    public function test_testing_connection_uses_dedicated_pabasa_test_user(): void
    {
        $connection = $this->app['config']->get('database.default');
        $username = $this->app['config']->get("database.connections.{$connection}.username");

        $this->assertEquals('pabasa_test', $username);
    }
}
