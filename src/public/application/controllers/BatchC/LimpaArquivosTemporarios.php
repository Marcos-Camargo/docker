<?php
/*

Verifica quais ordens precisam de frete e contrata no frete rápido

*/    
require APPPATH . '/libraries/REST_Controller.php';

/**
 * @property Model_products $model_products
 */
class LimpaArquivosTemporarios extends BatchBackground_Controller {
		
	public function __construct()
	{
		parent::__construct();
		$logged_in_sess = array(
			'id' 		=> 1,
			'username'  => 'batch',
			'email'     => 'batch@conectala.com.br',
			'usercomp'  => 1,
			'userstore' => 0,
			'logged_in' => TRUE
		);
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_orders','myorders');
		$this->load->model('model_products');

    }

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
	//	sleep(random_int(1,120));	//aguarda um valor randomico para evitar conflito com o mesmo processo rodando em outro servidor 	
		
		$this->limpaTabelaQuotesShip();
		$this->moveTabelaLog();
		$this->limpaCiSessions();
		$this->limpaTabelaSnapshotProducts();
		$this->limpaSellerIndexHistory();
		$this->limpaOrdersToIntegration(6);
		$this->limpaTabelaQuotesCorreios();
		$this->limpaTabelaML();
		$this->escolheDiretoriosLimpeza();
		$this->limpaTabelaLogProducts();
		$this->limpaTabelaLogIntegrationProductMarketplace();
		$this->limpaDiretoriosImagens();
		$this->limpaAnymarketLog();
		$this->limpaErrorTransformation();
		$this->limpaTabelaLogQuotes();
		$this->limpaTabelaLogIntegration();
		$this->limpaTabelaLogIntegrationUnique();
		$this->limpaTabelaCampaignv2VtexCampaignsLogs();
		$this->limpaTableShippingTemporary();
		$this->limpaExternalIntegrationHistory();
		$this->limpaOrdersIntegrationHistory();
		$this->limpaLogIntegrationOrderMarketplace();
        $this->limpaOrderPaymentTransactions();
        $this->limpaAnymarketTempProduct();
        $this->limpaAnymarketQueue();
		//$this->limpaGeneralLog(); // O RDS já está limpando mantendo os últimos 3 dias
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	function limpaTabelaML() {
		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela mercado_livre_notifications\n";
		$sql = 'DELETE FROM mercado_livre_notifications WHERE date_create < DATE_SUB(NOW(), INTERVAL 1 MONTH)';
		return $this->db->query($sql);
	}

	function limpaErrorTransformation() {
		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela de erros de transformação já tratados\n";
		$sql = 'DELETE FROM errors_transformation WHERE status=2 AND date_update < DATE_SUB(NOW(), INTERVAL 15 DAY)';
		return $this->db->query($sql);
	}

	function limpaAnymarketLog() {

		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela anymarket_temp_product\n";
		$sql = 'DELETE FROM anymarket_temp_product WHERE date_create < DATE_SUB(NOW(), INTERVAL 7 DAY)';
	    $this->db->query($sql);
		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela anymarket_log\n";
		$sql = 'DELETE FROM anymarket_log WHERE date_create < DATE_SUB(NOW(), INTERVAL 15 DAY)';
	    $this->db->query($sql);
		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela anymarket_queue\n";
		$sql = 'DELETE FROM anymarket_queue WHERE checked=1 AND date_update < DATE_SUB(NOW(), INTERVAL 15 DAY)';
	    $this->db->query($sql);
		
		/*
		echo "Limpando a tabela anymarket_log\n";
		$sql = 'SELECT * FROM anymarket_log WHERE date_create < DATE_SUB(NOW(), INTERVAL 15 DAY)';
		$query = $this->db->query($sql);

        $logs = $query->result_array();
		foreach($logs as $log) {
			$sql = 'DELETE FROM anymarket_log WHERE id = ?';
			$query = $this->db->query($sql, $log['id']);
		}

		echo "Limpando a tabela anymarket_temp_product\n";
		$sql = 'SELECT * FROM anymarket_temp_product WHERE date_create < DATE_SUB(NOW(), INTERVAL 15 DAY)';
		$query = $this->db->query($sql);

        $logs = $query->result_array();
		foreach($logs as $log) {
			$sql = 'DELETE FROM anymarket_temp_product WHERE id = ?';
			$query = $this->db->query($sql, $log['id']);
		}
		*/
	}
	
