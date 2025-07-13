<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if (!$this->dbforge->index_exists('index_pathProd', 'prd_image')){
            ## Create index index_pathProd ##
            $this->db->query('CREATE INDEX index_pathProd ON prd_image (pathProd);');
        }
        if (!$this->dbforge->index_exists('index_skumkt', 'errors_transformation')){
            ## Create index index_skumkt ##
            $this->db->query('CREATE INDEX index_skumkt ON errors_transformation (skumkt);');
        }
        if (!$this->dbforge->index_exists('index_status_id', 'queue_products_marketplace')){
            ## Create index index_status_id ##
            $this->db->query('CREATE INDEX index_status_id ON queue_products_marketplace (status, id);');
        }
    }

	public function down()	{
		### Drop index index_pathProd ##
        $this->db->query('DROP INDEX index_pathProd ON prd_image;');
        $this->db->query('DROP INDEX index_skumkt ON errors_transformation;');
        $this->db->query('DROP INDEX index_status_id ON queue_products_marketplace;');

	}

};