<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConnectionDebugTest extends TestCase
{
    public function test_check_database_connection(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        echo "\nCONNECTION: {$connection}\n";
        echo "DATABASE: {$database}\n";

        $this->assertEquals('sqlite', $connection);
        $this->assertEquals(':memory:', $database);
    }
}
