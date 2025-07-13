<?php 
/*
Model de Acesso ao BD para Rastreamento de Pedidos.
*/  

class Model_newsletter extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /***
     * Insere as informações de consulta ao código de rastreio na tabela "newsletter".
     * 
     * string $tracking_code: código de rastreio;
     * string $email: endereço de e-mail usado junto com a consulta ao rastreio de um pedido 
     *      (Observação: O endereço fornecido não tem relação com o endereço usado para fazer a compra.
     *      Em vez disso, o endereço é fornecido para que possamos enviar ao usuários informações de marketing.);
     * int $final_consumer: se o valor da variável é "0", então o endereço de e-mail pertence a um seller; 
     *      em caso contrário, isto é, se o valor é "1", então o endereço de e-mail pertence a um cliente final;
     * int $lgpd: o valor informado serve para indicar a concordância do usuário com a LGPD.
     *      O usuário:
     *          - 0: não concorda com os termos da LGDP nem em receber e-mails promocionais;
     *          - 1: concorda SOMENTE com os termos da LGDP;
     *          - 2: concorda SOMENTE em receber e-mails promocionais;
     *          - 3: concorda tanto com os termos da LGDP quanto em receber e-mails promocionais.
    */
    public function insert(
        string $tracking_code, 
        string $email,
        int $final_consumer, 
        int $lgpd
    ) {
        if (
            empty($tracking_code) || 
            empty($email) || 
            (($final_consumer != 0) && ($final_consumer != 1)) || 
            (($lgpd < 0) && ($lgpd > 3))
        ) {
            return false;
        }

        $this->db->insert('newsletter', array(
            'tracking_code'     => $tracking_code,
            'email'             => $email,
            'final_consumer'    => $final_consumer,
            'agreement'         => $lgpd,
            'request_date'      => date("Y-m-d H:i:s", strtotime("now"))
        ));
    }

    /***
     * Adiciona o endereço de e-mail do usuário no Mailchimp remoto.
     * 
     * O método PUT é usado porque isto garante que o endereço será armazenado, 
     * mesmo que ele já tenha sido armazenado previamente.
     */
    public function putListMemberMailchimp(
        string $dc,
        string $apikey,
        string $list_id,
        string $contact_email
    ) {
        $result = array(
            'status' => 'fail'
        );

        if ($contact_email) {
            $data = array(
                "email_address"         => $contact_email,
                "status_if_new"         => "subscribed",
                "email_type"            => "html",
                "status"                => "subscribed",
                "language"              => "pt"
            );

            $API_URL = 'https://' . $dc . '.api.mailchimp.com/3.0/lists/' . $list_id . '/members/' . md5(strtolower($contact_email)) . '?skip_merge_validation=true';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $API_URL);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Basic ' . base64_encode('user:' . $apikey . '-' . $dc)));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $result = json_decode(curl_exec($ch));
            curl_close($ch);
        }

        return $result;
    }
}