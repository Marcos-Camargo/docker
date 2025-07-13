<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {

        if (!$this->dbforge->column_exists('pj_pf', 'shopkeeper_form')){
            ## Create column shopkeeper_form
            $fields = array(
                'pj_pf' => array(
                    'type' => 'varchar',
                    'constraint' => ('2'),
                    'null' => FALSE,
                    'default' => 'pj'
                )
            );
            $this->dbforge->add_column("shopkeeper_form", $fields);
        }
    }

    public function down()	{
        ### Drop column shopkeeper_form
        $this->dbforge->drop_column("shopkeeper_form", 'pj_pf');

    }
};