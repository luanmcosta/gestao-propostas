<?php

namespace App\Database\Seeds;

use App\Models\ClienteModel;
use App\Models\PropostaModel;
use CodeIgniter\Database\Seeder;

class PropostaSeeder extends Seeder
{
    public function run(): void
    {
        $clienteModel = new ClienteModel();
        $propostaModel = new PropostaModel();

        $clientes = $clienteModel->findAll();
        if ($clientes === []) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $propostaModel->insertBatch([
            [
                'cliente_id' => $clientes[0]['id'],
                'produto' => 'Plano Basico',
                'valor_mensal' => 120.00,
                'status' => 'DRAFT',
                'origem' => 'SITE',
                'versao' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'cliente_id' => $clientes[1]['id'],
                'produto' => 'Plano Empresa',
                'valor_mensal' => 450.00,
                'status' => 'SUBMITTED',
                'origem' => 'APP',
                'versao' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
