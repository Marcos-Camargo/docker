<?php

/**
 * @property CI_DB_driver $db
 * @property CI_Session $session
 * @property CI_Loader $load
 *
 * @property Model_queue_products_marketplace $model_queue_products_marketplace
 * @property Model_products $model_products
 * 
 * @property CSV_Validation $csv_validation
 */
class FixVariationProduct extends BatchBackground_Controller
{
    private $time_file;

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
        $this->load->model('model_queue_products_marketplace');
        $this->load->model('model_products');
        $this->load->library('CSV_Validation');
        $this->time_file = date('dmYHi');
    }

    public function run()
    {
        $this->fixEqualSku();
        if ($this->get_if_exist_index('prd_variants', 'unique_prd_sku')) {
            $this->db->query("ALTER TABLE prd_variants DROP INDEX unique_prd_sku;");
        }
        $this->db->query("ALTER TABLE prd_variants ADD CONSTRAINT unique_prd_sku UNIQUE (prd_id, sku);");

        $this->fixEqualName();
        if ($this->get_if_exist_index('prd_variants', 'unique_prd_name')) {
            $this->db->query("ALTER TABLE prd_variants DROP INDEX unique_prd_name;");
        }
        $this->db->query("ALTER TABLE prd_variants ADD CONSTRAINT unique_prd_name UNIQUE (prd_id, name);");

        if ($this->get_if_exist_index('prd_variants', 'unique_prd_sku')) {
            echo "Index unique_prd_sku on prd_variants created\n";
        }
        if ($this->get_if_exist_index('prd_variants', 'unique_prd_name')) {
            echo "Index unique_prd_name on prd_variants created\n";
        }

        $this->removeRowCalendar();
    }

    public function fixEqualName()
    {
        echo dateNow()->format(DATETIME_INTERNATIONAL).' '.__FUNCTION__."\n";
        $limit = 200;
        $last_id = 0;

        while (true) {
            $result = $this->db->query("SELECT 
                pv.id,
                pv.sku,
                pv.name,
                pv.prd_id,
                COUNT(pv.name) AS qtd 
            FROM prd_variants pv 
            WHERE pv.id > ?
            GROUP BY pv.name,pv.prd_id 
            HAVING COUNT(pv.name) > 1 
            ORDER BY pv.id ASC
            LIMIT ?", [$last_id, $limit])->result_array();

            if (empty($result)) {
                break;
            }

            echo dateNow()->format(DATETIME_INTERNATIONAL)." reading ".count($result)." registers, last_id=$last_id\n";

            foreach ($result as $variant) {
                $last_id = $variant['id'];

                // remove os duplicados.
                $this->db->delete('prd_variants', [
                    'name' => $variant['name'],
                    'prd_id' => $variant['prd_id']
                ], $variant['qtd'] - 1);

                // corrige os indices das variações
                $this->fixVariationNumbers($variant['prd_id']);

                // Add os produtos na fila.
                $this->model_queue_products_marketplace->create(array(
                    'status' => 0,
                    'prd_id' => $variant['prd_id']
                ));
            }

            try {
                $dir_name = "assets/files/fix_variation_equals/$this->time_file";
                $file_name = "$dir_name/fix_variation_equals_by_name.csv";
                if (!file_exists($file_name)) {
                    checkIfDirExist($dir_name);
                    $this->csv_validation->createNewFileCsv($file_name, $result);
                } else {
                    $this->csv_validation->insertLinesInTheFile($file_name, $result);
                }
            } catch (Exception $exception) {
                echo $exception->getMessage()."\n";
            }
        }
    }

    public function fixEqualSku()
    {
        echo dateNow()->format(DATETIME_INTERNATIONAL).' '.__FUNCTION__."\n";
        $limit = 200;
        $last_id = 0;

        while (true) {
            $result = $this->db->query("SELECT 
                pv.id,
                pv.sku,
                pv.name,
                pv.prd_id,
                pv.variant,
                COUNT(pv.sku) AS qtd 
            FROM prd_variants pv 
            WHERE pv.id > ?
            GROUP BY pv.sku,pv.prd_id 
            HAVING COUNT(pv.sku) > 1 
            ORDER BY pv.id ASC
            LIMIT ?;", [$last_id, $limit])->result_array();

            if (empty($result)) {
                break;
            }

            echo dateNow()->format(DATETIME_INTERNATIONAL)." reading ".count($result)." registers, last_id=$last_id\n";

            foreach ($result as $variant) {
                $last_id = $variant['id'];

                // remove os duplicados.
                $this->db->delete('prd_variants', [
                    'sku' => $variant['sku'],
                    'prd_id' => $variant['prd_id']
                ], $variant['qtd'] - 1);

                // corrige os indices das variações
                $this->fixVariationNumbers($variant['prd_id']);

                // Add os produtos na fila.
                $this->model_queue_products_marketplace->create(array(
                    'status' => 0,
                    'prd_id' => $variant['prd_id']
                ));
            }

            try {
                $dir_name = "assets/files/fix_variation_equals/$this->time_file";
                $file_name = "$dir_name/fix_variation_equals_by_sku.csv";
                if (!file_exists($file_name)) {
                    checkIfDirExist($dir_name);
                    $this->csv_validation->createNewFileCsv($file_name, $result);
                } else {
                    $this->csv_validation->insertLinesInTheFile($file_name, $result);
                }
            } catch (Exception $exception) {
                echo $exception->getMessage()."\n";
            }
        }
    }

    private function fixVariationNumbers(int $prd_id)
    {
        $variations = $this->model_products->getVariantsByProd_id($prd_id);
        $variation_code = 0;
        foreach ($variations as $variation) {
            if ($variation['variant'] != $variation_code) {
                $this->model_products->updateVariationData($variation['id'], $prd_id, array(
                    'variant' => $variation_code
                ));
            }
            $variation_code++;
        }
    }

    public function get_if_exist_index(string $table, string $index): bool
    {
        $sql = "SHOW INDEX FROM $table";
        $result = $this->db->query($sql);

        foreach ($result->result_array() as $row){
            if ($row['Key_name'] == $index) {
                return true;
            }
        }

        return false;

    }

    private function removeRowCalendar()
    {
        // Ao fim do processamento o evento pode ser excluído.
        $this->db->delete('calendar_events', array('module_path' => 'Script/FixVariationProduct'));
    }
}
