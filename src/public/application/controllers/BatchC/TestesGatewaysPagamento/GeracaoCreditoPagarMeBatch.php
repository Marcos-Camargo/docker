<?php /** @noinspection PhpUnused */
/** @noinspection PhpUndefinedFieldInspection */
require APPPATH . "controllers/BatchC/GenericBatch.php";

/**
 * Classe responsável por gerar crédito em todas as subcontas conforme necessário para efetuar testes de repasse
 * Class GeracaoCreditoPagarMeBatch
 */
class GeracaoCreditoPagarMeBatch extends GenericBatch
{

    /**
     * @var PagarmeLibrary
     */
    public $integration;

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
        $this->load->model('model_repasse');
        $this->load->model('model_stores');

        //Libraries
        $this->load->library('Pagarmelibrary');

        //Starting Pagar.me integration library
        $this->integration = new PagarmeLibrary();

    }

    public function run(): void
    {

        $this->startJob(__FUNCTION__);

        $stores = $this->model_stores->getAllActiveStore();

        foreach ($stores as $store) {

            $originalAmount_to_transfer = $this->model_repasse->sumValorSellerByStoreId($store['id']);

            $receiver = $this->model_gateway->getSubAccountByStoreId($store['id']);

            if ($originalAmount_to_transfer > 0 && $receiver) {

                $receiver = $receiver['gateway_account_id'];

                //Sempre acrescentando 1 centavo para fazer sobrar 1 centavo no teste final
                $originalAmount_to_transfer += 0.01;

                //Sempre acrescentando R$3,50 da taxa do boleto
                $totalTransferAmount = $originalAmount_to_transfer + 3.50;

                if (!$this->integration->generateTestCreditToRecipientId($totalTransferAmount, $receiver)) {
                    exit("Não foi possível gerar crédito para o receiver $receiver no valor de $totalTransferAmount");
                }

                $balance = $this->integration->getBalance($receiver);

                echo "Gerado crédito de $originalAmount_to_transfer no recipient_id: $receiver - Saldo atual: {$balance->available->amount}";

                $valorEsperado = moneyToInt($originalAmount_to_transfer);
                $valorAtual = $balance->available->amount;

                if ($valorEsperado != $valorAtual) {
                    echo " - Crédito gerado incorretamente - Era para ter saldo de $valorEsperado e tem $valorAtual";
                } else {
                    echo " - OK ";
                }

                echo PHP_EOL;

            }

        }

        $this->endJob();

    }

    public function validateRemainingBalance(): void
    {

        $this->startJob(__FUNCTION__);

        $stores = $this->model_stores->getAllActiveStore();

        foreach ($stores as $store) {

            $total_to_transfer = $this->model_repasse->sumValorSellerByStoreId($store['id']);

            $receiver = $this->model_gateway->getSubAccountByStoreId($store['id'])['gateway_account_id'];

            if ($total_to_transfer > 0 && $receiver) {

                $balance = $this->integration->getBalance($receiver);

                echo "Validando conta $receiver, saldo restante: {$balance->available->amount}, valor que deveria transferir: $total_to_transfer - ";

                if ($balance->available->amount == 1) {
                    echo " OK ";
                } else {
                    echo " Incorreto: " . json_encode($balance);
                }

                echo PHP_EOL;

            }

        }

        $this->endJob();

    }

}
