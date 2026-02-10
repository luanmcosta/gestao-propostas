<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePropostas extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id'  => ['type' => 'INT', 'unsigned' => true],
            'produto'     => ['type' => 'VARCHAR', 'constraint' => 150],
            'valor_mensal'=> ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'status'      => ['type' => 'VARCHAR', 'constraint' => 20],
            'origem'      => ['type' => 'VARCHAR', 'constraint' => 10],
            'versao'      => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('cliente_id');
        $this->forge->addKey('status');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('cliente_id', 'clientes', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('propostas');
    }

    public function down(): void
    {
        $this->forge->dropTable('propostas');
    }
}
