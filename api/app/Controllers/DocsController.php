<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class DocsController extends Controller
{
    public function index()
    {
        return view('docs/swagger');
    }

    public function spec()
    {
        $path = APPPATH . 'Docs/openapi.json';
        if (! is_file($path)) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 404,
                'error' => 404,
                'messages' => 'OpenAPI spec not found.',
            ]);
        }

        $body = file_get_contents($path);

        return $this->response
            ->setHeader('Content-Type', 'application/json; charset=UTF-8')
            ->setBody($body);
    }
}
