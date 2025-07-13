<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . "libraries/GatewayPaymentLibrary.php";

class MoipLibrary extends GatewayPaymentLibrary
{
    protected $_CI;
    protected $log_name;
    protected $api_url;
    protected $app_id;
    protected $app_bank_id;
    protected $app_account;
    protected $app_token;
    protected $ymi_token;
    protected $ymi_url;

    protected $gateway_name;
    protected $gateway_id;

    private $result;
    private $responseCode;

    public function __construct()
	{
        $this->_CI = &get_instance();
        $this->_CI->load->model('model_moip');

        $this->_CI->load->model('model_transfer');
        $this->_CI->load->model('model_gateway');
        $this->_CI->load->model('model_gateway_transfers');
        $this->_CI->load->model('model_repasse');
        $this->_CI->load->model('model_conciliation');
        $this->_CI->load->model('model_legal_panel');

        $this->gateway_name     = Model_gateway::MOIP;
        $this->gateway_id       = $this->_CI->model_gateway->getGatewayId($this->gateway_name);

        $api_settings = $this->_CI->model_gateway_settings->getSettings($this->gateway_id);

        if (!empty($api_settings) && is_array($api_settings))
        {
            foreach ($api_settings as $key => $setting)
            {
                $this->{$setting['name']} = $setting['value'];
            }            
        }

    }

    public function processTransfers($conciliation_id = false, $transfer_type = 'BANK')
    {
        if (!$conciliation_id)
            return false;

        $transfer_error = 0;
        $conciliation_status = 23; // code for 100% success

        $transfers_array = $this->_CI->model_transfer->getTransfers($conciliation_id);

        if (!empty($transfers_array) && is_array($transfers_array))
        {

            $transfers_sum = $this->generateArraySumByTransfers($transfers_array);

            foreach ($transfers_sum as $transfer)
            {
                $sender = $this->app_account;
                $receiver = $this->_CI->model_moip->getStoreMoipData($transfer['store_id'])['moip_id'];

                if ($sender && $receiver)
                {
                    //cancellations
                    if ($transfer['cancel_value'] < 0)
                    {
                        $amount           = moneyToInt($transfer['cancel_value']);
                        $transfer['id']   = $transfer['cancel_id'];

                        if ($this->createTransfer($transfer, $receiver, $sender, $amount, 'MOIP'))
                        {
                            $this->_CI->model_repasse->updateTransferStatus(true, $transfer['id']);
                            $this->saveOrdersStatements($transfer, $amount);
                        }
                        else
                        {
                            $this->createLegalItem($transfer['store_id'], abs($amount));
                            $this->_CI->model_repasse->updateTransferStatus(false, $transfer['id']);
                            $transfer_error++;
                        }
                    }

                    //legal panel
                    if (!empty($transfer['legal_id']) && !empty($transfer['legal_value']))
                    {
                        foreach ($transfer['legal_id'] as $key => $val)
                        {
                            $amount               = moneyToInt($transfer['legal_value'][$key]);
                            $transfer['id']       = $val;

                            if ($this->createTransfer($transfer, $receiver, $this->app_account, $amount, 'MOIP'))
                            {
                                $this->_CI->model_repasse->updateTransferStatus(true, $transfer['id']);
                                $this->_CI->model_repasse->updateTransferLegal($transfer['store_id'], abs(round(($amount / 100), 2)));
                                $this->saveOrdersStatements($transfer, $amount);
                            }
                            else
                            {
                                $this->_CI->model_repasse->updateTransferStatus(false, $transfer['id']);
                                $transfer_error++;
                            }
                        }
                    }

                    //actual transfer with updated math
                    if ($transfer['orders_value'] < 0)
                    {
                        $this->_CI->model_repasse->updateTransferStatus(true, $transfer['orders_id']);
                    }
                    else
                    {
                        $amount          = moneyToInt($transfer['orders_value']);
                        $transfer['id']  = $transfer['orders_id'];

                        if ($this->createTransfer($transfer, $sender, $receiver, $amount, $transfer_type))
                        {
                            $this->_CI->model_repasse->updateTransferStatus(true, $transfer['id']);
                            $this->saveOrdersStatements($transfer, $amount);
                        }
                        else
                        {
                            $this->_CI->model_repasse->updateTransferStatus(false, $transfer['id']);
                            $transfer_error++;
                        }
                    }                    
                }
                else
                {
                    echo "Erro - transferencia nao possui Sender ou Receiver ou ambos (sender: ".$sender." || receiver: ".$receiver.")\r\n";
                    continue;
                }
            }

            if ($transfer_error > 0)
                $conciliation_status = 27; //code for processed with some errors

            $this->_CI->model_conciliation->updateConciliationStatus($conciliation_id, $conciliation_status);
        }
    } 

