<?php

namespace Tests\Feature;

use App\Models\ClienteModel;
use App\Models\PropostaModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class PropostasApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $refresh = true;
    protected $namespace = 'App';

    private ClienteModel $clientes;
    private PropostaModel $propostas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clientes = new ClienteModel();
        $this->propostas = new PropostaModel();
    }

    public function testStatusTransitionValidAndInvalid(): void
    {
        $clienteId = $this->createCliente();
        $propostaId = $this->propostas->insert([
            'cliente_id' => $clienteId,
            'produto' => 'Plano Teste',
            'valor_mensal' => 150.00,
            'origem' => 'SITE',
            'status' => 'DRAFT',
            'versao' => 1,
        ], true);

        $submit = $this->jsonPost(
            "/api/v1/propostas/{$propostaId}/submit",
            ['versao' => 1],
            ['Idempotency-Key' => 'submit-1', 'X-Actor' => 'user:1']
        );
        $submit->assertStatus(200);
        $submitted = $this->decodeJson($submit);
        $this->assertSame('SUBMITTED', $submitted['status']);

        $approve = $this->jsonPost(
            "/api/v1/propostas/{$propostaId}/approve",
            ['versao' => $submitted['versao']],
            ['X-Actor' => 'user:1']
        );
        $approve->assertStatus(200);
        $approved = $this->decodeJson($approve);
        $this->assertSame('APPROVED', $approved['status']);

        $invalid = $this->jsonPost(
            "/api/v1/propostas/{$propostaId}/cancel",
            ['versao' => $approved['versao']],
            ['X-Actor' => 'user:1']
        );
        $invalid->assertStatus(400);
    }

    public function testIdempotencyOnCreate(): void
    {
        $clienteId = $this->createCliente();

        $payload = [
            'cliente_id' => $clienteId,
            'produto' => 'Plano Ouro',
            'valor_mensal' => 220.00,
            'origem' => 'APP',
        ];

        $headers = ['Idempotency-Key' => 'create-1', 'X-Actor' => 'user:2'];

        $first = $this->jsonPost('/api/v1/propostas', $payload, $headers);
        $first->assertStatus(201);

        $second = $this->jsonPost('/api/v1/propostas', $payload, $headers);
        $second->assertStatus(201);

        $this->assertSame($first->getJSON(), $second->getJSON());
        $this->assertSame(1, $this->propostas->countAll());
    }

    public function testVersionConflict(): void
    {
        $clienteId = $this->createCliente();
        $propostaId = $this->propostas->insert([
            'cliente_id' => $clienteId,
            'produto' => 'Plano Prata',
            'valor_mensal' => 99.00,
            'origem' => 'API',
            'status' => 'DRAFT',
            'versao' => 1,
        ], true);

        $response = $this->jsonPatch(
            "/api/v1/propostas/{$propostaId}",
            ['versao' => 99, 'produto' => 'Plano Ajustado'],
            ['X-Actor' => 'user:3']
        );

        $response->assertStatus(409);
    }

    public function testSearchWithFiltersAndPagination(): void
    {
        $clienteId = $this->createCliente();

        $this->propostas->insertBatch([
            [
                'cliente_id' => $clienteId,
                'produto' => 'Plano A',
                'valor_mensal' => 100.00,
                'origem' => 'SITE',
                'status' => 'SUBMITTED',
                'versao' => 1,
            ],
            [
                'cliente_id' => $clienteId,
                'produto' => 'Plano B',
                'valor_mensal' => 200.00,
                'origem' => 'APP',
                'status' => 'SUBMITTED',
                'versao' => 1,
            ],
            [
                'cliente_id' => $clienteId,
                'produto' => 'Plano C',
                'valor_mensal' => 300.00,
                'origem' => 'API',
                'status' => 'DRAFT',
                'versao' => 1,
            ],
        ]);

        $response = $this->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'xmlhttprequest'])
            ->get('/api/v1/propostas?status=SUBMITTED&per_page=1&page=1');
        $response->assertStatus(200);

        $body = $this->decodeJson($response);
        $this->assertCount(1, $body['data']);
        $this->assertSame('SUBMITTED', $body['data'][0]['status']);
        $this->assertSame(2, $body['meta']['total']);
    }

    private function createCliente(): int
    {
        return $this->clientes->insert([
            'nome' => 'Cliente Teste',
            'email' => 'cliente' . uniqid() . '@example.com',
            'documento' => '52998224725',
        ], true);
    }

    private function jsonPost(string $uri, array $data, array $headers = [])
    {
        $headers = array_merge(
            ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'X-Requested-With' => 'xmlhttprequest'],
            $headers
        );

        return $this->withHeaders($headers)
            ->withBody(json_encode($data), 'application/json')
            ->post($uri);
    }

    private function jsonPatch(string $uri, array $data, array $headers = [])
    {
        $headers = array_merge(
            ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'X-Requested-With' => 'xmlhttprequest'],
            $headers
        );

        return $this->withHeaders($headers)
            ->withBody(json_encode($data), 'application/json')
            ->patch($uri);
    }

    private function decodeJson($response): array
    {
        $raw = $response->response()->getBody();
        $body = is_string($raw) ? $raw : '';
        $decoded = json_decode($body, true);

        $this->assertIsArray($decoded, 'Response body is not valid JSON: ' . $body);

        return $decoded;
    }
}
