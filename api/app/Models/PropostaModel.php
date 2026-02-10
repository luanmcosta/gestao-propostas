<?php

namespace App\Models;

use CodeIgniter\Model;

class PropostaModel extends Model
{
    protected $table = 'propostas';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'cliente_id',
        'produto',
        'valor_mensal',
        'status',
        'origem',
        'versao',
        'deleted_at',
    ];

    protected $useTimestamps = true;
    protected $useSoftDeletes = true;
    protected $dateFormat = 'datetime';

    public function findWithCliente(int $id): ?array
    {
        $builder = $this->builder();
        $builder->select('propostas.*, clientes.nome as cliente_nome, clientes.email as cliente_email, clientes.documento as cliente_documento');
        $builder->join('clientes', 'clientes.id = propostas.cliente_id');
        $builder->where('propostas.id', $id);
        $builder->where('propostas.deleted_at', null);

        $row = $builder->get()->getRowArray();
        if (! $row) {
            return null;
        }

        $cliente = [
            'id' => $row['cliente_id'],
            'nome' => $row['cliente_nome'],
            'email' => $row['cliente_email'],
            'documento' => $row['cliente_documento'],
        ];

        unset($row['cliente_nome'], $row['cliente_email'], $row['cliente_documento']);
        $row['cliente'] = $cliente;

        return $row;
    }
}
