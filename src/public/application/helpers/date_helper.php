<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

define('DATETIME_INTERNATIONAL', 'Y-m-d H:i:s');
define('DATE_INTERNATIONAL', 'Y-m-d');
define('DATETIME_BRAZIL', 'd/m/Y H:i:s');
define('DATE_BRAZIL', 'd/m/Y');
define('DATETIME_INTERNATIONAL_TIMEZONE', 'Y-m-d H:i:sP');
define('TIMEZONE_DEFAULT', 'America/Fortaleza');

if (!function_exists('datetimeNoGMT')) {
    function datetimeNoGMT(string $value): string
    {
        $time_zone = new DateTimeZone(TIMEZONE_DEFAULT);
        $date = new DateTime($value);
        $date->setTimezone($time_zone);
        $scheduledFor = $date->format(DATETIME_INTERNATIONAL);

        return $scheduledFor;
    }
}

if (!function_exists('dateNoGMT')) {
    function dateNoGMT(string $value): string
    {
        $time_zone = new DateTimeZone(TIMEZONE_DEFAULT);
        $date = new DateTime($value);
        $date->setTimezone($time_zone);
        $scheduledFor = $date->format(DATE_BRAZIL);

        return $scheduledFor;
    }
}

if (!function_exists('datetimeBrazil')) {
    function datetimeBrazil(?string $value, ?string $timeZone = TIMEZONE_DEFAULT): string
    {
        if(empty($value)){
            return '';
        }
         
        return dateFormat($value, DATETIME_BRAZIL, $timeZone);
    }
}

if (!function_exists('dateBrazil')) {
    function dateBrazil(string $value): string
    {
        return dateFormat($value, DATE_BRAZIL);
    }
}

if (!function_exists('dateFormat')) {
    function dateFormat(string $value, string $format, ?string $timeZone = TIMEZONE_DEFAULT): string
    {
        $date = new DateTime($value);

        if ($timeZone === null) {
            return $date->format($format);
        }

        $time_zone = new DateTimeZone($timeZone);
        $date->setTimezone($time_zone);

        return $date->format($format);

    }
}

if (!function_exists('dateBrazilToDateInternational')) {
    function dateBrazilToDateInternational(string $value): string
    {

        if (!$value) {
            return "";
        }

        $date = DateTime::createFromFormat('d/m/Y', $value);
        return $date->format(DATE_INTERNATIONAL);

    }

}

if (!function_exists('dateTimeBrazilToDateInternational')) {
    function dateTimeBrazilToDateInternational(string $value): string
    {

        if (!$value || strlen($value) !== 19) {
            return $value;
        }

        $date = DateTime::createFromFormat(DATETIME_BRAZIL, $value);
        return $date->format(DATETIME_INTERNATIONAL);

    }

}

if (!function_exists('dateNow')) {
    function dateNow($timezone = null): DateTime
    {
        if ($timezone) {
            $dateTimeNow = new DateTimeZone($timezone);
        }else{
            $dateTimeNow = new DateTimeZone(TIMEZONE_DEFAULT);
        }

        return (new DateTime())->setTimezone($dateTimeNow);

    }
}