	function limpaTabelaLogIntegration() {
		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela log_integration\n";

        // Esse caso de excluir a casa 1 mês, deve ser temporário, pois o banco não vai suportar excluir muitos registros
        for ($x = 10; $x > 0; $x--) {
            $last_id = $this->db->query("select id from log_integration use index (idx_log_integration_date_updated) WHERE date_updated < DATE_SUB(NOW(), INTERVAL $x MONTH) order by id desc limit 1")->row_array();
            if ($last_id) {
                $last_id = $last_id['id'];
                $this->db->query("DELETE FROM log_integration WHERE id < $last_id");
            }
        }
		$this->db->query('DELETE FROM log_integration WHERE date_updated < DATE_SUB(NOW(), INTERVAL 15 DAY)');
	}

	function limpaTabelaLogIntegrationUnique() {
		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela log_integration_unique\n";

        // Esse caso de excluir a casa 1 mês, deve ser temporário, pois o banco não vai suportar excluir muitos registros
        for ($x = 10; $x > 0; $x--) {
            $last_id = $this->db->query("select id from log_integration_unique use index (idx_log_integration_unique_date_updated) WHERE date_updated < DATE_SUB(NOW(), INTERVAL $x MONTH) order by id desc limit 1")->row_array();
            if ($last_id) {
                $last_id = $last_id['id'];
                $this->db->query("DELETE FROM log_integration_unique WHERE id < $last_id");
            }
        }
		$this->db->query('DELETE FROM log_integration_unique WHERE date_updated < DATE_SUB(NOW(), INTERVAL 15 DAY)');
	}

	function limpaTabelaCampaignv2VtexCampaignsLogs() {
		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela campaign_v2_vtex_campaigns_logs\n";
		$sql = 'DELETE FROM campaign_v2_vtex_campaigns_logs WHERE updated_at < DATE_SUB(NOW(), INTERVAL 3 MONTH)';
		return $this->db->query($sql);
	}

	function limpaSellerIndexHistory() {
		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela seller_index_history\n";
		$sql = 'DELETE FROM seller_index_history WHERE date < DATE_SUB(NOW(), INTERVAL 90 DAY)';
		return $this->db->query($sql);
	}

	function limpaTabelaLogProducts() {
		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela log_products\n";
		$sql = 'DELETE FROM log_products WHERE date_update < DATE_SUB(NOW(), INTERVAL 1 MONTH)';
		$query = $this->db->query($sql);
	}
	
	function limpaTabelaLogIntegrationProductMarketplace() {
		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela log_integration_product_marketplace\n";
		// $sql = 'DELETE FROM log_integration_product_marketplace WHERE date_update < DATE_SUB(NOW(), INTERVAL 1 MONTH)';
		$sql = 'DELETE FROM log_integration_product_marketplace WHERE date_update < DATE_SUB(NOW(), INTERVAL 5 DAY)';
		return $this->db->query($sql);
	}
	
	function limpaTabelaSnapshotProducts() {
		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela snapshot_products\n";
		$sql = 'DELETE FROM snapshot_products WHERE date < DATE_SUB(NOW(), INTERVAL 3 MONTH)';
		return $this->db->query($sql);
	}
	
	function limpaCiSessions() {
		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela ci_sessions\n";
		$yesterday = strtotime('YESTERDAY');
		$sql = 'DELETE FROM ci_sessions WHERE timestamp < ?';
		return $this->db->query($sql,$yesterday);
	}
		
	function limpaTabelaQuotesShip() {
		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela quotes_ship\n";
		$sql = "DELETE FROM quotes_ship WHERE validade < curdate()";
		return $this->db->query($sql);
	}
	
	function limpaTabelaQuotesCorreios() {
		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela quotes_correios\n";

        $quotes_correios_id = $this->db->select('id')
            ->where('date_update < DATE_SUB(NOW(), INTERVAL 1 MONTH)', null, false)
            ->order_by('id', 'desc')
            ->get('quotes_correios', 1)
            ->row_array();

        if ($quotes_correios_id) {
            $quotes_correios_id = (int)$quotes_correios_id['id'];
            $this->db->delete('quotes_correios', array('id <' => $quotes_correios_id));
        }
	}
	
	function limpaGeneralLog() {
		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela general_log\n";
		$ultimasemana= date("Y-m-d H:i:s",time() - 60 * 60 * 24*7);
		$sql = 'DELETE FROM mysql.general_log WHERE event_time < ?';
		return $this->db->query($sql,array($ultimasemana));
	}

