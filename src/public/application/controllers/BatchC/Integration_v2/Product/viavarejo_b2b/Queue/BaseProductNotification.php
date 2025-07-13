<?php

require_once APPPATH . "controllers/BatchC/Integration_v2/Product/viavarejo_b2b/Queue/QueueNotifications.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/ToolsProduct.php";

use \Integration\Integration_v2\viavarejo_b2b\ToolsProduct;

/**
 * Class BaseProductNotification
 * @property ToolsProduct $toolsProduct
 */
abstract class BaseProductNotification extends QueueNotifications
{
    protected function buildServiceProvider($params): ToolsProduct
    {
        $this->toolsProduct = new ToolsProduct();
        try {
            $this->toolsProduct->startRun($params['storeId']);
            $this->toolsProduct->setFlagId($params['flagId'])
                ->setFlagName($params['flagName'])
                ->setCampaignId($params['campaignId'] ?? null);
        } catch (InvalidArgumentException $e) {
            $this->toolsProduct->log_integration(
                "Erro para executar a integração",
                "<h4>Não foi possível iniciar as rotinas de integração</h4> <p>{$e->getMessage()}</p>",
                "E"
            );
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
        $this->toolsProduct->setJob($params['topic']);
        return $this->toolsProduct;
    }
}