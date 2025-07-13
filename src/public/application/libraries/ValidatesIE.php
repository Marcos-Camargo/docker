<?php

class ValidatesIE
{
    public static function check($insc_estadual, $uf)
    {
        // Extrai somente os números
        $ie = preg_replace('/[^0-9]/', '', $insc_estadual);
        
        // Lida com o caso de apenas palavras sendo passadas.
        if (strlen($ie) == 0) {
            return false;
        }
        
        // Chama a classe correta de acordo com a UF passada no formulário
        switch ($uf) {
            case 'AC':
                return Acre::check($ie);
                break;
            case 'AL':
                return Alagoas::check($ie);
                break;
            case 'AP':
                return Amapa::check($ie);
                break;
            case 'AM':
                return Amazonas::check($ie);
                break;
            case 'BA':
                return Bahia::check($ie);
                break;
            case 'CE':
                return Ceara::check($ie);
                break;
            case 'DF':
                return DistritoFederal::check($ie);
                break;
            case 'ES':
                return EspiritoSanto::check($ie);
                break;
            case 'GO':
                return Goias::check($ie);
                break;
            case 'MA':
                return Maranhao::check($ie);
                break;
            case 'MT':
                return MatoGrosso::check($ie);
                break;
            case 'MS':
                return MatoGrossoDoSul::check($ie);
                break;
            case 'MG':
                return MinasGerais::check($ie);
                break;
            case 'PA':
                return Para::check($ie);
                break;
            case 'PB':
                return Paraiba::check($ie);
                break;
            case 'PR':
                return Parana::check($ie);
                break;
            case 'PE':
                return Pernambuco::check($ie);
                break;
            case 'PI':
                return Piaui::check($ie);
                break;
            case 'RJ':
                return RioDeJaneiro::check($ie);
                break;
            case 'RN':
                return RioGrandeDoNorte::check($ie);
                break;
            case 'RS':
                return RioGrandeDoSul::check($ie);
                break;
            case 'RO':
                return Rondonia::check($ie);
                break;
            case 'RR':
                return Roraima::check($ie);
                break;
            case 'SC':
                return SantaCatarina::check($ie);
                break;
            case 'SP':
                return SaoPaulo::check($ie);
                break;
            case 'SE':
                return Sergipe::check($ie);
                break;
            case 'TO':
                return Tocantins::check($ie);
                break;
            
            default:
                return false;
                break;
        }
    }
}

class Acre
{
    /**
     * Verifica se a inscrição estadual é válida para o Acre (AC)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_AC.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 13) {
            $inscricao_estadual = str_pad($inscricao_estadual, 13, 0, STR_PAD_LEFT);
        }
        // se não tiver 13 digitos não é valido
        if (strlen($inscricao_estadual) != 13) {
            $valid = false;
        }
        if ($valid && substr($inscricao_estadual, 0, 2) != '01') {
            $valid = false;
        }
        if ($valid && !self::calculaDigitos($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * Valida o dígito da inscrição estadual
     *
     * Pesos: 4 3 2 9 8 7 6 5 4 3 2 para primeiro digito
     * Pesos: 5 4 3 2 9 8 7 6 5 4 3 2 para segundo digito
     * @param $inscricao_estadual string inscricao estadual
     * @return bool true caso os digitos sejam verificados, false caso contrário.
     */
    private static function calculaDigitos($inscricao_estadual)
    {

        $length = strlen($inscricao_estadual);
        $corpo = substr($inscricao_estadual, 0, $length - 2);

        // Calculando o primeiro dígito
        $_1dig = self::calculaDigito($corpo);
        //adicionando o primeiro dígito no corpo para calcular o segundo dígito
        $_2dig = self::calculaDigito($corpo . $_1dig);

        $pos2dig = strlen($inscricao_estadual) - 1;

        $pos1dig = strlen($inscricao_estadual) - 2;

        return $inscricao_estadual[$pos1dig] == $_1dig && $inscricao_estadual[$pos2dig] == $_2dig;
    }

    /**
     * Informa o digito para o corpo passado
     * @param $corpo
     * @return int dígito
     */
    private static function calculaDigito($corpo)
    {
        // vai começar em 4 quando o digito a ser verificado for o primeiro, e em 5 quando for o segundo
        $peso = strlen($corpo) - 7;

        $soma = 0;
        foreach (str_split($corpo) as $digito) {
            $soma += $digito * $peso;
            $peso--;
            if ($peso == 1) {
                $peso = 9;
            }
        }

        $modulo = 11;

        $resto = $soma % $modulo;

        $dig = $modulo - $resto;
        if ($dig >= 10) {
            $dig = 0;
        }

        return $dig;
    }
}

class Alagoas
{

    /**
     * Verifica se a inscrição estadual é válida para o Alagoas (AL)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_AL.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 9) {
            $inscricao_estadual = str_pad($inscricao_estadual, 9, 0, STR_PAD_LEFT);
        }
        // se não tiver 9 digitos não é valido
        if (strlen($inscricao_estadual) != 9) {
            $valid = false;
        }
        if ($valid && substr($inscricao_estadual, 0, 2) != '24') {
            $valid = false;
        }
        if ($valid && !self::calculaDigito($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * Valida o dígito da inscrição estadual
     *
     * Pesos: 9 8 7 6 5 4 3 2 para calculo do dígito
     * @param $inscricao_estadual string inscricao estadual
     * @return bool true caso o digito seja verificado, false caso contrário.
     */
    private static function calculaDigito($inscricao_estadual)
    {

        $peso = 9;
        $posicao = 8;
        $soma = 0;
        for ($i = 0; $i < $posicao; $i++) {
            $soma += $inscricao_estadual[$i] * $peso;
            $peso--;
        }
        $produto = $soma * 10;
        $dig = $produto - (((int)($produto / 11)) * 11);
        //se a diferença for 10 ou 11 então o digito é 0

        if ($dig >= 10) {
            $dig = 0;
        }
        return $dig == $inscricao_estadual[$posicao];
    }
}

