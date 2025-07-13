<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $this->db->where(
            array(
                'notification_type'     => 'order',
                'notification_id'       => 'Devolução de produto.',
                'status'                => 'Devolução de produto',
                'accountable_opening'   => 'Rotina API'
            )
        )->update('legal_panel', array(
            'status' => 'Chamado Aberto'
        ));
    }

	public function down()	{
        // Não tem rollback.
	}

};