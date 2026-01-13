<?php

namespace VendWeave\Gateway\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use VendWeave\Gateway\VendWeaveServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            VendWeaveServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'VendWeave' => \VendWeave\Gateway\Facades\VendWeave::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('vendweave.api_key', 'test-api-key');
        $app['config']->set('vendweave.api_secret', 'test-api-secret');
        $app['config']->set('vendweave.store_id', 1);
        $app['config']->set('vendweave.endpoint', 'https://sandbox.pos.vendweave.com/api');
    }
}
