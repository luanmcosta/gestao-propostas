<?php

namespace App\Domain\Events;

class ClienteCadastradoEvent implements DomainEvent
{
    private string $timestamp;

    public function __construct(
        private int $clienteId,
        private string $nome,
        private string $email,
        private string $documento,
        private string $actor
    ) {
        $this->timestamp = date(DATE_ATOM);
    }

    public function eventName(): string
    {
        return 'domain.cliente.cadastrado';
    }

    public function occurredAt(): string
    {
        return $this->timestamp;
    }

    public function payload(): array
    {
        return [
            'cliente_id' => $this->clienteId,
            'nome' => $this->nome,
            'email' => $this->email,
            'documento' => $this->documento,
            'actor' => $this->actor,
            'occurred_at' => $this->timestamp,
        ];
    }
}
