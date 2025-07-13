<?php
/*
 * 
*/  

require APPPATH . "controllers/Queues/Omnilogic/ProcessProductsNotifyOmnilogicQueue.php";

class ProcessProductsNotifyOmnilogicNovoMundo extends ProcessProductsNotifyOmnilogicQueue {
	protected function getFetchClass(){ return $this->router->fetch_class(); }
	protected function getPath() { return $this->router->directory; }
}
?>
