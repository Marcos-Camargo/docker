<?php

use Firebase\JWT\JWT;
use libraries\Attributes\Application\Resources\CustomAttribute;
use libraries\Attributes\Integration\IntegrationCustomAttributeApplicationService;
use libraries\Attributes\Custom\CustomApplicationAttributeService;

require_once APPPATH . "controllers/Api/Integration/AnyMarket/MainController.php";
require_once APPPATH . "libraries/Attributes/Integration/IntegrationCustomAttributeApplicationService.php";
require_once APPPATH . "libraries/Attributes/Custom/CustomApplicationAttributeService.php";
require_once APPPATH . "libraries/Attributes/Application/Resources/CustomAttribute.php";

/**
 * Class Categories
 * @property Model_categories_anymaket_from_to $model_categories_anymaket_from_to
 * @property IntegrationCustomAttributeApplicationService $integrationCustomAttrService
 * @property CustomApplicationAttributeService $customAppAttrService
 */
class Categories extends MainController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_brands');
        $this->load->model('model_api_integrations');
        $this->load->model('model_categories_anymaket_from_to');
        $this->load->model('model_category');
        $this->integrationCustomAttrService = new IntegrationCustomAttributeApplicationService();
        $this->customAppAttrService = new CustomApplicationAttributeService();
    }
    public function index_get($idInMarketplace = null)
    {
        if (!$this->checkToken()) {
            $this->response('unauthorized', 401);
            return;
        }
        if ($idInMarketplace == null) {
            $response = [];
            $anymarket_token = $_SERVER['HTTP_X_ANYMARKET_TOKEN'];
            $payload = explode('.', $anymarket_token)[1];
            $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
            $categories = $this->model_category->getActiveCategroy();
            foreach ($categories as $category) {
                if (!empty($category['name'])) {
                    $response[] = ['codeInMarketplace' => $category['id'], 'name' => $category['name']];
                }
            }
            $this->response($response, REST_Controller::HTTP_OK);
        } else {
            $response = [];
            $anymarket_token = $_SERVER['HTTP_X_ANYMARKET_TOKEN'];
            $payload = explode('.', $anymarket_token)[1];
            $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
            $category = $this->model_category->getCategoryData($idInMarketplace);
            $response = [
                'codeInMarketPlace' => $category['id'],
                'name' => $category['name'],
                'isReceivingItens' => $category['active'] == 2 ? false : true,
                'variationsMandatory' => null,
                'canBeSelected' => true,
            ];
            $this->response($response, REST_Controller::HTTP_OK);
        }
    }
    public function bind_get($idAnymarketBrand)
    {
        if (!$this->checkToken()) {
            $this->response('unauthorized', 401);
            return;
        }
        $response = [];
        $anymarket_token = $_SERVER['HTTP_X_ANYMARKET_TOKEN'];
        $payload = explode('.', $anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        $integration = $this->model_api_integrations->getUserByOI($payload->oi);
        $integration = $integration[0];
        $whereData = ['api_integration_id' => $integration['id'], 'idCategoryAnymarket' => $idAnymarketBrand];
        $category = $this->model_categories_anymaket_from_to->getData($whereData);
        if ($category != null) {
            $this->response(['idCategoryAnymarket' => $category['idCategoryAnymarket'], 'idCategoryMarketplace' => $category['categories_id']], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => 'err', 'msg' => "Categoria não conhecida na {{$this->sellercenterName}}"], REST_Controller::HTTP_OK);
        }
    }
    public function bind_post()
    {
        if (!$this->checkToken()) {
            $this->response('unauthorized', 401);
            return;
        }
        $body = json_decode(file_get_contents('php://input'), true);
        $anymarket_token = $_SERVER['HTTP_X_ANYMARKET_TOKEN'];

        $payload = explode('.', $anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        $integration = $this->model_api_integrations->getUserByOI($payload->oi);
        $integration = $integration[0];
        $category = $this->model_category->getCategoryData($body['idCategoryMarketplace']);
        // dd($body, $payload, $integration, $brand,$brand==null);
        if ($category == null) {
            $this->response(['status' => 'err', 'msg' => "Categoria não conhecida na {{$this->sellercenterName}}"], REST_Controller::HTTP_OK);
            // $this->response("Marca não conhecida na {$this->sellercenterName}", REST_Controller::HTTP_OK);
            // die;
        } else {
            $data = [
                'idCategoryAnymarket' => $body['idCategoryAnymarket'], 
                'categories_id' => $category['id'], 
                'api_integration_id' => $integration['id']];

            $whereData = [
                'idCategoryAnymarket' => $body['idCategoryAnymarket'],
                'api_integration_id' => $integration['id']
            ];

            $category = $this->model_categories_anymaket_from_to->getData($whereData);
            if ($category) {
                $update = $this->model_categories_anymaket_from_to->update($category['id'], $data);
                if ($update) {
                    $this->response(true, REST_Controller::HTTP_OK);
                } else {
                    $this->response(false, REST_Controller::HTTP_OK);
                }
            } else {
                $create = $this->model_categories_anymaket_from_to->create($data);
                if ($create) {
                    $this->response(true, REST_Controller::HTTP_OK);
                } else {
                    $this->response(false, REST_Controller::HTTP_OK);
                }
            }

        }
    }
    public function bind_delete($idAnymarketCategory)
    {
        if (!$this->checkToken()) {
            $this->response('unauthorized', 401);
            return;
        }
        $response = [];
        $anymarket_token = $_SERVER['HTTP_X_ANYMARKET_TOKEN'];

        $payload = explode('.', $anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        $integration = $this->model_api_integrations->getUserByOI($payload->oi);
        $integration = $integration[0];
        $whereData = ['api_integration_id' => $integration['id'], 'idCategoryAnymarket' => $idAnymarketCategory];
        $category = $this->model_categories_anymaket_from_to->getData($whereData);
        if ($category == null) {
            $this->response(['status' => 'err', 'msg' => "Categoria não vinculada no {{$this->sellercenterName}}"], REST_Controller::HTTP_OK);
        } else {
            $response = $this->model_categories_anymaket_from_to->delete($category['id']);
            $this->response($response, REST_Controller::HTTP_OK);
        }
    }

    public function attributes_get($anyMarketCategoryId)
    {
        $response = [];
        $integration = $this->accountIntegration;
        $linkedCategory = $this->model_categories_anymaket_from_to->getCategoryByExternalId($anyMarketCategoryId, $integration['id']);
        if (!empty($linkedCategory)) {
            $customAttr = $this->customAppAttrService->getCustomAttributes([
                'company_id' => $integration['company_id'],
                'store_id' => $integration['store_id'],
                'category_id' => $linkedCategory['category_id'],
                'module' => CustomAttribute::PRODUCT_CATEGORY_ATTRIBUTE_MODULE,
                'status' => 1
            ]);
            $mappedAttributes = array_map(function ($attr) {
                $type = $attr->getValueByColumn('field_type');
                $mappedAttr = [
                    'codeInMarketPlace' => $attr->getValueByColumn('code'),
                    'description' => $attr->getValueByColumn('name'),
                    'required' => (bool)$attr->getValueByColumn('required'),
                    'recommended' => false,
                    'type' => $type == CustomAttribute::FIELD_TYPE_SELECTABLE ? 'SELECT_INPUT' : ($type == CustomAttribute::FIELD_TYPE_NUMBER ? 'NUMBER_INPUT' : 'STRING_INPUT')
                ];
                if (isset($attr->{'values'})) {
                    $mappedAttr['values'] = array_map(function ($value) {
                        return [
                            'codeInMarketPlace' => $value->getValueByColumn('code'),
                            'name' => $value->getValueByColumn('value'),
                        ];
                    }, $attr->{'values'});
                }
                return $mappedAttr;
            }, $customAttr);
            $this->response($mappedAttributes, self::HTTP_OK);
            return;
        }
        $this->response($response, REST_Controller::HTTP_OK);
    }
}