class Amapa
{

    /**
     * Verifica se a inscrição estadual é válida para o Amapa (AP)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_AP.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 9) {
            $inscricao_estadual = str_pad($inscricao_estadual, 9, 0, STR_PAD_LEFT);
        }
        // se não tiver 9 digitos não é valido
        if (strlen($inscricao_estadual) != 9) {
            $valid = false;
        }
        if ($valid && substr($inscricao_estadual, 0, 2) != '03') {
            $valid = false;
        }
        if ($valid && !self::calculaDigito($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * Valida o dígito da inscrição estadual
     *
     * Pesos: 9 8 7 6 5 4 3 2 para calculo do dígito
     * @param $inscricao_estadual string inscricao estadual
     * @return bool true caso o digito seja verificado, false caso contrário.
     */
    private static function calculaDigito($inscricao_estadual)
    {

        $length = strlen($inscricao_estadual);
        $posicao = $length - 1;
        $peso = $length;
        $corpo = substr($inscricao_estadual, 0, $posicao);


        //verificando informações de "p" e "d"

        // utilizado no calculo do modulo
        $p = 0;
        // utilizado como verificador alternativo
        $d = 0;
        if ('03000001' <= $corpo && $corpo <= '03017000') {
            $p = 5;
            $d = 0;
        } elseif ('03017001' <= $corpo && $corpo <= '03019022') {
            $p = 9;
            $d = 1;
        }

        $soma = $p;
        foreach (str_split($corpo) as $item) {
            $soma += $item * $peso;
            $peso--;
        }
        $dig = 11 - ($soma % 11);
        //se a diferença for 10 o digito é 0, se for 11 o digito será $d

        if ($dig == 10) {
            $dig = 0;
        }
        if ($dig == 11) {
            $dig = $d;
        }

        return $dig == $inscricao_estadual[$posicao];
    }
}

class Amazonas
{

    /**
     * Verifica se a inscrição estadual é válida para o Amazonas (AM)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_AM.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 9) {
            $inscricao_estadual = str_pad($inscricao_estadual, 9, 0, STR_PAD_LEFT);
        }
        // se não tiver 9 digitos não é valido
        if (strlen($inscricao_estadual) != 9) {
            $valid = false;
        }
        if ($valid && !self::calculaDigito($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * Valida o dígito da inscrição estadual
     *
     * Pesos: 9 8 7 6 5 4 3 2 para calculo do dígito
     * @param $inscricao_estadual string inscricao estadual
     * @return bool true caso o digito seja verificado, false caso contrário.
     */
    private static function calculaDigito($inscricao_estadual)
    {

        $soma = 0;
        $length = strlen($inscricao_estadual);
        $posicao = $length - 1;
        $peso = $length;
        $corpo = substr($inscricao_estadual, 0, $posicao);
        foreach (str_split($corpo) as $item) {
            $soma += $item * $peso;
            $peso--;
        }
        if ($soma < 11) {
            $dig = 11 - $soma;
        } else {
            $resto = $soma % 11;
            $dig = 11 - $resto;

            if ($dig >= 10) {
                $dig = 0;
            }
        }
        return $dig == $inscricao_estadual[$posicao];
    }
}

class Bahia
{

    /**
     * Verifica se a inscrição estadual é válida para a Bahia (BA)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_BA.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {

        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 8) {
            $inscricao_estadual = str_pad($inscricao_estadual, 8, 0, STR_PAD_LEFT);
        }
        // se não tiver 8 ou 9 digitos não é valido
        $length = strlen($inscricao_estadual);

        if ($length !== 9 && $length !== 8) {
            $valid = false;
        }
        if ($valid && !self::calculaDigitos($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * Valida o dígito da inscrição estadual
     *
     * Pesos: de "x" a "2", onde x é o tamanho do corpo +1
     * @param $inscricao_estadual string inscricao estadual
     * @return bool true caso os digitos sejam verificados, false caso contrário.
     */
    private static function calculaDigitos($inscricao_estadual)
    {

        $length = strlen($inscricao_estadual);
        $corpo = substr($inscricao_estadual, 0, $length - 2);
        $modulo = self::getModulo($inscricao_estadual);
        // Calculando o segundo dígito
        $_2dig = self::calculaDigito($corpo, $modulo);
        //adicionando o segundo dígito no corpo para calcular o primeiro dígito
        $_1dig = self::calculaDigito($corpo . $_2dig, $modulo);

        $pos2dig = strlen($inscricao_estadual) - 1;

        $pos1dig = strlen($inscricao_estadual) - 2;

        return $inscricao_estadual[$pos1dig] == $_1dig && $inscricao_estadual[$pos2dig] == $_2dig;
    }

    /**
     * Identifica qual módulo deve ser usado para o calculo dos dígitos verificadores.
     *
     * @param $inscricao_estadual string inscrição estadual a ser verificada
     * @return integer módulo, 10 caso o primeiro dígito da inscrição estadual seja:0,1,2,3,4,5 ou 8. 11 caso: 6,7 ou 9
     */
    private static function getModulo($inscricao_estadual)
    {
        $comprimento = strlen($inscricao_estadual);
        // se for de 8 digitos devo analisar o primeiro digito
        $posicao = 0;
        // caso contrário analiso o segundo digito
        if ($comprimento == 9) {
            $posicao = 1;
        }
        $char = substr($inscricao_estadual, $posicao, 1);

        //para verificar qual módulo deve ser usado, com base na documentação.
        if (in_array($char, [0, 1, 2, 3, 4, 5, 8], false)) {
            return 10;
        }
        return 11;
    }

    /**
     * Informa o digito para o corpo passado
     * @param $corpo
     * @param $modulo
     * @return int dígito
     */
    private static function calculaDigito($corpo, $modulo)
    {
        $peso = strlen($corpo) + 1;

        $soma = 0;
        foreach (str_split($corpo) as $digito) {
            $soma += $digito * $peso;
            $peso--;
        }

        $resto = $soma % $modulo;

        $dig = $modulo - $resto;
        if ($dig >= 10) {
            $dig = 0;
        }

        return $dig;
    }
}

