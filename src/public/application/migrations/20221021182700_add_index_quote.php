
<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        if (!$this->dbforge->index_exists('ix_integration_logistic_store_id_active', 'integration_logistic')) {
            $this->db->query('ALTER TABLE `integration_logistic` ADD INDEX `ix_integration_logistic_store_id_active` (`store_id`, `active`);');
        }
        if (!$this->dbforge->index_exists('ix_api_integrations_integration', 'api_integrations')) {
            $this->db->query('ALTER TABLE `api_integrations` ADD INDEX `ix_api_integrations_integration` (`integration`);');
        }
        if (!$this->dbforge->index_exists('ix_integration_logistic_store_id_active_integration', 'integration_logistic')) {
            $this->db->query('ALTER TABLE `integration_logistic` ADD INDEX `ix_integration_logistic_store_id_active_integration` (`store_id`, `active`, `integration`);');
        }
        if (!$this->dbforge->index_exists('ix_shipping_company_active', 'shipping_company')) {
            $this->db->query('ALTER TABLE `shipping_company` ADD INDEX `ix_shipping_company_active` (`active`);');
        }
        if (!$this->dbforge->index_exists('ix_providers_to_seller_provider_id_store_id', 'providers_to_seller')) {
            $this->db->query('ALTER TABLE `providers_to_seller` ADD INDEX `ix_providers_to_seller_provider_id_store_id` (`provider_id`, `store_id`);');
        }
        if (!$this->dbforge->index_exists('ix_providers_to_seller_store_id', 'providers_to_seller')) {
            $this->db->query('ALTER TABLE `providers_to_seller` ADD INDEX `ix_providers_to_seller_store_id` (`store_id`);');
        }
        if (!$this->dbforge->index_exists('ix_states_uf', 'states')) {
            $this->db->query('ALTER TABLE `states` ADD INDEX `ix_states_uf` (`Uf`);');
        }
        if (!$this->dbforge->index_exists('ix_logistic_promotion_stores_active_status_id_stores', 'logistic_promotion_stores')) {
            $this->db->query('ALTER TABLE `logistic_promotion_stores` ADD INDEX `ix_logistic_promotion_stores_active_status_id_stores` (`active_status`, `id_stores`);');
        }
	}

	public function down()
    {
        $this->db->query('ALTER TABLE `integration_logistic` DROP INDEX `ix_integration_logistic_store_id_active`;');
        $this->db->query('ALTER TABLE `api_integrations` DROP INDEX `ix_api_integrations_integration`;');
        $this->db->query('ALTER TABLE `integration_logistic` DROP INDEX `ix_integration_logistic_store_id_active_integration`;');
        $this->db->query('ALTER TABLE `shipping_company` DROP INDEX `ix_shipping_company_active`;');
        $this->db->query('ALTER TABLE `providers_to_seller` DROP INDEX `ix_providers_to_seller_provider_id_store_id`;');
        $this->db->query('ALTER TABLE `providers_to_seller` DROP INDEX `ix_providers_to_seller_store_id`;');
        $this->db->query('ALTER TABLE `states` DROP INDEX `ix_states_uf`;');
        $this->db->query('ALTER TABLE `logistic_promotion_stores` DROP INDEX `ix_logistic_promotion_stores_active_status_id_stores`;');
	}
};
