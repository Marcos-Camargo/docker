<?php

require 'system/libraries/Vendor/autoload.php';

use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Writer;
use League\Csv\CharsetConverter;

/**
 * @property Model_csv_to_verifications $model_csv_to_verifications
 * @property Model_shipping_company $model_shipping_company
 * @property Model_table_shipping $model_table_shipping
 * @property Model_table_shipping_regions $model_table_shipping_regions
 */

class TableFreight extends BatchBackground_Controller
{
	private $module;
    private $enable_table_regions = false;
	
	public function __construct()
	{
		parent::__construct();
		$logged_in_sess = array(
			'id' 		=> 1,
			'username'  => 'batch',
			'email'     => 'batch@conectala.com.br',
			'usercomp' 	=> 1,
			'userstore' => 0,
			'logged_in' => TRUE
		);
		$this->session->set_userdata($logged_in_sess);

		$this->load->model('model_csv_to_verifications');
		$this->load->model('model_shipping_company');
		$this->load->model('model_table_shipping');
        $this->load->model('model_table_shipping_regions');
        $this->setEnableTableShippingRegions();
    }
	
	public function run($id=null,$params=null)
	{
		$this->module = 'Shippingcompany';

		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}

		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");

		$this->processTableFreight();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	private function processTableFreight()
	{
        $t_start = microtime(true) * 1000;
		$files = $this->model_csv_to_verifications->getDontChecked(false, $this->module);

		foreach ($files as $file) {

            $fileTempId = $file['id'];

			echo "Lendo arquivo id=$fileTempId ...\n";

			$formData = json_decode($file['form_data']);
			
			if (empty($formData)) {
				echo "Valor do campo 'form_data' chegou vázio ou errado. arquivo id=$fileTempId\n";
                $this->model_csv_to_verifications->update(
                    array(
                        'processing_response' 	=> 'O arquivo foi enviado incorretamente, faça um novo envio. (form_data empty)',
                        'final_situation' 		=> 'err',
                        'checked' 			  	=> 1
                    ),
                    $fileTempId
                );
				continue;
			}

			$dateFormat = DateTime::createFromFormat("d/m/Y", $formData->dt_fim);
			$formData->dt_fim = $dateFormat->format('Y-m-d');

			$fileName = substr($file['upload_file'], strrpos($file['upload_file'], '/') + 1);
			// $dir 		= getcwd() . '/importacao/frete/';
			$dir = "/importacao/frete/{$formData->shippingCompanyId}";
			// $fileName 	= uniqid() . '.csv';

			//Valida Colunas do CSV
			try {
				$this->processFile("$dir/$fileName", $fileTempId, $formData->shippingCompanyId);
			} catch (Exception $exception) {
				echo "Encontrou um erro para processar o arquivo. arquivo id=$fileTempId. Erros na coluna 'processing_response'\n";
				$this->model_csv_to_verifications->update(
					array(
						'processing_response' 	=> $exception->getMessage(),
						'final_situation' 		=> 'err',
						'checked' 			  	=> 1
					),
					$fileTempId
				);
				continue;
			}

            // echo "Dados processados, copiando arquivo para $dir\n";

			// copy($file['upload_file'], $dir . $fileName);

			// sucesso no processamento do arquivo
			$fileId = $this->model_shipping_company->insertFileTableShipping(
				array(
					"directory" 				=> $dir,
                    "file_table_shippingcol" 	=> $fileName,
                    "shipping_company_id" 	    => $formData->shippingCompanyId,
					"dt_create_file" 			=> date('Y-m-d H:i:s'),
					"dt_start_v" 				=> date('Y-m-d H:i:s'),
					"dt_end_v" 					=> $formData->dt_fim,
					"status" 					=> 1
				)
			);

            echo "Realizando transferência de dados de table_shipping_temporary -> table_shipping\n";
			$this->transferDataTemporary($fileId, $fileTempId);
            echo "Transferência realizada da table_shipping_temporary -> table_shipping\n";
			$this->model_shipping_company->setTypeTableShipping($formData->shippingCompanyId, 1);
			// $this->model_shipping_company->updateStatusInactiveFileRow($formData->shippingCompanyId, $fileId);
            if (!$this->enable_table_regions) {
                $this->model_shipping_company->deleteInactiveFileRow($formData->shippingCompanyId, $fileId);
            }
			$this->updateStatusInactiveFile($formData->shippingCompanyId, $fileId);
            echo "Limpando dados da tabela table_shipping_temporary\n";
            $this->model_table_shipping->deleteTemporary($fileTempId);

			$this->model_csv_to_verifications->update(
				array(
					'processing_response' => 'Arquivo processado com sucesso!',
					'final_situation' => 'success',
					'checked' => '1'
				),
				$fileTempId
			);

            echo "Total time process: " . number_format((((microtime(true) * 1000) - $t_start) / 1000), 5, '.', '')."\n";
			echo "Arquivo processado com sucesso. arquivo id=$fileTempId\n";
		}
	}

