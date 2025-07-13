<?php

/**
 * @property Model_products $model_products
 * @property Model_integrations $model_integrations
 * @property Model_vtex_ult_envio $model_vtex_ult_envio
 * @property Model_occ_last_post $model_occ_last_post
 * @property Model_integration_last_post $model_integration_last_post
 * @property Model_sellercenter_last_post $model_sellercenter_last_post
 * @property Model_vs_last_post $model_vs_last_post
 *
 * @property CSV_Validation $csv_validation
 */
class RemoveDeletedVariations extends BatchBackground_Controller
{
    /**
     * @var array Tabelas para uso
     */
    private $tables = array();

    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => true
        );
        $this->session->set_userdata($logged_in_sess);
        $this->load->model('model_products');
        $this->load->model('model_integrations');
        $this->load->model('model_vtex_ult_envio');
        $this->load->model('model_occ_last_post');
        $this->load->model('model_integration_last_post');
        $this->load->model('model_sellercenter_last_post');
        $this->load->model('model_vs_last_post');
        $this->load->library('CSV_Validation');
    }

    // php index.php BatchC/Automation/Fix/RemoveDeletedVariations/run
    function run($id = null, $params = null)
    {
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        if (!$this->gravaInicioJob('Automation/' . $this->router->fetch_class(), __FUNCTION__)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params), "I");

        $this->setTables();
        $this->fixVariations();
        $this->removeRowWithoutVariation();
        $this->removeRowCalendar();

        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    private function setTables()
    {
        if ($this->db->table_exists('vtex_ult_envio')) {
            if ($this->db->select('id')->limit(1)->get('vtex_ult_envio')->num_rows() > 0) {
                $this->tables[] = 'vtex_ult_envio';
            }
        }
        if ($this->db->table_exists('vs_last_post')) {
            if ($this->db->select('id')->limit(1)->get('vs_last_post')->num_rows() > 0) {
                $this->tables[] = 'vs_last_post';
            }
        }
        if ($this->db->table_exists('integration_last_post')) {
            if ($this->db->select('id')->limit(1)->get('integration_last_post')->num_rows() > 0) {
                $this->tables[] = 'integration_last_post';
            }
        }
        if ($this->db->table_exists('occ_last_post')) {
            if ($this->db->select('id')->limit(1)->get('occ_last_post')->num_rows() > 0) {
                $this->tables[] = 'occ_last_post';
            }
        }
        if ($this->db->table_exists('sellercenter_last_post')) {
            if ($this->db->select('id')->limit(1)->get('sellercenter_last_post')->num_rows() > 0) {
                $this->tables[] = 'sellercenter_last_post';
            }
        }
    }

    public function fixVariations()
    {
        $limit = 500;
        $offset = 0;

        while (true) {
            echo dateNow()->format('H:i:s') . " offset: $offset\n";
            $products = $this->model_products->listCompleteVariationProduct($offset, $limit);
            if (count($products) == 0) {
                break;
            }
            $offset += $limit;

            foreach ($products as $product) {
                $product_id = $product['id'];

                $variations = $this->model_products->getVariantsByProd_id($product_id);

                if (empty($variations)) {
                    echo "Produto $product_id sem variação\n";
                    continue;
                }

                $variations_id = array_map(function ($variation) {
                    return $variation['variant'];
                }, $variations);

                $result_query = array();

                $prd_to_integrations = $this->db
                    ->or_group_start()
                    ->where(array("prd_id" => $product_id))
                    ->where('variant IS NULL', NULL, FALSE)
                    ->group_end()
                    ->or_group_start()
                    ->where(array("prd_id" => $product_id))
                    ->where_not_in('variant', $variations_id)
                    ->group_end()
                    ->get('prd_to_integration')
                    ->result_array();

                if (!empty($prd_to_integrations)) {
                    $result_query['prd_to_integration'] = $prd_to_integrations;
                }

                foreach ($prd_to_integrations as $integration) {
                    $this->model_integrations->removePrdToIntegration($integration['id']);
                }

                foreach ($this->tables as $table) {
                    $query = $this->db
                        ->or_group_start()
                        ->where(array("prd_id" => $product_id))
                        ->where('variant IS NULL', NULL, FALSE)
                        ->group_end()
                        ->or_group_start()
                        ->where(array("prd_id" => $product_id))
                        ->where_not_in('variant', $variations_id)
                        ->group_end()
                        ->get($table)
                        ->result_array();

                    if (!empty($query)) {
                        $result_query[$table] = $query;
                    }

                    foreach ($query as $publiahed_product) {
                        $this->db->delete($table, array('id' => $publiahed_product['id']));
                    }
                }

                if (!empty($result_query)) {
                    foreach ($result_query as $table => $result) {
                        try {
                            $this->saveToFile($result, $table);
                        } catch (Throwable $exception) {
                            echo $exception->getMessage() . "\n";
                        }
                    }
                }
            }
        }
    }

    public function removeRowWithoutVariation()
    {
        while (true) {
            $results = $this->db->select('pti.*')->join('prd_variants pv', 'pv.prd_id = pti.prd_id', 'left')
                ->where('pv.id is null', null, false)
                ->where('pti.variant is not null', null, false)
                ->limit(200)
                ->get('prd_to_integration pti')
                ->result_array();

            if (empty($results)) {
                break;
            }

            try {
                $this->saveToFile($results, 'prd_to_integration');
            } catch (Throwable $exception) {
                echo $exception->getMessage() . "\n";
                break;
            }

            $ids = array_map(function ($result) {
                return $result['id'];
            }, $results);

            $this->db->where_in('id', $ids)->delete('prd_to_integration');
        }

        foreach ($this->tables as $table) {
            while (true) {
                $results = $this->db->select('vue.id')->join('prd_variants pv', 'pv.prd_id = vue.prd_id', 'left')
                    ->where('pv.id is null', null, false)
                    ->where('vue.variant is not null', null, false)
                    ->limit(200)
                    ->get("$table vue")
                    ->result_array();

                if (empty($results)) {
                    break;
                }

                try {
                    $this->saveToFile($results, $table);
                } catch (Throwable $exception) {
                    echo $exception->getMessage() . "\n";
                    break;
                }

                $ids = array_map(function ($result) {
                    return $result['id'];
                }, $results);

                $this->db->where_in('id', $ids)->delete($table);
            }
        }
    }

    private function removeRowCalendar()
    {
        // Ao fim do processamento o evento pode ser excluído.
        $this->db->delete('calendar_events', array('module_path' => 'Automation/Fix/RemoveDeletedVariations'));
    }

    /**
     * @throws Exception
     */
    private function saveToFile(array $result, string $path)
    {
        $dir_name = "assets/files/fix_deleted_variations/$path";
        $file_name = "$dir_name/fix_deleted_variations.csv";
        if (!file_exists($file_name)) {
            checkIfDirExist($dir_name);
            $this->csv_validation->createNewFileCsv($file_name, $result);
        } else {
            $this->csv_validation->insertLinesInTheFile($file_name, $result);
        }
    }
}
