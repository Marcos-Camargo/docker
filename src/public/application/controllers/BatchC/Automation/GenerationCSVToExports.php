<?php

require_once APPPATH . "libraries/Microservices/v1/Integration/Price.php";
require_once APPPATH . "libraries/Microservices/v1/Integration/Stock.php";

use Microservices\v1\Integration\Price;
use Microservices\v1\Integration\Stock;

/**
 * @property     Bucket                     $bucket
 * @property     PrivateBucket              $privatebucket
 * 
 * @property     CI_Session                 $session
 * @property     CI_Loader                  $load
 * @property     CI_Router                  $router
 *
 * @property     Model_brands               $model_brands
 * @property     Model_category             $model_category
 * @property     Model_company              $model_company 
 * @property     Model_csv_generator_export $model_csv_generator_export
 * @property     Model_settings             $model_settings
 * @property     Model_users                $model_users
 *
 * @property     Price                      $ms_price
 * @property     Stock                      $ms_stock
 * @property     string                     $serverpath
 * @property     string                     $sellerCenter
 * @property     string                     $from
 */
class GenerationCSVToExports extends BatchBackground_Controller
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
            'logged_in' => true,
        );
        $this->session->set_userdata($logged_in_sess);

        // Carrega bibliotecas e models.
        $this->load->library('PrivateBucket');
        $this->load->library('Bucket');
        $this->load->model('model_brands');
        $this->load->model('model_users');
        $this->load->model('model_category');
        $this->load->model('model_csv_generator_export');
        $this->load->model('model_company');
        $this->serverpath = "assets/images/temp/csv_temp_export/";
        $this->sellerCenter = 'conectala';

        // Busca o seller center.
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
        if ($settingSellerCenter) {
            $this->sellerCenter = $settingSellerCenter['value'];
        }

        // Busca a origem do email.
        $this->from = 'api@conectala.com.br';
        $settingSellerEmail = $this->model_settings->getSettingDatabyName('email_marketing');
        if ($settingSellerEmail) {
            $this->from = $settingSellerEmail['value'];
        }
    }

    /**
     * Executa o job.
     * php index.php BatchC/Automation/GenerationCSVToExports run null null
     */
    public function run($id = null, $params = null)
    {
        // Inicia o job.
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . $this->router->fetch_class();

        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            echo "Já tem um job rodando!\n";
            return;
        }

        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params), "I");

        // Busca todos arquivos que ainda devem ser gerados.
        $csv = $this->model_csv_generator_export->findAllIfFile_create_dateIsNull();
        $request_to_generate = $csv->first_row();
        while ($request_to_generate) {

            // Echo no nome do arquivo criado.
            echo ($request_to_generate->file_name . "\n");

            // Executa a queue para gerar os arquivos exportados.
            // Result é o retorno do envio
            $result = $this->processQueue($request_to_generate);

            if ($result && $result['success']) {
                // Verifica quem criou o arquivo.
                $user = $this->model_users->getUserData($request_to_generate->user_id);
                $data = [];
                $company = $this->model_company->getCompanyData(1);
                $data['logo'] = base_url() . $company['logo'];
                $data['user'] = $user;


                // Ao invés de enviar a URL do arquivo para download, redireciona para a tela.
                // Caso necessite trocar para o arquivo em si, adicionar o retorno da chave do objeto e gerar URL pre-signed.
                $data['url_csv'] =  base_url() . 'DownloadCenter';
                $sellercenter_name = $this->model_settings->getValueIfAtiveByName('sellercenter_name');
                if (!$sellercenter_name) {
                    $sellercenter_name = 'Conecta Lá';
                }

                // Monta o template do email.
                $data['sellercentername'] = $sellercenter_name;
                if (is_file(APPPATH . 'views/mailtemplate/' . $this->sellerCenter . '/export_csv.php')) {
                    $body = $this->load->view('mailtemplate/' . $this->sellerCenter . '/export_csv', $data, TRUE);
                } else {
                    $body = $this->load->view('mailtemplate/default/export_csv', $data, TRUE);
                }
                $title = "Url do CSV exportado. Arquivo: {$request_to_generate->file_name}";

                // Realiza o envio do email.
                $this->sendEmailMarketing($user["email"], $title, $body, $this->from);
            }
            // Salva a exportação no banco.
            $this->model_csv_generator_export->update($request_to_generate->id, [
                'file_create_date' => date('Y-m-d H:i:s'),
                'file_delete_date' => date('Y-m-d H:i:s', strtotime('+7 days'))
            ]);
            $request_to_generate = $csv->next_row();
        }

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    /**
     * Realiza o processamento da queue.
     * @param    mixed      $request_to_generate
     */
    public function processQueue($request_to_generate)
    {
        $readonlydb = $this->load->database('readonly', TRUE);
        $query = $readonlydb->query($request_to_generate->sql_genereted . " LIMIT 1 OFFSET 0");
        if (!$query || !$query->num_rows() > 0) {
            echo "Esta exportação não apresenta nenhum produto válido." . "\n";
            return null;
        }
        $item = $query->unbuffered_row();
        $item = $this->formatItem((array)$item, $request_to_generate->type);
        if (!is_dir($this->serverpath)) {
            mkdir($this->serverpath, 0777, true);
        }
        $file = fopen('php://temp', 'w'); //opens file in append mode  
        if (!$file) {
            return null;
        }
        fwrite($file, "\xEF\xBB\xBF");

        $this->persistColumnNames($file, $item);
        if ($item) {
            $this->persistRowData($file, $item);
        }
        $offset = 1;
        $limit = 5000;
        while (true) {
            //$item = $query->next_row();
            $query = $readonlydb->query($request_to_generate->sql_genereted . " LIMIT " . $limit . " OFFSET " . $offset);
            if (!$query) {
                echo "Não foi possível executar a exportação.\n";
                break;
            }
            $items = $query->result();
            if (!$items) {
                echo "Acabou com menos de " . $offset . "\n";
                break;
            } else {
                echo "Lendo produtos " . $offset . "\n";
            }

            foreach ($items as $item) {
                $new_item = $this->formatItem((array)$item, $request_to_generate->type);
                $this->persistRowData($file, $new_item);

                // Verifico se estou exportando produtos de catálogo dá loja, neste caso, preciso buscar também as associações.
                if ($request_to_generate->type == 'ProductCatalogStore') {
                    // Busca os itens associados a este produto.
                    $associated_items = $this->getProductAssociations($item->id, $item->catalog_id);

                    // Se estiver vazio, vou para o próximo.
                    if (empty($associated_items)) {
                        continue;
                    }

                    // Percorre cada item e formata eles.
                    foreach ($associated_items as $associated_item) {
                        $associated_item = $this->formatItem((array)$associated_item, $request_to_generate->type);
                        $this->persistRowData($file, $associated_item);
                    }
                }
            }

            $offset += $limit;
        }
        return $this->privatebucket->sendFileToObjectStorage($file, $this->serverpath . $request_to_generate->file_name, true);
    }

    /**
     * Realiza a formatação do item baseado no tipo.
     * @param    array      $item   Array contendo o item a ser formatado.
     * @param    string     $type   Tipo do item.
     */
    public function formatItem(array $item, $type)
    {
        switch ($type) {
            case 'Product':
                return $this->formatProduct($item);
            case 'Variation':
                return $this->formatVariation($item);
            case 'ProductCatalog':
                return $this->formatProductCatalog($item);
            case 'ProductCatalogStore':
                return $this->formatProductCatalogStore($item);
        }
    }

    /**
     * Realiza a formatação do produto.
     * @param    array      $item   Produto a ser formatado.
     *
     * @return   array      Retorna o item pós formatação.
     */
    public function formatProduct(array $item)
    {
        // Trata o caso de exportação vir do somaplace.
        if ($this->sellerCenter == 'somaplace') {
            return [
                "ID da Loja" => $item['store_id'],
                "Sku Produto Pai" => utf8_decode($item['sku']),
                "EAN" => $item['EAN'],
                "Nome do Item" => $item['name'],
                "Preco de Venda" => floatval($item['price']),
                "Quantidade em estoque" => $item['qty'],
            ];
        }

        // Busca as informações necessárias para montar o array.
        $brand = (int) filter_var($item['brand_id'], FILTER_SANITIZE_NUMBER_INT);
        $brand_name = $brand == 0 ? "" : $this->model_brands->getBrandData($brand)['name'];
        $category = (int) filter_var($item['category_id'], FILTER_SANITIZE_NUMBER_INT);
        $category_name = $category == 0 ? "" : $this->model_category->getCategoryData($category)['name'];
        $arrProducts = array(
            "ID da Loja" => $item['store_id'],
            "Nome da Loja" => $item['store_name'],
            "ID Produto" => $item['id'],
            "Sku do Parceiro" => utf8_decode($item['sku']),
            "Nome do Item" => $item['name'], //utf8_decode($product['name']),
            "Preco de Venda" => floatval($item['price']),
            "Preco de Lista" => $item['list_price'],
            "Quantidade em estoque" => $item['qty'],
            "Fabricante" => $brand_name, //utf8_decode($brand_name),
            "SKU no fabricante" => utf8_decode($item['codigo_do_fabricante']),
            "Categoria" => $category_name, //utf8_decode($category_name),
            "EAN" => $item['EAN'],
            "Peso Liquido em kgs" => $item['peso_liquido'],
            "Peso Bruto em kgs" => $item['peso_bruto'],
            "Largura em cm" => $item['largura'],
            "Altura em cm" => $item['altura'],
            "Profundidade em cm" => $item['profundidade'],
            "NCM" => $item['NCM'],
            "Origem do Produto _ Nacional ou Estrangeiro" => $item['origin'],
            "Garantia em meses" => $item['garantia'],
            "Prazo Operacional em dias" => $item['prazo_operacional_extra'],
            "Produtos por embalagem" => $item['products_package'],
            "Descricao do Item _ Informacoes do Produto" => $item['description'], //utf8_decode($product['description']),
            "Imagens" => $this->getImagens($item),
            "Status(1=Ativo|2=Inativo|3=Lixeira)" => $item['status']
        );

        // Caso tenha integrações, adiciona elas.
        if (isset($item['Integrations'])) {
            $arrProducts["Integrations"] = $item['Integrations'];
        }
        return $arrProducts;
    }

    /**
     * Realiza a formatação de produto com variação.
     * @param    array      $item   Produto com variações a ser formatado.
     *
     * @return   array      Retorna o item pós formatação.     
     */
    public function formatVariation(array $item)
    {
        // Busca as informações necessárias para montar o array.
        $brand = (int) filter_var($item['brand_id'], FILTER_SANITIZE_NUMBER_INT);
        $brand_name = $brand == 0 ? "" : $this->model_brands->getBrandData($brand)['name'];
        $category = (int) filter_var($item['category_id'], FILTER_SANITIZE_NUMBER_INT);
        $category_name = $category == 0 ? "" : $this->model_category->getCategoryData($category)['name'];
        $variants_labels = explode(';', $item['has_variants']);
        $variants = explode(';', $item['variant_name']);

        // Busca as informações referentes as variações.
        $item['variant_cor'] = in_array('Cor', $variants_labels) ? $variants[array_search('Cor', $variants_labels)] : '';
        $item['variant_voltagem'] = in_array('VOLTAGEM', $variants_labels) ? $variants[array_search('VOLTAGEM', $variants_labels)] : '';
        $item['variant_tamanho'] = in_array('TAMANHO', $variants_labels) ? $variants[array_search('TAMANHO', $variants_labels)] : '';
        $item['variant_sabor'] = in_array('SABOR', $variants_labels) ? $variants[array_search('SABOR', $variants_labels)] : '';
        $item['variant_grau'] = in_array('GRAU', $variants_labels) ? $variants[array_search('GRAU', $variants_labels)] : '';
        $item['variant_lado'] = in_array('LADO', $variants_labels) ? $variants[array_search('LADO', $variants_labels)] : '';

        $arrProducts = array(
            "ID da Loja" => $item['store_id'],
            "Nome da Loja" => $item['store_name'],
            "ID Produto" => $item['id'],
            "Sku Produto Pai" => utf8_decode($item['sku']),
            "ID da Variação" => $item['variant_id'],
            "Sku do Parceiro" => $item['variant_sku'],
            "Nome do Item" => $item['name'], //utf8_decode($product['name']),
            "Preco de Venda" => floatval($item['price']),
            "Preco de Lista" => $item['list_price'],
            "Quantidade em estoque" => $item['qty'],
            "Fabricante" => $brand_name, //utf8_decode($brand_name),
            "SKU no fabricante" => utf8_decode($item['codigo_do_fabricante']),
            "Categoria" => $category_name, //utf8_decode($category_name),
            "EAN" => $item['EAN'],
            "Peso Liquido em kgs" => $item['peso_liquido'],
            "Peso Bruto em kgs" => $item['peso_bruto'],
            "Largura em cm" => $item['largura'],
            "Altura em cm" => $item['altura'],
            "Profundidade em cm" => $item['profundidade'],
            "NCM" => $item['NCM'],
            "Origem do Produto _ Nacional ou Estrangeiro" => $item['origin'],
            "Garantia em meses" => $item['garantia'],
            "Prazo Operacional em dias" => $item['prazo_operacional_extra'],
            "Produtos por embalagem" => $item['products_package'],
            "Descricao do Item _ Informacoes do Produto" => $item['description'],
            "Status(1=Ativo|2=Inativo|3=Lixeira)" => $item['status'],
            "Imagens" => $this->getImagens($item),
            "Tamanho" => $item['variant_tamanho'],
            "Cor" => $item['variant_cor'],
            "Voltagem" => $item['variant_voltagem'],
            "Sabor" => $item['variant_sabor'],
            "Grau" => $item['variant_grau'],
            "Lado" => $item['variant_lado'],
            "Estoque da Variação" => $item['variant_qty'],
            "Preço da Variação" => $item['variant_price'],
            "Imagens da Variação" => empty(trim($item['variant_image'])) ? '' : $this->getImagens($item, $item['variant_image']),
            "Status(1=Ativo|2=Inativo|3=Lixeira) da Variação" => $item['status'],
        );


        // Busca Sku do parceiro.
        if (empty($arrProducts['Sku do Parceiro'])) {
            $arrProducts['Sku do Parceiro'] = $arrProducts['Sku Produto Pai'];
            $arrProducts['Sku Produto Pai'] = '';
        }

        // Caso tenha integrações, adiciona elas.
        if (isset($item['Integrations'])) {
            $arrProducts["Integrations"] = $item['Integrations'];
        }

        return  $arrProducts;
    }

    /**
     * Realiza a formatação de produto do catalogo.
     * @param    array      $item   Produto do catalogo a ser formatado.
     *
     * @return   array      Retorna o item pós formatação.     
     */
    public function formatProductCatalog(array $item)
    {
        // Busca as informações necessárias para montar o array.
        $brand = (int) filter_var($item['brand_id'], FILTER_SANITIZE_NUMBER_INT);
        $brand_name = $brand == 0 ? "" : $this->model_brands->getBrandData($brand)['name'];
        $category = (int) filter_var($item['category_id'], FILTER_SANITIZE_NUMBER_INT);
        $category_name = $category == 0 ? "" : $this->model_category->getCategoryData($category)['name'];
        $arrProducts = array(
            "ID da Loja" => $this->session->userdata('userstore') ?: '',
            "Catalogo" => $item['catalog_id'],
            "EAN Produto de Catalogo" => $item['EAN'],
            "Fabricante" => $brand_name, //utf8_decode($brand_name),
            "Sku do Parceiro" => '',
            "Quantidade em estoque" => '',
            "Limite Desconto(%)" => '',
            "Prazo Operacional em dias" => '',
            "Status(0=Inativo|1=Ativo)" => 1,
            "Nome do Item" => $item['name'], //utf8_decode($product['name']),
            "Preco de Venda" => floatval($item['price']),
            "SKU no fabricante" => utf8_decode($item['brand_code']),
            "Categoria" => $category_name, //utf8_decode($category_name),
            "Peso Liquido em kgs" => $item['net_weight'],
            "Peso Bruto em kgs" => $item['gross_weight'],
            "Largura em cm" => $item['width'],
            "Altura em cm" => $item['height'],
            "Profundidade em cm" => $item['length'],
            "NCM" => $item['NCM'],
            "Origem do Produto _ Nacional ou Estrangeiro" => $item['origin'],
            "Garantia em meses" => $item['warranty'],
            "Produtos por embalagem" => $item['products_package'],
            "Descricao do Item _ Informacoes do Produto" => $item['description'], //utf8_decode($product['description']),
            "Imagens" => $this->getImagensProductCatalog($item)
        );

        // Caso tenha integrações, adiciona elas.
        if (isset($item['Integrations'])) {
            $arrProducts["Integrations"] = $item['Integrations'];
        }
        return $arrProducts;
    }

    /**
     * Realiza a formatação de produto do catalogo de determinada loja.
     * Apenas preenche a planilha de exemplo.
     * @param    array      $item   Produto do catalogo a ser formatado.
     *
     * @return   array      Retorna o item pós formatação.     
     */
    public function formatProductCatalogStore(array $item)
    {
        // Busca as informações necessárias para montar o array.
        $brand = (int) filter_var($item['brand_id'], FILTER_SANITIZE_NUMBER_INT);
        $brand_name = $brand == 0 ? "" : $this->model_brands->getBrandData($brand)['name'];
        $arrProducts = array(
            "ID da Loja" => $item['store_id'],
            "Catalogo" => $item['catalog_id'],
            "EAN Produto de Catalogo" => $item['EAN'],
            "Fabricante" => $brand_name, //utf8_decode($brand_name),
            "Sku do Parceiro" => $item['sku'],
            "Quantidade em estoque" => $item['qty'],
            "Limite Desconto(%)" => $item['maximum_discount_catalog'] ?: 0,
            "Status(0=Inativo|1=Ativo)" => $item['status'],
        );

        // Caso tenha integrações, adiciona elas.
        if (isset($item['Integrations'])) {
            $arrProducts["Integrations"] = $item['Integrations'];
        }
        return $arrProducts;
    }

    /**
     * Monta string com as imagens do produto ou variante.
     * @param    array      $product        Produto cujas imagens serão buscadas.
     * @param    string     $variantFolder  Pasta das variantes do produto.
     * @return   string     Retorna todas imagens do produto separadas por ','
     */
    public function getImagens($product, $variantFolder = '')
    {
        $images = array();

        $fotos = array();
        if (!$product['is_on_bucket']) {
            if ($product['product_catalog_id']) {
                if (is_dir(FCPATH . 'assets/images/catalog_product_image/' . str_replace('catalog_', '', $product['image'] . $variantFolder))) {
                    $fotos = scandir(FCPATH . 'assets/images/catalog_product_image/' . str_replace('catalog_', '', $product['image'] . $variantFolder));
                }
            } else {
                if (is_dir(FCPATH . 'assets/images/product_image/' . $product['image'] . $variantFolder))
                    if (is_dir(FCPATH . 'assets/images/product_image/' . $product['image'] . $variantFolder)) {
                        $fotos = scandir(FCPATH . 'assets/images/product_image/' . $product['image'] . $variantFolder);
                    }
            }

            foreach ($fotos as $foto) {
                if (($foto != ".") && ($foto != "..") && ($foto != "") && is_file('assets/images/product_image/' . $product['image'] . $variantFolder . '/' . $foto)) {
                    array_push($images, base_url('assets/images/product_image/' . $product['image'] . '/' . $variantFolder . $foto));
                }
            }
        } else {
            if ($product['product_catalog_id']) {
                if ($this->bucket->isDirectory('assets/images/catalog_product_image/' . str_replace('catalog_', '', $product['image'] . $variantFolder))) {
                    $fotos = $this->bucket->getFinalObject('assets/images/catalog_product_image/' . str_replace('catalog_', '', $product['image'] . $variantFolder));
                }
            } else {
                if ($this->bucket->isDirectory('assets/images/product_image/' . $product['image'] . $variantFolder)) {
                    $fotos = $this->bucket->getFinalObject('assets/images/product_image/' . $product['image'] . $variantFolder);
                }
            }

            if (isset($fotos['contents'])) {
                foreach ($fotos['contents'] as $foto) {
                    if ($foto['url'] != "") {
                        array_push($images, $foto['url']);
                    }
                }
            }
        }

        $images = implode(",", $images);
        return $images;
    }

    /**
     * Monta string com as imagens do produto de catalogo ou variante.
     * @param    array      $product        Produto cujas imagens serão buscadas.
     * @param    string     $variantFolder  Pasta das variantes do produto.
     * @return   string     Retorna todas imagens do produto separadas por ','
     */
    public function getImagensProductCatalog($product, $variantFolder = '')
    {
        $images = array();
        $fotos = array();
        if (!$product['is_on_bucket']) {
            if (is_dir(FCPATH . 'assets/images/catalog_product_image/' . $product['image'])) {
                $fotos = scandir(FCPATH . 'assets/images/catalog_product_image/' . $product['image']);
            }

            foreach ($fotos as $foto) {
                if (($foto != ".") && ($foto != "..") && ($foto != "")) {
                    $images[] = base_url('assets/images/catalog_product_image/' . $product['image'] . '/' . $foto);
                }
            }
        } else {
            if ($this->bucket->isDirectory('assets/images/catalog_product_image/' . $product['image'])) {
                $fotos = $this->bucket->getFinalObject('assets/images/catalog_product_image/' . $product['image']);
                foreach ($fotos['contents'] as $foto) {
                    if ($foto['url'] != "") {
                        $images[] = $foto['url'];
                    }
                }
            }
        }

        $images = implode(",", $images);
    }

    /**
     * Busca as associações de determinado produto no catálogo.
     * @param    mixed   $product_id Id do produto que deve ser buscado.
     * @param    mixed   $base_catalog_id Id do catálogo base que o produto está usando, neste caso, já pegamos as informações no produto base.
     */
    private function getProductAssociations($product_id, $base_catalog_id)
    {
        // Verifica se qualquer dos valores passados está vazio.
        if (empty($product_id) || empty($base_catalog_id)) {
            return [];
        }

        // Busca cada produto associado sem ser do catálogo base.
        $items = $this->db->query(
            "SELECT
	            p.qty,
	            p.store_id,
	            pca.maximum_discount_catalog,
	            p.sku,
	            pc.EAN,
	            pc.brand_id,
	            pca.catalog_id,
	            pca.status
            FROM
            	products_catalog_associated pca
            JOIN products p ON
            	pca.product_id = p.id
            JOIN products_catalog pc ON
            	p.product_catalog_id = pc.id
            WHERE
                p.id = ?
            	AND pca.catalog_id != ?
            ORDER BY
            	p.id",
            [$product_id, $base_catalog_id]
        )->result_array();

        return $items;
    }

    /**
     * Adiciona o nome da coluna no csv.
     * @param    resource       $file   Stream do arquivo.
     * @param    array          $object Objeto contendo as chaves.
     */
    public function persistColumnNames($file, $object)
    {
        $row = [];
        foreach ($object as $key => $value) {
            $row[] = $key;
        }
        fputcsv($file, $row, ';');
    }

    /**
     * Adiciona os dados na linha no CSV.
     * @param    resource       $file   Stream do arquivo.
     * @param    array          $object Objeto contendo as chaves.
     */
    public function persistRowData($file, $object)
    {
        // Percorre cada chave, limpa os valores e insere.
        foreach ($object as $key => $value) {
            if (preg_match('/\\r|\\n|;|"/', $value)) {
                $object[$key] = '"' . str_replace('"', '""', $value) . '"';
            }
        }
        fputcsv($file, $object, ';');
    }
}
