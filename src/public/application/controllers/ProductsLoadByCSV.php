<?php
/*
SW Serviços de Informática 2019

Controller de Recebimentos

*/
defined('BASEPATH') or exit('No direct script access allowed');


use League\Csv\Reader;
use League\Csv\Writer;
use League\Csv\CharsetConverter;
use League\Csv\Statement;
use League\Csv\TabularDataReader;
use phpDocumentor\Reflection\Types\Array_;

/**
 * @property Model_stores $model_stores
 * @property Model_products $model_products
 * @property Model_category $model_category
 * @property Model_csv_to_verifications $model_csv_to_verifications
 * @property Model_brands $model_brands
 * @property Model_settings $model_settings
 * @property Model_catalogs $model_catalogs
 *
 * @property CSV_Validation $csv_validation
 *
 * @property CI_Upload $upload
 * @property CI_Loader $load
 * @property CI_Session $session
 * @property CI_Lang $lang
 */

class ProductsLoadByCSV extends Admin_Controller
{
    public $allowable_tags = null;
	const UNDER_ANALYSISS = 4;
	public function __construct()
	{
		parent::__construct();
		$this->data['page_title'] = $this->lang->line('application_upload_products');
		$this->not_logged_in();
		$this->load->model('model_stores');
		$this->load->model('model_products');
		$this->load->model('model_category');
        $this->load->model('model_csv_to_verifications');
		$this->load->model('model_brands');
        $this->load->model('model_settings');
        $this->load->model('model_catalogs');
        $this->load->helper('file');

        if ($allowableTags = $this->model_settings->getValueIfAtiveByName('products_allowable_tags')) {
            if (!empty($allowableTags)) {
                $this->allowable_tags = '<' . implode('><', explode(',', $allowableTags)) . '>';
            }
        }

		$usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
        $this->load->library('CSV_Validation', [
            'permission' => $this->permission
        ]);
	}

	public function index()
	{
		$this->check_if_permission_page();
		$this->render_template('products/new_load', $this->data);
	}

