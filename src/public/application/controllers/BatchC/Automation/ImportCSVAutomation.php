<?php

require APPPATH . "libraries/Integration_v2/internal_api/ToolsProduct.php";

use Integration\Integration_v2\internal_api\ToolsProduct;
use League\Csv\ByteSequence;
use League\Csv\Reader;
use League\Csv\TabularDataReader;

use function PHPSTORM_META\type;

/**
 * @property CI_Session $session
 * @property CI_Loader $load
 * @property CI_Lang $lang
 * @property CI_Router $router
 *
 * @property Model_csv_to_verifications $model_csv_to_verifications
 * @property Model_attributes $model_attributes
 * @property Model_stores $model_stores
 * @property Model_orders $model_orders
 * @property Model_category $model_category
 * @property Model_brands $model_brands
 * @property Model_integrations $model_integrations
 * @property Model_products $model_products
 * @property Model_groups $model_groups
 * @property Model_users $model_users
 * 
 * @property CSV_Validation $csv_validation
 * @property UploadProducts $uploadproducts
 * @property BlacklistOfWords $blacklistofwords
 *
 *
 * @property ToolsProduct $toolProduct
 */
class ImportCSVAutomation extends BatchBackground_Controller
{
    private $all_string_verification_if_utf8_encode = [
        'Nome do Item',
        'Sku do Parceiro',
        'Sku Produto Pai',
        'SKU no fabricante',
        'Categoria',
        'SKU no fabricante',
        'Fabricante',
        'Descricao do Item _ Informacoes do Produto',
        'Origem do Produto _ Nacional ou Estrangeiro',
    ];

    /**
     * @var object 
     */
    private $fields_csv = array(
        'store_id'              => 'ID da Loja',
        'sku'                   => 'Sku do Parceiro',
        'sku_pai'               => 'Sku Produto Pai',
        'name'                  => 'Nome do Item',
        'price'                 => 'Preco de Venda',
        'list_price'            => 'Preco de lista',
        'qty'                   => 'Quantidade em estoque',
        'manufacturer'          => 'Fabricante',
        'sku_manufacturer'      => 'SKU no fabricante',
        'category'              => 'Categoria',
        'ean'                   => 'EAN',
        'net_weight'            => 'Peso Liquido em kgs',
        'gross_weight'          => 'Peso Bruto em kgs',
        'width'                 => 'Largura em cm',
        'height'                => 'Altura em cm',
        'depth'                 => 'Profundidade em cm',
        'ncm'                   => 'NCM',
        'origin'                => 'Origem do Produto _ Nacional ou Estrangeiro',
        'guarantee'             => 'Garantia em meses',
        'extra_operating_time'  => 'Prazo Operacional em dias',
        'products_package'      => 'Produtos por embalagem',
        'description'           => 'Descricao do Item _ Informacoes do Produto',
        'images'                => 'Imagens',
        'status'                => 'Status(1=Ativo|2=Inativo|3=Lixeira)',
        'product_id'            => 'ID Produto'
    );

    /**
     * @var array[] $format_products Dados formatados dos produtos e variações para enviar na API.
     */
    private $format_products = array(
        'create_product'    => array(),
        'update_product'    => array(),
        'delete_product'    => array(),
        'create_variation'  => array(),
        'update_variation'  => array()
    );

    /**
     * @var int $store_id Código da loja.
     */
    private $store_id;

    /**
     * @var string $product_sku Código SKU do product.
     */
    private $product_sku;

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

    /**
     * @var array $product_by_sku_and_store
     */
    private $product_by_sku_and_store;

    /**
     * @var array $variation_by_sku_and_store
     */
    private $variation_by_sku_and_store;

    /**
     * @var array $data_categories
     */
    private $data_categories;

    /**
     * @var array $errors_import
     */
    private $errors_import;

    /**
     * @var array $new_file_with_error
     */
    private $new_file_with_error;

