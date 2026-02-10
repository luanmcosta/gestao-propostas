<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\PropostaAuditoriaModel;
use App\Models\PropostaModel;
use App\Services\IdempotencyService;
use App\Services\PropostaAuditService;
use App\Services\PropostaService;
use CodeIgniter\API\ResponseTrait;
use OpenApi\Annotations as OA;

class PropostasController extends BaseController
{
    use ResponseTrait;

    private PropostaModel $propostas;
    private PropostaAuditService $audit;
    private PropostaService $service;
    private IdempotencyService $idempotency;

    public function __construct()
    {
        $this->format = 'json';
        $this->propostas = new PropostaModel();
        $this->audit = new PropostaAuditService();
        $this->service = new PropostaService();
        $this->idempotency = new IdempotencyService();
    }

    /**
     * @OA\Post(
     *   path="/api/v1/propostas",
     *   tags={"Propostas"},
     *   summary="Cria uma proposta",
     *   @OA\Parameter(ref="#/components/parameters/IdempotencyKey"),
     *   @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/PropostaInput")),
     *   @OA\Response(response=201, description="Proposta criada", @OA\JsonContent(ref="#/components/schemas/Proposta")),
     *   @OA\Response(response=400, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *   @OA\Response(response=409, description="Conflict", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        $key = $this->request->getHeaderLine('Idempotency-Key');

        if ($key === '') {
            return $this->failValidationErrors(['idempotency_key' => 'Idempotency-Key header is required.']);
        }

        $rules = [
            'cliente_id' => 'required|is_natural_no_zero|is_not_unique[clientes.id]',
            'produto' => 'required|min_length[2]',
            'valor_mensal' => 'required|decimal|greater_than[0]',
            'origem' => 'required|in_list[APP,SITE,API]',
        ];

        if (! $this->validateData($payload, $rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $scope = 'propostas:create';
        $hash = $this->idempotency->buildHash($payload);
        $existing = $this->idempotency->find($scope, $key);

        if ($existing) {
            if ($existing['request_hash'] !== $hash) {
                return $this->fail('Idempotency-Key already used with different payload.', 409);
            }

            $body = json_decode($existing['response_body'] ?? '[]', true) ?? [];
            return $this->respond($body, (int) $existing['response_code']);
        }

        $data = [
            'cliente_id' => (int) $payload['cliente_id'],
            'produto' => $payload['produto'],
            'valor_mensal' => $payload['valor_mensal'],
            'origem' => $payload['origem'],
            'status' => PropostaService::STATUS_DRAFT,
            'versao' => 1,
        ];

        $id = $this->propostas->insert($data, true);
        $proposta = $this->propostas->find($id);

        $this->audit->log($id, $this->actor(), PropostaService::EVENT_CREATED, [
            'status' => $proposta['status'],
            'origem' => $proposta['origem'],
        ]);

        $this->idempotency->remember($scope, $key, $hash, 201, $proposta);

        return $this->respondCreated($proposta);
    }

    /**
     * @OA\Patch(
     *   path="/api/v1/propostas/{id}",
     *   tags={"Propostas"},
     *   summary="Atualiza campos sensiveis",
     *   @OA\Parameter(ref="#/components/parameters/id"),
     *   @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/PropostaUpdate")),
     *   @OA\Response(response=200, description="Proposta atualizada", @OA\JsonContent(ref="#/components/schemas/Proposta")),
     *   @OA\Response(response=400, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *   @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *   @OA\Response(response=409, description="Conflict", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function update(int $id)
    {
        $payload = $this->request->getJSON(true) ?? [];

        $rules = [
            'versao' => 'required|is_natural_no_zero',
            'produto' => 'permit_empty|min_length[2]',
            'valor_mensal' => 'permit_empty|decimal|greater_than[0]',
            'origem' => 'permit_empty|in_list[APP,SITE,API]',
        ];

        if (! $this->validateData($payload, $rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $proposta = $this->propostas->find($id);
        if (! $proposta) {
            return $this->failNotFound('Proposta nao encontrada.');
        }

        if ((int) $payload['versao'] !== (int) $proposta['versao']) {
            return $this->fail('Version conflict.', 409);
        }

        $updates = array_intersect_key($payload, array_flip(['produto', 'valor_mensal', 'origem']));
        $updates = array_filter($updates, static fn ($value) => $value !== null && $value !== '');

        if ($updates === []) {
            return $this->respond($proposta);
        }

        $changes = [];
        foreach ($updates as $field => $value) {
            if ((string) $proposta[$field] !== (string) $value) {
                $changes[$field] = ['from' => $proposta[$field], 'to' => $value];
            }
        }

        if ($changes === []) {
            return $this->respond($proposta);
        }

        $updates['versao'] = (int) $proposta['versao'] + 1;
        $this->propostas->update($id, $updates);

        $this->audit->log($id, $this->actor(), PropostaService::EVENT_UPDATED_FIELDS, [
            'changes' => $changes,
        ]);

        $updated = $this->propostas->find($id);

        return $this->respond($updated);
    }

    /**
     * @OA\Post(
     *   path="/api/v1/propostas/{id}/submit",
     *   tags={"Propostas"},
     *   summary="Submete proposta",
     *   @OA\Parameter(ref="#/components/parameters/id"),
     *   @OA\Parameter(ref="#/components/parameters/IdempotencyKey"),
     *   @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/PropostaVersion")),
     *   @OA\Response(response=200, description="Proposta submetida", @OA\JsonContent(ref="#/components/schemas/Proposta")),
     *   @OA\Response(response=400, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *   @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *   @OA\Response(response=409, description="Conflict", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function submit(int $id)
    {
        return $this->changeStatus($id, PropostaService::STATUS_SUBMITTED, true);
    }

    /**
     * @OA\Post(
     *   path="/api/v1/propostas/{id}/approve",
     *   tags={"Propostas"},
     *   summary="Aprova proposta",
     *   @OA\Parameter(ref="#/components/parameters/id"),
     *   @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/PropostaVersion")),
     *   @OA\Response(response=200, description="Proposta aprovada", @OA\JsonContent(ref="#/components/schemas/Proposta")),
     *   @OA\Response(response=400, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *   @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *   @OA\Response(response=409, description="Conflict", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function approve(int $id)
    {
        return $this->changeStatus($id, PropostaService::STATUS_APPROVED, false);
    }

    /**
     * @OA\Post(
     *   path="/api/v1/propostas/{id}/reject",
     *   tags={"Propostas"},
     *   summary="Rejeita proposta",
     *   @OA\Parameter(ref="#/components/parameters/id"),
     *   @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/PropostaVersion")),
     *   @OA\Response(response=200, description="Proposta rejeitada", @OA\JsonContent(ref="#/components/schemas/Proposta")),
     *   @OA\Response(response=400, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *   @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *   @OA\Response(response=409, description="Conflict", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function reject(int $id)
    {
        return $this->changeStatus($id, PropostaService::STATUS_REJECTED, false);
    }

    /**
     * @OA\Post(
     *   path="/api/v1/propostas/{id}/cancel",
     *   tags={"Propostas"},
     *   summary="Cancela proposta",
     *   @OA\Parameter(ref="#/components/parameters/id"),
     *   @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/PropostaVersion")),
     *   @OA\Response(response=200, description="Proposta cancelada", @OA\JsonContent(ref="#/components/schemas/Proposta")),
     *   @OA\Response(response=400, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *   @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *   @OA\Response(response=409, description="Conflict", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function cancel(int $id)
    {
        return $this->changeStatus($id, PropostaService::STATUS_CANCELED, false);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/propostas/{id}",
     *   tags={"Propostas"},
     *   summary="Detalha uma proposta",
     *   @OA\Parameter(ref="#/components/parameters/id"),
     *   @OA\Response(response=200, description="Proposta", @OA\JsonContent(ref="#/components/schemas/Proposta")),
     *   @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function show(int $id)
    {
        $proposta = $this->propostas->findWithCliente($id);
        if (! $proposta) {
            return $this->failNotFound('Proposta nao encontrada.');
        }

        return $this->respond($proposta);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/propostas",
     *   tags={"Propostas"},
     *   summary="Lista propostas",
     *   @OA\Parameter(ref="#/components/parameters/status"),
     *   @OA\Parameter(ref="#/components/parameters/origem"),
     *   @OA\Parameter(ref="#/components/parameters/cliente_id"),
     *   @OA\Parameter(ref="#/components/parameters/produto"),
     *   @OA\Parameter(ref="#/components/parameters/min_valor"),
     *   @OA\Parameter(ref="#/components/parameters/max_valor"),
     *   @OA\Parameter(ref="#/components/parameters/created_from"),
     *   @OA\Parameter(ref="#/components/parameters/created_to"),
     *   @OA\Parameter(ref="#/components/parameters/sort"),
     *   @OA\Parameter(ref="#/components/parameters/direction"),
     *   @OA\Parameter(ref="#/components/parameters/page"),
     *   @OA\Parameter(ref="#/components/parameters/per_page"),
     *   @OA\Response(response=200, description="Lista de propostas", @OA\JsonContent(ref="#/components/schemas/PropostaList")),
     *   @OA\Response(response=400, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index()
    {
        $query = $this->request->getGet();
        $rules = [
            'status' => 'permit_empty|in_list[DRAFT,SUBMITTED,APPROVED,REJECTED,CANCELED]',
            'origem' => 'permit_empty|in_list[APP,SITE,API]',
            'cliente_id' => 'permit_empty|is_natural_no_zero',
            'produto' => 'permit_empty|max_length[150]',
            'min_valor' => 'permit_empty|decimal',
            'max_valor' => 'permit_empty|decimal',
            'created_from' => 'permit_empty|valid_date[Y-m-d]',
            'created_to' => 'permit_empty|valid_date[Y-m-d]',
            'sort' => 'permit_empty|in_list[created_at,valor_mensal,status,versao]',
            'direction' => 'permit_empty|in_list[asc,desc,ASC,DESC]',
            'page' => 'permit_empty|is_natural_no_zero',
            'per_page' => 'permit_empty|is_natural_no_zero',
        ];

        if (! $this->validateData($query, $rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        if (! empty($query['status'])) {
            $this->propostas->where('status', $query['status']);
        }
        if (! empty($query['origem'])) {
            $this->propostas->where('origem', $query['origem']);
        }
        if (! empty($query['cliente_id'])) {
            $this->propostas->where('cliente_id', (int) $query['cliente_id']);
        }
        if (! empty($query['produto'])) {
            $this->propostas->like('produto', $query['produto']);
        }
        if (! empty($query['min_valor'])) {
            $this->propostas->where('valor_mensal >=', (float) $query['min_valor']);
        }
        if (! empty($query['max_valor'])) {
            $this->propostas->where('valor_mensal <=', (float) $query['max_valor']);
        }
        if (! empty($query['created_from'])) {
            $this->propostas->where('created_at >=', $query['created_from'] . ' 00:00:00');
        }
        if (! empty($query['created_to'])) {
            $this->propostas->where('created_at <=', $query['created_to'] . ' 23:59:59');
        }

        $sort = $query['sort'] ?? 'created_at';
        $direction = strtolower($query['direction'] ?? 'desc');
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        $page = isset($query['page']) ? max(1, (int) $query['page']) : 1;
        $perPage = isset($query['per_page']) ? min(100, max(1, (int) $query['per_page'])) : 20;

        $this->propostas->orderBy($sort, $direction);
        $group = 'propostas';
        $data = $this->propostas->paginate($perPage, $group, $page);
        $pager = $this->propostas->pager;

        return $this->respond([
            'data' => $data,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $pager->getTotal($group),
                'page_count' => $pager->getPageCount($group),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/propostas/{id}/auditoria",
     *   tags={"Auditoria"},
     *   summary="Lista auditoria",
     *   @OA\Parameter(ref="#/components/parameters/id"),
     *   @OA\Parameter(ref="#/components/parameters/page"),
     *   @OA\Parameter(ref="#/components/parameters/per_page"),
     *   @OA\Response(response=200, description="Auditoria", @OA\JsonContent(ref="#/components/schemas/AuditoriaList")),
     *   @OA\Response(response=400, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *   @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function auditoria(int $id)
    {
        $proposta = $this->propostas->find($id);
        if (! $proposta) {
            return $this->failNotFound('Proposta nao encontrada.');
        }

        $query = $this->request->getGet();
        $rules = [
            'page' => 'permit_empty|is_natural_no_zero',
            'per_page' => 'permit_empty|is_natural_no_zero',
        ];

        if (! $this->validateData($query, $rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $page = isset($query['page']) ? max(1, (int) $query['page']) : 1;
        $perPage = isset($query['per_page']) ? min(100, max(1, (int) $query['per_page'])) : 20;

        $auditoriaModel = new PropostaAuditoriaModel();
        $auditoriaModel->where('proposta_id', $id)->orderBy('created_at', 'desc');
        $group = 'auditoria';
        $data = $auditoriaModel->paginate($perPage, $group, $page);
        $pager = $auditoriaModel->pager;

        return $this->respond([
            'data' => $data,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $pager->getTotal($group),
                'page_count' => $pager->getPageCount($group),
            ],
        ]);
    }

    /**
     * @OA\Delete(
     *   path="/api/v1/propostas/{id}",
     *   tags={"Propostas"},
     *   summary="Exclusao logica de proposta",
     *   @OA\Parameter(ref="#/components/parameters/id"),
     *   @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/PropostaDelete")),
     *   @OA\Response(response=200, description="Proposta excluida", @OA\JsonContent(ref="#/components/schemas/Proposta")),
     *   @OA\Response(response=400, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *   @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *   @OA\Response(response=409, description="Conflict", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function delete(int $id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        $rules = ['versao' => 'required|is_natural_no_zero'];

        if (! $this->validateData($payload, $rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $proposta = $this->propostas->withDeleted()->find($id);
        if (! $proposta) {
            return $this->failNotFound('Proposta nao encontrada.');
        }

        if (! empty($proposta['deleted_at'])) {
            return $this->failValidationErrors(['deleted_at' => 'Proposta ja excluida.']);
        }

        if ((int) $payload['versao'] !== (int) $proposta['versao']) {
            return $this->fail('Version conflict.', 409);
        }

        $this->propostas->update($id, [
            'deleted_at' => date('Y-m-d H:i:s'),
            'versao' => (int) $proposta['versao'] + 1,
        ]);

        $this->audit->log($id, $this->actor(), PropostaService::EVENT_DELETED_LOGICAL, [
            'status' => $proposta['status'],
        ]);

        $deleted = $this->propostas->withDeleted()->find($id);

        return $this->respond($deleted);
    }

    private function changeStatus(int $id, string $targetStatus, bool $requireIdempotency)
    {
        $payload = $this->request->getJSON(true) ?? [];
        $rules = ['versao' => 'required|is_natural_no_zero'];

        if (! $this->validateData($payload, $rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $key = $this->request->getHeaderLine('Idempotency-Key');
        if ($requireIdempotency && $key === '') {
            return $this->failValidationErrors(['idempotency_key' => 'Idempotency-Key header is required.']);
        }

        $proposta = $this->propostas->find($id);
        if (! $proposta) {
            return $this->failNotFound('Proposta nao encontrada.');
        }

        if ((int) $payload['versao'] !== (int) $proposta['versao']) {
            return $this->fail('Version conflict.', 409);
        }

        if ($this->service->isFinal($proposta['status'])) {
            return $this->failValidationErrors(['status' => 'Status final nao permite alteracao.']);
        }

        if (! $this->service->canTransition($proposta['status'], $targetStatus)) {
            return $this->failValidationErrors(['status' => 'Transicao de status invalida.']);
        }

        if ($requireIdempotency) {
            $scope = 'propostas:submit:' . $id;
            $hash = $this->idempotency->buildHash($payload);
            $existing = $this->idempotency->find($scope, $key);

            if ($existing) {
                if ($existing['request_hash'] !== $hash) {
                    return $this->fail('Idempotency-Key already used with different payload.', 409);
                }

                $body = json_decode($existing['response_body'] ?? '[]', true) ?? [];
                return $this->respond($body, (int) $existing['response_code']);
            }
        }

        $from = $proposta['status'];
        $this->propostas->update($id, [
            'status' => $targetStatus,
            'versao' => (int) $proposta['versao'] + 1,
        ]);

        $this->audit->log($id, $this->actor(), PropostaService::EVENT_STATUS_CHANGED, [
            'from' => $from,
            'to' => $targetStatus,
        ]);

        $updated = $this->propostas->find($id);

        if ($requireIdempotency) {
            $scope = 'propostas:submit:' . $id;
            $hash = $this->idempotency->buildHash($payload);
            $this->idempotency->remember($scope, $key, $hash, 200, $updated);
        }

        return $this->respond($updated);
    }

    private function actor(): string
    {
        $actor = $this->request->getHeaderLine('X-Actor');
        return $actor !== '' ? $actor : 'system';
    }
}
