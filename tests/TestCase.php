<?php

namespace Tests;

use App\Models\SmsSetting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // The SMS settings row is memoised statically for performance; clear it
        // between tests so each test starts from a clean, isolated state.
        SmsSetting::flushCache();
    }
}
