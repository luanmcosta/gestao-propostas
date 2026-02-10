<?php

namespace App\Services;

use App\Models\PropostaAuditoriaModel;

class PropostaAuditService
{
    public function log(int $propostaId, string $actor, string $evento, array $payload = []): void
    {
        $model = new PropostaAuditoriaModel();

        $model->insert([
            'proposta_id' => $propostaId,
            'actor' => $actor,
            'evento' => $evento,
            'payload' => $payload === [] ? null : json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
