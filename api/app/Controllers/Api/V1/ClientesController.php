<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Domain\Events\ClienteCadastradoEvent;
use App\Models\ClienteModel;
use App\Services\DomainEventPublisher;
use CodeIgniter\API\ResponseTrait;
use OpenApi\Annotations as OA;

class ClientesController extends BaseController
{
    use ResponseTrait;

    private DomainEventPublisher $events;

    public function __construct()
    {
        $this->format = 'json';
        $this->events = service('domainEventPublisher');
    }

    /**
     * @OA\Post(
     *   path="/api/v1/clientes",
     *   tags={"Clientes"},
     *   summary="Cria um cliente",
     *   @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ClienteInput")),
     *   @OA\Response(response=201, description="Cliente criado", @OA\JsonContent(ref="#/components/schemas/Cliente")),
     *   @OA\Response(response=400, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];

        $rules = [
            'nome' => 'required|min_length[2]',
            'email' => 'required|valid_email|is_unique[clientes.email]',
            'documento' => 'required|documento',
        ];

        if (! $this->validateData($payload, $rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $model = new ClienteModel();
        $id = $model->insert([
            'nome' => $payload['nome'],
            'email' => $payload['email'],
            'documento' => $payload['documento'],
        ], true);

        $cliente = $model->find($id);
        $this->events->publish(new ClienteCadastradoEvent(
            (int) $cliente['id'],
            (string) $cliente['nome'],
            (string) $cliente['email'],
            (string) $cliente['documento'],
            $this->actor()
        ));

        return $this->respondCreated($cliente);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/clientes/{id}",
     *   tags={"Clientes"},
     *   summary="Detalha um cliente",
     *   @OA\Parameter(ref="#/components/parameters/id"),
     *   @OA\Response(response=200, description="Cliente", @OA\JsonContent(ref="#/components/schemas/Cliente")),
     *   @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function show(int $id)
    {
        $model = new ClienteModel();
        $cliente = $model->find($id);

        if (! $cliente) {
            return $this->failNotFound('Cliente nao encontrado.');
        }

        return $this->respond($cliente);
    }

    private function actor(): string
    {
        $actor = $this->request->getHeaderLine('X-Actor');

        return $actor !== '' ? $actor : 'system';
    }
}
