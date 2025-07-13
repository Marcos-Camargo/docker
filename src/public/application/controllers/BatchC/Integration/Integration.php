<?php

class Integration extends BatchBackground_Controller
{
    public $job;
    public $unique_id = null;
    public $formatReturn = "json";
    public $token;
    public $store;
    public $company;
    public $idEcommerce;
    public $typeIntegration;
    public $shutAppStatus = false;
    public $shutAppDesc = false;
    public $shutAppTitle = false;
    public $multiStore; // bling
    public $listPrice; // tiny
    public $countAttempt = 1;
    public $appKey;
    public $accountName;
    public $environment;
    public $salesChannel;
    public $affiliateId;
    public $shippingMethod;
    public $paymentId;
    public $generalStock;
    public $interface;
    public $dateStartJob;
    public $dateLastJob;

    protected $sellercenterName;

    public function __construct()
    {
        parent::__construct();

        // carrega os modulos necessários para o Job
        $this->load->model('model_products');
        $this->load->model('model_stores');
        $this->load->model('model_providers');
        $this->load->model('model_settings');
        $this->load->model('model_log_integration');
        $this->load->library('calculoFrete');

        $this->sellercenterName = $this->model_settings->getValueIfAtiveByName('sellercenter_name');
        if (!$this->sellercenterName) {
            $this->sellercenterName = "Conecta Lá";
        }
    }

