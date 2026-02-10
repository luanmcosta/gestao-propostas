<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use OpenApi\Generator;

class GenerateOpenApi extends BaseCommand
{
    protected $group       = 'Docs';
    protected $name        = 'openapi:generate';
    protected $description = 'Gera o arquivo OpenAPI (swagger-php) em app/Docs/openapi.json';

    public function run(array $params)
    {
        $outputPath = APPPATH . 'Docs/openapi.json';
        $scanPaths = [
            APPPATH . 'Controllers',
            APPPATH . 'Docs',
        ];

        $openapi = Generator::scan($scanPaths, ['validate' => false]);

        $dir = dirname($outputPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $spec = $openapi->toJson();
        $decoded = json_decode($spec, true) ?? [];

        if (empty($decoded['paths'])) {
            CLI::write('Aviso: nenhuma rota detectada no OpenAPI.', 'yellow');
        }

        file_put_contents($outputPath, $spec);

        CLI::write('OpenAPI gerado em: ' . $outputPath, 'green');
    }
}
