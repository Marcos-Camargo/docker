<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {

        if (!$this->dbforge->column_exists('pj_pf', 'stores')){
            ## Create column stores
            $fields = array(
                'pj_pf' => array(
                    'type' => 'varchar',
                    'constraint' => ('2'),
                    'null' => FALSE,
                    'default' => 'pj'
                )
            );
            $this->dbforge->add_column("stores", $fields);
        }
    }

    public function down()	{
        ### Drop column stores
        $this->dbforge->drop_column("stores", 'pj_pf');

    }
};