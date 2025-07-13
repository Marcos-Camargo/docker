<?php

require_once APPPATH . "controllers/Api/Integration_v2/anymarket/MainController.php";
require_once APPPATH . "libraries/Attributes/Integration/IntegrationCustomAttributeApplicationService.php";
require_once APPPATH . "libraries/Attributes/Custom/CustomApplicationAttributeService.php";

use \libraries\Attributes\Integration\IntegrationCustomAttributeApplicationService;
use \libraries\Attributes\Custom\CustomApplicationAttributeService;

/**
 * Class Variations
 * @property Model_api_integrations $model_api_integrations
 * @property IntegrationCustomAttributeApplicationService $integrationCustomAttrService
 * @property CustomApplicationAttributeService $customAppAttrService
 */
class Variations extends MainController
{

    private $integrationCustomAttrService;
    private $customAppAttrService;

    public function __construct()
    {
        parent::__construct();
        $this->integrationCustomAttrService = new IntegrationCustomAttributeApplicationService();
        $this->customAppAttrService = new CustomApplicationAttributeService();
    }

    public function types_get()
    {
        $integration = $this->accountIntegration;
        $customAttr = $this->customAppAttrService->getCustomAttributes([
            'company_id' => $integration['company_id'],
            'store_id' => $integration['store_id'],
            'module' => 'products_variation',
            'status' => 1
        ]);

        $mappedAttributes = array_map(function ($attr) {
            $mappedAttr = [
                'codeInMarketplace' => $attr->getValueByColumn('code'),
                'name' => $attr->getValueByColumn('name'),
                'values' => []
            ];
            if (isset($attr->{'values'})) {
                $mappedAttr['values'] = array_map(function ($value) {
                    return [
                        'codeInMarketplace' => $value->getValueByColumn('code'),
                        'name' => $value->getValueByColumn('value'),
                    ];
                }, $attr->{'values'});
            }
            return $mappedAttr;
        }, $customAttr);
        $this->response($mappedAttributes, self::HTTP_OK);
    }


    public function values_post($path = '')
    {
        if (strcasecmp('bind', $path) === 0) {
            $bindValue = $this->request->body ?? [];
            if (empty($bindValue)) {
                $this->response(['error' => 'Bad Request'], 400);
                return;
            }
            $integrationCustomAttr = [
                'company_id' => $this->accountIntegration['company_id'],
                'store_id' => $this->accountIntegration['store_id'],
                'integration_id' => $this->accountIntegration['id'],
                'code' => $bindValue['idVariationTypeMarketplace'],
                'external_id' => $bindValue['idVariationTypeMarketplace'],
                'module' => 'products_variation',
                'is_variation_attribute' => true,
                'values' => []
            ];
            $bindValue['idVariationValueMarketplace'] = $bindValue['idVariationValueMarketplace'] ?? 'default';
            array_push($integrationCustomAttr['values'], [
                'external_id' => $bindValue['idVariationValueAnymarket'],
                'code' => $bindValue['idVariationValueMarketplace'] ?? null,
            ]);
            try {
                $integrationAttrResult = $this->integrationCustomAttrService->saveIntegrationCustomAttribute($integrationCustomAttr);
                if (!$integrationAttrResult->exists()) {
                    throw new Exception('Ocorre um erro ao vincular a variação.');
                }
            } catch (Throwable $e) {
                $this->response([
                    'error' => $e->getMessage()
                ], 400);
                return;
            }
            $this->response(true, 200);
            return;
        }
        $this->response([
            'error' => "Resource not found: /{$path}",
            'code' => 404
        ], 404);
    }

    public function values_delete($path = '', $paramId = 0)
    {
        if (strcasecmp('bind', $path) === 0 && !empty($paramId)) {
            try {

                $deleted = $this->integrationCustomAttrService->removeIntegrationAttributeValuesByParams([
                    'company_id' => $this->accountIntegration['company_id'],
                    'store_id' => $this->accountIntegration['store_id'],
                    'integration_id' => $this->accountIntegration['id'],
                    'external_id' => $paramId,
                ]);
                if (!$deleted) {
                    throw new Exception('Ocorre um erro ao deletar o vinculo da variação.');
                }
            } catch (Throwable $e) {
                $this->response([
                    'error' => $e->getMessage()
                ], 400);
                return;
            }
            $this->response(true, 200);
            return;
        }
        $this->response([
            'error' => "Resource not found: /{$path}/{$paramId}",
            'code' => 404
        ], 404);
    }

    public function values_get($path = '', $paramId = 0)
    {
        if (strcasecmp('bind', $path) === 0 && !empty($paramId)) {
            try {
                $intAttrValue = $this->integrationCustomAttrService->getIntegrationAttributeValueByExternalId([
                    'company_id' => $this->accountIntegration['company_id'],
                    'store_id' => $this->accountIntegration['store_id'],
                    'integration_id' => $this->accountIntegration['id'],
                    'external_id' => $paramId,
                ]);
                if($intAttrValue->exists()) {
                    $customAttr = $this->customAppAttrService->getCustomAttributeById([
                        'id' => $intAttrValue->getValueByColumn('custom_application_attribute_id'),
                        'company_id' => $this->accountIntegration['company_id'],
                        'store_id' => $this->accountIntegration['store_id']
                    ]);
                    if ($customAttr->exists()) {
                        $customAttrValues = array_filter($customAttr->values ?? [], function (\models\Core\Entity\Entity $value) use ($intAttrValue) {
                            return $value->getValueByColumn('id') == $intAttrValue->getValueByColumn('custom_application_attribute_value_id');
                        });
                        $customAttrValue = !empty($customAttrValues) ? current($customAttrValues) : new models\Core\Entity\Entity();
                        $idVariationValueMarketplace = $customAttrValue->getValueByColumn('code');
                        $idVariationValueMarketplace = $idVariationValueMarketplace == 'default' ? null : $idVariationValueMarketplace;
                        $mappedAttributes = [
                            'idVariationValueAnymarket' => $intAttrValue->getValueByColumn('integration_external_attribute_value_id'),
                            'idVariationTypeMarketplace' => $customAttr->getValueByColumn('code'),
                            'idVariationValueMarketplace' => $idVariationValueMarketplace,
                        ];
                        $this->response($mappedAttributes, 200);
                        return;
                    }
                }
            } catch (Throwable $e) {
                $this->response(null, 200);
                return;
            }
        }
        $this->response(null, 200);
    }
}