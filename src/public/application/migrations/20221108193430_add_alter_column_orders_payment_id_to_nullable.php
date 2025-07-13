<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {
        $this->db->query('ALTER TABLE orders_conciliation_installments MODIFY orders_payment_id INT(11) NULL;');
    }

    public function down()	{
        // $this->db->query('ALTER TABLE orders_conciliation_installments MODIFY orders_payment_id INT(11) NOT NULL;');
    }

};