    public function gatewayUpdateBalance()
    {
        return $this->runBatch('MoipBatch', 'gatewayUpdateBalance');
    }


    public function runPayments()
    {
        return $this->runBatch('MoipBatch', 'runPayments');
    }


    /**
     * ------------------------------------------------------
     * Conjunto de métodos que tratam os Repasses entre contas e bancos
     * ------------------------------------------------------
     */
    
    public function createTransfer($transfer, $sender = null, $receiver = null, $amount = null, $transfer_type = 'BANK')
    {
        if (empty($sender) || empty($receiver) || empty($amount))
            return false;

        $bank_moip_id   = null;
        $function_id    = $this->_CI->model_moip->getFunctionId('transfers');

        if ($transfer_type == 'BANK')
        {
            if ($receiver == $this->app_account)
            {
                $this->app_token = $this->app_token;
                $bank_moip_id = $this->app_bank_id;
            }
            else
            {
                $this->app_token = $this->_CI->model_moip->getSubaccountToken(array('moip_id' => $receiver));             
                $bank_moip_id = $this->_CI->model_moip->getBankId($receiver);
            }

            $transfer_array = array("id" => $bank_moip_id);
        }
        else if ($transfer_type == 'MOIP')
        {
            if ($sender != $this->app_account)            
                $this->app_token = $this->_CI->model_moip->getSubaccountToken(array('moip_id' => $sender));

            $transfer_array = array("id" => $receiver);
        }
        else
        {
            return false;
        }


        //braun hack -> mantive para testes mas deturpei para poder testar com valores em ambiente de teste
        if (ENVIRONMENT === 'development')
        {
            if ($amount < 0)
            {
                return "retornando R$ ".$amount." da conta do seller para o mktplace";
            }
            else
            {
                return 'TRA-123123';
            }
        }

        $amount = abs($amount);

        $gateway_id = $this->_CI->model_moip->getGatewayId('moip');

        $transferId = $this->_CI->model_gateway_transfers->createPreTransfer($transfer['id'], $gateway_id, $receiver, $amount, ($transfer_type != 'BANK') ? 'WALLET' : 'BANK', $sender);

        $own_id_prefix =  (array_key_exists('pendency', $transfer)) ? 'pendency-' : '';

        $transfer_deposit_array = array
        (
            "ownId" => $transferId.'-'.$own_id_prefix.$transfer['id'],
            "amount" => $amount,
            "transferInstrument" => array(
                "method" => $transfer_type."_ACCOUNT", 
                strtolower($transfer_type)."Account" => $transfer_array
            )
        );

        if ($amount <= 200 && $transfer_type == 'BANK')
        {
            $transfer_data = array
            (
                'status' => 'ERROR',
                'result_status' => 0,
                'result_message' => 'Transferência menor que R$ 2,00 - valor solicitado: '.intToMoney($amount, 'R$ ')
            );

            $this->_CI->model_gateway_transfers->saveTransfer($transferId, $transfer_data);
            return false;
        }
        
        $url = $this->api_url.'/transfers';
        $url = str_replace(':/', '://',str_replace('//', '/', $url));

        $transfer_deposit_json = json_encode($transfer_deposit_array, JSON_UNESCAPED_UNICODE);

        $transfer_response_json = $this->moipTransactions($url, 'POST', $transfer_deposit_json, __FUNCTION__);
        $transfer_response_array = json_decode($transfer_response_json, true);

        if (substr($this->responseCode, 0, 1) == 2)
        {
            $transfer_data = [
                'transfer_type' => ($transfer_type != 'BANK') ? 'WALLET' : 'BANK',
                'sender_id' => $sender,
                'fee' => 0,
                'amount' => $transfer_response_array['amount'],
                'transfer_gateway_id' => $transfer_response_array['id'],
                'status' => $transfer_response_array['status'],
                'result_status' => 1,
                'result_number' => $this->responseCode,
                'funding_estimated_date' => datetimeNoGMT($transfer_response_array['entries'][0]['scheduledFor']),
            ];

            $transfer_saved = $this->_CI->model_gateway_transfers->saveTransfer($transferId, $transfer_data);

            if ($transfer_saved)
            {
                if (!empty($transfer_response_array['events']) && is_array($transfer_response_array['events']))
                {
                    foreach ($transfer_response_array['events'] as $event)
                    {
                        $event_data = array
                        (
                            'tbl_function'          => $function_id,
                            'function_moip_tbl_id'  => $transferId,
                            'type'                  => $event['type'],
                            'created_at'            => date('Y-m-d H:i:s', strtotime($event['createdAt'])),
                            'description'           => $event['description']
                        );

                        if ($this->_CI->model_moip->saveFunctionEvent($event_data))
                            echo "Transferencia ".$event['type']." ref.  a transfer_tbl_id = ".$transferId." gravados e registrado no banco de Events \r\n";
                    }
                }

                if (!empty($transfer_response_array['entries']) && is_array($transfer_response_array['entries']))
                {
                    foreach ($transfer_response_array['entries'] as $entry)
                    {
                        $entry_data = array
                        (
                            'external_id'           => $entry['external_id'],
                            'tbl_function'          => $function_id,
                            'function_moip_tbl_id'  => $transferId,
                            'moip_account'          => $entry['moipAccount']['account'],
                            'gross_amount'          => $entry['grossAmount'],
                            'liquid_amount'         => $entry['liquidAmount'],
                            'type'                  => $entry['type'],
                            'created_at'            => date('Y-m-d H:i:s', strtotime($entry['createdAt'])),
                            'description'           => $entry['description']
                        );

                        if ($this->_CI->model_moip->saveFunctionEntry($entry_data))
                            echo "Entrada ".$entry['type']." ref.  a transfer_tbl_id = ".$transferId." gravados e registrado no banco de Entradas \r\n";
                    }
                }

                return  $transfer_response_array['id'];
            }

            return false;
        }
        else
        {            
            $transfer_data = array
            (
                'status' => 'ERROR',
                'result_status' => 0,
                'result_number' => $this->responseCode,
                'result_message' => json_encode($transfer_response_array['errors'], JSON_UNESCAPED_UNICODE)
            );

            $this->_CI->model_gateway_transfers->saveTransfer($transferId, $transfer_data);

            echo "Transferencia ".$transfer_deposit_array['ownId']." retornou erro: ".serialize($transfer_response_array)." \r\n";   

            $errors = [];

            if (!empty($transfer_response_array['errors']) && is_array($transfer_response_array['errors']))
            {
                foreach ($transfer_response_array['errors'] as $error)
                {
                    $errors[] = 'CODE: '.$error['code'].' - '.$error['description'];
                }

                $errors = implode(' | ', $errors);
            }

            $event_data = array
            (
                'tbl_function' => $function_id,
                'function_moip_tbl_id' => $transferId,
                'type' => 'ERROR',
                'created_at' => date('Y-m-d H:i:s'),
                'description' => $errors
            );

            $this->_CI->model_moip->saveFunctionEvent($event_data);                
        }
        
        return false;
    }


