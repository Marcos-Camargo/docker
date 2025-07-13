<?php
/**
 * php index.php BatchC/Automation/CreateApplicationAttributes run {ID} {PARAMS}
 */
require_once APPPATH . "libraries/Attributes/Application/Resources/CustomAttribute.php";
require_once APPPATH . "libraries/Attributes/Application/ApplicationAttributeService.php";
require_once APPPATH . "libraries/Attributes/Custom/CustomApplicationAttributeService.php";
require_once APPPATH . "libraries/Marketplaces/Integrations/StoreIntegrationService.php";
require_once APPPATH . "libraries/Marketplaces/Integrations/Providers/Vtex/VtexIntegrationProvider.php";
require_once APPPATH . "libraries/Attributes/Application/Integration/Mappers/Vtex/VtexIntegrationAttributeMapper.php";
require_once APPPATH . "libraries/Attributes/Application/Integration/VariationIntegrationAttributeService.php";
require_once APPPATH . "libraries/Attributes/Application/Integration/CategoryIntegrationAttributeService.php";

use \libraries\Attributes\Custom\CustomApplicationAttributeService;
use \libraries\Attributes\Application\ApplicationAttributeService;
use \libraries\Attributes\Integration\IntegrationCustomAttributeCategoryService;
use \Integration_v2\anymarket\Providers\IntegrationCategoryProvider;
use \libraries\Attributes\Application\Resources\CustomAttribute;
use \libraries\Marketplaces\Integrations\StoreIntegrationService;
use \libraries\Marketplaces\Integrations\Providers\Vtex\VtexIntegrationProvider;
use \libraries\Attributes\Application\Integration\Mappers\Vtex\VtexIntegrationAttributeMapper;
use \libraries\Attributes\Application\Integration\VariationIntegrationAttributeService;
use \libraries\Attributes\Application\Integration\CategoryIntegrationAttributeService;

/**
 * Class CreateApplicationAttributes
 * @property CI_Loader $load
 * @property Model_attributes $model_attributes
 * @property Model_api_integrations $model_api_integrations
 * @property Model_integrations $model_integrations
 * @property Model_settings $model_settings
 * @property Model_categories_anymaket_from_to $categoryIntegrationRepo
 * @property Model_categorias_marketplaces $mktplCategoryRepo
 * @property ApplicationAttributeService $applicationAttrService
 * @property CustomApplicationAttributeService $customAttrService
 */
class CreateApplicationAttributes extends BatchBackground_Controller
{

    protected $integrationProviders = [];

    public function __construct()
    {
        parent::__construct();
        ini_set('memory_limit', '4096M');
        $this->load->model('model_attributes');
        $this->load->model('model_api_integrations');
        $this->load->model('model_integrations');
        $this->load->model('model_integrations');
        $this->load->model('model_categorias_marketplaces', 'mktplCategoryRepo');

        $this->applicationAttrService = new ApplicationAttributeService($this->model_attributes, $this->model_settings);
        $this->customAttrService = new CustomApplicationAttributeService();

        $this->integrationProviders = [
            [
                'provider' => new VtexIntegrationProvider(new VtexIntegrationAttributeMapper()),
            ]
        ];

    }

    public function run($id = null, $params = null)
    {
        $params = ((string)$params) == 'null' ? null : $params;
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . $this->router->fetch_class();
        echo "ModulePath=" . $modulePath . "\n";
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return false;
        }

        try {
            $this->process($params ?? null);
        } catch (Throwable $e) {

        }

