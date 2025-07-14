<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . "libraries/Cache/RedisCodeigniter.php";
require_once APPPATH . "libraries/Microservices/v1/Logistic/Shipping.php";
require_once APPPATH . "libraries/Microservices/v1/Logistic/ShippingCarrier.php";
require_once APPPATH . "libraries/Microservices/v1/Logistic/ShippingIntegrator.php";

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Promise\PromiseInterface;
/**
 * @property RedisCodeigniter $redis
 * @property \Microservices\v1\Logistic\Shipping $ms_shipping
 * @property \Microservices\v1\Logistic\ShippingCarrier $ms_shipping_carrier
 * @property \Microservices\v1\Logistic\ShippingIntegrator $ms_shipping_integrator
 */
class CalculoFrete {
	
	var $instance;
	var $readonlydb;
	private $fieldSKUQuote;

    public $logistic;
    public $sellercenter;
    public $dataIntegration = array();

    /**
     * @var LogisticTypes
     */
    private $logisticTypes;

    public $_time_start = 0;
    public $_time_start_query_sku = 0;
    public $_time_end_query_sku = 0;
    public $_time_start_integration = 0;
    public $_time_end_integration = 0;
    public $_time_start_integration_instance = 0;
    public $_time_end_integration_instance = 0;
    public $_time_start_internal_table = 0;
    public $_time_end_internal_table = 0;
    public $_time_start_contingency = 0;
    public $_time_end_contingency = 0;
    public $_time_start_promotion = 0;
    public $_time_end_promotion = 0;
    public $_time_start_auction = 0;
    public $_time_end_auction = 0;
    public $_time_start_price_rules = 0;
    public $_time_end_price_rules = 0;
    public $_time_start_redis = 0;
    public $_time_end_redis = 0;
    public $_time_end = 0;
 // === PROPRIEDADES MULTISELLER ===
    
    /**
     * Habilita operação multiseller
     * @var bool
     */
    private bool $enable_multiseller_operation = false;
    
    /**
     * Flag de padronização de métodos de envio
     * @var bool
     */
    private bool $multiseller_freight_results = false;
    /**
     * Configuração de padronização de métodos de envio
     * @var array|null
     */
    private ?array $marketplace_shipping_standardization_config = null;
    /**
     * Cache de sellers detectados
     * @var array
     */
    private array $detected_sellers_cache = [];
    
    /**
     * Flag de inicialização multiseller
     * @var bool
     */
    private bool $multiseller_initialized = false;

    /**
     * Parâmetros extras para operação multiseller
     * @var array
     */
    private array $multiseller_params = [];

    private $validationResult = null;

    public function __construct()
    {
	    $this->instance = &get_instance();
        $this->readonlydb = ENVIRONMENT === 'production' || ENVIRONMENT === 'production_x' ? $this->instance->load->database('readonly', TRUE) : $this->instance->db;
        $this->instance->load->model('model_vtex_ult_envio');
        $this->instance->load->model('model_table_shipping');
        $this->instance->load->model('model_orders');
        $this->instance->load->model('model_quotes_ship');
        $this->instance->load->model('model_settings');
        $this->instance->load->model('model_integration_logistic');
        $this->instance->load->library("Microservices\\v1\\Logistic\\Shipping", [], 'ms_shipping');

        $this->ms_shipping =  $this->instance->ms_shipping;
        //$this->setSellerCenter();

        $this->instance->load->library("Cache/RedisCodeigniter", array(), 'redis');

        $this->logisticTypes = new LogisticTypes($this->readonlydb);
    }

    public function instanceLogistic(string $logistic, int $store, array $dataQuote, bool $freightSeller)
    {
        if ($logistic == 'mevo') {
            $logistic = 'vtex';
        }
        // Por padrão seguir o nome da biblioteca com apenas a primeira letra em maiúsculo, pode usar underline em separação.
        $nameLib = ucfirst($logistic);

        if ($nameLib === 'sellercenter' || !file_exists(APPPATH . "libraries/Logistic/$nameLib.php")) {
            throw new InvalidArgumentException("Logística $logistic não configurada");
        }

        $arrValidate = array_map(function ($item) {
            return likeText(
                "%application==libraries==Logistic==Logistic.php%",
                str_replace('/', '==', str_replace('\\', '==', $item))
            );
        }, get_included_files());

        if (!in_array(true, $arrValidate)) {
            require APPPATH . "libraries/Logistic/Logistic.php";
        } else {
            unset($this->instance->logistic);
        }

        $this->instance->load->library(
            "Logistic/$nameLib",
            array(
                'readonlydb'    => $this->readonlydb,
                'store'         => $store,
                'integration'   => $logistic,
                'dataQuote'     => $dataQuote, // dataQuote ou dados da tabela de loja.
                'freightSeller' => $freightSeller,
                'sellerCenter'  => $this->sellercenter,
                'redis'         => $this->instance->redis
            ),
            'logistic'
        );
        $this->logistic = $this->instance->logistic;
    }

    public function calculaTaxa($total_price)
	{
		$sql = 'select * from settings where name = ?';
		$query = $this->readonlydb->query($sql, array('adicional_frete'));
		$setting = $query->row_array();

		// não existe o parametro ou está inativo, retorno que não existe taxa
		if (!$setting || $setting['status'] == 2) {
            return 0;
        }

		$aditional = 0;
		if (!is_null($setting)) {
			$aditional = $setting['value'];
		}

		if ($total_price <= 40) {
			return(0.5 + $aditional);		
		} else if ($total_price <= 70) {
			return(0.8 + $aditional);	
		} else if ($total_price <= 100) {
			return(1 + $aditional);	
		} else if ($total_price <= 150) {
			return(1.5 + $aditional);		
		} else if ($total_price <= 200) {
			return(2 + $aditional);
		} else if ($total_price <= 250) {
			return(3 + $aditional);	
		} else {
			return(3.5 + $aditional);	
		}
	}
	
	public function verificaCorreios($row_ult)
    {
        $whereTipoVolume = 'tipo_volume_codigo =';
        if ($row_ult['tipo_volume_codigo'] == null) {
            $whereTipoVolume = 'tipo_volume_codigo is';
        }

		$peso_cubico = ceil($row_ult['altura'] * $row_ult['largura'] *  $row_ult['profundidade'] /6000);	
		$sql = "SELECT tipo_volume_codigo FROM freights_by_tipo_volume WHERE {$whereTipoVolume} ? LIMIT 1";
		$query = $this->readonlydb->query($sql,array($row_ult['tipo_volume_codigo']));

		$resp = $query->result_array();

		return (
		 	((float)$row_ult['peso_bruto'] <= 30) && 
		 	((float)$peso_cubico <= 30) &&
		 	((float)$row_ult['largura'] <= 100 )  &&
		 	((float)$row_ult['profundidade'] <= 100 ) &&
		 	((float)$row_ult['altura'] <= 100) &&
		 	(((float)$row_ult['largura']+(float)$row_ult['altura']+(float)$row_ult['profundidade']) <= 200 ) &&
		 	(count($resp) == 0)
		);
	}

