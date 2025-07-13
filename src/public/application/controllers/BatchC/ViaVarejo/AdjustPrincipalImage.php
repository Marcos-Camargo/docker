<?php

/**
 * @property CI_Session $session
 * @property CI_Loader $load
 * @property CI_Router $router
 *
 * @property Model_products $model_products
 * @property UploadProducts $uploadproducts
 */

class AdjustPrincipalImage extends BatchBackground_Controller
{
	public function __construct()
	{
		parent::__construct();
		$logged_in_sess = array(
			'id' => 1,
			'username'  => 'batch',
			'email'     => 'batch@conectala.com.br',
			'usercomp' => 1,
			'userstore' => 0,
			'logged_in' => TRUE
		);
		$this->session->set_userdata($logged_in_sess);
		
		$this->load->model('model_products');
		$this->load->library('UploadProducts');
    }
	
	public function run($id = null, $params = null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params));

		$this->adjust();

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish');
		$this->gravaFimJob();
	}
		
	private function adjust() {


		$limit 	= 2000;
		$offset = 0;

		while (true) {
			$products = $this->model_products->getByPrincipalImageNull($offset, $limit);
			if (count($products) == 0) {
				echo "fim da fila\n";
				break;
			}
			$offset += $limit;

			foreach ($products as $product) {
				foreach($this->model_products->getVariantsByProd_id($product['id']) as $variant) {
					$principal_image = $this->uploadproducts->getPrimaryImageDir("{$product['image']}/{$variant['image']}");
					if (empty($principal_image)) {
						continue;
					}

					$update_var = array('principal_image' => $principal_image);
					if (
						$product['category_id'] != '[""]' &&
						$product['brand_id'] != '[""]' &&
						$product['situacao'] != 2
					) {
						$update_var['situacao'] = 2;
					}
					$this->model_products->update($update_var, $product['id']);
                    echo "Atualizou {$product['id']} - ". json_encode($update_var)."\n";
					break;
				}
			}
		}
	}
}

