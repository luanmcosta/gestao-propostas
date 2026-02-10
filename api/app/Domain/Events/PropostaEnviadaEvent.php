<?php

namespace App\Domain\Events;

class PropostaEnviadaEvent implements DomainEvent
{
    private string $timestamp;

    public function __construct(
        private int $propostaId,
        private int $clienteId,
        private int $versao,
        private string $actor
    ) {
        $this->timestamp = date(DATE_ATOM);
    }

    public function eventName(): string
    {
        return 'domain.proposta.enviada';
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
            'versao' => $this->versao,
            'actor' => $this->actor,
            'occurred_at' => $this->timestamp,
        ];
    }
}
