<?php
defined('BASEPATH') or exit('No direct script access allowed');
ini_set("memory_limit", "1024M");

class ReportsPagarme extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->data['page_title'] = 'Parâmetros de Boletos';

        $this->load->model('model_payment');
        $this->load->model('model_billet');
        $this->load->model('model_iugu');
        $this->load->model('model_repasse');
        $this->load->model('model_settings');
        $this->load->model('model_stores');
        $this->load->model('model_legal_panel');
        $this->load->model('model_banks');
        $this->load->model('model_payment_gateway_store_logs');
        $this->load->model('model_gateway');
        $this->load->model('model_gateway_settings');
        $this->load->model('model_orders');
        $this->load->model('model_order_payment_transactions');
        $this->load->model('model_users');

        //Libraries
        $this->load->library('PagarmeLibrary');

        //Starting Pagar.me integration library
        $this->integration = new PagarmeLibrary();

        $this->gateway_id = $this->model_settings->getValueIfAtiveByName('payment_gateway_id');
        $this->balance_transfers_valid_updated_minutes = intVal($this->model_settings->getValueIfAtiveByName('balance_transfers_valid_updated_minutes'));
        $this->allow_transfer_between_accounts = intVal($this->model_settings->getValueIfAtiveByName('allow_transfer_between_accounts'));

        $api_settings = $this->model_gateway_settings->getSettings($this->gateway_id);

        if (!empty($api_settings) && is_array($api_settings)) {
            foreach ($api_settings as $key => $setting) {
                $this->{$setting['name']} = $setting['value'];
            }
        }

    }

    public function index()
    {

        $this->pagarme_subaccounts_api_version = 1;

        get_instance()->load->model('model_settings');
        $sellercenter = get_instance()->model_settings->getValueIfAtiveByName('sellercenter');

        $items = \App\Libraries\Cache\CacheManager::get("$sellercenter:reportsPagarme3");

        if ($items) {
            $items = json_decode($items, true);
        }else{

            $page = 1;
            $hasItems = true;
            $items = [];

            do {

                $url = $this->integration->getUrlAPI_V1() . '/transfers?count=1000&page='.$page;
                $response = $this->integration->getRequest($url);
                if ($response['content'] && $content = json_decode($response['content'], true)){

                    $page++;

                    foreach ($content as $item){
                        if (in_array($item['type'], ['ted', 'credito_em_conta'])){
                            $items[] = $item;
                        }
                    }

                }else{
                    $hasItems = false;
                }

            }while($hasItems);

            $key = "$sellercenter:reportsPagarme3";
            \App\Libraries\Cache\CacheManager::setex($key, json_encode($items), 60 * 60 * 24 * 7);

        }

        $itemsWithoutRepasse = [];
        $itemsWithoutRepasseDarAtencao = [];
        $itemsWithoutRepasseDarAtencaoVerComAndre = [];
        $itemsRepasseEncontrados = [];
        $storesNotFound = [];
        $repetidos = [];
        $inAnotherSeller = []; //Já esta no 1º report
        $itensValorEspecifico = [];
        $itensLojaEspecifica = [];
        $verComAndre = [];
        $errosVerComAndre = [];
        $itemsWithoutRepasseDarAtencaoVerComAndreRevisadosEncontrados = [];
        $itemsWithoutRepasseDarAtencaoVerComAndreRevisadosNaoEncontrados = [];

        foreach ($items as $item){

            $sellerId = $item['source_id'];
            if ($sellerId == $this->primary_account){
                continue;
            }
            $store = $this->model_gateway->getStoreByGatewayId($sellerId);

            if (!$store){
                if (!isset($repetidos[$sellerId])){
                    $pagarmeData = $this->integration->getRequest($this->integration->getUrlAPI_V1()."/recipients/{$sellerId}");
                    $repetidos[$sellerId] = json_decode($pagarmeData['content'], true);
                }
                $item['pagarme_data'] = $repetidos[$sellerId];

                $idsVerified = [
                    're_cl9sxotne6ru5019tu1dix3oc', //epoca de migração (multilaser)
                    're_cl8pfu93476jq019ttastbfer', //epoca de migração (olist)
                ];

                if (!in_array($item['source_id'], $idsVerified)){
                    $storesNotFound[] = $item;
                }

                continue;
            }

            $valor_decimal = $item['amount'] / 100;
            $valor = rtrim(rtrim(number_format($valor_decimal, 2, '.', '')), '0');

            if ($valor == '1415.83'){
                $itensValorEspecifico[] = $item;
            }

            if ($item['source_id'] == 're_cl7rpwl6v8k3h019tmoq7k3r7'){
                $itensLojaEspecifica[] = $item;
            }

            $hasRepasse = $this->model_repasse->getByAmountAndStoreId($valor, $store[0]->store_id);
            $comTaxa = false;
            $encontradoDireto = false;
            if (!$hasRepasse){
                $hasRepasse = $this->model_repasse->getByAmountAndStoreId($valor+3.67, $store[0]->store_id);
                $comTaxa = true;
            }
            if (!$hasRepasse){
                $hasRepasse = $this->model_repasse->getByAmountAndStoreIdNormal($valor, $store[0]->store_id);
                $encontradoDireto = true;
            }
            if (!$hasRepasse){
                $anotherSeller = $this->model_repasse->getByAmountTotal($valor);
                $listAlreadyValidated = [
                    '171608212',
                    '171608211',
                    '171586729',
                    '171586725',
                    '171586721',
                    '171586714',
                    '171586705',
                    '171586703',
                    '171586695',
                    '123420803',
                    '123420101',
                    '158422067', //Revisado
                ];
                if ($anotherSeller && !in_array($item['id'], $listAlreadyValidated)){
                    $anotherSeller[0]['store_id_com_base_no_subaccount'] = $store[0]->store_id;
                    $anotherSeller[0]['item'] = $item;
                    $inAnotherSeller[] = $anotherSeller[0];
                }
            }

            if ($hasRepasse){
                $item['com_taxa_encontrado'] = $comTaxa;
                $item['encontrado_direto'] = $encontradoDireto;
                $itemsRepasseEncontrados[] = $item;
            }else{

                $itensValidated = [
                    '173487040', //Está pago agrupado
                    '171608212',
                    '171608211',
                    '171608209',
                    '171608158',
                    '171586729',
                    '171586725',
                    '171586721',
                    '171586714',
                    '171586705',
                    '171586703',
                    '171586695',
                    '122049545',
                    '105197299',
                    '150411611',
                    '140934912',
                    '122049547',
                    '114954940',
                    '114334897',
                    '105197300',
                    '122064618',
                    '114334340',
                    '140934936',
                    '140934938',
                    '140934942',
                    '123420803',
                    '122049573',
                    '105197321',
                    '106080481',
                    '140934948',
                    '105197328',
                    '140934955',
                    '140934959',
                    '122049588',
                    '106080242',
                    '122049624',
                    '105197414',
                    '105197416',
                    '140935009',
                    '106081813',
                    '140935011',
                    '106078996',
                    '105197426',
                    '117392983',
                    '106082247',
                    '140935018',
                    '122049633',
                    '141667308',
                    '105197434',
                    '140935027',
                    '106079562',
                    '140935029',
                    '140935034',
                    '106076486',
                    '140935035',
                    '122049640',
                    '105197446',
                    '122049641',
                    '105197448',
                    '106082069',
                    '140935040',
                    '140935042',
                    '140935044',
                    '114956110',
                    '114336454',
                    '106080374',
                    '106081315',
                    '140935048',
                    '140935050',
                    '123419364',
                    '122049642',
                    '115780099',
                    '105197460',
                    '106080841',
                    '140935052',
                    '122049645',
                    '106079235',
                    '132214071',
                    '106080649',
                    '114334132',
                    '106081139',
                    '106079735',
                    '140935093',
                    '140935103',
                    '140935104',
                    '125436997',
                    '122049652',
                    '140935111',
                ];

                //Já colocamos no report
                $darAtencao = [
                    '159434263', //Ja esta anotado para reportar
                    '159434262', //Ja esta anotado para reportar
                    '150852625', //Ja esta anotado para reportar
                    '150831243', //Ja esta anotado para reportar
                    '150426449', //revisado
                ];

                $verComAndre = [
                    '155182033',
                    '155142339',
                    '155122824', //Encontramos um pagamento no banco que deu sucesso, mas o log deu erro
                    '150517660',
                    '139264297',
                    '123420101',
                    '114954325', //não en.
                    '114336357', //nao en.
                    '115721875', //não en
                    '114334453', //não encontrado
                    '106079879', //ok
                    '140934921',
                    '122049551', //esta ok
                    '114953933', //não e.
                    '114336271',//não e.
                    '106079656', //ok
                    '115773239', //encontramos aproximado
                    '125407046',
                    '114955663', //não e
                    '114334538', //não encontrado
                    '131606109',
                    '115352965', //não en
                    '106080807', //ok
                    '115352965',
                    '136630215',
                    '114956767', //não en
                    '114334711', //não encontrado
                    '122979601', //não en.
                ];

                $verComAndreRevisadosEncontrados = [
                    '106079879',
                    '106079656',
                    '106080807',
                    '115773239',
                    '122049551',
                    '123420101',
                    '131606109',
                    '140934921',
                    '150517660', //valor muito baixo
                    '105197330', //está ok
                    '105197370', //Encontrado todas, loja 21
                    '122049593', //Encontrado todas, loja 21
                    '139268397', //Encontrado todas, loja 21
                    '105197438', //Encontrado
                    '122049634',
                    '125436459',
                    '106079206',//Não encontramos, mas por ser muito antigo deixaremos quieto
                    '122049638', //Encontrado
                    '133916820',
                    '131604521',
                    '114336571',
                    '106079978',
                ];

                $verComAndreRevisadosNaoEncontrados = [
                    '114954325',
                    '114336357',
                    '115721875',
                    '114334453',
                    '114953933',
                    '114336271',
                    '114955663',
                    '114334538',
                    '115352965',
                    '114956767',
                    '114334711',
                    '122979601',
                    '125407046',
                    '136630215',
                    '139264297',
                    '155142339',
                    '155182033',
                    '155122824', //Parece ter sido encontrado por fora da conecta
                ];

                $itensErrosVerComAndre = [

                ];

                $novoItem = [];
                $novoItem['id'] = $item['id'];
                $novoItem['source_id'] = $item['source_id'];
                $novoItem['store_id'] = $store[0]->store_id;
                $novoItem['amount'] = $item['amount'];
                $novoItem['amount_convertido'] = $valor;
                $novoItem['fee'] = $item['fee'];
                $novoItem['type'] = $item['type'];
                $novoItem['status'] = $item['status'];
                $novoItem['date_created'] = $item['date_created'];
                $novoItem['bank_response'] = $item['bank_response'];
                $novoItem['metadata'] = $item['metadata'];

                if (in_array($novoItem['id'], $darAtencao)){
                    $itemsWithoutRepasseDarAtencao[] = $novoItem;
                }

                if (in_array($novoItem['id'], $verComAndre)
                    && !in_array($novoItem['id'], $verComAndreRevisadosEncontrados)
                    && !in_array($novoItem['id'], $verComAndreRevisadosNaoEncontrados)){
                    $itemsWithoutRepasseDarAtencaoVerComAndre[] = $novoItem;
                }

                if (in_array($novoItem['id'], $verComAndreRevisadosEncontrados)){
                    $itemsWithoutRepasseDarAtencaoVerComAndreRevisadosEncontrados[] = $novoItem;
                }
                if (in_array($novoItem['id'], $verComAndreRevisadosNaoEncontrados)){
                    $itemsWithoutRepasseDarAtencaoVerComAndreRevisadosNaoEncontrados[] = $novoItem;
                }

                if (in_array($novoItem['id'], $itensErrosVerComAndre)){
                    $errosVerComAndre[] = $novoItem;
                }

                if (!in_array($novoItem['id'], $itensValidated) && !in_array($novoItem['id'], $darAtencao)
                    && !in_array($novoItem['id'], $verComAndre)  && !in_array($novoItem['id'], $itensErrosVerComAndre)){
                    $itemsWithoutRepasse[] = $novoItem;
                }
            }

        }

        d($itemsWithoutRepasseDarAtencaoVerComAndreRevisadosEncontrados, $itemsWithoutRepasseDarAtencaoVerComAndreRevisadosNaoEncontrados, $itensLojaEspecifica);
        ddd($storesNotFound, $itemsWithoutRepasse, $itemsWithoutRepasseDarAtencao, $itemsWithoutRepasseDarAtencaoVerComAndre, $errosVerComAndre, $itemsRepasseEncontrados, $inAnotherSeller, $itensValorEspecifico);

    }

}