class Ceara
{

    /**
     * Verifica se a inscrição estadual é válida para o Ceará (CE)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_CE.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 9) {
            $inscricao_estadual = str_pad($inscricao_estadual, 9, 0, STR_PAD_LEFT);
        }
        // se não tiver 9 digitos não é valido
        if (strlen($inscricao_estadual) != 9) {
            $valid = false;
        }
        if ($valid && !self::calculaDigito($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * Valida o dígito da inscrição estadual
     *
     * Pesos: 9 8 7 6 5 4 3 2 para calculo do dígito
     * @param $inscricao_estadual string inscricao estadual
     * @return bool true caso o digito seja verificado, false caso contrário.
     */
    protected static function calculaDigito($inscricao_estadual)
    {
        $soma = 0;
        $length = strlen($inscricao_estadual);
        $posicao = $length - 1;
        $peso = $length;
        $corpo = substr($inscricao_estadual, 0, $posicao);
        foreach (str_split($corpo) as $item) {
            $soma += $item * $peso;
            $peso--;
        }

        $resto = $soma % 11;

        $dig = 11 - $resto;
        if ($dig >= 10) {
            $dig = 0;
        }
        return $dig == $inscricao_estadual[$posicao];
    }
}

class DistritoFederal
{

    /**
     * Verifica se a inscrição estadual é válida para o Distrito Federal (DF)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_DF.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 13) {
            $inscricao_estadual = str_pad($inscricao_estadual, 13, 0, STR_PAD_LEFT);
        }
        // se não tiver 13 digitos não é valido
        if (strlen($inscricao_estadual) != 13) {
            $valid = false;
        }

        if ($valid && !self::calculaDigitos($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * Valida o dígito da inscrição estadual
     *
     * Pesos: de "x" a "2", onde x é o tamanho do corpo +1
     * @param $inscricao_estadual string inscricao estadual
     * @return bool true caso os digitos sejam verificados, false caso contrário.
     */
    private static function calculaDigitos($inscricao_estadual)
    {

        $length = strlen($inscricao_estadual);
        $corpo = substr($inscricao_estadual, 0, $length - 2);

        // Calculando o primeiro dígito
        $_1dig = self::calculaDigito($corpo);
        //adicionando o primeiro dígito no corpo para calcular o segundo dígito
        $_2dig = self::calculaDigito($corpo . $_1dig);

        $pos2dig = strlen($inscricao_estadual) - 1;

        $pos1dig = strlen($inscricao_estadual) - 2;

        return $inscricao_estadual[$pos1dig] == $_1dig && $inscricao_estadual[$pos2dig] == $_2dig;
    }

    /**
     * Informa o digito para o corpo passado
     * @param $corpo
     * @return int dígito
     */
    private static function calculaDigito($corpo)
    {
        // vai começar em 4 quando o digito a ser verificado for o primeiro, e em 5 quando for o segundo
        $peso = strlen($corpo) - 7;

        $soma = 0;
        foreach (str_split($corpo) as $digito) {
            $soma += $digito * $peso;
            $peso--;
            if ($peso == 1) {
                $peso = 9;
            }
        }

        $modulo = 11;

        $resto = $soma % $modulo;

        $dig = $modulo - $resto;
        if ($dig >= 10) {
            $dig = 0;
        }

        return $dig;
    }
}

class EspiritoSanto
{

    /**
     * Verifica se a inscrição estadual é válida para o Espirito Santo (ES)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_ES.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 9) {
            $inscricao_estadual = str_pad($inscricao_estadual, 9, 0, STR_PAD_LEFT);
        }
        // se não tiver 9 digitos não é valido
        if (strlen($inscricao_estadual) != 9) {
            $valid = false;
        }
        if ($valid && !self::calculaDigito($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    protected static function calculaDigito($inscricao_estadual)
    {
        $soma = 0;
        $length = strlen($inscricao_estadual);
        $posicao = $length - 1;
        $peso = $length;
        $corpo = substr($inscricao_estadual, 0, $posicao);
        foreach (str_split($corpo) as $item) {
            $soma += $item * $peso;
            $peso--;
        }

        $resto = $soma % 11;

        $dig = 11 - $resto;
        if ($dig >= 10) {
            $dig = 0;
        }
        return $dig == $inscricao_estadual[$posicao];
    }
}

class Goias
{

    /**
     * Verifica se a inscrição estadual é válida para Goiás (GO)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_GO.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 9) {
            $inscricao_estadual = str_pad($inscricao_estadual, 9, 0, STR_PAD_LEFT);
        }
        // se não tiver 9 digitos não é valido
        if (strlen($inscricao_estadual) != 9) {
            $valid = false;
        }
        $inicio = substr($inscricao_estadual, 0, 2);
        if ($valid && !in_array($inicio, ['10', '11', '15'])) {
            $valid = false;
        }
        if ($valid && !self::calculaDigito($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * Valida o dígito da inscrição estadual
     *
     * Pesos: 9 8 7 6 5 4 3 2 para calculo do dígito
     * @param $inscricao_estadual string inscricao estadual
     * @return bool true caso o digito seja verificado, false caso contrário.
     */
    protected static function calculaDigito($inscricao_estadual)
    {
        $peso = 9;
        $posicao = 8;
        $soma = 0;
        $length = strlen($inscricao_estadual);
        $corpo = substr($inscricao_estadual, 0, $length - 1);
        foreach (str_split($corpo) as $item) {
            $soma += $item * $peso;
            $peso--;
        }

        $resto = $soma % 11;

        $dig = 11 - $resto;

        if ($dig >= 10) {
            if ($dig == 11 && '10103105' <= $corpo && $corpo <= '10119997') {
                $dig = 1;
            } else {
                $dig = 0;
            }

        }
        return $dig == $inscricao_estadual[$posicao];
    }
}

class Maranhao
{

