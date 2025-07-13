<?php

/**
 * @property Model_csv_to_verifications $model_csv_to_verifications
 * @property Model_stores $model_stores
 * @property Model_integrations $model_integrations
 * @property Model_groups $model_groups
 * @property Model_users $model_users
 * @property Model_products $model_products
 * @property Model_queue_products_marketplace $model_queue_products_marketplace
 * 
 * @property CSV_Validation $csv_validation
 * @property DeleteProduct $deleteProduct
 */
class ImportCSVGroupSimpleSku extends BatchBackground_Controller
{
    /**
     * @var int $store_id Código da loja.
     */
    private $store_id;

    /**
     * @var array $data_stores
     */
    private $data_stores;

    /**
     * @var array $data_users
     */
    private $data_users;

    /**
     * @var array $data_groups_by_user
     */
    private $data_groups_by_user;

    /**
     * @var array $data_store_by_company
     */
    private $data_store_by_company;

    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'userstore' => 0,
            'logged_in' => true
        );
        $this->session->set_userdata($logged_in_sess);

        $this->load->model('model_csv_to_verifications');
        $this->load->model('model_stores');
        $this->load->model('model_groups');
        $this->load->model('model_users');
        $this->load->model('model_products');
        $this->load->model('model_orders');
        $this->load->model('model_queue_products_marketplace');
        $this->load->library('CSV_Validation');
        $this->load->library('DeleteProduct', [ 'productModel' => $this->model_products, 'lang' => $this->lang ], 'deleteProduct');
    }
    
    // php index.php BatchC/Automation/ImportCSVGroupSimpleSku run
    public function run($id = null, $params = null)
    {
        $this->setIdJob($id);
        $log_name = __CLASS__ . '/' . __FUNCTION__;

        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . __CLASS__;
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            echo "Já tem um job rodando!\n";
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params));

        $csvs_to_import = $this->model_csv_to_verifications->getDontChecked(false, 'GroupSimpleSku');
        foreach ($csvs_to_import as $csv_import) {
            $this->importCsv($csv_import);
        }

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();
    }

    /**
     * @param int $store_id
     * @return void
     */
    private function setStoreId(int $store_id)
    {
        $this->store_id = $store_id;
    }

    /**
     * @return int
     */
    private function getStoreId(): int
    {
        return $this->store_id;
    }

    /**
     * Faz a validação inicial antes de ler a linha
     *
     * @param   array   $fileCsv
     * @param   array   $data
     * @throws  Exception
     */
    private function initValidation(array $fileCsv, array $data)
    {
        $user_data              = $this->data_users[$fileCsv['user_id']] ?? null;
        $user_group             = $this->data_groups_by_user[$fileCsv['user_id']] ?? null;
        $data_store_by_company  = $this->data_store_by_company[$fileCsv['user_id']] ?? null;
        $user_email             = $fileCsv["user_email"];
        $store_id               = trim($data['ID da Loja'] ?? '');
        $store_csv              = trim($data['ID da Loja'] ?? '');

        if (is_null($user_data)) {
            $user_data = $this->data_users[$fileCsv['user_id']] = $this->model_users->getUserData($fileCsv['user_id']);
            $this->data_users = $user_data;
        }

        if (is_null($user_group)) {
            $user_group = $this->data_groups_by_user[$fileCsv['user_id']] = $this->model_groups->getUserGroupByUserId($fileCsv['user_id']);
        }

        $permissions = unserialize($user_group['permission']);

        if (!in_array('groupSimpleSku', $permissions)) {
            throw new Exception("Usuário sem permissão.");
        }

        // Não encontrou o grupo de usuário.
        if (empty($user_group)) {
            throw new Exception("Grupo do usuário não encontrado.");
        }

        // Recupera o ID da loja.
        if (!empty($store_id) && !is_numeric($store_id)) {
            $store_data = $this->data_stores[$store_id] ?? null;
            if (is_null($store_data)) {
                $store_data = $this->data_stores[$store_id] = $this->model_stores->getStoreByIdOrName($store_id, $store_id);
                $this->data_stores[$store_data['id']] = $store_data;
            }
            $store_id = (string)$store_data['id'];
        }

        // Validar se usuário é da loja. Se for adminisrador não faz a validação.
        if ($user_group['only_admin'] != 1) {
            $store_to_validation = array();
            $store_to_validation[] = $user_data['store_id'];
            if ($user_data['store_id'] == 0) {
                if (is_null($data_store_by_company)) {
                    $data_store_by_company = $this->data_store_by_company[$fileCsv['user_id']] = $this->model_stores->getStoresByCompany($user_data['company_id']);
                }

                $store_to_validation = array_map(function ($item) {
                    return $item['id'];
                }, $data_store_by_company);
            }

            // O usuário não gerencia a loja do arquivo.
            if (!in_array($store_id, $store_to_validation)) {
                throw new Exception("Loja '$store_csv' não foi encontrada para o usuário '$user_email'.");
            }
        }

        $store_data = $this->data_stores[$store_id] ?? null;
        if (is_null($store_data)) {
            $store_data = $this->data_stores[$store_id] = $this->model_stores->getStoreByIdOrName($store_id, $store_id);
        }

        // Não encontrou a loja
        if (empty($store_data)) {
            throw new Exception("Loja $store_id não encontrada.");
        }

        // Não existe código SKU para o novo produtos.
        if (empty($data['Sku do novo produto pai'])) {
            throw new Exception("Sku do novo produto pai não informado.");
        }
        // Não existe código SKU para a variação.
        if (empty($data['Sku do produto simples no seller center'])) {
            throw new Exception("Sku do produto simples no seller center deve ser informado.");
        }
        // Não existe código SKU.
        if (
            empty($data['Cor']) &&
            empty($data['Tamanho']) &&
            empty($data['Voltagem']) &&
            empty($data['Sabor'])
        ) {
            throw new Exception("Deve ser informado pelo menos um tipo de variação.");
        }

        $this->setStoreId($store_data['id']);
    }

    /**
     * Importa o arquivo csv
     *
     * @param array $csv
     */
    public function importCsv(array $csv)
    {
        $errors_import       = array();
        $new_file_with_error = array();
        $format_products     = array();
        $father_skus_error   = array();
        $error_lines         = array();
        $data_lines          = array();
        $var_sku_informed    = array();

        echo "Inciando para o csv: {$csv["upload_file"]}\n";

        try {
            $products = $this->csv_validation->convertCsvToArray($csv["upload_file"]);
        } catch (Exception $e) {
            $errors_import[] = array(
                "line"      => 0,
                "message"   => "O arquivo deve estar no formato UTF8, caso contrário alguns caracteres podem ficar desconfigurados."
            );

            $this->saveFinalImportFile($csv['id'], $errors_import);
            return;
        }

        foreach ($products as $line => $value) {
            $line = $line + 1;

            // Linha em branco.
            if ($this->csv_validation->lineEmptyCheck($value)) {
                echo "Linha $line em branco\n";
                continue;
            }

            // Faz as validações iniciais.
            try {
                $this->initValidation($csv, $value);

                $store              = $value['ID da Loja'];
                $store_id           = $this->getStoreId();
                $father_sku         = $value['Sku do novo produto pai'];
                $variation_sku      = $value['Sku do produto simples no seller center'];
                $father_sku_exist   = false;

                if (in_array($variation_sku, $var_sku_informed)) {
                    throw new Exception("Sku $variation_sku informado para o sku do produto simples no seller center já em uso no arquivo enviado.");
                }

                $var_sku_informed[] = $variation_sku;

                $product_to_merge = $this->model_products->getProductBySkuAndStore($variation_sku, $store_id);

                // Produto simples não encontrado.
                if (!$product_to_merge) {
                    throw new Exception("Sku $variation_sku informado para o sku do produto simples no seller center não encontrado para a loja $store.");
                }

                // Produto excluído
                if ($product_to_merge['status'] == Model_products::DELETED_PRODUCT) {
                    throw new Exception("Sku $variation_sku informado para o sku do produto simples no seller center está na lixeira.");
                }

                // Sku já está em uso.
                if (!$this->model_products->checkSkuAvailable($father_sku, $store_id)) {
                    $check_father_sku = $this->model_products->getProductBySkuAndStore($father_sku, $store_id);
                    if ($check_father_sku && $check_father_sku['is_variation_grouped'] == 1) {
                        $father_sku_exist = true;
                    } else {
                        throw new Exception("Sku do novo produto pai já está em uso.");
                    }
                }

                // Produto não é simples.
                if (!empty($product_to_merge['has_variants'])) {
                    throw new Exception("Sku do produto simples no seller center não é um produto simples.");
                }

                // Produto já aprovado na publicação.
                if ($this->model_integrations->checkIfExistProductByPrd($product_to_merge['id'])) {
                    throw new Exception("Sku do produto simples no seller center já está em processo de publicação.");
                }

                $error_lines[$variation_sku] = $line;
                $data_lines[$variation_sku] = $value;

            } catch (Exception $exception) {
                $errors_import[] = array(
                    "line"      => $line,
                    "message"   => $exception->getMessage()
                );
                $new_file_with_error[] = $value;
                echo "[ ERROR ] " . $exception->getMessage() . "\n";

                if (
                    !empty($value['Sku do novo produto pai']) &&
                    !in_array($value['Sku do novo produto pai'], $father_skus_error)
                ) {
                    $father_skus_error = [];
                }

                continue;
            }

            if (in_array($value['Sku do novo produto pai'], $father_skus_error)) {
                continue;
            }

            if (!array_key_exists($this->getStoreId(), $format_products)) {
                $format_products[$this->getStoreId()] = array();
            }
            if (!array_key_exists($father_sku, $format_products[$this->getStoreId()])) {
                $format_products[$this->getStoreId()][$father_sku] = array(
                    'variations'        => array(),
                    'product'           => $variation_sku,
                    'father_sku_exist'  => $father_sku_exist
                );
            }

            $format_products[$this->getStoreId()][$father_sku]['variations'][$variation_sku] = [];

            if (!empty($value['Tamanho'])) {
                $format_products[$this->getStoreId()][$father_sku]['variations'][$variation_sku]['TAMANHO'] = $value['Tamanho'];
            }
            if (!empty($value['Cor'])) {
                $format_products[$this->getStoreId()][$father_sku]['variations'][$variation_sku]['Cor'] = $value['Cor'];
            }
            if (!empty($value['Voltagem'])) {
                $format_products[$this->getStoreId()][$father_sku]['variations'][$variation_sku]['VOLTAGEM'] = $value['Voltagem'];
            }
            if (!empty($value['Sabor'])) {
                $format_products[$this->getStoreId()][$father_sku]['variations'][$variation_sku]['SABOR'] = $value['Sabor'];
            }
        }

        foreach ($format_products as $store_id => $products) {
            foreach ($products as $new_product_sku => $product) {
                $father_sku_exist_data = [];
                $father_sku_exist = $product['father_sku_exist'];
                if ($father_sku_exist) {
                    $father_sku_exist_data = $this->model_products->getProductBySkuAndStore($new_product_sku, $store_id);
                }

                try {
                    $this->checkVariationNewProduct($product['variations'], $father_sku_exist_data['has_variants'] ?? '');
                } catch (Exception $exception) {
                    $errors_import[] = array(
                        "line"      => $error_lines[key($product['variations'])],
                        "message"   => $exception->getMessage()
                    );
                    $new_file_with_error[] = $data_lines[key($product['variations'])];
                    echo "[ ERROR ] " . $exception->getMessage() . "\n";
                    continue;
                }

                $father_sku = $this->model_products->getProductBySkuAndStore($father_sku_exist ? $new_product_sku : key($product['variations']), $store_id);

                $this->db->trans_begin();

                // Adiciona variação no produto.
                $new_image_path = $father_sku_exist ? $father_sku['image'] : get_instance()->getGUID(false);

                $serverpath = $_SERVER['SCRIPT_FILENAME'];
                $pos        = strpos($serverpath,'assets');
                $serverpath = substr($serverpath,0,$pos);

                if (!$father_sku_exist) {
                    pathCopy(
                        "{$serverpath}assets/images/product_image/$father_sku[image]",
                        "{$serverpath}assets/images/product_image/$new_image_path/"
                    );
                }

                $variant = 0;
                if ($father_sku_exist) {
                    $variant = count($this->model_products->getVariantsByProd_id($father_sku['id']));
                }
                $product_qty = $father_sku_exist_data['qty'] ?? 0;
                foreach ($product['variations'] as $variation_sku => $variation) {
                    $name = implode(';', array_values($variation));

                    if (!$father_sku_exist && $father_sku['sku'] == $variation_sku) {
                        $data_variant = $father_sku;
                    } else {
                        $data_variant = $this->model_products->getProductBySkuAndStore($variation_sku, $store_id);
                    }

                    $product_qty += $data_variant['qty'];

                    $prd_varitant = [
                        'prd_id'         => $father_sku['id'],
                        'variant'        => $variant,
                        'name'           => $name,
                        'sku'            => $variation_sku,
                        'price'          => $data_variant['price'],
                        'qty'            => $data_variant['qty'],
                        'image'          => $data_variant['image'],
                        'status'         => $data_variant['status'],
                        'EAN'            => $data_variant['EAN'],
                        'variant_id_erp' => $data_variant['product_id_erp'],
                        'list_price'     => $data_variant['list_price'],
                        'created_at'     => $data_variant['date_create']
                    ];

                    $this->model_products->createvar($prd_varitant);

                    // criar imagem
                    pathCopy(
                        "{$serverpath}assets/images/product_image/$data_variant[image]",
                        "{$serverpath}assets/images/product_image/$new_image_path/$data_variant[image]/"
                    );

                    $variant++;

                    if ($father_sku_exist || $father_sku['sku'] != $variation_sku) {
                        $retorno = $this->deleteProduct->moveToTrash([$data_variant]);
                        if (isset($retorno['errors'])) {
                            $errors_import[] = array(
                                "line"      => $error_lines[$variation_sku],
                                "message"   => implode(' | ', $retorno['errors'])
                            );
                            $new_file_with_error[] = $data_lines[$variation_sku];
                            echo "[ ERROR ] " . implode(' | ', $retorno['errors']) . "\n";
                            $this->db->trans_rollback();
                            continue 2;
                        }
                    }
                }

                if ($father_sku_exist) {
                    $data_to_product_update = [
                        'qty' => $product_qty,
                    ];
                } else {
                    $data_to_product_update = [
                        'sku'                   => $new_product_sku,
                        'qty'                   => $product_qty,
                        'image'                 => $new_image_path,
                        'has_variants'          => implode(';', array_keys($product['variations'][key($product['variations'])])),
                        'product_id_erp'        => null,
                        'principal_image'       => is_null($father_sku['principal_image']) ? null : str_replace($father_sku['image'], $new_image_path, $father_sku['principal_image']),
                        'is_variation_grouped'  => 1
                    ];
                }

                $this->model_products->update(
                    $data_to_product_update,
                    $father_sku['id'],
                    'Produto transformado em variação'
                );

                if ($this->db->trans_status() === false) {
                    $this->db->trans_rollback();
                    echo "ocorreu um erro no banco de dados\n";
                    $errors_import[] = array(
                        "line"      => 0,
                        "message"   => "ocorreu um erro no banco de dados"
                    );
                } else {
                    $this->db->trans_commit();
                    echo "Novo produto com sku $new_product_sku criado com sucesso.\n";
                }

                $cnt = $this->model_queue_products_marketplace->countQueue();

                while($cnt['qtd'] > 400) {
                    echo "Esperar 15 segundos pois tem $cnt[qtd] na fila\n";
                    sleep(15);
                    $cnt = $this->model_queue_products_marketplace->countQueue();
                }
            }
        }

        // Se existem erros, criará um arquivo com as linhas com erros.
        if (!empty($errors_import)) {
            try {
                $this->csv_validation->createNewFileCsv(str_replace('.csv', '_with_error.csv', $csv["upload_file"]), $new_file_with_error, $csv["upload_file"]);
            } catch (Exception $exception) {
                $errors_import[] = array(
                    "line"      => 0,
                    "message"   => $exception->getMessage()
                );
            }
        }

        $this->saveFinalImportFile($csv['id'], $errors_import);
    }

    /**
     * @param   array $variations
     * @return  void
     * @throws  Exception
     */
    private function checkVariationNewProduct(array $variations, string $has_variants)
    {
        $check_types = !empty($has_variants) ? explode(';', $has_variants) : [];
        foreach ($variations as $variation) {
            $types = array_keys($variation);
            if (empty($check_types)) {
                $check_types = $types;
                continue;
            }

            if (array_diff($check_types, $types)) {
                throw new Exception("O tipo de variação dos produtos não se correspondem. " . implode(', ', array_keys($variations)));
            }
        }
    }

    /**
     * Salva o processamento.
     *
     * @param   int     $csv_id
     * @param   array   $errors_import
     */
    private function saveFinalImportFile(int $csv_id, array $errors_import)
    {
        $processing_response = null;
        $situation = 'success';

        if (!empty($errors_import)) {
            // Ordena os erros de forma crescente.
            $errors_import_to_save = array();
            foreach (array_msort($errors_import, array('line' => SORT_ASC)) as $data) {
                $errors_import_to_save[] = $data;
            }

            $processing_response = json_encode($errors_import_to_save, JSON_UNESCAPED_UNICODE);
            $situation = 'err';
        }

        $this->model_csv_to_verifications->setChecked($csv_id, $situation, $processing_response);
    }
}