	function limparDiretorio($diretorio,$data) {
		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando o diretório ".$diretorio." \n";
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$all_files  = glob($diretorio);
		foreach($all_files as $fileName) {
			if (file_exists($fileName) && filemtime($fileName) < $data )  {   // apago os arquivos temporários mais velho que a data 
				echo "Apagando ".$fileName."\n";
				shell_exec("sudo /bin/rm ".$fileName);
			}
		}
	}

	function escolheDiretoriosLimpeza() 
	{
		$yesterday = time() - 60 * 60 *24;
		$lastMonth = time() - 60 * 60 * 24 * 31;
		$lastSixMonths = time() - 60 * 60 * 24 * (31 * 6);
		$this->limparDiretorio(FCPATH."assets/images/etiquetas/temp_*.pdf", $yesterday);
		
		$this->limparDiretorio(FCPATH."assets/files/product_upload/*.csv", $yesterday);
		
		$this->limparDiretorio(FCPATH."assets/files/nfes_upload/*.csv", $yesterday);
		$this->limparDiretorio(FCPATH."assets/files/change_product_category/temp/*.csv", $yesterday);

		$this->removerDiretorios(FCPATH . "assets/files/products_via/tmp", $yesterday);
		$this->removerDiretorios(FCPATH . "assets/files/products_via/*/", $lastMonth);
		$this->removerDiretorios(FCPATH . "assets/files/report_products/*/", $lastSixMonths);
		$this->removerDiretorios(FCPATH . "assets/files/change_product_category/*/", $lastSixMonths);

		$this->limparDiretorio(FCPATH."application/logs/log-*.php", $lastMonth);

		$dirlog = $this->config->item('log_path');
		if ($dirlog !='') {
			$this->limparDiretorio($dirlog."log-*.php", $lastMonth);
		}
	}

	public function removerDiretorios($path, $date)
	{
		if (!file_exists($path)) {
			return true;
		}
		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Removendo o diretório ".$path." \n";
		if (strpos($path, '/*/') !== false) {
			$path = substr($path, 0, strrpos($path, '/*/'));
			$dir = opendir($path);
			if ($dir === false) {return true;}

			foreach (scandir($path) as $file) {
				if (in_array($file, ['.', '..'])) {continue;}
				if (is_dir("{$path}/{$file}")) {
					$this->removerDiretorios("{$path}/{$file}", $date);
				}
			}			
			closedir($dir);
			return true;
		}
		if (!file_exists($path)) {
			return true;
		}
		$dir = opendir($path);
		if ($dir === false) {return true;}
		foreach (scandir($path) as $file) {
			if (in_array($file, ['.', '..'])) {
				continue;
			}
			if (is_dir("{$path}/{$file}")) {
				$this->removerDiretorios("{$path}/{$file}", $date);
				if ($this->isDirEmpty("{$path}/{$file}")) {
					rmdir("{$path}/{$file}");
				}
				continue;
			}
			if (filemtime("{$path}/{$file}") < $date) {
				unlink("{$path}/{$file}");
			}
		}
		closedir($dir);
		return $this->isDirEmpty($path);
	}

	public function isDirEmpty($path): ?bool
	{
		if (!is_readable($path)) {
			return null;
		}
		return (count(scandir($path)) == 2);
	}

