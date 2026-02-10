<?php

namespace App\Domain\Events;

interface DomainEvent
{
    public function eventName(): string;

    public function occurredAt(): string;

    public function payload(): array;
}
