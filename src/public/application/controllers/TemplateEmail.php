<?php
/*
 
Controller de Catalogos de Produtos 

*/

use Symfony\Component\VarDumper\Cloner\Data;

defined('BASEPATH') OR exit('No direct script access allowed');

class TemplateEmail extends Admin_Controller 
{
	public function __construct()
	{
		parent::__construct();

		$this->data['page_title'] = 'Template de Email'; // $this->lang->line('application_mercado_livre');

		$this->load->model('model_template_email');
	}

	public function index()
    {
        if (!in_array('updateCompany', $this->permission) && !in_array('viewCompany', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = 'Templates de Email'; // $this->lang->line('application_manage_companies');
        $this->render_template('template_email/index', $this->data);
    }
	
	public function create()
	{
		if(!in_array('marketplaces_integrations', $this->permission)) {
           redirect('dashboard', 'refresh');
        }

		$this->data['page_title'] = 'Template de Email';
		
		$this->data['template_email_length_title'] = 100;
		$this->data['template_email_length_subject'] = 100;
		$this->data['status'] = $this->postClean('template_email_status');

		$this->form_validation->set_rules('template_email_title', 'Título do template' /*$this->lang->line('application_name')*/, 'trim|required');
		$this->form_validation->set_rules('template_email_subject', 'Assunto do template' /*$this->lang->line('application_name')*/, 'trim|required');
        $this->form_validation->set_rules('template_email_description', 'Descrição do template' /*$this->lang->line('application_name')*/, 'required');
        $this->form_validation->set_rules('template_email_status', 'Descrição do template' /*$this->lang->line('application_name')*/, '');

		if ($this->form_validation->run() == TRUE) {
            if ($this->data['status'] == 'on') {
                $this->data['status'] = '1';
            } else {
                $this->data['status']  = '0';
            }
			$data = array(
				'title' => $this->postClean('template_email_title',TRUE),
				'subject' => $this->postClean('template_email_subject',TRUE),
				'description' => $this->postClean('template_email_description'),
				'status' => $this->data['status']
            );
            
			$insert = $this->model_template_email->create($data);
            if ($insert) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created') . " O template de email foi criado.");
                redirect('templateEmail/index', 'refresh');
            } else {
                $this->session->set_flashdata('errors', $this->lang->line('messages_error_occurred'));
                redirect('templateEmail/create', 'refresh');
            }
		} else {
			$this->render_template('template_email/create', $this->data);
		}
	}

    /*
     * Fetches the orders data from the orders table
     * this function is called from the datatable ajax function
     */
    public function fetchTemplatesData()
    {
        $postdata = $this->postClean(NULL,TRUE);
		
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $busca = $postdata['search'];
        $length = $postdata['length'];

        $procura = '';
        if ($busca['value']) {
            if (strlen($busca['value']) >= 2) {  // Garantir no minimo 3 letras
				$procura = " AND ( template_email.title like '%" . $busca['value'] . "%' OR template_email.subject like '%" . $busca['value'] . "%') ";
            }
        } else {
            if (trim($postdata['title'])) {
                $procura .= " AND template_email.title like '%" . $postdata['title'] . "%'";
            }
            if (trim($postdata['subject'])) {
                $procura .= " AND template_email.subject like '%" . $postdata['subject'] . "%'";
            }
            if (trim($postdata['status']) != "") {
                $procura .= " AND template_email.status = " . $postdata['status'];
            }
        }

		$sOrder = '';
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "ASC";
            } else {
                $direcao = "DESC";
            }
            $campos = array('', 'template_email.id', 'template_email.title', 'template_email.subject', 'template_email.status', '');

            $campo = $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $data = $this->model_template_email->getTemplatesDataView($ini, $procura, $sOrder, $length);
        $filtered = $this->model_template_email->getTemplatesDataCount($procura);
        if ($procura == '') {
            $total_rec = $filtered;
        } else {
            $total_rec = $this->model_template_email->getTemplatesDataCount();
        }
        $result = array();

        foreach ($data as $key => $value) {
            // button
            $buttons = '';
            if (in_array('updateCompany', $this->permission) || in_array('viewCompany', $this->permission)) {
                $buttons .= ' <a href="' . base_url('templateEmail/copy/' . $value['id']) . '" class="btn btn-default" title="' . $this->lang->line('application_copy') . '"><i class="fa fa-copy"></i></a>';
                $buttons .= ' <a href="' . base_url('templateEmail/update/' . $value['id']) . '" class="btn btn-default" title="' . $this->lang->line('application_edit') . '"><i class="fa fa-pencil"></i></a>';
            }
            if (in_array('viewCompany', $this->permission)) {
                $buttons .= '<a href="' . base_url('templateEmail/delete/' . $value['id']) . '" class="btn btn-default" title="' . $this->lang->line('application_delete') . '"><i class="fa fa-trash"></i></a>';
            }
            $dont_use_url=(!in_array('updateCompany', $this->permission) && !in_array('viewCompany', $this->permission));

            if ($value['status']  == 1) {
                $value['status'] = '<span class="label label-success">' . $this->lang->line('application_active') . '</span>';
            } else {
                $value['status'] = '<span class="label label-danger">' . $this->lang->line('application_inactive') . '</span>';
            }
            $result[$key] = array(
                $value['title'],
                $value['subject'],
                $value['status'],
                $buttons
            );
        } // /foreach

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $total_rec,
            "recordsFiltered" => $filtered,
            "data" => $result
        );
        ob_clean();
        echo json_encode($output);
    }

	public function update($id)
    {
        if (!in_array('updateCompany', $this->permission) && !in_array('viewCompany', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
		
		$this->form_validation->set_rules('template_email_title', 'Título do template' /*$this->lang->line('application_name')*/, 'trim|required');
		$this->form_validation->set_rules('template_email_subject', 'Assunto do template' /*$this->lang->line('application_name')*/, 'trim|required');
		$this->form_validation->set_rules('template_email_description', 'Assunto do template' /*$this->lang->line('application_name')*/, 'required');
        $this->data['status'] = $this->postClean('template_email_status');

        if ($this->form_validation->run() == TRUE) {
            if ($this->data['status'] == 'on') {
                $this->data['status'] = '1';
            } else {
                $this->data['status']  = '0';
            }
			$data = array(
				'title' => $this->postClean('template_email_title',TRUE),
				'subject' => $this->postClean('template_email_subject',TRUE),
				'description' => $this->postClean('template_email_description'),
				'status' => $this->data['status']
			);
			$update = $this->model_template_email->update($data, $id);
            if ($update) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
                redirect('templateEmail/index', 'refresh');
            } else {
                $this->session->set_flashdata('errors', $this->lang->line('messages_error_occurred'));
                redirect('templateEmail/update', 'refresh');
            }
        } else {
			$this->data['page_title'] = 'Template de Email';			
			$this->data['template_email_length_title'] = 100;
			$this->data['template_email_length_subject'] = 100;
			$this->data['template_email_length_description'] = 10000;
			
            $templateEmail = $this->model_template_email->getTemplateData($id);
			$this->data['template_email_data'] = $templateEmail;
            $this->render_template('template_email/edit', $this->data);
        }
    }

	public function copy($id)
    {
		$templateEmail = $this->model_template_email->getTemplateData($id);

		$data = array(
			'title' => $templateEmail['title'],
			'subject' => $templateEmail['subject'],
			'description' => $templateEmail['description']
		);

		$insert = $this->model_template_email->create($data);
		if ($insert) {
			$this->session->set_flashdata('success', "O template de email foi copiado.");
			redirect('templateEmail/index', 'refresh');
		} else {
			$this->session->set_flashdata('errors', $this->lang->line('messages_error_occurred'));
			redirect('templateEmail/index', 'refresh');
		}
	}

	public function delete($id)
    {
		$templateEmail = $this->db->where('id', $id);
    	$templateEmailDelete = $this->db->delete('template_email');

		if ($templateEmailDelete) {
			$this->session->set_flashdata('success', "O template de email foi excluído.");
			redirect('templateEmail/index', 'refresh');
		} else {
			$this->session->set_flashdata('errors', $this->lang->line('messages_error_occurred'));
			redirect('templateEmail/index', 'refresh');
		}
	}
}
