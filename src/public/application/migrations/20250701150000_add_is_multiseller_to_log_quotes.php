<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('is_multiseller', 'log_quotes')) {
            $this->dbforge->add_column('log_quotes', [
                'is_multiseller' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => FALSE,
                    'default' => 0,
                    'after' => 'seller_id'
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->field_exists('is_multiseller', 'log_quotes')) {
            $this->dbforge->drop_column('log_quotes', 'is_multiseller');
        }
    }
};
