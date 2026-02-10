<?php

namespace App\Services;

use App\Models\IdempotencyKeyModel;

class IdempotencyService
{
    private IdempotencyKeyModel $model;

    public function __construct(?IdempotencyKeyModel $model = null)
    {
        $this->model = $model ?? new IdempotencyKeyModel();
    }

    public function buildHash(array $payload): string
    {
        $normalized = $this->normalize($payload);
        $json = json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash('sha256', $json ?: '');
    }

    public function find(string $scope, string $key): ?array
    {
        return $this->model->where(['scope' => $scope, 'idempotency_key' => $key])->first();
    }

    public function remember(string $scope, string $key, string $requestHash, int $statusCode, array $body): void
    {
        $this->model->insert([
            'scope' => $scope,
            'idempotency_key' => $key,
            'request_hash' => $requestHash,
            'response_code' => $statusCode,
            'response_body' => json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function normalize(array $data): array
    {
        $normalized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $normalized[$key] = $this->normalize($value);
                continue;
            }
            $normalized[$key] = $value;
        }

        ksort($normalized);

        return $normalized;
    }
}