    /**
     * Verifica se a inscrição estadual é válida para o Maranhao (MA)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_MA.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 9) {
            $inscricao_estadual = str_pad($inscricao_estadual, 9, 0, STR_PAD_LEFT);
        }
        // se não tiver 9 digitos não é valido
        if (strlen($inscricao_estadual) != 9) {
            $valid = false;
        }
        if ($valid && substr($inscricao_estadual, 0, 2) != '12') {
            $valid = false;
        }
        if ($valid && !self::calculaDigito($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    protected static function calculaDigito($inscricao_estadual)
    {
        $soma = 0;
        $length = strlen($inscricao_estadual);
        $posicao = $length - 1;
        $peso = $length;
        $corpo = substr($inscricao_estadual, 0, $posicao);
        foreach (str_split($corpo) as $item) {
            $soma += $item * $peso;
            $peso--;
        }

        $resto = $soma % 11;

        $dig = 11 - $resto;
        if ($dig >= 10) {
            $dig = 0;
        }
        return $dig == $inscricao_estadual[$posicao];
    }
}

class MatoGrosso
{

    /**
     * Verifica se a inscrição estadual é válida para o Mato Grosso (MT)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_MT.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 11) {
            $inscricao_estadual = str_pad($inscricao_estadual, 11, 0, STR_PAD_LEFT);
        }
        // se não tiver 11 digitos não é valido
        if (strlen($inscricao_estadual) > 11) {
            $valid = false;
        }
        if ($valid && !self::calculaDigito($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * Valida o dígito da inscrição estadual
     *
     * Pesos: 3 2 9 8 7 6 5 4 3 2 para calculo do dígito
     * @param $inscricao_estadual string inscricao estadual
     * @return bool true caso o digito seja verificado, false caso contrário.
     */
    protected static function calculaDigito($inscricao_estadual)
    {
        $peso = 3;
        $posicao = 10;
        $soma = 0;
        $length = strlen($inscricao_estadual);
        $corpo = substr($inscricao_estadual, 0, $length - 1);
        foreach (str_split($corpo) as $item) {
            $soma += $item * $peso;
            $peso--;
            if ($peso == 1) {
                $peso = 9;
            }
        }

        $resto = $soma % 11;

        $dig = 11 - $resto;
        if ($dig >= 10) {
            $dig = 0;
        }
        return $dig == $inscricao_estadual[$posicao];
    }
}

class MatoGrossoDoSul
{

    /**
     * Verifica se a inscrição estadual é válida para o Mato Grosso do Sul (MS)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_MS.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 9) {
            $inscricao_estadual = str_pad($inscricao_estadual, 9, 0, STR_PAD_LEFT);
        }
        // se não tiver 9 digitos não é valido
        if (strlen($inscricao_estadual) != 9) {
            $valid = false;
        }
        if ($valid && substr($inscricao_estadual, 0, 2) != '28') {
            $valid = false;
        }
        if ($valid && !self::calculaDigito($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    protected static function calculaDigito($inscricao_estadual)
    {
        $soma = 0;
        $length = strlen($inscricao_estadual);
        $posicao = $length - 1;
        $peso = $length;
        $corpo = substr($inscricao_estadual, 0, $posicao);
        foreach (str_split($corpo) as $item) {
            $soma += $item * $peso;
            $peso--;
        }

        $resto = $soma % 11;

        $dig = 11 - $resto;
        if ($dig >= 10) {
            $dig = 0;
        }
        return $dig == $inscricao_estadual[$posicao];
    }
}

class MinasGerais
{

    /**
     * Verifica se a inscrição estadual é válida para Minas Gerais (MG)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_MG.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 13) {
            $inscricao_estadual = str_pad($inscricao_estadual, 13, 0, STR_PAD_LEFT);
        }
        // se não tiver 13 digitos não é valido
        if (strlen($inscricao_estadual) != 13) {
            $valid = false;
        }
        if ($valid && !self::calculaDigito($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * Valida o dígito da inscrição estadual
     *
     * FORMATO GERAL: AAABBBBBBCCDD
     *
     * Onde: A= Código do Município
     * B= Número da inscrição
     * C= Número de ordem do estabelecimento
     * D= Dígitos de controle
     * @param $inscricao_estadual string inscricao estadual
     * @return bool true caso o digito seja verificado, false caso contrário.
     */
    protected static function calculaDigito($inscricao_estadual)
    {
        $length = strlen($inscricao_estadual);
        $pos_1dig = $length - 2;
        $pos_2dig = $length - 1;

        $corpo = substr($inscricao_estadual, 0, 11);

        $_1dig = self::calculaPrimeiroDigito($corpo);

        $_2dig = self::calculaSegundoDigito($corpo . $_1dig);

        return $_1dig == $inscricao_estadual[$pos_1dig] && $_2dig == $inscricao_estadual[$pos_2dig];
    }

    /**
     * Cálculo do primeiro dígito sobre o corpo de inscricao estadual a ser calculado
     *
     * @param $corpo string inscricao estadual sem os dois dígitos verificadores
     * @return int dígito verificador
     */
    private static function calculaPrimeiroDigito($corpo)
    {
        /**
         * Igualar as casas para o cálculo, o que consiste em inserir o algarismo zero "0" imediatamente após o número de código do município, desprezando-se os dígitos de controle.
         * Exemplo: Número da inscrição: 062 307 904 00 ? ?
         * Número a ser trabalhado: 062 "0" 307904 00 -- --
         */
        $corpo = substr_replace($corpo, '0', 3, 0);
        $concatenacao = "";
        foreach (str_split($corpo) as $i => $item) {
            //se index impar então peso é 1 senão é 2
            $peso = ((($i + 3) % 2) == 0) ? 2 : 1;
            $concatenacao .= ($item * $peso);
        }
        $soma = 0;

        // Soma-se os algarismos (não os produtos) do resultado obtido
        foreach (str_split($concatenacao) as $algarismo) {
            $soma += $algarismo;
        }
        // Subtrai-se o resultado da soma do item anterior, da primeira dezena exata imediatamente superior:
        $strSoma = $soma . '';
        $length = strlen($strSoma);
        $last_char = substr($strSoma, $length - 1, 1);

        return ($last_char == 0) ? 0 : (10 - $last_char);
    }

