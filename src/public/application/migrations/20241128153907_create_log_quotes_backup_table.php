<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
    {
        return;
        if (!in_array(ENVIRONMENT, ['production', 'production_x', 'production_oci'])) {
            return;
        }

		$this->db->query('CREATE TABLE log_quotes_bkp_20241128 AS SELECT * FROM log_quotes;');
        $errors = $this->db->error();
        if (empty($errors['message'])) {
            $log_quotes_id = $this->db->select('id')
                ->where('created_at <', subtractDateFromNow(24)->format(DATETIME_INTERNATIONAL))
                ->order_by('id', 'desc')
                ->get('log_quotes', 1)
                ->row_array();

            if ($log_quotes_id) {
                $log_quotes_id = (int)$log_quotes_id['id'];
                $this->db->delete('log_quotes', array('id <' => $log_quotes_id));
            }
        }

	}

	public function down()	{
	}
};