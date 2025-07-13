<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
    {
        if ($this->db->table_exists('creditcard_payment_mdr'))
        {

            $sql = "ALTER TABLE `creditcard_payment_mdr` ADD UNIQUE INDEX `payment_type_payment_method_parcels_mdr` (`payment_type`, `payment_method`, `parcels`, `mdr`);";

            $this->db->query($sql);

            $sql = "INSERT INTO `creditcard_payment_mdr` (`id`, `payment_type`, `payment_method`, `parcels`, `mdr`) 
                        VALUES
                        (1, 'giftCard', 'Vale', 1, 1),
                        (2, 'creditcard', 'MasterCard', 1, 2.1),
                        (3, 'creditcard', 'MasterCard', 2, 2.8),
                        (4, 'creditcard', 'MasterCard', 3, 2.8),
                        (5, 'creditcard', 'MasterCard', 4, 2.8),
                        (6, 'creditcard', 'Visa', 1, 2.1),
                        (7, 'creditcard', 'Visa', 2, 2.8),
                        (8, 'creditcard', 'Visa', 3, 2.8),
                        (9, 'creditcard', 'Visa', 4, 2.8);";

            $this->db->query($sql);
        }
	}

	public function down()
    {
        $this->db->query('TRUNCATE `creditcard_payment_mdr`;');
	}
};