<?php
/*

Importação de planilha de de x para para skus da RD 
 
 */

defined('BASEPATH') or exit('No direct script access allowed');

use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Writer;
use League\Csv\CharsetConverter;

class ImportRDSku extends Admin_Controller
{
    const PRODUCTS_ATTR_IMPORT_ROUTE = 'importRDSku/index';

    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();
        
        $this->data['page_title'] = $this->lang->line('application_import_rd_skus');

        $this->load->library('excel');

        $this->load->model('model_products');
        $this->load->model('model_csv_import_rd_sku');
        $this->load->library('UploadProducts');
        $this->load->library('FileDir');

    }

    public function index()
    {
        if (!in_array('updateProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        if (!in_array('importRDskus', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        $this->data['page_title'] = $this->lang->line('application_import');

        $this->render_template('importrdsku/index', $this->data);
    }

    public function upload_csv_file()
    {

        if (!in_array('importRDskus', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $config['upload_path'] = 'assets/files/product_upload';
        $config['file_name'] =  uniqid();
        $config['allowed_types'] = 'csv';
        $config['max_size'] = '100000';
        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('phase_upload')) {
            $error = $this->upload->display_errors();
            $this->data['upload_msg'] = $this->lang->line('messages_invalid_file');
            $this->data['upload_msg'] = $error;
            $this->session->set_flashdata('error', $this->lang->line('messages_product_attr_imported_queue_error'));
			$this->session->set_flashdata('error', $error);
            redirect(self::PRODUCTS_ATTR_IMPORT_ROUTE, 'refresh');
        } else {
            $data = array('upload_data' => $this->upload->data());
            $type = explode('.', $_FILES['phase_upload']['name']);
            $type = $type[count($type) - 1];

            $path = $config['upload_path'] . '/' . $config['file_name'] . '.' . $type;
            $data_imported = array(
                'store_id' => $this->session->userdata['userstore'],
                'path' => $path,
                'name_original' => $_FILES['phase_upload']['name'],
                'email' => $this->session->userdata['email']
            );
            $this->model_csv_import_rd_sku->insertCsvImported($data_imported);
            $this->session->set_flashdata('success', $this->lang->line('messages_product_attr_imported_queue_sucess'));
            redirect(self::PRODUCTS_ATTR_IMPORT_ROUTE, 'refresh');
        }
    }
}