        $this->gravaFimJob();
    }

    public function process($storeId = null)
    {
        echo "Iniciando criação/atualização de atributos...\n";
        $variationAttributes = $this->applicationAttrService->getAttributesDefinitionsByModule(CustomAttribute::PRODUCT_VARIATION_MODULE);
        $variationAttributes = $this->applicationAttrService->createUpdateApplicationAttributes($variationAttributes);
        echo "Finalizado criação/atualização de atributos.\n";
        echo "Iniciando criação/atualização de atributos customizados da conta...\n";
        $criteria = $storeId > 0 ? ['store_id' => $storeId] : [];
        $apiIntegrations = $this->model_api_integrations->getStoresAndIntegrationIfExists($criteria);
        foreach ($apiIntegrations as $apiIntegration) {
            $attributes = [];
            echo "Loja {$apiIntegration['store_name']} ({$apiIntegration['store_id']})...\n";
            foreach ($this->integrationProviders as $integrationProvider) {
                $integrationsCategoriesAttr = [];
                $storeIntegrationService = new StoreIntegrationService($integrationProvider['provider'], $this->model_integrations);
                $storeIntegrations = $storeIntegrationService->fetchStoreIntegrations((int)$apiIntegration['store_id']);
                if (empty($storeIntegrations)) {
                    $attributes = array_merge($attributes, $variationAttributes);
                }
                foreach ($storeIntegrations as $storeIntegration) {
                    echo "Obtendo e mapeando atributos de variação de integração com marketplace {$storeIntegration['int_to']}\n";
                    $variationIntegrationAttributeServices = new VariationIntegrationAttributeService($integrationProvider['provider']->getIntegrationAttributeMapper());
                    $storeIntegrationAttr = $variationIntegrationAttributeServices->fetchIntegrationAttributes(
                        $storeIntegration,
                        $variationIntegrationAttributeServices->mapSearchCriteria($variationAttributes),
                        true
                    );
                    $integrationAttr = $variationIntegrationAttributeServices->mapperIntegrationAttributes($storeIntegrationAttr);
                    echo "Mesclando com atributos de variação já existentes de integração com marketplace {$storeIntegration['int_to']}...\n";
                    $variationIntegrationAttributes = $variationIntegrationAttributeServices->mergeAttributeValues($variationAttributes, $integrationAttr);
                    echo "Iniciando consulta de atributos específicos de categorias do marketplace vinculadas a integração com marketplace {$storeIntegration['int_to']}...\n";

                    $integrationCategoriesAttr = [];
                    if (in_array($apiIntegration['integration'], ['anymarket'])) {
                        require_once APPPATH . "libraries/Integration_v2/anymarket/Providers/IntegrationCategoryProvider.php";
                        require_once APPPATH . "libraries/Attributes/Integration/IntegrationCustomAttributeCategoryService.php";
                        $this->load->model('model_categories_anymaket_from_to', 'categoryIntegrationRepo');
                        $attrCategoryServices = new IntegrationCustomAttributeCategoryService(
                            new IntegrationCategoryProvider($this->categoryIntegrationRepo)
                        );
                        $integrationCategories = $attrCategoryServices->fetchIntegrationLinkedCategories((int)$apiIntegration['integration_id']);
                        if (empty($integrationCategories)) continue;
                        $mktCategories = $this->mktplCategoryRepo->getCategoriesMktplace($storeIntegration['int_to'], array_column($integrationCategories, 'id'));
                        $countCategories = count($mktCategories);
                        echo "Numero de categorias vinculadas {$countCategories} integração {$storeIntegration['int_to']}...\n";
                        $categoriesAttrs = [];
                        foreach ($mktCategories as $mktCategory) {
                            $categoryIntegrationAttributeServices = new CategoryIntegrationAttributeService($integrationProvider['provider']->getIntegrationAttributeMapper());
                            $categoryIntegrationAttr = $categoryIntegrationAttributeServices->fetchIntegrationAttributes(
                                $storeIntegration,
                                $categoryIntegrationAttributeServices->mapSearchCriteria($mktCategory),
                                true
                            );
                            $mktCategory['module'] = CustomAttribute::PRODUCT_CATEGORY_ATTRIBUTE_MODULE;
                            $categoryAttrs = $categoryIntegrationAttributeServices->mapperIntegrationAttributes($categoryIntegrationAttr, $mktCategory);
                            $categoriesAttrs = array_merge($categoriesAttrs, $categoryAttrs);
                        }
                        $integrationCategoriesAttr = array_merge($integrationCategoriesAttr, $categoriesAttrs);
                        $countCatAttrs = count($categoriesAttrs);
                        echo "{$countCatAttrs} atributos em todas as categorias da integração {$storeIntegration['int_to']}\n";
                    }

                    $integrationsCategoriesAttr = array_merge($integrationsCategoriesAttr, $integrationCategoriesAttr);
                    $attributes = array_merge($attributes, $variationIntegrationAttributes);
                }
                $countCatAttrs = count($integrationsCategoriesAttr);
                echo "{$countCatAttrs} atributos em todas as categorias de integrações\n";
                $attributes = array_merge($attributes, $integrationsCategoriesAttr);

                $countAttrs = count($attributes);
                echo "Salvando {$countAttrs} atributos para a loja/conta...\n";
                $customAttributes = $this->customAttrService->createUpdateAccountAttributes($attributes, $apiIntegration);
            }
        }
        echo "Finalizado criação/atualização de atributos customizados da conta...\n";
    }

}