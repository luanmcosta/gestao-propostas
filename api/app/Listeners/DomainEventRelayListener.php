<?php

namespace App\Listeners;

use App\Domain\Events\DomainEvent;

class DomainEventRelayListener
{
    public function handle(DomainEvent $event): void
    {
        // Placeholder for future integrations (emails, webhooks, queues, etc.)
        // The listener receives a normalized domain event with full payload.
    }
}
