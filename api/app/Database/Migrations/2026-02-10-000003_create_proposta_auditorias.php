<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePropostaAuditorias extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'proposta_id' => ['type' => 'INT', 'unsigned' => true],
            'actor'       => ['type' => 'VARCHAR', 'constraint' => 50],
            'evento'      => ['type' => 'VARCHAR', 'constraint' => 30],
            'payload'     => ['type' => 'TEXT', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('proposta_id');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('proposta_id', 'propostas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('proposta_auditorias');
    }

    public function down(): void
    {
        $this->forge->dropTable('proposta_auditorias');
    }
}
