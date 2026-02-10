<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIdempotencyKeys extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'scope'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'idempotency_key' => ['type' => 'VARCHAR', 'constraint' => 100],
            'request_hash'    => ['type' => 'VARCHAR', 'constraint' => 64],
            'response_code'   => ['type' => 'INT', 'unsigned' => true],
            'response_body'   => ['type' => 'TEXT', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['scope', 'idempotency_key'], false, true);
        $this->forge->addKey('created_at');
        $this->forge->createTable('idempotency_keys');
    }

    public function down(): void
    {
        $this->forge->dropTable('idempotency_keys');
    }
}
