<?php
/*
    Title: PHP Script Background Processer
    Version: 1.0
    Auther: Sanjay Kumar Panda
    Description: Here we can run a PHP file (script) in background, This process is hidden to the end user. IT improves your Website efficiency.  

 * Varias mudanças para a Conecta la
 * */

class BackgroundProcess{
    public  $pid;
    private $command;
    private $msg=array();
	private $logfile= '/dev/null'; 
    
    /*
    * @Param $cmd: Pass the linux command want to run in background 
    */
    public function __construct($cmd=null){
      
        if(!empty($cmd))
        {
            $this->command=$cmd;
            $this->do_process();
        }
        else{
            $this->msg['error']="Please Provide the Command Here";
        }
    }
    
    public function setCmd($cmd){
        $this->command = $cmd;
        return true;
    }
    
	 public function setLogFile($logfile){
        $this->logfile = $logfile;
        return true;
    }
	 
    public function setProcessId($pid){
        $this->pid = $pid;
        return true;
    }

    public function getProcessId(){
        return $this->pid;
    } 
	
    public function status(){
        $command_ps = 'ps -p '.$this->pid;
        exec($command_ps,$op);        
        if (!isset($op[1])) {return false;}
        else {return true;}
    }
    
    public function showAllPocess(){
        $command_ps = 'ps -ef '.$this->pid;
        exec($command_ps,$op);
        return $op;
    }
    
    public function start(){
    	if ($this->command != '') {
            $this->do_process();
        }
        else {
            return true;
        }
    }

    public function stop(){
        $command_kill = 'kill '.$this->pid;
        exec($command_kill);
        if (!$this->status()) {return true;}
        else {return false;}
    }
    
    //do the process in background
    public function do_process(){
		$command_nohup = 'nohup '.$this->command.' > '.$this->logfile.' 2>/dev/null & echo $!';
		echo "\nExecutando ".$command_nohup;
        exec($command_nohup ,$pross);
        $this->pid = (int)$pross[0];
    }
       
}
?>