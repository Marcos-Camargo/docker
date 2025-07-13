<?php defined('BASEPATH') or exit('No direct script access allowed');
return new class extends CI_Migration
{
  public function up()
  {

    $this->load->model('model_products');
    $this->load->model('model_category');
    $this->load->model('model_settings');


    $seller =  $this->model_settings->getSettingDatabyName('sellercenter');
    if ($seller['value'] == 'conectala') {

      $limit = 200;
      $offset = 0;
      while (true) {

        $list = $this->model_products->listProduct($offset, $limit); //seleciono na model
        if (count($list) == 0) {
          break;
        }

        $offset += $limit;
        $i = 0;
        $soma = "";
        foreach ($list as $v) {
          $id = $v['id'];
          $idCategory_id = $v['category_id'];  //pego o id da categoria setado no banco
          $idCategory_id = trim($idCategory_id, '[" "]'); //limpo ela removendo esses [" "] caracteres
          $block = $this->model_category->getcategoryBlock($idCategory_id); //seleciono na model
          $name_cat = $this->model_category->getcategoryName($idCategory_id); //seleciono na model

          if ($block == 1) { // se produto estiver com a categoria com prazo fixado retorno o numero de dias
            $days_cross_docking = $this->model_category->getcategoryDays_cross_docking($idCategory_id); //seleciono na model
            if ($v['prazo_fixo'] <> 1) {
              $updatePrazoOperacionalExtra = $this->model_products->updatePrazoOperacionalExtra($days_cross_docking, $id); //Update de Dias Fixados
              $i++;
            }
          } else {
            $categoryName = strtolower($name_cat);
            $prazo_operacional_extra = $v['prazo_operacional_extra'];
            if ($v['prazo_fixo'] <> 1) {
              if ((preg_match("/moda/", $categoryName)) || (preg_match("/móveis/", $categoryName)) || (preg_match("/fashion/", $categoryName)) || (preg_match("/arte/", $categoryName)) || (preg_match("/pcs/", $categoryName))) {
                if ($prazo_operacional_extra > 15) {
                  $updatePrazoOperacional = $this->model_products->updatePrazoOperacionalExtra(15, $id); //Update de Dias Fixados
                }
              } else {
                if ($prazo_operacional_extra > 2) {
                  $updatePrazoOperacional = $this->model_products->updatePrazoOperacionalExtra(2, $id); //Update de Dias Fixados 
                }
              }
            }
          }
        }
      }
    }
  }

  public function down()
  {
    ### Drop table shipping_pricing_rules ##
    $this->dbforge->drop_table("shipping_pricing_rules", TRUE);
  }
};
