<?php
/*
SW Serviços de Informática 2019

Controller de Lojas/Depósitos

*/
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property CI_Loader $load
 * @property CI_Lang $lang
 * @property CI_Session $session
 *
 * @property CI_Form_validation $form_validation
 *
 * @property Model_suggestions $model_suggestions
 * @property Model_suggestions_likes $model_suggestions_likes
 */

class Suggestions extends Admin_Controller
{

  private $allowable_tags = 'p,b,br,i,u,strike,sup,span,ul,li,ol';
  private $categories;
  private $types;

  public function __construct()
  {
    parent::__construct();

    $this->not_logged_in();
    $this->load->model('model_suggestions');
    $this->load->model('model_suggestions_likes');
    $this->types = [
      [
        'text' => $this->lang->line('application_type_array_share'),
        'selected' => true,
        'value' => 'new_idea',
      ],
      [
        'text' => $this->lang->line('application_type_array_report'),
        'selected' => false,
        'value' => 'bug',
      ],
      [
        'text' => $this->lang->line('application_type_array_praise'),
        'selected' => false,
        'value' => 'to_praise',
      ],
    ];
    $this->categories = [
      [
        'text' => $this->lang->line('application_categories_array_general'),
        'selected' => true,
        'value' => 'general',
      ],
      [
        'text' => $this->lang->line('application_categories_array_process'),
        'selected' => false,
        'value' => 'law_suit',
      ],
    ];
    $this->allowable_tags = '<' . implode('><', explode(',', $this->allowable_tags)) . '>';
  }

  /*
    * It only redirects to the manage stores page
    */

  public function index()
  {
    $this->data['types'] = $this->types;
    $this->data['categories'] = $this->categories;
    $this->data['page_title'] = $this->lang->line('application_manage_suggestions');
    $this->data['title'] = $this->lang->line('application_manage_suggestions');
    $this->render_template('suggestions/index', $this->data);
  }
  public function fetchData()
  {
    ob_start();
    $postdata = $this->postClean();
    $draw = $postdata['draw'];
    $title = $postdata['title'] ?? null;
    $tags = $postdata['tags'] ?? null;
    $type = $postdata['type'] ?? null;
    $categorie = $postdata['categorie'] ?? null;
    $all_suggestions = $this->model_suggestions->getAll($title, $tags, $type, $categorie);
    $result = array();
    foreach ($all_suggestions as $suggestion) {
      $table_data = array();
      $like = $this->model_suggestions_likes->getByUserAndSuggestions($this->session->userdata('id'), $suggestion['id']);
      $like_qtd = $this->model_suggestions_likes->countBySuggestion($suggestion['id']);
      $button_like = '(' . $like_qtd . ') <a href="' . base_url('suggestions/like/' . $suggestion['id']) . '" class="btn btn-link">' . (!$like ? '<i class="fa fa-thumbs-o-up" aria-hidden="true"></i>' : '<i class="fa fa-thumbs-o-down" aria-hidden="true"></i>') . '</i></a>';
      $table_data[0] = '<a href="' . base_url('suggestions/update/' . $suggestion['id']) . '">' . $suggestion['id'] . '</a>';
      $table_data[1] = $suggestion['title'];
      $table_data[2] = $button_like;
      if (in_array('delete_suggestions', $this->permission)) {
        $button_delete = '<a href="' . base_url('suggestions/delete/' . $suggestion['id']) . '" class="btn btn-link"><i class="fa fa-trash" aria-hidden="true"></i></i></a>';
        $table_data[3] = $button_delete;
      }
      $result[] = $table_data;
    }
    $output = array(
      "draw" => $draw,
      "recordsTotal" => count($result),
      "recordsFiltered" => count($result),
      "data" => $result
    );
    ob_clean();
    echo json_encode($output);
  }
  public function like($id)
  {
    $data = [
      'user_id' => $this->session->userdata('id'),
      'suggestion_id' => $id,
    ];
    $like = $this->model_suggestions_likes->getByUserAndSuggestions($data['user_id'], $data['suggestion_id']);
    if ($like) {
      $this->model_suggestions_likes->delete($like['id']);
    } else {
      $this->model_suggestions_likes->create($data);
    }
    redirect('suggestions', 'refresh');
  }
  public function delete($id)
  {
    if (!in_array('delete_suggestions', $this->permission)) {
      $this->session->set_flashdata('error', $this->lang->line('application_dont_permission'));
    } else {
      $this->model_suggestions->delete($id);
    }
    redirect('suggestions', 'refresh');
  }
  public function update($id)
  {
    $this->data['page_title'] = $this->lang->line('application_update_suggestion');
    $this->data['title'] = $this->lang->line('application_update_suggestion');
    $this->data['types'] = $this->types;
    $this->data['categories'] = $this->categories;
    $this->data['suggestion'] = $this->model_suggestions->getOne($id);
    $this->data['user_id'] = $this->session->userdata('id');
    if (empty($this->postClean())) {
      $this->render_template('suggestions/edit', $this->data);
    } else {
        $this->validateFormSuggestion();
      if ($this->form_validation->run()) {
        $suggestion = $this->postClean();
        unset($suggestion['files']);
        $suggestion['user_id'] = $this->session->userdata('id');
        $suggestion['description'] = htmlentities(strip_tags_products(html_entity_decode($this->postClean('description')), $this->allowable_tags));
        $this->model_suggestions->update($id, $suggestion);
        redirect('suggestions', 'refresh');
      } else {
        $this->render_template('suggestions/edit', $this->data);
      }
    }
  }
  public function create()
  {
    $this->data['page_title'] = $this->lang->line('application_new_suggestion');
    $this->data['title'] = $this->lang->line('application_new_suggestion');
    $this->data['types'] = $this->types;
    $this->data['categories'] = $this->categories;
    if (empty($this->postClean())) {
      $this->render_template('suggestions/create', $this->data);
    } else {
        $this->validateFormSuggestion();
      if ($this->form_validation->run()) {
        $suggestion = $this->postClean();
        unset($suggestion['files']);
        $suggestion['user_id'] = $this->session->userdata('id');
        $suggestion['description'] = htmlentities(strip_tags_products(html_entity_decode($this->postClean('description')), $this->allowable_tags));
        $this->model_suggestions->create($suggestion);
        redirect('suggestions', 'refresh');
      } else {
        $this->render_template('suggestions/create', $this->data);
      }
    }
  }

    /**
     * @return void
     */
    public function validateFormSuggestion(): void
    {
        $this->form_validation->set_rules('type', $this->lang->line('application_status'), 'trim|required');
        $this->form_validation->set_rules('categorie', $this->lang->line('application_status'), 'trim|required');
        $this->form_validation->set_rules('title', $this->lang->line('application_status'), 'trim|required');
        $this->form_validation->set_rules('description', $this->lang->line('application_status'), 'trim|required');
        $this->form_validation->set_rules('tags', $this->lang->line('application_status'), 'trim|required');
    }
}