    /**
     * @var array $variation_lines
     */
    private $variation_lines;

    /**
     * @var array $product_lines
     */
    private $product_lines;

    /**
     * @var TabularDataReader $products
     */
    private $products;

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
        $this->load->model('model_category');
        $this->load->model('model_products');
        $this->load->model('model_groups');
        $this->load->model('model_users');
        $this->load->library('CSV_Validation');

        $this->toolProduct = new ToolsProduct();
    }
    
    // php index.php BatchC/Automation/ImportCSVAutomation run
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

        $csvs_to_import = $this->model_csv_to_verifications->getDontChecked();
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
     * @return string
     */
    private function getProductSku(): string
    {
        return $this->product_sku;
    }

    /**
     * @param string $product_sku
     * @return void
     */
    private function setProductSku(string $product_sku)
    {
        $this->product_sku = $product_sku;
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

        // Não existe código SKU.
        if (empty($data['Sku do Parceiro'])) {
            throw new Exception("Sku do Parceiro deve ser informado.");
        }
        if(isset($data['Preco de Venda']) && isset($data['Preco de lista'])) {
            $precoVenda = $data['Preco de Venda'];
            $precoLista = $data['Preco de lista'];
            if (empty($precoVenda) && empty($precoLista)) {
                throw new Exception($this->lang->line('application_prices_error'));
            }            
        } else if(isset($item['Preco de Venda'])) {
            $precoVenda = $item['Preco de Venda'];
            if (empty($precoVenda)) {
                throw new Exception($this->lang->line('application_prices_error'));                
            }
        } else if(isset($item['Preco de lista'])) {
            $precoLista = $item['Preco de lista'];
            if (empty($precoLista)) {
                throw new Exception($this->lang->line('application_prices_error'));
            }
        }

       

        $is_variation   = array_key_exists('Sku Produto Pai', $data) && !empty($data['Sku Produto Pai']);
        $key_sku        = trim($is_variation ? $data['Sku Produto Pai'] : $data['Sku do Parceiro']);

        $this->setStoreId($store_data['id']);
        $this->setProductSku($key_sku);
    }

    /**
     * Importa o arquivo csv
     *
     * @param array $csv
     */
    public function importCsv(array $csv)
    {
        $this->errors_import        = array();
        $this->new_file_with_error  = array();
        $this->variation_lines      = array();
        $this->product_lines        = array();
        $this->products             = array();

        echo "Inciando para o csv: {$csv["upload_file"]}\n";

        $store_import = array();

        try {
            $this->products = $this->csv_validation->convertCsvToArray($csv["upload_file"]);
        } catch (Exception $e) {
            $this->errors_import[] = array(
                "line"      => 0,
                "message"   => "O arquivo deve estar no formato UTF8, caso contrário alguns caracteres podem ficar desconfigurados."
            );

            $this->saveFinalImportFile($csv['id']);
            return;
        }

        /*if (ByteSequence::BOM_UTF8 !== Reader::createFromPath($csv["upload_file"])->getInputBOM()) {
            $this->errors_import[] = array(
                "line"      => 0,
                "message"   => "O arquivo deve estar no formato UTF8, caso contrário alguns caracteres podem ficar desconfigurados."
            );

            $this->saveFinalImportFile($csv['id']);
            return;
        }*/

        foreach ($this->products as $line => $value) {
            $line            = $line + 1;
            $variation_exist = false;


            // Linha em branco.
            if ($this->csv_validation->lineEmptyCheck($value)) {
                echo "Linha $line em branco\n";
                continue;
            }

            // Faz as validações iniciais.
            try {
                $this->initValidation($csv, $value);
            } catch (Exception $exception) {
                $this->errors_import[] = array(
                    "line"      => $line,
                    "message"   => $exception->getMessage()
                );
                $this->new_file_with_error[] = $value;
                echo $exception->getMessage() . "\n";
                continue;
            }
            $sku_product_father = $value['Sku Produto Pai'] ?? null;
            $sku = $value['Sku do Parceiro'];

            // Guarda qual a linha do produto/variação para mostrar em casos de erro.
            $is_variation = array_key_exists('Sku Produto Pai', $value) && !empty($sku_product_father);

            if ($is_variation) {
                $this->variation_lines[$sku] = $line;
            } else {
                $this->product_lines[$sku] = $line;
            }
           
            // Grava o código da loja para ser realizado a requisição loja a loja.
            if (!in_array($this->getStoreId(), $store_import)) {
                $store_import[] = $this->getStoreId();
            }

            // Define o código da loja que foi recuperado.
            $value['ID da Loja'] = $this->getStoreId();
            if ($is_variation) {
                $key_product_validation     = "$sku_product_father-{$this->getStoreId()}";
                $key_variation_validation   = "$sku_product_father-$sku-{$this->getStoreId()}";

                // Verifica se o produto já existe.
                $product_exist = $this->product_by_sku_and_store[$key_product_validation] ?? null;
                if (is_null($product_exist)) {
                    $product_exist = $this->product_by_sku_and_store[$key_product_validation] = !empty($this->model_products->getProductBySkuAndStore($sku_product_father, $this->getStoreId()));
                }

                // Verifica se a variação já existe.
                $variation_exist = $this->variation_by_sku_and_store[$key_variation_validation] ?? null;
                if (is_null($variation_exist)) {
                    $variation_exist = $this->variation_by_sku_and_store[$key_variation_validation] = !empty($this->model_products->getVariationForSkuAndSkuVar($sku_product_father, $this->getStoreId(), $sku));
                }
            } else {
                // Verifica se o produto já existe.
                $key_product_validation = "$sku-{$this->getStoreId()}";
                $product_exist = $this->product_by_sku_and_store[$key_product_validation] ?? null;
                if (is_null($product_exist)) {
                    $product_exist = $this->product_by_sku_and_store[$key_product_validation] = !empty($this->model_products->getProductBySkuAndStore($sku, $this->getStoreId()));
                }
            }

            // Formata os dados e adiciona no vetor '$format_products' de acorodo com a regra, se for para criar/atualizar uma variação/produto.
            if ($is_variation) {
                $this->formatVariation($value, $product_exist, $variation_exist);
            } else {
                $this->formatProduct($value, $product_exist);
            }
        }

        foreach ($store_import as $store) {
            // Verifica se as informações da loja estão corretas.
            try {
                $this->toolProduct->startRun($store);
                $this->toolProduct->putCredentialsApiInternal($this->data_users['id']);
            } catch (InvalidArgumentException $exception) {
                $error_message = $exception->getMessage();
                echo "[ ERROR ][CHECK_STORE] Loja: $store. $error_message\n";
                if (array_key_exists($store, $this->format_products['create_product'])) {
                    $this->setErrorLine($this->format_products['create_product'][$store], $error_message, 'create_product');
                }
                if (array_key_exists($store, $this->format_products['update_product'])) {
                    $this->setErrorLine($this->format_products['update_product'][$store], $error_message, 'update_product');
                }
                if (array_key_exists($store, $this->format_products['create_variation'])) {
                    foreach ($this->format_products['create_variation'][$store] as $variation) {
                        $variations_formatted = $this->toolProduct->getDataFormattedToIntegration(array('variations' => $variation));
                        $this->setErrorLine($variations_formatted['variations']['value'], $error_message, 'create_variation');
                    }
                }
                if (array_key_exists($store, $this->format_products['update_variation'])) {
                    foreach ($this->format_products['update_variation'][$store] as $variation) {
                        $variations_formatted = $this->toolProduct->getDataFormattedToIntegration(array('variations' => $variation));
                        $this->setErrorLine($variations_formatted['variations']['value'], $error_message, 'update_variation');
                    }
                }
                if (array_key_exists($store, $this->format_products['delete_product'])) {
                    $this->setErrorLine($this->format_products['delete_product'][$store], $error_message, 'delete_product');
                }

                continue;
            }

            // Criação de produtos.
            if (array_key_exists($store, $this->format_products['create_product'])) {
                $this->toolProduct->setJob('CreateProduct');
                foreach ($this->format_products['create_product'][$store] as $product) {
                    try {
                        $response = $this->toolProduct->createProduct($this->toolProduct->getDataFormattedToIntegration($product));
                        if (!$response) {
                            $erro = $this->toolProduct->getRequestResponse();
                            if(is_string($erro))
                                throw new InvalidArgumentException($erro);
                            else if(method_exists($erro, 'getMessage'))
                               throw new InvalidArgumentException($erro->getMessage());
                            else
                              throw new InvalidArgumentException('Internal Erro Processamento');
                        }
                        echo "[SUCCESS][CREATE_PRODUCT] SKU: {$product['sku']}\n";
                    } catch (InvalidArgumentException $exception) {
                        $this->setLinesWithErrorToNewFile('create_product', $product);
                        if (array_key_exists($product['sku'], $this->product_lines)) {
                            $this->errors_import[] = array(
                                "line" => $this->product_lines[$product['sku']],
                                "message" => $exception->getMessage()
                            );
                            echo "[ ERROR ][CREATE_PRODUCT] SKU1: {$product['sku']}. {$exception->getMessage()}\n";
                        }
                    }
                }
            }
            // Atualização de produtos.
            if (array_key_exists($store, $this->format_products['update_product'])) {
                $this->toolProduct->setJob('UpdateProduct');
                foreach ($this->format_products['update_product'][$store] as $product) {
                    try {
                        $response = $this->toolProduct->updateProduct($this->toolProduct->getDataFormattedToIntegration($product));
                        if (!$response) {
                            $erro = $this->toolProduct->getRequestResponse();
                            if(is_string($erro))
                               throw new InvalidArgumentException($erro);
                            else if(method_exists($erro, 'getMessage'))
                               throw new InvalidArgumentException($erro->getMessage());
                            else
                                throw new InvalidArgumentException('Internal Erro Processamento');
                        }
                        echo "[SUCCESS][UPDATE_PRODUCT] SKU: {$product['sku']}\n";
                    } catch (InvalidArgumentException $exception) {
                        $this->setLinesWithErrorToNewFile('update_product', $product);
                        if (array_key_exists($product['sku'], $this->product_lines)) {
                            $this->errors_import[] = array(
                                "line" => $this->product_lines[$product['sku']],
                                "message" => $exception->getMessage()
                            );
                            echo "[ ERROR ][UPDATE_PRODUCT] SKU: {$product['sku']}. {$exception->getMessage()}\n";
                        }
                    }
                }
            }
            // Criação de variações.
            if (array_key_exists($store, $this->format_products['create_variation'])) {
                $this->toolProduct->setJob('UpdateProduct');
                foreach ($this->format_products['create_variation'][$store] as $sku_product => $variation) {
                    $variations_formatted = $this->toolProduct->getDataFormattedToIntegration(array('variations' => $variation));
                    foreach ($variations_formatted['variations']['value'] as $variation_formatted) {
                        try {
                            $response = $this->toolProduct->createVariation($variation_formatted, $sku_product);
                            if (!$response) {
                            $erro = $this->toolProduct->getRequestResponse();
                                if(is_string($erro))
                                    throw new InvalidArgumentException($erro);
                                else if(method_exists($erro, 'getMessage'))
                                    throw new InvalidArgumentException($erro->getMessage());
                                else
                                    throw new InvalidArgumentException('Internal Erro Processamento');
                            }
                            echo "[SUCCESS][CREATE_VARIATION] SKU do pai: $sku_product. SKU da variação: {$variation_formatted['sku']}\n";
                        } catch (InvalidArgumentException $exception) {
                            $this->setLinesWithErrorToNewFile('create_variation', $variation_formatted);
                            if (array_key_exists($variation_formatted['sku'], $this->variation_lines)) {
                                $this->errors_import[] = array(
                                    "line" => $this->variation_lines[$variation_formatted['sku']],
                                    "message" => $exception->getMessage()
                                );
                                echo "[ ERROR ][CREATE_VARIATION] SKU do pai: $sku_product. SKU da variação: {$variation_formatted['sku']}. {$exception->getMessage()}\n";
                            }
                        }
                    }
                }
            }
            // Atualização de variações.
            if (array_key_exists($store, $this->format_products['update_variation'])) {
                $this->toolProduct->setJob('UpdateProduct');
                foreach ($this->format_products['update_variation'][$store] as $sku_product => $variation) {
                    $variations_formatted = $this->toolProduct->getDataFormattedToIntegration(array('variations' => $variation));
                    foreach ($variations_formatted['variations']['value'] as $variation_formatted) {
                        try {
                            $response = $this->toolProduct->updateVariation($variation_formatted, $sku_product);
                            if (!$response) {
                                $erro = $this->toolProduct->getRequestResponse();
                            if(is_string($erro))
                               throw new InvalidArgumentException($erro);
                            else if(method_exists($erro, 'getMessage'))
                               throw new InvalidArgumentException($erro->getMessage());
                            else
                                throw new InvalidArgumentException('Internal Erro Processamento');
                            }
                            echo "[SUCCESS][UPDATE_VARIATION] SKU do pai: $sku_product. SKU da variação: {$variation_formatted['sku']}\n";
                        } catch (InvalidArgumentException $exception) {
                            $this->setLinesWithErrorToNewFile('update_variation', $variation_formatted);
                            if (array_key_exists($variation_formatted['sku'], $this->variation_lines)) {
                                $this->errors_import[] = array(
                                    "line" => $this->variation_lines[$variation_formatted['sku']],
                                    "message" => $exception->getMessage()
                                );
                                echo "[ ERROR ][UPDATE_VARIATION] SKU do pai: $sku_product. SKU da variação: {$variation_formatted['sku']}. {$exception->getMessage()}\n";
                            }
                        }
                    }
                }
            }
            // Excluir produtos.
            if (array_key_exists($store, $this->format_products['delete_product'])) {
                $this->toolProduct->setJob('UpdateProduct');
                foreach ($this->format_products['delete_product'][$store] as $product) {
                    try {
                        $this->toolProduct->trashProduct($product['sku']);
                        echo "[SUCCESS][DELETE_PRODUCT] SKU: {$product['sku']}}\n";
                    } catch (InvalidArgumentException $exception) {
                        $this->setLinesWithErrorToNewFile('delete_product', $product);
                        if (array_key_exists($product['sku'], $this->product_lines)) {
                            $this->errors_import[] = array(
                                "line" => $this->product_lines[$product['sku']],
                                "message" => $exception->getMessage()
                            );
                            echo "[ ERROR ][DELETE_PRODUCT] SKU: {$product['sku']}. {$exception->getMessage()}\n";
                        }
                    }
                }
            }
        }

        // Se existem erros, criará um arquivo com as linhas com erros.
        if (!empty($this->errors_import)) {
            try {
                $this->csv_validation->createNewFileCsv(str_replace('.csv', '_with_error.csv', $csv["upload_file"]), $this->new_file_with_error, $csv["upload_file"]);
            } catch (Exception $exception) {
                $this->errors_import[] = array(
                    "line"      => 0,
                    "message"   => $exception->getMessage()
                );
            }
        }

        $this->saveFinalImportFile($csv['id']);
    }

    /**
     * Alimenta os vetores com a linha de erro e o motivo em cada linha.
     *
     * @param   array   $values
     * @param   string  $message
     * @param   string  $type
     * @return  void
     */
    private function setErrorLine(array $values, string $message, string $type)
    {
        foreach ($values as $value) {
            $this->errors_import[] = array(
                "line"      => likeText('%product%', $type) ? ($this->product_lines[$value['sku']] ?? 0) : ($this->variation_lines[$value['sku']] ?? 0),
                "message"   => $message
            );
            $this->setLinesWithErrorToNewFile($type, $value);
        }
    }

    /**
     * Salva o processamento.
     *
     * @param   int $csv_id
     */
    private function saveFinalImportFile(int $csv_id)
    {
        $processing_response = null;
        $situation = 'success';

        if (!empty($this->errors_import)) {
            // Ordena os erros de forma crescente.
            $errors_import_to_save = array();
            foreach (array_msort($this->errors_import, array('line' => SORT_ASC)) as $data) {
                $errors_import_to_save[] = $data;
            }

            $processing_response = json_encode($errors_import_to_save, JSON_UNESCAPED_UNICODE);
            $situation = 'err';
        }

        $this->model_csv_to_verifications->setChecked($csv_id, $situation, $processing_response);
    }

    /**
     * Corrigi os dados para UTF8
     *
     * @param array $data
     */
    public function parseToUtf8(array &$data)
    {
        foreach ($this->all_string_verification_if_utf8_encode as $value) {
            if (isset($data[$value]) && false === mb_check_encoding($data[$value], 'UTF-8')) {
                $data[$value] = utf8_encode($data[$value]);
            }
        }
    }

    /**
     * Salva as linhas com erros em 'new_file_with_error'.
     *
     * @param   string  $type
     * @param   array   $data
     * @return  void
     */
    private function setLinesWithErrorToNewFile(string $type, array $data)
    {
        try {
            // Deu erro na variação
            switch ($type) {
                case 'create_product':
                    $exist_variation = array_key_exists('variations', $data);
                    if (array_key_exists($data['sku'], $this->product_lines)) {
                        $this->new_file_with_error[] = $this->products->fetchOne($this->product_lines[$data['sku']]);
                    }

                    if ($exist_variation) {
                        foreach ($data['variations'] as $variation) {
                            if (array_key_exists($variation['sku'], $this->variation_lines)) {
                                $line = $this->variation_lines[$variation['sku']];
                                $this->new_file_with_error[] = $this->products->fetchOne($line);
                            }
                        }
                    }

                    break;
                case 'delete_product':
                case 'update_product':
                    if (array_key_exists($data['sku'], $this->product_lines)) {
                        $this->new_file_with_error[] = $this->products->fetchOne($this->product_lines[$data['sku']]);
                    }
                    break;
                case 'update_variation':
                case 'create_variation':
                    if (array_key_exists($data['sku'], $this->variation_lines)) {
                        $this->new_file_with_error[] = $this->products->fetchOne($this->variation_lines[$data['sku']]);
                    }
                    break;
            }
        } catch (\League\Csv\Exception $e) {}
    }

    /**
     * Formatar dados do produto para atualização/criação.
     *
     * @param   array   $data           Dados do produto, vindo do csv.
     * @param   bool    $exist_product  Produto já existe?
     */
    public function formatProduct(array $data, bool $exist_product)
    {
        $product = $this->convertDataCsv($data, !$exist_product, 'product');

        $type = $exist_product ? 'update_product' : 'create_product';
        if (array_key_exists('status', $product) && $product['status'] == 3) {
            $type = 'delete_product';
        }

        if (!isset($this->format_products[$type][$this->getStoreId()][$this->getProductSku()])) {
            $this->format_products[$type][$this->getStoreId()][$this->getProductSku()] = array();
        }

        $this->format_products[$type][$this->getStoreId()][$this->getProductSku()] = array_merge($this->format_products[$type][$this->getStoreId()][$this->getProductSku()], $product);
    }

    /**
     * Formatar variação para atualização/criação.
     *
     * @param array $data               Dados do produto, vindo do csv.
     * @param bool  $product_exist      Produto já existe?
     * @param bool  $variation_exist    Variação já existe?
     */
    private function formatVariation(array $data, bool $product_exist, bool $variation_exist)
    {
        $variation = $this->convertDataCsv($data, !$variation_exist, 'variation');

        // Deve atualizar a variação.
        if ($product_exist && $variation_exist) {
            if (!isset($this->format_products['update_variation'][$this->getStoreId()][$this->getProductSku()])) {
                $this->format_products['update_variation'][$this->getStoreId()][$this->getProductSku()] = array();
            }
            $this->format_products['update_variation'][$this->getStoreId()][$this->getProductSku()][] = $variation;

        }
        // Deve criar uma variação no produto.
        else if ($product_exist && !$variation_exist) {
            if (!isset($this->format_products['create_variation'][$this->getStoreId()][$this->getProductSku()])) {
                $this->format_products['create_variation'][$this->getStoreId()][$this->getProductSku()] = array();
            }

            $this->format_products['create_variation'][$this->getStoreId()][$this->getProductSku()][] = $variation;
        }
        // Deve adicionar os campos 'variations' na criação do produto.
        else if (!$product_exist) {
            if (!isset($this->format_products['create_product'][$this->getStoreId()][$this->getProductSku()])) {
                $this->format_products['create_product'][$this->getStoreId()][$this->getProductSku()]['variations'] = array();
            }

            $this->format_products['create_product'][$this->getStoreId()][$this->getProductSku()]['variations'][] = $variation;
        }
    }

    /**
     * Formata os campos do CSV para o formato esperado para a importação.
     *
     * @param   array   $data           Dados do produto, vindo do csv.
     * @param   bool    $new            Produto/variação é nova.
     * @param   string  $type_import    Tipo da importação (variation/product).
     * @return  array
     */
    public function convertDataCsv(array $data, bool $new, string $type_import): array
    {
        $this->parseToUtf8($data);
        $convert = array();
        
        if (!empty($data[$this->fields_csv['sku']])) {
            $convert['sku'] = trim($data[$this->fields_csv['sku']]);
        }

        if (!empty($data[$this->fields_csv['price']])) {
            $convert['price'] = forceNumberToFloat($data[$this->fields_csv['price']]);
            // É um novo produto, então como padrão o 'preço de', será o 'preço por'.
            if ($new) {
                $convert['list_price'] = $convert['price'];
            }
        }

        if (!empty($data[$this->fields_csv['list_price']])) {
            $convert['list_price'] = forceNumberToFloat($data[$this->fields_csv['list_price']]);
        }

        if (!empty($data[$this->fields_csv['qty']]) || (array_key_exists($this->fields_csv['qty'], $data) && $data[$this->fields_csv['qty']] == 0)) {
            $convert['qty'] = trim($data[$this->fields_csv['qty']]);
        }

        if (!empty($data[$this->fields_csv['ean']])) {
            $convert['ean'] = trim($data[$this->fields_csv['ean']]);
        }

        if (!empty($data[$this->fields_csv['images']])) {
            $convert["images"] = explode(',', trim($data[$this->fields_csv['images']]));
        }

        if (!empty($data[$this->fields_csv['status']])) {
            $convert['status'] = trim($data[$this->fields_csv['status']]);
        }

        if ($type_import === 'variation') {
            $type_variation = array();
            // Adiciona os valores de variação
            foreach ($data as $key => $value) {
                $key = strtolower($key);
                $type = '';
                switch ($key) {
                    case 'tamanho':
                        $type = 'size';
                        break;
                    case 'cor':
                        $type=  'color';
                        break;
                    case 'voltagem':
                        $type = 'voltage';
                        break;
                    case 'sabor':
                        $type = 'flavor';
                        break;
                    case 'grau':
                        $type = 'degree';
                        break;
                    case 'lado':
                        $type = 'side';
                        break;    
                    default:
                        break;
                }

                if (empty($type) || empty($value) || in_array($type, $type_variation)) {
                    continue;
                }

                $type_variation[] = $type;
                $convert['variations'][$type] = $value;
            }
        } elseif ($type_import === 'product') {
            $convert['store_id'] = $data[$this->fields_csv['store_id']];
            if ($new) {
                $convert['products_package'] = 1;
                $convert['unity'] = 'UN';
            }

            if (!empty($data[$this->fields_csv['ncm']])) {
                $convert['ncm'] = str_replace(".", "", trim($data[$this->fields_csv['ncm']]));
            }

            if (!empty($data[$this->fields_csv['name']])) {
                $convert['name'] = trim($data[$this->fields_csv['name']]);
            }

            if (!empty($data[$this->fields_csv['description']])) {
                $convert['description'] = trim($data[$this->fields_csv['description']]);
            }

            if (!empty($data[$this->fields_csv['category']])) {
                $convert['category'] = $data[$this->fields_csv['category']];

                $category_data = $this->data_categories[trim($data[$this->fields_csv['category']])] ?? null;
                if (is_null($category_data)) {
                    $category_data = $this->data_categories[trim($data[$this->fields_csv['category']])] = $this->model_category->getcategoryName(trim($data[$this->fields_csv['category']]));
                }
                if ($category_data) {
                    $convert['category'] = $category_data;
                }
            }

            if (!empty($data[$this->fields_csv['sku_manufacturer']])) {
                $convert['sku_manufacturer'] = trim($data[$this->fields_csv['sku_manufacturer']]);
            }

            if (!empty($data[$this->fields_csv['net_weight']])) {
                $convert['net_weight'] = forceNumberToFloat($data[$this->fields_csv['net_weight']]);
            }

            if (!empty($data[$this->fields_csv['gross_weight']])) {
                $convert['gross_weight'] = forceNumberToFloat($data[$this->fields_csv['gross_weight']]);
            }

            if (!empty($data[$this->fields_csv['width']])) {
                $convert['width'] = forceNumberToFloat($data[$this->fields_csv['width']]);
            }

            if (!empty($data[$this->fields_csv['height']])) {
                $convert['height'] = forceNumberToFloat($data[$this->fields_csv['height']]);
            }

            if (!empty($data[$this->fields_csv['depth']])) {
                $convert['depth'] = forceNumberToFloat($data[$this->fields_csv['depth']]);
            }

            if (!empty($data[$this->fields_csv['guarantee']]) || (array_key_exists($this->fields_csv['guarantee'], $data) && $data[$this->fields_csv['guarantee']] == 0)) {
                $convert['guarantee'] = trim($data[$this->fields_csv['guarantee']]);
            }

            if (!empty($data[$this->fields_csv['origin']]) || (array_key_exists($this->fields_csv['origin'], $data) && $data[$this->fields_csv['origin']] == 0)) {
                $convert['origin'] = trim($data[$this->fields_csv['origin']]);
            }

            if (!empty($data[$this->fields_csv['manufacturer']])) {
                $convert['manufacturer'] = trim($data[$this->fields_csv['manufacturer']]);
            }

            if (!empty($data[$this->fields_csv['extra_operating_time']]) || (array_key_exists($this->fields_csv['extra_operating_time'], $data) && $data[$this->fields_csv['extra_operating_time']] == 0)) {
                $convert['extra_operating_time'] = trim($data[$this->fields_csv['extra_operating_time']]);
            }

            if (!empty($data[$this->fields_csv['products_package']]) || (array_key_exists($this->fields_csv['products_package'], $data) && $data[$this->fields_csv['products_package']] == 0)) {
                $convert['products_package'] = trim($data[$this->fields_csv['products_package']]);
            }
        }

        return $convert;
    }
}
