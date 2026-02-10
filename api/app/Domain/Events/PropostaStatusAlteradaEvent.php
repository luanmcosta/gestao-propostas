<?php

namespace App\Domain\Events;

class PropostaStatusAlteradaEvent implements DomainEvent
{
    private string $timestamp;

    public function __construct(
        private int $propostaId,
        private int $clienteId,
        private string $fromStatus,
        private string $toStatus,
        private int $versao,
        private string $actor
    ) {
        $this->timestamp = date(DATE_ATOM);
    }

    public function eventName(): string
    {
        return 'domain.proposta.status_alterada';
    }

    public function occurredAt(): string
    {
        return $this->timestamp;
    }

    public function payload(): array
    {
        return [
            'proposta_id' => $this->propostaId,
            'cliente_id' => $this->clienteId,
            'from_status' => $this->fromStatus,
            'to_status' => $this->toStatus,
            'versao' => $this->versao,
            'actor' => $this->actor,
            'occurred_at' => $this->timestamp,
        ];
    }
}