	function moveTabelaLog() 
	{
		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Movendo logs\n";
		$this->load->dbforge();
		if(date('j') === '1') {  // Renomeia o arquivo de log no primeiro dia do mês			
			$newtable = 'log_history_'.date("Y_m", strtotime("first day of previous month"));
			if (!$this->db->table_exists($newtable)) {  // ve se já existe antes de renomear
				echo "Renomeando log_history para ".$newtable;
				$this->dbforge->rename_table('log_history', $newtable);
				$this->db->query("CREATE TABLE log_history LIKE $newtable");	
			}
			$newtable = 'log_history_api_'.date("Y_m", strtotime("first day of previous month"));
			if (!$this->db->table_exists($newtable)) {  // ve se já existe antes de renomear
				echo "Renomeando log_history para ".$newtable;
				$this->dbforge->rename_table('log_history_api', $newtable);
				$this->db->query("CREATE TABLE log_history_api LIKE $newtable");
			}	
			$newtable = 'log_history_batch_'.date("Y_m", strtotime("first day of previous month"));
			if (!$this->db->table_exists($newtable)) {  // ve se já existe antes de renomear
				echo "Renomeando log_history para ".$newtable;
				$this->dbforge->rename_table('log_history_batch', $newtable);
				$this->db->query("CREATE TABLE log_history_batch LIKE $newtable");
			}
		}

		for ($i=3; $i<=12; $i++) { // guardo só 6 meses 
			$killtable = 'log_history_'.date("Y_m", strtotime("first day of ".$i." month ago"));
			echo $killtable."\n";
			if ($this->db->table_exists($killtable)) {  // ve se já existe antes de destruir
				$this->dbforge->drop_table($killtable);				
			}
		}
		for ($i=3;$i<=12;$i++) { // guardo só 4 meses e a atual
			$killtable = 'log_history_batch_'.date("Y_m", strtotime("first day of ".$i." month ago"));
			echo $killtable."\n";
			if ($this->db->table_exists($killtable)) {  // ve se já existe antes de destruir
				$this->dbforge->drop_table($killtable);
			}
		}
		for ($i=3;$i<=12;$i++) { // guardo só 2 meses e a atual 
			$killtable = 'log_history_api_'.date("Y_m", strtotime("first day of ".$i." month ago"));
			echo $killtable."\n";
			if ($this->db->table_exists($killtable)) {  // ve se já existe antes de destruir
				$this->dbforge->drop_table($killtable);
			}			
		}
		
	}

	function limpaDiretoriosImagens() 
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpa imagens \n";

		$root = FCPATH . 'assets/images/product_image/';
		$delfolder = FCPATH . 'assets/images/delete_image/';
		if(!is_dir($delfolder)) {
			mkdir($delfolder);
		}
		
		if (date('D') == 'Mon') { // Só roda na segunda 
			$folders = scandir($delfolder);	
			foreach($folders as $folder) {
				if (($folder!=".") && ($folder!="..")) {
					echo 'Removendo '.$delfolder.$folder."\n";
					shell_exec("sudo /bin/rm -fr '".$delfolder.$folder."'");
				}
			}
		}
		
