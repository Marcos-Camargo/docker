<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {


        $this->db->query('update calendar_events set alert_after=360 where module_path = "SellerCenter/Vtex/VtexPaymentInteration";');
        $this->db->query('update calendar_events set alert_after=120 where module_path = "FreteCadastrar";');
        $this->db->query('update calendar_events set alert_after=1200 where module_path = "AutomaticPublishing";');
        $this->db->query('update calendar_events set alert_after=360 where module_path = "Vtex/CampaignsV2";');
        $this->db->query('update calendar_events set alert_after=360 where module_path = "Automation/CreateApplicationAttributes";');
        $this->db->query('update calendar_events set alert_after=60 where module_path = "Automation/UpdateSetDeadlineNovo";');
        $this->db->query('update calendar_events set alert_after=60 where module_path = "Intelipost/CancelOrder";');
        $this->db->query('update calendar_events set alert_after=60 where module_path = "CancelPlpCorreios";');
        $this->db->query('update calendar_events set alert_after=360 where module_path = "Publication/AddProductsToQueueByPublishFilter";');
        $this->db->query('update calendar_events set alert_after=60 where module_path = "CSVFileProcessing/ExportLabelTracking";');
        $this->db->query('update calendar_events set alert_after=60 where module_path = "CSVFileProcessing/ChangeProductCategory";');
        $this->db->query('update calendar_events set alert_after=800 where module_path = "SellerCenter/Vtex/CategoryV2";');
        $this->db->query('update calendar_events set alert_after=120 where module_path = "SellerCenter/Vtex/BrandsUpload";');
        $this->db->query('update calendar_events set alert_after=60 where module_path = "SellerCenter/Vtex/BrandsDownload";');
        $this->db->query('update calendar_events set alert_after=360 where module_path = "SellerCenter/Vtex/ProductsStatusV2";');
        $this->db->query('update calendar_events set alert_after=120 where module_path = "SellerCenter/Vtex/VtexOrders";');
        $this->db->query('update calendar_events set alert_after=60 where module_path = "SellerCenter/Vtex/VtexOrdersStatus";');
        $this->db->query('update calendar_events set alert_after=30 where module_path = "SellerCenter/Vtex/SellerV2";');
        $this->db->query('update calendar_events set alert_after=240 where module_path = "SellerCenter/Vtex/ProductsGetURL";');
        $this->db->query('update calendar_events set alert_after=360 where module_path = "SellerCenter/Vtex/VtexPaymentInteration";');
    }

	public function down()	{

	}

};