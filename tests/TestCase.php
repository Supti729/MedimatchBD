<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests don't need compiled CSS/JS, so they run without `npm run build` (e.g. in CI).
        $this->withoutVite();
    }
}
