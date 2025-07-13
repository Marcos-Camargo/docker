<?php
/*
Controller de login externo com OpenID Connect 
*/
require_once 'system/libraries/Vendor/autoload.php';

defined('BASEPATH') || exit('No direct script access allowed');

class ExternalLogin extends Admin_Controller
{
    var $external_auth;
    var $external_auth_confs;
   
    public function __construct()
    {
        parent::__construct();

        $this->load->model('model_externals_authentication');
        $this->load->model('model_settings');

    }

    public function index($id = null)
    {
        if (!is_null($id)) {
            $this->external_auth = $this->model_externals_authentication->getData($id);            
            if (!$this->external_auth) {
                redirect('auth/login', 'refresh');
            }
            $this->external_auth_confs = $this->model_externals_authentication->getAllDataConfiguration($id); 
            if ($this->external_auth['type']== 'OPENID') {
                return $this->OpenIDLogin();
            }
        }
        redirect('auth/login', 'refresh');
    }

    private function VerifyOpenIDConnectSite($url) 
    {
  
        $openidsite = 'https://'.$url.'/.well-known/openid-configuration';
      
        $ch = curl_init($openidsite);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $data = json_decode($response, true);
        curl_close($ch);

        $error = true; 
        if (($responseCode < 200) || ($responseCode > 299)) {
            $result = 'Response code '.$responseCode.' para o site '.$openidsite; 
        }elseif (!isset($data['authorization_endpoint'])) {
            $result = $openidsite.$this->lang->line('application_error_no_authorization_endpoint');
        } else {
            $result = $this->lang->line('application_site_ok_openid').$data['authorization_endpoint'];
            $error = false;            
        }
        $output = array(
            'ok'        => !$error,
            'result'    => $result
        );
        
        return $output;
    }

    public function OpenIDLogin() {

        if (is_null($this->external_auth_confs )) {
            redirect('auth/login', 'refresh');
        }
        foreach ($this->external_auth_confs as $conf) {
            $configuration[$conf['name']] = $conf['value'];
        }
        
        $checksite = $this->VerifyOpenIDConnectSite($configuration['openid_url_openid_configuration']);

        if(!$checksite['ok']) {
            $this->session->set_flashdata('error', $this->lang->line('application_authentication_site_unavailable'));
            redirect('auth/login', 'refresh');
        }
        
        $oidc = new Jumbojett\OpenIDConnectClient(
            'https://'.$configuration['openid_url_openid_configuration'], 
            $configuration['openid_client_id'], 
            $configuration['openid_client_secret']
        );
        
        $oidc->addScope('email');
        $oidc->setRedirectURL(base_url("externalLogin/openIDLoginClose"));
        $this->session->oidc = $oidc;
        $oidc->authenticate();  // will jump to openIDLoginClose

    }
    public function OpenIDLoginClose() {
        // SENTRY ID: 498
        $oidc =  $this->session->oidc ; 

        if (is_null($oidc)) {
            $this->session->set_flashdata('error', $this->lang->line('application_authentication_session_expired'));
            redirect('auth/login', 'refresh');
        }

        $oidc->authenticate(); // se tudo estiver correto, coninua 

        $name = $oidc->requestUserInfo('email');  // pego o email de quem está usando
        $this->session->oidc = $oidc;
        $this->session->loginemail = $name;
        redirect('auth/loginExternal', 'refresh');
       
    }

    public function OpenIDLogout($oidc) {
        $oidc->revokeToken($oidc->getAccessToken());
    }  

}
