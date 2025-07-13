<?php

/**
 * Classe de utilidade para construir o body das requests XML para a Microvix.
 * Adiciona automaticamente a autenticação e cria os parâmetros para pesquisa.
 */
class MicrovixXMLUtil
{
    protected $credentials;
    public function __construct($credentials)
    {
        $this->credentials = $credentials;
    }

    /**
     * Gera o body de uma request XML para buscar dados armazenados na Microvix.
     * @param string $method Método que deve ser buscado.
     * @param int $portal Número do portal que será consultado.
     * @param array<string,string> $params Parâmetros para buscar, sendo a chave o id do parâmetros, e o valor, o valor da pesquisa.
     * @return string Retorna o XML.
     */
    public function generateBody($method,$portal = null, $params = [])
    {
        // Cria um elemento da Dom.
        $dom = new DOMDocument('1.0', 'UTF-8');

        // Cria a raiz do XML.
        $root = $dom->createElement("LinxMicrovix");
        $dom->appendChild($root);

        // Adiciona o node de autenticação e seus atributos.
        $auth = $dom->createElement("Authentication");
        $root->appendChild($auth);

        $auth->setAttribute('user', $this->credentials->microvix_usuario);
        $auth->setAttribute('password', $this->credentials->microvix_senha);

        // Prossegue para o body.
        $responseFormat = $dom->createElement("ResponseFormat", "xml");
        $root->appendChild($responseFormat);

        // Insere o id do portal caso passado como argumento.
        if($portal !=null){
            $idPortal = $dom->createElement("IdPortal",$portal);
            $root->appendChild($idPortal);
        }

        $command = $dom->createElement("Command");
        $root->appendChild($command);

        // Método no webservice.
        $met = $dom->createElement("Name", $method);
        $command->appendChild($met);

        // Inicia os parâmetros.
        $parameters = $dom->createElement("Parameters");
        $command->appendChild($parameters);

        // Verifica se a chave foi enviada como um parâmetro, se não, insere ela diretamente.
        if (!isset($params['chave'])) {
            $chave = $dom->createElement("Parameter", $this->credentials->microvix_chave_acesso);
            $chave->setAttribute('id', 'chave');
            $parameters->appendChild($chave);
        }

        // Percorre cada parâmetro enviado.
        foreach ($params as $key => $value) {
            $new_param = $dom->createElement("Parameter", $value);
            $new_param->setAttribute('id', $key);
            $parameters->appendChild($new_param);
        }

        // Retorna o XML como string.
        return $dom->saveXML();
    }
}
