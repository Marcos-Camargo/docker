<?php

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;

/**
 * @property CI_Loader $load
 * @property CI_Lang $lang
 * @property CI_Config $config
 * @property Model_settings $model_settings
 * @property Model_users $model_users
 * @property Model_reset_tokens $model_reset_tokens
 * @property Model_company $model_company
 */
class AuthUser
{
    public function __construct()
    {
        $this->load->model('model_settings');
        $this->load->model('model_users');
        $this->load->model('model_reset_tokens');
        $this->load->model('model_company');
    }

    /**
     * Método mágico para utilização do CI_Controller.
     *
     * @param   string  $var    Propriedade para consulta.
     * @return  mixed           Objeto da propriedade.
     */
    public function __get(string $var)
    {
        return get_instance()->$var;
    }

    /**
     * @param string $email
     * @throws Exception
     */
    public function resetPassword(string $email)
    {
        $domain = ($_SERVER['HTTP_HOST']);
        $key = $this->config->config['encryption_key'];
        $reset_password_token_expiration_time_in_minutes = $this->model_settings->getValueIfAtiveByName('reset_password_token_expiration_time_in_minutes');
        if (!$reset_password_token_expiration_time_in_minutes) {
            $reset_password_token_expiration_time_in_minutes = 60; //  60 minutos é quanto dura o token se não tiver parametro
        }
        $time = time();
        $uidEmail = $this->model_users->getUserByEmail($email);
        if (!is_null($uidEmail[0]['external_authentication_id'])) {
            throw new Exception("Usuário configurado com external_authentication");
        }

        $uid = array(
            'id'        => $uidEmail[0]['id'],
            'email'     => $uidEmail[0]['email'],
            'username'  => $uidEmail[0]['username']
        );

        $old_token = $this->model_reset_tokens->getOldToken($uid['id'], $reset_password_token_expiration_time_in_minutes);
        if ($old_token) {
            $stringToken = $old_token['token'];
        }
        else {
            $signer = new Sha256();
            $token = (new Builder())->issuedBy($domain) // Configures the issuer (iss claim)
            ->permittedFor($domain) // Configures the audience (aud claim)
            ->identifiedBy($uid['id'], true) // Configures the id (jti claim), replicating as a header item
            ->issuedAt($time) // Configures the time that the token was issue (iat claim)
            ->canOnlyBeUsedAfter($time + 5) // Configures the time that the token can be used (nbf claim)
            ->expiresAt($time + $reset_password_token_expiration_time_in_minutes*60) // Configures the expiration time of the token (exp claim)
            ->withClaim('uid', $uid) // Configures a new claim, called "uid"
            ->getToken($signer, new Key($key)); // Retrieves the generated token
            $stringToken = $token->__toString();
        }

        $logo =  $this->model_company->getFirstCompanyLogo();
        $body = $this->lang->line('application_reset_email_body');
        $body = str_replace('#LOGO#', base_url()."/$logo",$body);
        $body = str_replace('#SITE#', base_url(),$body);
        $body = str_replace('#LINK#', base_url()."auth/passwordReset/$stringToken", $body);

        $reset_password_time_in_hours_to_reset_again= $this->model_settings->getValueIfAtiveByName('reset_password_time_in_hours_to_reset_again');
        if (!$reset_password_time_in_hours_to_reset_again) {
            $reset_password_time_in_hours_to_reset_again = 6; //  6 horas para usar o esqueci de minha senha de novo
        }

        $email_marketing = $this->model_settings->getValueIfAtiveByName('email_marketing');

        if ($this->model_reset_tokens->create($stringToken,$uid['id'], $reset_password_time_in_hours_to_reset_again)){

            $resp = get_instance()->sendEmailMarketing($email, $this->lang->line('application_reset_email_subject'), $body, $email_marketing);

            $email_ok = false;
            if (is_array($resp)) {
                if (key_exists('ok', $resp)){
                    $email_ok = $resp['ok'];
                }
            }
            if (!$email_ok) {
                $this->model_reset_tokens->remove($stringToken,$uid['id']);
                $error_message = $resp['msg'] ?? '';
                throw new Exception("Ocorreu um erro para enviar o e-mail. $error_message");
            }
        }
    }
}