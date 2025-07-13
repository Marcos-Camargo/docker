<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    private $table_name = "products";

    public function up()
    {

        $fields = array(
            'categorized_at' => array(
                'type' => 'DATETIME',
                'default' => null
            )
        );
        $this->dbforge->add_column($this->table_name, $fields);
    }

    public function down()
    {
        $this->dbforge->drop_column($this->table_name, 'categorized_at');
    }
};