    /**
     * Validate Token Bling
     *
     * @return bool
     */
    public function validateToken()
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->typeIntegration == 'bling') {
            $url = "https://bling.com.br/Api/v2/produtos";
            $data = "";
            $dataBling = $this->sendREST($url, $data);

            $contentBling = json_decode($dataBling['content']);

            if ($contentBling == null || ($dataBling['httpcode'] != 200 && $contentBling->retorno->erros->erro->cod == 3)) {
                // limite de requisição diário, coloquei 999 para não confundir com o limite de minutos
                if ($dataBling['httpcode'] == 999) {
                    return null;
                }

                echo "Token inválido, token informado={$this->token}, retorno=" . json_encode($dataBling) . "\n";
                $this->log_data('batch', $log_name, "Token inválido, token informado={$this->token}, retorno=" . json_encode($dataBling), "E");
                return false;
            }
        } elseif ($this->typeIntegration == 'pluggto') {

            $pluggto_setting = $this->model_settings->getSettingDatabyName('credencial_pluggto');
            $credentials = json_decode($pluggto_setting['value']);

            if (!isset($credentials->client_id_pluggto)) {
                return false;
            }

            // Busca por token - válido por 1 hora.
            $urlAuth = "https://api.plugg.to/oauth/token";
            $dataAuth = "grant_type=password&client_id=$credentials->client_id_pluggto&client_secret=$credentials->client_secret_pluggto&username=$credentials->username_pluggto&password=$credentials->password_pluggto";
            $authResult = json_decode(json_encode($this->sendREST($urlAuth, $dataAuth, 'POST', true, 'Content-Type: application/x-www-form-urlencoded')));

            if ($authResult->httpcode != 200) {

                $authResult = json_decode($authResult->content);

                $this->log_data('batch', $log_name, "Erro ao obter o token\nretorno=$authResult->content\nhttp_code=$authResult->httpcode", "E");

                if ($authResult === null || $authResult->details->code == 400) {
                    return null;
                }

                echo "Erro ao obter o token, retorno = " . json_encode($authResult) . "\n";
                return false;
            }
        } elseif ($this->typeIntegration == 'bseller') {
            $dataIntegrationStore = $this->model_stores->getDataApiIntegration($this->store);
            $BSELLER_URL = '';
            if ($dataIntegrationStore) {
                $credentials = json_decode($dataIntegrationStore['credentials']);
                $BSELLER_URL = $credentials->url_bseller;
            }

            $url = $BSELLER_URL . 'api/itens/massivo?tipoInterface=' . $this->interface . '&maxRegistros=100';
            $data = '';
            $dataBseller = json_decode(json_encode($this->sendREST($url, $data)));

            if ($dataBseller->httpcode != 200) {
                if (isset($dataBseller->retorno->codigo_erro) && $dataBseller->retorno->codigo_erro == 400) {
                    return null;
                }

                $regProducts = json_decode($dataBseller->content);
                $this->shutAppDesc = "Erro na API do Bseller:\t" . $regProducts->message . "\n";

                $this->log_data('batch', $log_name, "Erro Bseller retorno = " . json_encode($dataBseller), "E");
                return false;
            }
        } elseif ($this->typeIntegration == 'eccosys') {
            $dataIntegrationStore = $this->model_stores->getDataApiIntegration($this->store);
            $ECCOSYS_URL = '';
            if ($dataIntegrationStore) {
                $credentials = json_decode($dataIntegrationStore['credentials']);
                $ECCOSYS_URL = $credentials->url_eccosys;
            }

            $url = $ECCOSYS_URL . '/api/produtos';
            $data = '';
            $dataEccosys = json_decode(json_encode($this->sendREST($url, $data)));

            if ($dataEccosys->httpcode != 200) {
                if ($dataEccosys->retorno->codigo_erro == 400) {
                    return null;
                }

                echo "Token inválido, token informado={$this->token}, retorno=" . json_encode($dataEccosys) . "\n";
                $this->log_data('batch', $log_name, "Token inválido, token informado={$this->token}, retorno=" . json_encode($dataEccosys), "E");
                return false;
            }
        } elseif ($this->typeIntegration == 'tiny') {
            $url = "https://api.tiny.com.br/api2/info.php";
            $data = "";
            $dataTiny = json_decode($this->sendREST($url, $data));

            if ($dataTiny->retorno->status != "OK") {
                if ($dataTiny->retorno->codigo_erro == 99) {
                    return null;
                }

                echo "Token inválido, token informado={$this->token}, retorno=" . json_encode($dataTiny) . "\n";
                $this->log_data('batch', $log_name, "Token inválido, token informado={$this->token}, retorno=" . json_encode($dataTiny), "E");
                return false;
            }
        }
        elseif ($this->typeIntegration == 'vtex') {
            $url        = "api/catalog_system/pvt/products/GetProductAndSkuIds?_from=0&_to=1";
            $dataVtex   = $this->sendREST($url);

            if($dataVtex['httpcode'] != 200 && $dataVtex['httpcode'] != 206) {
                echo "Token inválido, token informado={$this->token}, retorno=" . json_encode($dataVtex) . "\n";
                $this->log_data('batch', $log_name, "Token inválido, token informado={$this->token}, retorno=" . json_encode($dataVtex), "E");
                return false;
            }
        } elseif ($this->typeIntegration == 'jn2') {
            $dataIntegrationStore = $this->model_stores->getDataApiIntegration($this->store);
            $JN2_URL = '';

            if ($dataIntegrationStore) {
                $credentials = json_decode($dataIntegrationStore['credentials']);
                $JN2_URL = $credentials->url_jn2;
            }

            $url = $JN2_URL . 'rest/all/V1/directory/currency';

            $data = '';
            $dataJn2 = json_decode(json_encode($this->sendREST($url, $data)));

            if ($dataJn2->httpcode != 200) {
                if ($dataJn2->httpcode == 400) {
                    return null;
                }

                if ($dataJn2->httpcode == 404) {
                    return null;
                }

                echo "Token inválido, token informado={$this->token}, retorno=" . json_encode($dataJn2) . "\n";
                $this->log_data('batch', $log_name, "Token inválido, token informado={$this->token}, retorno=" . json_encode($dataJn2), "E");
                return false;
            }
        }

        return true;
    }

    /**
     * Define o token de integração
     *
     * @param string $token Token de integraçõa
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Define a loja
     *
     * @param int $store Código da loja
     */
    public function setStore($store)
    {
        $this->store = $store;
    }

    /**
     * Define a empresa
     *
     * @param int $company Código da empresa
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * Define o job
     *
     * @param string $job Nome do job que será executado
     */
    public function setJob($job)
    {
        $this->job = $job;
    }

    /**
     * Define o unique id
     *
     * @param int $uniqueId Código único para controle
     */
    public function setUniqueId($uniqueId)
    {
        $this->unique_id = $uniqueId;
    }

    /**
     * Define a lista de preço, caso exista
     */
    public function setListPrice($list)
    {
        if (!$list) { // não usa lista
            $this->listPrice = null;
            return;
        }


        $url        = "https://api.tiny.com.br/api2/listas.precos.pesquisa.php";
        $data       = '&pesquisa='.urlencode($list);
        $idList     = false;
        $dataList   = json_decode($this->sendREST($url, $data));

        $registros = $dataList->retorno->registros ?? null;

        if (empty($registros)) { // existe uma lista configurada mas não encontrou na tiny
            $this->listPrice = false;
            return;
        }

        foreach ($registros as $registro) {
            if ($registro->registro->descricao == $list) {
                $idList = $registro->registro->id;
                continue;
            }
        }

        $this->listPrice = $idList;

        $dataIntegration = $this->db->get_where('api_integrations', array('store_id' => $this->store))->row_array();
        $credentials = json_decode($dataIntegration['credentials']);
        $credentials->id_lista_tiny = $idList;

        // Atualiza o id da lista
        $this->db->where(array('store_id' => $this->store))->update('api_integrations', array('credentials' => json_encode($credentials)));
    }

    /**
     * Define o código do ecommerce, caso exista
     */
    public function setIdEcommerce($idEcommerce)
    {
        $this->idEcommerce = $idEcommerce == "" ? null : $idEcommerce;
    }

    /**
     * Define a multi loja
     *
     * @param int $multiStore Código da multi loja
     */
    public function setMultiStore($multiStore)
    {
        if (!$multiStore) { // não usa lista
            $this->multiStore = null;
            return;
        }

        $this->multiStore = $multiStore;
    }

    /**
     * Define o tipo de integração
     *
     * @param int $integration Tipo de integração
     */
    public function setTypeIntegration($integration)
    {
        $this->typeIntegration = $integration;
    }

    /**
     * Define AppKey da VTEX
     *
     * @param string $appKey AppKey VTEX
     */
    public function setAppKey($appKey)
    {
        $this->appKey = $appKey;
    }

    /**
     * Define AccountName VTEX
     *
     * @param string $accountName accountName VTEX
     */
    public function setAccountName($accountName)
    {
        $this->accountName = $accountName;
    }

    /**
     * Define environment
     *
     * @param string $environment environment VTEX
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    /**
     * Define salesChannel
     *
     * @param int $salesChannel salesChannel VTEX
     */
    public function setSalesChannel($salesChannel)
    {
        $this->salesChannel = $salesChannel;
    }

    /**
     * Define salesChannel
     */
    public function setAffiliateId($affiliateId)
    {
        $this->affiliateId = $affiliateId;
    }

    /**
     * Define shippingMethod
     */
    public function setShippingMethod($shippingMethod)
    {
        $this->shippingMethod = $shippingMethod;
    }

    /**
     * Define salesChannel
     */
    public function setPaymentId($paymentId)
    {
        $this->paymentId = $paymentId;
    }

    /**
     * Define generalStock
     */
    public function setGeneralStock($generalStock)
    {
        $this->generalStock = $generalStock;
    }

    //define a interface para o bseller funcionar
    public function setInterface($interface)
    {
        $this->interface = $interface;
    }
    /**
     * Request API
     *
     * @param string        $url        URL requisição
     * @param null|string   $data       Dados para envio no body
     * @param string        $method     Metodo da requisição
     * @param bool          $newRequest Nova requisição que naõ se repetiu?
     * @param array         $header_opt Header adicional
     * @return mixed
     * @throws Exception
     *
     * BLING:   Caso seja ultrapassado o limite a requisição retornará o status 429 (too many requests) e a mensagem:
     *          O limite de requisições foi atingido.
     *
     * TINY:    Entrará no método apiBlockSleep() e fará a validação se foi bloqueado a requisição para aguarda
     */
    public function sendREST($url, $data = '', $method = 'GET', $newRequest = true, $header_opt = array())
    {
        if ($this->typeIntegration == 'bling') {
            if ($newRequest) {
                $url .= "/{$this->formatReturn}/";
                $data = "?apikey={$this->token}{$data}";
            }

            $curl_handle = curl_init();
            if ($method == "GET") {
                curl_setopt($curl_handle, CURLOPT_URL, $url . $data);
            } elseif ($method == "POST" || $method == "PUT") {
            	if (!is_array($data)) {
            		$data = substr($data, 1);
					$data = explode('&', $data);
                    $this->countAttempt = 0;
            	}
               
                $arrPost = array();

                foreach ($data as $value) {
                    $impPost = explode("=", $value);
                    $value = str_replace("{$impPost[0]}=", '', $value);

                    if ($impPost[0] == "xml") {
                        $value = rawurlencode($value);
                    }

                    $arrPost[$impPost[0]] = $value;
                }

                if ($method == "PUT") {
                    curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');
                }

                curl_setopt($curl_handle, CURLOPT_URL, $url);
                curl_setopt($curl_handle, CURLOPT_POST, count($arrPost));
                curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $arrPost);
            }

            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, true);
            $response = curl_exec($curl_handle);
            $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
            curl_close($curl_handle);

            $header['httpcode'] = $httpcode;
            $header['content'] = $response;

            $header = $this->apiBlockSleepBling($url, $data, $method, $header);

            return $header;
        } elseif ($this->typeIntegration == 'tiny') {

            $params = array('http' => array(
                'method' => 'POST',
                'content' => "token={$this->token}&formato={$this->formatReturn}{$data}",
            ));

            $ctx = stream_context_create($params);
            $fp = @fopen($url, 'rb', false, $ctx);
            if (!$fp) {
                return '{"retorno":{"status_processamento":1,"status":"Erro","codigo_erro":"99","erros":[{"erro":"Nao foi possivel acessar a URL(fopen): ' . $url . ' "}]}}';
            }

            $response = @stream_get_contents($fp);
            if ($response === false) {
                return '{"retorno":{"status_processamento":1,"status":"Erro","codigo_erro":"99","erros":[{"erro":"Nao foi possivel acessar a URL(stream_get_contents): ' . $url . ' "}]}}';
            }

            $response = $this->apiBlockSleep($url, $data, $response);

            return $response;
        } elseif ($this->typeIntegration == 'pluggto') {
            $curl_handle = curl_init();

            if ($method == "GET") {
                curl_setopt($curl_handle, CURLOPT_URL, $url);
            } elseif ($method == "POST" || $method == "PUT") {

                if ($method == "PUT") {
                    curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');
                }

                if ($method == "POST") {
                    curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'POST');
                }

                curl_setopt($curl_handle, CURLOPT_URL, $url);
                curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
            }

            if ($header_opt == 'Content-Type: application/x-www-form-urlencoded') {
                curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(
                    "Content-Type: application/x-www-form-urlencoded",
                ));
            } else {
                curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(
                    "Content-Type: application/json",
                ));
            }

            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, true);
            $response = curl_exec($curl_handle);
            $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
            curl_close($curl_handle);

            $header['httpcode'] = $httpcode;
            $header['content'] = $response;

            return $header;
        } elseif ($this->typeIntegration == 'bseller') {
            $curl_handle = curl_init();

            if ($method == "GET") {
                curl_setopt($curl_handle, CURLOPT_URL, $url);
            } elseif ($method == "POST" || $method == "PUT") {
                if ($method == "PUT") {
                    curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');
                }

                curl_setopt($curl_handle, CURLOPT_URL, $url);
                curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
            }

            curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json",
                "X-Auth-Token: {$this->token}",
            ));

            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, true);
            $response = curl_exec($curl_handle);
            $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
            curl_close($curl_handle);

            $header['httpcode'] = $httpcode;
            $header['content'] = $response;

            return $header;
        } elseif ($this->typeIntegration == 'eccosys') {

            $curl_handle = curl_init();

            if ($method == "GET") {
                curl_setopt($curl_handle, CURLOPT_URL, $url);
            } elseif ($method == "POST" || $method == "PUT") {

                if ($method == "PUT") {
                    curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');
                }

                curl_setopt($curl_handle, CURLOPT_URL, $url);
                curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
            }

            curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json",
                "Authorization: {$this->token}",
            ));

            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, true);
            $response = curl_exec($curl_handle);
            $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
            curl_close($curl_handle);

            $header['httpcode'] = $httpcode;
            $header['content'] = $response;

            return $header;
        } elseif ($this->typeIntegration == 'vtex') {

            if (!preg_match('/http/', $url)) {
                $url = "https://{$this->accountName}.{$this->environment}.com.br/{$url}";
            }

            $curl_handle = curl_init();
            curl_setopt($curl_handle, CURLOPT_URL, $url);

            $arr_opt = array(
                "accept: application/vnd.vtex.ds.v10+json",
                "content-type: application/json",
                "x-vtex-api-apptoken: {$this->token}",
                "x-vtex-api-appkey: {$this->appKey}",
            );
            $arr_opt = array_merge($arr_opt, $header_opt);

            if ($method == "POST" || $method == "PUT" || $method == "PATCH") {

                if ($method == "PUT") {
                    curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');
                } elseif ($method == "PATCH") {
                    curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PATCH');
                }

                curl_setopt($curl_handle, CURLOPT_POSTFIELDS, json_encode($data));
            }

            curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $arr_opt);

            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($curl_handle);
            $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
            curl_close($curl_handle);

            $header['httpcode'] = $httpcode;
            $header['content'] = $response;

            return $header;
        } elseif ($this->typeIntegration == 'jn2') {
            $curl_handle = curl_init();

            if ($method == "GET") {
                //curl_setopt($curl_handle, CURLOPT_HEADER, TRUE);
                curl_setopt($curl_handle, CURLOPT_URL, $url);
            } elseif ($method == "POST" || $method == "PUT") {

                if ($method == "PUT") {
                    curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');
                }

                curl_setopt($curl_handle, CURLOPT_URL, $url);
                curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
            }

            curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json",
                "Authorization: Bearer {$this->token}",
            ));

            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, true);
            $response = curl_exec($curl_handle);
            $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);

            curl_close($curl_handle);

            $header['httpcode'] = $httpcode;
            $header['content'] = $response;

            return $header;
        } elseif ($this->typeIntegration == 'AnyMarket') {
            $curl_handle = curl_init();

            if ($method == "GET") {
                //curl_setopt($curl_handle, CURLOPT_HEADER, TRUE);
                curl_setopt($curl_handle, CURLOPT_URL, $url);
            } elseif ($method == "POST" || $method == "PUT") {

                if ($method == "PUT") {
                    curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');
                }

                curl_setopt($curl_handle, CURLOPT_URL, $url);
                curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
            }
            curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(                
                "Content-Type: application/json",
                "appId: {$this->appKey}",
                "token: {$this->token}",
            ));
            // dd(["appId: {$this->appKey}",
            // "token: {$this->token}"]);

            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, true);
            $response = curl_exec($curl_handle);
            $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);

            curl_close($curl_handle);

            $header['httpcode'] = $httpcode;
            $header['content'] = $response;

            return $header;
        } elseif ($this->typeIntegration == 'LojaIntegrada') {
            $curl_handle = curl_init();
            $content = array(
                "Content-Type: application/json",
            );
            if ($method == "GET") {
                curl_setopt($curl_handle, CURLOPT_URL, $url);
            } elseif ($method == "POST" || $method == "PUT") {
                if ($method == "PUT") {
                    curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');
                }else{
                    curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'POST');
                }

                curl_setopt($curl_handle, CURLOPT_URL, $url);
                curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
            }
            curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array_merge($content, $header_opt));
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, true);
            $response = curl_exec($curl_handle);
            $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);

            curl_close($curl_handle);

            $header['httpcode'] = $httpcode;
            $header['content'] = $response;

            return $header;
        }

        return array(
            'httpcode'  => 404,
            'content'   => json_encode(array('message' => "Integration not found"))
        );
    }

    /**
     * Cria um log da integração para ser mostrada ao usuário
     *
     * @param   string      $title          Título do log
     * @param   string      $description    Descrição do log
     * @param   string      $type           Tipo de log
     * @return  bool                        Retornar o status da criação do log
     */
    public function log_integration(string $title, string $description, string $type): bool
    {
        $data = array(
            'store_id'      => $this->store,
            'company_id'    => $this->company,
            'title'         => $title,
            'description'   => $description,
            'type'          => $type,
            'job'           => $this->job,
            'unique_id'     => $this->unique_id,
            'status'        => 1,
        );

        $logExist = null;

        if ($type !== 'S') {
            // verifica se o log já existe, para não ser duplicado
            $logExist = $this->db->get_where('log_integration',
                array(
                    'store_id' => $this->store,
                    'company_id' => $this->company,
                    'description' => $description,
                    'title' => $title,
                )
            )->row_array();

            if ($logExist && ($type == 'E' || $type == 'W')) {
                $data['type'] = $type;
                $data['date_updated'] = dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL);
            }
        }

        if ($logExist === null) {
            return (bool)$this->model_log_integration->create($data);
        } else {
            return (bool)$this->model_log_integration->update($data, $logExist['id']);
        }
    }

    /**
     * Verifica se a API foi bloqueada por limite de requisição
     *
     * @param   string  $url        URL requisição
     * @param   string  $data       Dados para envio no body
     * @param   string  $response   Resposta da requisição atual
     * @return  string              Retorno da requisição
     */
    public function apiBlockSleep($url, $data, $response)
    {
        // Converte caso não chegue em xml
        if ($this->formatReturn == 'json') {
            $responseDecode = json_decode($response);
        }

        // enquanto a api estiver bloqueada ficará tentando até encontrar o resultado
        if ($this->formatReturn == 'json') {
            while ($responseDecode->retorno->status == "Erro" && isset($responseDecode->retorno->codigo_erro) && $responseDecode->retorno->codigo_erro == 6) {
                echo "API Bloqueada, vou esperar 15s e tentar novamente...\n";
                sleep(15); // espera 15 segundos
                $response = $this->sendREST($url, $data); // enviar uma nova requisição para ver se já liberou
                $responseDecode = json_decode($response);
            }
        } elseif ($this->formatReturn == 'xml') {

            // Converte caso chegue em xml
            $responseArr = $this->convertXmlArray($response);

            while ($responseArr['status'] == "Erro" && isset($responseArr['codigo_erro']) && $responseArr['codigo_erro'] == 6) {
                echo "API Bloqueada, vou esperar 15s e tentar novamente...\n";
                sleep(15); // espera 15 segundos
                $response = $this->sendREST($url, $data); // enviar uma nova requisição para ver se já liberou

                // Converte caso chegue em xml
                $responseArr = $this->convertXmlArray($response);
            }
        }

        return $response;

    }

    /**
     * Verifica se a API foi bloqueada por limite de requisição
     *
     * @param   string  $url        URL requisição
     * @param   string  $data       Dados para envio no body
     * @param   string  $method     Método da requisição
     * @param   string  $header     Resposta da requisição atual
     * @return  array               Retorno da requisição
     */
    public function apiBlockSleepBling($url, $data, $method, $header)
    {
        $attempts = 15;
        // enquanto a api estiver bloqueada ficará tentando até encontrar o resultado
        while ($header['httpcode'] == 429) {
            $content = json_decode($header["content"]);
            if ($this->countAttempt > $attempts) {
                return array('httpcode' => 999, 'content' => '{"retorno":{"erros":{"erro": {"cod": 3}}}}');
            }

            if (
                !isset($content->retorno->erros->erro->msg) ||
                !likeText('%por segundo foi atingido%', strtolower($content->retorno->erros->erro->msg))
            ) {
                echo "API Bloqueada, vou esperar 5s e tentar novamente (Tentativas: {$this->countAttempt}/$attempts)...\n";
                $this->countAttempt++;
            } else {
                echo "Bloqueio por segundo. (Tentativas: {$this->countAttempt}/$attempts).\n";
            }

            sleep(5); // espera 1 minuto

            $header = $this->sendREST($url, $data, $method, false); // enviar uma nova requisição para ver se já liberou
        }

        return $header;

    }

    /**
     * Converter um XML em array
     *
     * @param   string $xml String de uma xml
     * @return  array       Retorna um array convertido do xml
     */
    public function convertXmlArray($xml)
    {
        $responseXml = simplexml_load_string($xml);
        $jsonEncode = json_encode($responseXml);

        return json_decode($jsonEncode, true);
    }

    /**
     * Grava o horário da última execução
     *
     * @return  bool Status da atualização
     */
    public function saveLastRun()
    {
        $this->db->where(
            array(
                'job' => $this->job,
                'store_id' => $this->store,
                'LOWER(integration)' => strtolower($this->typeIntegration),
            )
        );

        if ($this->dateStartJob && !strtotime($this->dateStartJob)) {
            $this->dateStartJob = DateTime::createFromFormat("d/m/Y%20H:i:s", $this->dateStartJob)->format('Y-m-d H:i:s');
        }

        return $this->db->update('job_integration', array('last_run' => $this->dateStartJob ?? date('Y-m-d H:i:s'))) ? true : false;
    }

    public function validateIntegrationActive()
    {
        return $this->db->get_where('api_integrations',
            array(
                'store_id' => $this->store,
                'status' => 1,
            )
        )->num_rows() == 0 ? false : true;
    }

    /**
     * Define o horário da última execução
     */
    /*
    public function setDataIntegration($store_id)
    {
    $dataIntegration = $this->db->get_where('api_integrations', array('store_id' => $store_id))->row_array();
    $dataStore       = $this->model_stores->getStoresData($store_id);

    $this->setStore($store_id);
    $this->setCompany($dataStore['company_id']);

    if ($this->typeIntegration == 'bling') {
    $this->setToken($dataIntegration['apikey_bling']);
    $this->setMultiStore($dataIntegration['loja_bling']);
    } elseif ($this->typeIntegration == 'tiny') {
    $this->setToken($dataIntegration['token_tiny']);
    $this->setIdEcommerce($dataIntegration['id_ecommerce_tiny']);
    }

    $validateToken = $this->validateToken();

    if(!$validateToken) {
    $this->shutAppStatus = true;
    $this->shutAppTitle = "Token Inválido";
    $this->shutAppDesc = "Token inválido, token informado={$this->token}";
    } elseif ($validateToken && $this->typeIntegration == 'tiny')
    $this->setListPrice($dataIntegration['lista_tiny']);

    if($this->typeIntegration == 'tiny' && $this->listPrice === false) {
    $this->shutAppStatus = true;
    $this->shutAppTitle = "Lista de Preço Não Encontrada";
    $this->shutAppDesc = "A loja está configurada para usar uma lista de preço, mas não foi encontrada da tiny, token informado={$this->token}";
    }
    }*/
    public function setLastRun()
    {
        $query = $this->db->select('last_run')->get_where('job_integration', array(
            'job' => $this->job,
            'store_id' => $this->store,
            'integration' => $this->typeIntegration,
        ))->row_array();

        if (!$query || $query['last_run'] === null) {
            $this->dateLastJob = null;
        } else {
            $this->dateLastJob = $query['last_run'];
        }

    }

    /**
     * Define o valor que o job foi iniciado para filtros de consultas
     */
    public function setDateStartJob()
    {
        $this->dateStartJob = date('Y-m-d H:i:s');
    }

    /**
     * Remover todos os acentos
     *
     * @param   string  $string Texto para remover os acentos
     * @return  string
     */
    public function removeAccents($string){
        return preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/"),explode(" ","a A e E i I o O u U n N"),$string);
    }


    
        /**
     * Notifica marketplace sobre envio do pedido
     * 
     * @param array $api_keys Chaves de API do marketplace
     * @param array $notificationData Dados da notificação de envio
     * @return array
     */
    public function notifyShipping($api_keys, $notificationData)
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;
        
        try {
            // Validar dados obrigatórios
            if (empty($notificationData['order_code'])) {
                throw new Exception('order_code é obrigatório');
            }
            
            if (empty($notificationData['tracking_code'])) {
                throw new Exception('tracking_code é obrigatório');
            }
            
            // Preparar URL da API
            $url = rtrim($api_keys['api_url'], '/') . '/orders/shipping-notification';
            
            // Preparar dados para envio
            $postData = [
                'order_code' => $notificationData['order_code'],
                'tracking_code' => $notificationData['tracking_code'],
                'carrier' => $notificationData['carrier'] ?? 'CORREIOS',
                'status' => $notificationData['status'] ?? 'shipped',
                'shipped_at' => $notificationData['shipped_at'] ?? date('Y-m-d H:i:s'),
                'estimated_delivery' => $notificationData['estimated_delivery'] ?? null
            ];
            
            // Log da tentativa
            $this->log_data('batch', $log_name, "Notificando envio - Pedido: {$postData['order_code']}, Tracking: {$postData['tracking_code']}", "I");
            
            // Enviar notificação
            $response = $this->sendNotificationRequest($url, $postData, $api_keys);
            
            // Validar resposta
            if ($response['http_code'] === 200) {
                $this->log_data('batch', $log_name, "Notificação de envio enviada com sucesso - Pedido: {$postData['order_code']}", "I");
                return [
                    'success' => true,
                    'http_code' => $response['http_code'],
                    'content' => $response['content'],
                    'message' => 'Notificação de envio enviada com sucesso'
                ];
            } else {
                $this->log_data('batch', $log_name, "Erro na notificação de envio - HTTP: {$response['http_code']}, Response: {$response['content']}", "E");
                return [
                    'success' => false,
                    'http_code' => $response['http_code'],
                    'content' => $response['content'],
                    'message' => 'Erro ao enviar notificação de envio'
                ];
            }
            
        } catch (Exception $e) {
            $this->log_data('batch', $log_name, "Exceção na notificação de envio: " . $e->getMessage(), "E");
            return [
                'success' => false,
                'http_code' => 500,
                'content' => $e->getMessage(),
                'message' => 'Erro interno na notificação de envio'
            ];
        }
    }
    /**
     * Notifica marketplace sobre faturamento do pedido
     * @param array $api_keys
     * @param array $notificationData
     * @return array
     */
    public function notifyInvoicing($api_keys, $notificationData)
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        
        try {
            // Validar parâmetros obrigatórios
            if (!isset($notificationData['order_code']) || !isset($notificationData['invoice_number'])) {
                $message = "Parâmetros obrigatórios não informados (order_code, invoice_number)";
                $this->log_data('api', $log_name, $message, "E");
                return [
                    'http_code' => 400,
                    'content' => $message,
                    'success' => false
                ];
            }
            
            // Preparar URL da API
            $url = $api_keys['api_url'] . '/orders/invoice-notification';
            
            // Preparar dados para envio
            $postData = [
                'order_code' => $notificationData['order_code'],
                'invoice_number' => $notificationData['invoice_number'],
                'total_value' => $notificationData['total_value'] ?? 0,
                'status' => $notificationData['status'] ?? 'invoiced',
                'invoiced_at' => date('Y-m-d H:i:s')
            ];
            
            $this->log_data('api', $log_name, 
                "Enviando notificação de faturamento - Pedido: " . $notificationData['order_code'] . 
                ", NF: " . $notificationData['invoice_number'], "I");
            
            // Configurar headers
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . ($api_keys['access_token'] ?? ''),
                'User-Agent: SellerCenter/1.0'
            ];
            
            // Configurar cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            // Executar requisição
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            // Verificar erros de cURL
            if ($error) {
                $this->log_data('api', $log_name, "Erro cURL: " . $error, "E");
                return [
                    'http_code' => 0,
                    'content' => 'Erro de conexão: ' . $error,
                    'success' => false
                ];
            }
            
            // Verificar código HTTP
            $success = ($http_code >= 200 && $http_code < 300);
            
            if ($success) {
                $this->log_data('api', $log_name, 
                    "Notificação de faturamento enviada com sucesso - Pedido: " . $notificationData['order_code'], "I");
            } else {
                $this->log_data('api', $log_name, 
                    "Erro na notificação de faturamento - Pedido: " . $notificationData['order_code'] . 
                    ", HTTP: " . $http_code . ", Resposta: " . $response, "E");
            }
            
            return [
                'http_code' => $http_code,
                'content' => $response,
                'success' => $success,
                'message' => $success ? 'Notificação enviada com sucesso' : 'Erro HTTP ' . $http_code
            ];
            
        } catch (Exception $e) {
            $message = "Exceção ao notificar faturamento: " . $e->getMessage();
            $this->log_data('api', $log_name, $message, "E");
            return [
                'http_code' => 500,
                'content' => $e->getMessage(),
                'success' => false,
                'message' => $message
            ];
        }
    }
        /**
     * Envia requisição de notificação para o marketplace
     * 
     * @param string $url URL da API
     * @param array $postData Dados para envio
     * @param array $api_keys Chaves de API
     * @return array
     */
    private function sendNotificationRequest($url, $postData, $api_keys)
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;
        
        try {
            // Preparar headers
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json'
            ];
            
            // Adicionar autenticação se disponível
            if (isset($api_keys['access_token'])) {
                $headers[] = 'Authorization: Bearer ' . $api_keys['access_token'];
            } elseif (isset($api_keys['api_key'])) {
                $headers[] = 'X-API-Key: ' . $api_keys['api_key'];
            }
            
            // Configurar cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            // Executar requisição
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            // Verificar erros de cURL
            if ($curl_error) {
                throw new Exception("Erro cURL: $curl_error");
            }
            
            return [
                'http_code' => $http_code,
                'content' => $response,
                'success' => $http_code === 200
            ];
            
        } catch (Exception $e) {
            $this->log_data('batch', $log_name, "Erro na requisição: " . $e->getMessage(), "E");
            return [
                'http_code' => 500,
                'content' => $e->getMessage(),
                'success' => false
            ];
        }
    }
}