    /**
     * ------------------------------------------------------
     * Conjunto de métodos genéricos
     * ------------------------------------------------------
     */

    public function runBatch($module_path, $module_method, $params = null)
    {
        if ($module_path && $module_method)
        {
            $event = $this->_CI->model_moip->getCalendarEvent($module_path, $module_method); 

			if (!$event) 
            {
                return false;
			}

            $save_job_array = [
                'module_path'   => $event['module_path'],
                'module_method' => $event['module_method'],
                'params'        => $event['params'],
                'status'        => 0,
                'finished'      => 0,
                'error'         => NULL,
                'error_count'   => 0,
                'error_msg'     => NULL, 
                'date_start'    => date('Y-m-d H:i:s', (time() + 5)),
                'date_end'      => NULL,
                'server_id'     => 1,
                'alert_after'   => $event['alert_after']
            ];

            return $this->_CI->model_moip->saveNewJob($save_job_array);
        }

        return false;
    }


    protected function moipTransactions($url, $method = 'GET', $data = null, $function = null, $optional_headers = null)
    {    
        $headers = array
        (
	        'Content-Type: application/json',
	        'Authorization: Bearer '.$this->app_token
	    );

        if(!empty($optional_headers) && is_array($optional_headers))
        {
            foreach($optional_headers as $header)
            {
                $headers[] = $header;
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST')
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if ($method == 'PUT')
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        }
		
		if ($method == 'DELETE')
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');        

        $this->result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);
		
        return $this->result;
    }

}