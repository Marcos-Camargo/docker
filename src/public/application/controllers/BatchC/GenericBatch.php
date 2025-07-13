<?php

abstract class GenericBatch extends BatchBackground_Controller
{

    public $logName;

    protected function startJob(string $functionName, $id = null, $params = null): void
    {

        /* inicia o job */
        $this->setIdJob($id);
        $this->logName = $this->router->fetch_class() . '/' . $functionName;
        if (!$this->gravaInicioJob($this->router->fetch_class(), $functionName)) {
            $this->log_data('batch', $this->logName, 'Já tem um job rodando ou que foi cancelado', "E");
            return;
        }
        $this->log_data('batch', $this->logName, 'start ' . trim($id . " " . $params), "I");

    }

    protected function endJob(): void
    {

        /* encerra o job */
        $this->log_data('batch', $this->logName, 'finish', "I");
        $this->gravaFimJob();

    }

}
