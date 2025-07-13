<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->db->delete('calendar_events', array('module_path' => 'Automation/Fix/RemoveDeletedVariations'));
        $this->db->delete('calendar_events', array('module_path' => 'Script/FixVariationProduct'));

        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'Omnilogic/JobBuscarProdutosSemCategoria', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 30), array('module_path' => 'Automation/ImportCSVAddOn', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 30), array('module_path' => 'Automation/ImportCSVGroupSimpleSku', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 30), array('module_path' => 'Automation/ImportCSVSyncSkusellerSkumkt', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 15), array('module_path' => 'CreateOciQueues', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 30), array('module_path' => 'SellerCenter/Wake/Seller', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'SellerCenter/Wake/Category', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'SellerCenter/Wake/BrandsDownload', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 30), array('module_path' => 'Logistic/TableFreightMigration', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 30), array('module_path' => 'Marketplace/CommissioningProcess', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 30), array('module_path' => 'AgideskCriarContatos', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'Marketplace/External/Fastshop', 'module_method' => 'runConciliacao'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'Marketplace/External/Fastshop', 'module_method' => 'runDownloadNfse'));
        $this->db->update('calendar_events', array('alert_after' => 120), array('module_path' => 'SellerCenter/Vtex/Catalog', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 120), array('module_path' => 'SellerCenter/Vtex/GetCatalogPrices', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'Marketplace/External/General', 'module_method' => 'runResendOrders'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'Marketplace/External/General', 'module_method' => 'runResendWithError'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'GSomaSnapshotProducts', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 30), array('module_path' => 'Marketplace/External/Magalu', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'Marketplace/OrderMassiveRefundProcess', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 30), array('module_path' => 'Microservice/Shipping/Seller', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 30), array('module_path' => 'MigrateSeller/MigrateSeller', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 30), array('module_path' => 'MultiChannelFulfillment/ChangeSeller', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'ProductsModified', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'SellerCenter/OCC/Category', 'module_method' => 'sync'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'SellerCenter/OCC/Collection', 'module_method' => 'sync'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'SellerCenter/OCC/Seller', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'SellerCenter/OCC/OccOrdersSync', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 120), array('module_path' => 'SaveCountSimulationDaily', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 30), array('module_path' => 'SellerCenter/Marketplace/Order', 'module_method' => 'run'));

        // Ver com André
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'Getnet/GetnetBatch', 'module_method' => 'relatorioconsilidadovtex'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'Getnet/GetnetBatch', 'module_method' => 'gerasaldossubcontasgetnet'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'Magalupay/MagalupayBatch', 'module_method' => 'runSyncStoresWithoutSubaccount'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'Magalupay/MagalupayBatch', 'module_method' => 'checkstatussubaccount'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'Magalupay/MagalupayBatch', 'module_method' => 'runSyncStoresUpdated'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'Collection/CollectionBatch', 'module_method' => 'runSyncCollections'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'ConciliationInstallmentsBatch', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'ConciliationInstallmentsBatch', 'module_method' => 'runciclofiscal'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'ExternalGateway/ExternalGatewayBatch', 'module_method' => 'resetNegativePayments'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'Vtex/CampaignsV2', 'module_method' => 'updateVtexCampaigns'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'ExternalGateway/ExternalGatewayBatch', 'module_method' => 'resetNegativePaymentsFiscal'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'Getnet/GetnetBatch', 'module_method' => 'relatorioconsilidadovtexv2'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'ProductsCatalogVerifyChanges', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'PagarMe/PagarmeBatch', 'module_method' => 'runSyncStoresWithSubaccounts'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'PagarMe/PagarmeBatch', 'module_method' => 'runPayments'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'PagarMe/PagarmeBatch', 'module_method' => 'runSyncStoresWithoutSubaccount'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'PagarMe/PagarmeBatch', 'module_method' => 'runAntecipations'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'PagarMe/PagarmeBatch', 'module_method' => 'runSyncStoresWithSubaccountsPendencies'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'PagarMe/PagarmeBatch', 'module_method' => 'gatewayUpdateBalance'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'PagarMe/PagarmeBatch', 'module_method' => 'runSyncStoresWithSubaccount'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'Publication/SetValueToAttributeMarketplaceProduct', 'module_method' => 'run'));
        $this->db->update('calendar_events', array('alert_after' => 60), array('module_path' => 'Getnet/GetnetBatch', 'module_method' => 'relatorioconsilidadooracle'));
    }

	public function down()	{}
};