<?php

/**

 */
require_once APPPATH . "libraries/Microservices/v1/Logistic/FreightTables.php";
require_once APPPATH . "libraries/Microservices/v1/Logistic/ShippingIntegrator.php";
require_once APPPATH . "libraries/Microservices/v1/Logistic/ShippingCarrier.php";
require_once APPPATH . "libraries/Microservices/v1/Logistic/Shipping.php";
require_once APPPATH . "libraries/Microservices/v1/Logistic/PickupPoints.php";
require_once APPPATH . "libraries/Microservices/v1/Integration/Price.php";
require_once APPPATH . "libraries/Microservices/v1/Integration/Stock.php";

use Microservices\v1\Logistic\FreightTables;
use Microservices\v1\Logistic\PickupPoints;
use Microservices\v1\Logistic\ShippingIntegrator;
use Microservices\v1\Logistic\ShippingCarrier;
use Microservices\v1\Logistic\Shipping;
use Microservices\v1\Integration\Price;
use Microservices\v1\Integration\Stock;

/**
 * @property FreightTables $ms_freight_tables
 * @property ShippingIntegrator $ms_shipping_integrator
 * @property ShippingCarrier $ms_shipping_carrier
 * @property Shipping $ms_shipping
 * @property PickupPoints $ms_pickup_points
 * @property Price $ms_price
 * @property Stock $ms_stock
 *
 * @property Model_settings $model_settings
 */

class UpdateMSSettings extends BatchBackground_Controller
{

