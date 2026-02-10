<?php

namespace App\Services;

use App\Domain\Events\DomainEvent;
use CodeIgniter\Events\Events;

class DomainEventPublisher
{
    public function publish(DomainEvent $event): void
    {
        Events::trigger('domain.event', $event);
        Events::trigger($event->eventName(), $event);
    }
}