    /**
     * Cálculo do segundo dígito verificador.
     *
     * @param $corpo string corpo da inscricao estadual acrescido do primeiro dígito verificador correto
     * @return int segundo dígito verificador
     */
    private static function calculaSegundoDigito($corpo)
    {
        $peso = 3;
        $soma = 0;
        foreach (str_split($corpo) as $item) {
            $soma += $item * $peso;
            $peso--;
            if ($peso == 1) {
                $peso = 11;
            }
        }

        $resto = $soma % 11;

        $dig = 11 - $resto;

        if ($dig >= 10) {
            $dig = 0;
        }
        return $dig;
    }
}

class Para
{

    /**
     * Verifica se a inscrição estadual é válida para o Para (PA)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_PA.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 9) {
            $inscricao_estadual = str_pad($inscricao_estadual, 9, 0, STR_PAD_LEFT);
        }
        // se não tiver 9 digitos não é valido
        if (strlen($inscricao_estadual) != 9) {
            $valid = false;
        }
        if ($valid && substr($inscricao_estadual, 0, 2) != '15') {
            $valid = false;
        }
        if ($valid && !self::calculaDigito($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    protected static function calculaDigito($inscricao_estadual)
    {
        $soma = 0;
        $length = strlen($inscricao_estadual);
        $posicao = $length - 1;
        $peso = $length;
        $corpo = substr($inscricao_estadual, 0, $posicao);
        foreach (str_split($corpo) as $item) {
            $soma += $item * $peso;
            $peso--;
        }

        $resto = $soma % 11;

        $dig = 11 - $resto;
        if ($dig >= 10) {
            $dig = 0;
        }
        return $dig == $inscricao_estadual[$posicao];
    }
}

class Paraiba
{

    /**
     * Verifica se a inscrição estadual é válida para o Estado da Paraiba (PB)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_PB.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 9) {
            $inscricao_estadual = str_pad($inscricao_estadual, 9, 0, STR_PAD_LEFT);
        }
        // se não tiver 9 digitos não é valido
        if (strlen($inscricao_estadual) != 9) {
            $valid = false;
        }
        if ($valid && !self::calculaDigito($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * Valida o dígito da inscrição estadual
     *
     * Pesos: 9 8 7 6 5 4 3 2 para calculo do dígito
     * @param $inscricao_estadual string inscricao estadual
     * @return bool true caso o digito seja verificado, false caso contrário.
     */
    protected static function calculaDigito($inscricao_estadual)
    {
        $soma = 0;
        $length = strlen($inscricao_estadual);
        $posicao = $length - 1;
        $peso = $length;
        $corpo = substr($inscricao_estadual, 0, $posicao);
        foreach (str_split($corpo) as $item) {
            $soma += $item * $peso;
            $peso--;
        }

        $resto = $soma % 11;

        $dig = 11 - $resto;
        if ($dig >= 10) {
            $dig = 0;
        }
        return $dig == $inscricao_estadual[$posicao];
    }

}

class Parana
{

    /**
     * Verifica se a inscrição estadual é válida para o Paraná (PR)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_PR.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 10) {
            $inscricao_estadual = str_pad($inscricao_estadual, 10, 0, STR_PAD_LEFT);
        }
        if (strlen($inscricao_estadual) !== 10) {
            $valid = false;
        }
        if ($valid && !self::calculaDigitos($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * Valida o dígito da inscrição estadual
     *
     * Pesos: de 2 a 7 da direita para esquerda
     * @param $inscricao_estadual string inscricao estadual
     * @return bool true caso os digitos sejam verificados, false caso contrário.
     */
    private static function calculaDigitos($inscricao_estadual)
    {

        $length = strlen($inscricao_estadual);
        $corpo = substr($inscricao_estadual, 0, $length - 2);

        // Calculando o primeiro dígito
        $_1dig = self::calculaDigito($corpo);
        //adicionando o primeiro dígito no corpo para calcular o segundo dígito
        $_2dig = self::calculaDigito($corpo . $_1dig);

        $pos2dig = strlen($inscricao_estadual) - 1;

        $pos1dig = strlen($inscricao_estadual) - 2;

        return $inscricao_estadual[$pos1dig] == $_1dig && $inscricao_estadual[$pos2dig] == $_2dig;
    }

    /**
     * Informa o digito para o corpo passado
     * @param $corpo
     * @return int dígito
     */
    private static function calculaDigito($corpo)
    {
        $peso = strlen($corpo) - 5;

        $soma = 0;
        foreach (str_split($corpo) as $digito) {
            $soma += $digito * $peso;
            $peso--;
            if ($peso == 1) {
                $peso = 7;
            }
        }

        $modulo = 11;

        $resto = $soma % $modulo;

        $dig = $modulo - $resto;
        if ($dig >= 10) {
            $dig = 0;
        }

        return $dig;
    }
}

class Pernambuco
{

    /**
     * Verifica se a inscrição estadual é válida para o estado do Pernambuco (PE)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_PE.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 9) {
            $inscricao_estadual = str_pad($inscricao_estadual, 9, 0, STR_PAD_LEFT);
        }
        if (strlen($inscricao_estadual) !== 9) {
            $valid = false;
        }
        if ($valid && !self::calculaDigitos($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * Valida o dígito da inscrição estadual
     *
     * Pesos: de 2 a 7 da direita para esquerda
     * @param $inscricao_estadual string inscricao estadual
     * @return bool true caso os digitos sejam verificados, false caso contrário.
     */
    protected static function calculaDigitos($inscricao_estadual)
    {

        $length = strlen($inscricao_estadual);
        $corpo = substr($inscricao_estadual, 0, $length - 2);

        // Calculando o primeiro dígito
        $_1dig = self::calculaDigito($corpo);
        //adicionando o primeiro dígito no corpo para calcular o segundo dígito
        $_2dig = self::calculaDigito($corpo . $_1dig);

        $pos2dig = strlen($inscricao_estadual) - 1;

        $pos1dig = strlen($inscricao_estadual) - 2;

        return $inscricao_estadual[$pos1dig] == $_1dig && $inscricao_estadual[$pos2dig] == $_2dig;
    }

