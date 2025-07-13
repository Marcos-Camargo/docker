<?php
defined('BASEPATH') || exit('No direct script access allowed');
class CustomizationTheme extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!in_array('enableCustomizationScreen', $this->permission)) {
			redirect('dashboard', 'refresh');
		}
    }

    public function index()
    {

        $sellerCenter = $this->session->userdata('layout');
        $this->data['page_title'] = "Customização de tema";

        $this->data['favicon'] = '';
        $this->data['banner']  = '';

        if(file_exists('assets/skins/'.$sellerCenter["value"].'/favicon.ico')){
          $this->data['favicon'] = 'assets/skins/'.$sellerCenter["value"].'/favicon.ico';
        }
        if(file_exists('assets/skins/'.$sellerCenter["value"].'/banner.jpg')){
          $this->data['banner'] = 'assets/skins/'.$sellerCenter["value"].'/banner.jpg';
        }

        $this->render_template('customizationTheme/index', $this->data);
    }

    public function create()
    {
        $cores = $this->input->post('color_side_bar_a') ."," .
                 $this->input->post('color_side_bar_a_hover') . "," .
                 $this->input->post('color_side_bar_a_background') . "," .
                 $this->input->post('color_side_bar_a_background_hover') . "," .
                 $this->input->post('color_main_background') . "," .
                 $this->input->post('color_footer_background') . "," .
                 $this->input->post('button_default_background_color') . "," .
                 $this->input->post('button_default_color') . "," .
                 $this->input->post('button_primary_background_color') . "," .
                 $this->input->post('button_primary_color') . ",".
                 $this->input->post('button_danger_background_color') . ",".
                 $this->input->post('button_danger_color') . "," .
                 $this->input->post('button_warning_background_color') . "," .
                 $this->input->post('button_warning_color') . "," .
                 $this->input->post('color_all_text') . "," .
                 $this->input->post('button_success_background_color') . "," .
                 $this->input->post('button_success_color') . ",";

        $sellerCenter = $this->session->userdata('layout');

//        $fp = fopen("assets/dist/css/CustomizationTheme/styles_".$sellerCenter['value'].".txt", "w+");
//        fwrite($fp, $cores . "\r\n");
//        fclose($fp);

        $file = "assets/dist/css/CustomizationTheme/styles_".$sellerCenter['value'].".txt";
        $fp = fopen($file, "w+");
        fwrite($fp, $cores. "\r\n");
        fclose($fp);
        chmod($file, 0775);

        if($_FILES['inputFavicon']['name'] != '' || $_FILES['inputFavicon']['name'] != null)
        {
            if ($_FILES["inputFavicon"]["type"] != "image/x-icon") {
                $this->session->set_flashdata('error', 'Favicon deve ser no formato ico');
                redirect('CustomizationTheme', 'refresh');
                die();
            }
            if ($_FILES["inputFavicon"]["size"] > 51200) {
                $this->session->set_flashdata('error', 'Favicon deve ser menor que 50 kb');
                redirect('CustomizationTheme', 'refresh');
                die();
            }
            if (!file_exists('assets/skins/'.$sellerCenter["value"])) {
                $dir = mkdir('assets/skins/'.$sellerCenter["value"], 0775, true);
            }
            $new_name = 'favicon.ico';
            $dir = 'assets/skins/'.$sellerCenter["value"];
            move_uploaded_file($_FILES['inputFavicon']['tmp_name'], $dir.'/'.$new_name);
            if($_FILES['inputBanner']['name'] == '' || $_FILES['inputBanner']['name'] == null){
                $this->session->set_flashdata('success', 'Favicon inserido com sucesso');
                redirect('CustomizationTheme', 'refresh');
                exit();
            }
        }

        if($_FILES['inputBanner']['name'] != '' || $_FILES['inputBanner']['name'] != null)
        {
            if ($_FILES["inputBanner"]["type"] != "image/jpeg") {
                $this->session->set_flashdata('error', 'O Banner deve ser no formato jpg ou jpeg');
                redirect('CustomizationTheme', 'refresh');
                die();
            }
            if ($_FILES["inputBanner"]["size"] > 512000) {
                $this->session->set_flashdata('error', 'O Banner deve ser menor que 500kb');
                redirect('CustomizationTheme', 'refresh');
                die();
            }
            if (!file_exists('assets/skins/'.$sellerCenter["value"])) {
                $dir = mkdir('assets/skins/'.$sellerCenter["value"], 0775, true);
            }
            $new_name = 'banner.jpg';
            $dir = 'assets/skins/'.$sellerCenter["value"];
            move_uploaded_file($_FILES['inputBanner']['tmp_name'], $dir.'/'.$new_name);
            $this->session->set_flashdata('success', 'Banner inserido com sucesso');
            redirect('CustomizationTheme', 'refresh');
        }

        $this->session->set_flashdata('success', 'Personalização aplicada com sucesso!');
        redirect('/CustomizationTheme', 'refresh');
    }
    public function removeTheme()
    {
        $cores = '';
        $sellerCenter = $this->session->userdata('layout');
        $fp = fopen("assets/dist/css/CustomizationTheme/styles_".$sellerCenter['value'].".txt", "w+");
        fwrite($fp, $cores . "\r\n");
        fclose($fp);

        $this->session->set_flashdata('success', 'Personalização removida com sucesso!');
        redirect('/CustomizationTheme', 'refresh');
        exit();
    }
    public function removeFavicon()
    {
        $sellerCenter = $this->session->userdata('layout');
        if(file_exists('assets/skins/'.$sellerCenter["value"].'/favicon.ico')){
            unlink('assets/skins/'.$sellerCenter["value"].'/favicon.ico');
        }
        $this->session->set_flashdata('success', 'Favicon removido com sucesso!');
        redirect('/CustomizationTheme', 'refresh');
        exit();
    }
    public function removeBanner()
    {
        $sellerCenter = $this->session->userdata('layout');
        if(file_exists('assets/skins/'.$sellerCenter["value"].'/banner.jpg')){
            unlink('assets/skins/'.$sellerCenter["value"].'/banner.jpg');
        }
        $this->session->set_flashdata('success', 'Banner removido com sucesso!');
        redirect('/CustomizationTheme', 'refresh');
        exit();
    }
}