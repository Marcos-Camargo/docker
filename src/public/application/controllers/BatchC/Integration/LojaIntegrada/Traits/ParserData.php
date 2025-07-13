<?php
require APPPATH . "controllers/BatchC/Integration/LojaIntegrada/Traits/PrintInTerminal.php";

if (!defined('ParserData')) {
    define('ParserData', '');
    /**
     * Para os arrays é muito mais simples trazer os dados em um campo mesmo que inserido manualmente e o
     * parser só realizar a conversão.
     */
    trait ParserData
    {
        use PrintInTerminal;
        private function converteData($fields, $body, &$data = [])
        {
            foreach ($fields as $key => $field) {
                if ($field['require']) {
                    $this->verifyIfExiste($body, $field, $data);
                } else {
                    try {
                        $this->verifyIfExiste($body, $field, $data);
                    } catch (Exception $e) {
                        $this->product[$field['fieldGoal']] = $field['default'];
                    }
                }
            }
            return $data;
        }
        private function makeString($body, $field, &$data)
        {
            if (!isset($body[$field['field']])) {
                throw new Exception("Campo " . $field['field'] . " Não foi devidamente configurado.\n");
            } else {
                $this->printInTerminal('Validado com sucesso campo:' . json_encode($field['field']) . " para :" . json_encode($field['fieldGoal']) . " " . $body[$field['field']] . "\n");
                $data[$field['fieldGoal']] = $body[$field['field']];
            }
        }
        private function makeArrayToString($body, $field, &$data)
        {
            $bodyToTeste = $body;
            foreach ($field['field'] as $key => $anyKey) {
                if (!isset($bodyToTeste[$anyKey])) {
                    throw new Exception("Campo " . json_encode($field['field']) . " Não foi devidamente configurado.\nNão encontrado: " . $anyKey . "\n");
                } else {
                    $bodyToTeste = $bodyToTeste[$anyKey];
                }
            }
            // if($bodyToTeste)
            $data[$field['fieldGoal']] = $bodyToTeste;
            $this->printInTerminal('Validado com sucesso campo:' . json_encode($field['field']) . " para:" . json_encode($field['fieldGoal']) . "\n");
        }
        private function verifyIfExiste($body, $field, &$data = [])
        {
            if ($field['type'] == 'string') {
                $this->makeString($body, $field, $data);
            }
            if ($field['type'] == 'arraytostring') {
                $this->makeArrayToString($body, $field, $data);
            }
            if ($field['type'] == 'arraytoarray') {
                $this->makeArrayToArray($body, $field, $data);
            }

        }
        private function makeArrayToArray($body, $field, &$data,$default=null)
        {
            $bodyToTeste = $body;
            foreach ($field['field'] as $key => $anyKey) {
                if (!isset($bodyToTeste[$anyKey])) {
                    throw new Exception("Campo " . json_encode($field['field']) . " Não foi devidamente configurado.\nNão encontrado: " . $anyKey . "\n");
                } else {
                    $bodyToTeste = $bodyToTeste[$anyKey];
                }
            }
            $this->setDataOnArray($field['fieldGoal'], $bodyToTeste, $data);
            $this->printInTerminal('Validado com sucesso campo:' . json_encode($field['field']) . " para:" . json_encode($field['fieldGoal']) . "\n");
        }
        private function setDataOnArray($arrayDefinition, $dataField, &$data)
        {
            if (empty($arrayDefinition)) {
                return;
            }
            $lastLabel = 'undefined';
            $position = &$data;
            foreach ($arrayDefinition as $label) {
                $lastLabel = $label;
                if (!isset($position[$label]) || !is_array($position[$label])) {
                    $position[$label] = [];
                }
                $position = &$position[$label];
            }
            $position = $dataField;
        }
    }
}