		$folders = scandir($root);	
		$cnt = 0;
		foreach($folders as $folder) {
			if (($folder!=".") && ($folder!="..")) {
				
				$sql = 'SELECT * FROM products WHERE image = "'.$folder.'"';
				$query = $this->db->query($sql);
				$prd = $query->row_array();
				if (empty($prd)) {
					echo 'Movendo para remover '.$folder."\n";
					//shell_exec("sudo /bin/rm -fr '".$root.$folder."'");
					shell_exec("sudo /bin/mv  '".$root.$folder."' '".$delfolder."'");
				} else {
					/*
					// Não tem variação.
					if (empty($prd['has_variants'])) {
						continue;
					}

					// Remove as pastas sem uso das variações.
					// Nome das pastas que realmente estão em uso.
					$images_variation = array_merge(array('.', '..'), array_map(function ($variation){
						return $variation['image'];
					}, $this->model_products->getVariantsByProd_id($prd['id'])));

					// Ler arquivos/pastas dentro da pasta do produto pai.
					$dir_image_product  = $root.$folder.'/';
					$folders_var 		= scandir($dir_image_product);
					foreach($folders_var as $folder_var) {
						// Se for um diretório, faz validação para saber se a pasta está em uso.
						if (is_dir($dir_image_product.$folder_var) && !in_array($folder_var, $images_variation)) {
							echo 'Movendo para remover '.$folder_var."\n";
							shell_exec("sudo /bin/mv  '".$root.$folder.'/'.$folder_var.'/'."' '".$delfolder."'");
						}
					}
					*/
				}
			}
		}
		
		
	}

	/**
	 * @param 	int  $months  Quantidade de meses pra trás que serão removidos
	 * @return 	bool		  Status do retorno da exclusão
	 */
	function limpaOrdersToIntegration(int $months)
	{
		$dateBeforeMonths = date('Y-m-d H:i:s', strtotime("-{$months} months", time()));

		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela orders_to_integration\n";
		if (!$this->db->query('DELETE FROM orders_to_integration WHERE updated_at < ?', array($dateBeforeMonths))) {return false;}

		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela orders_to_integration_master\n";
		if (!$this->db->query('DELETE FROM orders_to_integration_master WHERE updated_at < ?', array($dateBeforeMonths))) {return false;}

		return true;
	}

	private function saveFile($logs, $dateEnd, $path)
	{
		// nome de arquivo não aceita ':' e troca os espaços
		$dateEnd = date('Y-m-d', strtotime($dateEnd));

		// troca barra
		$path = str_replace('\\', '/', $path);

		// Se não existe a pasta, será criada.
		if(!is_dir($path)) {
			$oldmask = umask(0);
			@mkdir($path, 0775);
			umask($oldmask);
		}

		// Cria o arquivo de texto.
		$fp = fopen("$path/$dateEnd.txt", "w");

		// Escreve no arquivo de texto.
		fwrite($fp, json_encode($logs));

		// Fecha o arquivo de texto.
		fclose($fp);

		// Compactar arquivo de texto.
		echo "tar -czf $path/$dateEnd.tar.gz $path/$dateEnd.txt\n";
		shell_exec("tar -czf $path/$dateEnd.tar.gz $path/$dateEnd.txt");

		// Remove arquivo de texto.
		unlink("$path/$dateEnd.txt");
	}

	private function limpaTabelaLogQuotes()
	{
        echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela log_quotes\n";

        // Esse caso de excluir a casa 1 mês, deve ser temporário, pois o banco não vai suportar excluir muitos registros
        for ($x = 10; $x > 0; $x--) {
            $last_id = $this->db->query("select id from log_quotes WHERE created_at < DATE_SUB(NOW(), INTERVAL $x MONTH) order by id desc limit 1")->row_array();
            if ($last_id) {
                $last_id = $last_id['id'];
                $this->db->query("DELETE FROM log_quotes WHERE id < $last_id");
            }
        }

        $log_quotes_id = $this->db->select('id')
            ->where('created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)', null, false)
            ->order_by('id', 'desc')
            ->get('log_quotes', 1)
            ->row_array();

        if ($log_quotes_id) {
            $log_quotes_id = (int)$log_quotes_id['id'];
            return $this->db->delete('log_quotes', array('id <' => $log_quotes_id));
        }

        return true;
	}

	private function limpaTableShippingTemporary() {
		$date = date('Y-m-d H:i:s', strtotime("-10 days", time()));
		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela table_shipping_temporary de $date pra trás.\n";
		$this->db->delete('table_shipping_temporary', array('date_created <' => $date));
	}

	private function limpaExternalIntegrationHistory() {
		$date = date('Y-m-d H:i:s', strtotime("-2 months", time()));
		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela external_integration_history de $date pra trás.\n";
		$this->db->delete('external_integration_history', array('created_at <' => $date));
	}

	private function limpaOrdersIntegrationHistory() {
		$date = date('Y-m-d H:i:s', strtotime("-3 months", time()));
		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela orders_integration_history de $date pra trás.\n";
		$this->db->delete('orders_integration_history', array('created_at <' => $date));
	}

	private function limpaLogIntegrationOrderMarketplace() {
		$date = date('Y-m-d H:i:s', strtotime("-1 year", time()));
		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela log_integration_order_marketplace de $date pra trás.\n";
		$this->db->delete('log_integration_order_marketplace', array('date_create <' => $date));
	}

	private function limpaOrderPaymentTransactions() {
		$date = date('Y-m-d H:i:s', strtotime("-1 month", time()));
		echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela order_payment_transactions de $date pra trás.\n";
		$this->db->delete('order_payment_transactions', array('date_created <' => $date));
	}

    private function limpaAnymarketTempProduct() {
        echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela anymarket_temp_product\n";

        $anymarket_temp_product_id = $this->db->select('id')
            ->where('date_create < DATE_SUB(NOW(), INTERVAL 1 MONTH)', null, false)
            ->order_by('id', 'desc')
            ->get('anymarket_temp_product', 1)
            ->row_array();

        if ($anymarket_temp_product_id) {
            $anymarket_temp_product_id = (int)$anymarket_temp_product_id['id'];
            $this->db->delete('date_create', array('id <' => $anymarket_temp_product_id));
        }
    }

    private function limpaAnymarketQueue() {
        $date = date('Y-m-d H:i:s', strtotime("-1 month", time()));
        echo dateNow()->format(DATETIME_INTERNATIONAL) . " Limpando a tabela anymarket_queue de $date pra trás.\n";
        $this->db->delete('anymarket_queue', array('date_create <' => $date, 'checked' => 1));
    }
}
