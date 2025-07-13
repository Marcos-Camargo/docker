<?php

require_once APPPATH . "controllers/BatchC/Integration_v2/Product/viavarejo_b2b/BaseProductBatch.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Services/ProductAvailabilityService.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/FlagMapper.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Resources/Parsers/DTO/AvailabilityCollectionDTO.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Resources/Parsers/DTO/AvailabilityDTO.php";

use Integration_v2\viavarejo_b2b\Services\ProductAvailabilityService;
use Integration_v2\viavarejo_b2b\Resources\Mappers\XML\AvailabilityDeserializer;
use Integration_v2\viavarejo_b2b\Resources\Mappers\FlagMapper;
use Integration_v2\viavarejo_b2b\Resources\Parsers\DTO\AvailabilityCollectionDTO;
use Integration_v2\viavarejo_b2b\Resources\Parsers\DTO\AvailabilityDTO;
use Integration\Integration_v2;

/**
 * Class UpdateAvailability
 *
 * php index.php BatchC/Integration_v2/Product/viavarejo_b2b/UpdateAvailability run {ID} {STORE} {QUEUEID}
 * @property ProductAvailabilityService $productAvailabilityService
 */
class UpdateAvailability extends BaseProductBatch
{

    private $enabledFlags = [
        FlagMapper::FLAG_CASASBAHIA, FlagMapper::FLAG_PONTOFRIO, FlagMapper::FLAG_EXTRA
    ];

    protected $campaignId = 0;

    protected $productAvailabilityService;

    public function __construct()
    {
        parent::__construct();
        $this->toolsProduct->setJob(__CLASS__);
    }

    protected function handleDeserializedXML(object $object)
    {
        $availabilities = new AvailabilityCollectionDTO();
        foreach ($object->{AvailabilityDeserializer::NODE_CAMPAIGN_LIST} ?? [] as $campaign) {
            if ($campaign->IdCampanha != $this->campaignId) continue;
            foreach ($campaign->{AvailabilityDeserializer::NODE_PRODUCT_LIST} ?? [] as $productCampaign) {
                foreach ($object->{AvailabilityDeserializer::NODE_PRODUCT_LIST} ?? [] as $product) {
                    if ($productCampaign->codigo != $product->codigo) continue;
                    $availabilities->add(new AvailabilityDTO(
                        $productCampaign->codigo,
                        decimalNumber($product->precoDe),
                        decimalNumber($productCampaign->precoPor),
                        $productCampaign->disponibilidade,
                        $this->campaignId,
                        $this->flagId
                    ));
                }
            }
        }
        $this->productAvailabilityService->handleWithRawObject($availabilities);
    }

    protected function initializeServiceProvider()
    {
        $this->toolsProduct->setCampaignId($this->campaignId);
        $this->toolsProduct->setIgnoreIntegrationLogTypes([
            Integration_v2::LOG_TYPE_ERROR
        ]);
        $this->productAvailabilityService = new ProductAvailabilityService($this->toolsProduct);
    }

    protected function fetchStore()
    {
        foreach ($this->enabledFlags as $flag) {
            $companyStore = $this->model_api_integrations->getIntegrationByCompanyId(
                $this->companyId, FlagMapper::getIntegrationNameFromFlag($flag)
            );
            if (!$companyStore) continue;
            $authData = json_decode($companyStore['credentials'], true);
            if (!isset($authData['campaign'])) continue;
            $campaignIds = $this->getCampaignIds();
            $checkStore = array_filter($campaignIds, function ($campaignId) use ($authData) {
                return $campaignId == $authData['campaign'];
            });
            if (empty($checkStore)) continue;
            if ($flag !== $authData['flag']) continue;
            if (!empty($this->storeId) && $this->storeId != $companyStore['store_id']) continue;
            $this->storeId = $companyStore['store_id'];
            $this->flagId = FlagMapper::getFlagIdByName($flag);
            $this->campaignId = $authData['campaign'];
            break;
        }
        if (empty($this->storeId)) {
            throw new ErrorException('Não foi encontrada nenhuma loja com o IdCampanha específicado no arquivo.');
        }
    }

    protected function getCampaignIds()
    {
        $object = $this->deserializer->getDeserializedObject();
        if (isset($object->{AvailabilityDeserializer::NODE_CAMPAIGN_LIST})) {
            return array_column($object->{AvailabilityDeserializer::NODE_CAMPAIGN_LIST}, 'IdCampanha');
        }
        return [];
    }

    protected function getFlagId()
    {
        return $this->flagId ?? 0;
    }
}