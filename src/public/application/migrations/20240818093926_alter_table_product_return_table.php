<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        $fieldUpdate = array(
            '`returned_at` timestamp NULL ',
            '`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'
        );

        if (!$this->dbforge->column_exists('created_at', 'product_return')) {
            $this->dbforge->add_column('product_return', $fieldUpdate);
        }
    }

    public function down()	{
        $this->dbforge->drop_column('product_return', 'returned_at');
        $this->dbforge->drop_column('product_return', 'updated_at');
        $this->dbforge->drop_column('product_return', 'created_at');
    }
};