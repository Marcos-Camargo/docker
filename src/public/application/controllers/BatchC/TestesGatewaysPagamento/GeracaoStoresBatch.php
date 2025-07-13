<?php
require APPPATH . "controllers/BatchC/GenericBatch.php";

/**
 * Classe responsável pela geração de dados para efetuar testes nos Gateway de Pagamento
 * Class GeracaoStoresBatch
 */
class GeracaoStoresBatch extends GenericBatch
{

    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );

        $this->session->set_userdata($logged_in_sess);

        //Models
        $this->load->model('model_gateway');
        $this->load->model('model_transfer');
        $this->load->model('model_banks');
        $this->load->model('model_conciliation');
        $this->load->model('model_stores');
        $this->load->model('model_company');

    }

    public function run(): void
    {

        $this->startJob(__FUNCTION__);

        //Buscando as transferências para saber quais
        $transfers_array = $this->model_transfer->getAll();

        $storesNotFound = 0;

        foreach ($transfers_array as $transfer) {

            //Verificando se já temos uma store cadastrada com essa id
            $store = $this->model_stores->getStoresById($transfer['store_id']);

            //Não tem, vamos cadastrar com dados gerados
            if (!$store) {

                $storesNotFound++;

                echo "Loja {$transfer['store_id']} {$transfer['name']} - Não cadastrada ainda";

                $company = $this->generateFakeCompanyData($transfer['name'], $storesNotFound);

                $companyId = $this->model_company->create($company);

                $store = $this->generateFakeStoreData($transfer['store_id'], $company, $companyId, $transfer['name'], $storesNotFound);

                $storeId = $this->model_stores->create($store);

                echo " - Cadastrado na ID: $storeId" . PHP_EOL;

            }

        }

        $this->endJob();

    }

    private function generateFakeCompanyData(string $name, int $storesNotFound): array
    {
        return [
            'name' => $name,
            'address' => "Address de $name",
            'addr_num' => '999',
            'addr_compl' => 'complemento teste',
            'addr_neigh' => 'bairro teste',
            'addr_city' => 'Braço do Norte',
            'addr_uf' => 'SC',
            'zipcode' => '88750000',
            'phone_1' => '48912345678',
            'phone_2' => '',
            'country' => 'BR',
            'logo' => "",
            'reputacao' => 70,
            'parent_id' => 1,
            'prefix' => 'TESTE',
            'CNPJ' => '22.968.230/0001-81',
            'email' => "$storesNotFound@a.com",
            'bank' => 'Itaú',
            'agency' => '1234',
            'account_type' => 'Conta Corrente',
            'account' => '9999-' . $storesNotFound,
            'responsible_sac_name' => '',
            'responsible_sac_email' => '',
            'currency' => 'BRL',
            'message' => "message",
        ];

    }

    private function generateFakeStoreData(int $id, array $company, int $companyId, string $name, int $storesNotFound): array
    {

        return [
            'id' => $id,
            'name' => $name,
            'company_id' => $companyId,
            'active' => 1,
            'address' => $company['address'],
            'addr_num' => $company['addr_num'],
            'addr_compl' => $company['addr_compl'],
            'addr_neigh' => $company['addr_neigh'],
            'addr_city' => $company['addr_city'],
            'addr_uf' => $company['addr_uf'],
            'zipcode' => $company['zipcode'],
            'phone_1' => $company['phone_1'],
            'phone_2' => $company['phone_2'],
            'country' => $company['country'],
            'raz_social' => $name,
            'prefix' => $company['prefix'],
            'CNPJ' => $company['CNPJ'],
            'bank' => $company['bank'],
            'agency' => $company['agency'],
            'account_type' => $company['account_type'],
            'account' => $company['account'],
            'responsible_name' => 'Responsável',
            'responsible_email' => $company['email'],
            'responsible_cpf' => $company['CNPJ'],
            'business_street' => $company['address'],
            'business_addr_num' => $company['addr_num'],
            'business_addr_compl' => $company['addr_compl'],
            'business_neighborhood' => $company['addr_neigh'],
            'business_town' => $company['addr_city'],
            'business_uf' => $company['addr_uf'],
            'business_nation' => $company['country'],
            'business_code' => $company['zipcode'],
            'token_api' => uniqid(),
        ];

    }

}
