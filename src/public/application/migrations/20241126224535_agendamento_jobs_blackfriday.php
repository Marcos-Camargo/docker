<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		$sellercenter_setting = $this->db->get_where('settings', array('name' => 'sellercenter'))->row_array();
        $sellercenter = $sellercenter_setting['value'];

		if($sellercenter != "epoca" && $sellercenter != "vertem" && $sellercenter != "privalia")
		{
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'SellerCenter/Vtex/BrandsUpload' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'SellerCenter/Vtex/CategoryV2' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'SellerCenter/Vtex/BrandsDownload' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'SellerCenter/Vtex/ProductsStatusV2' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'NotifyImportationAttributes' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'CSVFileProcessing/TableFreight' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'Publication/AddProductsToQueueByPublishFilter' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'SellerCenter/Vtex/ProductsGetURL' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'Publication/SendProductsWithTransformationError' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'CSVFileProcessing/ChangeProductCategory' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'MigrateSeller/MigrateSeller' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'Automation/ImportFilesViaB2B' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'Integration_v2/Product/tiny/CreateProduct' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'Integration_v2/Product/tiny/UpdateProduct' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'Integration_v2/Product/anymarket/UpdateProduct' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'Integration_v2/Product/vtex/CreateProduct' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'Integration_v2/Product/vtex/UpdateProduct' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'ntegration_v2/Product/hub2b/CreateProduct' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'Integration_v2/Product/hub2b/UpdateProduct' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'Integration_v2/Product/pluggto/CreateProduct' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'Integration_v2/Product/pluggto/UpdateProduct' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'Integration_v2/Product/tray/CreateProduct' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'Integration_v2/Product/tray/UpdateProduct' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'Integration_v2/Product/hub2b/CreateProduct' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'Integration_v2/Product/hub2b/UpdateProduct' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'Integration_v2/Product/lojaintegrada/CreateProduct' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'Integration_v2/Product/lojaintegrada/UpdateProduct' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'Integration_v2/Product/bling/CreateProduct' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'Integration_v2/Product/bling/UpdateProduct' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'Integration_v2/Product/ideris/CreateProduct' AND module_method = 'run';");
			$this->db->query("update calendar_events ce set ce.`start` = cast(concat('2024-12-03',SUBSTRING(ce.`start`, 11,9)) as datetime) where module_path = 'Integration_v2/Product/ideris/UpdateProduct' AND module_method = 'run';");
		}
		
	}

	public function down()	{
	}
};