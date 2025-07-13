
<?php

require APPPATH . "controllers/BatchC/SellerCenter/Mosaico/Main.php";

/**
 * Sistema de categorização da Mosaico será realizado pelo lado deles.
 * Apenas buscamos a árvore de categorias para receber a chamada do matcher.
 */
class Category extends Main
{
    /**
     * @var 	 array	 	Dados da integração do marketplace.
     */
    private $mainIntegration;

    /**
     * @var 	 array	 	Dados de autenticação do marketplace.
     */
    private $mainAuthData;

    /**
     * @var 	 array	 	Categorias da mosaico.
     */
    private $mosaicoCategories;

    /**
     * @var 	 array	 	Categorias da mosaico.
     */
    private $fullCategories = [];

    /**
     * @var     array       Cache para evitar reprocessamento de categorias
     */
    private $categoryCache = [];

    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id'         => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'     => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        $this->load->model('model_categorias_marketplaces');
        $this->load->model('model_category');
        $this->load->model('model_integrations');
        $this->load->model('model_settings');
    }

    // php index.php BatchC/SellerCenter/Mosaico/Category run null int_to
    function run($id = null, $params = null)
    {
        // Inicializa o job.
        $this->setIdJob($id);
        $log_name =  __CLASS__ . '/' . __FUNCTION__;
        $modulePath = str_replace("BatchC/", '', $this->router->directory) . __CLASS__;

        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return;
        }

        try {
            $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params), "I");

            if (is_null($params)  || ($params == 'null')) {
                throw new Exception("É OBRIGATÓRIO passar o int_to no params");
            }

            // Busca a integração do sellercenter.
            $integration = $this->model_integrations->getIntegrationbyStoreIdAndInto(0, $params);
            if (!$integration) {
                throw new Exception("int_to $params não tem integração definida");
            }

            $this->int_to = $integration['int_to'];
            $this->mainIntegration = $integration;
            $this->mainAuthData = json_decode($integration['auth_data']);

            echo 'Buscando categorias do marketplace: ' . $integration['int_to'] . "\n";
            $this->getMosaicoCategory();
            if (empty($this->mosaicoCategories)) {
                throw new Exception("Sem categorias para processar");
            }

            $this->processCategories();
            $this->handleCategories();
            $this->inactivateCategories();

            $this->log_data('batch', $log_name, 'finish', "I");
            return true;
        } catch (Exception $e) {
            $this->log_data('batch', $log_name, "Error: {$e->getMessage()}", 'E');
            echo "\nErro: {$e->getMessage()}\n";
            return false;
        } finally {
            $this->gravaFimJob();
        }
    }

    /**
     * Busca as informações de categoria da Mosaico.
     */
    private function getMosaicoCategory()
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;

        $main_integration = $this->mainIntegration;
        echo "\nBuscando todas as categorias na {$main_integration['int_to']}\n";

        // Realiza a query para buscar as opções de banco na Mosaico.
        $endPoint = "categories";
        $this->processNew($this->mainAuthData, $endPoint);
        if ($this->responseCode != 200) {
            $err = "Erro na chamada ao endpoint $endPoint, com status $this->responseCode - Response: " . print_r($this->result, true);
            echo $err . "\n";
            $this->log_data('batch', $log_name, $err, "E");
            // Não vamos conseguir pegar os novos, tenta criar com os já salvos no local.
            return;
        }

        $this->mosaicoCategories = json_decode($this->result, true)["categories"];
    }

    /**
     * Processa cada categoria presente na Mosaico.
     */
    private function processCategories()
    {
        $categoryMap = [];
        $results = [];

        // Cria um mapa baseado no id das categorias.
        foreach ($this->mosaicoCategories as $cat) {
            $categoryMap[$cat["id"]] = $cat;
        }

        // Percorre cada categoria.
        foreach ($this->mosaicoCategories as $cat) {
            $paths = $this->getFullCategory($categoryMap, $cat);
            foreach ($paths as $path) {
                $results[] = $path;
            }
        }

        // Remove duplicatas baseadas no ID.
        // Caso uma categoria tenha mais de um parent, irá ser duplicada.
        $uniqueResults = [];
        $seenIds = [];
        foreach ($results as $result) {
            if (!in_array($result['id'], $seenIds)) {
                $uniqueResults[] = $result;
                $seenIds[] = $result['id'];
            }
        }

        echo "\nTotal de categorias processadas: " . count($uniqueResults) . "\n";
    }

    /**
     * Função chamada de forma recursiva para gerar a lista de categorias da Mosaico.
     * 
     * @param array $map Mapa de categorias indexadas por ID
     * @param array $cat Categoria atual sendo processada
     * 
     * @return array Array com os caminhos completos da categoria
     */
    private function getFullCategory($map, $cat)
    {
        // Verifica se já processamos esta categoria
        if (isset($this->categoryCache[$cat["id"]])) {
            return $this->categoryCache[$cat["id"]];
        }

        $newCats = [];

        // Verifica se é uma categoria raiz
        if (
            empty($cat["parent_ids"]) ||
            (
                $cat["type"] == "BRANCH" &&
                $cat["parent_ids"] == [1]
            )
        ) {

            $baseCategory = [
                "id" => $cat["id"],
                "name" => $cat["name"],
                "active" => $cat["active"]
            ];
            $newCats[] = $baseCategory;
            $this->fullCategories[] = $baseCategory;

            // Cache o resultado
            $this->categoryCache[$cat["id"]] = $newCats;
            return $newCats;
        }

        // Garante que parent_ids seja um array
        foreach ($cat["parent_ids"] as $parentId) {
            if (!isset($map[$parentId])) {
                $unfoundParent = [
                    "id" => $cat["id"],
                    "name" => "Unknown/" . $cat["name"],
                    "active" => $cat["active"]
                ];
                $newCats[] = $unfoundParent;
                $this->fullCategories[] = $unfoundParent;
                continue;
            }

            $parent = $map[$parentId];
            $parentPaths = $this->getFullCategory($map, $parent);

            foreach ($parentPaths as $path) {
                $category = [
                    "id" => $cat["id"],
                    "name" => $path["name"] . "/" . $cat["name"],
                    "active" => $cat["active"]
                ];
                $newCats[] = $category;
                $this->fullCategories[] = $category;
            }
        }

        // Cache do resultado
        $this->categoryCache[$cat["id"]] = $newCats;
        return $newCats;
    }

    /**
     * Realiza o tratamento das categorias recuperadas do mkt.
     *
     * @return void
     */
    private function handleCategories()
    {
        echo "Criando as categorias localmente...\n";

        foreach ($this->fullCategories as $category) {
            echo "\n";
            echo str_repeat("-", 100);
            echo "\nSalvando categoria {$category['name']} com Id {$category['id']}\n";

            // Busca a cateogria do marketplace já existente.
            $categoryMkt = $this->model_categorias_marketplaces->getAllCategoriesByMarketplaceAndCategoryId($this->int_to, $category['id']);
            if (!$categoryMkt) {
                $this->createNewCategoryAssociation($category);
            } else {
                $this->syncExistingCategory($categoryMkt, $category);
            }

            // Adiciona na tabela de todas categorias.
            $category_todos_mkt = [
                'id_integration' =>  $this->mainIntegration['id'],
                'id' => $category['id'],
                'nome' => $category['name'],
                'int_to' => $this->int_to
            ];

            // Verifica se já existe a entrada.
            $catTodos = $this->model_categorias_marketplaces->getAllCategoryByMarketplace($this->int_to, $category["id"]);
            if (count($catTodos) == 0) {
                $this->model_categorias_marketplaces->createTodosMarketplace($category_todos_mkt);
                continue;
            }

            // Caso o nome estaja diferente, realiza um replace.
            $categorieMkt = $catTodos[0];
            if ($category['name'] != $categorieMkt['nome']) {
                $this->model_categorias_marketplaces->replaceTodosMarketplace($category_todos_mkt);
            }
        }
    }

    /**
     * Cria ou atualiza uma categoria local e a associa com uma categoria de marketplace.
     *
     * Caso a categoria ainda não exista localmente, ela será criada.
     * Se já existir, mas estiver inativa, será ativada.
     * 
     * @param array{
     *     id: int,            
     *     name: string,        
     *     active: int|bool    
     * } $category Dados da categoria do marketplace. 
     *
     * @return void
     */
    private function createNewCategoryAssociation($category)
    {
        echo "\nCategoria não encontrada, inexistente ou sem associação.\n";

        // Verifica se já existe categoria local com este nome.
        $existingCategory = $this->model_category->getDataCategoryByName($category['name']);

        if (!$existingCategory) {
            // Cria a categoria.
            $newCategory = [
                'name' => $category['name'],
                'active' => $category['active'],
                'qty_products' => 0
            ];
            $category_id = $this->model_category->create($newCategory);
            echo "Criando a categoria {$newCategory['name']} com ID $category_id\n";
        } else {
            // Categoria com mesmo nome já existe, para o caso da Mosaico, não deverá ocorrer normalmente.
            $category_id = $existingCategory['id'];

            // Ativa a categoria se estiver inativa.
            if ($existingCategory['active'] != 1) {
                echo "Ativando a categoria $category_id\n";
                $this->model_category->update(['active' => 1], $category_id);
            }

            $verifyIfExist = $this->model_categorias_marketplaces->getDataByCategoryId($category_id);
            if ($verifyIfExist) {
                echo "Categoria $category_id já associada ao categoria_marketplace {$verifyIfExist[0]['category_marketplace_id']} ...Removendo\n";
                $this->model_categorias_marketplaces->removeByIntToCategoryId($this->int_to, $category_id);
            }
        }

        // Cria a associação entre a categoria do marketplace e a categoria recem criada.
        echo "Categoria $category_id associada a categoria do marketplace com id {$category['id']}\n";
        $category_mkt = [
            'int_to' => $this->int_to,
            'category_id' => $category_id,
            'category_marketplace_id' => $category['id']
        ];
        $this->model_categorias_marketplaces->create($category_mkt);
    }

    /**
     * Atualiza a categoria salva localmente caso alguma mudança tenha ocorrido.
     * 
     * @param    array          $localCategoryMkt Categoria salva localmente.
     * @param    array          $category Categoria no marketplace.
     */
    private function syncExistingCategory($localCategoryMkt, $category)
    {
        $categoryChanges = [];

        // Pega a primeira categoria local salva.
        $firstCat = $localCategoryMkt[0];
        $localCategory = $this->model_category->getCategoryData($firstCat['category_id']);
        $category_id = $localCategory['id'];

        if ($category['name'] != $localCategory['name']) {
            $all_categories =  $this->model_categorias_marketplaces->verifyExistCategoryAssociateDiferentByIntTo($category_id, $this->int_to);
            if (count($all_categories)) {
                echo "Nome mudou de {$localCategory['name']} para {$category['name']}, mas não é a única associação, logo não mudará.\n";
            } else {
                $categoryChanges['name'] = $category['name'];
            }
        }

        if ($localCategory != 1 && $category['active']) {
            echo "Ativando a categoria $category_id\n";
            $categoryChanges['active'] = 1;
        }

        if (!empty($categoryChanges)) {
            $this->model_category->update($categoryChanges, $category_id);
        }
    }

    /**
     * Caso habilitado nas settings, inativa as categorias inativas na Mosaico.
     */
    private function inactivateCategories()
    {
        $inactivate_category = $this->model_settings->getValueIfAtiveByName('inactivate_sellercenter_category_if_associated_marketplace_category_is_inactive');
        if (!$inactivate_category) {
            echo "Inativação de categorias inativas na integração está desativada...\n";
            return;
        }

        foreach ($this->fullCategories as $category) {
            if (!$category['active']) {
                $categories_to_inactivate = $this->model_categorias_marketplaces->getAllCategoriesByMarketplaceAndCategoryId($this->int_to, $category['id']);
                foreach ($categories_to_inactivate as $cat_inactivate) {
                    echo "\nInativando categoria {$cat_inactivate['category_id']}\n";
                    $this->model_category->update(array('active' => 2), $cat_inactivate['category_id']);
                }
            }
        }
    }
}
