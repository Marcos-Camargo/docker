<?php


namespace Integration_v2\tray\Resources;


class Notification
{

    const ORIGIN = 'tray';

    const STATUS_NEW = 0;
    const STATUS_PROCESSING = 1;

    const PRODUCT_SCOPE = 'product';
    const VARIANT_SCOPE = 'variant';
    const PRODUCT_PRICE_SCOPE = 'product_price';
    const PRODUCT_STOCK_SCOPE = 'product_stock';
    const VARIANT_PRICE_SCOPE = 'variant_price';
    const VARIANT_STOCK_SCOPE = 'variant_stock';
    const ORDER_SCOPE = 'order';

    const INSERT_ACT = 'insert';
    const UPDATE_ACT = 'update';

    const ENABLED_PRODUCT_NOTIFICATIONS = [
        self::PRODUCT_SCOPE => [self::INSERT_ACT, self::UPDATE_ACT],
        self::VARIANT_SCOPE => [self::INSERT_ACT, self::UPDATE_ACT],
        self::PRODUCT_PRICE_SCOPE => [self::UPDATE_ACT],
        self::PRODUCT_STOCK_SCOPE => [self::UPDATE_ACT],
        self::VARIANT_PRICE_SCOPE => [self::UPDATE_ACT],
        self::VARIANT_STOCK_SCOPE => [self::UPDATE_ACT]
    ];
    const ENABLED_ORDER_NOTIFICATIONS = [
        self::ORDER_SCOPE => [self::UPDATE_ACT]
    ];

    const NOTIFICATION_JOB_CONFIGURATION = [
        '*' => [
            'interval' => 5,
            'scope' => 'product',
            'topics' => self::ENABLED_PRODUCT_NOTIFICATIONS,
            'class' => 'Integration_v2/Product/tray/Queue/ProductQueueHandler'
        ],
        'order' => [
            'interval' => 5,
            'scope' => 'order',
            'topics' => self::ENABLED_ORDER_NOTIFICATIONS,
            'class' => 'Integration_v2/Product/tray/Queue/OrderQueueHandler'
        ]
    ];


    public static function isEnabledScopeAction(string $scope, string $action): bool
    {
        $enabledNotifications = array_merge(self::ENABLED_PRODUCT_NOTIFICATIONS, self::ENABLED_ORDER_NOTIFICATIONS);
        $actions = $enabledNotifications[$scope ?? ''] ?? [];
        return in_array($action, $actions);
    }

    public static function getJobConfiguration(string $scope = '*'): array
    {
        return self::NOTIFICATION_JOB_CONFIGURATION[$scope] ?? self::NOTIFICATION_JOB_CONFIGURATION['*'];
    }

    public static function retrieveQueueTopic(string $scope, string $action): string
    {
        return implode('.', [$scope, $action]);
    }

    public static function retrieveScopeByTopic(string $topic): string
    {
        return explode('.', $topic)[0] ?? '';
    }

    public static function retrieveActionByTopic(string $topic): string
    {
        return explode('.', $topic)[1] ?? '';
    }

    public static function getEnabledTopicsByScope(string $grantScope): array
    {
        $scopes = [];
        foreach (self::getJobConfiguration($grantScope)['topics'] ?? [] as $scope => $actions) {
            foreach ($actions as $action) {
                array_push($scopes, self::retrieveQueueTopic($scope, $action));
            }
        }
        return $scopes;
    }
}