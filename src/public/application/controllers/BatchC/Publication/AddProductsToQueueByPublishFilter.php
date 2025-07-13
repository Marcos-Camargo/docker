<?php

require 'system/libraries/Vendor/autoload.php';
require_once APPPATH . "libraries/Publish/Publishing.php";

use Publish\Publishing;

/**
 * @property CI_Loader $load
 * @property CI_Router $router
 * @property CI_Session $session
 * @property CI_DB_query_builder $db
 *
 * @property Model_csv_to_verifications $model_csv_to_verifications
 * @property Model_integrations $model_integrations
 * @property Model_queue_products_marketplace $model_queue_products_marketplace
 * @property Model_users $model_users
 * @property Model_company $model_company
 * @property Model_settings $model_settings
 *
 * @property Publishing $publishing
 */

class AddProductsToQueueByPublishFilter extends BatchBackground_Controller
{
	private $module;
	
	public function __construct()
	{
		parent::__construct();
		$logged_in_sess = array(
			'id' 		=> 1,
			'username'  => 'batch',
			'email'     => 'batch@conectala.com.br',
			'usercomp' 	=> 1,
			'userstore' => 0,
			'logged_in' => TRUE
		);
		$this->session->set_userdata($logged_in_sess);

		$this->load->model('model_csv_to_verifications');
		$this->load->model('model_integrations');
        $this->load->model('model_queue_products_marketplace');
        $this->load->model('model_users');
        $this->load->model('model_company');
        $this->load->model('model_settings');

	$this->load->helper('datatables');

        $this->load->library("Publish\\Publishing", array(), 'publishing');
    }
	
	public function run($id=null,$params=null)
	{
		$this->module = 'AddProductsToQueueByPublishFilter';

		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}

		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");

