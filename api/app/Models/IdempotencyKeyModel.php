<?php

namespace App\Models;

use CodeIgniter\Model;

class IdempotencyKeyModel extends Model
{
    protected $table = 'idempotency_keys';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'scope',
        'idempotency_key',
        'request_hash',
        'response_code',
        'response_body',
        'created_at',
    ];
}