    public function CatalogProduct()
    {
        if (in_array('createProduct', $this->permission) || !in_array('disablePrice', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->data['catalogs'] = $this->model_catalogs->getActiveCatalogs();
        $this->render_template('products/new_catalog_product_load', $this->data);
    }

    public function SyncPublishedSku()
    {
        if (!in_array('syncPublishedSku', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->render_template('products/sync_published_sku', $this->data);
    }

    public function GroupSimpleSku()
    {
        if (!in_array('groupSimpleSku', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->render_template('products/group_simple_sku', $this->data);
    }


    public function AddOn()
    {
        if (!in_array('addOn', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->render_template('products/add_on_load', $this->data);
    }

	function check_if_permission_page()
	{
		if (!in_array('createProduct', $this->permission) && in_array('disablePrice', $this->permission)) {
            redirect('products/loadCatalog', 'refresh');
        } if (!in_array('createProduct', $this->permission) && !in_array('updateProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
	}

    public function onlyVerifyAddOn()
    {
        if (!in_array('addOn', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $dir_to_csv      = 'assets/images/csv_of_addon_sku_uploaded/';
        $dirPathTemp     = "assets/files/addon_sku_upload/";

        // Se não existir o diretório, será criado.
        if (!is_dir($dir_to_csv)) {
            mkdir($dir_to_csv);
        }

        // Se não existir o diretório temporário, será criado.
        if (!is_dir($dirPathTemp)) {
            mkdir($dirPathTemp);
        }

        $file            = $this->uploadFile($dirPathTemp);
        $count_rows_file = count($this->csv_validation->convertCsvToArray($file));
        $limit_rows      = 200000;
        $new_file        = $dir_to_csv.$this->getGUID(false).'.csv';

        // Verifica se o arquivo tem mais linhas que o permitido.
        if ($count_rows_file > $limit_rows) {
            $this->session->set_flashdata('error', sprintf($this->lang->line('messages_only_x_lines_are_allowed'), 200000));
            redirect('ProductsLoadByCSV/AddOn', 'refresh');
        }

        // Faz a cópia do arquivo para outro local e salva o registro no banco de dados.
        if (copy($file, $new_file)){
            $csv_to_verification = array(
                'upload_file'   => $new_file,
                'user_id'       => $this->session->userdata('id'),
                'username'      => $this->session->userdata('username'),
                'user_email'    => $this->session->userdata('email'),
                'usercomp'      => $this->data['usercomp'],
                'allow_delete'  => 0,
                'module'        => 'AddOnSkus'
            );

            if ($this->model_csv_to_verifications->create($csv_to_verification)) {
                $this->session->set_flashdata('success', sprintf(
                    $this->lang->line('messages_file_added_to_import_queue_see_the_link'),
                    base_url('FileProcess/product_load'),
                    "{$this->lang->line('application_products')} > {$this->lang->line('application_add_products')} > {$this->lang->line('application_file_process')}"
                ));
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred') . ' .' . $this->lang->line('messages_reload_page_try_again'));
            }
        } else {
            $this->session->set_flashdata('error', $this->lang->line('messages_save_media_error') . ' .' . $this->lang->line('messages_reload_page_try_again'));
        }

        redirect('ProductsLoadByCSV/AddOn', 'refresh');
    }

	public function onlyVerify()
	{
        $dir_to_csv      = 'assets/images/csv_of_products_uploaded/';
        $dirPathTemp     = "assets/files/product_upload/";

        // Se não existir o diretório, será criado.
        if (!is_dir($dir_to_csv)) {
            mkdir($dir_to_csv);
        }

        // Se não existir o diretório temporário, será criado.
        if (!is_dir($dirPathTemp)) {
            mkdir($dirPathTemp);
        }

        $file            = $this->uploadFile($dirPathTemp);

        if ($file === false) {
            $this->session->set_flashdata('error', $this->lang->line('messages_invalid_file'));
            redirect('ProductsLoadByCSV', 'refresh');
        }

        $count_rows_file = count($this->csv_validation->convertCsvToArray($file));
        $limit_rows      = $this->model_settings->getValueIfAtiveByName('limit_product_rows_csv');
        $new_file        = $dir_to_csv.$this->getGUID(false).'.csv';

        // Verifica se o arquivo tem mais linhas que o permitido.
        if ($limit_rows && $count_rows_file > $limit_rows) {
            $this->session->set_flashdata('error', sprintf($this->lang->line('messages_only_x_lines_are_allowed'), $limit_rows));
            redirect('ProductsLoadByCSV', 'refresh');
        }

        // VERIFICA SE OS PREÇOS DOS PRODUTOS ESTÃO CORRETOS
        $countErrors = 0;
        $fileArray = $this->csv_validation->convertCsvToArray($file);
        foreach($fileArray as $index => $item){
            if(isset($item['Preco de Venda']) && isset($item['Preco de lista'])) {
                $precoVenda = $item['Preco de Venda'];
                $precoLista = $item['Preco de lista'];
                if (empty($precoVenda) || empty($precoLista)) {
                    $this->session->set_flashdata('error', $this->lang->line('application_prices_error_csv'));
                    $countErrors++;
                }
            }
        }

        // SE TODOS OS PREÇOS DO ARQUIVO ESTIVEREM ERRADOS, EXIBO A MENSAGEM DE ERRO
        if($countErrors == $count_rows_file){
            $this->session->set_flashdata('error', $this->lang->line('application_prices_error_csv'));
            redirect('ProductsLoadByCSV', 'refresh');
        }
        // VERIFICO SE AO MENOS UMA LINHA DEU ERRO NOS PREÇOS E MOSTRO UMA MENSAGEM DE AVISO
        if($countErrors > 0){
            $this->session->set_flashdata('warning', $this->lang->line('application_prices_some_errors_csv'));
        }

        // Faz a cópia do arquivo para outro local e salva o registro no banco de dados.
        if (copy($file, $new_file)){
            $csv_to_verification = array(
                'upload_file'   => $new_file,
                'user_id'       => $this->session->userdata('id'),
                'username'      => $this->session->userdata('username'),
                'user_email'    => $this->session->userdata('email'),
                'usercomp'      => $this->data['usercomp'],
                'allow_delete'  => 0,
                'module'        => 'Products'
            );
            
            if ($this->model_csv_to_verifications->create($csv_to_verification)) {
                $this->session->set_flashdata('success', sprintf(
                    $this->lang->line('messages_file_added_to_import_queue_see_the_link'),
                    base_url('FileProcess/product_load'),
                    "{$this->lang->line('application_products')} > {$this->lang->line('application_add_products')} > {$this->lang->line('application_file_process')}"
                ));
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred') . ' .' . $this->lang->line('messages_reload_page_try_again'));
            }
        } else {
            $this->session->set_flashdata('error', $this->lang->line('messages_save_media_error') . ' .' . $this->lang->line('messages_reload_page_try_again'));
        }
        
        redirect('ProductsLoadByCSV', 'refresh');
	}

    public function onlyVerifySyncPublishedSku()
    {
        if (!in_array('syncPublishedSku', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $dir_to_csv     = 'assets/images/csv_of_sync_skuseller_skumkt_uploaded/';
        $dirPathTemp    = "assets/files/sync_skuseller_skumkt_upload/";

        // Se não existir o diretório, será criado.
        if (!is_dir($dir_to_csv)) {
            mkdir($dir_to_csv);
        }

        // Se não existir o diretório temporário, será criado.
        if (!is_dir($dirPathTemp)) {
            mkdir($dirPathTemp);
        }

        $file       = $this->uploadFile($dir_to_csv);
        $new_file   = $dir_to_csv.$this->getGUID(false).'.csv';

        // Faz a cópia do arquivo para outro local e salva o registro no banco de dados.
        if (copy($file, $new_file)){
            $csv_to_verification = array(
                'upload_file'   => $new_file,
                'user_id'       => $this->session->userdata('id'),
                'username'      => $this->session->userdata('username'),
                'user_email'    => $this->session->userdata('email'),
                'usercomp'      => $this->data['usercomp'],
                'allow_delete'  => 0,
                'module'        => 'SyncPublishedSku'
            );

            if ($this->model_csv_to_verifications->create($csv_to_verification)) {
                $this->session->set_flashdata('success', sprintf(
                    $this->lang->line('messages_file_added_to_import_queue_see_the_link'),
                    base_url('FileProcess/product_load'),
                    "{$this->lang->line('application_products')} > {$this->lang->line('application_add_products')} > {$this->lang->line('application_file_process')}"
                ));
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred') . ' .' . $this->lang->line('messages_reload_page_try_again'));
            }
        } else {
            $this->session->set_flashdata('error', $this->lang->line('messages_save_media_error') . ' .' . $this->lang->line('messages_reload_page_try_again'));
        }

        redirect('ProductsLoadByCSV/SyncPublishedSku', 'refresh');
    }

    public function onlyVerifyGroupSimpleSku()
    {
        if (!in_array('groupSimpleSku', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $dir_to_csv     = 'assets/images/csv_of_group_simple_sku_uploaded/';
        $dirPathTemp    = "assets/files/sync_group_simple_sku_upload/";

        // Se não existir o diretório, será criado.
        if (!is_dir($dir_to_csv)) {
            mkdir($dir_to_csv);
        }

        // Se não existir o diretório temporário, será criado.
        if (!is_dir($dirPathTemp)) {
            mkdir($dirPathTemp);
        }

        $file       = $this->uploadFile($dir_to_csv);
        $new_file   = $dir_to_csv.$this->getGUID(false).'.csv';

        // Faz a cópia do arquivo para outro local e salva o registro no banco de dados.
        if (copy($file, $new_file)){
            $csv_to_verification = array(
                'upload_file'   => $new_file,
                'user_id'       => $this->session->userdata('id'),
                'username'      => $this->session->userdata('username'),
                'user_email'    => $this->session->userdata('email'),
                'usercomp'      => $this->data['usercomp'],
                'allow_delete'  => 0,
                'module'        => 'GroupSimpleSku'
            );

            if ($this->model_csv_to_verifications->create($csv_to_verification)) {
                $this->session->set_flashdata('success', sprintf(
                    $this->lang->line('messages_file_added_to_import_queue_see_the_link'),
                    base_url('FileProcess/product_load'),
                    "{$this->lang->line('application_products')} > {$this->lang->line('application_add_products')} > {$this->lang->line('application_file_process')}"
                ));
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred') . ' .' . $this->lang->line('messages_reload_page_try_again'));
            }
        } else {
            $this->session->set_flashdata('error', $this->lang->line('messages_save_media_error') . ' .' . $this->lang->line('messages_reload_page_try_again'));
        }

        redirect('ProductsLoadByCSV/GroupSimpleSku', 'refresh');
    }

    public function onlyVerifyCatalogProduct()
    {
        if (in_array('createProduct', $this->permission) || !in_array('disablePrice', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $dir_to_csv     = 'assets/images/csv_of_catalog_product_marketplace_uploaded/';
        $dirPathTemp    = "assets/files/sync_catalog_product_marketplace_upload/";

        checkIfDirExist($dir_to_csv);
        checkIfDirExist($dirPathTemp);

        $file       = $this->uploadFile($dir_to_csv);
        $new_file   = $dir_to_csv.$this->getGUID(false).'.csv';

        // Faz a cópia do arquivo para outro local e salva o registro no banco de dados.
        if (copy($file, $new_file)){
            $csv_to_verification = array(
                'upload_file'   => $new_file,
                'user_id'       => $this->session->userdata('id'),
                'username'      => $this->session->userdata('username'),
                'user_email'    => $this->session->userdata('email'),
                'usercomp'      => $this->data['usercomp'],
                'allow_delete'  => 0,
                'module'        => 'CatalogProductMarketplace'
            );

            if ($this->model_csv_to_verifications->create($csv_to_verification)) {
                $this->session->set_flashdata('success', sprintf(
                    $this->lang->line('messages_file_added_to_import_queue_see_the_link'),
                    base_url('FileProcess/product_load'),
                    "{$this->lang->line('application_products')} > {$this->lang->line('application_add_products')} > {$this->lang->line('application_file_process')}"
                ));
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred') . ' .' . $this->lang->line('messages_reload_page_try_again'));
            }
        } else {
            $this->session->set_flashdata('error', $this->lang->line('messages_save_media_error') . ' .' . $this->lang->line('messages_reload_page_try_again'));
        }

        redirect('ProductsLoadByCSV/CatalogProduct', 'refresh');
    }

    public function uploadFile(string $upload_path)
	{
		$config['upload_path']   = $upload_path;
		$config['file_name']     =  uniqid();
		$config['allowed_types'] = 'csv|txt';
        
		$this->load->library('upload', $config);
        
		if (!$this->upload->do_upload('product_upload')) {
			$error = $this->upload->display_errors();
			$this->data['upload_msg'] = $this->lang->line('messages_invalid_file');
			$this->data['upload_msg'] = $error;
			return false;
		} else {
			$data = array('upload_data' => $this->upload->data());
			$type = explode('.', $_FILES['product_upload']['name']);
			$type = $type[count($type) - 1];

			$path = $config['upload_path'] . '/' . $config['file_name'] . '.' . $type;
			return $data ? $path : false;
		}
	}
}