    /**
     * Informa o digito para o corpo passado
     * @param $corpo
     * @return int dígito
     */
    private static function calculaDigito($corpo)
    {
        $peso = strlen($corpo) + 1;

        $soma = 0;
        foreach (str_split($corpo) as $digito) {
            $soma += $digito * $peso;
            $peso--;
        }

        $modulo = 11;

        $resto = $soma % $modulo;

        $dig = $modulo - $resto;
        if ($dig >= 10) {
            $dig = 0;
        }
        return $dig;
    }
}

class Piaui
{

    /**
     * Verifica se a inscrição estadual é válida para o Estado do Piaui (PI)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_PI.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 9) {
            $inscricao_estadual = str_pad($inscricao_estadual, 9, 0, STR_PAD_LEFT);
        }
        // se não tiver 9 digitos não é valido
        if (strlen($inscricao_estadual) != 9) {
            $valid = false;
        }
        if ($valid && !self::calculaDigito($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * Valida o dígito da inscrição estadual
     *
     * Pesos: 9 8 7 6 5 4 3 2 para calculo do dígito
     * @param $inscricao_estadual string inscricao estadual
     * @return bool true caso o digito seja verificado, false caso contrário.
     */
    protected static function calculaDigito($inscricao_estadual)
    {
        $soma = 0;
        $length = strlen($inscricao_estadual);
        $posicao = $length - 1;
        $peso = $length;
        $corpo = substr($inscricao_estadual, 0, $posicao);
        foreach (str_split($corpo) as $item) {
            $soma += $item * $peso;
            $peso--;
        }

        $resto = $soma % 11;

        $dig = 11 - $resto;
        if ($dig >= 10) {
            $dig = 0;
        }
        return $dig == $inscricao_estadual[$posicao];
    }

}

class RioDeJaneiro
{

    /**
     * Verifica se a inscrição estadual é válida para o Rio de Janeiro (RJ)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_RJ.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 8) {
            $inscricao_estadual = str_pad($inscricao_estadual, 8, 0, STR_PAD_LEFT);
        }
        // se não tiver 8 digitos não é valido
        if (strlen($inscricao_estadual) != 8) {
            $valid = false;
        }
        if ($valid && !self::calculaDigito($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * Valida o dígito da inscrição estadual
     *
     * Pesos: 2 7 6 5 4 3 2 para calculo do dígito
     * @param $inscricao_estadual string inscricao estadual
     * @return bool true caso o digito seja verificado, false caso contrário.
     */
    protected static function calculaDigito($inscricao_estadual)
    {
        $peso = 2;
        $soma = 0;
        $length = strlen($inscricao_estadual);
        $posicao = $length - 1;
        $corpo = substr($inscricao_estadual, 0, $length - 1);
        foreach (str_split($corpo) as $item) {
            $soma += $item * $peso;
            $peso--;
            if ($peso == 1) {
                $peso = 7;
            }
        }

        $resto = $soma % 11;

        $dig = 11 - $resto;
        if ($dig >= 10) {
            $dig = 0;
        }
        return $dig == $inscricao_estadual[$posicao];
    }
}

class RioGrandeDoNorte
{

    /**
     * Verifica se a inscrição estadual é válida para o Rio Grande do Norte (RN)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_RN.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 9) {
            $inscricao_estadual = str_pad($inscricao_estadual, 9, 0, STR_PAD_LEFT);
        }
        // se não tiver 9 ou 10 digitos não é valido
        $length = strlen($inscricao_estadual);
        if ($length != 9 && $length != 10) {
            $valid = false;
        }
        if ($valid && substr($inscricao_estadual, 0, 2) != '20') {
            $valid = false;
        }
        if ($valid && !self::calculaDigito($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * Valida o dígito da inscrição estadual
     *
     * Pesos:  9 8 7 6 5 4 3 2 para calculo do dígito verificador para 9 digitos
     * Pesos: 10 9 8 7 6 5 4 3 2 para calculo do dígito verificador para 10 digitos
     * @param $inscricao_estadual string inscricao estadual
     * @return bool true caso o digito seja verificado, false caso contrário.
     */
    protected static function calculaDigito($inscricao_estadual)
    {
        $soma = 0;
        $length = strlen($inscricao_estadual);
        $posicao = $length - 1;
        $peso = $length;
        $corpo = substr($inscricao_estadual, 0, $posicao);
        foreach (str_split($corpo) as $item) {
            $soma += $item * $peso;
            $peso--;
        }

        $dig = $soma * 10 % 11;

        if ($dig == 10) {
            $dig = 0;
        }
        return $dig == $inscricao_estadual[$posicao];
    }
}

class RioGrandeDoSul
{

    /**
     * Verifica se a inscrição estadual é válida para o Rio Grande do Sul (RS)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_RS.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 10) {
            $inscricao_estadual = str_pad($inscricao_estadual, 10, 0, STR_PAD_LEFT);
        }
        // se não tiver 10 digitos não é valido
        if (strlen($inscricao_estadual) != 10) {
            $valid = false;
        }
        if ($valid && !self::calculaDigito($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * Valida o dígito da inscrição estadual
     *
     * Pesos: 2 9 8 7 6 5 4 3 2 para calculo do dígito
     * @param $inscricao_estadual string inscricao estadual
     * @return bool true caso o digito seja verificado, false caso contrário.
     */
    protected static function calculaDigito($inscricao_estadual)
    {
        $soma = 0;
        $length = strlen($inscricao_estadual);
        $posicao = $length - 1;
        $peso = 2;
        $corpo = substr($inscricao_estadual, 0, $posicao);
        foreach (str_split($corpo) as $item) {
            $soma += $item * $peso;
            $peso--;
            if ($peso == 1) {
                $peso = 9;
            }
        }

        $resto = $soma % 11;

        $dig = 11 - $resto;

        if ($dig >= 10) {
            $dig = 0;
        }
        return $dig == $inscricao_estadual[$posicao];
    }
}

