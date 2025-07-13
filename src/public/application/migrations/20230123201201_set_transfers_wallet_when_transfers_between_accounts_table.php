<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {

        $sql = "UPDATE gateway_transfers SET transfer_type = 'WALLET' WHERE transfer_type = '' AND gateway_id = 2 AND sender_id <> receiver_id";
        $this->db->query($sql);

    }

    public function down()
    {
    }
};