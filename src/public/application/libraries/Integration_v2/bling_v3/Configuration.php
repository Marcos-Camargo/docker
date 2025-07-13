<?php

namespace Integration\bling_v3;

require_once APPPATH . 'libraries/Integration_v2/Applications/Resources/IntegrationConfiguration.php';

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Integration_v2\Applications\Resources\IntegrationConfiguration;
use InvalidArgumentException;

class Configuration extends IntegrationConfiguration
{

    /**
     * @var object|null Credenciais
     */
    private $credentials;

    /**
     * @var string Integração
     */
    protected $integration = 'bling_v3';

    /**
     * @var int Código da loja
     */
    private $store_id = null;

    /**
     * @var Client Cliente of GuzzleHttp
     */
    private $client;

    public function __construct(?object $credentials = null, ?int $store_id = null)
    {
        $this->credentials = $credentials;
        $this->store_id = $store_id;
        $this->setClientGuzzle();
    }

    /**
     * Define a instância Client de GuzzleHttp
     */
    private function setClientGuzzle()
    {
        $this->client = new Client([
            'verify' => false, // no verify ssl
            'timeout' => 900,
            'connect_timeout' => 900,
            'allow_redirects' => true
        ]);
    }

    public function getAccessToken(): string
    {
        if (!$this->availbilityAccessToken()) {
            $this->generateNewAccessToken();
        }

        $this->setStockIdBling();

        return "{$this->credentials->token_type} {$this->credentials->access_token}";
    }

    public function getNewCredentials(): object
    {
        return $this->credentials;
    }

    public function availbilityAccessToken(): bool
    {
        return strtotime($this->credentials->expire_at) > strtotime(dateNow()->format(DATETIME_INTERNATIONAL));
    }

    private function generateNewAccessToken()
    {
        $client_id      = $this->getIntegrationConfig('client_id');
        $client_secret  = $this->getIntegrationConfig('client_secret');
        $authorization  = base64_encode("$client_id:$client_secret");
        $options = array(
            'form_params' => array(
                'grant_type'    => 'refresh_token',
                'refresh_token' => $this->credentials->refresh_token
            ),
            'headers' => array(
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'Accept'        => '1.0',
                'Authorization' => "Basic $authorization"
            )
        );

        try {
            // Aguarda alguns segundos rondomicamente para gerar um novo token, assim não dará conflitos com outros jobs
            sleep(rand(3,45));

            get_instance()->load->model('model_api_integrations');
            $data_integration = get_instance()->model_api_integrations->getDataByStore($this->store_id, true);
            $credentials_integration = json_decode($data_integration['credentials'], true);
            if (strtotime($credentials_integration['expire_at']) > strtotime(dateNow()->format(DATETIME_INTERNATIONAL))) {
                $credentials_integration['integration'] = $this->credentials->integration;
                $credentials_integration['api_internal'] = $this->credentials->api_internal;

                $this->credentials = json_decode(json_encode($credentials_integration, JSON_UNESCAPED_UNICODE));
                $this->credentials->api_internal = (array)$this->credentials->api_internal;
                get_instance()->log_data('Bling_v3', 'tryUpdateCredentials_1', "old_credencials=$data_integration[credentials]\nnew_credencials=".json_encode($credentials_integration,JSON_UNESCAPED_UNICODE)."\nstore=$this->store_id");
                return;
            }

            $request    = $this->client->post('https://www.bling.com.br/Api/v3/oauth/token', $options);
            $response   = json_decode($request->getBody()->getContents(), true);
            $data_auth  = $response;

            $created_at = dateNow()->format(DATETIME_INTERNATIONAL);
            $new_credentials = array_merge($data_auth, array(
                'created_at'        => $created_at,
                'expire_at'         => date(DATETIME_INTERNATIONAL, strtotime($created_at) + ($data_auth['expires_in'] - 600)),
                'loja_bling'        => $this->credentials->loja_bling,
                'stock_bling'       => $this->credentials->stock_bling,
                'stock_id_bling'    => empty($this->credentials->stock_id_bling) ? null : $this->credentials->stock_id_bling,
            ));
            $this->updateCredentials($new_credentials);

            get_instance()->log_data('Bling_v3', 'generateNewAccessToken', "store=$this->store_id\n".json_encode($new_credentials)."\n".json_encode($options));
        } catch (GuzzleException | BadResponseException $exception) {
            $error_message = $exception->getMessage();
            $error_code = $exception->getCode();
            $message = method_exists($exception, 'getResponse') ?
                (
                    method_exists($exception->getResponse(), 'getBody') ?
                    $exception->getResponse()->getBody()->getContents() :
                    $error_message
                ) : $error_message;
            if ($error_code != 403) {
                get_instance()->log_data('Bling_v3', 'generateNewAccessToken', "code=$error_code\nerror=$error_message\nstore=$this->store_id\n".json_encode($options), 'E');
            }
            throw new InvalidArgumentException($message, $error_code);
        }
    }

