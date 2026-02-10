<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->get('docs', 'DocsController::index');
$routes->get('docs/openapi.json', 'DocsController::spec');

$routes->group('api/v1', ['namespace' => 'App\Controllers\Api\V1'], static function ($routes) {
    $routes->post('clientes', 'ClientesController::create');
    $routes->get('clientes/(:num)', 'ClientesController::show/$1');

    $routes->post('propostas', 'PropostasController::create');
    $routes->patch('propostas/(:num)', 'PropostasController::update/$1');
    $routes->post('propostas/(:num)/submit', 'PropostasController::submit/$1');
    $routes->post('propostas/(:num)/approve', 'PropostasController::approve/$1');
    $routes->post('propostas/(:num)/reject', 'PropostasController::reject/$1');
    $routes->post('propostas/(:num)/cancel', 'PropostasController::cancel/$1');
    $routes->get('propostas/(:num)', 'PropostasController::show/$1');
    $routes->get('propostas', 'PropostasController::index');
    $routes->get('propostas/(:num)/auditoria', 'PropostasController::auditoria/$1');
    $routes->delete('propostas/(:num)', 'PropostasController::delete/$1');
});
