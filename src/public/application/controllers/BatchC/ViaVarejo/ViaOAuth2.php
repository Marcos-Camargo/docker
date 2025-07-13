<?php
/*
 
Realiza a authenticação no via varejo

*/   
 class ViaOAuth2 {
        
    private $url_autority = 'https://api-mktplace.viavarejo.com.br/oauth';
    
    private $access_token_development = 'YWTH7dOvY5xd';

    public function __construct() 
    { 
        echo '[VIA VAREJO]['. strtoupper(__CLASS__) .'] '. strtoupper(__FUNCTION__) . PHP_EOL;
    }

	function getUrlAuthority() { return $this->url_autority; }

    function authorize($client_id, $client_secret, $grant_code)
    {
        // if (ENVIRONMENT === 'production') {
        //     $prod_data = array("code" => $grant_code, "token_type" => "access_token");
        //     $json_data = json_encode($prod_data);
            
        //     $retorno = postRequest($this->getUrlAuthority(), $client_id, $client_secret, $json_data);
    
        //     if (($retorno['httpcode'] >= 200) && (($retorno['httpcode'] < 300))){
        //         $content = json_decode($retorno["content"]);
        //         return  array(
        //             'client_id' => $client_id,
        //             'access_token' => $content->access_token
        //         );
        //     }
        //     return null;
        // }
        return  array(
            'client_id' => $client_id,
            'access_token' => $this->access_token_development
        );
    }

    private function postRequest($url, $client_id, $client_secret, $post_data){
		
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_POST		=> true,
			CURLOPT_POSTFIELDS	=> $post_data,
			CURLOPT_HTTPHEADER =>  array(
				'accept: application/json;charset=UTF-8',
                'content-type: application/json', 
                'Authorization: Basic '. base64_encode($client_id.':'.$client_secret) 
				)
	    );
        
        $ch         = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content    = curl_exec( $ch );
		$httpcode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err        = curl_errno( $ch );
	    $errmsg     = curl_error( $ch );
	    $header     = curl_getinfo( $ch );
        
        curl_close( $ch );
        
		$header['httpcode'] = $httpcode;
	    $header['errno']    = $err;
	    $header['errmsg']   = $errmsg;
	    $header['content']  = $content;
        
        return $header;
	}
}