if (!function_exists('subtractDateFromNow')) {
    function subtractDateFromNow(int $hours = 0, int $minutes = 0): DateTime
    {

        $date = dateNow();

        if ($hours) {
            $date->sub(new DateInterval('P0Y0DT' . $hours . 'H0M'));
        }
        if ($minutes) {
            $date->sub(new DateInterval('P0Y0DT0H' . $minutes . 'M'));
        }

        return $date;
    }
}
if (!function_exists('subtractDateFromDays')) {
    function subtractDateFromDays(int $days = 15)
    {

        $date = dateNow();
        $date->sub(new DateInterval("P{$days}D"));
        return $date->format('Y-m-d');

    }
}
if (!function_exists('subtractHoursToDatetime')) {
    function subtractHoursToDatetime(string $datetime, int $hours)
    {
        return gmdate( DATETIME_INTERNATIONAL, strtotime( '-' . $hours . ' hour', strtotime( $datetime ) ) );
    }
}
if (!function_exists('subtractMinutesToDatetime')) {
    function subtractMinutesToDatetime(string $datetime, int $minutes)
    {
        return gmdate( DATETIME_INTERNATIONAL, strtotime( '-' . $minutes . ' minute', strtotime( $datetime ) ) );
    }
}
if (!function_exists('addMinutesToDatetime')) {
    function addMinutesToDatetime(string $datetime, int $minutes)
    {
        return gmdate( DATETIME_INTERNATIONAL, strtotime( '+' . $minutes . ' minute', strtotime( $datetime ) ) );
    }
}
if (!function_exists('addDaysToDate')) {
    function addDaysToDate(string $date, int $days)
    {
        switch (strlen($date)) {
            case 16:
                $format = 'Y-m-d H:i';
                break;
            case 19:
                $format = 'Y-m-d H:i:s';
                break;
            default:
                $format = 'Y-m-d';
                break;
        }

        return gmdate( $format, strtotime( '+' . $days . ' day', strtotime( $date ) ) );
    }
}
if (!function_exists('addHoursToDate')) {
    function addHoursToDate(string $date, int $hours = null)
    {
        switch (strlen($date)) {
            case 16:
                $format = 'Y-m-d H:i';
                break;
            case 19:
                $format = 'Y-m-d H:i:s';
                break;
            default:
                $format = 'Y-m-d';
                break;
        }

        return gmdate( $format, strtotime( '+' . $hours . ' hours', strtotime( $date ) ) );
    }
}
if (!function_exists('addMinutesToDate')) {
    function addMinutesToDate(string $date, int $hours = null)
    {
        switch (strlen($date)) {
            case 16:
                $format = 'Y-m-d H:i';
                break;
            case 19:
                $format = 'Y-m-d H:i:s';
                break;
            default:
                $format = 'Y-m-d';
                break;
        }

        return gmdate( $format, strtotime( '+' . $hours . ' minutes', strtotime( $date ) ) );
    }
}
if (!function_exists('addTimesToDate')) {
    function addTimesToDate(string $date, int $days = null, int $hours = null, int $minutes = null)
    {
        if (!is_null($days)) {
            $date = addDaysToDate($date, $days);
        }

        if (!is_null($hours)) {
            $date = addHoursToDate($date, $hours);
        }

        if (!is_null($minutes)) {
            $date = addMinutesToDate($date, $minutes);
        }

        return $date;
    }
}
if (!function_exists('addMonthToDate')) {
    function addMonthToDate(string $date, int $months)
    {
        switch (strlen($date)) {
            case 16:
                $format = 'Y-m-d H:i';
                break;
            case 19:
                $format = 'Y-m-d H:i:s';
                break;
            default:
                $format = 'Y-m-d';
                break;
        }

        return gmdate( $format, strtotime( '+' . $months . ' month', strtotime( $date ) ) );
    }
}
if (!function_exists('subtractMonthToDate')) {
    function subtractMonthToDate(string $date, int $months)
    {
        switch (strlen($date)) {
            case 16:
                $format = 'Y-m-d H:i';
                break;
            case 19:
                $format = 'Y-m-d H:i:s';
                break;
            default:
                $format = 'Y-m-d';
                break;
        }

        return gmdate( $format, strtotime( '-' . $months . ' month', strtotime( $date ) ) );
    }
}
if (!function_exists('parseDateFromFormatToDateObject')) {
    function parseDateFromFormatToDateObject(string $date, string $format = 'Y-m-d\TH:i:s.000\Z')
    {
        $dateParse = date_parse_from_format($format, $date);

        $date = new datetime();
        $date->setDate($dateParse['year'], $dateParse['month'], $dateParse['day']);
        $date->setTime($dateParse['hour'], $dateParse['minute'], $dateParse['second']);

        return $date;

    }
}
if (!function_exists('parseDateFromFormatToDateFormat')) {
    function parseDateFromFormatToDateFormat(string $date, string $format = 'Y-m-d\TH:i:s.000\Z', $outputFormat = DATETIME_INTERNATIONAL)
    {

        $date = parseDateFromFormatToDateObject($date, $format);

        return $date->format($outputFormat);

    }
}
if (!function_exists('dateDiffDays')) {
    function dateDiffDays($later, $earlier)
    {

        return $earlier->diff($later)->format("%r%a");

    }
}
if (!function_exists('subtractMinutesToDatetimeV2')) {
    function subtractMinutesToDatetimeV2(string $datetime, int $minutes)
    {
        return date( DATETIME_INTERNATIONAL, strtotime( '-' . $minutes . ' minute', strtotime( $datetime ) ) );
    }
}
if (!function_exists('subtractHoursToDatetimeV2')) {
    function subtractHoursToDatetimeV2(string $datetime, int $hours)
    {
        return date( DATETIME_INTERNATIONAL, strtotime( '-' . $hours . ' hour', strtotime( $datetime ) ) );
    }
}
if (!function_exists('diminuir_dias_uteis')) {
    function diminuir_dias_uteis($str_data, $int_qtd_dias_remover, $feriados = '') {
        // Caso seja informado uma data do MySQL do tipo DATETIME - aaaa-mm-dd 00:00:00
        // Transforma para DATE - aaaa-mm-dd
        $str_data = substr( $str_data, 0, 10 );
        // Se a data estiver no formato brasileiro: dd/mm/aaaa
        // Converte-a para o padrão americano: aaaa-mm-dd
        if ( preg_match( "@/@", $str_data ) == 1 ) {
            $str_data = implode( "-", array_reverse( explode( "/", $str_data ) ) );
        }
        // chama a funcao que calcula a pascoa
        $pascoa_dt = dataPascoa( date( 'Y' ) );
        $aux_p = explode( "/", $pascoa_dt );
        $aux_dia_pas = $aux_p[0];
        $aux_mes_pas = $aux_p[1];
        $pascoa = "$aux_mes_pas" . "-" . "$aux_dia_pas"; // crio uma data somente como mes e dia
        // chama a funcao que calcula o carnaval
        $carnaval_dt = dataCarnaval( date( 'Y' ) );
        $aux_carna = explode( "/", $carnaval_dt );
        $aux_dia_carna = $aux_carna[0];
        $aux_mes_carna = $aux_carna[1];
        $carnaval = "$aux_mes_carna" . "-" . "$aux_dia_carna";
        // chama a funcao que calcula corpus christi
        $CorpusChristi_dt = dataCorpusChristi( date( 'Y' ) );
        $aux_cc = explode( "/", $CorpusChristi_dt );
        $aux_cc_dia = $aux_cc[0];
        $aux_cc_mes = $aux_cc[1];
        $Corpus_Christi = "$aux_cc_mes" . "-" . "$aux_cc_dia";
        // chama a funcao que calcula a sexta feira santa
        $sexta_santa_dt = dataSextaSanta( date( 'Y' ) );
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
}

if (!function_exists('somar_dias_uteis')) {
    function somar_dias_uteis($str_data, $int_qtd_dias_somar, $feriados = '', $removegmdate = FALSE)
    {

        // limite máximo de 365 dias
        $int_qtd_dias_somar = $int_qtd_dias_somar > 365 ? 365 : $int_qtd_dias_somar;

        // Caso seja informado uma data do MySQL do tipo DATETIME - aaaa-mm-dd 00:00:00
        // Transforma para DATE - aaaa-mm-dd
        $str_data = substr($str_data, 0, 10);
        // Se a data estiver no formato brasileiro: dd/mm/aaaa
        // Converte-a para o padrão americano: aaaa-mm-dd
        if (preg_match("@/@", $str_data) == 1) {
            $str_data = implode("-", array_reverse(explode("/", $str_data)));
        }
        // chama a funcao que calcula a pascoa
        $pascoa_dt = dataPascoa(date('Y'));
        $aux_p = explode("/", $pascoa_dt);
        $aux_dia_pas = $aux_p[0];
        $aux_mes_pas = $aux_p[1];
        $pascoa = "$aux_mes_pas" . "-" . "$aux_dia_pas"; // crio uma data somente como mes e dia
        // chama a funcao que calcula o carnaval
        $carnaval_dt = dataCarnaval(date('Y'));
        $aux_carna = explode("/", $carnaval_dt);
        $aux_dia_carna = $aux_carna[0];
        $aux_mes_carna = $aux_carna[1];
        $carnaval = "$aux_mes_carna" . "-" . "$aux_dia_carna";
        // chama a funcao que calcula corpus christi
        $CorpusChristi_dt = dataCorpusChristi(date('Y'));
        $aux_cc = explode("/", $CorpusChristi_dt);
        $aux_cc_dia = $aux_cc[0];
        $aux_cc_mes = $aux_cc[1];
        $Corpus_Christi = "$aux_cc_mes" . "-" . "$aux_cc_dia";
        // chama a funcao que calcula a sexta feira santa
        $sexta_santa_dt = dataSextaSanta(date('Y'));
        $aux = explode("/", $sexta_santa_dt);
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

        $array_data = explode('-', $str_data);
        $count_days = 0;
        $int_qtd_dias_uteis = 0;
        while ($int_qtd_dias_uteis < $int_qtd_dias_somar) {
            $count_days++;
            $day = date('m-d', strtotime('+' . $count_days . 'day', strtotime($str_data)));
            if (($dias_da_semana = gmdate('w', strtotime('+' . $count_days . ' day', gmmktime(0, 0, 0, $array_data[1], $array_data[2], $array_data[0])))) != '0' && $dias_da_semana != '6' && !in_array($day, $feriados)) {
                $int_qtd_dias_uteis++;
            }
        }
        if ($removegmdate == TRUE) {
            return date('Y-m-d', strtotime('+' . $count_days . ' day', strtotime($str_data)));
        } else {
            return gmdate('Y-m-d', strtotime('+' . $count_days . ' day', strtotime($str_data)));
        }
    }
}

if (!function_exists('dataPascoa')) {
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
}

if (!function_exists('dataCarnaval')) {
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

    function dataCarnaval($ano = false, $form = "d/m/Y")
    {
        $ano = $ano ? $ano : date("Y");
        $a = explode("/", dataPascoa($ano));
        return date($form, mktime(0, 0, 0, $a[1], $a[0] - 47, $a[2]));
    }
}

if (!function_exists('dataCorpusChristi')) {
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

    function dataCorpusChristi($ano = false, $form = "d/m/Y")
    {
        $ano = $ano ? $ano : date("Y");
        $a = explode("/", dataPascoa($ano));
        return date($form, mktime(0, 0, 0, $a[1], $a[0] + 60, $a[2]));
    }
}

if (!function_exists('dataSextaSanta')) {
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

    function dataSextaSanta($ano = false, $form = "d/m/Y")
    {
        $ano = $ano ? $ano : date("Y");
        $a = explode("/", dataPascoa($ano));
        return date($form, mktime(0, 0, 0, $a[1], $a[0] - 2, $a[2]));
    }
}