<?php

namespace Tests\Feature;

use App\Models\ClienteModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class ClientesApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $refresh = true;
    protected $namespace = 'App';

    private ClienteModel $clientes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clientes = new ClienteModel();
    }

    public function testCreateAndShowCliente(): void
    {
        $create = $this->jsonPost('/api/v1/clientes', [
            'nome' => 'Maria da Silva',
            'email' => 'maria.teste@example.com',
            'documento' => '52998224725',
        ], ['X-Actor' => 'user:11']);
        $create->assertStatus(201);

        $created = $this->decodeJson($create);
        $this->assertArrayHasKey('id', $created);

        $show = $this->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'xmlhttprequest'])
            ->get('/api/v1/clientes/' . $created['id']);
        $show->assertStatus(200);

        $shown = $this->decodeJson($show);
        $this->assertSame('Maria da Silva', $shown['nome']);
        $this->assertSame('maria.teste@example.com', $shown['email']);
    }

    public function testCreateClienteValidationAndUniqueEmail(): void
    {
        $invalid = $this->jsonPost('/api/v1/clientes', [
            'nome' => 'A',
            'email' => 'email-invalido',
            'documento' => '123',
        ]);
        $invalid->assertStatus(400);

        $this->clientes->insert([
            'nome' => 'Cliente Base',
            'email' => 'duplicado@example.com',
            'documento' => '52998224725',
        ], true);

        $duplicate = $this->jsonPost('/api/v1/clientes', [
            'nome' => 'Outro Cliente',
            'email' => 'duplicado@example.com',
            'documento' => '11144477735',
        ]);
        $duplicate->assertStatus(400);
    }

    public function testShowClienteNotFound(): void
    {
        $response = $this->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'xmlhttprequest'])
            ->get('/api/v1/clientes/99999');

        $response->assertStatus(404);
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

    private function decodeJson($response): array
    {
        $raw = $response->response()->getBody();
        $body = is_string($raw) ? $raw : '';
        $decoded = json_decode($body, true);

        $this->assertIsArray($decoded, 'Response body is not valid JSON: ' . $body);

        return $decoded;
    }
}
