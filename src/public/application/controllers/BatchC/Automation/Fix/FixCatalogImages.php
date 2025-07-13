<?php

/**
 * Corrige a imagem principal de produtos de catálogo.
 * Alguns produtos de catálogo apresentam como imagem principal imagens na pasta de produtos simples.
 * Percorre cada produto de catálogo que está assim, verifica se possui imagem no local certo e altera a URL.
 */
class FixCatalogImages extends BatchBackground_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * php index.php BatchC/Automation/Fix/FixCatalogImages run
     */
    public function run($id = null, $params = null)
    {
        // Seta ID do job.
        $this->setIdJob($id);

        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . $this->router->fetch_class();
        echo "ModulePath=" . $modulePath . "\n";

        // Inicia o job.
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return false;
        }

        // Aplica as correções.
        $this->fixImages();

        $this->gravaFimJob();
    }

    private function fixImages()
    {
        // Numero de produtos que deram errado.
        $error_count = 0;
        $success_count = 0;

        // Busca todos produtos que não apresentam a pasta catalog_product_image como url principal.
        $products_cat = $this->db->query(
            "SELECT * FROM products_catalog pc
                WHERE pc.principal_image NOT LIKE '%catalog_product_image%'
            "
        )->result_array();

        foreach ($products_cat as $key => $value) {
            // Verifica se há o nome da imagem.
            $prd_id = $value['id'];
            $image = basename($value["principal_image"]);
            if (empty($image)) {
                echo "Não há imagem válida para o produto de catálogo de ID $prd_id.\n";
                continue;
            }

            // Verifica se o arquivo existe na catalog_product_image.
            $file_path = FCPATH . "assets/images/catalog_product_image/" . $value['image'] . "/" . $image;
            if (!file_exists($file_path)) {
                // A imagem não existe no catalog_product_image, verifica se não foi salva no local errado.
                $alternate_file_path = FCPATH . "assets/images/product_image/" . $value['image'] . "/" . $image;
                if (!file_exists($alternate_file_path)) {
                    echo "Imagem não encontrada no local.\n";
                    $error_count++;
                    continue;
                }

                // Caso tenha encontrado na product image, renomeia para catalog_product_image.
                rename($alternate_file_path, $file_path);
            }
            // Monta a URL que será exposta.
            $url = base_url("assets/images/catalog_product_image/" . $value['image'] . "/" . $image);

            // Update no banco.
            $this->db->update(
                "products_catalog",
                ["principal_image" => $url],
                ["id" => $prd_id]
            );
            $success_count++;
        }
        echo "Excluindo o Job...\n";
		$this->db->delete('calendar_events', array('module_path' => 'Automation/Fix/FixCatalogImages'));
        echo "\n Corrigiu com sucesso $success_count imagens, apenas $error_count imagens não puderam ser corrigidas.\n";
    }
}
