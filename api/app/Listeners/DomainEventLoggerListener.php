<?php

namespace App\Listeners;

use App\Domain\Events\DomainEvent;

class DomainEventLoggerListener
{
    public function handle(DomainEvent $event): void
    {
        log_message('info', '[domain-event] {event} {payload}', [
            'event' => $event->eventName(),
            'payload' => json_encode($event->payload(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
