<?php

namespace Tests;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests render real pages; they don't need built assets.
        $this->withoutVite();
    }

    /**
     * GET a path exactly as a browser sends it. Laravel's get() trims trailing
     * slashes, which would hide the category trailing-slash policy.
     */
    protected function getRaw(string $path): TestResponse
    {
        $request = Request::create(config('app.url').$path, 'GET');

        return TestResponse::fromBaseResponse($this->app->make(Kernel::class)->handle($request), $request);
    }
}
