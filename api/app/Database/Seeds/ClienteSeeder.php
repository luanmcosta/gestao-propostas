<?php

namespace App\Database\Seeds;

use App\Models\ClienteModel;
use CodeIgniter\Database\Seeder;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        $model = new ClienteModel();

        $model->insertBatch([
            [
                'nome' => 'Ana Costa',
                'email' => 'ana.costa@example.com',
                'documento' => '52998224725',
            ],
            [
                'nome' => 'Grupo Lima',
                'email' => 'contato@grupolima.example',
                'documento' => '11444777000161',
            ],
        ]);
    }
}