class Rondonia
{

    /**
     * Verifica se a inscrição estadual é válida para Rondônia (RO)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_RO.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 14) {
            $inscricao_estadual = str_pad($inscricao_estadual, 14, 0, STR_PAD_LEFT);
        }
        // se não tiver 14 digitos não é valido
        if (strlen($inscricao_estadual) != 14) {
            $valid = false;
        }
        if ($valid && !self::calculaDigito($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * Valida o dígito da inscrição estadual
     *
     * Pesos:  6 5 4 3 2 9 8 7 6 5 4 3 2 para calculo do dígito verificador
     * @param $inscricao_estadual string inscricao estadual
     * @return bool true caso o digito seja verificado, false caso contrário.
     */
    protected static function calculaDigito($inscricao_estadual)
    {
        $soma = 0;
        $length = strlen($inscricao_estadual);
        $posicao = $length - 1;
        $peso = 6;
        $corpo = substr($inscricao_estadual, 0, $posicao);
        foreach (str_split($corpo) as $item) {
            $soma += $item * $peso;
            $peso--;
            if ($peso == 1) {
                $peso = 9;
            }
        }

        $resto = $soma % 11;

        $dig = 11 - $resto;

        if ($dig >= 10) {
            $dig -= 10;
        }
        return $dig == $inscricao_estadual[$posicao];
    }
}

class Roraima
{

    /**
     * Verifica se a inscrição estadual é válida para Roraima (RR)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_RR.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 9) {
            $inscricao_estadual = str_pad($inscricao_estadual, 9, 0, STR_PAD_LEFT);
        }
        // se não tiver 9 digitos não é valido
        if (strlen($inscricao_estadual) != 9) {
            $valid = false;
        }
        if ($valid && substr($inscricao_estadual, 0, 2) != '24') {
            $valid = false;
        }
        if ($valid && !self::calculaDigito($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * Valida o dígito da inscrição estadual
     *
     * Pesos: 1 2 3 4 5 6 7 8 para calculo do dígito
     * @param $inscricao_estadual string inscricao estadual
     * @return bool true caso o digito seja verificado, false caso contrário.
     */
    protected static function calculaDigito($inscricao_estadual)
    {
        $soma = 0;
        $length = strlen($inscricao_estadual);
        $posicao = $length - 1;
        $peso = 1;
        $corpo = substr($inscricao_estadual, 0, $posicao);
        foreach (str_split($corpo) as $item) {
            $soma += $item * $peso;
            $peso++;
        }

        $dig = $soma % 9;

        return $dig == $inscricao_estadual[$posicao];
    }
}

class SantaCatarina
{

    /**
     * Verifica se a inscrição estadual é válida para o Estado da Santa Catarina (SC)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_SC.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 9) {
            $inscricao_estadual = str_pad($inscricao_estadual, 9, 0, STR_PAD_LEFT);
        }
        // se não tiver 9 digitos não é valido
        if (strlen($inscricao_estadual) != 9) {
            $valid = false;
        }
        if ($valid && !self::calculaDigito($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * Valida o dígito da inscrição estadual
     *
     * Pesos: 9 8 7 6 5 4 3 2 para calculo do dígito
     * @param $inscricao_estadual string inscricao estadual
     * @return bool true caso o digito seja verificado, false caso contrário.
     */
    protected static function calculaDigito($inscricao_estadual)
    {
        $soma = 0;
        $length = strlen($inscricao_estadual);
        $posicao = $length - 1;
        $peso = $length;
        $corpo = substr($inscricao_estadual, 0, $posicao);
        foreach (str_split($corpo) as $item) {
            $soma += $item * $peso;
            $peso--;
        }

        $resto = $soma % 11;

        $dig = 11 - $resto;
        if ($dig >= 10) {
            $dig = 0;
        }
        return $dig == $inscricao_estadual[$posicao];
    }

}

class SaoPaulo
{

    /**
     * Verifica se a inscrição estadual é válida para o estado de São Paulo (SP)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_SP.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 12) {
            $inscricao_estadual = str_pad($inscricao_estadual, 12, 0, STR_PAD_LEFT);
        }
        // se não tiver 12 digitos não é valido
        if (strlen($inscricao_estadual) != 12) {
            $valid = false;
        }
        if ($valid && !self::calculaDigito($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * Valida o dígito da inscrição estadual
     *
     * FORMATO GERAL: NNNNNNNNDNND
     *
     * Onde: 9º E O 12º são os dígitos verificadores
     * @param $inscricao_estadual string inscricao estadual
     * @return bool true caso o digito seja verificado, false caso contrário.
     */
    protected static function calculaDigito($inscricao_estadual)
    {
        $length = strlen($inscricao_estadual);
        $pos_1dig = $length - 4;
        $pos_2dig = $length - 1;

        $_1dig = self::calculaPrimeiroDigito($inscricao_estadual);

        $_2dig = self::calculaSegundoDigito($inscricao_estadual);

        return $_1dig == $inscricao_estadual[$pos_1dig] && $_2dig == $inscricao_estadual[$pos_2dig];
    }

    /**
     * Cálculo do primeiro dígito verificador 9º dígito
     *
     * @param $inscricao_estadual string inscrição estadual
     * @return int dígito verificador
     */
    private static function calculaPrimeiroDigito($inscricao_estadual)
    {
        $corpo = substr($inscricao_estadual, 0, 8);
        $pesos = [1, 3, 4, 5, 6, 7, 8, 10];
        $soma = 0;
        foreach (str_split($corpo) as $i => $item) {
            $soma += ($item * $pesos[$i]);
        }

        $dig = $soma % 11;

        $strDig = $dig . '';
        $length = strlen($strDig);

        return substr($dig, $length - 1, 1);
    }

