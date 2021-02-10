<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Concerns;

use Closure;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Testing\Fakes\EventFake;

trait FakesEvents
{
    protected ?EventFake $eventFake = null;
    protected bool $shouldFakeEvents = true;

    protected function fakeEvents(): void
    {
        $this->eventFake = Event::fake($this->eventsToFake ?? []);
    }

    protected function dontFakeEvents(): void
    {
        if (! $this->eventFake) {
            return;
        }

        $rebindOriginalDispatcher = Closure::bind(function (EventFake $eventFake): void {
            Event::swap($eventFake->dispatcher);
        }, null, EventFake::class);

        $rebindOriginalDispatcher($this->eventFake);
    }
}