    private function setStockIdBling()
    {
        if (!empty($this->credentials->stock_id_bling) || empty($this->credentials->stock_bling)) {
            return;
        }

        $options = array(
            'query' => array(
                'descricao' => $this->credentials->stock_bling
            ),
            'headers' => array(
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'Accept'        => '1.0',
                'Authorization' => "Bearer {$this->credentials->access_token}"
            )
        );

        try {
            $request    = $this->client->get('https://www.bling.com.br/Api/v3/depositos', $options);
            $response   = json_decode($request->getBody()->getContents());

            if (empty($response->data[0])) {
                return;
            }

            $new_credentials = array('stock_id_bling' => $response->data[0]->id);
            $this->updateCredentials($new_credentials);
        } catch (GuzzleException | BadResponseException $exception) {
            $message = method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
            throw new InvalidArgumentException($message, $exception->getCode());
        }
    }

    public function updateCredentials(array $new_credentials)
    {
        // Aguarda alguns segundos rondomicamente para gerar um novo token, assim não dará conflitos com outros jobs
        //sleep(rand(3,45));

        get_instance()->load->model('model_api_integrations');
        $data_integration = get_instance()->model_api_integrations->getDataByStore($this->store_id, true);
        $credentials_integration = json_decode($data_integration['credentials'], true);

        if (strtotime($credentials_integration['expire_at']) > strtotime(dateNow()->format(DATETIME_INTERNATIONAL))) {
            $credentials = $credentials_integration;
            $credentials['integration'] = $this->credentials->integration;
            $credentials['api_internal'] = $this->credentials->api_internal;

            $this->credentials = json_decode(json_encode($credentials, JSON_UNESCAPED_UNICODE));
            $this->credentials->api_internal = (array)$this->credentials->api_internal;
            get_instance()->log_data('Bling_v3', 'tryUpdateCredentials_2', "old_credencials=$data_integration[credentials]\nnew_credencials=".json_encode($credentials,JSON_UNESCAPED_UNICODE)."\nstore=$this->store_id\ndata_try_new=".json_encode($new_credentials));
            return;
        }

        $credentials = array_merge($credentials_integration, $new_credentials);

        if (array_key_exists('refresh_token', $new_credentials)) {
            $credentials['old_refresh_token'] = $new_credentials['refresh_token'];
        }
        if (array_key_exists('access_token', $new_credentials)) {
            $credentials['old_access_token'] = $new_credentials['access_token'];
        }

        get_instance()->log_data('Bling_v3', 'updateCredentials', "old_credencials=$data_integration[credentials]\nnew_credencials=".json_encode($credentials,JSON_UNESCAPED_UNICODE)."\nstore=$this->store_id");

        get_instance()->model_api_integrations->updateByStore($this->store_id, array(
            'credentials' => json_encode($credentials, JSON_UNESCAPED_UNICODE)
        ));

        $credentials['integration'] = $this->credentials->integration;
        $credentials['api_internal'] = $this->credentials->api_internal;

        $this->credentials = json_decode(json_encode($credentials, JSON_UNESCAPED_UNICODE));
        $this->credentials->api_internal = (array)$this->credentials->api_internal;
    }
}