		$this->processProducts();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	private function processProducts()
	{
		$files = $this->model_csv_to_verifications->getDontChecked(false, $this->module);

        if (!count($files)) {
            echo "Não tem produtos enviados para publicação\n";
            return;
        }

        $company = $this->model_company->getCompanyData(1);
        $logo = base_url() . $company['logo'];

        $sellercenter_name = $this->model_settings->getValueIfAtiveByName('sellercenter_name');
        $from_send_email = $this->model_settings->getValueIfAtiveByName('email_marketing');

        if (!$sellercenter_name) {
            $sellercenter_name = 'Conecta Lá';
        }
        if (!$from_send_email) {
            $from_send_email = 'api@conectala.com.br';
        }

		foreach ($files as $file) {
            $fileTempId = $file['id'];

			echo "Lendo dados do id=$fileTempId ...\n";

            // {"int_to_several":["ConectaLa"],"buscasku":"teste","buscanome":"produto","buscaestoque":"1","busca_situacao":"0","busca_status":"0","busca_lojas":"15,21","busca_marketplace":"ConectaLa","busca_status_integracao":"999","filter_search":"hex"}
			$formData = json_decode($file['form_data']);
			
			if (empty($formData)) {
				echo "Valor do campo 'form_data' chegou vazio ou incorreto. id=$fileTempId\n";
                $this->model_csv_to_verifications->update(
                    array(
                        'processing_response' 	=> 'Dados gerados incorretamente. (form_data empty)',
                        'final_situation' 		=> 'err',
                        'checked' 			  	=> 1
                    ),
                    $fileTempId
                );
				continue;
			}

            $status         = $formData->busca_status ?? '';
            $stores         = $formData->busca_lojas ? explode(',', $formData->busca_lojas ?? '') : '';
            $status_int     = $formData->busca_status_integracao ?? '';
            $int_to         = $formData->busca_marketplace ? explode(',', $formData->busca_marketplace ?? '') : '';
            $sku            = trim($formData->buscasku ?? '');
            $name           = trim($formData->buscanome ?? '');
            $stock          = (int)trim($formData->buscaestoque ?? '');
            $situation      = (int)trim($formData->busca_situacao ?? '');
            $search_text    = trim($formData->filter_search);
            $filters        = array();
            $filter_default = array();

            $deletedStatus = Model_products::DELETED_PRODUCT;
            $filter_default[]['where']['p.status !='] = $deletedStatus;
            $filter_default[]['where']['p.dont_publish !='] = true;

            if (!empty($sku)) {
                $filters[]['like']['p.sku'] = $sku;
            }
            if (!empty($name)) {
                $filters[]['like']['p.name'] = $name;
            }
            if ($stock) {
                $filters[]['where'][$stock == 1 ? 'p.qty >' : 'p.qty <='] = 0;
            }
            if ($situation) {
                $filters[]['where']['p.situacao'] = $situation == 1 ? 1 : 2;
            }
            if ($status) {
                $filters[]['where']['p.status'] = $status;
            }
            if (is_array($stores) && !empty($stores)) {
                $filters[]['where_in']['s.id'] = $stores;
            }

            if ($int_to) {
                if (!array_filter($int_to) == []){
                    if (is_array($int_to)) {
                        $int_tos = $int_to;
                        $filters[]['group_start'] = '';
                        foreach($int_tos as $int_to) {
                            $filters[]['or_group_start'] = '';

                            if ($status_int == 998) {
                                $filters[]['where']['i.int_to !='] = $int_to;
                            } else {
                                $filters[]['where']['i.int_to'] = $int_to;
                            }
                            if ($status_int && ($status_int != 999 && $status_int != 998 && $status_int != 40)) {
                                $filters[]['where']['i.status_int'] = $status_int;
                            }

                            $filters[]['group_end'] = '';
                        }
                        $filters[]['group_end'] = '';

                        if ($status_int == 998) {
                            $filters[]['where']['i.id !='] = null;  
                        }

                        if ($status_int != 999 && $status_int != 998) {
                            switch ($status_int) {
                                case 30:
                                    $filters[]['where']['et.status'] = 0;
                                    break;
                                case 40:
                                    $filters[]['where']['i.ad_link !='] = null;
                                    break;
                                default:
                                    $filters[]['where']['i.status_int'] = $status_int;
                                    break;
                            }
                        }
                    }
                }
            } else {
                // SE NÃO TEM NENHUM MARKETPLACE SELECIONADO, CONSULTA TODOS OS PRODUTOS QUE NAO ESTAO PUBLICADOS
                if ($status_int != 999) {
                    switch ($status_int) {             
                        case 998:
                            $filters[]['where']['i.id'] = null;
                            break;
                        default:
                            $filters[]['where']['i.status_int'] = $status_int;
                            break;
                    }
                } 
            }

            $this->data['usercomp']  = $file['usercomp'];
            $this->data['userstore'] = $file['store_id'] ?? 0;

            $intos_Active  = $formData->int_to_several ?? null;
            $intos_Inactive = $formData->int_to_inactive ?? null;

            $offset = 0;
            $limit = 500;

            echo "Filtro aplicado: ". json_encode($filters, JSON_UNESCAPED_UNICODE) . "\n";
            $fields_order = array('', '', 'p.sku', 'p.name', 's.name', 'p.price', 'p.qty', 'p.status', 'p.situacao', 'i.int_to', '');

            $filters = array_merge_recursive($filter_default, $filters);

            $query = array();
            $query['select'][] = "p.*, s.name AS store";
            $query['from'][] = 'products p';
            $query['join'][] = ["stores s", "s.id=p.store_id ", 'LEFT'];
            $query['join'][] = ["prd_to_integration i", "i.prd_id = p.id", 'LEFT'];
            $query['join'][] = ["errors_transformation et", "et.prd_id = p.id", 'LEFT'];

            $registers_count = getFetchDataTables(
                $this->db,
                $query,
                $this->data,
                array(
                    'company'   => 'p.company_id',
                    'store'     => 'p.store_id'
                ),
                null,
                null,
                array('p.id', 'DESC'),
                'p.id',
                $search_text,
                $filters,
                true,
                $fields_order
            );

            $limit_add_product_queue_publish_filter = $this->model_settings->getValueIfAtiveByName('limit_add_product_queue_publish_filter') ?: 10000 ;
            if ($registers_count > intval($limit_add_product_queue_publish_filter)) {
                echo "Existem um total de $registers_count registros. O limite é de $limit_add_product_queue_publish_filter \n";
                $this->model_csv_to_verifications->update(
                    array(
                        'processing_response' 	=> "Existem um total de $registers_count registros",
                        'final_situation' 		=> 'err',
                        'checked' 			  	=> 1
                    ),
                    $fileTempId
                );
                continue;
            }

            while (true) {
                echo "Offset: $offset - Limit:$limit\n";
                $products = getFetchDataTables(
                    $this->db,
                    $query,
                    $this->data,
                    array(
                        'company'   => 'p.company_id',
                        'store'     => 'p.store_id'
                    ),
                    $offset,
                    $limit,
                    array('p.id', 'DESC'),
                    'p.id',
                    $search_text,
                    $filters,
                    false,
                    $fields_order
                );

                // Fim dos registros;
                if (empty($products)) {
                    echo "Fim dos produtos\n";
                    break;
                }

                echo "Lendo ".count($products)." produtos \n";

                $offset += $limit;

                while ($this->model_queue_products_marketplace->countQueue()['qtd'] > 500) {
                    echo "Fila com muitos produtos, aguardar 10 segundos para checar novamente\n";
                    sleep(10);
                }

                foreach ($products as $product) {
                    $product_id = $product['id'];

                    $this->publishing->setPublish($product_id, $intos_Active, 1, False);
                    $this->publishing->setPublish($product_id, $intos_Inactive, 0, False);
                }

                echo "Enviado\n";
            }

            $label_status = 'publicação';
            if (!empty($intos_Inactive)) {
                $label_status = 'inativação';
            }

			$this->model_csv_to_verifications->update(
				array(
					'processing_response' => 'Arquivo processado com sucesso!',
					'final_situation' => 'success',
					'checked' => '1'
				),
				$fileTempId
			);

			echo "Arquivo processado com sucesso. arquivo id=$fileTempId\n";

            $user = $this->model_users->getUserData($file['user_id']);

            $body = "
            <body lang=PT-BR link='#0563C1' vlink='#954F72' style='tab-interval:35.4pt'>
                <div>
                <p style='text-align:center;'><img width=210 height=63 src='$logo' ></p>
                    <p style='text-align:center;'>Olá, $user[firstname] $user[lastname], bem-vindo ao SellerCenter <?=$sellercenter_name?>!</p>
                    <p style='text-align:center;'><span style='font-family:'Helvetica',sans-serif;color:#404040;background:white'></span></p>
                    <p style='text-align:center;'><span style='font-family:'Helvetica',sans-serif;color:#404040;background:white'>Nossa missão é tornar sua experiência de vendas nos nossos canais simples, fácil e de muito resultado. Conte conosco!</span></p>
                    <p style='text-align:center;'></p>
                    <br>
                    <h3 style='text-align:center;font-weight: bold'>Foram enviados $registers_count produtos para $label_status com sucesso.</h3>
                    <br>
                    <p style='text-align:center;'><span style='font-family:'Helvetica',sans-serif;color:#404040;background:white'>Qualquer dúvida estamos a disposição!</span><br>
                    <span style='background:white'>Um abraço</span><br>
                    <br><br>
                </div>
            </body>
            ";

            $title = "Publicação de produtos $sellercenter_name";
            $this->sendEmailMarketing($user["email"], $title, $body, $from_send_email);
		}
	}
}
