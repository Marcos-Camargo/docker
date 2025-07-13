<?php

/**
 * @property Model_freights $model_freights
 * @property Model_integrations $model_integrations
 * @property Model_settings $model_settings
 */
class SyncTrackingUrl extends BatchBackground_Controller
{
    private $int_to = '';
    private $apikey = '';
    private $appToken = '';
    private $accountName = '';
    private $environment = '';
    private $dns = '.com.br';
    private $linkApiSite;
    private $linkApi = false;


    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        // carrega os módulos necessários para o Job
        $this->load->model('model_freights');
        $this->load->model('model_integrations');
        $this->load->model('model_settings');
    }

    private function setInt_to($int_to)
    {
        $this->int_to = $int_to;
    }

    private function getInt_to(): string
    {
        return $this->int_to;
    }

    private function setApikey($apikey)
    {
        $this->apikey = $apikey;
    }

    private function getApikey(): string
    {
        return $this->apikey;
    }

    private function setAppToken($appToken)
    {
        $this->appToken = $appToken;
    }

    private function getAppToken(): string
    {
        return $this->appToken;
    }

    private function setAccountName($accountName)
    {
        $this->accountName = $accountName;
    }

    private function getAccountName(): string
    {
        return $this->accountName;
    }

    private function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    private function getEnvironment(): string
    {
        return $this->environment;
    }

    private function setDns($dns)
    {
        $this->dns = $dns;
    }

    private function getDns(): string
    {
        return $this->dns;
    }

    private function getBaseUrlVtex(): string
    {
        return "https://{$this->getAccountName()}.{$this->getEnvironment()}{$this->getDns()}/api/oms/pvt";
    }

    public function run(string $int_to, string $stores, string $date_start, string $date_end = 'null')
    {
        echo "Iniciado em ".dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL)."\n";
        $this->changeTrackingUrlByParam($int_to, $date_start, $date_end, $stores);
        echo "Encerrado em ".dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL)."\n";
    }

    private function changeTrackingUrlByParam(string $int_to, string $date_start, string $date_end, string $stores)
    {
        $tracking_url = $this->model_settings->getValueIfAtiveByName('tracking_url_default');

        if (!$tracking_url) {
            echo "Parâmetro tracking_url_default não configurado\n";
            return;
        }

        if ($date_end === 'null') {
            $date_end = dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL);
        }

        if (strtotime($date_end) < strtotime($date_start)) {
            echo "A data inicial não pode ser maior que a data final\n";
            return;
        }

        $this->setkeys($int_to);

        $freights = $this->model_freights->getDataFreightToFixTrackingUl($date_start, $date_end, $int_to, $tracking_url, $stores);

        foreach ($freights as $freight) {
            $json_data = json_encode(array(
                'trackingUrl' => $tracking_url
            ));

            $freight['nfe_num'] = (int)$freight['nfe_num'];

            if ($this->linkApi) {
                $url = $this->linkApiSite . "/api/oms/pvt/orders/$freight[numero_marketplace]/invoice/$freight[nfe_num]?apiKey=$this->apikey";
                $resp = $this->restVtex($url, $json_data, array(), 'PATCH');
            } else {
                $url = "{$this->getBaseUrlVtex()}/orders/$freight[numero_marketplace]/invoice/$freight[nfe_num]";
                $resp = $this->restVtex($url, $json_data, array('x-vtex-api-appkey: ' . $this->getApikey(), 'x-vtex-api-apptoken: ' . $this->getAppToken()), 'PATCH');
            }

            if ($resp['httpcode'] != 200) {
                echo "Erro para alterar a URL do pedido $freight[id].\n[$resp[httpcode]] $resp[content]\n";
                continue;
            }

            $this->model_freights->update(array('url_tracking' => $tracking_url), $freight['freight_id']);

            echo "Pedido $freight[id] alterado.\n";
        }
    }

    private function setKeys($int_to)
    {
        $this->setInt_to($int_to);

        //pega os dados da integração. Por enquanto só a conectala faz a integração direta
        $integration = $this->model_integrations->getIntegrationsbyCompIntType(1, $this->getInt_to(), "CONECTALA", "DIRECT", 0);
        $api_keys = json_decode($integration['auth_data'], true);
        $this->setApikey($api_keys['X_VTEX_API_AppKey'] ?? null);
        $this->setAppToken($api_keys['X_VTEX_API_AppToken'] ?? null);
        $this->setAccountName($api_keys['accountName'] ?? null);
        $this->setEnvironment($api_keys['environment'] ?? null);
        $this->setDns($api_keys['suffixDns'] ?? '.com.br');

        $this->linkApi = false;
        if (key_exists('apiKey', $api_keys)) {
            $this->apikey = $api_keys['apiKey'];
            $this->linkApiSite = $api_keys['site'];
            $this->linkApi = true;
        }
    }

    private function restVtex($url, $data, $httpHeader = array(), $method = 'GET')
    {
        $httpHeader = array_merge(array(
            'Accept: application/json',
            'Content-Type: application/json'
        ), $httpHeader);

        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => $httpHeader
        );
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $header = curl_getinfo($ch);
        curl_close($ch);
        $header['httpcode'] = $httpcode;
        $header['errno'] = $err;
        $header['errmsg'] = $errmsg;
        $header['content'] = $content;
        return $header;
    }
}