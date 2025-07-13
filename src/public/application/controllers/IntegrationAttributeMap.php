<?php

use libraries\Attributes\Custom\CustomAttributeService;
use libraries\Attributes\Application\Resources\CustomAttribute;
use libraries\Attributes\Custom\CustomAttributeMapService;
use \libraries\Helpers\StringHandler;

require 'system/libraries/Vendor/autoload.php';

require_once APPPATH . "libraries/Helpers/StringHandler.php";
require_once APPPATH . "libraries/Attributes/Custom/CustomAttributeService.php";
require_once APPPATH . "libraries/Attributes/Custom/CustomAttributeMapService.php";
require_once APPPATH . "libraries/Attributes/Application/Resources/CustomAttribute.php";

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Class IntegrationAttributeMap
 * @property Model_stores $model_stores
 * @property Model_atributos_categorias_marketplaces $model_atributos_categorias_marketplaces
 * @property Model_attributes $model_attributes
 * @property Model_products $model_products
 * @property Model_integrations $model_integrations
 * @property Model_seller_attributes_marketplace $model_seller_attributes_marketplace
 * @property Model_seller_attribute_values_marketplace $model_seller_attribute_values_marketplace
 * @property Model_categorias_marketplaces $model_categorias_marketplaces
 * @property CustomAttributeService $customAttrService
 * @property CustomAttributeMapService $customAttrMapService
 */
class IntegrationAttributeMap extends Admin_Controller
{

    private $company_id;
    private $store_id;

    public function __construct()
    {
        parent::__construct();
        $this->not_logged_in();

        $this->load->model('model_stores');
        $this->load->model('model_atributos_categorias_marketplaces');
        $this->load->model('model_attributes');
        $this->load->model('model_products');
        $this->load->model('model_integrations');
        $this->load->model('model_seller_attributes_marketplace');
        $this->load->model('model_seller_attribute_values_marketplace');
        $this->load->model('model_categorias_marketplaces');

        $this->data['page_title'] = $this->lang->line('application_integration_data_normalization');
        $this->data['pageinfo'] = "application_integration";
        $this->data['page_now'] = "data_normalization";
        $this->company_id = $this->data['usercomp'] = $this->session->userdata('usercomp');
        $this->store_id = $this->data['userstore'] = $this->session->userdata('userstore');

        $this->customAttrService = new CustomAttributeService();
        $this->customAttrMapService = new CustomAttributeMapService();
    }

