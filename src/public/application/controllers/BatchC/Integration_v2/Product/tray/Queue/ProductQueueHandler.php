<?php

use Integration\Integration_v2;
use Integration_v2\tray\Resources\Notification;
use Integration\Integration_v2\tray\ToolsProduct;
use Integration_v2\tray\Services\ProductNotificationService;

require_once APPPATH . "controllers/BatchC/Integration_v2/Product/tray/Queue/BaseQueueHandler.php";
require_once APPPATH . 'libraries/Integration_v2/tray/ToolsProduct.php';
require_once APPPATH . 'libraries/Integration_v2/tray/Services/ProductNotificationService.php';

/**
 * Class ProductQueueHandler
 * @property ToolsProduct $toolsProvider
 * @property ProductNotificationService $productNotificationService
 */
class ProductQueueHandler extends BaseQueueHandler
{

    protected function getProvider(): Integration_v2
    {
        if ($this->toolsProvider === null || !isset($this->toolsProvider)) {
            $this->toolsProvider = new ToolsProduct();
            $this->toolsProvider->setJob(get_class($this));
            $this->toolsProvider->startRun($this->storeId);
            $this->productNotificationService = new ProductNotificationService($this->toolsProvider);
        }
        return $this->toolsProvider;
    }

    protected function queueDataHandler(array $queueNotification)
    {
        $notificationData = $queueNotification['data'] ?? (object)[];
        $actionsByTopics = $this->prioritizeAndMapTopics($queueNotification['grouped_topics'] ?? '');
        foreach ($actionsByTopics as $topic => $actions) {
            $scopeId = $notificationData->scope_id ?? $queueNotification['scope_id'];
            if (in_array($scopeId, $this->productNotificationService->getProcessedScopeIds())) {
                echo sprintf("[PROCESS][LINE:%s] - %s:%s já processado em outro evento. Ignorando...\n", __LINE__, $scopeId, $topic);
                continue;
            }

            if (strcasecmp(Notification::PRODUCT_SCOPE, $topic) === 0) {
                echo sprintf("[PROCESS][LINE:%s] - %s:%s. Inserindo/Atualizando produto...\n", __LINE__, $scopeId, $topic);
                $this->productNotificationService->handleWithProductNotification($queueNotification);
                echo sprintf("[PROCESS][LINE:%s] - %s:%s. Concluído!\n", __LINE__, $scopeId, $topic);
                continue;
            }
            if (array_key_exists($topic, array_flip([
                Notification::PRODUCT_PRICE_SCOPE,
                Notification::PRODUCT_STOCK_SCOPE
            ]))) {
                if (array_key_exists($topic, array_flip([Notification::PRODUCT_PRICE_SCOPE]))) {
                    echo sprintf("[PROCESS][LINE:%s] - %s:%s. Atualizando Preço do Produto...\n", __LINE__, $scopeId, $topic);
                    $this->productNotificationService->updatePriceProductByNotification($queueNotification);
                    echo sprintf("[PROCESS][LINE:%s] - %s:%s. Concluído!\n", __LINE__, $scopeId, $topic);
                }
                if (array_key_exists($topic, array_flip([Notification::PRODUCT_STOCK_SCOPE]))) {
                    echo sprintf("[PROCESS][LINE:%s] - %s:%s. Atualizando Estoque do Produto...\n", __LINE__, $scopeId, $topic);
                    $this->productNotificationService->updateStockProductByNotification($queueNotification);
                    echo sprintf("[PROCESS][LINE:%s] - %s:%s. Concluído!\n", __LINE__, $scopeId, $topic);
                }
                continue;
            }
            if (strcasecmp(Notification::VARIANT_SCOPE, $topic) === 0) {
                echo sprintf("[PROCESS][LINE:%s] - %s:%s. Inserindo/Atualizando variação...\n", __LINE__, $scopeId, $topic);
                $this->productNotificationService->handleWithVariationNotification($queueNotification);
                echo sprintf("[PROCESS][LINE:%s] - %s:%s. Concluído!\n", __LINE__, $scopeId, $topic);
                continue;
            }
            if (array_key_exists($topic, array_flip([
                Notification::VARIANT_PRICE_SCOPE,
                Notification::VARIANT_STOCK_SCOPE
            ]))) {
                $queueNotification['is_variation'] = true;
                if (array_key_exists($topic, array_flip([Notification::VARIANT_PRICE_SCOPE]))) {
                    echo sprintf("[PROCESS][LINE:%s] - %s:%s. Atualizando Preço da variação...\n", __LINE__, $scopeId, $topic);
                    $this->productNotificationService->updatePriceProductByNotification($queueNotification);
                    echo sprintf("[PROCESS][LINE:%s] - %s:%s. Concluído!\n", __LINE__, $topic, $scopeId);
                }
                if (array_key_exists($topic, array_flip([Notification::VARIANT_STOCK_SCOPE]))) {
                    echo sprintf("[PROCESS][LINE:%s] - %s:%s. Atualizando Estoque da variação...\n", __LINE__, $scopeId, $topic);
                    $this->productNotificationService->updateStockProductByNotification($queueNotification);
                    echo sprintf("[PROCESS][LINE:%s] - %s:%s. Concluído!\n", __LINE__, $scopeId, $topic);
                }
            }
        }
    }

    protected function prioritizeAndMapTopics(string $groupedTopics = ''): array
    {
        $topicsActions = explode(',', $groupedTopics);
        $topicsByPriority = [
            Notification::PRODUCT_SCOPE, Notification::PRODUCT_STOCK_SCOPE, Notification::PRODUCT_PRICE_SCOPE,
            Notification::VARIANT_SCOPE, Notification::VARIANT_STOCK_SCOPE, Notification::VARIANT_PRICE_SCOPE
        ];
        usort($topicsActions, function ($a, $b) use ($topicsByPriority) {
            $a = Notification::retrieveScopeByTopic($a);
            $b = Notification::retrieveScopeByTopic($b);
            $a = array_search($a, $topicsByPriority);
            $b = array_search($b, $topicsByPriority);
            if ($a === false && $b === false) return 0;
            else if ($a === false) return 1;
            else if ($b === false) return -1;
            else return $a - $b;
        });

        $actionsByTopics = [];
        foreach ($topicsActions as $topicAction) {
            $topic = Notification::retrieveScopeByTopic($topicAction);
            $action = Notification::retrieveActionByTopic($topicAction);
            if (empty($topic)) continue;
            if (empty($action)) continue;
            if (!isset($actionsByTopics[$topic])) $actionsByTopics[$topic] = [];
            array_push($actionsByTopics[$topic], $action);
        }
        return $actionsByTopics;
    }
}