    /**
     * Cálculo do segundo dígito verificador.
     *
     * @param $inscricao_estadual string inscrição estadual
     * @return int segundo dígito verificador
     */
    private static function calculaSegundoDigito($inscricao_estadual)
    {
        $corpo = substr($inscricao_estadual, 0, 11);
        $peso = 3;
        $soma = 0;
        foreach (str_split($corpo) as $item) {
            $soma += $item * $peso;
            $peso--;
            if ($peso == 1) {
                $peso = 10;
            }
        }

        $dig = $soma % 11;

        $strDig = $dig . '';
        $length = strlen($strDig);

        return substr($dig, $length - 1, 1);
    }
}

class Sergipe
{

    /**
     * Verifica se a inscrição estadual é válida para o Estado de Sergipe (SE)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_SE.html
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    public static function check($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 9) {
            $inscricao_estadual = str_pad($inscricao_estadual, 9, 0, STR_PAD_LEFT);
        }
        // se não tiver 9 digitos não é valido
        if (strlen($inscricao_estadual) != 9) {
            $valid = false;
        }
        if ($valid && !self::calculaDigito($inscricao_estadual)) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * Valida o dígito da inscrição estadual
     *
     * Pesos: 9 8 7 6 5 4 3 2 para calculo do dígito
     * @param $inscricao_estadual string inscricao estadual
     * @return bool true caso o digito seja verificado, false caso contrário.
     */
    protected static function calculaDigito($inscricao_estadual)
    {
        $soma = 0;
        $length = strlen($inscricao_estadual);
        $posicao = $length - 1;
        $peso = $length;
        $corpo = substr($inscricao_estadual, 0, $posicao);
        foreach (str_split($corpo) as $item) {
            $soma += $item * $peso;
            $peso--;
        }

        $resto = $soma % 11;

        $dig = 11 - $resto;
        if ($dig >= 10) {
            $dig = 0;
        }
        return $dig == $inscricao_estadual[$posicao];
    }

}

class Tocantins
{

    /**
     * Valida se a inscrição estadual para o Estado do Tocantins é válida de acordo com as regras antigas e novas.
     * Bastando atender pelo menos uma das regras para ser considerado válida.
     * @param string $inscricao_estadual
     * @return bool
     */
    public static function check($inscricao_estadual)
    {
        return static::checkAntiga($inscricao_estadual) || static::checkNova($inscricao_estadual);
    }

    /**
     * Verifica se a inscrição estadual é válida para o Tocantins (TO)
     * seguindo a regra: http://www.sintegra.gov.br/Cad_Estados/cad_TO.html (Válida até dezembro de 2.003)
     *
     * @param $inscricao_estadual string Inscrição Estadual que deseja validar.
     * @return bool true caso a inscrição estadual seja válida para esse estado, false caso contrário.
     */
    protected static function checkAntiga($inscricao_estadual)
    {
        $valid = true;
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 11) {
            $inscricao_estadual = str_pad($inscricao_estadual, 11, 0, STR_PAD_LEFT);
        }
        // se não tiver 11 digitos não é valido
        if (strlen($inscricao_estadual) != 11) {
            $valid = false;
        }
        if ($valid) {
            $categoria = substr($inscricao_estadual, 2, 2);
            if (!in_array($categoria, ['01', '02', '03', '99'])) {
                $valid = false;
            }
            // removo a categoria do calculo de validação
            $corpo = substr_replace($inscricao_estadual, '', 2, 2);
        }

        if ($valid && !self::calculaDigito($corpo)) {
            $valid = false;
        }
        return $valid;

    }

    /**
     * Verifica critérios de avaliação da nova inscrição estadual de Tocantins (Em vigor desde junho de 2.002)
     * seguindo regra: http://www2.sefaz.to.gov.br/Servicos/Sintegra/calinse.htm
     * @param $inscricao_estadual
     * @return bool true se a inscrição estadual for valida segundo a nova regra de validação, falso caso contrário.
     */
    protected static function checkNova($inscricao_estadual)
    {
        // pad para a quantidade dígitos
        if (strlen($inscricao_estadual) < 9) {
            $inscricao_estadual = str_pad($inscricao_estadual, 9, 0, STR_PAD_LEFT);
        }
        // se não tiver 9 digitos não é valido
        return strlen($inscricao_estadual) == 9 && static::calculaDigitoNova($inscricao_estadual);
    }

    /**
     * Valida o dígito da inscrição estadual de Tocantins (Em vigor desde junho de 2.002)
     * seguindo regra: http://www2.sefaz.to.gov.br/Servicos/Sintegra/calinse.htm
     *
     * Pesos: 9 8 7 6 5 4 3 2 para calculo do dígito
     * @param $inscricao_estadual string inscricao estadual
     * @return bool true caso o digito seja verificado, false caso contrário.
     */
    protected static function calculaDigitoNova($inscricao_estadual)
    {
        $peso = 9;
        $soma = 0;
        $length = strlen($inscricao_estadual);
        $posicao = $length - 1;
        $corpo = substr($inscricao_estadual, 0, $length - 1);
        foreach (str_split($corpo) as $item) {
            $soma += $item * $peso;
            $peso--;
        }

        $resto = $soma % 11;

        $dig = 11 - $resto;
        if ($resto < 2) {
            $dig = 0;
        }

        return $dig == $inscricao_estadual[$posicao];
    }

    protected static function calculaDigito($inscricao_estadual)
    {
        $soma = 0;
        $length = strlen($inscricao_estadual);
        $posicao = $length - 1;
        $peso = $length;
        $corpo = substr($inscricao_estadual, 0, $posicao);
        foreach (str_split($corpo) as $item) {
            $soma += $item * $peso;
            $peso--;
        }

        $resto = $soma % 11;

        $dig = 11 - $resto;
        if ($dig >= 10) {
            $dig = 0;
        }
        return $dig == $inscricao_estadual[$posicao];
    }
}