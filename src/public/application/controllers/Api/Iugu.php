<?php
require APPPATH . '/libraries/REST_Controller.php';

class Iugu extends REST_Controller {
    
    /**
     * Get All Data from this method.
     *
     * @return Response
     */
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Get All Data from this method.
     *
     * @return Response
     */
    public function index(){
        
        echo "entrou aqui";
        die;
        
    }
    
    function geraToken($token){
        
        return "chegou na gera Token";
        
    }
    
    
    
    public function index_get($id = NULL)
    {
        $data = $this->input->get();
        
        header('Content-type: application/json');
        if (!array_key_exists ('zipCode', $data)) {
            $json_msg = json_encode([
                'freights' => Array()
            ],JSON_UNESCAPED_UNICODE);
            echo stripslashes($json_msg);
            $this->response('', REST_Controller::HTTP_BAD_REQUEST);
            die;
        }
        if (!array_key_exists ('skuId', $data)) {
            $json_msg = json_encode([
                'freights' => Array()
            ],JSON_UNESCAPED_UNICODE);
            echo stripslashes($json_msg);
            $this->response('', REST_Controller::HTTP_BAD_REQUEST);
            die;
        }
        $zip = $data['zipCode'];
        $tmpArray = explode("|",$data['skuId']);
        //$volumes = array();
        foreach ($tmpArray as $skuqtd) {
            $temp =explode(",",$skuqtd);
            $skus_key[] = $temp[0];
            $volumes[] = array(
                'sku' => $temp[0],
                'quantity' => (int) $temp[1]
            );
        }
        
        $fr = Array();
        $fr["destinatario"] = Array ( "tipo_pessoa" => 1,
            "endereco" => Array ( "cep" => $zip));
        $prim_cpy = 0;
        foreach ($volumes as $vol) {
            $sku = $vol['sku'];
            $sql = "SELECT * FROM bling_ult_envio WHERE skubling = ? and int_to ='VIA'";
            $query = $this->db->query($sql, array($sku));
            $row_ult = $query->row_array();
            if (is_null($row_ult)) {
                //produto não existe
                $json_msg = json_encode([
                    'freights' => Array()
                ],JSON_UNESCAPED_UNICODE);
                echo stripslashes($json_msg);
                $this->response($json_msg, REST_Controller::HTTP_BAD_REQUEST);
                $this->log_data("api", "FreteVia Consulta Frete", "Produto inexistente para a Via Varejo: ".print_r($data,true),"W");
                die;
            }
            $cpy = $row_ult['company_id'];
            if ($prim_cpy == 0) {
                $prim_cpy = $cpy; // leio a primeira compania do pacote
            }
            if ($prim_cpy != $cpy) {
                //$this->response([
                //'message' => 'Região de entrega não atendida'
                //], REST_Controller::HTTP_NOT_FOUND);
                
                $json_msg = json_encode([
                    'freights' => Array()
                ],JSON_UNESCAPED_UNICODE);
                echo stripslashes($json_msg);
                $this->response($json_msg, REST_Controller::HTTP_BAD_REQUEST);
                
                $this->log_data("api", "FreteVia Consulta Frete", "Pedido com mais de uma empresa: ".print_r($data,true),"I");
                die;
            }
            if ($vol['quantity']> $row_ult['qty_atual'] ) {
                // quantidade maior que o estoque
                $json_msg = json_encode([
                    'freights' => Array()
                ],JSON_UNESCAPED_UNICODE);
                echo stripslashes($json_msg);
                $this->response($json_msg, REST_Controller::HTTP_BAD_REQUEST);
                $this->log_data("api", "FreteVia Consulta Frete", "Sem estoque ".print_r($data,true),'W');
                die;
            }
            //$sql = "SELECT altura, largura, profundidade, peso_bruto FROM products WHERE id = ? ";
            //$query = $this->db->query($sql, array($row_ult['prd_id']));
            //$prod = $query->row_array();
            
            $tipo_volume_codigo = intval($row_ult['tipo_volume_codigo']);
            if (is_null($row_ult['tipo_volume_codigo'])) {
                $tipo_volume_codigo = 999;
            }
            
            //$tipo_volume_codigo = $row['tipo_volume_codigo'];
            $sql = "SELECT * FROM prefixes WHERE company_id = ?";
            $query = $this->db->query($sql, array($cpy));
            $row_pr = $query->row_array();
            $origin = $row_pr['cep'];
            $token_fr = $row_pr['token_fr'];
            $vl = Array (
                "tipo" => $tipo_volume_codigo,
                "sku" => $vol['sku'],
                "quantidade" => (int) $vol['quantity'],
                "altura" => (float) $row_ult['altura'] / 100,
                "largura" => (float) $row_ult['largura'] /100,
                "comprimento" => (float) $row_ult['profundidade'] /100,
                "peso" => (float) $row_ult['peso_bruto'],
                "valor" => $row_ult['price'] * $vol['quantity'],
                "volumes_produto" => 1,
                "consolidar" => false,
                "sobreposto" => false,
                "tombar" => false);
            $fr['volumes'][] = $vl;
        }
        $sku = $volumes[0]['sku'];
        $fr["remetente"] = Array (
            "cnpj" => $row_pr['CNPJ']
            );
        //$fr["expedidor"] = Array (
        //	"cnpj" => $row_pr['CNPJ'],
        //	"endereco" => Array( 'cep' => $row_pr['cep'])
        //	);
        $fr["codigo_plataforma"] = "nyHUB56ml";
        // $fr["token"] = "5d1c7889ff8789959cb39eb151a3698e";  // Rick pegar o Token do Parceiro., talvez colcoar na bling_ult_envio
        $fr["token"] = $token_fr;
        $fr["retornar_consolidacao"] = true;
        //var_dump($fr);
        $json_data = json_encode($fr,JSON_UNESCAPED_UNICODE);
        $json_data = stripslashes($json_data);
        //rick - talvez mudar para o protocolo grpc
        //https://github.com/freterapido/sdk-grpc
        //https://github.com/freterapido/sdk-grpc/blob/master/exemplos/php/index.php
        
        $url = "https://freterapido.com/api/external/embarcador/v1/quote-simulator";
        
        $data = $this->get_web_page( $url,$json_data);
        
        //rick - testando se voltou tudo ok - Feito
        if (!($data['httpcode']=="200"))  {
            // Consulta ao Frete Rápido não funcionou.
            // echo 'ERRO - httpcode: '.$data['httpcode'].' RESPOSTA FR: '.$data['content'].' DADOS ENVIADOS:'.$json_data;
            // Nao consegui alocar frete então devolvo a resposta esperada da B2W
            $json_msg = json_encode([
                'freights' => Array()
            ],JSON_UNESCAPED_UNICODE);
            echo stripslashes($json_msg);
            $this->response('', REST_Controller::HTTP_BAD_REQUEST);
            //gravar LOG com o Erro - Como este controller não usa o Admin Conttroler, puxei a log_data para cá
            $this->log_data("api", "FreteVia Consulta Frete", 'ERRO - httpcode: '.$data['httpcode'].' RESPOSTA FR: '.$data['content'].' DADOS ENVIADOS:'.$json_data,"E");
            die;
        }
        
        $retorno_fr = $data['content'];
        $data = json_decode($data['content'],true);
        $transp = $data['transportadoras'];
        if (count($transp) == 0) {
            // Não voltou transportadora.
            $json_msg = json_encode([
                'freights' => Array()
            ],JSON_UNESCAPED_UNICODE);
            echo stripslashes($json_msg);
            $this->response('', REST_Controller::HTTP_BAD_REQUEST);
            die;
        }
        // Adiciono a taxa de frete ao valor retornado
        $sql = 'SELECT av.value FROM attribute_value av, attributes a WHERE a.name ="frete_taxa" and a.id = av.attribute_parent_id';
        $query = $this->db->query($sql);
        $row_taxa = $query->row_array();
        $transp[0]['preco_frete'] += (float) $row_taxa['value'];
        
        foreach ($volumes as $vol) {
            $ret[]=Array (
                'skuIdOrigin' => $vol['sku'],
                'quantity' => $vol['quantity'],
                'freightAmount' =>  $transp[0]['preco_frete'],
                'deliveryTime' => $transp[0]['prazo_entrega'],
                'freightType' => 'NORMAL'
                );
        }
        
        $retorno = Array();
        $retorno['freights'] = $ret;
        $retorno['freightAdditionalInfo'] = $transp[0]['nome'];
        $retorno['sellerMpToken'] = 'xxxxxxxxxxxxxxxx';
        
        $json_data = json_encode($retorno,JSON_UNESCAPED_UNICODE);
        $json_data = stripslashes($json_data);
        
        // Retorna Resposta para o a B2W
        //echo $json_data;
        //  Tirei a resposta por echo e coloquei por response...
        $this->response($retorno, REST_Controller::HTTP_OK);
        
        sort($skus_key);
        $quotes = Array();
        $quotes['marketplace'] = $row_ult['int_to'];
        $quotes['zip'] = $zip;
        $quotes['sku'] =  json_encode($skus_key);
        $quotes['cost'] = $transp[0]['preco_frete'];
        $quotes['id'] = $data['token_oferta'];
        $quotes['oferta'] = $transp[0]['oferta'];
        $quotes['validade'] = $transp[0]['validade'];
        $quotes['retorno'] = $retorno_fr;
        $quotes['frete_taxa'] = $row_taxa['value'];
        $this->db->replace('quotes_ship', $quotes);
        
    }
    
    
    
    
    function get_web_page( $url,$post_data )
    {
        $options = array(
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_USERAGENT      => "conectala", // who am i
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
            CURLOPT_POST		=> true,
            CURLOPT_POSTFIELDS	=> $post_data,
            CURLOPT_SSL_VERIFYPEER => false     // Disabled SSL Cert checks
        );
        $ch      = curl_init( $url );
        curl_setopt_array( $ch, $options );
        $content = curl_exec( $ch );
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );
        $header['httpcode']   = $httpcode;
        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $content;
        return $header;
    }
    
}