	private function updateStatusInactiveFile($shippingCompanyId, $fileId)
	{
        if ($this->enable_table_regions) {
            $this->model_table_shipping_regions->removeOldRows($shippingCompanyId, $fileId);
        }

		$getTableConfigShippingIds = $this->model_shipping_company->getTableConfigShippingIds($shippingCompanyId, $fileId);
		$fileListId = array();

		for ($i = 0; $i < count($getTableConfigShippingIds); $i++) {
			$fileListId[] = $getTableConfigShippingIds[$i]['id_file'];
		}
		if (!empty($fileListId)) {
			$this->model_shipping_company->updateStatusInactiveFile(implode(",", $fileListId));
		}
	}

	private function transferDataTemporary($fileId, $fileTempId)
	{
        if ($this->enable_table_regions) {
            $this->model_table_shipping->transferDataTemporaryToTableRegions($fileTempId, $fileId);
        } else {
            $this->model_table_shipping->transferDataTemporaryToReal($fileTempId, $fileId);
        }
	}

	/**
	 * @param 	string 	$fileCSV			Caminho do arquivo.
	 * @param 	int 	$fileId				Código do arquivo.
	 * @param 	int 	$shippingCompanyId	Código da transportadora.
	 * @throws 	\League\Csv\Exception
	 * @throws 	Exception
	 */
	private function processFile(string $fileCSV, int $fileId, int $shippingCompanyId)
	{
		$count  = 1;
		$errors	= array();

        $t_start = microtime(true) * 1000;

		$csv = Reader::createFromPath(getcwd() . $fileCSV); // lê o arquivo csv
		$csv->setDelimiter(';'); // separados de colunas
		$csv->setHeaderOffset(0); // linha do header

		$colunaFaltante = false;
		$colunasFaltantes = array();
		$headers = $csv->getHeader();

		if (in_array('', $headers)) {
			$errors[] = "[LINHA: 1] Não é possível importar arquivo contendo cabeçalho em branco.";
			throw new Exception('<ul><li>' . implode('</li><li>', $errors) . '</li></ul>');
		}

		foreach (array("regiao","cep_inicial","cep_final","peso_mim","peso_max","preco","qtd_dias") as $chave) {
			if (!in_array($chave, $headers)) {
				$colunasFaltantes[] = $chave;
				$colunaFaltante = true;
			}
		}
		$colunasFaltantes = implode(', ', $colunasFaltantes);

		if ($colunaFaltante) {
			$errors[] = "[LINHA: 1] Informe todas as colunas para enviar o arquivo. Faltando: ($colunasFaltantes)";
			throw new Exception('<ul><li>' . implode('</li><li>', $errors) . '</li></ul>');
		}

		$stmt   = new Statement();
		$datas  = $stmt->process($csv);

		$this->model_table_shipping->deleteTemporary($fileId);

        $t_init_validation = microtime(true) * 1000;

        $arrInsertBatch = array();
		foreach ($datas as $data) {
			$count++;

			$lineEmpty = true;
			foreach ($data as $line) {
				if (trim($line) != '') $lineEmpty = false;
			}

			if ($lineEmpty) {
				echo "[LINHA: $count] Processando [EM BRANCO]...\n";
				continue; // ignoro linha em branco
			}

			echo "[LINHA: $count] Processando...\n";

			$zipStart       = $data['cep_inicial'] 	= str_pad(onlyNumbers($data['cep_inicial']), 8, 0, STR_PAD_LEFT);
			$zipEnd         = $data['cep_final']	= str_pad(onlyNumbers($data['cep_final']), 8, 0, STR_PAD_LEFT);
			$weightStart    = $data['peso_mim'] 	= $this->fmtNum($data['peso_mim']);
			$weightEnd      = $data['peso_max'] 	= $this->fmtNum($data['peso_max']);
			$priceQuote     = $data['preco'] 		= $this->fmtNum($data['preco']);
			$deadlineQuote  = $data['qtd_dias'] 	= $this->fmtNum($data['qtd_dias']);
			$region			= $data['regiao'] 		= $this->detectUTF8($data['regiao']);

			// cep inicial com 8 caracteres
			if (strlen($zipStart) !== 8) {
				$errors[] = "[LINHA: $count] O CEP inicial está inválido";
			}
			// cep final com 8 caracteres
			if (strlen($zipEnd) !== 8) {
				$errors[] = "[LINHA: $count] O CEP final está inválido";
			}
			// preço para entrega negativo
			if ($priceQuote < 0) {
				$errors[] = "[LINHA: $count] O preço para entrega deve ser maior que zero.";
			}
			// prazo para entrega negativo
			if ($deadlineQuote < 0) {
				$errors[] = "[LINHA: $count] O prazo para entrega deve ser maior que zero.";
			}
			// preço para entrega negativo
			if ($weightStart < 0) {
				$errors[] = "[LINHA: $count] O peso inicial deve ser maior que zero.";
			}
			// preço para entrega negativo
			if ($weightEnd < 0) {
				$errors[] = "[LINHA: $count] O peso inicial deve ser maior que zero.";
			}
			// validar dias de entrega como inteiro
			if (!preg_match('/^[1-9][0-9]*$/', $deadlineQuote)) {
				$errors[] = "[LINHA: $count] O prazo para entrega deve ser um valor inteiro.";
			}
			// peso inicial maior que o final
			if ($weightStart > $weightEnd) {
				$errors[] = "[LINHA: $count] O peso inicial não pode ser maior que o peso final.";
			}
			// cep inicial maior que o final
			if ($zipStart > $zipEnd) {
				$errors[] = "[LINHA: $count] O CEP inicial não pode ser maior que o CEP final.";
			}

            $arrInsertBatch[] = array(
                'shipping_company_id' 	=> $shippingCompanyId,
                'file_id' 				=> $fileId,
                'region' 				=> $region,
                'cep_start' 			=> $zipStart,
                'cep_end' 				=> $zipEnd,
                'weight_start' 			=> $weightStart,
                'weight_end' 			=> $weightEnd,
                'price' 				=> $priceQuote,
                'deadline' 				=> $deadlineQuote,
                'line_file'             => $count
			);
		}

        $t_read_all_data = microtime(true) * 1000;

        if (count($errors)) {
            throw new Exception('<ul><li>' . implode('</li><li>', $errors) . '</li></ul>');
        }

        if (!count($arrInsertBatch)) {
            throw new Exception('<ul><li>Planilha com valores em branco. Lembre-se todas as colunas devem ser preenchidas. Reveja seu arquivo e tente novamente!</li></ul>');
        }

        echo "Inserindo ".count($arrInsertBatch)." linhas, para validar os dados\n";
        $this->model_table_shipping->createTemporary($arrInsertBatch);
        $t_insert_all_data = microtime(true) * 1000;

        $t_validate_5k      = microtime(true) * 1000;
        $t_validate_10k     = microtime(true) * 1000;
        $t_validate_50k     = microtime(true) * 1000;
        $t_validate_100k    = microtime(true) * 1000;

        $count = 0;
        foreach ($this->model_table_shipping->getTemporaryByFile($fileId) as $file) {

            // range de cep e peso com conflito
            $getTemporaryByFileAndRangeZip = $this->model_table_shipping->getTemporaryByFileAndRangeZip($fileId, $file['cep_start'], $file['cep_end']);
            foreach ($getTemporaryByFileAndRangeZip as $rowRange) {
                $count++;
                echo "[LINHA: $count] Validando...\n";

                if ($count == 5000) {
                    $t_validate_5k = microtime(true) * 1000;
                }
                if ($count == 10000) {
                    $t_validate_10k = microtime(true) * 1000;
                }
                if ($count == 50000) {
                    $t_validate_50k = microtime(true) * 1000;
                }
                if ($count == 100000) {
                    $t_validate_100k = microtime(true) * 1000;
                }

                $weightStart = $rowRange['weight_start'];
                $weightEnd   = $rowRange['weight_end'];
                $codeRow     = $rowRange['id'];

                foreach ($getTemporaryByFileAndRangeZip as $lineImported) {
                    if ($lineImported['id'] == $codeRow) {
                        continue;
                    }

                    if (
                        ($lineImported['weight_start'] <= $weightStart && $weightStart <= $lineImported['weight_end']) ||
                        ($lineImported['weight_start'] <= $weightEnd && $weightEnd <= $lineImported['weight_end'])
                    ) {
                        $errors[] = "[LINHA: {$lineImported['line_file']}] Foi encontrado um conflito de CEP com Peso. Faça o ajuste para garantir apenas uma resposta no range.";
                    }

                    if (count($errors) > 100) {
                        throw new Exception('<ul><li>' . implode('</li><li>', $errors) . '</li><li>Existem mais linhas com erro, mas é limitado para mostrar apenas 100 linhas, reveja o arquivo completo e faça a correção.</li></ul>');
                    }
                }
            }
        }

        $t_validate_all_data = microtime(true) * 1000;
        $t_finish_process = microtime(true) * 1000;

        echo "Métricas:\n";
        print_r(
            array(
                'errors'                => $errors,
                'total_registers'       => $count,
                't_init_validation'     => number_format((($t_init_validation - $t_start) / 1000), 5, '.', '') . ' seconds',
                't_read_all_data'       => number_format((($t_read_all_data - $t_init_validation) / 1000), 5, '.', '') . ' seconds',
                't_insert_all_data'     => number_format((($t_insert_all_data - $t_read_all_data) / 1000), 5, '.', '') . ' seconds',
                't_validate_5k'         => number_format((($t_validate_5k - $t_insert_all_data) / 1000), 5, '.', '') . ' seconds',
                't_validate_10k'        => number_format((($t_validate_10k - $t_insert_all_data) / 1000), 5, '.', '') . ' seconds',
                't_validate_50k'        => number_format((($t_validate_50k - $t_insert_all_data) / 1000), 5, '.', '') . ' seconds',
                't_validate_100k'       => number_format((($t_validate_100k - $t_insert_all_data) / 1000), 5, '.', '') . ' seconds',
                't_validate_all_data'   => number_format((($t_validate_all_data - $t_insert_all_data) / 1000), 5, '.', '') . ' seconds',
                't_finish_process'      => number_format((($t_finish_process - $t_start) / 1000), 5, '.', '') . ' seconds',
                'pid'                   => getmypid(),
                'date'                  => dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL)
            )
        );
        echo "\n";

		if (count($errors)) {
			throw new Exception('<ul><li>' . implode('</li><li>', $errors) . '</li></ul>');
		}
	}

	public function detectUTF8($string): string
	{
		//$string = utf8_encode($string);
		$detect = preg_match('%(?:
        [\xC2-\xDF][\x80-\xBF]        # non-overlong 2-byte
        |\xE0[\xA0-\xBF][\x80-\xBF]               # excluding overlongs
        |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
        |\xED[\x80-\x9F][\x80-\xBF]               # excluding surrogates
        |\xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
        |[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
        |\xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
        )+%xs', $string);

		return $detect == 1 ? $string : utf8_encode($string);
	}

    private function setEnableTableShippingRegions()
    {
        $settings_enable_table_regions  = $this->db->get_where('settings', array('name' => 'enable_table_shipping_regions'));
        $row_enable_table_regions       = $settings_enable_table_regions->row_array();
        $enable_table_regions           = false;

        if ($row_enable_table_regions && $row_enable_table_regions['status'] == 1) {
            $enable_table_regions = true;
        }

        $this->enable_table_regions = $enable_table_regions;
    }
	
}
