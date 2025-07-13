<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $fieldsUpdate = array(
            'cod_type_prod' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => TRUE
            ),
            'cod_type_prod_adm_seller' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => TRUE
            ),
            'cod_type_prod_gcp' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => TRUE
            ),
            'cod_type_prod_adm_seller_gcp' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => TRUE
            ),
            'cod_type_prod_oci' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => TRUE
            ),
            'cod_type_prod_adm_seller_oci' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => TRUE
            ),
            'cod_type_test' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => TRUE
            )
        );

        foreach ($fieldsUpdate as $filed => $fieldUpdate) {
            if (!$this->dbforge->column_exists($filed, 'reports_metabase')) {
                $this->dbforge->add_column('reports_metabase', array($filed => $fieldUpdate));
            } else {
                $this->dbforge->modify_column('reports_metabase', array($filed => $fieldUpdate));
            }
        }

    }

    public function down()
    {
        $this->dbforge->drop_column('reports_metabase', 'cod_type_prod_gcp');
        $this->dbforge->drop_column('reports_metabase', 'cod_type_prod_adm_seller_gcp');
        $this->dbforge->drop_column('reports_metabase', 'cod_type_prod_oci');
        $this->dbforge->drop_column('reports_metabase', 'cod_type_prod_adm_seller_oci');
    }
};