    public function cadastraCEP($cep)
    {
		$zip = substr('00000000'.$cep,-8); 
		$url = 'https://viacep.com.br/ws/' . $zip . '/json/';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 249);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 250);
		$output = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch); 
		if ($httpcode != 200) {
			return false;
		}
		$resp = json_decode($output,true);

		if (array_key_exists('erro', $resp)) {
			return false;
		}

		if (!array_key_exists('cep', $resp)) {
			return false;
		}

		$capitais = array(
			'Rio Branco/AC',
			'Maceió/AL',
			'Macapá/AP',
			'Manaus/AM', 
			'Salvador/BA',
			'Fortaleza/CE', 
			'Brasília/DF', 
			'Vitória/ES',
			'Goiânia/GO', 
			'São Luís/MA', 
			'Cuiabá/MT',
			'Campo Grande/MS',
			'Belo Horizonte/MG',
			'Belém/PA',
			'João Pessoa/PB', 
			'Curitiba/PR', 
			'Recife/PE',
			'Teresina/PI',
			'Rio de Janeiro/RJ', 
			'Natal/RN',
			'Porto Alegre/RS', 
			'Porto Velho/RO', 
			'Boa Vista/RR',
			'Florianópolis/SC',
			'São Paulo/SP', 
			'Aracaju/SE',
			'Palmas/TO', 
		);

		$data = array(
            'zipcode' => $cep, 
            'city' => $resp['localidade'],
            'state' => $resp['uf'],
            'address' => $resp['logradouro'],
            'neighborhood' => $resp['bairro'],
            'capital' => in_array($resp['localidade'], $capitais),
        );

		$insert = $this->instance->db->insert('cep', $data);
		return true;
	}

    public function calculaCorreios($fr)
    {
    	$cepDestino = $fr['destinatario']['endereco']['cep'];
	    $cepOrigem = $fr['expedidor']['endereco']['cep'];
		
		// Pego as informações do CEP
		$sql = 'SELECT * FROM cep WHERE zipcode = '.$cepOrigem;
		$query = $this->readonlydb->query($sql);
		$origem = $query->row_array();
		if (empty($origem)) { // não achei na base local
			if (!$this->cadastraCEP($cepOrigem)) { // Procuro na internet
				return array(
					'cep_origem' => $cepOrigem,
					'cep_destino' => $cepDestino,
					'erro' => 'CEP '.$cepOrigem.' não encontrado');
			} else {
				$sql = 'SELECT * FROM cep WHERE zipcode = '. $cepOrigem;
				$query = $this->readonlydb->query($sql);
				$origem = $query->row_array();
			}
		}

		$destino = $origem; 
		if ($cepOrigem !=$cepDestino) {
			$sql = 'SELECT * FROM cep WHERE zipcode = '. $cepDestino;
			$query = $this->readonlydb->query($sql);
			$destino = $query->row_array();
			if (empty($destino)) {  // não achei na base local
				if (!$this->cadastraCEP($cepDestino)) { // Procuro na internet
					return array(
						'cep_origem' => $cepOrigem,
						'cep_destino' => $cepDestino,
						'erro' => 'CEP '.$cepDestino.' não encontrado'
                    );
				} else {
					$sql = 'SELECT * FROM cep WHERE zipcode = '.$cepDestino;
					$query = $this->readonlydb->query($sql);
					$destino = $query->row_array();
				}
			}
		}
		
		// Vejos se tem exceções a consulta normal
		$excecaoSedex = '';
		$excecaoDivisa = '';
		if ($origem['state'] != $destino['state']) {
			$sql = 'SELECT * FROM cep_exceptions_divisa WHERE 
			origin_start_zipcode <= '.$cepOrigem.' AND origin_end_zipcode >= '.$cepOrigem.'
			AND destiny_start_zipcode <= '.$cepDestino.' AND destiny_end_zipcode >= '.$cepDestino;
			// AND type=2';
			$query = $this->readonlydb->query($sql);
			$excecaoDivisa = $query->row_array();
		} else {
			$sql = 'SELECT * FROM cep_exceptions_local WHERE 
			origin_start_zipcode <= '.$cepOrigem.' AND origin_end_zipcode >= '.$cepOrigem.'
			AND destiny_start_zipcode <= '.$cepDestino.' AND destiny_end_zipcode >= '.$cepDestino;
			// AND type=1';
			$query = $this->readonlydb->query($sql);
			$excecaoSedex = $query->row_array();
		}

		if (empty($excecaoSedex)) { 
			if (empty($excecaoDivisa)) {
				// Se não tem exceção, consulto a tabela normal
				$sql = 'SELECT * FROM correios_states WHERE origin = "'.$origem['state'].'" AND destiny = "'.$destino['state'].'"';
				$query = $this->readonlydb->query($sql);
				$states = $query->row_array();
				$nivel = $states['nivel'];
				if (strlen($nivel) == 1) { // Vejo se é capital x capital ou não 
					if (($origem['capital']) && ($destino['capital'])) {
						$nivel = 'N'.$nivel;
					} else {
						$nivel = 'I'.$nivel;
					}
				}
			} else {
				$nivel = $excecaoDivisa['nivel'];
			}
		} else {
			$nivel = $excecaoSedex['nivel'];
		}
		
		$resposta = array(
			'cep_origem' => $cepOrigem,
			'cep_destino' => $cepDestino,
			'nivel' => $nivel,
			'servicos' => array(
				'MINI' => array ('preco'=>0,'prazo'=>0),
				'PAC' => array ('preco'=>0,'prazo'=>0),
				'SEDEX' => array ('preco'=>0,'prazo'=>0),
				
			),
		);

		$mini = 0;
		$total_price = 0; 
		foreach ($fr['volumes'] as $item) {
			$total_price+= $item['valor'];
			$peso = $item['peso'];
			// Verificar se está no mínimo do correio
			if (((float)$item['altura'])< 0.02) {
				$item['altura'] = 0.02; 
			}

			if (((float)$item['comprimento']) < 0.16) {
				$item['comprimento'] = 0.16; 
			}

			if (((float)$item['largura']) < 0.11) {
				$item['largura'] = 0.11; 
			}
			$peso_cubico = ceil($item['altura'] * 100 * $item['largura'] * 100 *  $item['comprimento'] * 100 /6000);

			if ($peso_cubico > 5 ) {
				if ($peso_cubico > $item['peso']) {
					$peso = $peso_cubico;
				}
			}
			$peso = ceil($peso * 1000);
			if (($peso <=300) && ($item['valor'] <= 100) && ($item['comprimento']<=0.24) && ($item['largura']<=0.16) && 
				($item['altura']<=0.02)) {
				$mini++;
			} 
			$sql = 'SELECT * FROM correios_prices WHERE nivel = "'.$nivel.'" AND start_weight <= '.$peso.' 
							AND end_weight >='.$peso.' ORDER BY CAST(price AS DECIMAL(12,2)) ASC ';
			$query = $this->readonlydb->query($sql);
			$prices = $query->result_array();
			foreach($prices as $price) {
				$resposta['servicos'][$price['service']]['preco'] += $price['price'] * $item['quantidade'];
				if ($price['time'] > $resposta['servicos'][$price['service']]['prazo']) {
					$resposta['servicos'][$price['service']]['prazo'] = $price['time'];
				}
			}
		}

		if ($mini != count($fr['volumes'])) { // se todos não forem mini, desconsidero o preço do mini. 
			$resposta['servicos']['MINI']['preco'] = 0;
		}

		if (($resposta['servicos']['PAC']['preco'] == 0) && ($total_price > 3000)) {
			unset($resposta['servicos']['PAC']);
		}

		if (($resposta['servicos']['SEDEX']['preco'] == 0) && ($total_price > 10000)) {
			unset($resposta['servicos']['SEDEX']);
		}

		if (($resposta['servicos']['MINI']['preco'] == 0) && ($total_price > 100)){
			unset($resposta['servicos']['MINI']);
		}

		return ($resposta);
	}

    public function lerCep($cep)
	{
        $key_redis = $this->getSellerCenter().":zipcode:$cep";
        if ($this->instance->redis->is_connected) {
            $data_redis = $this->instance->redis->get($key_redis);
            if ($data_redis !== null) {
                $result_redis = json_decode($data_redis, true);
                if (!empty($result_redis)) {
                    return $result_redis;
                }
            }
        }

		$sql = "SELECT * FROM cep WHERE zipcode = $cep";
		$query = $this->readonlydb->query($sql);
		$origem = $query->row_array();
		if (empty($origem)) { // não achei na base local
			if (!$this->cadastraCEP($cep)) { // Procuro na internet
				return false;
			} else {
				$sql = 'SELECT * FROM cep WHERE zipcode = '.$cep;
				$query = $this->readonlydb->query($sql);
				$origem = $query->row_array();
			}
		}

        if (!empty($origem) && $this->instance->redis->is_connected) {
            $this->instance->redis->setex($key_redis, 21600, json_encode($origem, JSON_UNESCAPED_UNICODE));
        }

		return $origem;
	}

	public function verificaEspecial($row_ult, $cep_dest)
    {
		if ($row_ult['int_to'] == 'CAR') {
			if (in_array($row_ult['skubling'], array('D6BDFB51D22AF','92D6D92E2EC98','A3596C88A392F','63B7E861E8360',
				'03DBEE0C31ECF','856E0BEEF04A2','CD31080AF142C','DD773CBE19CCB'))) {
				$cep = $this->lerCep($cep_dest);
				if ($cep) {
					if (($cep['state'] == 'SP') || ($cep['state'] == 'RJ') || ($cep['state'] == 'MG') || ($cep['state'] == 'PR')) {
						return true;
					}
				}
			}
		    return(false);
		}

		if ($row_ult['int_to'] == 'B2W') {
			if (in_array($row_ult['skubling'], array('D9B2D4A7F5833','9D628BA642FD8','1E8FED423A7EB','4DE7022A6D4DC',
				'C993A429A3A2A','45FE26CDA0E64','82310B7D28F7D','86596244FCEE6'))) {
				$cep = $this->lerCep($cep_dest);
				if ($cep) {
					if (($cep['state'] == 'SP') || ($cep['state'] == 'RJ') || ($cep['state'] == 'MG') || ($cep['state'] == 'PR')) {
						return true;
					}
				}
			}
		    return(false);
		}

		if ($row_ult['int_to'] == 'VIA') {
			if (in_array($row_ult['skubling'], array('84DD5A29B2E1B','2608E1D36E5CB','201259B9A58CC','CBC594F74318C',
				'BAA23080A59A0','0D62040EB91AF','2EEB1672C3944','663650E11A3AB'))) {
				$cep = $this->lerCep($cep_dest);
				if ($cep) {
					if (($cep['state'] == 'SP') || ($cep['state'] == 'RJ') || ($cep['state'] == 'MG') || ($cep['state'] == 'PR')) {
						return true;
					}
				}
			}
		    return(false);
		}
		return(false);
	}

	public function verificaTipoVolume($row_ult, $origem, $destino)
    {
	    $whereTipoVolume = 'tipo_volume_codigo =';
	    if ($row_ult['tipo_volume_codigo'] == null) {
            $whereTipoVolume = 'tipo_volume_codigo is';
        }

		$sql = "SELECT * FROM freights_by_tipo_volume WHERE {$whereTipoVolume} ? AND origin_state = ? AND destiny_state = ? ";
		$query = $this->readonlydb->query($sql,array($row_ult['tipo_volume_codigo'],$origem,$destino));
		$resp = $query->result_array();
		return (count($resp));	
	}

    public function calculaTipoVolume($fr, $origem, $destino)
    {
		$capitais = array(
			'Rio Branco/AC',
			'Maceió/AL',
			'Macapá/AP',
			'Manaus/AM', 
			'Salvador/BA',
			'Fortaleza/CE', 
			'Brasília/DF', 
			'Vitória/ES',
			'Goiânia/GO', 
			'São Luís/MA', 
			'Cuiabá/MT',
			'Campo Grande/MS',
			'Belo Horizonte/MG',
			'Belém/PA',
			'João Pessoa/PB', 
			'Curitiba/PR', 
			'Recife/PE',
			'Teresina/PI',
			'Rio de Janeiro/RJ', 
			'Natal/RN',
			'Porto Alegre/RS', 
			'Porto Velho/RO', 
			'Boa Vista/RR',
			'Florianópolis/SC',
			'São Paulo/SP', 
			'Aracaju/SE',
			'Palmas/TO', 
		); 
		
		$cepDestino = $fr['destinatario']['endereco']['cep'];
	    $cepOrigem  = $fr['expedidor']['endereco']['cep'];

        if (empty($origem) || empty($destino)) {
            return array(
                'cep_origem' => $cepOrigem,
                'cep_destino' => $cepDestino,
                'calculo' => 'tipo_volume',
                'servicos' => array()
            );
        }
		
		$resposta = array(
			'cep_origem' => $cepOrigem,
			'cep_destino' => $cepDestino,
		); 	
		
		$capital = in_array($destino['city'].'/'.$destino['state'], $capitais);
		if (!$capital) {
			$capital = $destino['capital_shipping'];
		}

		$servicos = array();
		foreach ($fr['volumes'] as $item) {
            $whereTipoVolume = 'tipo_volume_codigo =';
            if ($item['tipo'] == null) {
                $whereTipoVolume = 'tipo_volume_codigo is';
            }

			$sql = "SELECT * FROM freights_by_tipo_volume WHERE {$whereTipoVolume} ? AND origin_state = ? AND destiny_state = ? AND capital = ? order by price, time";
			$query = $this->readonlydb->query($sql,array($item['tipo'],$origem['state'], $destino['state'], $capital));
			$transportadoras = $query->result_array();
			foreach ($transportadoras as $transportadora) {
				$key = $transportadora['ship_company']."|".$transportadora['service'];
				if (!key_exists($key, $servicos)) {
					$servicos[$key]['empresa'] = $transportadora['ship_company']; 
					$servicos[$key]['servico'] = $transportadora['service']; 
					$servicos[$key]['preco'] = $transportadora['price'] * $item['quantidade']; 
					$servicos[$key]['prazo'] = $transportadora['time'];
					$servicos[$key]['tipo_volume_codigo'] = $item['tipo'];
					$servicos[$key]['origin_state'] = $origem['state'];
					$servicos[$key]['destiny_state'] = $destino['state'];
					$servicos[$key]['capital'] =$capital;
					$servicos[$key]['count'] = 1;
				} else {
					$servicos[$key]['preco'] += $transportadora['price'] * $item['quantidade'];  // somo os preços
					if ($servicos[$key]['prazo'] < $transportadora['time']) {   // pego o maior tempo
						$servicos[$key]['prazo'] = $transportadora['time'];  
					}
					$servicos[$key]['count']++; 
				}
			}
		}
		
		foreach ($servicos as $servicekey => $servico) {
			if ($servico['count'] !=  count($fr['volumes'])) {  // uma transportadora não atende todos
				unset($servicos[$servicekey]); 
			}
		}
		
		$resposta = array(
			'cep_origem' => $cepOrigem,
			'cep_destino' => $cepDestino,
			'calculo' => 'tipo_volume',
			'servicos' => $servicos
		);
		
		return ($resposta);
	}

    public function verificaPorPeso($row_ult, $destino)
    {
		$like = '%,'.$row_ult['tipo_volume_codigo'].',%'; 
		$sql = 'SELECT * FROM freights_by_weight WHERE start_weight < ? AND end_weight >= ? AND tipo_volume_exceptions NOT LIKE ? AND state = ? ';
		$query = $this->readonlydb->query($sql, array($row_ult['peso_bruto'], $row_ult['peso_bruto'], $like, $destino));
		$resp = $query->result_array();
		return (count($resp));	
	}

    public function calculaPorPeso($fr, $origem, $destino)
    {
		$cepDestino = $fr['destinatario']['endereco']['cep'];
	    $cepOrigem = $fr['expedidor']['endereco']['cep'];

        if (empty($origem) || empty($destino)) {
            return array(
                'cep_origem' => $cepOrigem,
                'cep_destino' => $cepDestino,
                'calculo' => 'por_peso',
                'servicos' => array()
            );
        }
		
		$resposta = array(
			'cep_origem' => $cepOrigem,
			'cep_destino' => $cepDestino,
		); 	
		
		$servicos = array();
		foreach ($fr['volumes'] as $item) {
			$like = '%,'.$item['tipo'].',%';
			$sql = 'SELECT * FROM freights_by_weight WHERE start_weight < ? AND end_weight >= ? AND tipo_volume_exceptions NOT LIKE ? AND state = ? order by price, time';
			$query = $this->readonlydb->query($sql, array($item['peso'], $item['peso'], $like, $destino['state']));
			$transportadoras = $query->result_array();
			foreach ($transportadoras as $transportadora) {
				$key = $transportadora['ship_company']."|".$transportadora['service'];
				if (!key_exists($key, $servicos) ) {
					$servicos[$key]['empresa'] = $transportadora['ship_company']; 
					$servicos[$key]['servico'] = $transportadora['service']; 
					$servicos[$key]['preco'] = $transportadora['price'] * $item['quantidade']; 
					$servicos[$key]['prazo'] = $transportadora['time'];
					$servicos[$key]['count'] = 1;
				} else {
					$servicos[$key]['preco'] += $transportadora['price'] * $item['quantidade'];  // somo os preços
					if ($servicos[$key]['prazo'] < $transportadora['time']) {   // pego o maior tempo
						$servicos[$key]['prazo'] = $transportadora['time'];  
					}
					$servicos[$key]['count']++; 
				}
			}
		}
		
		foreach ($servicos as $servicekey => $servico) {
			if ($servico['count'] !=  count($fr['volumes'])) {  // uma transportadora não atende todos
				unset($servicos[$servicekey]); 
			}
		}
		
		$resposta = array(
			'cep_origem' => $cepOrigem,
			'cep_destino' => $cepDestino,
			'calculo' => 'por_peso',
			'servicos' => $servicos
		);
		
		return ($resposta);
	}

    public function calculaCorreiosNovo($fr, $origem, $destino)
    {
    	$cepDestino = $fr['destinatario']['endereco']['cep'];
	    $cepOrigem = $fr['expedidor']['endereco']['cep'];

        if (empty($origem) || empty($destino)) {
            return array(
                'cep_origem' => $cepOrigem,
                'cep_destino' => $cepDestino,
                'calculo' => 'correios',
                'nivel' => '',
                'servicos' => array(
                    'MINI' => array ('empresa'=>'CORREIOS','servico'=>'MINI', 'preco'=>0,'prazo'=>0,),
                    'PAC' => array ('empresa'=>'CORREIOS','servico'=>'PAC','preco'=>0,'prazo'=>0),
                    'SEDEX' => array ('empresa'=>'CORREIOS','servico'=>'SEDEX','preco'=>0,'prazo'=>0,),

                ),
            );
        }
		
		// Vejos se tem exceções a consulta normal
		$excecaoSedex = '';
		$excecaoDivisa = '';
		if ($origem['state'] != $destino['state']) {
			$sql = 'SELECT * FROM cep_exceptions_divisa WHERE 
			origin_start_zipcode <= '.$cepOrigem.' AND origin_end_zipcode >= '.$cepOrigem.'
			AND destiny_start_zipcode <= '.$cepDestino.' AND destiny_end_zipcode >= '.$cepDestino;
			$query = $this->readonlydb->query($sql);
			$excecaoDivisa = $query->row_array();
		} else {
			$sql = 'SELECT * FROM cep_exceptions_local WHERE 
			origin_start_zipcode <= '.$cepOrigem.' AND origin_end_zipcode >= '.$cepOrigem.'
			AND destiny_start_zipcode <= '.$cepDestino.' AND destiny_end_zipcode >= '.$cepDestino;
			$query = $this->readonlydb->query($sql);
			$excecaoSedex = $query->row_array();
		}
		
		if (empty($excecaoSedex)) { 
			if (empty($excecaoDivisa)) {
				// Se não tem exceção, consulto a tabela normal
				$sql = 'SELECT * FROM correios_states WHERE origin = "'.$origem['state'].'" AND destiny = "'.$destino['state'].'"';
				$query = $this->readonlydb->query($sql);
				$states = $query->row_array();
				$nivel = $states['nivel'];
				if (strlen($nivel) == 1) { // Vejo se é capital x capital ou não 
					if (($origem['capital']) && ($destino['capital'])) {
						$nivel = 'N'.$nivel;
					} else {
						$nivel = 'I'.$nivel;
					}
				}
			} else {
				$nivel = $excecaoDivisa['nivel'];
			}
		} else {
			$nivel = $excecaoSedex['nivel'];
		}
		
		$resposta = array(
			'cep_origem' => $cepOrigem,
			'cep_destino' => $cepDestino,
			'calculo' => 'correios',
			'nivel' => $nivel,
			'servicos' => array(
				'MINI' => array ('empresa'=>'CORREIOS','servico'=>'MINI', 'preco'=>0,'prazo'=>0,),
				'PAC' => array ('empresa'=>'CORREIOS','servico'=>'PAC','preco'=>0,'prazo'=>0),
				'SEDEX' => array ('empresa'=>'CORREIOS','servico'=>'SEDEX','preco'=>0,'prazo'=>0,),
				
			),
		);

		$mini = 0;
		$total_price = 0; 
		foreach ($fr['volumes'] as $item) {
			$total_price+= $item['valor'];
			$peso = $item['peso'];
			$peso_cubico = ceil($item['altura'] * 100 * $item['largura'] * 100 *  $item['comprimento'] * 100 / 6000);

			if ($peso_cubico > 5 ) {
				if ($peso_cubico > $item['peso']) {
					$peso = $peso_cubico;
				}
			}

			$peso = ceil($peso * 1000);
			if (($peso <=300) && ($item['valor'] <= 100) && ($item['comprimento'] <= 0.24) && ($item['largura'] <= 0.16) && 
				($item['altura']<=0.02)) {
				$mini++;
			}

			$taxa_adicional =0 ;
			if (($item['altura'] * 100 > 70) || ($item['largura'] * 100 > 70) || ($item['comprimento'] * 100 > 70)) {
				$taxa_adicional = 79 ;
			}

			$sql = 'SELECT * FROM correios_prices WHERE nivel = "'.$nivel.'" AND start_weight <= '.$peso.' 
							AND end_weight >='.$peso.' ORDER BY CAST(price AS DECIMAL(12,2)) ASC ';
			$query = $this->readonlydb->query($sql);
			$prices = $query->result_array();
			foreach($prices as $price) {
				$resposta['servicos'][$price['service']]['preco'] += $price['price'] * $item['quantidade'] + $taxa_adicional* $item['quantidade'];
				if ($price['time'] > $resposta['servicos'][$price['service']]['prazo']) {
					$resposta['servicos'][$price['service']]['prazo'] = $price['time'] ; 
				}
			}	
		
		}

		if ($mini != count($fr['volumes'])) { // se todos não forem mini, desconsidero o preço do mini. 
			$resposta['servicos']['MINI']['preco'] = 0;
		}

		if (($resposta['servicos']['PAC']['preco'] == 0) || ($total_price > 3000)){
			unset($resposta['servicos']['PAC']);
		}

		if (($resposta['servicos']['SEDEX']['preco'] == 0) || ($total_price > 10000)) {
			unset($resposta['servicos']['SEDEX']);
        }

		if (($resposta['servicos']['MINI']['preco'] == 0) || ($total_price > 100)) {
			unset($resposta['servicos']['MINI']);
		}

		// se liberarem, ainda tem que rever a lógica aqui para os 5% da diferença entre PAC e SEDEX
		//if (isset($resposta['servicos']['PAC']['preco']) && isset($resposta['servicos']['SEDEX']['preco'])) { // Se existe PAC e SEDEX
		//	$defirencaFrete = (float)$resposta['servicos']['SEDEX']['preco'] - (float)$resposta['servicos']['PAC']['preco'];
		//	if($defirencaFrete > 0) { // Se a diferença for maior que zero
		//		if( (($defirencaFrete * 100)/ (float)$total_price) <= 5) { // Se for menor que 5% define SEDEX e apaga PAC 
		//			unset($resposta['servicos']['PAC']);
		//		}
		//	}
		//}

		return ($resposta);
	}

    /**
     * Fazer requisição externa por CUrl
     *
     * @param   string   $url        URL da requisição
     * @param   array    $header_opt Headers, geralmente usado para informar o apikey
     * @param   string   $data       Dados em JSON para o body da requisição
     * @param   string   $method     Metódo de envio GET|PUT|POST|PATH
     * @param   int|null $timeOut_ms Tempo em timeout para realizar a cotação em ms
     * @return  array                Retorno um array com httpcode e content
     */
    public function sendRest(string $url, array $header_opt = array(), string $data = '', string $method = 'GET', int $timeOut_ms = null): array
    {
        $curl_handle = curl_init();

        curl_setopt($curl_handle, CURLOPT_URL, $url);

        if ($method == "POST" || $method == "PUT") {
            if ($method == "PUT") {
                curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');
            }

            curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
        }

        $header_opt = array_merge($header_opt, array(
            "Content-Type: application/json"
        ));

        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $header_opt);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, TRUE);

        if ($timeOut_ms) {
            curl_setopt($curl_handle, CURLOPT_TIMEOUT_MS, $timeOut_ms);
            curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT_MS, $timeOut_ms-1); // rick
        }

        $response = curl_exec($curl_handle);
        $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
        $err      = curl_errno( $curl_handle );
        $errmsg   = curl_error( $curl_handle );
        curl_close($curl_handle);

        $header['httpcode'] = $httpcode;
        $header['content']  = $response;
        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;

        return $header;
    }

    /**
     * Identifica qual logística o seller utiliza.
     *
     * freight_seller=true - Seller não utilizará a tabela interna do seller center (Exceto freight_seller_type=2).
     * freight_seller_type - Tipo de logística que o seller utilizará.
     * store_id|id         - Código da loja
     *
     * @param   array $array    Dados para identificação da logistica
     * @return  array           Retorna os dados para identificação da logística seller/sellercenter
     */
	public function getLogisticStore(array $array, bool $returnException = false): array
    {
        $store_id = $array['store_id'] ?? $array['id'];
        $key_redis = $this->getSellerCenter().":setting_logistic:$store_id";
        if ($this->instance->redis->is_connected) {
            $data_redis = $this->instance->redis->get($key_redis);
            if ($data_redis !== null) {
                $data_redis = json_decode($data_redis, true);
                if (!empty($data_redis)) {
                    return $data_redis;
                }
            }
        }

        $logistic = $this->getLogisticIntegration($store_id, $returnException);
        if ($this->instance->redis->is_connected) {
            $this->instance->redis->setex($key_redis, 3600, json_encode($logistic, JSON_UNESCAPED_UNICODE));
        }
        return $logistic;
    }

    /**
     * Recupera qual a logística que o seller utiliza(ou não utiliza)
     * @param   int     $store_id   Codigo da loja (stores.id)
     * @return  array               Response dados da logística
     */
    public function getLogisticIntegration(int $store_id, bool $returnException = false): array
    {
        // Consulta a integração da loja nos microsserviços
        if ($this->ms_shipping->use_ms_shipping) {
            $integration = null;
            try {
                // Carregar as bibliotecas e popula as variáveis.
                $this->instance->load->library("Microservices\\v1\\Logistic\\ShippingCarrier", [], 'ms_shipping_carrier');
                $this->ms_shipping_carrier = $this->instance->ms_shipping_carrier;

                // Define a loja e faz a consulta no microsserviço.
                $this->ms_shipping_carrier->setStore($store_id);
                $integration = $this->ms_shipping_carrier->getConfigures();
            } catch (Exception $exception) {
                if($returnException && $exception->getCode() >= 500){
                    throw new InvalidArgumentException("MS ms_shipping_carrier está fora do ar. code: {$exception->getCode()}. msg: {$exception->getMessage()}");
                }
                try {
                    // Carregar as bibliotecas e popula as variáveis.
                    $this->instance->load->library("Microservices\\v1\\Logistic\\ShippingIntegrator", [], 'ms_shipping_integrator');
                    $this->ms_shipping_integrator = $this->instance->ms_shipping_integrator;

                    // Define a loja e faz a consulta no microsserviço.
                    $this->ms_shipping_integrator->setStore($store_id);
                    $integration = $this->ms_shipping_integrator->getConfigures();
                } catch (Exception $exception) {
                    if($returnException && $exception->getCode() >= 500){
                        throw new InvalidArgumentException("MS ms_shipping_integrator está fora do ar. code: {$exception->getCode()}. msg: {$exception->getMessage()}");
                    }
                }
            }

            $type_contract      = $integration->type_contract ?? 'seller';
            $integration_name   = $integration->integration_name ?? false;

            return [
                'seller'        => $type_contract === 'seller',
                'sellercenter'  => $type_contract === 'sellercenter',
                'type'          => $integration_name,
                'cnpj'          => null,
                'shipping_id'   => null
            ];
        }
        // $integration = $this->instance->model_integration_logistic->getIntegrationSeller($store_id);

        // return $this->db->where('store_id', $storeId)->get('integration_logistic')->row_array();
        //$sql = "SELECT * FROM integration_logistic use index (ix_integration_logistic_store_id_active)  WHERE store_id = ? AND active = 1";
        //$query = $this->readonlydb->query($sql, $store_id);
		//$integration = $query->row_array();
        $integration = $this->getDataIntegrationActiveByStore($store_id);

		// não tem integração. É tabela de frete.
        if (!$integration) {
            $shippingCompany = null;
            $key_redis_shipping_company = $this->getSellerCenter().":shipping_company:$store_id";
            if ($this->instance->redis->is_connected) {
                $data_redis = $this->instance->redis->get($key_redis_shipping_company);
                if ($data_redis !== null) {
                    $shippingCompany = json_decode($data_redis, true);
                }
            }

            if ($shippingCompany === null) {
                $sql = "SELECT sc.* FROM providers_to_seller AS pts JOIN shipping_company AS sc USE INDEX (ix_shipping_company_active) ON sc.id = pts.provider_id WHERE pts.store_id = ? AND sc.active = 1";
                $query = $this->readonlydb->query($sql, $store_id);
                $shippingCompany = $query->row_array();
                if ($this->instance->redis->is_connected) {
                    $this->instance->redis->setex($key_redis_shipping_company, 1800, json_encode($shippingCompany, JSON_UNESCAPED_UNICODE));
                }
            }

            return [
                'seller'        => !$shippingCompany || $shippingCompany['freight_seller'] == 1,
                'sellercenter'  => $shippingCompany && $shippingCompany['freight_seller'] == 0,
                'type'          => false,
                'cnpj'          => $shippingCompany['cnpj'] ?? null,
                'shipping_id'   => null // Transportadora
            ];
        }

        // se usa erp, a logística sempre será do seller
        $useERP = $integration->integration === 'erp';

        if ($integration->integration === 'erp') {
            $queryApiIntegration = $this->readonlydb->get_where('api_integrations', array('store_id' => $store_id));
            $rowApiIntegration = $queryApiIntegration->row_array();

            if ($rowApiIntegration) {
                $integration->integration = $rowApiIntegration['integration'];
            }
        }

        return [
            'seller'        => $useERP || $integration->credentials !== null,
            'sellercenter'  => !$useERP && $integration->credentials === null,
            'type'          => $integration->integration,
            'cnpj'          => null,
            'shipping_id'   => $integration->id_integration // Integradora
        ];
    }

    /**
     * Recupera quais as integrações existentes no ambiente.
     *
     * @return array
     */
    public function getTypesLogisticERP(): array
    {
        return $this->logisticTypes->getTypesLogisticERP();
    }

    /**
     * Retorna as credenciais de acordo com a integradora configurada no seller
     *
     * @param   int     $store_id   Dados da loja para recuperação dos dados da loja
     * @return  array               Retorna um array com as credenciais. Será um array geralmente diferente para cada integração
     */
    public function getCredentialsRequest(int $store_id): array
    {
        $integrations = $this->readonlydb->query($this->getQueryIntegrationsProviders($store_id))->row_array();

        if (!$integrations) {
            return [];
        }

        $erps = $this->getTypesLogisticERP();
        $keyErp = array_search('precode', $erps);
        if($keyErp != false){
            unset($erps[$keyErp]);
        }

        if ($integrations['name'] === 'erp' || in_array($integrations['name'], $erps)) {
            $queryApiIntegration = $this->readonlydb->get_where('api_integrations', array('store_id' => $store_id));
            $rowApiIntegration = $queryApiIntegration->row_array();

            return (array)json_decode($rowApiIntegration['credentials']);
        }

        //verifica se tem token, se não pega do seller center
        if($integrations['token_api'] === null) {
            $integrations = $this->readonlydb->query(
                $this->getQueryIntegrationsProvidersByIntegration(0, $integrations['id_integration'])
            )->row_array();
            if (!$integrations) {
                return [];
            }
        }

        $responseCredentials = (array)json_decode($integrations['token_api']);

        if (in_array($integrations['name'], array('sgpweb', 'correios'))) {
            if ($responseCredentials['type_contract'] === 'new') {
                $available_services = (array)json_decode('{"MINI":"04227","PAC":"03298","SEDEX":"03220"}');
            } else {
                $available_services = (array)json_decode('{"MINI":"00000","PAC":"04669","SEDEX":"04162"}');
            }

            $responseCredentials['available_services'] = $available_services;
        }

        return $responseCredentials;
    }

    /**
     * Formata os dados para realização de cotação.
     * ---------------------------------------------------------
     * $mkt   = [ "platform" => "VTEX", "channel" => "Farm" ]
     * $items = [
     *           ["sku" => "TEST_123", "qty" => 1, "seller" => "MKT0001"],
     *           ["sku" => "TEST_456", "qty" => 2, "seller" => "MKT0002"]
     * ]
     * $zipcode = "88010000"
     * ---------------------------------------------------------
     * A array items, pode e deve ser aumentado de acordo com a
     * necessidade de cada integradora e/ou marketplace
     * ---------------------------------------------------------
     * @param   array       $mkt            Marketplace.
     * @param   array       $items          Array contendo os itens para realização da cotação, contendo os indices 'sku', 'qty' e 'seller'.
     * @param   string|null $zipcode        CEP do cliente final.
     * @param   bool        $checkStock     Ignorar validações de estoque (usava para gerar os preview quando chegar novo pedido).
     * @param   bool        $groupServices  Agrupará todos os serviços em comum dos skus.
     * @return  array                       Retorna os dados necessários para cotação.
     */
    public function formatQuote(array $mkt, array $items, string $zipcode = null, bool $checkStock = true, bool $groupServices = true): array
    {
        $this->initializeMultisellerIfNeeded();
        $this->_time_start_query_sku = 0;
        $this->_time_end_query_sku = 0;
        $this->_time_start_integration = 0;
        $this->_time_end_integration = 0;
        $this->_time_start_integration_instance = 0;
        $this->_time_end_integration_instance = 0;
        $this->_time_start_internal_table = 0;
        $this->_time_end_internal_table = 0;
        $this->_time_start_contingency = 0;
        $this->_time_end_contingency = 0;
        $this->_time_start_promotion = 0;
        $this->_time_end_promotion = 0;
        $this->_time_start_auction = 0;
        $this->_time_end_auction = 0;
        $this->_time_start_redis = 0;
        $this->_time_end_redis = 0;

        if (count($mkt) != 2 || !isset($mkt['platform']) || !isset($mkt['channel'])) {
            return array(
                'success' => false,
                'data' => array(
                    'message' => 'Canal e plataforma incorretos. ' . json_encode($mkt)
                )
            );
        }

        $platform       = $mkt['platform'];
        $channel        = $mkt['channel'];

        // Verifica se vai ser utilizado o redis.
        $enable_redis_quote = false;
        $time_exp_redis = 120; // Tempo de expiração do cache em segundos.
        $setting_enable_redis_quote = $this->readonlydb->get_where('settings', array('name' => 'enable_redis_quote'))->row_array();
        if ($setting_enable_redis_quote && $setting_enable_redis_quote['status'] == 1) {
            try {
                $endpoint_redis_quote   = '127.0.0.1';
                $port_redis_quote       = 6379;

                $setting_endpoint_redis_quote   = $this->readonlydb->get_where('settings', array('name' => 'endpoint_redis_quote'))->row_array();
                $setting_port_redis_quote       = $this->readonlydb->get_where('settings', array('name' => 'port_redis_quote'))->row_array();

                if ($setting_endpoint_redis_quote && $setting_endpoint_redis_quote['status'] == 1) {
                    $endpoint_redis_quote = $setting_endpoint_redis_quote['value'];
                }

                if ($setting_port_redis_quote && $setting_port_redis_quote['status'] == 1) {
                    $port_redis_quote = $setting_port_redis_quote['value'];
                }

                $this->instance->redis->configure(array(
                    'timeout'   => 0.5,
                    'host'      => $endpoint_redis_quote,
                    'port'      => $port_redis_quote,
                    'password'  => ''
                ));

                $enable_redis_quote = true;
                $setting_time_exp_redis = $this->readonlydb->get_where('settings', array('name' => 'time_exp_redis_s'))->row_array();
                if ($setting_time_exp_redis && $setting_time_exp_redis['status'] == 1) {
                    $time_exp_redis = $setting_time_exp_redis['value'];
                }
            } catch (RedisException | Throwable $exception) {}
        }

        $this->setSellerCenter();
        $sellerCenter = $this->getSellerCenter();
        // Consulta cotação no 'redis'.
        $keyRedis = $sellerCenter.':'.$channel.':'.implode(':', array_map(function ($item){ return "{$item['sku']}:{$item['qty']}"; }, $items)).':'.$zipcode;
        if ($enable_redis_quote) {
            $this->_time_start_redis = microtime(true) * 1000;
            try {
                $data_redis = $this->instance->redis->get($keyRedis);
                $this->_time_end_redis = microtime(true) * 1000;
                if ($data_redis !== null) {
                    return json_decode($data_redis, true);
                }
            } catch (RedisException $exception) {}
            $this->_time_end_redis = $this->_time_start_redis = 0;
        }

        $this->setFieldSKUQuote($platform);

        // Se não passar zipcode, não precisa validar.
        // Isso é feito, pois a vtex vem fazer simulação
        // para saber o preço e estoque atual do sku.
        $dataRecipient = array();
        if ($zipcode !== null) {
            $zipcode = str_pad($zipcode, 8, "0", STR_PAD_LEFT);
            $dataRecipient = $this->lerCep($zipcode) ?: array();
        }
        $this->_time_start_query_sku = microtime(true) * 1000;

        $columnMkt      = $this->getColumnsMarketplace($platform);
        $table          = $columnMkt['table'];
        $columnTotalQty = $columnMkt['qty'];

        try {
            $dataQuoteValid = $this->validItemsQuote($items, $mkt, $table, $columnTotalQty, $checkStock, $zipcode, $dataRecipient);
            
            $this->validationResult = $dataQuoteValid;

            $arrDataAd          = $dataQuoteValid['arrDataAd'];
            $dataSkus           = $dataQuoteValid['dataSkus'];
            $totalPrice         = $dataQuoteValid['totalPrice'];
            $cross_docking      = $dataQuoteValid['cross_docking'];
            $quoteResponse      = $dataQuoteValid['quoteResponse'];
            $zipCodeSeller      = $dataQuoteValid['zipCodeSeller'];
            $dataQuote          = $dataQuoteValid['dataQuote'];
            $logistic           = $dataQuoteValid['logistic'];
            $storeId            = $dataQuoteValid['storeId'];
            $store_integration  = $dataQuoteValid['store_integration'];
        } catch (Exception $exception) {
            $quoteResponse = array(
                'success' => false,
                'data' => array(
                    'message' => $exception->getMessage()
                )
            );
            try {
                if ($zipcode !== null && $enable_redis_quote) {
                    $this->instance->redis->setex($keyRedis, $time_exp_redis, json_encode($quoteResponse, JSON_UNESCAPED_UNICODE));
                }
            } catch (RedisException $exception) {}
            return $quoteResponse;
        }

        $this->_time_end_query_sku = microtime(true) * 1000;
        /**
         * LOCALIZAR o início do método formatQuote() após as validações iniciais
         * ADICIONAR esta seção APÓS a configuração do Redis:
         */

        // === ORQUESTRADOR MULTISELLER COM FEATURE FLAGS ===

        // Verificar se multiseller está habilitado via feature feature-OEP-1921-multiseller-quote
        $multisellerEnabled = \App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1921-multiseller-freight-results');

        if ($multisellerEnabled && $this->enable_multiseller_operation) {
            // Executar análise multiseller otimizada
            $multisellerAnalysis = $this->analyzeMultisellerRequestOptimized($mkt, $items, $dataQuoteValid, $zipcode);
            
            if ($multisellerAnalysis['is_multiseller'] && $multisellerAnalysis['total_sellers'] > 1) {
                get_instance()->log_data('batch', __CLASS__ . '/' . __FUNCTION__, 
                    "Executando cotação multiseller - " . $multisellerAnalysis['total_sellers'] . " sellers detectados", "I");
                
                // Verificar feature flag para cotação paralela feature-OEP-1921-parallel-quotefeature-OEP-1921-parallel-quote
                if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1921-multiseller-freight-results')) {
                    try {
                        $parallelResult = $this->executeParallelMultisellerQuote(
                            $mkt, 
                            $multisellerAnalysis['seller_groups'], 
                            $zipcode, 
                            $checkStock, 
                            $groupServices
                        );
                        
                        if ($parallelResult['success']) {
                            return $parallelResult;
                        }
                        
                        // Fallback para sequencial se paralelo falhar
                       get_instance()->log_data('batch', __CLASS__ . '/' . __FUNCTION__, 
                            "Cotação paralela falhou - tentando sequencial", "W");
                        
                    } catch (Exception $e) {
                        get_instance()->log_data('batch', __CLASS__ . '/' . __FUNCTION__, 
                            "Erro na cotação paralela: " . $e->getMessage() . " - tentando sequencial", "E");
                    }
                }
                
                // Executar cotação sequencial
                try {
                    $sequentialResult = $this->executeSequentialMultisellerQuote(
                        $mkt, 
                        $multisellerAnalysis['seller_groups'], 
                        $zipcode, 
                        $checkStock, 
                        $groupServices
                    );
                    
                    if ($sequentialResult['success']) {
                        return $sequentialResult;
                    }
                    
                } catch (Exception $e) {
                    get_instance()->log_data('batch', __CLASS__ . '/' . __FUNCTION__, 
                        "Erro na cotação sequencial: " . $e->getMessage() . " - usando fallback tradicional", "E");
                }
            }
        }

        // Fallback: executar cotação tradicional
        get_instance()->log_data('batch', __CLASS__ . '/' . __FUNCTION__, 
            "Executando cotação tradicional (fallback)", "I");

        return $this->executeTraditionalQuote($mkt, $items, $zipcode, $checkStock, $groupServices);

        // === FIM DA LÓGICA MULTISELLER ===
        // Continuar com a validação original
        if (!array_key_exists('data', $quoteResponse)) {
            $quoteResponse = array(
                'success'   => true,
                'data'      => array()
            );
        }

        if ($platform === 'VIA') {
            $cross_docking = 0;
        }

        // Ocorreu algum problema de validação no produto.
        if (!$quoteResponse['success']) {
            if (!count($dataSkus)) {
                try {
                    if ($zipcode !== null && $enable_redis_quote) {
                        $this->instance->redis->setex($keyRedis, $time_exp_redis, json_encode($quoteResponse, JSON_UNESCAPED_UNICODE));
                    }
                } catch (RedisException $exception) {}
                return $quoteResponse;
            }

            $quoteResponse['data']['logistic']          = $logistic ?? null;
            $quoteResponse['data']['store_integration'] = $store_integration ?? null;
            $quoteResponse['data']['skus']              = $dataSkus;
            $quoteResponse['data']['totalPrice']        = $totalPrice;
            $quoteResponse['data']['crossDocking']      = $cross_docking;

            try {
                if ($zipcode !== null && $enable_redis_quote) {
                    // Não salvar no redis quando der erro
                    //$this->instance->redis->setex($keyRedis, $time_exp_redis, json_encode($quoteResponse, JSON_UNESCAPED_UNICODE));
                }
            } catch (RedisException $exception) {}
            return $quoteResponse;

        }

        // Adição do tempo de crossdocking.
        $dataQuote['crossDocking']  = $cross_docking;
        $dataQuote['zipcodeSender'] = $zipCodeSeller;
        $dataQuote['dataInternal']  = $arrDataAd;

        if ($zipcode !== null && empty($quoteResponse['data']['services'])) {
            $store_id = $storeId;

            // Transportadoras das tabelas simplificadas e carregadas a partir dum arquivo CSV.
            $integration = $this->getIntegrationLogistic($store_id);
            $dataStore = $dataQuote['dataInternal'][$dataQuote['items'][0]['sku']];

            try {
                if ($integration) {
                    try {
                        $this->_time_start_integration_instance = microtime(true) * 1000;
                        $this->instanceLogistic($integration, $dataStore['store_id'], $dataQuote, $logistic['seller']);
                        $this->_time_end_integration_instance = microtime(true) * 1000;

                        $this->_time_start_integration = microtime(true) * 1000;
                        $quoteResponse = $this->logistic->getQuote($dataQuote, true, $this->enable_multiseller_operation);
                        $this->_time_end_integration = microtime(true) * 1000;
                    } catch (InvalidArgumentException $exception) {
                        if (empty($this->_time_end_integration_instance)) {
                            $this->_time_end_integration_instance = $this->_time_start_integration_instance ? microtime(true) * 1000 : 0;
                        }
                        $this->_time_end_integration = $this->_time_start_integration ? microtime(true) * 1000 : 0;
                        $quoteResponse = array(
                            'success' => false,
                            'data' => array(
                                'message' => $exception->getMessage()
                            )
                        );
                    }

                    // Não tem nenhuma integração - Pegará as tabelas de contingência.
                    if (!isset($quoteResponse['data']['services']) || !count($quoteResponse['data']['services'])) {
                        $oldQuoteResponse = $quoteResponse;
                        throw new InvalidArgumentException('TableContingency');

                    }
                } else {
                    throw new InvalidArgumentException('TableInternal');
                }
            } catch (InvalidArgumentException $exception) {
                $error_message = $exception->getMessage();
                if ($error_message == 'TableContingency') {
                    $this->_time_start_contingency = microtime(true) * 1000;
                } elseif ($error_message == 'TableInternal') {
                    $this->_time_start_internal_table = microtime(true) * 1000;
                }

                try {
                    $this->instanceLogistic('TableInternal', $storeId, $dataQuote, $logistic['seller']);
                    $quoteResponse = $this->logistic->getQuote($dataQuote, false, $this->enable_multiseller_operation);
                } catch (InvalidArgumentException $exception) {
                    $quoteResponse = array(
                        'success' => false,
                        'data' => array(
                            'message' => $exception->getMessage()
                        )
                    );
                }

                if ($error_message == 'TableContingency') {
                    $this->_time_end_contingency = microtime(true) * 1000;

                    if (
                        isset($oldQuoteResponse) &&
                        !$oldQuoteResponse['success'] &&
                        (
                            !isset($quoteResponse['data']['services']) ||
                            !count($quoteResponse['data']['services'])
                        )
                    ) {
                        $quoteResponse = $oldQuoteResponse;
                    }
                } elseif ($error_message == 'TableInternal') {
                    $this->_time_end_internal_table = microtime(true) * 1000;
                }
            }

            // Aplica promoções
            $this->_time_start_promotion = microtime(true) * 1000;
            if (isset($quoteResponse['data']['services']) && count($quoteResponse['data']['services'])) {
                $state = null;
                $dataRecipientState = $dataRecipient['state'] ?? '';
                $key_redis_state = "$sellerCenter:state:$dataRecipientState";
                if ($enable_redis_quote) {
                    $data_redis = $this->instance->redis->get($key_redis_state);
                    if ($data_redis !== null) {
                        $state = json_decode($data_redis);
                    }
                }

                if ($state === null) {
                    $state = $this->readonlydb->where('Uf', $dataRecipient['state'] ?? '')->get('states')->row_object();
                    if ($enable_redis_quote) {
                        $this->instance->redis->setex($key_redis_state, 21600, json_encode($state, JSON_UNESCAPED_UNICODE));
                    }
                }

                $quoteResponse['data']['services'] = $this->logistic->getPromotion($quoteResponse['data']['services'], !empty($state) ? $state->CodigoUf : '');
            }
            $this->_time_end_promotion = microtime(true) * 1000;

            // Se não for VTEX(erp) e permite agrupar, irá agrupar os serviços para retornar um valor por serviço.
            if (
                $groupServices &&
                isset($quoteResponse['data']['services']) &&
                count($quoteResponse['data']['services'])
            ) {
                $serviceResponseVtex = array();
                foreach ($quoteResponse['data']['services'] as $service) {
                    if (isset($serviceResponseVtex[$service['method']])) {
                        $serviceResponseVtex[$service['method']]['value'] += $service['value'];
                        $serviceResponseVtex[$service['method']]['counter'] += 1;

                        if ($service['deadline'] > $serviceResponseVtex[$service['method']]['deadline']) {
                            $serviceResponseVtex[$service['method']]['deadline'] = $service['deadline'];
                        }
                    } else {
                        $serviceResponseVtex[$service['method']] = array(
                            'prd_id'        => $service['prd_id'] ?? null,
                            'skumkt'        => $service['skumkt'] ?? null,
                            'quote_id'      => $service['quote_id'],
                            'method_id'     => $service['method_id'],
                            'value'         => $service['value'],
                            'deadline'      => $service['deadline'],
                            'method'        => $service['method'],
                            'provider'      => $service['provider'],
                            "provider_cnpj" => $service['provider_cnpj'] ?? null,
                            "shipping_id"   => $service['shipping_id'] ?? null,
                            'counter'       => 1
                        );
                    }
                }

                $services = array();
                foreach ($serviceResponseVtex as $service) {
                    unset($serviceResponseVtex[$service['method']]['counter']);
                    $services[] = $serviceResponseVtex[$service['method']];
                }

                $quoteResponse['data']['services'] = $services;
            }
        }

        if (!array_key_exists('data', $quoteResponse)) {
            if ($zipcode !== null) {
                $quoteResponse = array(
                    'success' => false,
                    'data' => array(
                        'message' => 'Não foi possível consultar os serviços disponíveis.'
                    )
                );
            } else {
                $quoteResponse = array(
                    'success'   => true,
                    'data'      => array()
                );
            }
        }

        if (!$quoteResponse['success'] && is_string($quoteResponse['data'])) {
            $messageError = $quoteResponse['data'];
            $quoteResponse['data'] = array();
            $quoteResponse['data']['message'] = $messageError;
        }

        $quoteResponse['data']['store_integration'] = $store_integration;
        $quoteResponse['data']['logistic']          = $logistic;
        $quoteResponse['data']['skus']              = $dataSkus;
        $quoteResponse['data']['totalPrice']        = $totalPrice;
        $quoteResponse['data']['crossDocking']      = $cross_docking;
        $quoteResponse['data']['marketplace']       = $channel;
        if ($this->logistic) {
            $quoteResponse['data']['pickup_points'] = $this->logistic->getPickupPoints();
        }

        if (!$quoteResponse['success']) {
            try {
                if ($zipcode !== null && $enable_redis_quote) {
                    $this->instance->redis->setex($keyRedis, $time_exp_redis, json_encode($quoteResponse, JSON_UNESCAPED_UNICODE));
                }
            } catch (RedisException $exception) {}
            return $quoteResponse;
        }

        // Se existe algum serviço, então as regras de leilão de frete são aplicadas.
        if (isset($quoteResponse['data']['services']) && count($quoteResponse['data']['services'])) {
            $log_name = __CLASS__.'/'.__FUNCTION__;

            $quoteResponse['data']['services'] = $this->logistic->setAdditionalDeadline($this->instance->redis, $quoteResponse['data']['services']);

            $rule = null;
            $key_redis_rules = "$sellerCenter:auction_rule:$channel";
            if ($enable_redis_quote) {
                $data_redis = $this->instance->redis->get($key_redis_rules);
                if ($data_redis !== null) {
                    $rule = json_decode($data_redis);
                }
            }

            if ($rule === null) {
                if ($this->ms_shipping->use_ms_shipping) {
                    //if (false) {
                    $rule = (object)$this->ms_shipping->getRuleAuction($channel);
                } else {
                    $this->readonlydb->select('id');
                    $this->readonlydb->where(['store_id' => 0, 'INT_TO' => $channel]);
                    $subQuery = $this->readonlydb->get_compiled_select('integrations', true);
                    $rule = $this->readonlydb->where("mkt_id = ($subQuery)")->get('rules_seller_conditions')->row_object();
                }

                if ($enable_redis_quote) {
                    $this->instance->redis->setex($key_redis_rules, 21600, json_encode($rule, JSON_UNESCAPED_UNICODE));
                }
            }

            $this->_time_start_auction = microtime(true) * 1000;
            $quoteResponse = $this->logistic->shippingAuctionRules(
                $rule,
                $quoteResponse,
                $platform,
                $groupServices
            );
            $this->_time_end_auction = microtime(true) * 1000;

            // Precificação do frete.
            $quote_data = $quoteResponse;
            $quote_data['table_name'] = $table;
            $this->_time_start_price_rules = microtime(true) * 1000;
            $quoteResponse = $this->logistic->applyShippingPricingRules($quote_data, $this->instance->redis);
            $this->_time_end_price_rules = microtime(true) * 1000;

            if (isset($quoteResponse["shipping"])) {
                get_instance()->log_data('api', $log_name, json_encode($quoteResponse["shipping"]), 'I');
            } else {
                get_instance()->log_data('api', $log_name, '{"apply":{"success":"No shipping pricing rule applied."}}', 'I');
            }

            if (!empty($this->logistic) && $this->logistic->has_multiseller) {
                $this->loadFreightStandardizationConfig();
                if ($this->multiseller_freight_results) {
                    $this->setMarketplaceReplaceShippingMethod($quoteResponse);
                }
            }
        }

        $store = $this->instance->db->select('additional_operational_deadline')->get_where('stores', array('id' => $storeId))->row_array();

        if(isset($quoteResponse['data']['services'])){
            $this->setAdditionalOperationalDeadlineSla($quoteResponse, $store['additional_operational_deadline']);
        }

        if (isset($store_id)) {
            $quoteResponse['store_id'] = $store_id;
        }

        try {
            if ($zipcode !== null && $enable_redis_quote) {
                $this->instance->redis->setex($keyRedis, $time_exp_redis, json_encode($quoteResponse, JSON_UNESCAPED_UNICODE));
            }
        } catch (RedisException $exception) {}

        return $quoteResponse;
    }

    private function setMarketplaceReplaceShippingMethod(&$quoteResponse, &$orignal_services = array())
    {
        $this->loadFreightStandardizationConfig();

        if (!$this->multiseller_freight_results || empty($this->marketplace_shipping_standardization_config)) {
            return;
        }

        $lowest_price    = $this->marketplace_shipping_standardization_config['lowest_price'];
        $lowest_deadline = $this->marketplace_shipping_standardization_config['lowest_deadline'];

        $sku_services = array();

        if (empty($quoteResponse['data']['services'])) {
            return;
        }

        foreach ($quoteResponse['data']['services'] as $service) {
            if (!array_key_exists($service['skumkt'], $sku_services)) {
                $sku_services[$service['skumkt']] = array();
            }

            $sku_services[$service['skumkt']][] = $service;
        }

        foreach ($sku_services as $skumkt => $new_sku_services) {
            $price    = null;
            $deadline = null;

            foreach ($new_sku_services as $service_key => $service) {
                // Por enquanto, considerar somente os dois primeiros serviços do produto.
                if ($service_key >= 2) {
                    unset($sku_services[$skumkt][$service_key]);
                    continue;
                }

                $orignal_services[$skumkt][$service_key] = array(
                    'method' => $service['method'],
                    'value' => $service['value'],
                    'deadline' => $service['deadline'],
                );
                // Primeiro serviço do sku
                if ($service_key == 0) {
                    $price = $service['value'];
                    $deadline = $service['deadline'];

                    $sku_services[$skumkt][$service_key]['method'] = count($new_sku_services) == 1 ? $lowest_price : $lowest_deadline;
                    $sku_services[$skumkt][$service_key]['provider'] = count($new_sku_services) == 1 ? $lowest_price : $lowest_deadline;
                    continue;
                }

                // menor prazo
                if ($service['deadline'] < $deadline) {
                    $sku_services[$skumkt][$service_key]['method'] = $lowest_deadline;
                    $sku_services[$skumkt][$service_key]['provider'] = $lowest_deadline;
                    $sku_services[$skumkt][0]['method'] = $lowest_price;
                    $sku_services[$skumkt][0]['provider'] = $lowest_price;
                }
                // menor preço e menor prazo ou menor preço
                else if ($service['value'] < $price) {
                    $sku_services[$skumkt][$service_key]['method'] = $lowest_price;
                    $sku_services[$skumkt][$service_key]['provider'] = $lowest_price;
                    $sku_services[$skumkt][0]['method'] = $lowest_deadline;
                    $sku_services[$skumkt][0]['provider'] = $lowest_deadline;
                }
                else {
                    $sku_services[$skumkt][$service_key]['method'] = $lowest_price;
                    $sku_services[$skumkt][$service_key]['provider'] = $lowest_price;
                }
            }
        }

        $new_services = array();

        foreach ($sku_services as $services) {
            $new_services = array_merge($new_services, $services);
        }

        $quoteResponse['data']['services'] = $new_services;
    }

    private function setAdditionalOperationalDeadlineSla(array &$quoteResponse, int $additional_operational_deadline)
    {
        if (empty($additional_operational_deadline)) {
            return;
        }

        foreach ($quoteResponse['data']['services'] as $key_service => $services) {
            $quoteResponse['data']['services'][$key_service]['deadline'] += $additional_operational_deadline;
        }
    }

    /**
     * Recupera o filtro para consultar a tabela de cotação (*_ult_envio)
     *
     * @param   array   $mkt    Dados de plataforma e canal ('platform', 'channel')
     * @param   array   $iten   Dados do iten recebido na cotação
     * @param   string  $sku    SKU do item recebido na cotação, as vezes retiramos o "traço"( - ) para consultar se for variação antiga 'AAA-0', 'AAA-1', ficaria 'AAA'
     * @return  array
     */
    public function getFilterTableUltEnvio(array $mkt, array $iten, string $sku): array
    {
        $platform = $mkt['platform'];
        $channel  = $mkt['channel'];

        $filterUltEnvio = array('int_to' => $channel);

        $filterUltEnvio[$this->fieldSKUQuote] = $sku;
        if ($platform == 'VTEX' || isset($iten['seller'])) {
            $filterUltEnvio['seller_id'] = $iten['seller'];
        }

        return $filterUltEnvio;
    }

    /**
     * Define qual o valor do campo para fazer a busca do sku (foi criado muitas várias colunas di)
     *
     * @param string $platform
     */
    public function setFieldSKUQuote(string $platform)
    {
        $platform = strtolower($platform);

        switch ($platform) {
            case 'b2w':
            case 'via':
            case 'car':
                $this->fieldSKUQuote = 'skubling';
                break;
            case 'ml':
            case 'vtex':
                $this->fieldSKUQuote = 'skumkt';
                break;
            default:
				$this->fieldSKUQuote = 'skulocal';
                break;
        }
    }

    private function getQueryIntegrationsProviders($store_id): string
    {
        return "SELECT integration_logistic.integration as name, integration_logistic.id_integration, " .
        "integration_logistic.id as id, integration_logistic.credentials token_api FROM integration_logistic " .
        "where integration_logistic.store_id = " . $store_id . " and integration_logistic.active = 1;";
    }

    private function getQueryIntegrationsProvidersByIntegration($store_id, $integration): string
    {
        return "SELECT integration_logistic.integration AS name, 
                integration_logistic.id AS id, 
                integration_logistic.credentials token_api 
            FROM integration_logistic 
            WHERE integration_logistic.store_id = $store_id AND 
                integration_logistic.id_integration = $integration AND 
                integration_logistic.active = 1;";
    }

    public function getQueryTableProvidersUltEnvio(array $mkt): string
    {
        $platform = $mkt['platform'];

        $query = "SELECT 
                   {$this->fieldSKUQuote}, 
                   sku as skuseller, 
                   tipo_volume_codigo, 
                   store_id, 
                   prd_id, 
                   price, 
                   zipcode, 
                   crossdocking,
                   freight_seller,
                   freight_seller_end_point,
                   freight_seller_type,
                   variant,";

        switch ($platform) {
            case 'B2W':
            case 'VIA':
            case 'CAR':
            case 'ML':
                $query .= "qty_atual, 
                           largura, 
                           altura, 
                           profundidade, 
                           peso_bruto, 
                           list_price 
                    FROM bling_ult_envio WHERE";
                break;
            case 'VTEX':
                $query .= "qty_atual, 
                           largura, 
                           altura, 
                           profundidade, 
                           peso_bruto, 
                           list_price, 
                           seller_id 
                    FROM vtex_ult_envio WHERE seller_id = ? AND";
                break;
			case 'MAD':
				$query .= "qty as qty_atual,
				           width as largura,
				           height as altura, 
				           length as profundidade, 
				           gross_weight as peso_bruto, 
				           list_price
				    FROM mad_last_post WHERE";
                break;
			case 'VS':
				$query .= "qty as qty_atual, 
                           width as largura, 
                           height as altura, 
                           length as profundidade, 
                           gross_weight as peso_bruto, 
                           list_price
                    FROM vs_last_post WHERE";
                break;
			case 'NM':
				$query .= "qty as qty_atual, 
				           largura, 
				           altura, 
				           profundidade, 
				           peso_bruto, 
				           price as list_price 
				    FROM integration_last_post WHERE";
                break;
            case 'GPA':
                $query .= "qty as qty_atual, 
                           width as largura, 
                           height as altura, 
                           length as profundidade, 
                           gross_weight as peso_bruto, 
                           list_price
                    FROM gpa_last_post WHERE";
                break;
            case 'OCC':
                $query .= "qty as qty_atual, 
                            width as largura, 
                            height as altura, 
                            length as profundidade, 
                            gross_weight as peso_bruto, 
                            list_price
                    FROM occ_last_post WHERE";
                    break;    
            default:
                $query .= "qty as qty_atual, 
                           width as largura, 
                           height as altura, 
                           length as profundidade, 
                           gross_weight as peso_bruto, 
                           list_price
				    FROM sellercenter_last_post WHERE";
                break;
        }

        $query .= " {$this->fieldSKUQuote} IN ? AND int_to = ?";

        return $query;
    }

    /**
     * Define informações prevista de envio
     *
     * orders.ship_company_preview  Transportadora
     * orders.ship_service_preview  Método de envio
     * orders.ship_time_preview     Tempo prometido de entrega
     *
     * @param   int  $orderId Código do pedido ( orders.id )
     * @return  bool          Retorna status da atualização
     */
    public function updateShipCompanyPreview(int $orderId): bool
    {
        $log_name = __CLASS__.'/'.__FUNCTION__;

        $order = $this->instance->db->get_where('orders', array('id' => $orderId))->row_array();
        if (!$order) {
            get_instance()->log_data('batch', $log_name, "Não atualizou os dados de envio do pedido:$orderId.\n\nNão encontrou o pedido.", 'E');
            return false;
        }

        $items = $this->instance->db->get_where('orders_item', array('order_id' => $orderId))->result_array();
        if (!$items) {
            get_instance()->log_data('batch', $log_name, "Não atualizou os dados de envio do pedido:$orderId.\n\nNão encontrou os itens do pedido.", 'E');
            return false;
        }

        $store = $this->instance->db->get_where('stores', array('id' => $order['store_id']))->row_array();
        if (!$store) {
            get_instance()->log_data('batch', $log_name, "Não atualizou os dados de envio do pedido:$orderId.\n\nNão encontrou a loja ({$order['store_id']}).", 'E');
            return false;
        }

        $logistic = $this->getLogisticStore(array(
            'freight_seller' 		=> $store['freight_seller'],
            'freight_seller_type' 	=> $store['freight_seller_type'],
            'store_id'				=> $store['id']
        ));

        $this->instance->model_orders->updateByOrigin(
            $orderId,
            array(
                'freight_seller'        => $logistic['seller'],
                'manual_freight'        => $logistic['type'] === false,
                'integration_logistic'  => $logistic['sellercenter'] ? ($logistic['type'] ?: null) : null
            )
        );

        // Por padrão os marketplaces B2W, CAR, VIA e ML deverão sempre responder o menor preço nas cotações.
        // Até o módulo frete ficar pronto, então nesses casos devemos usar a mesma ideia para gravar as previsões.
        switch ($order['origin']) {
            case 'B2W':
            case 'CAR':
            case 'VIA':
            case 'MAD':
			case 'NM':
			case 'VS':
            case 'ML':
            case 'GPA':
                $platform = $order['origin'];
                break;
			default:
				if ($order['origin'] != '') {
					$platform = $order['origin'];
                	break;
				}
				else {
					get_instance()->log_data('batch', $log_name, "Não atualizou os dados de envio do pedido:$orderId.\n\nO marketplace({$order['origin']}) informado não está mapeado para usar esse recurso.", 'E');
                	return false;
				}
        }

        $mkt = array("platform" => $platform, "channel" => $order['origin']);
        $marketplace = $order['origin'];

        // cria array com itens
        $arrItems = array();
        $skuMkt = array();

        // consulta seller_id
        $table_get_seller_id = 'vtex_ult_envio';
        if ($order['origin'] == 'RD') {
            $table_get_seller_id = 'rd_last_post';
        }
        $prd_id = $items[0]['product_id'] ?? '';
        $result_seller_id = $this->instance->db
            ->select('seller_id')
            ->where(array('int_to' => $order['origin'], 'prd_id' => $prd_id))
            ->get($table_get_seller_id)
            ->row_array();

        foreach ($items as $item) {
            $arrItem = array(
                'qty'    => $item['qty'],
                'sku'    => $item['skumkt']
            );

            if ($result_seller_id) {
                $arrItem['seller'] = $result_seller_id['seller_id'];
            }

            $arrItems[] = $arrItem;
            $skuMkt[]   = $item['skumkt'];
        }

        if ($this->ms_shipping->use_ms_shipping) {
            $quoteResponse = $this->ms_shipping->getFormatQuoteAuction($marketplace, $arrItems, $order['customer_address_zip']);
        }
        else{
            $quoteResponse = $this->formatQuote($mkt, $arrItems, $order['customer_address_zip'], false);
        }

        // verificar se deu algum problema na cotação
        if (!$quoteResponse['success']) {
            get_instance()->log_data('batch', $log_name, "Não atualizou os dados de envio do pedido:$orderId.\n\nNão foi encontrado serviços na cotação para recuperar os dados.\n\nquoteResponse=".json_encode($quoteResponse), 'E');
            return false;
        }

        // se não existir serviços
        if (!isset($quoteResponse['data']['services']) || !count($quoteResponse['data']['services'])) {
            return false;
        }

        // recupera a cotação mais em conta
        $quote = $this->getLowerShipping($quoteResponse['data']['services'], true);

        $oferta = $order['ship_service_preview'];
        if ($logistic['type'] === 'freterapido') {
            $expOferta = explode("_", $order['ship_service_preview']);
            $oferta = end($expOferta);
        }
        //Verificar pelo número da cotação, valor pago e sla.
        $selectedQuote = NULL;
        foreach($quoteResponse['data']['services'] as $service){
            if ($logistic['type'] !== 'freterapido' && strtolower($oferta) == strtolower($service['method'])) {
                $selectedQuote = $service;
                break;
            }
            elseif ($logistic['type'] === 'freterapido' && $oferta == $service['quote_id']) {
                $selectedQuote = $service;
                break;
            }
        }

        if ($selectedQuote === null && count($quoteResponse['data']['services']) === 1) {
            $selectedQuote = $quoteResponse['data']['services'][0];
        }

        $service_method = $selectedQuote['method'] ?? $order['ship_service_preview'];
        if ($logistic['type'] === 'freterapido') {
            $service_method = explode("_", $service_method);
            $service_method = $service_method[0];
        }

        // recupera o primeiro serviço já que sempre retornará apenas uma
        $dataFr = array(
            'cost'           => $selectedQuote['value'] ?? $order['total_ship'],
            'retorno'        => json_encode($quoteResponse),
            'oferta'         => $selectedQuote['quote_id'] ?? 0,
            'token_oferta'   => $selectedQuote['token_oferta'] ?? '{}',
            'prazo_entrega'  => $selectedQuote['deadline'] ?? $order['ship_time_preview'],
            'service_method' => $service_method,
            'provider'       => $selectedQuote['provider'] ?? $order['ship_company_preview'],
            'provider_cnpj'  => $selectedQuote['provider_cnpj'] ?? '',
            'order_id'       => $orderId,
            'marketplace'    => $order['origin'],
            'zip'            => $order['customer_address_zip'],
            'sku'            => json_encode($skuMkt)
        );

        $this->instance->model_quotes_ship->create($dataFr);

        $update_order = array();
        // Atualiza a transportadora.
        if (empty($order['ship_company_preview'])) {
            $update_order['ship_company_preview'] = $selectedQuote['provider'] ?? $order['ship_company_preview'];
        }
        // Atualiza o método de envio.
        if (empty($order['ship_service_preview'])) {
            $update_order['ship_service_preview'] = $service_method ?? $order['ship_service_preview'];
        }
        // Atualiza o tempo de entrega.
        if (empty($order['ship_time_preview'])) {
            $update_order['ship_time_preview'] = $selectedQuote['deadline'] ?? $order['ship_time_preview'];
        }

        // Salva se o frete será manual ou por uma integradora.
        $ship_company_preview = $update_order['ship_company_preview'] ?? $order['ship_company_preview'];
        $update_order['manual_freight'] = $logistic['type'] === false;

        $this->instance->model_orders->updateByOrigin($orderId, $update_order);

        return true;
    }

    public function getShipCompanyPreviewToCreateOrder(int $store_id, string $int_to, array $items, string $zipcode, string $ship_service_preview)
    {
        $this->initializeMultisellerIfNeeded();
        $store = $this->instance->db->get_where('stores', array('id' => $store_id))->row_array();
        if (!$store) {
            return [];
        }


        $logistic = $this->getLogisticStore(array(
            'freight_seller' 		=> $store['freight_seller'],
            'freight_seller_type' 	=> $store['freight_seller_type'],
            'store_id'				=> $store['id']
        ));

        // Por padrão os marketplaces B2W, CAR, VIA e ML deverão sempre responder o menor preço nas cotações.
        // Até o módulo frete ficar pronto, então nesses casos devemos usar a mesma ideia para gravar as previsões.
        switch ($int_to) {
            case 'B2W':
            case 'CAR':
            case 'VIA':
            case 'MAD':
            case 'NM':
            case 'VS':
            case 'ML':
            case 'GPA':
                $platform = $int_to;
                break;
            default:
                if ($int_to != '') {
                    $platform = $int_to;
                    break;
                }
                else {
                    return [];
                }
        }

        $mkt = array("platform" => $platform, "channel" => $int_to);
        $marketplace = $int_to;

        // cria array com itens
        $arrItems = array();

        // consulta seller_id
        $table_get_seller_id = 'vtex_ult_envio';
        if ($int_to == 'RD') {
            $table_get_seller_id = 'rd_last_post';
        }
        $prd_id = $items[0]['product_id'] ?? '';
        $result_seller_id = $this->instance->db
            ->select('seller_id')
            ->where(array('int_to' => $int_to, 'prd_id' => $prd_id))
            ->get($table_get_seller_id)
            ->row_array();

        foreach ($items as $item) {
            $arrItem = array(
                'qty'    => $item['qty'],
                'sku'    => $item['skumkt']
            );

            if ($result_seller_id) {
                $arrItem['seller'] = $result_seller_id['seller_id'];
            }

            $arrItems[] = $arrItem;
        }

        if ($this->ms_shipping->use_ms_shipping) {
            $quoteResponse = $this->ms_shipping->getFormatQuoteAuction($marketplace, $arrItems, $zipcode);
        }
        else{
            $quoteResponse = $this->formatQuote($mkt, $arrItems, $zipcode, false, false);
        }

        // verificar se deu algum problema na cotação
        if (!$quoteResponse['success']) {
            return [];
        }

        // se não existir serviços
        if (!isset($quoteResponse['data']['services']) || !count($quoteResponse['data']['services'])) {
            return [];
        }

        // recupera a cotação mais em conta
        $quote = $this->getLowerShipping($quoteResponse['data']['services'], true);

        $oferta = $ship_service_preview;
        if ($logistic['type'] === 'freterapido') {
            $expOferta = explode("_", $ship_service_preview);
            $oferta = end($expOferta);
        }
        //Verificar pelo número da cotação, valor pago e sla.
        $selectedQuote = array();

        if ($this->enable_multiseller_operation) {
            if (!empty($quoteResponse['data']['services'])) {
                $price    = null;
                $deadline = null;
                $this->loadFreightStandardizationConfig();

                $orignal_services = array();
                $this->setMarketplaceReplaceShippingMethod($quoteResponse, $orignal_services);

                $shipping_method = current($orignal_services)[0]['method'] ?? null;

                if (empty($shipping_method)) {
                    return [];
                }

                foreach ($quoteResponse['data']['services'] as $service) {
                    if ($service['method'] == $ship_service_preview) {
                        $price += $service['value'];
                        if ($service['deadline'] > $deadline) {
                            $deadline = $service['deadline'];
                        }
                    }
                }

                $selectedQuote = array(
                    'value' => $price,
                    'deadline' => $deadline,
                    'provider' => $shipping_method,
                    'method' => $shipping_method,
                );
            }
        } else {
            foreach ($quoteResponse['data']['services'] as $service) {
                if ($logistic['type'] !== 'freterapido' && strtolower($oferta) == strtolower($service['method'])) {
                    $selectedQuote = $service;
                    break;
                } elseif ($logistic['type'] === 'freterapido' && $oferta == $service['quote_id']) {
                    $selectedQuote = $service;
                    break;
                }
            }
        }

        if ($selectedQuote === null && count($quoteResponse['data']['services']) === 1) {
            $selectedQuote = $quoteResponse['data']['services'][0];
        }

        return $selectedQuote;
    }

    /**
     * createQuoteShipRegister
     * Quando o pedido vier da VTEX, entrará nessa função para
     * salvar os dados da cotação no nosso banco.
     * @param  mixed $orderId
     * @return bool
     */
    public function createQuoteShipRegister(int $orderId, $platform = 'VTEX'): bool
    {   
        //$platform = 'VTEX';
        $log_name = __CLASS__.'/'.__FUNCTION__;

        $order = $this->instance->db->get_where('orders', array('id' => $orderId))->row_array();
        if (!$order) {
            get_instance()->log_data('batch', $log_name, "Não atualizou os dados de envio do pedido:$orderId.\n\nNão encontrou o pedido.", 'E');
            return false;
        }

        $items = $this->instance->db->get_where('orders_item', array('order_id' => $orderId))->result_array();
        if (!$items) {
            get_instance()->log_data('batch', $log_name, "Não atualizou os dados de envio do pedido:$orderId.\n\nNão encontrou os itens do pedido.", 'E');
            return false;
        }

        $store = $this->instance->db->get_where('stores', array('id' => $order['store_id']))->row_array();
        if (!$store) {
            get_instance()->log_data('batch', $log_name, "Não atualizou os dados de envio do pedido:$orderId.\n\nNão encontrou a loja ({$order['store_id']}).", 'E');
            return false;
        }

        $logistic = $this->getLogisticStore(array(
            'freight_seller' 		=> $store['freight_seller'],
            'freight_seller_type' 	=> $store['freight_seller_type'],
            'store_id'				=> $store['id']
        ));

        $this->instance->model_orders->updateByOrigin(
            $orderId,
            array(
                'freight_seller'        => $logistic['seller'],
                'manual_freight'        => $logistic['type'] === false,
                'integration_logistic'  => $logistic['sellercenter'] ? ($logistic['type'] ?: null) : null
            )
        );
        
        //Funcionalidade apenas para pedidos frete rápido, para realizar cotação inicial e salvar dados da cotação
        //if (!in_array($logistic['type'], ['freterapido', 'intelipost'])) return true;

        $mkt = array("platform" => $platform, "channel" => $order['origin']);
        $marketplace = $order['origin'];

        //consulta no banco pesquisando a plataforma (int_to) e o (prd_id)id do produto vtex_ult_envio, retornando o seller_id
        // cria array com itens
        $arrItems   = array();
        $skuMkt     = array();
        foreach ($items as $item) {
            $result = $this->instance->db
                ->select('seller_id')
                ->where(array('int_to' => $order['origin'], 'prd_id' => $item['product_id']))
                ->get('vtex_ult_envio')
                ->row_array();

            $skuMkt[] = $item['skumkt'];
            $arrItems[] = array(
                'qty'    => $item['qty'],
                'sku'    => $item['skumkt'],
                'seller' => $result['seller_id']
            );
        }
        if ($this->ms_shipping->use_ms_shipping) {
        //if (false) {
            $quoteResponse = (object)$this->ms_shipping->getFormatQuoteAuction($marketplace, $arrItems, $order['customer_address_zip']);
        }
        else{
            $quoteResponse = $this->formatQuote($mkt, $arrItems, $order['customer_address_zip'], false);
        }

        // verificar se deu algum problema na cotação
        if (empty((array)$quoteResponse) || !$quoteResponse['success']) {
            get_instance()->log_data('batch', $log_name, "Não atualizou os dados de envio do pedido:$orderId.\n\nNão foi encontrado serviços na cotação para recuperar os dados.\n\nquoteResponse=".json_encode($quoteResponse), 'E');
            return false;
        }

        $sellerCenter = $this->getSellerCenter();
        
        // se não existir serviços
        if (!isset($quoteResponse['data']['services']) || !count($quoteResponse['data']['services'])) {
            return false;
        }

        $oferta = $order['ship_service_preview'];
        if ($logistic['type'] === 'freterapido') {
            $expOferta = explode("_", $order['ship_service_preview']);
            $oferta = end($expOferta);
        }
        //Verificar pelo número da cotação, valor pago e sla.
        $selectedQuote = NULL;
        foreach ($quoteResponse['data']['services'] as $service) {
            // Se for soma, devemos atualizar o ship_company_preview e ship_service_preview, no pedido sempre virá como "Normal".
            if ($sellerCenter == 'somaplace') {
                $update_order = array();
                // Atualiza a transportadora e método de envio.
                $update_order['ship_company_preview'] = $service['provider'] ?? $order['ship_company_preview'];
                $update_order['ship_service_preview'] = $service['method'] ?? $order['ship_service_preview'];

                // Salva se o frete será manual ou por uma integradora.
                $ship_company_preview = $update_order['ship_company_preview'] ?? $order['ship_company_preview'];
                $update_order['manual_freight'] = $logistic['type'] === false;

                $this->instance->model_orders->updateByOrigin($orderId, $update_order);
                break;
            }
            if (strtolower($oferta) == strtolower($service['quote_id']) && $logistic['type'] === 'freterapido') {
                $selectedQuote = $service;
                break;
            }
            elseif (strtolower($oferta) == strtolower($service['method']) && $logistic['type'] !== 'freterapido') {
                $selectedQuote = $service;
                break;
            }
        }

        if ($selectedQuote === NULL) {
            return false;
        }

        $service_method = $selectedQuote['method'] ?? $order['ship_service_preview'];
        if ($logistic['type'] === 'freterapido') {
            $service_method = explode("_", $service_method);
            $service_method = $service_method[0];
        }

        $dataFr = array(
            'cost'           => $selectedQuote['value'] ?? $order['total_ship'],
            'retorno'        => $selectedQuote['quote_json'] ?? json_encode($quoteResponse),
            'oferta'         => $selectedQuote['quote_id'] ?? 0,
            'token_oferta'   => $selectedQuote['token_oferta'] ?? '{}',
            'prazo_entrega'  => $selectedQuote['deadline'] ?? $order['ship_time_preview'],
            'service_method' => $service_method,
            'provider'       => $selectedQuote['provider'] ?? $order['ship_company_preview'],
            'provider_cnpj'  => $selectedQuote['provider_cnpj'] ?? '',
            'order_id'       => $orderId,
            'marketplace'    => $order['origin'],
            'zip'            => $order['customer_address_zip'],
            'sku'            => json_encode($skuMkt)
        );
        $this->instance->model_orders->updateByOrigin($orderId, array("ship_companyName_preview"=> $dataFr["provider"]));
        $this->instance->model_quotes_ship->create($dataFr);

        return true;
    }

    public function getQuoteViaVarejo(array $dataQuote, array $logistic, bool $moduloFrete = false): array
    {
        $credentials = $this->getCredentialsRequest($dataQuote['dataInternal'][$dataQuote['items'][0]['sku']]['store_id']);
        $cnpjStore = cnpj(onlyNumbers($dataQuote['dataInternal'][$dataQuote['items'][0]['sku']]['CNPJ']));
        $arrQuote = array(
            "idCampanha"            => $credentials['campaign'],
            "cnpj"                  => $cnpjStore,
            "cep"                   => $dataQuote['zipcodeRecipient'],
//            "idEntregaTipo"         => 1,
//            "idEnderecoLojaFisica"  => 1,
//            "idUnidadeNegocio"      => 1,
            "produtos"              => array(),
        );

        $token = $credentials['token_b2b_via'];
        $endpoint = null;
        $deadline = 0;
        switch ($credentials['integration']) {
            case 'viavarejo_b2b_casasbahia':
                $endpoint = $this->endpointViaB2BCasasBahia;
                break;
            case 'viavarejo_b2b_extra':
                $endpoint = $this->endpointViaB2BExtra;
                break;
            case 'viavarejo_b2b_pontofrio':
                $endpoint = $this->endpointViaB2BPontoFrio;
                break;
        }

        $urlQuote = "$endpoint/pedidos/carrinho";
        $urlAvailable = "$endpoint/campanhas/{$credentials['campaign']}/produtos";

        $arr_opt = array(
            "Content-Type: application/json",
            "Authorization: $token",
            "Accept: text/plain"
        );

        $services = array();

        if ($moduloFrete) {
            foreach ($dataQuote['items'] as $sku) {
                $arrQuote['produtos'] = array(
                    array(
                        "codigo"        => $sku['skuseller'],
                        "quantidade"    => $sku['quantidade'],
                        "idLojista"     => $credentials['idLojista'],
                    )
                );

                $getAvailable = $this->sendRest("$urlAvailable/{$sku['skuseller']}?cnpj=$cnpjStore&idLojista={$credentials['idLojista']}", $arr_opt);
                $responseAvailable = json_decode($getAvailable['content'],true);
                if ($getAvailable['httpcode'] != 200 || !isset($responseAvailable['data']['valor'])) {
                    $messageError = $responseAvailable['error']['message'] ?? json_encode($responseAvailable);
                    return array(
                        'success' => false,
                        'data' => "Produto ({$sku['skuseller']}) não disponível - HTTP_CODE={$getAvailable['httpcode']} - RESPONSE=$messageError"
                    );
                }

                if (roundDecimal($responseAvailable['data']['valor']) != roundDecimal($sku['valor'])) {
                    return array(
                        'success' => false,
                        'data' => "Produto ({$sku['skuseller']}) está com o preço diferente do pretendido. SellerCenter={$sku['valor']} | Via={$responseAvailable['data']['valor']}"
                    );
                }

                $postQuote = $this->sendRest($urlQuote, $arr_opt, json_encode($arrQuote), 'POST');
                // estorou limite, vai na tabela interna
                // limitado 1s para ir a contigencia (caso seja muitos itens item ou caso tenha mais que 1 itens para cotar e precise fazer mais que uma requisição na sgp)
                // errno 28 = connection timeout
                if (isset($postQuote['errno']) && $postQuote['errno'] == 28) {
                    return array(
                        'success' 	=> false,
                        'data' 		=> "Timeout B2B"
                    );
                }

                $httpCode = (int)$postQuote['httpcode'];
                $response = json_decode($postQuote['content'],true);

                if ($httpCode != 200) {
                    $messageError = $response['error']['message'] ?? json_encode($response);
                    return array(
                        'success' => false,
                        'data' => "Ocorreu um problema para realiza a cotação na Via Varejo - HTTP_CODE=$httpCode - RESPONSE=$messageError"
                    );
                }

                $service = $response['data'];

                foreach ($service['produtos'] as $product) {
                    if ($product['previsaoEntrega'] === 'Imediato') {
                        $product['previsaoEntrega'] = 1;
                    } else {
                        $datetime1 = new DateTime($product['previsaoEntrega']);
                        $datetime2 = dateNow(TIMEZONE_DEFAULT);
                        $interval = $datetime1->diff($datetime2);
                        $product['previsaoEntrega'] = $interval->days;
                    }

                    if ($deadline < $product['previsaoEntrega']) {
                        $deadline = $product['previsaoEntrega'];
                    }
                }

                $services[] = array(
                    'prd_id'    => $dataQuote['dataInternal'][$sku['sku']]['prd_id'],
                    'quote_id'  => NULL,
                    'method_id' => NULL,
                    'value'     => $service['valorFrete'],
                    'deadline'  => $deadline + $dataQuote['crossDocking'],
                    'method'    => 'Via Varejo',
                    'provider'  => 'Via Varejo'
                );
            }
        } else {
            foreach ($dataQuote['items'] as $sku) {
                $dataProduct = array(
                    "codigo"        => $sku['skuseller'],
                    "quantidade"    => $sku['quantidade'],
                    "idLojista"     => $credentials['idLojista'],
                );
                $arrQuote['produtos'][] = $dataProduct;
            }
        }

        if (!$moduloFrete) {
            $postQuote = $this->sendRest($urlQuote, $arr_opt, json_encode($arrQuote), 'POST');
            // estorou limite, vai na tabela interna
            // limitado 1s para ir a contigencia (caso seja muitos itens item ou caso tenha mais que 1 itens para cotar e precise fazer mais que uma requisição na sgp)
            // errno 28 = connection timeout
            if (isset($postQuote['errno']) && $postQuote['errno'] == 28) {
                return array(
                    'success' 	=> false,
                    'data' 		=> "Timeout B2B"
                );
            }

            $httpCode = (int)$postQuote['httpcode'];
            $response = json_decode($postQuote['content'], true);

            if ($httpCode != 200) {
                $messageError = $response['error']['message'] ?? json_encode($response);
                return array(
                    'success' => false,
                    'data' => "Ocorreu um problema para realiza a cotação na Via Varejo - HTTP_CODE=$httpCode - RESPONSE=$messageError"
                );
            }

            $service = $response['data'];

            foreach ($service['data']['produtos'] as $product) {
                if ($deadline < $product['previsaoEntrega']) {
                    $deadline = (int)onlyNumbers($product['previsaoEntrega']);
                }
            }

            $services[] = array(
                'prd_id'    => NULL,
                'quote_id'  => NULL,
                'method_id' => NULL,
                'value'     => $service['valorFrete'],
                'deadline'  => $deadline + $dataQuote['crossDocking'],
                'method'    => 'Via Varejo',
                'provider'  => 'Via Varejo'
            );
        }

        return array(
            'success'   => true,
            'data'      => array(
                'services'  => $services
            )
        );
    }
    
    //Função para desabilitar as integrações logísticas para black friday
    function disableLogisticsIntegration(array $dataQuote): array
    {
        $services = array();
        array_push($services, array(
            'prd_id'    =>  $dataQuote['dataInternal'][$dataQuote['items'][0]['sku']]['prd_id'],
            'quote_id'  => 'Transportadora',
            'method_id' => 'Transportadora',
            'value'     =>  100000,
            'deadline'  => '20',
            'method'    => 'Transportadora',
            'provider'  => 'ConectaLa'
        ));

        return array(
            'success'   => true,
            'data'      => array(
                'services'  => $services
            )
        );
    }

    public function getSellerCenter()
    {
        return $this->sellercenter;
    }

    public function setSellerCenter()
    {
        $sellerCenter = $this->getSettingRedis($this->instance->redis, 'sellercenter');
        $this->sellercenter = $sellerCenter['value'];
    }

    public function getStoreIdBySellerId(string $seller_id)
    {
        /*
        $sql = "SELECT store_id, seller_id, freight_seller, freight_seller_type
                FROM vtex_ult_envio
                WHERE seller_id IN ?
                GROUP BY store_id";
        */

        return $this->readonlydb
                    ->select('store_id, seller_id, freight_seller, freight_seller_type')
                    ->where('seller_id', $seller_id)
                    ->limit(1)
                    ->get('vtex_ult_envio')
                    ->result_array();

    }

    public function getIntegrationLogistic(int $store)
    {
        $integration = $this->getDataIntegrationActiveByStore($store);

        if (!$integration) {
            return null;
        }

        // recupera a credencial do seller center.
        if ($integration->credentials === null) {
            $key_redis_integration_logistic_seller_center = $this->getSellerCenter().":integration_logistic_seller_center:$store";
            if ($this->instance->redis->is_connected) {
                $data_redis = $this->instance->redis->get($key_redis_integration_logistic_seller_center);
                if ($data_redis !== null) {
                    return $data_redis;
                }
            }

            $integration_logistic_seller_center = $this->readonlydb
                ->select('credentials,store_id,integration')
                ->where('store_id', 0)
                ->where('active', 1)
                ->where('integration', $integration->integration)
                ->get('integration_logistic use index (ix_integration_logistic_store_id_active_integration)')
                ->row_object();

            if (!$integration_logistic_seller_center) {
                return null;
            }

            if ($this->instance->redis->is_connected) {
                $this->instance->redis->setex($key_redis_integration_logistic_seller_center, 1800, $integration_logistic_seller_center->integration);
            }

            return $integration_logistic_seller_center->integration;
        }

        return $integration->integration;
    }

    /**
     * Retornar o serviço mais barato
     *
     * Consultar parametro 'frete_mais_barato' e recuperar se está ativo para participaram desse evento.
     *
     * O recebimento no parametro '$services' sempre será todos os serviços disponíveis.
     * O retorno deverá ser um array contendo apenas serviço com o valor mais barato.
     *
     * @param   array $services             Serviços cotado
     * @param   bool  $alwaysLowerShipping  Sempre retornar o frete mais barato, o parâmetro será ignorado
     * @return  array                       Retonar os serviços com valor zerado, caso não existe a promoção para a loja, retornar o que recebeu
     */
    public function getLowerShipping(array $services, bool $alwaysLowerShipping = false): array
    {
        $rowSettings = $this->getSettingRedis($this->instance->redis, 'frete_mais_barato');

        if (!$alwaysLowerShipping && (!$rowSettings || $rowSettings['status'] == 2)) return $services;

        $newService = array();

        // ler os serviços e pegar o mais barato
        foreach ($services as $key => $service) {

            if (empty($newService) || $newService['value'] > $service['value']) {
                $newService = $service;
            }
        }

        return array($newService);
    }

    public function getShippingCompanyStore(int $store)
    {
        $key_redis_integration_logistic_seller_center = $this->getSellerCenter().":shipping_company_type:$store";
        if ($this->instance->redis->is_connected) {
            $data_redis = $this->instance->redis->get($key_redis_integration_logistic_seller_center);
            if ($data_redis !== null) {
                return json_decode($data_redis);
            }
        }

        $shipping_company = $this->readonlydb
            ->select('p.*, t.id_type')
            ->join('shipping_company AS p use index (ix_shipping_company_active)', 'p.id = pts.provider_id')
            ->join('type_table_shipping AS t', 't.id_provider = p.id', 'left')
            ->where(['pts.store_id' => $store, 'p.active' => true])
            ->get('providers_to_seller pts')
            ->result_object();

        if ($this->instance->redis->is_connected && count($shipping_company)) {
            $this->instance->redis->setex($key_redis_integration_logistic_seller_center, 1800, json_encode($shipping_company));
        }

        return $shipping_company;
    }

    /**
     * @param   int         $store      Código da loja.
     * @throws  Exception
     */
    public function productCanBePublished(int $store)
    {
        // Não tem integração e não tem transportadora.
        if (!$this->getIntegrationLogistic($store) && !count($this->getShippingCompanyStore($store))) {
            throw new Exception('Loja não tem integração e transportadora.');
        }
    }

    public function getColumnsMarketplace(string $platform): array
    {
        $platform_selected = true;
        switch ($platform) {
            case 'B2W':
            case 'VIA':
            case 'CAR':
                $table = 'bling_ult_envio';
                $columnTotalQty = 'qty_atual';
                break;
            case 'ML':
                $table = 'ml_ult_envio';
                $columnTotalQty = 'qty_total';
                break;
            case 'VTEX':
                $table = 'vtex_ult_envio use index (index_by_int_to_skumkt_seller_id)';
                $columnTotalQty = 'qty_atual';
                break;
            case 'MAD':
                $table = 'mad_last_post';
                $columnTotalQty = 'qty_total';
                break;
            case 'NM':
                $table = 'integration_last_post';
                $columnTotalQty = 'qty_atual';
                break;
            case 'GPA':
                $table = 'gpa_last_post';
                $columnTotalQty = 'qty_total';
                break;
            case 'VS':
                $table = 'vs_last_post';
                $columnTotalQty = 'qty_total';
                break;
            case 'OCC':
                $table = 'occ_last_post';
                $columnTotalQty = 'qty_total';
                break;
            default:
                $platform_selected = false;
                $table = 'sellercenter_last_post';
                $columnTotalQty = 'qty_total';
                break;
        }

        if (!$platform_selected) {
            $lowercase_platform = strtolower($platform);
            // se a tabela last_post existe para o marketplace, será utilizada essa.
            if ($this->readonlydb->table_exists("{$lowercase_platform}_last_post") && $this->readonlydb->select('id')->limit(1)->get("{$lowercase_platform}_last_post")->num_rows() > 0) {
                $table = "{$lowercase_platform}_last_post";
                $columnTotalQty = 'qty_total';
            } elseif ($this->readonlydb->table_exists("{$lowercase_platform}_ult_envio") && $this->readonlydb->select('id')->limit(1)->get("{$lowercase_platform}_ult_envio")->num_rows() > 0) {
                $table = "{$lowercase_platform}_ult_envio";
                $columnTotalQty = 'qty_atual';
            }
        }

        return [
            'table' => $table,
            'qty'   => $columnTotalQty
        ];
    }

    /**
     * @param   array       $items          Itens para validação.
     * @param   array       $mkt            Vetor com os dados de channel e platform.
     * @param   string      $table          Nome da tabela onde serão validados os itens publicados.
     * @param   string      $columnTotalQty Nome da coluna de quantidade.
     * @param   bool        $checkStock     Será verificado o estoque.
     * @param   string|null $zipcode        CEP do cliente.
     * @param   array       $dataRecipient  Dados do endereço do remetente.
     * @return  array
     * @throws  Exception
     */
    public function validItemsQuote(array $items, array $mkt, string $table, string $columnTotalQty, bool $checkStock = true, string $zipcode = null, array $dataRecipient = array()): array
    {
        if (!count($items)) {
            throw new Exception("Não foram encontrados itens");
        }

        $dataQuote = array('zipcodeRecipient' => $zipcode, 'items' => array());
        $firstStore         = 0;
        $totalPrice         = 0;
        $storeId            = 0;
        $dataSkus           = array();
        $arrDataAd          = array();
        $channel            = $mkt['channel'];
        $cross_docking      = 0;
        $zipCodeSeller      = null;
        $quoteResponse      = array();
        $store_integration  = null;
        $logistic           = array(
            'type'          => 'sellercenter',
            'seller'        => false,
            'sellercenter'  => true
        );

        $stores_multi_cd = false;
        $settingStoresMultiCd = $this->getSettingRedis($this->instance->redis, 'stores_multi_cd');
        if ($settingStoresMultiCd && $settingStoresMultiCd['status'] == 1) {
            $stores_multi_cd = true;
        }

        if ($zipcode !== null) {
            $zipcode = str_pad($zipcode, 8, "0", STR_PAD_LEFT);
        }

        $change_sku_seller = false;  // problema da VIA que não coloca todas as variações na bling-ult_envio.
        foreach ($items as $iten) {
            $sku = $iten['sku'];

            if (empty($sku)) {
                $quoteResponse = array(
                    'success' => false,
                    'data' => array(
                        'message' => "Produto inexistente para $channel - $table - " . json_encode([$items, $mkt])
                    )
                );
                continue;
            }

            $filterUltEnvio = $this->getFilterTableUltEnvio($mkt, $iten, $sku);

            $query   = $this->readonlydb->get_where($table, $filterUltEnvio);
            $dataAd  = $query->row_array();
            $variant = null;
            $data    = array('int_to' => $channel, 'sku' => $iten['sku']);
            if (!$dataAd) {
                // pode ser produto com variação (FORMATO ANTIGO)
                if (strrpos($sku, '-') !=0) {
                    $sku     = substr($iten['sku'], 0, strrpos($iten['sku'], '-'));
                    $variant = substr($iten['sku'], strrpos($iten['sku'], '-') + 1);

                    $filterUltEnvio = $this->getFilterTableUltEnvio($mkt, $iten, $sku);

                    $query   = $this->readonlydb->get_where($table, $filterUltEnvio);
                    $dataAd = $query->row_array();
                }

                // não encontrou o produto nem a variação antiga
                if (!$dataAd) {
                    //produto não existe
                    $quoteResponse = array(
                        'success' => false,
                        'data' => array(
                            'message' => "Produto inexistente para $channel - $table - ".print_r($filterUltEnvio, true)." : ".print_r($data, true)
                        )
                    );
                    continue;
                }

                if ($stores_multi_cd) {
                    $dataAd = $this->setDataMultiCd($dataAd, $columnTotalQty, $zipcode, $iten['qty']);
                }

                if ($mkt['platform'] != 'VTEX') {
                    $filter_variant = array('prd_id' => $dataAd['prd_id']);
                    if ($stores_multi_cd) {
                        $filter_variant['sku'] = $dataAd['sku'];
                    } else {
                        $filter_variant['variant'] = $variant;
                    }
                    $query = $this->readonlydb->get_where('prd_variants', $filter_variant);

                    $prd_variants = $query->row_array();
                    if (!$prd_variants) {
                        $quoteResponse = array(
                            'success' => false,
                            'data' => array(
                                'message' => "Produto inexistente para $channel - $table - ".print_r($filterUltEnvio, true)." : ".print_r($data, true)
                            )
                        );
                        continue;
                    }
                    // se a qty em estoque da variação for menor, considera a variação
                    if ($prd_variants['qty'] < $dataAd[$columnTotalQty]) {
                        $dataAd[$columnTotalQty] = $prd_variants['qty'];  // vale o estoque do produto variante
                    }
                    $change_sku_seller = $prd_variants['sku'];
                }
            } else {
                if ($stores_multi_cd) {
                    $dataAd = $this->setDataMultiCd($dataAd, $columnTotalQty, $zipcode, $iten['qty']);
                }
                // existe variação (FORMATO NOVO)
                if ($dataAd['variant'] !== null) {
                    $variant = $dataAd['variant'];

                    if ($mkt['platform'] != 'VTEX' && $mkt['platform'] != 'OCC') {
                        $filter_variant = array('prd_id' => $dataAd['prd_id']);
                        if ($stores_multi_cd) {
                            $filter_variant['sku'] = $dataAd['sku'];
                        } else {
                            $filter_variant['variant'] = $variant;
                        }

                        $query = $this->readonlydb->get_where('prd_variants', $filter_variant);

                        $prd_variants = $query->row_array();
                        if (!$prd_variants) {
                            $quoteResponse = array(
                                'success' => false,
                                'data' => array(
                                    'message' => "Produto inexistente para $channel - $table - ".print_r($filterUltEnvio, true)." : ".print_r($data, true)
                                )
                            );
                            continue;
                        }
                        // se a qty em estoque da variação for menor, considera a variação
                        if ($prd_variants['qty'] < $dataAd[$columnTotalQty]) {
                            $dataAd[$columnTotalQty] = $prd_variants['qty'];  // vale o estoque do produto variante
                        }
                    }
                }
            }

            // Precode
            // Não tem variação, então pego o sku do seller na bling_ult_envio
            $skuseller = $dataAd['sku'];
            if ($change_sku_seller) {  // resolve problema da VIA que não grava as variações na Bling_ult_envio
                $skuseller = $change_sku_seller;
            }

            $tipoVolumeCodigo = $dataAd['tipo_volume_codigo'];

            // se já cadastrou no bling_ult_envio, não preciso ler o prefix
            $zipCodeSeller  = $dataAd['zipcode'];

            $logistic = $this->getLogisticStore(array(
                'freight_seller' 		=> $dataAd['freight_seller'],
                'freight_seller_type' 	=> $dataAd['freight_seller_type'],
                'store_id'				=> $dataAd['store_id']
            ));

            $arrDataAd[$iten['sku']] = $dataAd;
            $prd_to_skumkt[$dataAd['prd_id']] = $iten['sku'];

            // Adição dos dados sobre o sku necessário para uso na cotação.
            $listPrice = array_key_exists('list_price', $dataAd) ? $dataAd['list_price']:  $dataAd['price'];
            if (empty($listPrice)) {
                $listPrice = 0;
            }
            $dataSkus[$iten['sku']] = array(
                'current_qty'   => $dataAd[$columnTotalQty],
                'sale_price'    => $dataAd['price'],
                'list_price'    => $listPrice,
                'store_id'      => $dataAd['store_id'],
                'prd_id'        => $dataAd['prd_id'],
                'seller_id'     => $dataAd['seller_id'] ?? NULL
            );
            $totalPrice += $dataAd['price'] * $iten['qty'];

            if (isset($dataAd['crossdocking'])) {
                // pega o pior tempo de crossdocking dos produtos
                if (((int)$dataAd['crossdocking']) > $cross_docking) {
                    $cross_docking = (int)$dataAd['crossdocking'];
                }
            }

            $storeId = $dataAd['store_id'];
            if ($firstStore == 0) {
                $firstStore = $storeId;
            }

            $store_integration = $this->getStoreIntegrationStore($dataAd['store_id']);


            if ($firstStore != $storeId) {
                // Verificar se multiseller está habilitado
                $enable_multiseller = false;
                
                // Verificar configuração
                $setting_enable = $this->readonlydb->get_where('settings', array('name' => 'enable_multiseller_operation', 'status' => 1))->row_array();
                if ($setting_enable) {
                    $setting_marketplace = $this->readonlydb->get_where('settings', array('name' => 'marketplace_multiseller_operation', 'status' => 1))->row_array();
                    if ($setting_marketplace && strpos($setting_marketplace['value'], $channel) !== false) {
                        $enable_multiseller = true;
                    }
                }
                
                // Verificar feature flag
                if ($enable_multiseller && class_exists('\App\Libraries\FeatureFlag\FeatureManager')) {
                    $enable_multiseller = \App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1921-multiseller-freight-results');
                }
                
                if (!$enable_multiseller) {
                    // Multiseller desabilitado - manter erro original
                    $quoteResponse = array(
                        'success' => false,
                        'data' => array(
                            'message' => 'Cotação com mais de uma loja: '.print_r($data,true)
                        )
                    );
                    continue;
                } else {
                    // Multiseller habilitado - permitir múltiplas lojas
                    error_log("CalculoFrete: Múltiplas lojas permitidas - Multiseller ativo (Store: {$firstStore} -> {$storeId})");
                }
            }

            if ($checkStock && $iten['qty'] > $dataAd[$columnTotalQty]) {
                // quantidade maior que o estoque
                $quoteResponse = array(
                    'success' => false,
                    'data' => array(
                        'message' => 'Produto sem estoque '.print_r($data,true)
                    )
                );
                continue;
            }

            // Fluxo novo (Já está em uso nas integrações)
            $dataQuote['items'][] = array(
                'tipo'          => $tipoVolumeCodigo,
                'sku'           => $iten['sku'],
                'quantidade'    => (int)$iten['qty'],
                'altura'        => (float)(($dataAd['altura'] ?? $dataAd['height']) / 100),
                'largura'       => (float)(($dataAd['largura'] ?? $dataAd['width']) / 100),
                'comprimento'   => (float)(($dataAd['profundidade'] ?? $dataAd['length']) / 100),
                'peso'          => (float)($dataAd['peso_bruto'] ?? $dataAd['gross_weight']),
                'valor'         => (float)($dataAd['price'] * $iten['qty']),
                'skuseller'     => $skuseller,
                'variant'       => $dataAd['variant'] === '' ? null : $dataAd['variant']
            );
            if ($checkStock && $iten['qty'] <= 0) {
                $quoteResponse = array(
                    'success' => false,
                    'data' => array(
                        'message' => "Quantidade não atendida (<= 0) para $channel: " . print_r($items,true)
                    )
                );
            }
        }
        $storeIds = [];
        foreach ($arrDataAd as $sku => $data) {
            if (!in_array($data['store_id'], $storeIds)) {
                $storeIds[] = $data['store_id'];
            }
        }
        // Se multiseller, retornar informações adicionais
        $multisellerInfo = null;
        if (count($storeIds) > 1) {
            $multisellerInfo = [
                'is_multiseller' => true,
                'store_ids' => $storeIds,
                'total_stores' => count($storeIds),
                'items_by_store' => []
            ];
            
            // Agrupar itens por store
            foreach ($arrDataAd as $sku => $data) {
                $storeId = $data['store_id'];
                if (!isset($multisellerInfo['items_by_store'][$storeId])) {
                    $multisellerInfo['items_by_store'][$storeId] = [];
                }
                $multisellerInfo['items_by_store'][$storeId][] = $sku;
            }
        }

        return [
            'arrDataAd'         => $arrDataAd,
            'dataSkus'          => $dataSkus,
            'totalPrice'        => $totalPrice,
            'cross_docking'     => $cross_docking,
            'quoteResponse'     => $quoteResponse,
            'zipCodeSeller'     => $zipCodeSeller,
            'dataQuote'         => $dataQuote,
            'storeId'           => $storeId,  // Manter para compatibilidade
            'storeIds'          => $storeIds, // Novo: array de store_ids
            'logistic'          => $logistic,
            'store_integration' => $store_integration,
            'multiseller_info'  => $multisellerInfo // Novo: informações multiseller
        ];
    }

    public function getDataIntegrationActiveByStore(int $store)
    {
        if (!array_key_exists($store, $this->dataIntegration)) {
            $key_redis_integration_logistic = $this->getSellerCenter().":integration_logistic:$store";
            if ($this->instance->redis->is_connected) {
                $data_redis = $this->instance->redis->get($key_redis_integration_logistic);
                if ($data_redis !== null) {
                    $this->dataIntegration[$store] = json_decode($data_redis);
                    return $this->dataIntegration[$store];
                }
            }

            $this->dataIntegration[$store] = $this->readonlydb
                ->where('store_id', $store)
                ->where('active', 1)
                ->get('integration_logistic use index (ix_integration_logistic_store_id_active)')
                ->row_object();

            if ($this->instance->redis->is_connected) {
                $this->instance->redis->setex($key_redis_integration_logistic, 21600, json_encode($this->dataIntegration[$store], JSON_UNESCAPED_UNICODE));
            }
        }

        return $this->dataIntegration[$store];
    }

    private function getSettingRedis(RedisCodeigniter $redis, string $setting_name)
    {
        if ($this->getSellerCenter()) {
            $key_redis = $this->getSellerCenter().":settings:$setting_name";
            if ($redis->is_connected) {
                try {
                    $data_redis = $redis->get($key_redis);
                    if ($data_redis !== null) {
                        return json_decode($data_redis, true);
                    }
                } catch (Throwable $exception) {}
            }
        }

        $time_exp_redis = 3600;
        if ($setting_name === 'sellercenter') {
            $time_exp_redis = 43200;
        }

        $setting = $this->readonlydb->get_where('settings', array('name' => $setting_name))->row_array();

        if ($setting_name === 'sellercenter') {
            $key_redis = "{$setting['value']}:settings:$setting_name";
        }

        if ($redis->is_connected && ($this->getSellerCenter() || $setting_name === 'sellercenter')) {
            $redis->setex($key_redis, $time_exp_redis, json_encode($setting, JSON_UNESCAPED_UNICODE));
        }
        return $setting;
    }

    /**
     * @param   array       $dataAd
     * @param   string      $quantity_column
     * @param   string|null $zipcode
     * @param   int|null    $qty
     * @return  array
     */
    public function setDataMultiCd(array $dataAd, string $quantity_column, string $zipcode = null, int $qty = null): array
    {
        // Não foi informado CEP.
        if (empty($zipcode)) {
            return $dataAd;
        }

        $key_check_store_multi_cd = $this->getSellerCenter() . ":check_multi_channel_fulfillment:store:$dataAd[store_id]";
        $check_store_multi_cd = null;
        // Consultando os dados no redis.
        if ($this->instance->redis->is_connected) {
            $data_check_store_multi_cd = $this->instance->redis->get($key_check_store_multi_cd);
            if ($data_check_store_multi_cd !== null) {
                $check_store_multi_cd = json_decode($data_check_store_multi_cd, true);
            }
        }

        // Consulta a loja no banco.
        if (is_null($check_store_multi_cd)) {
            $check_store_multi_cd = $this->readonlydb->get_where('stores', array('id' => $dataAd['store_id']))->row_array();
            if ($this->instance->redis->is_connected) {
                $this->instance->redis->setex($key_check_store_multi_cd, 43200, json_encode($check_store_multi_cd, JSON_UNESCAPED_UNICODE));
            }
        }

        $inventory_utilization = $check_store_multi_cd['inventory_utilization'];

        if ($check_store_multi_cd['type_store'] == 0) {
            return $dataAd;
        }

        // Se chegou sem estoque, é porque o pulmão e CD não atendem.
        if ($dataAd[$quantity_column] <= 0) {
            return $dataAd;
        }

        // Chaves redis
        $key_stores_multi_channel_fulfillment   = $this->getSellerCenter() . ":stores_multi_channel_fulfillment:$dataAd[store_id]:$zipcode";
        $key_multi_channel_fulfillment_store    = $this->getSellerCenter() . ":multi_channel_fulfillment:store:$dataAd[store_id]";

        // Dados em ache para validar no redis
        $stores_multi_channel_fulfillment = null;
        $stores = null;

        // Consultando os dados no redis.
        if ($this->instance->redis->is_connected) {
            $data_redis_stores_multi_channel_fulfillment = $this->instance->redis->get($key_stores_multi_channel_fulfillment);
            $data_redis_multi_channel_fulfillment_store = $this->instance->redis->get($key_multi_channel_fulfillment_store);
            if ($data_redis_stores_multi_channel_fulfillment !== null) {
                $stores_multi_channel_fulfillment = json_decode($data_redis_stores_multi_channel_fulfillment, true);
            }
            if ($data_redis_multi_channel_fulfillment_store !== null) {
                $stores = json_decode($data_redis_multi_channel_fulfillment_store, true);
            }
        }

        // Caso não esteja no redis, irá consultar a informação na tabela.
        if (!$stores_multi_channel_fulfillment) {
            $stores_multi_channel_fulfillment = $this->readonlydb->get_where('stores_multi_channel_fulfillment', array(
                'store_id_principal' => $dataAd['store_id'],
                'zipcode_start <='   => $zipcode,
                'zipcode_end >='     => $zipcode
            ))->row_array();
        }

        $quote_by_main = false;
        // Nenhum CD atende o cep do cliente.
        // Precisamos ir ao pulmão.
        if (!$stores_multi_channel_fulfillment) {
            $quote_by_main = true;
            $zipcode    = $dataAd['zipcode'];
            $cnpj       = $dataAd['CNPJ'] ?? $dataAd['cnpj'];
            $store_id   = $dataAd['store_id'];
        } else {
            // Salva range de cep da loja em cache.
            if ($this->instance->redis->is_connected) {
                $this->instance->redis->setex($key_stores_multi_channel_fulfillment, 43200, json_encode($stores_multi_channel_fulfillment, JSON_UNESCAPED_UNICODE));
            }

            if (!$stores) {
              $stores = $this->readonlydb->get_where('stores', array('id' => $stores_multi_channel_fulfillment['store_id_cd']))->row_array();
            }
                
            // Loja não encontrada.
            if (!$stores) {
                return $dataAd;
            }

            // Salva a loja em cache.
            if ($this->instance->redis->is_connected) {
                $this->instance->redis->setex($key_multi_channel_fulfillment_store, 1800, json_encode($stores, JSON_UNESCAPED_UNICODE));
            }

            $zipcode    = $stores['zipcode'];
            $cnpj       = $stores['CNPJ'];
            $store_id   = $stores_multi_channel_fulfillment['store_id_cd'];
        }

        try {
            $dataAd = $this->getDataToQuoteByProductOrVariation($dataAd, $store_id, $qty, $quantity_column, $inventory_utilization);

            $dataAd['store_id'] = $store_id;
            $dataAd['zipcode']  = $zipcode;
            $dataAd['CNPJ']     = $cnpj;
        } catch (Exception $exception) {
            // O produto não tem disponibilidade.
            // Se não for o pulmão, irei ao pulmão saber se tem disponibilidade.
            // Se não tem utilização de estoque, usar estoque de todas as lojas ou somente a pulmão cota.
            if (!$quote_by_main && in_array($inventory_utilization, [null, 'all_stores', 'main_store_only'])) {
                try {
                    $dataAd = $this->getDataToQuoteByProductOrVariation($dataAd, $dataAd['store_id'], $qty, $quantity_column, $inventory_utilization);
                } catch (Exception $exception) {
                    // SKu do pulmão também não tem disponibilidade.
                    // Pode acontecer quando o CD que atende o cep não tem estoque e nem o pulmão, quem atender é outro CD que atende o cep da cotação.
                    $dataAd[$quantity_column] = 0;
                }
            } else {
                // Nenhum CD atende o cep, mas existe algum CD com estoque para esse sku.
                $dataAd[$quantity_column] = 0;
            }
        }

        return $dataAd;
    }

    /**
     * Consulta produto ou variação para cotação em Multi CD.
     *
     * @param   array       $dataAd
     * @param   int         $store_id_cd
     * @param   int|null    $qty
     * @param   string      $quantity_column
     * @param   string|null $inventory_utilization
     * @return  array
     * @throws  Exception
     */
    private function getDataToQuoteByProductOrVariation(array $dataAd, int $store_id_cd, ?int $qty, string $quantity_column, ?string $inventory_utilization): array
    {
        $can_return_stock_store = is_null($inventory_utilization) || in_array($inventory_utilization, ['all_stores', 'cd_store_only']);
        $force_withou_stock = false;

        // Se a loja é a principal e a utilização do estoque é somente para o CD, não usar estoque do CD.
        if ($dataAd['store_id'] == $store_id_cd) {
            if ($inventory_utilization == 'cd_store_only') {
                $force_withou_stock = true;
            } else {
                $can_return_stock_store = true;
            }
        }

        if ($dataAd['variant'] === '' || is_null($dataAd['variant'])) {
            $products = $this->getDataProductToMultiCd(false, $store_id_cd, $dataAd['sku']);;

            if (!$products) {
                throw new Exception("Produto $dataAd[sku] da loja $store_id_cd não encontrado.");
            }

            // O estoque é da principal, mas chegou estoque do CD.
            if (!$force_withou_stock && !$can_return_stock_store) {
                $product_inventory_utilization = $this->getDataProductToMultiCd(false, $dataAd['store_id'], $dataAd['sku']);
                $products['qty'] = $product_inventory_utilization['qty'];
            }

            // Valida o estoque, se o CD não tem, volta para a loja principal.
            if (!is_null($qty) && $qty > $products['qty']) {
                throw new Exception("Produto $dataAd[sku] da loja $store_id_cd não tem estoque suficiente.");
            }

            $dataAd['prd_id']           = $products['id'];
            $dataAd[$quantity_column]   = $force_withou_stock ? 0 : $products['qty'];
            $dataAd['price']            = $products['price'];
            $dataAd['list_price']       = $products['list_price'];
            $dataAd['EAN']              = $products['EAN'];
        }
        // É uma variação.
        else {
            $variant = $this->getDataProductToMultiCd(true, $store_id_cd, $dataAd['sku']);

            if (!$variant) {
                throw new Exception("Variação $dataAd[sku] da loja $store_id_cd não encontrado.");
            }

            // O estoque é da principal, mas chegou estoque do CD.
            if (!$force_withou_stock && !$can_return_stock_store) {
                $variant_inventory_utilization = $this->getDataProductToMultiCd(true, $dataAd['store_id'], $dataAd['sku']);
                $variant['qty'] = $variant_inventory_utilization['qty'];
            }

            // Valida o estoque, se o CD não tem, volta para a loja principal.
            if (!is_null($qty) && $qty > $variant['qty']) {
                throw new Exception("Variação $dataAd[sku] da loja $store_id_cd não tem estoque suficiente.");
            }

            $dataAd['prd_id']           = $variant['prd_id'];
            $dataAd[$quantity_column]   = $force_withou_stock ? 0 : $variant['qty'];
            $dataAd['price']            = $variant['price'];
            $dataAd['list_price']       = $variant['list_price'];
            $dataAd['EAN']              = $variant['EAN'];
            $dataAd['variant']          = $variant['variant'];
        }

        return $dataAd;
    }

    private function getDataProductToMultiCd(bool $has_variant, int $store_id_cd, string $sku)
    {
        if (!$has_variant) {
            return $this->readonlydb->get_where('products', array(
                'store_id'  => $store_id_cd,
                'sku'       => $sku,
                'status'    => 1
            ))->row_array();
        }

        return $this->readonlydb->select('pv.*')
            ->join('products p', 'p.id = pv.prd_id')
            ->where(array(
                'p.store_id' => $store_id_cd,
                'p.status' => 1,
                'pv.sku' => $sku
            ))
            ->get('prd_variants pv')
            ->row_array();
    }

    private function getStoreIntegrationStore(int $store_id)
    {
        $key_integration = $this->getSellerCenter() . ":seller_integration:store:$store_id";

        // Consultando os dados no redis.
        if ($this->instance->redis->is_connected) {
            $data_integration = $this->instance->redis->get($key_integration);
            if ($data_integration !== null) {
                return $data_integration;
            }
        }

        // Consulta a integração da loja no banco.
        $integration = $this->readonlydb->select('integration')->where("store_id", $store_id)->get('api_integrations')->row_array();

        if ($integration) {
            if ($this->instance->redis->is_connected) {
                $this->instance->redis->setex($key_integration, 43200, $integration['integration']);
            }

            return $integration['integration'];
        }

        return null;
    }
        // Método getStoreIntegrationStore existente...
    
    // === NOVOS MÉTODOS MULTISELLER ===
    
    /**
     * Setter público para controle manual da operação multiseller
     * 
     * Permite ativação/desativação manual da funcionalidade multiseller.
     * Útil para testes unitários e controle programático.
     * 
     * @param bool $enabled True para habilitar, false para desabilitar
     * @return self Para method chaining
     */
    public function setEnableMultisellerOperation(bool $enabled): self
    {
        // Verificar feature flag antes de permitir ativação
        if ($enabled && !\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1921-multiseller-freight-results')) { 
            $this->enable_multiseller_operation = false;
            $this->multiseller_initialized = true;
            return $this;
        }
        
        $this->enable_multiseller_operation = $enabled;
        $this->multiseller_initialized = true; // Marcar como inicializado
        
        return $this;
    }

    /**
     * Define parametros adicionais da operacao multiseller
     *
     * @param array $params
     * @return self
     */
    public function setMultisellerParams(array $params): self
    {
        $this->multiseller_params = $params;
        return $this;
    }
    
    /**
     * Inicializa configurações multiseller se necessário
     * 
     * Carrega configurações do banco de dados e prepara o ambiente
     * para operações multiseller. Usa cache para evitar múltiplas consultas.
     * 
     * @return void
     */
    private function initializeMultisellerIfNeeded(): void
    {
        // Evitar múltiplas inicializações
        if ($this->multiseller_initialized) {
            return;
        }
        
        // Verificar feature flag
       
        if ( !\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1921-multiseller-freight-results')) {
            $this->enable_multiseller_operation = false;
            $this->multiseller_initialized = true;
            return;
        }
        
        // Carregar configuração do banco de dados
        try {
            $setting = $this->readonlydb->get_where('settings', [
                'name' => 'enable_multiseller_operation',
                'status' => 1
            ])->row_array();
            
            if ($setting && $setting['value'] === '1') {
                $this->enable_multiseller_operation = true;
            }
        } catch (Exception $e) {
            // Em caso de erro, manter desabilitado
            $this->enable_multiseller_operation = false;
        }
        
        $this->multiseller_initialized = true;
    }

    /**
     * Carrega configuracao de padronizacao de metodos de envio.
     *
     * Consulta primeiro no Redis e depois no banco de dados a configuracao
     * `multiseller_freight_results`. O resultado e armazenado nas propriedades
     * `$multiseller_freight_results` e `$marketplace_shipping_standardization_config`.
     *
     * @return void
     */
    private function loadFreightStandardizationConfig(): void
    {
        if ($this->marketplace_shipping_standardization_config !== null) {
            return; // ja carregado
        }

        $setting = $this->getSettingRedis($this->instance->redis, 'multiseller_freight_results');

        $this->multiseller_freight_results = false;
        $this->marketplace_shipping_standardization_config = null;

        if ($setting && isset($setting['status']) && $setting['status'] == 1) {
            $config = json_decode($setting['value'], true);
            if (is_array($config)) {
                $this->multiseller_freight_results = true;
                $this->marketplace_shipping_standardization_config = $config;
            }
        }
    }
    
        /**
     * Detecta se a cotação envolve múltiplos sellers baseado nos SKUs
     * 
     * Analisa os SKUs dos itens procurando pelo padrão 'S' que indica seller.
     * Exemplo: 'PROD123S456' onde '456' é o ID do seller.
     * 
     * @param array $items Lista de itens para cotação
     * @return bool True se múltiplos sellers foram detectados
     */
    private function detectMultipleSellers(array $items): bool
    {
        // Verificar feature flag primeiro
        
        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1921-multiseller-freight-results')) {
            return false;
        }
        
        $sellers = [];
        
        foreach ($items as $item) {
            if (!isset($item['sku'])) {
                continue;
            }
            
            $sku = $item['sku'];
            
            // Verificar se SKU contém padrão de seller (formato: PRODXSYYY)
            if (strpos($sku, 'S') !== false) {
                $parts = explode('S', $sku);
                if (count($parts) >= 2) {
                    // Extrair seller ID (parte após o 'S')
                    $seller = str_replace($parts[0], '', $sku);
                    $sellers[$seller] = true;
                }
            } else {
                // SKU sem seller específico = seller padrão
                $sellers['default'] = true;
            }
        }
        
        // Armazenar sellers detectados para uso posterior
        $this->detected_sellers_cache = array_keys($sellers);
        
        // Retornar true se mais de um seller foi detectado
        return count($sellers) > 1;
    }
    
    
    /**
     * Agrupa itens por seller usando dados otimizados da quotes_ship
     * 
     * @param array $items Array de itens do carrinho
     * @return array Array agrupado por seller
     */
    private function groupItemsBySellerOptimized(array $items): array
    {
        $sellerGroups = [];
        $sellerInfo = [];
        $statistics = [
            'total_items' => count($items),
            'found_in_quotes_ship' => 0,
            'fallback_used' => 0
        ];
        
        foreach ($items as $item) {
            if (!isset($item['sku'])) {
                error_log("CalculoFrete: Item sem SKU: " . json_encode($item));
                continue;
            }
            
            // Usar método otimizado
            $sellerData = $this->getSellerFromLogQuotes($item['sku']);
            $sellerId = $sellerData['seller_id'];
            
            // Estatísticas
            if ($sellerData['sku_info']['reliable']) {
                $statistics['found_in_quotes_ship']++;
            } else {
                $statistics['fallback_used']++;
            }
            
            // Agrupar por seller
            if (!isset($sellerGroups[$sellerId])) {
                $sellerGroups[$sellerId] = [];
                $sellerInfo[$sellerId] = [
                    'seller_id' => $sellerId,
                    'item_count' => 0,
                    'product_ids' => []
                ];
            }
            
            // Adicionar item com informações
            $item['seller_info'] = $sellerData;
            $sellerGroups[$sellerId][] = $item;
            
            // Atualizar info do seller
            $sellerInfo[$sellerId]['item_count']++;
            if ($sellerData['product_id']) {
                $sellerInfo[$sellerId]['product_ids'][] = $sellerData['product_id'];
            }
        }
        
        error_log("CalculoFrete: Agrupamento - " . count($sellerGroups) . " sellers, " . 
                "{$statistics['found_in_quotes_ship']}/{$statistics['total_items']} encontrados na quotes_ship");
        
        return [
            'groups' => $sellerGroups,
            'seller_info' => $sellerInfo,
            'statistics' => $statistics,
            'total_sellers' => count($sellerGroups),
            'total_items' => $statistics['total_items']
        ];
    }
    /**
     * Extrai seller ID do SKU
     */
    private function extractSellerFromSku(string $sku): string
    {
        if (preg_match('/S(\d+)/', $sku, $matches)) {
            return $matches[1];
        }

        return '';
    }
        /**
     * Verifica se execução paralela está disponível
     * 
     * Verifica se o sistema suporta execução paralela baseado em:
     * - Disponibilidade do Guzzle
     * - Feature flags
     * - Configurações do sistema
     * 
     * @return bool True se paralelismo está disponível
     */
    private function isParallelExecutionAvailable(): bool
    {
        try {
            // Verificar se feature flag de paralelismo está habilitada
            if (!\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1921-multiseller-freight-results')) {
                return false;
            }
            
            // Verificar se Guzzle está disponível
            if (!class_exists('\GuzzleHttp\Client')) {
                error_log("CalculoFrete: Guzzle não disponível para execução paralela");
                return false;
            }
            
            // Verificar se promises estão disponíveis
            if (!class_exists('\GuzzleHttp\Promise\Promise')) {
                error_log("CalculoFrete: Guzzle Promises não disponível");
                return false;
            }
            
            // Verificar configuração de timeout
            $maxExecutionTime = ini_get('max_execution_time');
            if ($maxExecutionTime > 0 && $maxExecutionTime < 30) {
                error_log("CalculoFrete: Timeout muito baixo para execução paralela: {$maxExecutionTime}s");
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("CalculoFrete: Erro ao verificar disponibilidade paralela: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Executa cotação tradicional (fallback)
     * @param array $mkt Dados do marketplace. Pode ser no formato
     *                   ['platform' => 'identificador'] ou [0 => 'identificador']
     * @param array $items
     * @param string|null $zipcode
     * @param bool $checkStock
     * @param bool $groupServices
     * @return array
     */
    private function executeTraditionalQuote(array $mkt, array $items, ?string $zipcode, bool $checkStock, bool $groupServices): array
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        $time_start = microtime(true);
        
        try {
            // Log para debugging
           get_instance()->log_data('batch', $log_name, "Executando cotação tradicional (fallback)", "I");
            
            // Validação básica do marketplace
            if (empty($mkt) || !is_array($mkt)) {
                return [
                    'success' => false,
                    'data' => [
                        'message' => 'Parâmetro marketplace inválido',
                        'execution_time' => microtime(true) - $time_start,
                        'execution_mode' => 'traditional_error'
                    ]
                ];
            }
            
            // Extrair identificador do marketplace
            if (array_key_exists('platform', $mkt) && !empty($mkt['platform'])) {
                $marketplace = $mkt['platform'];
            } elseif (array_key_exists(0, $mkt) && !empty($mkt[0])) {
                $marketplace = $mkt[0];
            } else {
                $marketplace = '';
            }
            
            if (empty($marketplace)) {
                return [
                    'success' => false,
                    'data' => [
                        'message' => 'Marketplace não informado',
                        'execution_time' => microtime(true) - $time_start,
                        'execution_mode' => 'traditional_error'
                    ]
                ];
            }
            
            // Validação de itens
            if (empty($items) || !is_array($items)) {
                return [
                    'success' => false,
                    'data' => [
                        'message' => 'Itens não informados ou inválidos',
                        'execution_time' => microtime(true) - $time_start,
                        'execution_mode' => 'traditional_error'
                    ]
                ];
            }
            
            // Validação de CEP
            if (empty($zipcode)) {
                return [
                    'success' => false,
                    'data' => [
                        'message' => 'CEP não informado',
                        'execution_time' => microtime(true) - $time_start,
                        'execution_mode' => 'traditional_error'
                    ]
                ];
            }
            
            // Instanciar Logistic com parâmetro correto
            $logistic = new Logistic($marketplace);
            
            // Executar cotação com parâmetros corretos
            $result = $logistic->getQuote($items, $zipcode, $checkStock, $groupServices);
            
            // Verificar se resultado é válido
            if (!is_array($result)) {
                get_instance()->log_data('batch', $log_name, "Resultado da cotação não é array válido", "E");
                return [
                    'success' => false,
                    'data' => [
                        'message' => 'Erro interno na cotação tradicional',
                        'execution_time' => microtime(true) - $time_start,
                        'execution_mode' => 'traditional_error'
                    ]
                ];
            }
            
            // Adicionar informações de timing e modo de execução
            $executionTime = microtime(true) - $time_start;
            
            if (!isset($result['data'])) {
                $result['data'] = [];
            }
            
            $result['data']['execution_time'] = round($executionTime, 3);
            $result['data']['execution_mode'] = 'traditional';
            
            get_instance()->log_data('batch', $log_name, 
                "Cotação tradicional executada com sucesso em " . round($executionTime, 3) . "s", "I");
            
            return $result;
            
        } catch (Exception $e) {
            $executionTime = microtime(true) - $time_start;
            
            get_instance()->log_data('batch', $log_name, 
                "Erro na cotação tradicional: " . $e->getMessage(), "E");
            
            return [
                'success' => false,
                'data' => [
                    'message' => 'Erro interno na cotação: ' . $e->getMessage(),
                    'execution_time' => round($executionTime, 3),
                    'execution_mode' => 'traditional_error'
                ]
            ];
        }
    }
    
       /**
     * Executa cotação multiseller com paralelismo
     * 
     * Coordena a execução de cotações para múltiplos sellers,
     * utilizando execução paralela quando possível.
     * 
     * @param array $mkt Dados do marketplace
     * @param array $items Lista de itens
     * @param string|null $zipcode CEP de destino
     * @param bool $checkStock Verificar estoque
     * @param bool $groupServices Agrupar serviços
     * @return array Resultado agregado
     */
    private function executeMultisellerQuote(array $mkt, array $items, ?string $zipcode, bool $checkStock, bool $groupServices): array
    {
        $time_start = microtime(true);
        
        try {
            // Agrupar itens por seller
            $sellerGroups = $this->groupItemsBySellerFromLogQuotes($items);
            
            // Log para debugging
            $sellerCount = count($sellerGroups);
            error_log("CalculoFrete: Executando cotação multiseller para {$sellerCount} sellers");
            
            // Se só tem um seller, usar cotação tradicional
            if ($sellerCount <= 1) {
                error_log("CalculoFrete: Apenas um seller detectado, usando cotação tradicional");
                return $this->executeTraditionalQuote($mkt, $items, $zipcode, $checkStock, $groupServices);
            }
            
            // Verificar se paralelismo está disponível
            if ($this->isParallelExecutionAvailable() && $sellerCount > 1) {
                error_log("CalculoFrete: Usando execução paralela para {$sellerCount} sellers");
                return $this->executeParallelMultisellerQuote($mkt, $sellerGroups, $zipcode, $checkStock, $groupServices);
            } else {
                error_log("CalculoFrete: Usando execução sequencial para {$sellerCount} sellers");
                return $this->executeSequentialMultisellerQuote($mkt, $sellerGroups, $zipcode, $checkStock, $groupServices);
            }
            
        } catch (Exception $e) {
            // Fallback para execução tradicional
            error_log("CalculoFrete: Erro na cotação multiseller, fallback para tradicional: " . $e->getMessage());
            return $this->executeTraditionalQuote($mkt, $items, $zipcode, $checkStock, $groupServices);
        }
    }
    
        /**
     * Executa cotações multiseller em paralelo
     * 
     * Utiliza Guzzle promises para executar cotações de múltiplos
     * sellers simultaneamente, melhorando significativamente a performance.
     * 
     * @param array $mkt Dados do marketplace
     * @param array $sellerGroups Itens agrupados por seller
     * @param string|null $zipcode CEP de destino
     * @param bool $checkStock Verificar estoque
     * @param bool $groupServices Agrupar serviços
     * @return array Resultado agregado
     */
    private function executeParallelMultisellerQuote(array $mkt, array $sellerGroups, ?string $zipcode, bool $checkStock, bool $groupServices): array
    {
        $time_start = microtime(true);
        
        try {
            
            $promises = [];
            
            // Criar promises para cada seller
            foreach ($sellerGroups as $seller => $sellerItems) {
                try {
                    // Preparar dados da cotação com argumentos corretos
                
                    $dataQuote = $this->prepareQuoteData($mkt, $sellerItems, $this->validationResult, $seller, $zipcode, $checkStock, $groupServices);
                    if (!$dataQuote || !isset($dataQuote['dataQuote'])) {
                        throw new Exception("Erro ao preparar dados de cotação para seller $seller");
                    }
                    
                    // Instanciar logística corretamente
                   $this->instanceLogistic($dataQuote['store_integration'], $dataQuote['store_id'], $mkt, false);
                    
                    if (!$this->logistic) {
                        throw new Exception("Erro ao instanciar logística para seller $seller");
                    }
                    
                    // Executar cotação assíncrona
                    $asyncResult = $this->logistic->getQuoteAsync($dataQuote['dataQuote'], false);
                    
                    // Verificar se é Promise ou resultado direto
                    if ($asyncResult instanceof PromiseInterface) {
                        $promises[$seller] = $asyncResult->then(
                            function ($result) use ($seller, $dataQuote) {
                                return [
                                    'seller' => $seller,
                                    'store_id' => $dataQuote['store_id'],
                                    'result' => $result,
                                    'status' => 'success'
                                ];
                            },
                            function ($error) use ($seller) {
                                return [
                                    'seller' => $seller,
                                    'error' => $error,
                                    'status' => 'error'
                                ];
                            }
                        );
                    } else {
                        // Resultado síncrono - converter para Promise
                        $promises[$seller] = Create::promiseFor([
                            'seller' => $seller,
                            'store_id' => $dataQuote['store_id'],
                            'result' => $asyncResult,
                            'status' => 'success'
                        ]);
                    }
                    
                } catch (Exception $e) {
                    error_log("Erro na cotação do seller $seller: " . $e->getMessage());
                    
                    // Adicionar erro como Promise rejeitada
                        $promises[] = Create::rejectionFor([
                        'seller' => $seller, 
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            $processedResults = [];
            foreach ($promises as $sellerKey => $promise) {
                try {
                    $result = $this->waitPromiseWithTimeout($promise, rand(10, 15));

                    if (empty($result['result']['data']['shipping_methods'] ?? [])) {
                        throw new Exception('Nenhum frete retornado');
                    }

                    $processedResults[$sellerKey] = $result;
                } catch (Exception $e) {
                    error_log("CalculoFrete: Timeout ou erro na promise do seller {$sellerKey}: " . $e->getMessage());

                    return [
                        'success' => false,
                        'data' => [
                            'message' => 'Falha na execução paralela do seller ' . $sellerKey,
                            'seller_id' => $sellerKey,
                            'execution_mode' => 'parallel_timeout',
                            'error' => $e->getMessage()
                        ]
                    ];
                }
            }
            
            // Agregar resultados
            $executionTime = microtime(true) - $time_start;
            return $this->aggregateMultisellerResults($processedResults, $executionTime);
            
        } catch (Exception $e) {
            error_log("CalculoFrete: Erro crítico na execução paralela: " . $e->getMessage());
            
            // Fallback para execução sequencial
            return $this->executeSequentialMultisellerQuote($mkt, $sellerGroups, $zipcode, $checkStock, $groupServices);
        }
    }
    
        /**
     * Executa cotações multiseller sequencialmente
     * 
     * Executa cotações de múltiplos sellers uma por vez,
     * usado quando paralelismo não está disponível.
     * 
     * @param array $mkt Dados do marketplace
     * @param array $sellerGroups Itens agrupados por seller
     * @param string|null $zipcode CEP de destino
     * @param bool $checkStock Verificar estoque
     * @param bool $groupServices Agrupar serviços
     * @return array Resultado agregado
     */
    private function executeSequentialMultisellerQuote(array $mkt, array $sellerGroups, ?string $zipcode, bool $checkStock, bool $groupServices): array
    {
        $time_start = microtime(true);
        $results = [];
        
        try {
            foreach ($sellerGroups as $seller => $sellerItems) {
                $sellerStartTime = microtime(true);
                
                try {
                    // Preparar dados primeiro
                $preparedData = $this->prepareQuoteData(
                    $mkt,
                    $sellerItems,
                    $this->validationResult,
                    $seller,
                    $zipcode,
                    $checkStock,
                    $groupServices
                );
                $this->instanceLogistic(
                    $preparedData['store_integration'],
                    $preparedData['seller_info']['seller_id'],
                    $preparedData['dataQuote'],
                    true
                );
                // Depois chamar com dados preparados
                $result = $this->executeSingleSellerQuote($preparedData);
                    
                    // Adicionar timing específico do seller
                    if (isset($result['data'])) {
                        $result['data']['seller_execution_time'] = round(microtime(true) - $sellerStartTime, 3);
                        $result['data']['seller_execution_mode'] = 'sequential';
                    }
                    
                    $results[$seller] = $result;
                    
                } catch (Exception $e) {
                    error_log("CalculoFrete: Erro na cotação sequencial do seller {$seller}: " . $e->getMessage());
                    
                    $results[$seller] = [
                        'success' => false,
                        'data' => [
                            'message' => 'Erro na cotação do seller ' . $seller,
                            'seller_id' => $seller,
                            'seller_execution_time' => round(microtime(true) - $sellerStartTime, 3),
                            'seller_execution_mode' => 'sequential_error',
                            'error' => $e->getMessage()
                        ]
                    ];
                }
            }
            
            // Agregar resultados
            $executionTime = microtime(true) - $time_start;
            return $this->aggregateMultisellerResults($results, $executionTime);
            
        } catch (Exception $e) {
            error_log("CalculoFrete: Erro crítico na execução sequencial: " . $e->getMessage());
            
            return [
                'success' => false,
                'data' => [
                    'message' => 'Erro crítico na cotação multiseller sequencial',
                    'execution_time' => microtime(true) - $time_start,
                    'execution_mode' => 'sequential_critical_error',
                    'error' => $e->getMessage()
                ]
            ];
        }
    }
    
        /**
     * Executa cotação para um seller específico
     * 
     * Executa cotação para itens de um seller específico,
     * usado tanto na execução paralela quanto sequencial.
     * 
     * @param array $mkt Dados do marketplace
     * @param array $sellerItems Itens do seller específico
     * @param string|null $zipcode CEP de destino
     * @param bool $checkStock Verificar estoque
     * @param bool $groupServices Agrupar serviços
     * @param string $seller ID do seller
     * @return array Resultado da cotação do seller
     */
    private function executeSingleSellerQuote(array $preparedData): array
    {
        // VALIDAÇÃO ROBUSTA NO INÍCIO:
        if (!isset($preparedData['seller_info']) || !is_array($preparedData['seller_info'])) {
            error_log("executeSingleSellerQuote: seller_info ausente nos dados preparados");
            error_log("preparedData keys: " . implode(', ', array_keys($preparedData)));
            
            return [
                'success' => false,
                'data' => [
                    'message' => 'Erro: Informações do seller não encontradas nos dados preparados',
                    'error_type' => 'missing_seller_info',
                    'available_keys' => array_keys($preparedData)
                ]
            ];
        }
        
        if (!isset($preparedData['seller_info']['seller_id'])) {
            error_log("executeSingleSellerQuote: seller_id ausente em seller_info");
            
            return [
                'success' => false,
                'data' => [
                    'message' => 'Erro: ID do seller não encontrado',
                    'error_type' => 'missing_seller_id'
                ]
            ];
        }
        
        $sellerId = $preparedData['seller_info']['seller_id'];
        error_log("=== executeSingleSellerQuote Seller: {$sellerId} ===");
        
        try {
            // Validar dados preparados
            if (!isset($preparedData['logistic']) || !$preparedData['logistic']) {
                throw new Exception("Configuração logística não encontrada para seller {$sellerId}");
            }
            
            $logisticConfig = $preparedData['logistic'];
            error_log("executeSingleSellerQuote: Configuração logística: " . json_encode($logisticConfig));
            
            // Instanciar Logistic com tratamento de erro
            try {
                // Verificar se já existe instância
                if (!isset($this->logistic)) {
                    error_log("executeSingleSellerQuote: Criando nova instância Logistic para seller {$sellerId}");
                    $this->logistic = new Logistic($logisticConfig);
                } else {
                    error_log("executeSingleSellerQuote: Atualizando configuração Logistic para seller {$sellerId}");
                    
                    // Verificar se método updateConfig existe
                    if (method_exists($this->logistic, 'updateConfig')) {
                        $this->logistic->updateConfig($logisticConfig);
                    } else {
                        // Recriar instância se não há método de atualização
                        error_log("executeSingleSellerQuote: Recriando instância Logistic (updateConfig não existe)");
                        $this->logistic = new Logistic($logisticConfig);
                    }
                }
                
            } catch (Exception $logisticException) {
                error_log("executeSingleSellerQuote: Erro ao instanciar Logistic: " . $logisticException->getMessage());
                
                // Tentar com configuração padrão
                $defaultConfig = [
                    'type' => 'sellercenter',
                    'seller' => false,
                    'sellercenter' => true
                ];
                
                error_log("executeSingleSellerQuote: Tentando com configuração padrão");
                $this->logistic = new Logistic($defaultConfig);
            }
            
            error_log("executeSingleSellerQuote: Logistic instanciada com sucesso para seller {$sellerId}");
            
            // Chamar getQuote com dados preparados
            error_log("executeSingleSellerQuote: Chamando getQuote para seller {$sellerId}");
            
            $quote = $this->logistic->getQuote(
                $preparedData['dataQuote'],
                $preparedData['zipCodeSeller'],
                $preparedData['cross_docking'],
                $preparedData['totalPrice'],
                $preparedData['store_integration'],
                $preparedData['arrDataAd'],
                $preparedData['dataSkus'],
                $preparedData['checkStock'],
                $preparedData['groupServices']
            );
            
            error_log("executeSingleSellerQuote: getQuote executado para seller {$sellerId}");
            error_log("executeSingleSellerQuote: Resultado: " . json_encode($quote));
            
            // Verificar resultado
            if (!$quote || !isset($quote['success'])) {
                throw new Exception("Resultado inválido da cotação para seller {$sellerId}");
            }
            
            return $quote;
            
        } catch (Exception $e) {
            error_log("executeSingleSellerQuote: Erro seller {$sellerId}: " . $e->getMessage());
            error_log("executeSingleSellerQuote: Stack trace: " . $e->getTraceAsString());
            
            return [
                'success' => false,
                'data' => [
                    'message' => 'Erro na cotação do seller ' . $sellerId . ': ' . $e->getMessage(),
                    'seller_id' => $sellerId,
                    'error_type' => 'execution_error',
                    'error_details' => [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'code' => $e->getCode()
                    ]
                ]
            ];
        }
    }
    
        /**
     * Agrega resultados de múltiplos sellers
     * 
     * Combina resultados de cotações de múltiplos sellers em um
     * resultado único, organizando por métodos de envio e calculando
     * totais consolidados.
     * 
     * @param array $results Resultados por seller
     * @param float $executionTime Tempo total de execução
     * @return array Resultado agregado final
     */
    private function aggregateMultisellerResults(array $results, float $executionTime = 0): array
    {
        try {
            $aggregatedData = [
                'success' => true,
                'execution_time' => round($executionTime, 3),
                'execution_mode' => 'multiseller',
                'seller_count' => count($results),
                'sellers' => [],
                'shipping_methods' => [],
                'summary' => [
                    'total_sellers' => count($results),
                    'successful_sellers' => 0,
                    'failed_sellers' => 0,
                    'total_shipping_options' => 0
                ]
            ];
            
            $allShippingMethods = [];
            $allSuccessful = true;

            // Regra de agregacao
            $rule = $this->multiseller_params['rule'] ?? 'menor_preco';
            error_log("aggregateMultisellerResults: regra utilizada - {$rule}");

            $chosenMethod = null;
            
            // Processar resultados de cada seller
            foreach ($results as $seller => $result) {
                $sellerData = [
                    'seller_id' => $seller,
                    'success' => $result['success'] ?? false,
                    'execution_time' => $result['data']['seller_execution_time'] ?? 0,
                    'execution_mode' => $result['data']['seller_execution_mode'] ?? 'unknown'
                ];
                
                if ($result['success'] && isset($result['data']['shipping_methods'])) {
                    $aggregatedData['summary']['successful_sellers']++;
                    
                    $sellerData['shipping_methods'] = $result['data']['shipping_methods'];
                    $sellerData['item_count'] = $result['data']['seller_item_count'] ?? 0;
                    
                    // Agregar métodos de envio
                    foreach ($result['data']['shipping_methods'] as $method) {
                        $methodKey = $method['name'] ?? 'unknown';
                        
                        if (!isset($allShippingMethods[$methodKey])) {
                            $allShippingMethods[$methodKey] = [
                                'name' => $method['name'] ?? 'Método Desconhecido',
                                'price' => 0,
                                'deadline' => 0,
                                'sellers' => [],
                                'total_price' => 0,
                                'max_deadline' => 0
                            ];
                        }
                        
                        $allShippingMethods[$methodKey]['sellers'][] = [
                            'seller_id' => $seller,
                            'price' => $method['price'] ?? 0,
                            'deadline' => $method['deadline'] ?? 0
                        ];
                        
                        $allShippingMethods[$methodKey]['total_price'] += ($method['price'] ?? 0);
                        $allShippingMethods[$methodKey]['max_deadline'] = max(
                            $allShippingMethods[$methodKey]['max_deadline'],
                            $method['deadline'] ?? 0
                        );
                    }
                    
                    $aggregatedData['summary']['total_shipping_options'] += count($result['data']['shipping_methods']);
                    
                } else {
                    $aggregatedData['summary']['failed_sellers']++;
                    $sellerData['error'] = $result['data']['message'] ?? 'Erro desconhecido';
                    $allSuccessful = false;
                }
                
                $aggregatedData['sellers'][] = $sellerData;
            }
            
            // Finalizar métodos de envio agregados
            foreach ($allShippingMethods as $methodKey => $method) {
                $methodData = [
                    'name' => $method['name'],
                    'total_price' => round($method['total_price'], 2),
                    'max_deadline' => $method['max_deadline'],
                    'seller_count' => count($method['sellers']),
                    'sellers' => $method['sellers']
                ];

                $aggregatedData['shipping_methods'][] = $methodData;

                if ($rule === 'menor_preco') {
                    if ($chosenMethod === null || $methodData['total_price'] < $chosenMethod['total_price']) {
                        $chosenMethod = $methodData;
                    }
                } else { // menor_prazo
                    if ($chosenMethod === null || $methodData['max_deadline'] < $chosenMethod['max_deadline']) {
                        $chosenMethod = $methodData;
                    }
                }
            }

            if ($chosenMethod !== null) {
                $aggregatedData['chosen_shipping'] = $chosenMethod;
                error_log("aggregateMultisellerResults: metodo escolhido - {$chosenMethod['name']} preco {$chosenMethod['total_price']} prazo {$chosenMethod['max_deadline']}");
            }
            
            // Determinar sucesso geral
            $aggregatedData['success'] = $allSuccessful;

            if (!$allSuccessful) {
                $aggregatedData['message'] = 'Não há cotação de frete disponível para este carrinho';
            }
            
            // Log para debugging
            error_log("CalculoFrete: Agregação concluída - {$aggregatedData['summary']['successful_sellers']} sucessos, {$aggregatedData['summary']['failed_sellers']} falhas");
            
            return [
                'success' => $aggregatedData['success'],
                'data' => $aggregatedData
            ];
            
        } catch (Exception $e) {
            error_log("CalculoFrete: Erro na agregação de resultados: " . $e->getMessage());
            
            return [
                'success' => false,
                'data' => [
                    'message' => 'Erro na agregação de resultados multiseller',
                    'execution_time' => round($executionTime, 3),
                    'execution_mode' => 'multiseller_aggregation_error',
                    'error' => $e->getMessage()
                ]
            ];
        }
    }

    private function waitPromiseWithTimeout(PromiseInterface $promise, int $seconds)
    {
        if (function_exists('GuzzleHttp\\Promise\\timeout')) {
            return \GuzzleHttp\Promise\timeout($promise, $seconds)->wait();
        }

        if (!function_exists('pcntl_alarm')) {
            return $promise->wait();
        }

        pcntl_async_signals(true);
        $timedOut = false;
        pcntl_signal(SIGALRM, function () use (&$timedOut) {
            $timedOut = true;
            throw new \RuntimeException('Promise timeout');
        });
        pcntl_alarm($seconds);

        try {
            $result = $promise->wait();
            pcntl_alarm(0);
            return $result;
        } catch (\Throwable $e) {
            pcntl_alarm(0);
            if ($timedOut) {
                throw new \RuntimeException('Promise timeout');
            }
            throw $e;
        }
    }

    /**
     * Valida e corrige configuração logística
     *
     * @param array $logisticConfig Configuração logística
     * @param string $sellerId ID do seller
     * @return array Configuração validada
     */
    private function validateLogisticConfig(array $logisticConfig, string $sellerId): array
    {
        error_log("validateLogisticConfig: Validando configuração para seller {$sellerId}");
        
        // Configuração padrão
        $defaultConfig = [
            'type' => 'sellercenter',
            'seller' => false,
            'sellercenter' => true,
            'store_id' => $sellerId
        ];
        
        // Mesclar com configuração padrão
        $validatedConfig = array_merge($defaultConfig, $logisticConfig);
        
        // Validações específicas
        if (empty($validatedConfig['type'])) {
            $validatedConfig['type'] = 'sellercenter';
        }
        
        if (!isset($validatedConfig['seller'])) {
            $validatedConfig['seller'] = false;
        }
        
        if (!isset($validatedConfig['sellercenter'])) {
            $validatedConfig['sellercenter'] = true;
        }
        
        error_log("validateLogisticConfig: Configuração validada: " . json_encode($validatedConfig));
        
        return $validatedConfig;
    }
        /**
     * Prepara dados da cotação no formato esperado pelo Logistic
     * 
     * Converte os parâmetros do formatQuote para o formato esperado
     * pelo método getQuote() da classe Logistic.
     * 
     * @param array $mkt Dados do marketplace
     * @param array $items Lista de itens
     * @param string|null $zipcode CEP de destino
     * @param bool $checkStock Verificar estoque
     * @param bool $groupServices Agrupar serviços
     * @return array Dados formatados para getQuote
     */
    /**
     * Prepara dados para cotação individual de um seller
     */
    private function prepareQuoteData(array $mkt, array $sellerItems, array $validationResult, string $sellerId, ?string $zipcode, bool $checkStock, bool $groupServices): array
    {
        error_log("=== prepareQuoteData Seller: {$sellerId} ===");
        
        // Extrair dados de validação
        $arrDataAd = $validationResult['arrDataAd'];
        $dataSkus = $validationResult['dataSkus'];
        $originalDataQuote = $validationResult['dataQuote'];
        
        // Preparar dados específicos do seller
        $sellerDataQuote = [
            'zipcodeRecipient' => $zipcode,
            'items' => []
        ];
        
        $sellerArrDataAd = [];
        $sellerDataSkus = [];
        $totalPrice = 0;
        $cross_docking = 0;
        $zipCodeSeller = null;
        $store_integration = null;
        $logistic = null;
        $firstItemData = null;
        
        // Processar items do seller
        foreach ($sellerItems as $item) {
            $sku = $item['sku'];
            
            // Verificar se item existe nos dados validados
            if (!isset($arrDataAd[$sku])) {
                error_log("prepareQuoteData: SKU {$sku} não encontrado em arrDataAd");
                continue;
            }
            
            $itemData = $arrDataAd[$sku];
            
            // Verificar se pertence ao seller correto
            if ($itemData['store_id'] != $sellerId) {
                error_log("prepareQuoteData: SKU {$sku} não pertence ao seller {$sellerId}");
                continue;
            }
            
            // Guardar primeiro item para configuração
            if ($firstItemData === null) {
                $firstItemData = $itemData;
            }
            
            // Adicionar aos dados do seller
            $sellerArrDataAd[$sku] = $itemData;
            $sellerDataSkus[$sku] = $dataSkus[$sku];
            
            // Encontrar item correspondente no dataQuote original
            foreach ($originalDataQuote['items'] as $quotedItem) {
                if ($quotedItem['sku'] === $sku) {
                    $sellerDataQuote['items'][] = $quotedItem;
                    $totalPrice += $quotedItem['valor'];
                    break;
                }
            }
            
            // Calcular crossdocking (pior tempo)
            if (isset($itemData['crossdocking'])) {
                $itemCrossdocking = (int)$itemData['crossdocking'];
                if ($itemCrossdocking > $cross_docking) {
                    $cross_docking = $itemCrossdocking;
                }
            }
        }
        
        // Validar se encontrou items
        if (empty($sellerDataQuote['items']) || $firstItemData === null) {
            throw new Exception("Nenhum item válido encontrado para seller {$sellerId}");
        }
        
        // Configurar dados do seller usando primeiro item
        $zipCodeSeller = $firstItemData['zipcode'];
        
        // Obter integração da loja
        try {
            $store_integration = $this->getStoreIntegrationStore($firstItemData['store_id']);
            error_log("prepareQuoteData: store_integration para seller {$sellerId}: " . $store_integration);
        } catch (Exception $e) {
            error_log("prepareQuoteData: Erro ao obter store_integration para seller {$sellerId}: " . $e->getMessage());
            $store_integration = 'default'; // Fallback
        }
        
        // Obter configuração logística com validação robusta
        try {
            $logisticConfig = [
                'freight_seller' => $firstItemData['freight_seller'] ?? 0,
                'freight_seller_type' => $firstItemData['freight_seller_type'] ?? null,
                'store_id' => $firstItemData['store_id']
            ];
            
            error_log("prepareQuoteData: Configuração logística para seller {$sellerId}: " . json_encode($logisticConfig));
            
            $logistic = $this->getLogisticStore($logisticConfig);
            
            // Validar se logistic foi obtida corretamente
            if (!$logistic || !is_array($logistic)) {
                error_log("prepareQuoteData: getLogisticStore retornou dados inválidos para seller {$sellerId}");
                throw new Exception("Configuração logística inválida");
            }
            
            // Verificar campos obrigatórios
            if (!isset($logistic['type'])) {
                error_log("prepareQuoteData: Campo 'type' ausente na configuração logística");
                $logistic['type'] = 'sellercenter'; // Fallback padrão
            }
            
            error_log("prepareQuoteData: Configuração logística válida para seller {$sellerId}: " . json_encode($logistic));
            
        } catch (Exception $e) {
            error_log("prepareQuoteData: Erro ao obter configuração logística para seller {$sellerId}: " . $e->getMessage());
            
            // Configuração logística padrão como fallback
            $logistic = [
                'type' => 'sellercenter',
                'seller' => false,
                'sellercenter' => true,
                'store_id' => $sellerId
            ];
            
            error_log("prepareQuoteData: Usando configuração logística padrão para seller {$sellerId}");
        }
        
        // Validações finais
        if ($zipCodeSeller === null) {
            throw new Exception("CEP do seller {$sellerId} não encontrado");
        }
        
        error_log("prepareQuoteData Seller {$sellerId}: " . count($sellerDataQuote['items']) . " items, Total: R$ {$totalPrice}");
        // GARANTIR que seller_info sempre existe:
        $sellerInfo = [
            'seller_id' => $sellerId,
            'items_count' => count($sellerDataQuote['items']),
            'total_price' => $totalPrice,
            'crossdocking_days' => $cross_docking,
            'zipcode_seller' => $zipCodeSeller,
            'integration_type' => $store_integration
        ];
        return [
            // Dados principais para getQuote()
            'dataQuote' => $sellerDataQuote,
            'zipCodeSeller' => $zipCodeSeller,
            'cross_docking' => $cross_docking,
            'totalPrice' => $totalPrice,
            'store_integration' => $store_integration,
            'arrDataAd' => $sellerArrDataAd,
            'dataSkus' => $sellerDataSkus,
            'checkStock' => $checkStock,
            'groupServices' => $groupServices,
            
            // Configuração logística validada
            'logistic' => $logistic,
            
            // Dados adicionais para contexto
           'seller_info' => $sellerInfo,
        ];
    }

     /**
     * Recupera informações do seller e produto da tabela log_quotes
     * 
     * @param string $sku SKU do produto
     * @return array Informações do seller e produto
     */
    private function getSellerFromLogQuotes(string $sku): array
    {
        try {
            // Validação de entrada
            if (empty($sku) || !is_string($sku)) {
                error_log("CalculoFrete: SKU inválido: " . var_export($sku, true));
                return $this->getDefaultSellerInfo($sku, 'invalid_sku');
            }

            error_log("CalculoFrete: Consultando log_quotes para SKU: {$sku}");

            // Consulta na log_quotes (dados reais de cotações)
            $query = $this->readonlydb->select('
                    lq.skumkt,
                    lq.product_id,
                    lq.store_id,
                    lq.seller_id,
                    lq.marketplace,
                    lq.integration,
                    lq.success,
                    lq.created_at,
                    lq.updated_at
                ')
                ->where('lq.skumkt', $sku)
                ->where('lq.success', 1)  // Apenas cotações bem-sucedidas
                ->order_by('lq.updated_at', 'DESC')
                ->limit(1)
                ->get('log_quotes lq');

            // Verificar se a consulta foi bem-sucedida
            if ($query === false) {
                error_log("CalculoFrete: Erro na consulta log_quotes para SKU {$sku}");
                return $this->getDefaultSellerInfo($sku, 'query_failed');
            }

            // Verificar se há resultados
            if ($query->num_rows() === 0) {
                error_log("CalculoFrete: SKU {$sku} não encontrado na log_quotes");
                return $this->getDefaultSellerInfo($sku, 'not_found_in_log_quotes');
            }

            // Obter dados
            $logData = $query->row_array();

            // Validar dados retornados
            if (!$logData || !isset($logData['store_id']) || !isset($logData['product_id'])) {
                error_log("CalculoFrete: Dados inválidos da log_quotes para SKU {$sku}");
                return $this->getDefaultSellerInfo($sku, 'invalid_data_returned');
            }

            // Usar store_id como seller_id se seller_id estiver vazio (conforme saveLogQuotes)
            $sellerId = !empty($logData['seller_id']) ? $logData['seller_id'] : $logData['store_id'];

            error_log("CalculoFrete: Dados encontrados - SKU: {$sku}, Product: {$logData['product_id']}, Store: {$logData['store_id']}, Seller: {$sellerId}");

            return [
                'seller_id' => (string) $sellerId,
                'product_id' => (string) $logData['product_id'],
                'sku_info' => [
                    'original_sku' => $sku,
                    'data_source' => 'log_quotes_optimized',
                    'extraction_method' => 'database_log_quotes',
                    'marketplace' => $logData['marketplace'],
                    'integration' => $logData['integration'],
                    'store_id' => $logData['store_id'],
                    'last_updated' => $logData['updated_at'],
                    'reliable' => true,
                    'complete_data' => true
                ]
            ];

        } catch (Exception $e) {
            error_log("CalculoFrete: Exceção em getSellerFromLogQuotes para SKU {$sku}: " . $e->getMessage());
            return $this->getDefaultSellerInfo($sku, 'exception_occurred');
        }
    }

    /**
     * Analisa requisição multiseller usando dados de validItemsQuote()
     * Usa dados já processados em vez de reprocessar
     * 
     * @param array $mkt Dados do marketplace
     * @param array $items Itens do carrinho (para compatibilidade)
     * @param array $validationResult Resultado do validItemsQuote()
     * @param string|null $zipcode CEP de destino
     * @return array Análise da requisição
     */
    private function analyzeMultisellerRequestOptimized(array $mkt, array $items, array $validationResult, ?string $zipcode): array
    {
        error_log("=== ANÁLISE MULTISELLER OTIMIZADA (validItemsQuote) ===");
        
        // Usar dados já processados de validItemsQuote
        $multisellerInfo = $validationResult['multiseller_info'];
        $storeIds = $validationResult['storeIds'];
        $arrDataAd = $validationResult['arrDataAd'];
        $dataQuote = $validationResult['dataQuote'];
        
        $isMultiseller = false;
        $totalSellers = 1;
        $sellerGroups = [];
        $sellerInfo = [];
        
        if ($multisellerInfo && $multisellerInfo['is_multiseller']) {
            // É multiseller - usar dados de validItemsQuote
            $totalSellers = count($storeIds);
            $isMultiseller = true;
            
            error_log("CalculoFrete: Multiseller detectado via validItemsQuote - {$totalSellers} stores");
            
            // Converter estrutura de validItemsQuote para formato esperado
            foreach ($multisellerInfo['items_by_store'] as $storeId => $skus) {
                $sellerGroups[$storeId] = [];
                $sellerInfo[$storeId] = [
                    'seller_id' => $storeId,
                    'item_count' => count($skus),
                    'product_ids' => [],
                    'data_source' => 'validItemsQuote'
                ];
                
                foreach ($skus as $sku) {
                    // Encontrar item original nos dados processados
                    foreach ($dataQuote['items'] as $quotedItem) {
                        if ($quotedItem['sku'] === $sku) {
                            // Adicionar informações do seller baseadas em validItemsQuote
                            $quotedItem['seller_info'] = [
                                'seller_id' => $storeId,
                                'product_id' => $arrDataAd[$sku]['prd_id'] ?? null,
                                'sku_info' => [
                                    'original_sku' => $sku,
                                    'data_source' => 'validItemsQuote_optimized',
                                    'extraction_method' => 'sellercenter_last_post',
                                    'store_id' => $storeId,
                                    'reliable' => true,
                                    'complete_data' => true
                                ]
                            ];
                            
                            $sellerGroups[$storeId][] = $quotedItem;
                            
                            // Adicionar product_id se disponível
                            if (isset($arrDataAd[$sku]['prd_id'])) {
                                $sellerInfo[$storeId]['product_ids'][] = $arrDataAd[$sku]['prd_id'];
                            }
                            
                            break;
                        }
                    }
                }
            }
            
        } else {
            // Não é multiseller - usar dados tradicionais
            $storeId = $validationResult['storeId'];
            $totalSellers = 1;
            $isMultiseller = false;
            
            error_log("CalculoFrete: Seller único detectado via validItemsQuote - Store: {$storeId}");
            
            // Criar grupo único
            $sellerGroups[$storeId] = [];
            $sellerInfo[$storeId] = [
                'seller_id' => $storeId,
                'item_count' => count($dataQuote['items']),
                'product_ids' => [],
                'data_source' => 'validItemsQuote'
            ];
            
            foreach ($dataQuote['items'] as $quotedItem) {
                // Adicionar informações do seller
                $quotedItem['seller_info'] = [
                    'seller_id' => $storeId,
                    'product_id' => $arrDataAd[$quotedItem['sku']]['prd_id'] ?? null,
                    'sku_info' => [
                        'original_sku' => $quotedItem['sku'],
                        'data_source' => 'validItemsQuote_single',
                        'extraction_method' => 'sellercenter_last_post',
                        'store_id' => $storeId,
                        'reliable' => true,
                        'complete_data' => true
                    ]
                ];
                
                $sellerGroups[$storeId][] = $quotedItem;
                
                // Adicionar product_id se disponível
                if (isset($arrDataAd[$quotedItem['sku']]['prd_id'])) {
                    $sellerInfo[$storeId]['product_ids'][] = $arrDataAd[$quotedItem['sku']]['prd_id'];
                }
            }
        }
        
        // Determinar estratégia de execução
        $shouldParallelize = false;
        if ($isMultiseller) {
            $parallelAvailable = $this->isParallelExecutionAvailable();
            $worthParallelizing = $totalSellers >= 2;
            $shouldParallelize = $parallelAvailable && $worthParallelizing;
        }
        
        $result = [
            'is_multiseller' => $isMultiseller,
            'should_parallelize' => $shouldParallelize,
            'total_sellers' => $totalSellers,
            'seller_groups' => $sellerGroups,
            'seller_info' => $sellerInfo,
            'store_ids' => $storeIds,
            'statistics' => [
                'total_items' => count($items),
                'found_in_validation' => count($arrDataAd),
                'data_source' => 'validItemsQuote'
            ],
            'analysis_details' => [
                'execution_mode' => $shouldParallelize ? 'parallel' : ($isMultiseller ? 'sequential' : 'traditional'),
                'data_quality' => '100%', // Dados já validados
                'optimization' => 'validItemsQuote_based'
            ]
        ];
        
        error_log("CalculoFrete: Análise validItemsQuote - {$totalSellers} sellers, " .
                "Modo: {$result['analysis_details']['execution_mode']}, " .
                "Items: {$result['statistics']['total_items']}");
        
        return $result;
    }

    /**
     * Verifica se uma tabela existe no banco de dados
     * 
     * @param string $tableName Nome da tabela
     * @return bool True se existe, false caso contrário
     */
    private function tableExists(string $tableName): bool
    {
        try {
            $query = $this->readonlydb->query("SHOW TABLES LIKE '{$tableName}'");
            return $query !== false && $query->num_rows() > 0;
        } catch (Exception $e) {
            error_log("CalculoFrete: Erro ao verificar existência da tabela {$tableName}: " . $e->getMessage());
            return false;
        }
    }
    /**
     * Retorna informações padrão quando não é possível obter da quotes_ship
     * 
     * @param string $sku SKU do produto
     * @param string $reason Motivo do fallback
     * @return array Informações padrão
     */
    private function getDefaultSellerInfo(string $sku, string $reason): array
    {
        error_log("CalculoFrete: Usando fallback para SKU {$sku} - Motivo: {$reason}");
        
        return [
            'seller_id' => 'default',
            'product_id' => null,
            'sku_info' => [
                'original_sku' => $sku,
                'data_source' => 'fallback',
                'extraction_method' => $reason,
                'reliable' => false,
                'complete_data' => false,
                'fallback_reason' => $reason
            ]
        ];
    }
    /**
     * Agrupa itens por seller usando dados da log_quotes
     * 
     * @param array $items Array de itens do carrinho
     * @return array Array agrupado por seller
     */
    private function groupItemsBySellerFromLogQuotes(array $items): array
    {
        $sellerGroups = [];
        $sellerInfo = [];
        $statistics = [
            'total_items' => count($items),
            'found_in_log_quotes' => 0,
            'fallback_used' => 0
        ];
        
        foreach ($items as $item) {
            if (!isset($item['sku'])) {
                error_log("CalculoFrete: Item sem SKU: " . json_encode($item));
                continue;
            }
            
            // Usar método otimizado
            $sellerData = $this->getSellerFromLogQuotes($item['sku']);
            $sellerId = $sellerData['seller_id'];
            
            // Estatísticas
            if ($sellerData['sku_info']['reliable']) {
                $statistics['found_in_log_quotes']++;
            } else {
                $statistics['fallback_used']++;
            }
            
            // Agrupar por seller
            if (!isset($sellerGroups[$sellerId])) {
                $sellerGroups[$sellerId] = [];
                $sellerInfo[$sellerId] = [
                    'seller_id' => $sellerId,
                    'item_count' => 0,
                    'product_ids' => []
                ];
            }
            
            // Adicionar item com informações
            $item['seller_info'] = $sellerData;
            $sellerGroups[$sellerId][] = $item;
            
            // Atualizar info do seller
            $sellerInfo[$sellerId]['item_count']++;
            if ($sellerData['product_id']) {
                $sellerInfo[$sellerId]['product_ids'][] = $sellerData['product_id'];
            }
        }
        
        error_log("CalculoFrete: Agrupamento log_quotes - " . count($sellerGroups) . " sellers, " . 
                "{$statistics['found_in_log_quotes']}/{$statistics['total_items']} encontrados");
        
        return [
            'groups' => $sellerGroups,
            'seller_info' => $sellerInfo,
            'statistics' => $statistics,
            'total_sellers' => count($sellerGroups),
            'total_items' => $statistics['total_items']
        ];
    }
    
} // FIM DA CLASSE CalculoFrete - LINHA 3253

// === OUTRAS CLASSES EXISTENTES ===

class LogisticTypes
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getTypesLogisticERP(): array
    {
        $arrIntegration = ['precode', 'viavarejo_b2b'];
        $rowApiIntegration = $this->db->select('integration')
            ->from('api_integrations')->group_by("integration")
            ->get()->result_array();
        foreach ($rowApiIntegration as $integration) {
            $arrIntegration[] = $integration['integration'];
        }

        return $arrIntegration;
    }
}

class LogisticTypesWithAutoFreightAcceptedGeneration
{
    private $db;

    private $environment;

    private $logisticTypes;

    public function __construct($db)
    {
        $this->db = $db;
        $this->logisticTypes = new LogisticTypes($db);
    }

    public function setEnvironment($environment): LogisticTypesWithAutoFreightAcceptedGeneration
    {
        $this->environment = $environment;
        return $this;
    }

    public function isLogisticTypeWithAutoFreightAcceptedGeneration($logisticType): bool
    {
        return in_array(
            strtolower($logisticType),
            $this->getLogisticTypesWithAutoFreightAcceptedGeneration()
        );
    }

    public function getLogisticTypesWithAutoFreightAcceptedGeneration(): array
    {
        return array_merge(
            array_map(function ($item) {
                return strtolower($item);
            },
                $this->logisticTypes->getTypesLogisticERP()
            ),
            $this->getEnvironmentLogisticTypesWithAutoFreightAcceptedGeneration()
        );
    }

    public function getEnvironmentLogisticTypesWithAutoFreightAcceptedGeneration(): array
    {
        $logisticTypesList = $this->getDefaultLogisticTypesWithAutoFreightAcceptedGeneration();
        $hire_automatic_freight = $this->db->get_where('settings', array('name' => 'hire_automatic_freight'))->row_array();

        if ($hire_automatic_freight && $hire_automatic_freight['status'] == 1) {
            $logisticTypesList = array_merge($logisticTypesList, [
                'intelipost',
                'freterapido'
            ]);
        }
        return $logisticTypesList;
    }

    public function getDefaultLogisticTypesWithAutoFreightAcceptedGeneration(): array
    {
        $integrations_logistic = $this->db->where('external_integration_id IS NOT NULL', NULL, FALSE)->get('integrations_logistic')->result_array();

        return array_merge(
            array_map(function($logistic){
                return $logistic['name'];
            }, $integrations_logistic),
            [
                'correios',
                'sgpweb',
                'sellercenter',
                false
            ]
        );
    }
}
