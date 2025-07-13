<?php

namespace Integration_v2\Applications\Resources;

/**
 * Class IntegrationConfiguration
 * @package Integration_v2\Applications\Resources
 * @property \Model_settings $settings
 * @property \Model_integration_erps $integrationErp
 */
class IntegrationConfiguration
{
    const INTEGRATION = null;

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    protected static $settings;

    protected static $integrationErp;

    protected $integrationConfig;
    protected $settingConfig;

    protected $integration;

    protected static function getSettings(): \Model_settings
    {
        if (self::$settings === null) {
            get_instance()->load->model('model_settings');
            self::$settings = new \Model_settings();
        }
        return self::$settings;
    }

    protected static function getIntegrationErp(): \Model_integration_erps
    {
        if (self::$integrationErp === null) {
            get_instance()->load->model('model_integration_erps');
            self::$integrationErp = new \Model_integration_erps();
        }
        return self::$integrationErp;
    }

    protected function getSettingConfig(string $field)
    {
        if (!empty($this->settingConfig[$field] ?? '')) {
            return $this->settingConfig[$field];
        }
        $this->settingConfig[$field] = self::getSettings()->getValueIfAtiveByName($field);
        return !empty($this->settingConfig[$field] ?? '') ? $this->settingConfig[$field] : null;
    }

    protected function getIntegrationConfig(string $field)
    {
        if (!empty($this->integrationConfig[$field] ?? '')) {
            return $this->integrationConfig[$field];
        }
        $integration = self::getIntegrationErp()->find([
            'name' => $this->integration,
            'active' => self::STATUS_ACTIVE
        ]);
        $this->integrationConfig = json_decode($integration['configuration'] ?? '{}', true);
        return !empty($this->integrationConfig[$field] ?? '') ? $this->integrationConfig[$field] : null;
    }
}