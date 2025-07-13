<?php

require APPPATH . "controllers/BatchC/SellerCenter/Mosaico/Main.php";

/**
 * Classe responsável pelo fluxo completo de cadastro de sellers na Mosaico.
 * Sincroniza dados de bancos e aggregrate merchant.
 * Pode ser sub-dividido em diferentes jobs, como job de cadastro de bancos e aggregate merchant.
 * A principio, manter unificado caso não apresente impacto negativo na execução. (PO solicitou)
 * 
 * @property Model_banks 						$model_banks
 * @property Model_integrations 				$model_integrations
 * @property Model_mosaico_aggregate_merchant 	$model_mosaico_aggregate_merchant
 * @property Model_mosaico_sales_channel		$model_mosaico_sales_channel 
 * @property Model_stores 						$model_store
 */
class Seller extends Main
{
	var $int_from = null;

	/**
	 * @var 	 array{string} Array contendo as opções de tipos de conta na mosaico.
	 */
	private $mosaicoAccountTypes;

	/**
	 * @var array{array{id:int,name:string,aggregate_merchant:string}}
	 */
	private $conectaAggregate;

	/**
	 * @var array<string,string> Array contendo o aggregate como chave e o nome como valor.
	 */
	private $mosaicoAggregate;

	/**
	 * @var 	 array{array{code:string,name:string}} Array contendo código COMPE e nome disponíveis na Mosaico.
	 */
	private $conectaBanks;

	/**
	 * @var 	 array{array{code:string,name:string}} Array contendo código COMPE e nome disponíveis na Mosaico.
	 */
	private $mosaicoBanks;

	/**
	 * @var 	 array	 	Dados da integração do marketplace.
	 */
	private $mainIntegration;

	/**
	 * @var 	 array	 	Dados de autenticação do marketplace.
	 */
	private $mainAuthData;

	/**
	 * @var 	 array		Canais de marketplace disponíveis.
	 */
	private $conectalaSalesChannels;

	/**
	 * @var 	 array		Canais de marketplace disponíveis.
	 */
	private $mosaicoSalesChannels;

	/**
	 * @var		 array	 	De/Para dos valores suportados para tipo de conta.
	 */
	private static $ACCOUNT_TYPES = [
		"Conta Corrente" => "CC",
		"Conta Poupança" => "PP"
	];

	public function __construct()
	{
		parent::__construct();

		$logged_in_sess = [
			'id' 		=> 1,
			'username'  => 'batch',
			'email'     => 'batch@conectala.com.br',
			'usercomp' 	=> 1,
			'userstore' => 0,
			'logged_in' => TRUE
		];
		$this->session->set_userdata($logged_in_sess);

		$this->load->model('model_banks');
		$this->load->model('model_integrations');
		$this->load->model('model_mosaico_aggregate_merchant');
		$this->load->model('model_mosaico_sales_channel');
		$this->load->model('model_stores');
	}

	// php index.php BatchC/SellerCenter/Mosaico/Seller run null int_to
	function run($id = null, $params = null)
	{

		// Inicializa o job.
		$this->setIdJob($id);
		$log_name =  __CLASS__ . '/' . __FUNCTION__;
		$modulePath = str_replace("BatchC/", '', $this->router->directory) . __CLASS__;
		if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
			$this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
			return;
		}

