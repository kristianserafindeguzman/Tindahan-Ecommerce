<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;

/** Refreshes the database between tests, skipping on SQLite where older MySQL-only migrations can't run and refusing to wipe anything but a *_test database. */
trait RefreshesTestDatabase
{
    use RefreshDatabase;

    /** Runs before any table is dropped, so a wrong database is caught before anything is lost. */
    protected function beforeRefreshingDatabase()
    {
        $connection = config('database.connections.' . config('database.default'));
        $name = (string) ($connection['database'] ?? '');

        if (($connection['driver'] ?? '') === 'sqlite') {
            $this->markTestSkipped('Needs a MySQL *_test database, e.g. DB_CONNECTION=mysql DB_DATABASE=tindahan_test.');
        }

        if (!str_ends_with($name, '_test')) {
            throw new RuntimeException("Refusing to refresh the '{$name}' database; point DB_DATABASE at a *_test database.");
        }
    }
}
