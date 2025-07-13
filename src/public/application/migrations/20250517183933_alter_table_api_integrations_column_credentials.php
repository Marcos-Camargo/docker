<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $this->db->select('id, credentials');
        $query = $this->db->get_where('api_integrations', ['integration' => 'linx_microvix']);

        foreach ($query->result() as $row) {
            $credentials = json_decode($row->credentials, true);

            if (isset($credentials['microvix_chave'])) {
                unset($credentials['microvix_chave']);

                $this->db->where('id', $row->id)->update('api_integrations', [
                    'credentials' => json_encode($credentials, JSON_UNESCAPED_UNICODE)
                ]);
            }
        }
    }

    public function down()
    {
        $this->db->select('id, credentials');
        $query = $this->db->get_where('api_integrations', ['name' => 'linx_microvix']);

        foreach ($query->result() as $row) {
            $credentials = json_decode($row->credentials, true);

            if (!isset($credentials['microvix_chave'])) {
                $credentials['microvix_chave'] = '06A9E390-F493-489F-A988-065020EC316F'; // valor original

                $this->db->where('id', $row->id)->update('api_integrations', [
                    'credentials' => json_encode($credentials, JSON_UNESCAPED_UNICODE)
                ]);
            }
        }
    }

};
