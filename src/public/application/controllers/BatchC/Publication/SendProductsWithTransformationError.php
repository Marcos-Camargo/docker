<?php

/**
 * @property CI_Session $session
 * @property CI_Loader $load
 * @property CI_Router $router
 * @property CI_DB_driver $db
 *
 * @property Model_errors_transformation $model_errors_transformation
 * @property Model_queue_products_marketplace $model_queue_products_marketplace
 */
class SendProductsWithTransformationError extends BatchBackground_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->session->set_userdata(array(
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        ));
        $this->load->model('model_errors_transformation');
        $this->load->model('model_queue_products_marketplace');
    }

    public function run($id = null, $params = null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;
        if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
            $this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
            return;
        }
        $this->log_data('batch',$log_name,'start '.trim($id." ".$params));

        if ($params === 'null') {
            $params = null;
        }

        $this->sendToQueue($params);

        $this->log_data('batch', $log_name,'finish');
        $this->gravaFimJob();
    }

    private function sendToQueue(int $store_id = null)
    {
        $date = dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL);
        $limit = 200;
        $last_id = 0;
        $check_registers_queue = 400;
        $time_sleep_queue_full = 60;
        //$time_limit_job_in_execution = 45;
        //$date_limit = addMinutesToDatetime(dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL), $time_limit_job_in_execution);

        while (true) {
            $errors = $this->model_errors_transformation->getErrosActiveWithProductActiveAndComplete($date, $limit, $last_id, $store_id);

            // Não encontrou mais produtos.
            if (empty($errors)) {
                break;
            }

            echo "Adicionando $limit produtos, iniciando no id $last_id.\n";

            // Lendo todos os produtos com erros.
            $create = array();
            foreach ($errors as $error) {
                // Id do ultimo erro, para a próximo consulta.
                $last_id = $error['id'];
                // Adicionar todos os produtos no vetor para adicionar na fila.
                $create[] = array(
                    'status' => 0,
                    'prd_id' => $error['prd_id'],
                    'int_to' => $error['int_to'],
                );
                echo "[$error[int_to]] $error[prd_id]\n";
            }

            // Validar se a fila está cheia.
            while (true) {
                $queue = $this->model_queue_products_marketplace->countQueue();
                if (!$queue || ($queue['qtd'] ?? 0) <= $check_registers_queue) {
                    break;
                }
                echo "A fila contem $queue[qtd] produtos, esperar diminuir para no mínimo $check_registers_queue registros. Esperar $time_sleep_queue_full segundos para tentar novamente.\n";
                // Se tem mais de 1000 produtos na fila, esperar 30 segundos para validar novamente antes de adicionar na fila.
                sleep($time_sleep_queue_full);

                // Se o programa está mais de 45 minutos em execução, deverá ser encerrado, pois, a fila está muito grande.
                /*if (strtotime($date_limit) < strtotime(dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL))) {
                    echo "A rotina ultrapassou o tempo de $time_limit_job_in_execution minutos em execução e a fila continua cheio, será executado no próximo dia.\n";
                    break 2;
                }*/
            }

            // Adiciona os produtos na fila.
            if (!empty($create)) {
                $this->model_queue_products_marketplace->create($create, true);
            }
        }
    }
}