	/**
	 * @return void
	 */
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
        /* ATENÇÂO : AO COLOCAR UM NOVO MS, ALTERAR TAMBÈM O BATCHC/UpdateMSSetting.php */
		$this->load->model('model_settings');       
		$this->load->library("Microservices\\v1\\Logistic\\FreightTables", array(), 'ms_freight_tables');
        $this->load->library("Microservices\\v1\\Logistic\\ShippingIntegrator", array(), 'ms_shipping_integrator');
        $this->load->library("Microservices\\v1\\Logistic\\ShippingCarrier", array(), 'ms_shipping_carrier');
        $this->load->library("Microservices\\v1\\Logistic\\Shipping", array(), 'ms_shipping');
        $this->load->library("Microservices\\v1\\Integration\\Stock", array(), 'ms_stock');
        $this->load->library("Microservices\\v1\\Integration\\Price", array(), 'ms_price');

    }

	// php index.php BatchC/UpdateMSSettings run null/ALL
	public function run($id = null, $params = null)
	{
		/* inicia o job */
		$this->setIdJob($id); 
		$log_name = $this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
        if (is_null($params)) {
            $params = 'null';
        }
        $params = strtoupper($params);

        if (($params != 'NULL') && ($params != 'ALL')) {
            echo "Parametro ".$params." não reconhecido \n";
            echo "ou informe null para puxar os parametros alterados nas últimas 6 horas ou informe ALL para todos os parametros";
            die; 
        }
		/* faz o que o job precisa fazer */
		$this->checkAndUpdateSettings($params);
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	/**
     * Define novas regras de bloqueio
	 */
	private function checkAndUpdateSettings($params)
	{
		echo "Verificando se tem settings a ser alterados\n";

		if (!(isset($this->ms_shipping_carrier) && $this->ms_shipping_carrier->use_ms_shipping) &&
			!(isset($this->ms_shipping_integrator) && $this->ms_shipping_integrator->use_ms_shipping) &&
			!(isset($this->ms_freight_tables) && $this->ms_freight_tables->use_ms_shipping) &&
            !(isset($this->ms_shipping) && $this->ms_shipping->use_ms_shipping) &&
            !(isset($this->ms_pickup_points) && $this->ms_pickup_points->use_ms_shipping) &&
			!(isset($this->ms_price) && $this->ms_price->use_ms_price) &&
			!(isset($this->ms_stock) && $this->ms_stock->use_ms_stock)) {
			echo "Este sellercenter ainda não usa microserviços\n";
			return ;
		}

		$settings = $this->model_settings->getSettingData();
		foreach($settings as $data) {
			if ($params != 'ALL') {
				if (DateTime::createFromFormat('Y-m-d H:i:s', $data['date_updated'])->getTimestamp() < strtotime("-6 hours")) {
					continue; 
				}
			}
            if (empty($data['value'])) {
                $data['value'] = '-';
            }
			echo "Atualizando ".$data['name']." nos microsservicos\n"; 
			// Ajustes para o microsserviço.
			$data['active'] = $data['status'] == 1;
            unset($data['status']);
            unset($data['user_id']);
            unset($data['setting_category_id']);
            unset($data['friendly_name']);
            unset($data['description']);
            $this->microServicesSettings($data, $data['name']);

		}
	}

	private function microServicesSettings($data, $setting_name_old)
    {
        try {
            if ($this->ms_shipping_carrier->use_ms_shipping) {
                $this->ms_shipping_carrier->updateSetting($data, $setting_name_old);
            }
        } catch (Exception $exception) {
            $message = $this->ms_shipping_carrier->getErrorFormatted($exception->getMessage());
            if (isset($message[0]) && $message[0] === 'Parâmetro não localizado.') {
                // Tentar criar
                try {
                    $this->ms_shipping_carrier->createSetting($data);
                } catch (Exception $exception) {
					echo "Erro ao tentar criar ".$setting_name_old." em ms_shipping_carrier\n";
					var_dump($exception->getMessage());
				}
            }
        }

        try {
            if ($this->ms_shipping_integrator->use_ms_shipping) {
                $this->ms_shipping_integrator->updateSetting($data, $setting_name_old);
            }
        } catch (Exception $exception) {
            $message = $this->ms_shipping_integrator->getErrorFormatted($exception->getMessage());
            if (isset($message[0]) && $message[0] === 'Parâmetro não localizado.') {
                // Tentar criar
                try {
                    $this->ms_shipping_integrator->createSetting($data);
                } catch (Exception $exception) {
					echo "Erro ao tentar criar ".$setting_name_old." em ms_shipping_integrator\n";
					var_dump($exception->getMessage());
				}
            }
        }

        try {
            if ($this->ms_freight_tables->use_ms_shipping) {
                $this->ms_freight_tables->updateSetting($data, $setting_name_old);
            }
        } catch (Exception $exception) {
            $message = $this->ms_freight_tables->getErrorFormatted($exception->getMessage());
            if (isset($message[0]) && $message[0] === 'Parâmetro não localizado.') {
                // Tentar criar
                try {
                    $this->ms_freight_tables->createSetting($data);
                } catch (Exception $exception) {
					echo "Erro ao tentar criar ".$setting_name_old." em ms_freight_tables\n";
					var_dump($exception->getMessage());
				}
            }
        }

        try {
            if ($this->ms_shipping->use_ms_shipping) {
                $this->ms_shipping->updateSetting($data, $setting_name_old);
            }
        } catch (Exception $exception) {
            $message = $this->ms_shipping->getErrorFormatted($exception->getMessage());
            if (isset($message[0]) && $message[0] === 'Parâmetro não localizado.') {
                // Tentar criar
                try {
                    $this->ms_shipping->createSetting($data);
                } catch (Exception $exception) {
					echo "Erro ao tentar criar ".$setting_name_old." em ms_shipping\n";
					var_dump($exception->getMessage());
				}
            }
        }

        try {
            if ($this->ms_price->use_ms_price) {
                $this->ms_price->updateSetting($data, $setting_name_old);
            }
        } catch (Exception $exception) {
            $message = $this->ms_price->getErrorFormatted($exception->getMessage());
            if (isset($message[0]) && $message[0] === 'Parâmetro não localizado.') {
                // Tentar criar
                try {
                    $this->ms_price->createSetting($data);
                } catch (Exception $exception) {
					echo "Erro ao tentar criar ".$setting_name_old." em ms_price\n";
					var_dump($exception->getMessage());
                }
            }
        }

        try {
            if ($this->ms_stock->use_ms_stock) {
                $this->ms_stock->updateSetting($data, $setting_name_old);
            }
        } catch (Exception $exception) {
            $message = $this->ms_stock->getErrorFormatted($exception->getMessage());
            if (isset($message[0]) && $message[0] === 'Parâmetro não localizado.') {
                // Tentar criar
                try {
                    $this->ms_stock->createSetting($data);
                } catch (Exception $exception) {
					echo "Erro ao tentar criar ".$setting_name_old." em ms_stock\n";
					var_dump($exception->getMessage());
                }
            }
        }

        try {
            if ($this->ms_pickup_points->use_ms_shipping) {
                $this->ms_pickup_points->updateSetting($data, $setting_name_old);
            }
        } catch (Exception $exception) {
            $message = $this->ms_pickup_points->getErrorFormatted($exception->getMessage());
            if (($message[0] ?? '') === 'Parâmetro não localizado.') {
                // Tentar criar
                try {
                    $this->ms_pickup_points->createSetting($data);
                } catch (Exception $exception) {}
            }
        }
    }

}
