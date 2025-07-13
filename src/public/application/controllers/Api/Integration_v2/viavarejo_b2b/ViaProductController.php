<?php

require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/ToolsProduct.php";
require_once APPPATH . "controllers/Api/Integration_v2/viavarejo_b2b/ViaHttpController.php";

use Integration\Integration_v2\viavarejo_b2b\ToolsProduct;

/**
 * Class BaseProduct
 * @package Api\Integration_v2\viavarejo_b2b
 * @property ToolsProduct $toolsProduct
 */
abstract class ViaProductController extends ViaHttpController
{

    public function __construct($config = 'rest')
    {
        parent::__construct($config);

    }

    protected function buildToolsClass(): ToolsProduct
    {
        $this->toolsProduct = new ToolsProduct();
        try {
            $this->toolsProduct->startRun($this->storeId);
            $this->toolsProduct->setFlagId($this->flagId)
                ->setFlagName($this->flagName)
                ->setCampaignId($this->campaignId);
        } catch (InvalidArgumentException $e) {
            $this->toolsProduct->log_integration(
                "Erro para executar a integração",
                "<h4>Não foi possível iniciar as rotinas de integração</h4> <p>{$e->getMessage()}</p>",
                "E"
            );
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
        return $this->toolsProduct;
    }
}