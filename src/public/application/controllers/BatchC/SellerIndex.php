<?php

class SellerIndex extends BatchBackground_Controller
{
	private $stores;

	// Número (em dias) que um seller index deverá ser considerado velho e, consequentemente, retirado do histórico
	private const EXPIRE = 60;

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
 
        $this->load->model('model_stores');
        $this->load->model('model_query_seller_index');
    }

    // php index.php BatchC/SellerIndex run
	function run($id=null, $params=null)
	{
		/* inicia o job */
		$this->setIdJob($id); 
		$log_name = $this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		/* faz o que o job precisa fazer */
		$this->getStoreIndexes();
		$this->searchAndDeleIndexOlder();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	private function getStoreIndexes()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		echo "Pegando as lojas ativas \n";
		$this->stores = $this->model_stores->getAllActiveStore();

		if (!$this->stores) {
			$message = "Não foi encontrada loja ativa para atualizar o Seller Index";
			echo $message." \n"; 
			$this->log_data('batch', $log_name, $message, "E");
			return false;
		}

		foreach ($this->stores as $key => $store) {
			$sellerIndex = $this->getSellerIndex($store['id']);

			if (!$sellerIndex) {
				continue;
			}

			$indexExists = $this->currentDayIndexAlreadyExists($store['id'], date('Y-m-d'));

			if ($indexExists) {
				$message = "Já existe um Seller Index para a loja ".$store['id']." com a data de hoje";
				echo $message." \n"; 
				$this->log_data('batch', $log_name, $message, "E");
				continue;
			}

			$created = $this->createSellerIndex($store['id'], $sellerIndex);

			if (!$created) {
				continue;
			}
		}
	}

	private function getSellerIndex($storeId)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		if (!$storeId) {
			$message = "Não foi informado o ID de uma loja válida";
			echo $message." \n"; 
			$this->log_data('batch', $log_name, $message, "E");
			return false;
		}

		echo "Obtendo o Seller Index da loja $storeId \n";

        try {
            $querySellerIndex = $this->model_query_seller_index->get_all()[0];

            $values = [];

            if (!empty($querySellerIndex)) {
                $fields = [
                    'store_reputation',
                    'cancellation_evaluation',
                    'shipping_delay_assessment',
                    'delivery_delay_assessment'
                ];

                foreach ($fields as $field) {
                    if (!empty($querySellerIndex[$field])) {
                        $result = $this->model_query_seller_index->execute_query($querySellerIndex[$field]);

                        if (!empty($result) && isset($result[0])) {
                            switch ($field) {
                                case 'store_reputation':
                                    if (isset($result[0]['Reputação da loja'])) {
                                        $value = $result[0]['Reputação da loja'];
                                    }
                                    break;
                                case 'cancellation_evaluation':
                                case 'shipping_delay_assessment':
                                case 'delivery_delay_assessment':
                                    if (isset($result[0]['Nota'])) {
                                        $value = $result[0]['Nota'];
                                    }
                                    break;
                            }
                        }

                        if (is_numeric($value)) {
                            $values[] = (float)$value;
                        }
                    }
                }

                if (!empty($values)) {
                    if ((int)$querySellerIndex['average'] === 1) {
                        $media = array_sum($values) / count($values);
                        $sellerIndex = round($media, 2);
                    } else {
                        $sellerIndex = min($values);
                    }
                } else {
                    $message = "Nenhum valor válido retornado nas queries do Seller Index.";
                    echo $message . "\n";
                    $this->log_data('batch', $log_name, $message, "E");
                    return false;
                }

            } else {
                $sellerIndex = $this->model_stores->getStoreSellerIndex($storeId);
            }
        } catch (Exception $e) {
            $message = "Não foi possível obter o Seller Index para a loja $storeId. Erro: ".$e->getMessage();
            echo $message." \n";
            $this->log_data('batch', $log_name, $message, "E");
            return false;
        }

		if ($sellerIndex === null || $sellerIndex === false) {
			$message = "Não foi possível obter o Seller Index para a loja $storeId";
			echo $message." \n"; 
			$this->log_data('batch', $log_name, $message, "E");
			return false;
		}

		return $sellerIndex;
	}

	private function createSellerIndex($storeId, $sellerIndex)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		if (!$storeId) {
			$message = "Não foi informado o ID de uma loja válida";
			echo $message." \n"; 
			$this->log_data('batch', $log_name, $message, "E");
			return false;
		}

		if ($sellerIndex === null || $sellerIndex === false) {
			$message = "Não foi possível obter o Seller Index para a loja $storeId";
			echo $message." \n"; 
			$this->log_data('batch', $log_name, $message, "E");
			return false;
		}

		$data = [
			'store_id' => $storeId,
			'seller_index' => $sellerIndex,
			'date' => date('Y-m-d')
		];

		echo "Salvando Seller Index para a loja $storeId \n";
		$saved = $this->model_stores->saveSellerIndex($data);

		if (!$saved) {
			$message = "Não foi possível salvar o Seller Index para a loja $storeId";
			echo $message." \n"; 
			$this->log_data('batch', $log_name, $message, "E");
			return false;
		}

		return true;
	}

	private function currentDayIndexAlreadyExists($storeId, $date)
	{
		echo "Verificando se já existe um Seller Index registrado para a loja ".$storeId." hoje \n";
		$hasIndex = $this->model_stores->getSellerIndex(['store_id' => $storeId, 'date' => $date]);

		return $hasIndex;
	}

	private function searchAndDeleIndexOlder()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		$expire = '-'.self::EXPIRE.' days';

		echo "Verificando se existe algum Seller Index com mais de ".self::EXPIRE." dias \n";
		$sellersIndexExpired = $this->model_stores->getSellerIndex('date < "'.date("Y-m-d", strtotime(date("Y-m-d").$expire)).'"');

		if (!$sellersIndexExpired) {
			$message = "Não foi encontrado nenhum Seller Index com mais de ".self::EXPIRE." dias";
			echo $message." \n"; 
			$this->log_data('batch', $log_name, $message, "I");
			return false;
		}

		echo "Excluindo Seller Index com mais de ".self::EXPIRE." dias \n";
		foreach ($sellersIndexExpired as $key => $sellerIndexExpired) {
			$result = $this->model_stores->deleteSellerIndex($sellerIndexExpired['id']);

			if (!$result) {
				$message = "Erro ao tentar excluir o Seller Index de ID ".$sellerIndexExpired['id'];
				echo $message." \n"; 
				$this->log_data('batch', $log_name, $message, "E");
				continue;
			}
		}
	}
}
