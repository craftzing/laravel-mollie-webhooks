<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing;

use Craftzing\Laravel\MollieWebhooks\Exceptions\FakeExceptionHandler;
use Craftzing\Laravel\MollieWebhooks\MollieWebhooksServiceProvider;
use Craftzing\Laravel\MollieWebhooks\Testing\Concerns\FakesEvents;
use Craftzing\Laravel\MollieWebhooks\Testing\Concerns\FakesMollie;
use CreateWebhookCallsTable;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class IntegrationTestCase extends OrchestraTestCase
{
    use FakesEvents;
    use FakesMollie;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
        $this->app[Factory::class]->load(__DIR__ . '/Factories');
    }

    protected function setUpDatabase(): void
    {
        include_once __DIR__ .
            '/../../vendor/spatie/laravel-webhook-client/database/migrations/create_webhook_calls_table.php.stub';

        (new CreateWebhookCallsTable())->up();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app->useEnvironmentPath(__DIR__ . '/../../');
        $app->bootstrapWith([LoadEnvironmentVariables::class]);
        $this->setupMollieEnv($app);
    }

    protected function setUpTraits(): array
    {
        Bus::fake();
        Queue::fake();
        FakeExceptionHandler::swap($this->app);

        if ($this->shouldFakeMollie) {
            $this->fakeMollie();
        }

        if ($this->shouldFakeEvents) {
            $this->fakeEvents();
        }

        return parent::setUpTraits();
    }

    /**
     * @return array<string>
     */
    protected function getPackageProviders($app): array
    {
        return [MollieWebhooksServiceProvider::class];
    }

    /**
     * @param object $class
     * @return mixed
     */
    public function handle(object $class)
    {
        return $this->app->call([$class, 'handle']);
    }
}
