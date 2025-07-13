<?php

require APPPATH . "/libraries/REST_Controller.php";

class FreteConectala extends REST_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_settings');
    }
	
	function get_web_page( $url,$post_data )
	{
	    $options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => '',       // handle all encodings
	        CURLOPT_USERAGENT      => 'conectala', // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
	        CURLOPT_TIMEOUT        => 120,      // timeout on response
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_POST		=> true,
			CURLOPT_POSTFIELDS	=> $post_data,
            CURLOPT_HTTPHEADER =>  array('Content-Type:application/json'),
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

    function fmtNum($num, $padrao = "US") {    // Ou BR
        $temp = str_replace(",", "", $num);
        $temp = str_replace(".", "", $temp);
        if (is_numeric($temp)) {
            $num = str_replace(",", ".", $num);
            $ct = false;
            while (!$ct) {
                $temp = str_replace(".", "", $num,$cnt);
                if ($cnt < 2) {
                    $ct = true;
                } else {
                    $pos = strpos($num,".");
                    $num = substr($num,0,$pos).substr($num,$pos+1);
                    $ct = false;
                }
            }
            return $num;
        } else {
            return false;
        }
    }

    public function somar_dias_uteis( $str_data, $int_qtd_dias_somar, $feriados = '')
    {
        // Caso seja informado uma data do MySQL do tipo DATETIME - aaaa-mm-dd 00:00:00
        // Transforma para DATE - aaaa-mm-dd
        $str_data = substr( $str_data, 0, 10 );
        // Se a data estiver no formato brasileiro: dd/mm/aaaa
        // Converte-a para o padrão americano: aaaa-mm-dd
        if ( preg_match( "@/@", $str_data ) == 1 ) {
            $str_data = implode( "-", array_reverse( explode( "/", $str_data ) ) );
        }
        // chama a funcao que calcula a pascoa
        $pascoa_dt = $this->dataPascoa( date( 'Y' ) );
        $aux_p = explode( "/", $pascoa_dt );
        $aux_dia_pas = $aux_p[0];
        $aux_mes_pas = $aux_p[1];
        $pascoa = "$aux_mes_pas" . "-" . "$aux_dia_pas"; // crio uma data somente como mes e dia
        // chama a funcao que calcula o carnaval
        $carnaval_dt = $this->dataCarnaval( date( 'Y' ) );
        $aux_carna = explode( "/", $carnaval_dt );
        $aux_dia_carna = $aux_carna[0];
        $aux_mes_carna = $aux_carna[1];
        $carnaval = "$aux_mes_carna" . "-" . "$aux_dia_carna";
        // chama a funcao que calcula corpus christi
        $CorpusChristi_dt = $this->dataCorpusChristi( date( 'Y' ) );
        $aux_cc = explode( "/", $CorpusChristi_dt );
        $aux_cc_dia = $aux_cc[0];
        $aux_cc_mes = $aux_cc[1];
        $Corpus_Christi = "$aux_cc_mes" . "-" . "$aux_cc_dia";
        // chama a funcao que calcula a sexta feira santa
        $sexta_santa_dt = $this->dataSextaSanta( date( 'Y' ) );
        $aux = explode( "/", $sexta_santa_dt );
        $aux_dia = $aux[0];
        $aux_mes = $aux[1];
        $sexta_santa = "$aux_mes" . "-" . "$aux_dia";
        $feriados = array(
            "01-01", //Ano Novo
            $carnaval,
            $sexta_santa,
            $pascoa,
            $Corpus_Christi,
            "04-21", //Tiradentes
            "05-01", //Dia Mundial do Trabalho
            "07-09", //Independência do Brasil
            "10-12", //Nossa Senhora Aparecida
            "11-02", //Finados
            "11-15", //Proclamação da República
            "12-25", //Natal
        );

        $array_data = explode( '-', $str_data );
        $count_days = 0;
        $int_qtd_dias_uteis = 0;
        while ( $int_qtd_dias_uteis < $int_qtd_dias_somar) {
            $count_days++;
            $day = date( 'm-d', strtotime( '+' . $count_days . 'day', strtotime( $str_data ) ) );
            $dias_da_semana = gmdate( 'w', strtotime( '+' . $count_days . ' day', gmmktime( 0, 0, 0, $array_data[1], $array_data[2], $array_data[0] ) ) );
            if ($dias_da_semana != '0' && $dias_da_semana != '6' && !in_array( $day, $feriados ) ) {
                $int_qtd_dias_uteis++;
            }
        }
        return date('Y-m-d', strtotime( '+' . $count_days . ' day', strtotime( $str_data ) ) );
        #return gmdate( 'Y-m-d', strtotime( '+' . $count_days . ' day', strtotime( $str_data ) ) );
    }

    public function diminuir_dias_uteis( $str_data, $int_qtd_dias_remover, $feriados = '' )
    {
        // Caso seja informado uma data do MySQL do tipo DATETIME - aaaa-mm-dd 00:00:00
        // Transforma para DATE - aaaa-mm-dd
        $str_data = substr( $str_data, 0, 10 );
        // Se a data estiver no formato brasileiro: dd/mm/aaaa
        // Converte-a para o padrão americano: aaaa-mm-dd
        if ( preg_match( "@/@", $str_data ) == 1 ) {
            $str_data = implode( "-", array_reverse( explode( "/", $str_data ) ) );
        }
        // chama a funcao que calcula a pascoa
        $pascoa_dt = $this->dataPascoa( date( 'Y' ) );
        $aux_p = explode( "/", $pascoa_dt );
        $aux_dia_pas = $aux_p[0];
        $aux_mes_pas = $aux_p[1];
        $pascoa = "$aux_mes_pas" . "-" . "$aux_dia_pas"; // crio uma data somente como mes e dia
        // chama a funcao que calcula o carnaval
        $carnaval_dt = $this->dataCarnaval( date( 'Y' ) );
        $aux_carna = explode( "/", $carnaval_dt );
        $aux_dia_carna = $aux_carna[0];
        $aux_mes_carna = $aux_carna[1];
        $carnaval = "$aux_mes_carna" . "-" . "$aux_dia_carna";
        // chama a funcao que calcula corpus christi
        $CorpusChristi_dt = $this->dataCorpusChristi( date( 'Y' ) );
        $aux_cc = explode( "/", $CorpusChristi_dt );
        $aux_cc_dia = $aux_cc[0];
        $aux_cc_mes = $aux_cc[1];
        $Corpus_Christi = "$aux_cc_mes" . "-" . "$aux_cc_dia";
        // chama a funcao que calcula a sexta feira santa
        $sexta_santa_dt = $this->dataSextaSanta( date( 'Y' ) );
        $aux = explode( "/", $sexta_santa_dt );
        $aux_dia = $aux[0];
        $aux_mes = $aux[1];
        $sexta_santa = "$aux_mes" . "-" . "$aux_dia";
        $feriados = array(
            "01-01", //Ano Novo
            $carnaval,
            $sexta_santa,
            $pascoa,
            $Corpus_Christi,
            "04-21", //Tiradentes
            "05-01", //Dia Mundial do Trabalho
            "07-09", //Independência do Brasil
            "10-12", //Nossa Senhora Aparecida
            "11-02", //Finados
            "11-15", //Proclamação da República
            "12-25", //Natal
        );

        $array_data = explode( '-', $str_data );
        $count_days = 0;
        $int_qtd_dias_uteis = 0;
        while ( $int_qtd_dias_uteis < $int_qtd_dias_remover ) {
            $count_days++;
            $day = date( 'm-d', strtotime( '-' . $count_days . 'day', strtotime( $str_data ) ) );
            if ( ($dias_da_semana = gmdate( 'w', strtotime( '-' . $count_days . ' day', gmmktime( 0, 0, 0, $array_data[1], $array_data[2], $array_data[0] ) ) ) ) != '0' && $dias_da_semana != '6' && !in_array( $day, $feriados ) ) {
                $int_qtd_dias_uteis++;
            }
        }
        return gmdate( 'Y-m-d', strtotime( '-' . $count_days . ' day', strtotime( $str_data ) ) );
    }

    function dataPascoa( $ano = false, $form = "d/m/Y" ) {
        $ano = $ano ? $ano : date( "Y" );
        if ( $ano < 1583 ) {
            $A = ($ano % 4);
            $B = ($ano % 7);
            $C = ($ano % 19);
            $D = ((19 * $C + 15) % 30);
            $E = ((2 * $A + 4 * $B - $D + 34) % 7);
            $F = ( int ) (($D + $E + 114) / 31);
            $G = (($D + $E + 114) % 31) + 1;
            return date( $form, mktime( 0, 0, 0, $F, $G, $ano ) );
        } else {
            $A = ($ano % 19);
            $B = ( int ) ($ano / 100);
            $C = ($ano % 100);
            $D = ( int ) ($B / 4);
            $E = ($B % 4);
            $F = ( int ) (($B + 8) / 25);
            $G = ( int ) (($B - $F + 1) / 3);
            $H = ((19 * $A + $B - $D - $G + 15) % 30);
            $I = ( int ) ($C / 4);
            $K = ($C % 4);
            $L = ((32 + 2 * $E + 2 * $I - $H - $K) % 7);
            $M = ( int ) (($A + 11 * $H + 22 * $L) / 451);
            $P = ( int ) (($H + $L - 7 * $M + 114) / 31);
            $Q = (($H + $L - 7 * $M + 114) % 31) + 1;
            return date( $form, mktime( 0, 0, 0, $P, $Q, $ano ) );
        }
    }

    // dataCarnaval(ano, formato);
    // Autor: Yuri Vecchi
    //
    // Funcao para o calculo do Carnaval
    // Retorna o dia do Carnaval no formato desejado ou false.
    //
    // ######################ATENCAO###########################
    // Esta funcao sofre das limitacoes de data de mktime()!!!
    // ########################################################
    //
    // Possui dois parametros, ambos opcionais
    // ano = ano com quatro digitos
    //	 Padrao: ano atual
    // formato = formatacao da funcao date() http://br.php.net/date
    //	 Padrao: d/m/Y

    function dataCarnaval( $ano = false, $form = "d/m/Y" ) {
        $ano = $ano ? $ano : date( "Y" );
        $a = explode( "/", $this->dataPascoa( $ano ) );
        return date( $form, mktime( 0, 0, 0, $a[1], $a[0] - 47, $a[2] ) );
    }

    // dataCorpusChristi(ano, formato);
    // Autor: Yuri Vecchi
    //
    // Funcao para o calculo do Corpus Christi
    // Retorna o dia do Corpus Christi no formato desejado ou false.
    //
    // ######################ATENCAO###########################
    // Esta funcao sofre das limitacoes de data de mktime()!!!
    // ########################################################
    //
    // Possui dois parametros, ambos opcionais
    // ano = ano com quatro digitos
    //	 Padrao: ano atual
    // formato = formatacao da funcao date() http://br.php.net/date
    //	 Padrao: d/m/Y

    function dataCorpusChristi( $ano = false, $form = "d/m/Y" ) {
        $ano = $ano ? $ano : date( "Y" );
        $a = explode( "/", $this->dataPascoa( $ano ) );
        return date( $form, mktime( 0, 0, 0, $a[1], $a[0] + 60, $a[2] ) );
    }

    // dataSextaSanta(ano, formato);
    // Autor: Yuri Vecchi
    //
    // Funcao para o calculo da Sexta-feira santa ou da Paixao.
    // Retorna o dia da Sexta-feira santa ou da Paixao no formato desejado ou false.
    //
    // ######################ATENCAO###########################
    // Esta funcao sofre das limitacoes de data de mktime()!!!
    // ########################################################
    //
    // Possui dois parametros, ambos opcionais
    // ano = ano com quatro digitos
    // Padrao: ano atual
    // formato = formatacao da funcao date() http://br.php.net/date
    // Padrao: d/m/Y

    function dataSextaSanta( $ano = false, $form = "d/m/Y" ) {
        $ano = $ano ? $ano : date( "Y" );
        $a = explode( "/", $this->dataPascoa( $ano ) );
        return date( $form, mktime( 0, 0, 0, $a[1], $a[0] - 2, $a[2] ) );
    }
    
	public function tiraAcentos($str) {
	 	return strtr(utf8_decode(urldecode($str)),
	                 utf8_decode('ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'),
	                             'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy');
	}

    function restApi( $url,$post_data,$timout = 99999 )
    {
        $options = array(
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => '',       // handle all encodings
            CURLOPT_USERAGENT      => 'conectala', // who am i
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 0,      // timeout on connect
            CURLOPT_TIMEOUT        => $timout,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
            CURLOPT_POST		=> true,
            CURLOPT_POSTFIELDS	=> $post_data,
            CURLOPT_HTTPHEADER =>  array('Content-Type:application/json'),
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

    public function getEmailClient(string $email, string $idPedido, string $intTo): string
    {
        if (empty($email)) {
            return '';
        }

        $hide_marketplace_email = $this->model_settings->getSettingDatabyName('hide_marketplace_email');

        if ($hide_marketplace_email['status'] == 1) {
            $orderIdClean = preg_replace('/[^a-zA-Z0-9]/', '', $idPedido);
            return  $intTo . $orderIdClean . '@example.com';
        } else {
            return $email;
        }
    }
}