    public function index()
    {
        if (!in_array('viewIntegrationAttributeMap', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['stores'] = $this->model_stores->getActiveStore();

        $this->render_template('integrations/configurations/attributes/index', $this->data);
    }

    public function create()
    {
        if (!in_array('createIntegrationAttributeMap', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->render_template('integrations/configurations/attributes/create', $this->data);
    }

    public function edit($attrId)
    {
        if (!in_array('updateIntegrationAttributeMap', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $params = $this->retrieveAddCriteria();
        $customAttr = $this->customAttrService->getCustomAttributeByCriteria(array_merge([
            'id' => $attrId
        ], $params));
        if(!$customAttr->exists()) redirect('dashboard', 'refresh');

        $this->data['attr'] = (array)$customAttr;
        $this->render_template('integrations/configurations/attributes/update', $this->data);
    }

    public function edit_attribute(int $attr_id, int $store_id)
    {
        if (!in_array('updateIntegrationAttributeMap', $this->permission) || empty($attr_id) || empty($store_id)) {
            redirect('integrations/configurations/attributes', 'refresh');
        }

        $attribute = $this->model_attributes->getAttributeProduct($attr_id);
        $categories = $this->model_products->getCategoriesByStoreProduct(array($store_id));
        $integrations = $this->model_integrations->getIntegrationsbyStoreId($store_id);

        if (!$attribute) {
            redirect('integrations/configurations/attributes', 'refresh');
        }

        $this->data['attribute'] = $attribute;
        $this->data['categories'] = $categories;
        $this->data['integrations'] = $integrations;
        $this->data['attr_id'] = $attr_id;
        $this->data['store_id'] = $store_id;
        $this->render_template('integrations/configurations/attributes/update_attribute', $this->data);
    }

    protected function retrieveAddCriteria(array $params = [])
    {
        if ((isset($this->data['usercomp']) && $this->data['usercomp'] != 1)) {
            $params['company_id'] = $this->data['usercomp'];
        }
        if ((isset($this->data['userstore']) && $this->data['userstore'] != 0)) {
            $params['store_id'] = $this->data['userstore'];
        }
        return $params;
    }

    public function fetchVariationData()
    {
        if (!in_array('viewIntegrationAttributeMap', $this->permission)) {
            echo json_encode(['error' => $this->lang->line('application_dont_permission')]);
            return;
        }

        $params = $this->postClean(NULL, TRUE);

        $limit = $params['length'] > 0 ? $params['length'] : 20;
        $offset = $params['start'] >= 0 ? $params['start'] : 0;
        if (isset($params['search']) && is_array($params['search'])) {
            $params['search'] = current(array_values($params['search']));
        }

        $params = $this->retrieveAddCriteria($params);

        $nroRegisters = $this->customAttrService->countCustomVariationsByCriteria($params);
        $attrResults = $this->customAttrService->fetchCustomVariationsByCriteria($params, $offset, $limit);
        $attributes = array_map(function ($attr) {
            $attr['status'] = $attr['status'] ? "<span class=\"label label-success\">{$this->lang->line('application_active')}</span>" : "<span class=\"label label-warning\">{$this->lang->line('application_inactive')}</span>";
            $attr['type'] = $attr['module'] == CustomAttribute::PRODUCT_VARIATION_MODULE ? $this->lang->line('application_variation') : $this->lang->line('application_category_attribute');
            $attr['mapped_values'] = $this->customAttrMapService->countCustomAttributesMapByCriteria([
                'custom_attribute_id' => $attr['id'],
                'store_id' => $attr['store_id'],
                'company_id' => $attr['company_id'],
            ]);
            $editButton = '';
            $addButton = '';
            $deleteButton = '';
            if (in_array('deleteIntegrationAttributeMap', $this->permission)) {
                $enabled = ($attr['system'] ?? 1) ? 'disabled="disabled"' : '';
                $deleteButton = "<button 
                class='btn btn-danger btn-del-attribute'
                data-toggle='tooltip'
                data-placement='top'
                data-attribute-id='{$attr['id']}'
                data-store-id='{$attr['store_id']}'
                data-company-id='{$attr['company_id']}'
                data-type='delete'
                title='{$this->lang->line('application_delete_attribute')}'
                {$enabled}
                >
                <i class='fa fa-trash-o'></i>
                </button>";
            }

            if (in_array('createIntegrationAttributeMap', $this->permission)) {
                $addButton = "<button class='btn btn-default btn-add-value-attribute'
                        data-toggle='tooltip'
                        data-placement='top'
                        data-attribute-id='{$attr['id']}'
                        data-store-id='{$attr['store_id']}'
                        data-company-id='{$attr['company_id']}'
                        data-attribute-name='{$attr['name']}'
                        title='{$this->lang->line('application_add_attribute_map')}'
                    >
                        <i class='fa fa-plus'></i>&nbsp;&nbsp;{$this->lang->line('application_add_attribute_map')}
                    </button>";
            }

            if (in_array('updateIntegrationAttributeMap', $this->permission)) {
                $editButton = "<button class='btn btn-default btn-edit-attribute'
                        data-toggle='tooltip'
                        data-placement='top'
                        data-attribute-id='{$attr['id']}'
                        title='{$this->lang->line('application_edit')}'
                    >
                        <i class='far fa-edit'></i>
                    </button>";
            }

            $deleteButton = '';
            $attr['actions'] = "<div class=''>
                <div class='input-group attribute-actions'>
                    {$addButton}
                    {$editButton}
                    {$deleteButton}
                </div>
            </div>";

            return $attr;
        }, $attrResults);

        $totalPages = $nroRegisters > 0 ? (int)($nroRegisters / (int)$params['length']) : 0;
        $page = (int)($params['start'] > 0 ? ((int)$params['length'] / (int)($params['start'])) : 0);
        $return = [
            'draw' => (int)$params['draw'],
            'data' => $attributes,
            'pagination' => [
                'page' => ceil($page),
                'per_page' => (int)$params['length'],
                'total_pages' => $totalPages,
                'filtered_items' => $nroRegisters,
                'total_items' => $nroRegisters,
            ],
            'recordsTotal' => $nroRegisters,
            'recordsFiltered' => $nroRegisters,
        ];
        echo json_encode($return);
    }

    public function fetchAttributeData()
    {
        try {
            $stores = $this->postClean('stores');
            $draw   = $this->postClean('draw');
            $result = array();

            $filters        = array();
            $filter_default = array();

            $filter_default[]['where']['p.status !='] = Model_products::DELETED_PRODUCT;

            if (!empty($stores)) {
                $filters[]['where_in']['p.store_id'] = $stores;
            }

            $fields_order = array('ap.name','','s.name','');

            $query = array();
            $query['select'][] = 'ap.id, ap.name, s.name as store_name, p.store_id, p.company_id';
            $query['from'][] = 'products p';
            $query['join'][] = ['attributes_products_value apv', 'p.id = apv.prd_id'];
            $query['join'][] = ['attributes_products ap', 'ap.id = apv.id_attr_prd'];
            $query['join'][] = ['stores s', 's.id = p.store_id'];

            $data = fetchDataTable(
                $query,
                array(
                    'company'   => 'p.company_id',
                    'store'     => 'p.store_id'
                ),
                array('ap.name', 'ASC'),
                null,
                ['viewIntegrationAttributeMap'],
                $filters,
                $fields_order,
                $filter_default
            );
        } catch (Exception $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output($exception->getMessage())
                ->set_status_header($exception->getCode());
        }

        foreach ($data['data'] as $key => $value) {
            /*$attr['mapped_values'] = $this->customAttrMapService->countCustomAttributesMapByCriteria([
                'custom_attribute_id' => $attr['id'],
                'store_id' => $attr['store_id'],
                'company_id' => $attr['company_id'],
            ]);*/
            $editButton = '';
            $addButton = '';
            $deleteButton = '';
            /*if (in_array('deleteIntegrationAttributeMap', $this->permission)) {
                $deleteButton = "<button 
                class='btn btn-danger btn-del-attribute'
                data-toggle='tooltip'
                data-placement='top'
                data-attribute-id='{$value['id']}'
                data-store-id='{$value['store_id']}'
                data-company-id='{$value['company_id']}'
                data-type='delete'
                title='{$this->lang->line('application_delete_attribute')}'
                >
                <i class='fa fa-trash-o'></i>
                </button>";
            }

            if (in_array('createIntegrationAttributeMap', $this->permission)) {
                $addButton = "<button class='btn btn-default btn-add-value-attribute'
                        data-toggle='tooltip'
                        data-placement='top'
                        data-attribute-id='{$value['id']}'
                        data-store-id='{$value['store_id']}'
                        data-company-id='{$value['company_id']}'
                        data-attribute-name='{$value['name']}'
                        title='{$this->lang->line('application_add_attribute_map')}'
                    >
                        <i class='fa fa-plus'></i>&nbsp;&nbsp;{$this->lang->line('application_add_attribute_map')}
                    </button>";
            }*/

            if (in_array('updateIntegrationAttributeMap', $this->permission)) {
                $editButton = "<button class='btn btn-default btn-edit-attribute'
                        data-toggle='tooltip'
                        data-placement='top'
                        data-attribute-id='{$value['id']}'
                        data-store-id='{$value['store_id']}'
                        title='{$this->lang->line('application_edit')}'
                    >
                        <i class='far fa-edit'></i>
                    </button>";
            }

            $deleteButton = '';

            $result[$key] = array(
                $value['name'],
                0,
                $value['store_name'],
                "<div class=''>
                    <div class='input-group attribute-actions'>
                        {$addButton}
                        {$editButton}
                        {$deleteButton}
                    </div>
                </div>"
            );
        }

        $output = array(
            "draw"              => $draw,
            "recordsTotal"      => $data['recordsTotal'],
            "recordsFiltered"   => $data['recordsFiltered'],
            "data"              => $result,
        );

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    public function fetchAttributeMappedValues($attrId)
    {
        if (!in_array('viewIntegrationAttributeMap', $this->permission)) {
            echo json_encode(['error' => $this->lang->line('application_dont_permission')]);
            return;
        }

        $params = $this->postClean(NULL, TRUE);

        $limit = $params['length'] > 0 ? $params['length'] : 20;
        $offset = $params['start'] >= 0 ? $params['start'] : 0;
        if (isset($params['search']) && is_array($params['search'])) {
            $params['search'] = current(array_values($params['search']));
        }

        if ((isset($this->data['usercomp']) && $this->data['usercomp'] != 1)) {
            $params['company_id'] = $this->data['usercomp'];
        }
        if ((isset($this->data['userstore']) && $this->data['userstore'] != 0)) {
            $params['store_id'] = $this->data['userstore'];
        }

        $params['custom_attribute_id'] = $attrId;
        $nroRegisters = $this->customAttrMapService->countCustomAttributesMapByCriteria($params);
        $attrResults = $this->customAttrMapService->fetchCustomAttributesMapByCriteria($params, $offset, $limit);
        $attributes = array_map(function ($attr) {
            $editButton = '';
            $deleteButton = '';
            if (in_array('deleteIntegrationAttributeMap', $this->permission)) {
                $deleteButton = "<button 
                class='btn btn-danger btn-del-attribute'
                data-toggle='tooltip'
                data-placement='top'
                data-attribute-id='{$attr['custom_attribute_id']}'
                data-store-id='{$attr['store_id']}'
                data-company-id='{$attr['company_id']}'
                data-mapped-id='{$attr['id']}'
                data-type='delete'
                title='{$this->lang->line('application_delete_attribute')}'
                >
                <i class='fa fa-trash-o'></i>
                </button>";
            }

            if (in_array('updateIntegrationAttributeMap', $this->permission)) {
                $editButton = "<button class='btn btn-default btn-edit-attribute'
                        data-toggle='tooltip'
                        data-placement='top'
                        data-attribute-id='{$attr['custom_attribute_id']}'
                        data-attribute-name='{$attr['name']}'
                        data-mapped-id='{$attr['id']}'
                        data-store-id='{$attr['store_id']}'
                        data-company-id='{$attr['company_id']}'
                        data-value='{$attr['value']}'
                        title='{$this->lang->line('application_edit')}'
                    >
                        <i class='far fa-edit'></i>
                    </button>";
            }

            $attr['actions'] = "<div class=''>
                <div class='input-group attribute-actions'>
                    {$editButton}
                    {$deleteButton}
                </div>
            </div>";

            return $attr;
        }, $attrResults);

        $totalPages = $nroRegisters > 0 ? (int)($nroRegisters / (int)$params['length']) : 0;
        $page = (int)($params['start'] > 0 ? ((int)$params['length'] / (int)($params['start'])) : 0);
        $return = [
            'draw' => (int)$params['draw'],
            'data' => $attributes,
            'pagination' => [
                'page' => ceil($page),
                'per_page' => (int)$params['length'],
                'total_pages' => $totalPages,
                'filtered_items' => $nroRegisters,
                'total_items' => $nroRegisters,
            ],
            'recordsTotal' => $nroRegisters,
            'recordsFiltered' => $nroRegisters,
        ];
        echo json_encode($return);
    }

    public function addUpdateAttrMap($mappedAttrId = 0)
    {
        if (empty($mappedAttrId) && !in_array('createIntegrationAttributeMap', $this->permission)) {
            echo json_encode([
                'errors' => [$this->lang->line('application_dont_permission')]
            ]);
            return;
        }

        if (!empty($mappedAttrId) && !in_array('updateIntegrationAttributeMap', $this->permission)) {
            echo json_encode([
                'errors' => [$this->lang->line('application_dont_permission')]
            ]);
            return;
        }

        $formData = json_decode($this->postClean('data', true), true);

        if (!empty($formData['attributeId'] ?? 0)) {
            $customAttr = $this->customAttrService->getCustomAttributeById([
                'id' => $formData['attributeId'],
                'store_id' => $formData['storeId'] ?? $this->store_id,
                'company_id' => $formData['companyId'] ?? $this->company_id,
            ]);
            if (!$customAttr->exists()) {
                echo json_encode([
                    'messages' => [sprintf($this->lang->line('messages_attribute_not_exists'), $formData['attributeId'])]
                ]);
                return;
            }
            $mappedAttrsValues = [];
            $mappedAttrsValueMgs = [];
            $mappedAttrsValueErrors = [];
            foreach ($formData['values'] ?? [] as $value) {
                if (empty($value)) continue;
               $addAttrParams = [];
                if (!empty($mappedAttrId)) {
                    $mappedAttr = $this->customAttrMapService->getCustomAttributeByCriteria([
                        'id' => $mappedAttrId,
                        'store_id' => $customAttr->getValueByColumn('store_id') ?? $this->store_id,
                        'company_id' => $customAttr->getValueByColumn('company_id') ?? $this->company_id,
                        'custom_attribute_id' => $customAttr->getValueByColumn('id') ?? $formData['attributeId'],
                    ]);
                    if(!$mappedAttr->exists()) {
                        $mappedAttrsValueErrors[] = sprintf($this->lang->line('messages_exist_attribute_mapping_error'), $customAttr->getValueByColumn('name'), $value);
                        continue;
                    }
                    $addAttrParams['id'] = $mappedAttr->getValueByColumn('id') ?? $mappedAttrId;
                }
                $existsMappedValues = $this->customAttrMapService->fetchCustomAttributesMapByCriteria([
                    'store_id' => $customAttr->getValueByColumn('store_id') ?? $this->store_id,
                    'company_id' => $customAttr->getValueByColumn('company_id') ?? $this->company_id,
                    //'custom_attribute_id' => $customAttr->getValueByColumn('id') ?? $formData['attributeId'],
                    'value' => trim($value),
                ]);
                $existsMappedValue = !empty($existsMappedValues) ? current($existsMappedValues) : [];
                if (!empty($existsMappedValue) && ($existsMappedValue['id'] != ($addAttrParams['id'] ?? 0))) {
                    $mappedAttrsValueErrors[] = sprintf($this->lang->line('messages_exist_attribute_mapping'), "'{$value}'", "'{$existsMappedValue['name']}'");
                    continue;
                }
                $mappedAttrs = $this->customAttrMapService->createUpdateAttributesMapping([
                    array_merge([
                        'store_id' => $customAttr->getValueByColumn('store_id') ?? $this->store_id,
                        'company_id' => $customAttr->getValueByColumn('company_id') ?? $this->company_id,
                        'custom_attribute_id' => $customAttr->getValueByColumn('id') ?? $formData['attributeId'],
                        'value' => trim($value),
                    ], $addAttrParams)
                ]);
                if (empty($mappedAttrs)) {
                    $mappedAttrsValueErrors[] = sprintf($this->lang->line('messages_attribute_mapping_error'), $customAttr->getValueByColumn('name'), $value);
                    continue;
                }
                $mappedAttrsValues = array_merge($mappedAttrsValues, $mappedAttrs);
                $mappedAttrsValueMgs[] = sprintf($this->lang->line('messages_attribute_mapped_success'), $customAttr->getValueByColumn('name'), $value);
            }
            if (!empty($mappedAttrsValueMgs)) {
                echo json_encode([
                    'messages' => $mappedAttrsValueMgs,
                    'data' => $mappedAttrsValues
                ]);
                return;
            }
        }
        header("HTTP/1.1 420");
        echo json_encode([
            'errors' => !empty($mappedAttrsValueErrors) ? $mappedAttrsValueErrors : [$this->lang->line('messages_attribute_map_error')]
        ]);
    }

    public function deleteAttrMap($mappedAttrId)
    {
        if (!in_array('deleteIntegrationAttributeMap', $this->permission)) {
            echo json_encode([
                'errors' => [$this->lang->line('application_dont_permission')]
            ]);
            return;
        }

        $params = $this->retrieveAddCriteria();
        $mappedAttr = $this->customAttrMapService->getCustomAttributeByCriteria(array_merge([
            'id' => $mappedAttrId,
        ], $params));
        if($mappedAttr->exists()) {
            $customAttr = $this->customAttrService->getCustomAttributeById([
                'id' => $mappedAttr->getValueByColumn('custom_attribute_id'),
                'store_id' => $mappedAttr->getValueByColumn('store_id'),
                'company_id' => $mappedAttr->getValueByColumn('company_id')
            ]);
            if($this->customAttrMapService->removeCustomAttrMapById($mappedAttrId, $params)) {
                echo json_encode([
                    'messages' => [sprintf($this->lang->line('messages_attribute_map_deleted'), $mappedAttr->getValueByColumn('value'), $customAttr->getValueByColumn('name'))],
                ]);
                return;
            }
            echo json_encode([
                'errors' => [sprintf($this->lang->line('messages_attribute_map_deleted_error'), $mappedAttr->getValueByColumn('value'), $customAttr->getValueByColumn('name'))],
            ]);
            return;
        }
        echo json_encode([
            'errors' => [sprintf($this->lang->line('messages_attribute_map_deleted_error'), '', '')]
        ]);
        return;
    }

    public function getAttributesCategoryMarketplace(int $category_id = null, string $int_to = null): CI_Output
    {
        if (empty($category_id) || empty($int_to)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array()));
        }
        $attributes = $this->model_atributos_categorias_marketplaces->getAttributesCategoryMarketplace($category_id, $int_to);

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($attributes));
    }

    public function getAttributesCustomByStore(int $store_id): CI_Output
    {
        if (empty($store_id)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array()));
        }
        $attributes = $this->model_attributes->getAttributeCustomStore($store_id);

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($attributes));
    }

    public function getAttributesStoreCategoryMarketplace(int $store_id = null, int $category_id = null, string $int_to = null, bool $show_attribute_name = false): CI_Output
    {
        if (empty($store_id) ||  empty($category_id) || empty($int_to)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array()));
        }
        $attributes = $this->model_seller_attributes_marketplace->getAttributesStoreCategoryMarketplace($store_id, $category_id, $int_to, $show_attribute_name);

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($attributes));
    }

    public function getValuesAttributeCategoryMarketplaceAttribute(int $category_id = null, string $int_to = null, string $attribute = null): CI_Output
    {
        if (empty($category_id) || empty($int_to) || empty($attribute)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array()));
        }
        $attributes = $this->model_atributos_categorias_marketplaces->getAttributesCategoryMarketplaceAttribute($category_id, $int_to, $attribute);

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($attributes ?? []));
    }

    public function getValuesAttributeSellerCategoryMarketplaceAttribute(int $category_id = null, string $int_to = null, string $attribute = null): CI_Output
    {
        if (empty($category_id) || empty($int_to) || empty($attribute)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array()));
        }
        $attributes = $this->model_seller_attribute_values_marketplace->getValuesAttributeSellerCategoryMarketplaceAttribute($category_id, $int_to, $attribute);

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($attributes));
    }

    public function saveSellerAttributes(): CI_Output
    {
        $category_id = $this->postClean('category');
        $store_id = $this->postClean('store');
        $int_to = $this->postClean('marketplace');
        $attributes_keys = $this->postClean('attributes_keys');
        $attributes_values = $this->postClean('attributes_values');
        $data_store = $this->model_stores->getStoresData($store_id);

        if (!$this->model_stores->checkIfTheStoreIsMine($store_id)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => $this->lang->line('application_store_not_found')
                )));
        }

        $attributes = array();
        foreach ($attributes_keys as $attribute_key) {
            $attributes[$attribute_key] = array();
        }

        foreach ($attributes_values as $attribute_values) {
            if ($attribute_values['name'] == 'attribute[]') {
                continue;
            }

            if ($attribute_values['value'] === '') {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'success' => false,
                        'message' => "Existem atributos não preenchido, preencha todos para continuar."
                    )));
            }

            $attributes[$attribute_values['name']][] = $attribute_values['value'];
        }

        $this->db->trans_begin();

        $this->model_seller_attributes_marketplace->removeAllAttributesByStoreCategoryMarketplace($store_id, $category_id, $int_to);

        $arr_attributes_marketplace_id = array();
        foreach ($attributes as $attribute_key => $attribute_values) {

            $exp_attribute_key = explode('_', $attribute_key);
            $attribute_marketplace_id = $exp_attribute_key[0];
            $category_marketplace_id = $exp_attribute_key[1];
            $arr_attributes_marketplace_id[] = $attribute_marketplace_id;

            foreach ($attribute_values as $attribute_value) {
                $this->model_seller_attributes_marketplace->create(array(
                    'store_id'                  => $store_id,
                    'company_id'                => $data_store['company_id'],
                    'int_to'                    => $int_to,
                    'category_id'               => $category_id,
                    'category_marketplace_id'   => $category_marketplace_id,
                    'attribute_marketplace_id'  => $attribute_marketplace_id,
                    'attribute_seller_value'    => $attribute_value
                ));
            }
        }
        $this->model_seller_attribute_values_marketplace->removeAllValuesAttributeByStoreCategoryMarketplace($store_id, $category_id, $int_to, $arr_attributes_marketplace_id);

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => $this->lang->line('messages_error_occurred')
                )));
        }

        $this->db->trans_commit();

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => $this->lang->line('messages_successfully_updated')
            )));
    }

    public function saveSellerValuesAttribute(): CI_Output
    {
        $category_id            = $this->postClean('category');
        $store_id               = $this->postClean('store');
        $int_to                 = $this->postClean('marketplace');
        $attribute              = $this->postClean('attribute');
        $attributes_keys        = $this->postClean('attributes_keys');
        $attributes_values      = $this->postClean('attributes_values');
        $data_store             = $this->model_stores->getStoresData($store_id);
        $category_marketplace   = $this->model_categorias_marketplaces->getCategoryMktplace($int_to, $category_id);

        if (!$this->model_stores->checkIfTheStoreIsMine($store_id)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => $this->lang->line('application_store_not_found')
                )));
        }

        if (!$category_marketplace) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => $this->lang->line('application_category_not_found')
                )));
        }

        $attributes = array();
        foreach ($attributes_keys as $attribute_key) {
            $attributes[$attribute_key] = array();
        }

        foreach ($attributes_values as $attribute_values) {
            if ($attribute_values['name'] == 'attribute[]') {
                continue;
            }

            if ($attribute_values['value'] === '') {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'success' => false,
                        'message' => "Existem atributos não preenchido, preencha todos para continuar."
                    )));
            }

            $attributes[$attribute_values['name']][] = $attribute_values['value'];
        }

        $this->db->trans_begin();

        $this->model_seller_attribute_values_marketplace->removeAllValuesAttributeByStoreCategoryMarketplaceAttribute($store_id, $category_id, $int_to, $attribute);

        foreach ($attributes as $value_attribute => $seller_value_attribute) {

            foreach ($seller_value_attribute as $attribute_value) {
                $this->model_seller_attribute_values_marketplace->create(array(
                    'store_id'                       => $store_id,
                    'company_id'                     => $data_store['company_id'],
                    'int_to'                         => $int_to,
                    'category_id'                    => $category_id,
                    'category_marketplace_id'        => $category_marketplace['category_marketplace_id'],
                    'attribute_marketplace_id'       => $attribute,
                    'attribute_value_marketplace_id' => $value_attribute,
                    'attribute_value_seller_name'    => $attribute_value
                ));
            }
        }

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => $this->lang->line('messages_error_occurred')
                )));
        }

        $this->db->trans_commit();

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => $this->lang->line('messages_successfully_updated')
            )));
    }
}