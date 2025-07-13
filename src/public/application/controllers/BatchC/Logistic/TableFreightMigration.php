<?php

require 'system/libraries/Vendor/autoload.php';

/**
 * @property Model_table_shipping_regions $model_table_shipping_regions
 * @property Model_table_shipping $model_table_shipping
 */
class TableFreightMigration extends BatchBackground_Controller
{
    public function __construct()
    {
        parent::__construct();
        $logged_in_sess = array(
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        $this->load->model('model_table_shipping_regions');
        $this->load->model('model_table_shipping');
    }

    public function run($id = null, $params = null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        if (!$this->gravaInicioJob($this->router->fetch_class(), __FUNCTION__)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return;
        }

        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params), "I");

        $this->migrateTableFreight();

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    private function migrateTableFreight()
    {
        /*$settings_enable_table_regions  = $this->db->get_where('settings', array('name' => 'enable_table_shipping_regions'));
        $row_enable_table_regions       = $settings_enable_table_regions->row_array();

        if (!$row_enable_table_regions || $row_enable_table_regions['status'] != 1) {
            echo "Parâmetro 'enable_table_shipping_regions' inativo.";
            return;
        }*/
        $settings_use_ms_shipping  = $this->db->get_where('settings', array('name' => 'use_ms_shipping'));
        $use_ms_shipping           = $settings_use_ms_shipping->row_array();

        if ($use_ms_shipping && $use_ms_shipping['status'] == 1) {
            echo "Parâmetro 'use_ms_shipping' ativo. Cliente está usando microsserviço.";
            return;
        }

        // Consulta os registros de estados.
        $table_regions = $this->model_table_shipping_regions->getAllActive();

        foreach ($table_regions as $table_region) {
            if ($table_region['migrated_data']) {
                echo "Table $table_region[table] já migrada, não pode migrar os registros novamente.\n";
                continue;
            }

            $limit  = 1000;
            $offset = 0;

            echo "UF = $table_region[uf]\n";
            while (true) {
                // Consulta os dados com limit e offset.
                $data = $this->model_table_shipping->getActivesByRangeZipcodeAndLimitOffset($table_region['zipcode_start'], $table_region['zipcode_end'], $limit, $offset);

                // Não encontrou registros.
                if (!count($data)) {
                    break;
                }

                echo "LIMIT=$limit - OFFSET=$offset - COUNT_DATA=".count($data)."\n";

                // Posiciona para ler mais registros.
                $offset += $limit;

                // Salva os dados na nova tabela de região.
                $this->db->insert_batch($table_region['table'], $data);
            }

            // ler registros duplicados.
            $registers_duplicated = $this->db->select('idproviders_to_seller, CEP_start, CEP_end, weight_minimum, weight_maximum, COUNT(*) as duplicated')
                ->group_by('idproviders_to_seller, CEP_start, CEP_end, weight_minimum, weight_maximum')
                ->having('COUNT(*) >', 1)
                ->get($table_region['table'])->result_array();

            // remove os registros duplicados.
            foreach ($registers_duplicated as $register_duplicated) {
                $quantity_duplicated = (int)$register_duplicated['duplicated'];
                $this->db->delete($table_region['table'], array(
                    'idproviders_to_seller' => $register_duplicated['idproviders_to_seller'],
                    'CEP_start' => $register_duplicated['CEP_start'],
                    'CEP_end' => $register_duplicated['CEP_end'],
                    'weight_minimum' => $register_duplicated['weight_minimum'],
                    'weight_maximum' => $register_duplicated['weight_maximum']
                ), $quantity_duplicated - 1);
            }

            $this->model_table_shipping_regions->update(['migrated_data' => true, 'migrated_at' => dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL)], $table_region['id']);
        }
    }

    public function checkDataMigrated()
    {
        // Consulta os registros de estados.
        $table_regions = $this->model_table_shipping_regions->getAllActive();
        $uf_already_red = [];

        foreach ($table_regions as $table_region) {
            if (in_array($table_region['uf'], $uf_already_red)) {
                continue;
            }

            $uf_already_red[] = $table_region['uf'];

            $ufs = $this->model_table_shipping_regions->getRangesByUf($table_region['uf']);

            $query = $this->db->from('table_shipping')->group_start();
            foreach ($ufs as $uf) {
                $query->or_group_start()
                    ->or_where("'$uf[zipcode_start]' between cep_start and cep_end", NULL, FALSE)
                    ->or_where("'$uf[zipcode_end]' between cep_start and cep_end", NULL, FALSE)
                    ->or_where("cep_start between '$uf[zipcode_start]' and '$uf[zipcode_end]'", NULL, FALSE)
                    ->or_where("cep_end between '$uf[zipcode_start]' and '$uf[zipcode_end]'", NULL, FALSE)
                ->group_end();
            }

            $reg_in_table_shipping = $query->group_end()->where('status', true)->get()->num_rows();
            $reg_in_table_shipping_region =  $this->db->get($table_region['table'])->num_rows();

            $status = $reg_in_table_shipping_region == $reg_in_table_shipping ? 'V' : 'X';

            echo "[$status] [$table_region[uf]] OLD=$reg_in_table_shipping | NEW=$reg_in_table_shipping_region\n";
        }
    }
}
