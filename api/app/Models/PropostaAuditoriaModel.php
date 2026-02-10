<?php

namespace App\Models;

use CodeIgniter\Model;

class PropostaAuditoriaModel extends Model
{
    protected $table = 'proposta_auditorias';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['proposta_id', 'actor', 'evento', 'payload', 'created_at'];
}
