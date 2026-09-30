<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // Laravel mocks console output with Mockery, which is not installed.
        $this->mockConsoleOutput = false;

        parent::setUp();
    }
}
