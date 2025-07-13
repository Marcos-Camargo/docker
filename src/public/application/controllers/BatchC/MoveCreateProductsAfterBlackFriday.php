<?php
/*
troca os jobs de CreateProduct para a semana depois da BF - 2022 
*/
class MoveCreateProductsAfterBlackFriday extends BatchBackground_Controller {

    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        // carrega os modulos necessários para o Job
        $this->load->model('model_orders','myorders');

    }
    // php index.php BatchC/MoveCreateProductsAfterBlackFriday run mull
    function run($id=null,$params=null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
            $this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
            return ;
        }
        $this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");

        /* faz o que o job precisa fazer */
        $newDate = '2022-11-27';
        echo "Procurando Jobs para alterar \n";
        $this->changeDateCalendar($newDate);

        /* encerra o job */
        $this->log_data('batch',$log_name,'finish',"I");
        $this->gravaFimJob();
    }

    function changeDateCalendar($newDate)
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;

        $sql = 'SELECT * FROM calendar_events WHERE module_path LIKE "%CreateProduct%" AND params is not null';
        $query = $this->db->query($sql);
        $calendars = $query->result_array();
        foreach($calendars as $calendar) 
        {
            if (!is_numeric($calendar['params'])) {
                continue;
            }
            $novo = $newDate.' '.substr($calendar['start'], -8);
            echo "Processando ".$calendar['module_path'].' '.$calendar['module_method'].' '.$calendar['params'].' '.$calendar['start'].' -> '.$novo."\n"; 

            $this->db->where('id', $calendar['ID']);
			$update = $this->db->update('calendar_events', array('start'=> $novo));            

            $this->db->where('module_path', $calendar['module_path']);
            $this->db->where('module_method', $calendar['module_method']);
            $this->db->where('params', $calendar['params']);
			$delete = $this->db->delete('job_schedule');

        }
       
    }

}

?>
