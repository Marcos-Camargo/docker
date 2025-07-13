<?php
/*
SW Serviços de Informática 2019

Controller de Recebimentos

*/  
defined('BASEPATH') OR exit('No direct script access allowed');

class Receivables extends Admin_Controller 
{
	public function __construct()
	{
		parent::__construct();

		$this->not_logged_in();

		$this->data['page_title'] = $this->lang->line('application_receivables');

		$this->load->model('model_orders');
		$this->load->model('model_products');
		$this->load->model('model_receivables');
		$this->load->model('model_company');
		
	}

	/* 
	* It only redirects to the manage order page
	*/
	public function index()
	{
		if(!in_array('viewReceivables', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

		$this->data['page_title'] = $this->lang->line('application_manage_receivables');
		$this->render_template('receivables/index', $this->data);		
	}

	/*
	* Fetches the orders data from the orders table 
	* this function is called from the datatable ajax function
	*/
	public function fetchReceivablesData()
	{
		$result = array('data' => array());

		$data = $this->model_receivables->getReceivablesData();

		foreach ($data as $key => $value) {

			$date = date('d-m-Y', strtotime($value['date_ready']));
			$time = date('h:i a', strtotime($value['date_ready']));
			$date_ready = $date . ' ' . $time;
			$date = date('d-m-Y', strtotime($value['date_received']));
			$time = date('h:i a', strtotime($value['date_received']));
			$date_received = $date . ' ' . $time;
			$taxes = $value['service_charge'] + $value['vat_charge'];
			$liquido = get_instance()->formatprice($value['net_amount'] - $taxes);
			// button
			$buttons = '';

			if(in_array('viewReceivables', $this->permission)) {
				$buttons .= '<a target="__blank" href="'.base_url('receivables/printDiv/'.$value['id']).'" class="btn btn-default"><i class="fa fa-print"></i></a>';
			}

			if(in_array('updateReceivables', $this->permission)) {
				$buttons .= ' <a href="'.base_url('receivables/update/'.$value['id']).'" class="btn btn-default"><i class="fa fa-pencil"></i></a>';
			}

			if(in_array('deleteReceivables', $this->permission)) {
				$buttons .= ' <button type="button" class="btn btn-default" onclick="removeFunc('.$value['id'].')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-trash"></i></button>';
			}

			if($value['status'] == 1) {
				$paid_status = '<span class="label label-warning">'.$this->lang->line('application_rec_1').'</span>';
			}
			elseif($value['status'] == 2) {
				$paid_status = '<span class="label label-success">'.$this->lang->line('application_rec_2').'</span>';	
			}
			elseif($value['status'] == 3) {
				$paid_status = '<span class="label label-success">'.$this->lang->line('application_rec_3').'</span>';	
			}
			elseif($value['status'] == 4) {
				$paid_status = '<span class="label label-success">'.$this->lang->line('application_rec_4').'</span>';	
			}
			elseif($value['status'] == 5) {
				$paid_status = '<span class="label label-success">'.$this->lang->line('application_rec_5').'</span>';	
			}

			$result['data'][$key] = array(
				$value['id'],
				$value['bill_no'],
				$value['date_ready'],
				$value['date_requested'],
				$value['date_received'],
				$value['gross_amount'],
				$value['other'],
				$value['logistics'],
				$value['net_amount'],
				$taxes,
				$liquido,
				$paid_status,
				$buttons
			);
		} // /foreach

		echo json_encode($result);
	}

	/*
	* If the validation is not valid, then it redirects to the create page.
	* If the validation for each input field is valid then it inserts the data into the database 
	* and it stores the operation message into the session flashdata and display on the manage group page
	*/
	public function create()
	{
		if(!in_array('createReceivables', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

		$this->data['page_title'] = $this->lang->line('application_add_receivable');

	
        if ($this->form_validation->run() == TRUE) {        	
        	
        	$receivable_id = $this->model_receivables->create();
        	
        	if($receivable_id) {
        		$this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
        		redirect('receivables/update/'.$order_id, 'refresh');
        	}
        	else {
        		$this->session->set_flashdata('errors', $this->lang->line('messages_error_occurred'));
        		redirect('receivables/create/', 'refresh');
        	}
        }
        else {
            // false case
        	$company = $this->model_company->getCompanyData(1);
        	$this->data['company_data'] = $company;
        	$this->data['is_vat_enabled'] = ($company['vat_charge_value'] > 0) ? true : false;
        	$this->data['is_service_enabled'] = ($company['service_charge_value'] > 0) ? true : false;

            $this->render_template('receivables/create', $this->data);
        }	
	}


	/*
	* If the validation is not valid, then it redirects to the edit orders page 
	* If the validation is successfully then it updates the data into the database 
	* and it stores the operation message into the session flashdata and display on the manage group page
	*/
	public function update($id)
	{
		if(!in_array('updateReceivables', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

		if(!$id) {
			redirect('dashboard', 'refresh');
		}

		$this->data['page_title'] = $this->lang->line('application_update_receivable');


        if (($this->form_validation->run() == TRUE) || (!is_null($this->postClean("newrequest")))){        	
        	
        	$update = $this->model_receivables->update($id);
        	
        	if($update == true) {
        		$this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
        		redirect('receivables/update/'.$id, 'refresh');
        	}
        	else {
        		$this->session->set_flashdata('errors', $this->lang->line('messages_error_occurred'));
        		redirect('receivables/update/'.$id, 'refresh');
        	}
        }
        else {
            // false case
        	$company = $this->model_company->getCompanyData(1);
        	$this->data['company_data'] = $company;
        	$this->data['is_vat_enabled'] = ($company['vat_charge_value'] > 0) ? true : false;
        	$this->data['is_service_enabled'] = ($company['service_charge_value'] > 0) ? true : false;

        	$receivables_data = $this->model_receivables->getReceivablesData($id);

        	$result = array();
    		$this->data['receivables_data'] = $receivables_data;

        	$orders_data = $this->model_orders->getOrdersDatabyBill($receivables_data['origin'],$receivables_data['bill_no']);
    		$result['order'] = $orders_data;
    		$orders_item = $this->model_orders->getOrdersItemData($orders_data['id']);
    		foreach($orders_item as $k => $v) {
    			$result['order_item'][] = $v;
    		}
    		$this->data['order_data'] = $result;

        	$this->data['products'] = $this->model_products->getActiveProductData();      	

            $this->render_template('receivables/edit', $this->data);
        }
	}


	/*
	* It removes the data from the database
	* and it returns the response into the json format
	*/
	public function remove()
	{
		if(!in_array('deleteReceivables', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

		$receivable_id = $this->postClean('receivable_id');

        $response = array();
        if($receivable_id) {
            $delete = $this->model_receivables->remove($receivable_id);
            if($delete == true) {
                $response['success'] = true;
                $response['messages'] = $this->lang->line('messages_successfully_removed');
            }
            else {
                $response['success'] = false;
                $response['messages'] = $this->lang->line('messages_error_database_remove_product');
            }
        }
        else {
            $response['success'] = false;
            $response['messages'] = $this->lang->line('messages_refresh_page_again');
        }

        echo json_encode($response); 
	}


	/* 
	* It only redirects to the manage order page
	*/
	public function account()
	{
		if(!in_array('viewReceivables', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

		$this->data['page_title'] = $this->lang->line('application_manage_receivables');
		$this->render_template('receivables/account', $this->data);		
	}

	/*
	* Fetches the orders data from the orders table 
	* this function is called from the datatable ajax function
	*/
	public function fetchAccountData()
	{
		$result = array('data' => array());

		$data = $this->model_receivables->getAccountData();

		foreach ($data as $key => $value) {

			$date = date('d-m-Y', strtotime($value['date_schedulled']));
			$time = date('h:i a', strtotime($value['date_schedulled']));
			$date_ready = $date . ' ' . $time;
			$date = date('d-m-Y', strtotime($value['date_received']));
			$time = date('h:i a', strtotime($value['date_received']));
			$date_received = $date . ' ' . $time;
			// button
			$buttons = '';

			if(in_array('viewReceivables', $this->permission)) {
				$buttons .= '<a target="__blank" href="'.base_url('receivables/printDiv/'.$value['id']).'" class="btn btn-default"><i class="fa fa-print"></i></a>';
			}


			$result['data'][$key] = array(
				$value['id'],
				$value['store_id'],
				$value['date_ready'],
				$value['date_received'],
				$value['account'],
				$value['net_value'],
				$buttons
			);
		} // /foreach

		echo json_encode($result);
	}
	/*
	* It gets the product id and fetch the order data. 
	* The order print logic is done here 
	*/
	public function printDiv($id)
	{
		if(!in_array('viewReceivables', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
		if($id) {
			$receivables_data = $this->model_orders->getReceivablesData($id);
			$company_info = $this->model_company->getCompanyData(1);

			$receivables_date = date('d/m/Y', strtotime($receivables_data['date_time']));
			$paid_status = ($receivables_data['paid_status'] == 1) ? "Paid" : "Unpaid";

			$html = '<!-- Main content -->
			<!DOCTYPE html>
			<html>
			<head>
			  <meta charset="utf-8">
			  <meta http-equiv="X-UA-Compatible" content="IE=edge">
			  <title>AdminLTE 2 | Invoice</title>
			  <!-- Tell the browser to be responsive to screen width -->
			  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
			  <!-- Bootstrap 3.3.7 -->
			  <link rel="stylesheet" href="'.base_url('assets/bower_components/bootstrap/dist/css/bootstrap.min.css').'">
			  <!-- Font Awesome -->
			  <link rel="stylesheet" href="'.base_url('assets/bower_components/font-awesome/css/font-awesome.min.css').'">
			  <link rel="stylesheet" href="'.base_url('assets/dist/css/AdminLTE.min.css').'">
			</head>
			<body onload="window.print();">
			
			<div class="wrapper">
			  <section class="invoice">
			    <!-- title row -->
			    <div class="row">
			      <div class="col-xs-12">
			        <h2 class="page-header">
			          '.$company_info['name'].'
			          <small class="pull-right">Date: '.$receivables_date.'</small>
			        </h2>
			      </div>
			      <!-- /.col -->
			    </div>
			    <!-- info row -->
			    <div class="row invoice-info">
			      
			      <div class="col-sm-4 invoice-col">
			        
			        <b>Bill ID:</b> '.$receivables_data['bill_no'].'<br>
			        <b>Name:</b> '.$receivables_data['customer_name'].'<br>
			        <b>Address:</b> '.$receivables_data['customer_address'].' <br />
			        <b>Phone:</b> '.$receivables_data['customer_phone'].'
			      </div>
			      <!-- /.col -->
			    </div>
			    <!-- /.row -->


			    <div class="row">
			      
			      <div class="col-xs-6 pull pull-right">

			        <div class="table-responsive">
			          <table class="table">
			            <tr>
			              <th style="width:50%">Gross Amount:</th>
			              <td>'.$receivables_data['gross_amount'].'</td>
			            </tr>';

			            if($order_data['service_charge'] > 0) {
			            	$html .= '<tr>
				              <th>Service Charge ('.$receivables_data['service_charge_rate'].'%)</th>
				              <td>'.$receivables_data['service_charge'].'</td>
				            </tr>';
			            }

			            if($receivables_data['vat_charge'] > 0) {
			            	$html .= '<tr>
				              <th>Vat Charge ('.$receivables_data['vat_charge_rate'].'%)</th>
				              <td>'.$receivables_data['vat_charge'].'</td>
				            </tr>';
			            }
			            
			            
			            $html .=' <tr>
			              <th>Net Amount:</th>
			              <td>'.$receivables_data['net_amount'].'</td>
			            </tr>
			            <tr>
			              <th>Paid Status:</th>
			              <td>'.$paid_status.'</td>
			            </tr>
			          </table>
			        </div>
			      </div>
			      <!-- /.col -->
			    </div>
			    <!-- /.row -->
			  </section>
			  <!-- /.content -->
			</div>
		</body>
	</html>';

			  echo $html;
		}
	}

}