		$this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params), "I");

		if (is_null($params)  || ($params == 'null')) {
			echo PHP_EOL . "É OBRIGATÓRIO passar o int_to no params" . PHP_EOL;
		} else {
			// Busca a integração do sellercenter.
			$integration = $this->model_integrations->getIntegrationbyStoreIdAndInto(0, $params);
			if ($integration) {
				$this->int_to = $integration['int_to'];
				$this->useHybridPaymentOption = FALSE;
				$this->int_from = 'HUB';
				$this->mainIntegration = $integration;
				$this->mainAuthData = json_decode($integration['auth_data']);
				echo 'Iniciando sincronização com marketplace: ' . $integration['int_to'] . "\n";

				// Tratamento do aggregate merchant.
				$this->createMissingAggregate();
				$this->getLocalAggregateMerchant();
				$this->getMosaicoAggregateMerchant();

				// Tratamento dos dados de banco.
				$this->getLocalBanks();
				$this->getAvailableBanks();

				// Tratamento de tipos de conta.
				$this->getAvailableAccountTypes();

				// Tratamento dos sales_channels.
				$this->getLocalSalesChannels();
				$this->getAvailableContexts();

				// Cria e atualiza os sellers.
				$this->createSeller();
				$this->updateSeller();
			} else {
				echo PHP_EOL . "int_to $params não tem integração definida" . PHP_EOL;
			}
		}

		// Encerra o job.
		$this->log_data('batch', $log_name, 'finish', "I");
		$this->gravaFimJob();
	}

	/**
	 * Cria os bancos faltantes na Conecta.
	 * Realiza o de-para, caso um banco da Mosaico possua um COMPE não definido aqui, cria o banco.
	 * 
	 * @return	 void		Não retorna nada, apenas cadastra os bancos faltantes.
	 */
	public function createMissingBanks()
	{
		// Pega os códigos aqui da Conecta.
		$conectaCompe = array_column($this->conectaBanks, 'code');

		// Filtra todos os bancos existentes na Mosaico e não existentes aqui.
		$missingBanks = array_filter($this->mosaicoBanks, function ($bank) use ($conectaCompe) {
			return !in_array($bank['code'], $conectaCompe, true);
		});

		// Percorre cada banco faltante.
		foreach ($missingBanks as $bank) {
			// Cria os bancos faltantes.
			$result = $this->model_banks->create([
				"name" => $bank['name'],
				"number" => $bank['code']
			]);

			if (!$result) {
				echo "Não foi possível criar o banco {$bank['name']} com código COMPE {$bank['code']}\n";
				continue;
			}

			// Adiciona no array de bancos da Conecta, visto que já foram cadastrados.
			echo "Banco {$bank['name']} criado com código COMPE {$bank['code']}\n";
			$this->conectaBanks[] = $bank;
		}
	}

	/**
	 * Cadastra na Mosaico os aggregate merchants faltantes.
	 */
	public function createMissingAggregate()
	{
		$log_name = __CLASS__ . '/' . __FUNCTION__;
		$endPoint = "sellers/aggregates";

		// Pega os códigos aqui da Conecta.
		$missingAggregates = $this->model_mosaico_aggregate_merchant->getAllNonRegistered();
		foreach ($missingAggregates as $missing) {
			$payload = ["name" => $missing["name"]];
			$this->processNew($this->mainAuthData, $endPoint, "POST", $payload);
			if ($this->responseCode != 201) {
				$err = "Erro na chamada ao endpoint $endPoint, com status $this->responseCode - Response: " . print_r($this->result, true);
				echo $err . "\n";
				$this->log_data('batch', $log_name, $err, "E");
				continue;
			}

			$result = json_decode($this->result, true);
			if (isset($result['aggregate_uuid'])) {
				$newData = ["aggregate_merchant" => $result['aggregate_uuid']];
				$this->model_mosaico_aggregate_merchant->update($newData, $missing["id"]);
			} else {
				echo "Aggregate_uuid não foi retornado.\n";
			}
		}
	}

	/**
	 * Cria os sales channels faltantes na Conecta.
	 * Realiza o de-para, caso um sales channel da Mosaico não esteja definido, cria-o.
	 * 
	 * @return	 void 		Não retorna nada, apenas cadastra os sales channels faltantes.
	 */
	public function createMissingChannels()
	{
		// Busca os sales channels cadastrados.
		$conectaRegistered = array_column($this->conectalaSalesChannels, "mosaico_id");

		// Filtra os sales channels faltantes.
		$missingSalesChannels = array_filter($this->mosaicoSalesChannels, function ($sc) use ($conectaRegistered) {
			return !in_array($sc['mosaico_id'], $conectaRegistered);
		});

		// Realiza a inserção dos faltantes no banco.
		foreach ($missingSalesChannels as $sc) {
			$createdSc = $this->model_mosaico_sales_channel->create($sc);
			if (!$createdSc) {
				echo "\nNão foi possível criar o sales_channel {$sc['mosaico_value']}\n";
				continue;
			}
		}
	}

	/**
	 * Realiza a criação do seller na Mosaico.
	 */
	public function createSeller()
	{
		$log_name = __CLASS__ . '/' . __FUNCTION__;

		$main_integration = $this->mainIntegration;
		echo "Verificando novas lojas para {$main_integration['int_to']} \n";

		// Realiza a tentativa de criar cada loja ativa.
		$stores = $this->model_stores->getAllActiveStore();
		foreach ($stores as $store) {

			echo PHP_EOL . str_repeat('-', 100) . PHP_EOL;

			// A principio, não haverá migração na Mosaico.
			if ($store['type_store'] == 2) {
				echo "Pulando a loja " . $store['id'] . " pois a mesma é a loja CD de uma empresa Multi-CD\n";
				continue;
			}

			// Busca integração da loja.
			$integration =  $this->model_integrations->getIntegrationbyStoreIdAndInto($store['id'], $main_integration['int_to']);
			if ($integration) {
				continue;
			}

			echo "\nCriando integração no marketplace {$main_integration['int_to']} para a loja {$store['id']} - {$store['name']}\n\n";

			$data = null;
			try {
				$data = $this->getFormattedBody($store);
			} catch (Exception $e) {
				echo $e->getMessage() . "\n";
				continue;
			}

			$bodyParams    = json_encode($data);
			$endPoint      = 'sellers/';
			$this->processNew($this->mainAuthData, $endPoint, 'POST', $bodyParams);

			echo "Retorno da Mosaico:\n";
			var_dump($this->result);

			if ($this->responseCode != 201) {
				$erro = "Erro Endpoint " . $endPoint . " httpcode=" . $this->responseCode . " RESPOSTA " . print_r($this->result, true) . " ENVIADO " . print_r($bodyParams, true);
				echo $erro . "\n";
				$this->log_data('batch', $log_name, $erro, "E");
				continue;
			}

			$data_int = [
				'name' 			=> $main_integration['name'],
				'active' 		=> $main_integration['active'],
				'store_id' 		=> $store['id'],
				'company_id' 	=> $store['company_id'],
				'auth_data' 	=> json_encode(['date_integrate' => $store['date_update'], 'seller_id' => $result_decode['id']]),
				'int_type' 		=> 'BLING',
				'int_from' 		=> is_null($this->int_from) ? $main_integration['int_from'] : $this->int_from,
				'int_to' 		=> $main_integration['int_to'],
				'auto_approve' 	=> $main_integration['auto_approve']
			];
			$this->model_integrations->create($data_int);
		}
	}

	public function updateSeller()
	{
		$main_integration = $this->mainIntegration;
		$integrations =  $this->model_integrations->getAllIntegrationsbyType('BLING');
		foreach ($integrations as $integration) {
			if ($integration['int_to'] !== $main_integration['int_to']) {
				continue;
			}
			$store = $this->model_stores->getStoresData($integration['store_id']);

			$separateIntegrationData = json_decode($integration['auth_data']);
			if ($store['date_update'] > $separateIntegrationData->date_integrate) {

				echo 'Alterando no marketplace ' . $main_integration['int_to'] . ' a loja ' . $store['id'] . ' ' . $store['name'] . "\n";
				$auth_data = json_decode($integration['auth_data']);
				$sellerId = $auth_data->seller_id;

				$data = null;
				try {
					$data = $this->getFormattedBody($store);
				} catch (Exception $e) {
					echo $e->getMessage() . "\n";
					continue;
				}
			}
		}
	}

	/**
	 * Faz o De/Para do account type recebido da Mosaico.
	 * 
	 * @param	 mixed		$accountType Conta salva na Conecta.
	 * @throws	 Exception 	Joga uma exception caso o accountType não esteja mapeado na Conecta ou presente na Mosaico.
	 * 
	 * @return	 int		Retorna a chave da conta na Mosaico.
	 */
	public function getAccountTypeId($accountType)
	{
		$desiredAccount = SELF::$ACCOUNT_TYPES[$accountType];
		if (!$desiredAccount) {
			throw new Exception("O tipo de conta $accountType não está mapeado.");
		}

		$mosaicoAccount = array_filter($this->mosaicoAccountTypes, function ($account) use ($desiredAccount) {
			return $account == $desiredAccount;
		});

		// Não deveria acontecer.
		if (!$mosaicoAccount) {
			throw new Exception("Não foi possível achar a chave na Mosaico.");
		}

		return key($mosaicoAccount);
	}

	/**
	 * Busca o aggregate merchant baseado no ID do banco.
	 * 
	 * @param	 mixed	$id Id do aggregate cadastrado na Conecta.
	 * @throws	 Exception Joga uma exception caso não encontre o aggregate merchant no banco.
	 */
	public function getAggregateByIdConecta($id)
	{
		$aggregate = $this->model_mosaico_aggregate_merchant->getById($id);
		if (!$aggregate) {
			throw new Exception("Não foi possível recuperar o aggregate merchant para esta loja.\n");
		}

		return $aggregate['aggregate_merchant'];
	}

	/**
	 * Busca todos os tipos de conta disponíveis na Mosaico.
	 * 
	 * @return	 void 		Não retorna nada, apenas seta a propriedade mosaicoAccountTypes.
	 */
	public function getAvailableAccountTypes()
	{
		$log_name = __CLASS__  . '/' . __FUNCTION__;

		$main_integration = $this->mainIntegration;
		echo "\nBuscando os tipos de contas na integração {$main_integration['int_to']}\n";

		// Realiza a query para buscar as opções de banco na Mosaico.
		$endPoint = "sellers/banks/types";
		$this->processNew($this->mainAuthData, $endPoint);
		if ($this->responseCode != 200) {
			$err = "Erro na chamada ao endpoint $endPoint, com status $this->responseCode - Response: " . print_r($this->result, true);
			echo $err . "\n";
			$this->log_data('batch', $log_name, $err, "E");
			$this->gravaFimJob();
			die;
		}

		// Percorre cada banco e realiza a formatação dos dados.
		$accountTypes = json_decode($this->result, true);

		$this->mosaicoAccountTypes = $accountTypes;

		echo "Tipos de conta sincronizados com sucesso.\n";
	}

	/**
	 * Busca todos os bancos disponíveis na Mosaico.
	 * Realiza a formatação da string para apresentar o código COMPE formatado.
	 * O envio por parte deles é através de string composta pelo código e nome.
	 * 
	 * @return	 void 		Não retorna nada, apenas seta a propriedade mosaicoBanks.
	 */
	public function getAvailableBanks()
	{
		$log_name = __CLASS__ . '/' . __FUNCTION__;

		$main_integration = $this->mainIntegration;
		echo "\nBuscando todas opções de bancos na integração {$main_integration['int_to']}\n";

		// Realiza a query para buscar as opções de banco na Mosaico.
		$endPoint = "sellers/banks/codes";
		$this->processNew($this->mainAuthData, $endPoint);
		if ($this->responseCode != 200) {
			$err = "Erro na chamada ao endpoint $endPoint, com status $this->responseCode - Response: " . print_r($this->result, true);
			echo $err . "\n";
			$this->log_data('batch', $log_name, $err, "E");
			// Não vamos conseguir pegar os novos, tenta criar com os já salvos no local.
			return;
		}

		// Percorre cada banco e realiza a formatação dos dados.
		$banks = json_decode($this->result, true);
		$formatedBanks = [];
		foreach ($banks as $key => $bank) {
			// Quebra o banco no -.
			list($code, $name) = explode(' - ', $bank, 2);

			// Adiciona padding para o código COMPE ter sempre 3 digitos.
			$code = str_pad($code, 3, '0', STR_PAD_LEFT);

			// Salva no novo array, agora formatado.
			$formatedBanks[$key] = [
				'code' => $code,
				'name' => $name
			];
		}

		// Seta os bancos da Mosaico e verifica se é necessário a criação do nosso lado.
		$this->mosaicoBanks = $formatedBanks;
		$this->createMissingBanks();

		echo "Bancos sincronizados com sucesso.\n";
	}

	/**
	 * Busca da Mosaico todos os sales channels disponíveis.
	 * 
	 * @return	 void 		Não retorna nada, apenas seta a propriedade mosaicoBanks.
	 */
	public function getAvailableContexts()
	{
		$log_name = __CLASS__ . '/' . __FUNCTION__;

		$main_integration = $this->mainIntegration;
		echo "\nBuscando todas opções de sales channel na integração {$main_integration['int_to']}\n";

		// Realiza a query para buscar as opções de sales channels na Mosaico.
		$endPoint = "sellers/campaigns/context";
		$this->processNew($this->mainAuthData, $endPoint);
		if ($this->responseCode != 200) {
			$err = "Erro na chamada ao endpoint $endPoint, com status $this->responseCode - Response: " . print_r($this->result, true);
			echo $err . "\n";
			$this->log_data('batch', $log_name, $err, "E");
			$this->gravaFimJob();
			// Não vamos conseguir pegar os novos, tenta criar com os já salvos no local.
			return;
		}

		// Percorre cada sales channel e realiza a formatação dos dados.
		$salesChannels = json_decode($this->result, true);
		$formatedSallesChannels = [];
		foreach ($salesChannels as $key => $channel) {
			// Salva no novo array, agora formatado.
			$formatedSallesChannels[] = [
				'mosaico_id' => $key,
				'mosaico_value' => $channel
			];
		}

		// Seta os sales channels da Mosaico e verifica se é necessário a criação do nosso lado.
		$this->mosaicoSalesChannels = $formatedSallesChannels;
		$this->createMissingChannels();

		echo "Sales channels sincronizados com sucesso.\n";
	}

	/**
	 * @param	 array		$store Dados da loja.
	 *
	 * @return	 array		Body da loja formatado para envio.
	 */
	private function getFormattedBody($store)
	{
		// Verifica se todos campos necessários de 
		$missingFields = [];
		$obrigatoryFields = ["account", "account_type", "agency", "bank", "responsable_birth_date", "service_charge_value"];
		foreach ($obrigatoryFields as $storeKey) {
			if (!$store[$storeKey]) {
				$missingFields[] = $storeKey;
			}
		}

		// Campos faltantes, apenas não cadastra.
		if (count($missingFields) > 0) {
			$faltantes = implode(", ", $missingFields);
			echo "\nLoja {$store['name']}(ID:{$store['id']}) não apresenta todos campos necessários, não será enviada.\n";
			throw new Exception("Campos faltantes: $faltantes");
		}

		// Busca dados para envio da Mosaico.
		$accountType = null;
		$aggregate = null;
		$bankId = null;
		$sc = null;
		try {
			$accountType = $this->getAccountTypeId($store['account_type']);
			$aggregate = $this->getAggregateByIdConecta($store['aggregate_id']);
			$bankId = $this->getMosaicoCodeByName($store['bank']);
			$sc = $this->getSalesChannelByStoreId($store['id']);
		} catch (Exception $e) {
			throw new Exception("Não foi possível realizar o de-para de dados para a Mosaico: {$e->getMessage()}");
		}

		// Monta o endereço comercial.
		$commercial_address = "{$store['business_street']}, {$store['business_addr_num']} - {$store['business_town']},{$store['business_uf']}";

		// Monta o endereço de entrega.
		$address = "{$store['address']} ,{$store['addr_num']}";

		// Parsing do telefone.
		// Remove DDI caso tenha, pega 2 primeiros números como DDD, resto como telefone.
		// Pode ser problemático caso por algum motivo salvem DDD com 3 digitos. Ex: (011) ao invés de (11) PO solicitou assim.
		$fullPhone = str_replace("+55", "", $store["responsible_sac_tell"]);
		$onlyNumPhone = preg_replace('/[^0-9\-]/', '', $fullPhone);
		$ddd = substr($onlyNumPhone, 0, 2);
		$phone = substr($onlyNumPhone, 2);

		$sc = array_map('intval', array_column($sc, 'mosaico_id'));

		$data = [
			"corporate_name" => $store['raz_social'],
			"commercial_name" => $store['name'],
			"corporate_document" => $store["CNPJ"],
			"corporate_document_creation_date" => null, // Não temos
			"telephone" => $store["phone_1"],
			"country" => $store["country"],
			"region" => $store["addr_uf"],
			"city" => $store["addr_city"],
			"address" => $address,
			"complement" => $store["addr_compl"],
			"collect_zip_code" => $store["zipcode"],
			"aggregate" => $aggregate,
			"contact_name" => $store["responsible_sac_name"],
			"contact_email" => $store["responsible_sac_email"],
			"contact_ddi" => "55", // Não temos separado, alinhado com PO envio hard coded.
			"contact_ddd" => $ddd,
			"contact_phone" =>  $phone,
			"commercial_address" => $commercial_address,
			"bank_code_id" => $bankId,
			"bank_agency" => $store["agency"],
			"bank_account_type_id" => $accountType,
			"bank_account" => $store["account"],
			"birth_date" => $store["responsable_birth_date"],
			"state_registration" => $store["inscricao_estadual"],
			"municipal_registration" => $store["inscricao_municipal"], // Não temos.
			"website_url" => $store["website_url"],
			"campaign_context_ids" => $sc,
			"commission" => $store["service_charge_value"] / 100,
			"audit_user" => $store["audit_user"],
		];

		return $data;
	}

	/**
	 * Busca todos os aggregate merchants cadastrados na Conecta.
	 * Seta eles na propriedade 'conectaAggregate'.
	 */
	public function getLocalAggregateMerchant()
	{
		$local = $this->model_mosaico_aggregate_merchant->getAll();
		$this->conectaAggregate = $local;
	}

	/**
	 * Busca todos os bancos disponíveis na Conecta.
	 * Realiza a formatação do array para ser igual ao modelo da propriedade mosaicoBanks.
	 * 
	 * @return	 void 		Não retorna nada, apenas seta a propriedade conectaBanks.
	 */
	public function getLocalBanks()
	{
		echo "\nBuscando os bancos disponíveis na Conecta.\n";
		// Busca todos os bancos cadastrados.
		$banks = $this->getBanks();

		// Mapeia cada entrada de banco da Conecta, retornando apenas o código COMPE e o nome.
		$formatedBanks = array_map(function ($bank) {
			return [
				"code" => $bank["number"],
				"name" => $bank["name"],
			];
		}, $banks);

		$this->conectaBanks = $formatedBanks;

		echo "Bancos encontrados na Conecta.\n";
	}

	/**
	 * Busca os sales channels salvos no local e seta na propriedade conectalaSalesChannels.
	 * @return	 void 		Não retorna nada, apenas seta a propriedade mosaicoBanks.
	 */
	public function getLocalSalesChannels()
	{
		$sales_channels = $this->model_mosaico_sales_channel->getAll();
		$this->conectalaSalesChannels = $sales_channels;
	}

	/**
	 * Busca os aggregates merchant's na Mosaico.
	 * Responsável por salvar todos em memória.
	 * 
	 * @return	 void		Apenas salva os valores em memória.
	 */
	public function getMosaicoAggregateMerchant()
	{
		$log_name = __CLASS__ . '/' . __FUNCTION__;

		$main_integration = $this->mainIntegration;
		echo "\nBuscando todos os aggregate merchants na {$main_integration['int_to']}\n";

		// Realiza a query para buscar as opções de banco na Mosaico.
		$endPoint = "sellers/aggregates";
		$this->processNew($this->mainAuthData, $endPoint);
		if ($this->responseCode != 200) {
			$err = "Erro na chamada ao endpoint $endPoint, com status $this->responseCode - Response: " . print_r($this->result, true);
			echo $err . "\n";
			$this->log_data('batch', $log_name, $err, "E");
			// Não vamos conseguir pegar os novos, tenta criar com os já salvos no local.
			return;
		}

		// Percorre cada banco e realiza a formatação dos dados.
		$aggregates = json_decode($this->result, true);
		$this->mosaicoAggregate = $aggregates;

		$this->pullMissingAggregate();
		echo "Aggregate merchants sincronizado com sucessos.\n";
	}

	/**
	 * Realiza o de-para entre os bancos na conecta e na Mosaico.
	 * No cadastro da loja não temos o código COMPE, apenas o nome.
	 * É necessário dar match do nome do banco (Loja) com os bancos cadastrados.
	 * Com isto, buscar o COMPE na Conecta e assim acessar o ID na Mosaico.
	 * 
	 * @param	 string		$bankName Nome do banco no cadastro da loja.
	 */
	public function getMosaicoCodeByName($bankName)
	{
		// Verifica os bancos salvos na conecta pelo nome.
		$bankConecta = [];
		foreach ($this->conectaBanks as $bank) {
			// Se tiver encontrado o banco de mesmo nome, pega o COMPE.
			if (isset($bank['name']) && $bank['name'] === $bankName) {
				$bankConecta = $bank;
				break;
			}
		}

		// Não encontrou o banco pelo nome.
		if (empty($bankConecta)) {
			throw new Exception("O banco de nome $bankName não foi encontrado na tabela de bancos.");
		}

		// Pega o banco da Mosaico com mesmo código COMPE.
		$bankMosaico = array_filter($this->mosaicoBanks, function ($bank) use ($bankConecta) {
			return trim($bankConecta["code"]) == trim($bank["code"]);
		});

		// Nenhum banco encontrado lá na Mosaico.
		if (!$bankMosaico) {
			throw new Exception("O banco de nome $bankName e COMPE {$bankConecta['code']} não foi encontrado na Mosaico.");
		}

		// Retorna o primeiro banco da lista.
		// Não deveria haver qualquer tipo de duplicata.
		return key($bankMosaico);
	}

	/**
	 * Busca o salles channel baseado no ID da loja.
	 * 
	 * @param	 mixed		$id Id da loja.
	 * @throws	 Exception 	Joga uma exception caso não encontre nenhum sales channel disponível.
	 * 
	 * @return	 array		Retorna um array contendo o ID dos sales channels na Mosaico.
	 */
	public function getSalesChannelByStoreId($storeId)
	{
		$sc = $this->model_mosaico_sales_channel->getStoreSalesChannelsMscId($storeId);
		if (!$sc || count($sc) == 0) {
			throw new Exception("Não foi possível recuperar o sales channel para esta loja.");
		}

		return $sc;
	}

	/**
	 * Cria as entradas de aggregate merchant não disponíveis na Conecta.
	 */
	public function pullMissingAggregate()
	{
		// Pega os códigos aqui da Conecta.
		$conectaAggregateName = array_column($this->conectaAggregate, 'name');

		// Filtra todos os aggregates existentes na Mosaico e não existentes aqui.
		$missingAggregates = array_filter($this->mosaicoAggregate, function ($aggr) use ($conectaAggregateName) {
			return !in_array($this->mosaicoAggregate[$aggr], $conectaAggregateName, true);
		}, ARRAY_FILTER_USE_KEY);

		// Caso todos já tenham sido cadastrados.
		if (count($missingAggregates) == 0) {
			echo "\nNenhum aggregate merchant para ser inserido.\n";
		}

		// Percorre cada aggregate faltante.
		foreach ($missingAggregates as $aggregate => $name) {
			// Cria os aggregates faltantes.
			$result = $this->model_mosaico_aggregate_merchant->create([
				"name" => $name,
				"aggregate_merchant" => $aggregate
			]);

			if (!$result) {
				echo "Não foi possível criar o aggregate da loja $name com aggregate $aggregate\n";
				continue;
			}

			echo "Aggregate $aggregate criado com nome $name\n";
		}

		echo "\nAggregate merchants inseridos com sucesso.\n";
	}
}
