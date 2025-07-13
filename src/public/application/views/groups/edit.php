<!--
SW Serviços de Informática 2019

Editar Grupos de Acesso

-->


  <!-- Content Wrapper. Contains page content -->
<style>
    #div_hide{
        display: none;
    }
    .text_one{
        font-weight:bold;
        margin: 15px 0;
        color:#007fff;
    }
    span.bool {
        background: #007fff;
        color: #fff;
        padding: 7px 14px;
        border-radius: 36px;
        margin-right: 10px;
    }
    .ed_bool{
        padding: 10px 0;
    }
    .ed_ul{
        list-style-type: none;
        margin-left: -40px;
        margin-bottom: 25px;
    }
    .ed_box_2{
        margin: 13% 0;
        /*width: 600px;*/
    }
    .ed_text{
        margin: 25px 0;
    }
    .ed_btn_cg{
        margin-left: 10px;
    }
    .ed_imgg{
        background-image: url('<?= base_url("assets/images/groups_permission_Imagem.png") ?>');
        background-size: cover;
        display: inline-block;
        width: 55%;
        height: 340px;
        margin-left: 0px;
    }
    .ed_video{
        background: #059;
        margin: 206px 0 0 0;
        display: inline-flex;
        position: absolute;
        height: 182px;
        margin-top: 0px;
        margin-left: 100px;
        z-index: 4;
    }
    .div_overflow{
        overflow: scroll;
        height: 450px;
    }
    @media only screen and (max-width: 1600px) {
        .ed_imgg {
            width: 80%;
            margin-left: 0px;
        }
    }
    tr.selected td {
        background-color: #ECF0F5;
        font-weight: bold;
    }
    .nav-tabs-custom a{
        text-decoration: none;
    }
    .tab-content{
        overflow-x: hidden;
        height: 450px;
    }
    .text_show{
        margin-right: 12px;
    }
    .image {
        display: block;
        width: 100%;
        height: auto;
    }
    .overlay {
        position: absolute;
        bottom: 0;
        background: #000!important;
        color: #f1f1f1;
        transition: .5s ease;
        opacity:0;
        color: white;
        font-size: 20px;
        padding: 88px 78px;
        text-align: center;
        top: 3px;
        left: 115px;
    }
    .for_overlay:hover .overlay {
        opacity: 1;
    }
</style>

  <div class="content-wrapper">


  <?php
      $action = base_url('groups/'.$function);
      $ro = '';
      $di = '';
      if ($function == 'create') {
          $data['pageinfo'] = 'application_add';
          $header = $this->lang->line('application_add_group');
      } elseif ($function == 'edit') {
          $data['pageinfo'] = 'application_edit';
          $header = $this->lang->line('application_update_information');
          $action .= '/'.$group_data['id'];
      } else {
          $data['pageinfo'] = 'application_view';
          $header = $this->lang->line('application_group');
          $action .= '/'.$group_data['id'];
          $ro = ' readonly ';
          $di = ' disabled ';
      }
      $this->load->view('templates/content_header',$data);
      $serialize_permission = unserialize($group_data['permission']);
  ?>

    <!-- Main content -->
    <section class="content">
      <!-- Small boxes (Stat box) -->
        <div class="box" id="div_apresentation">
            <div class="box-body">
                <div class="form-group col-md-6 col-xs-6" style="bottom:-90px;">
                    <div class="col-sm-12">
                        <span class="ed_imgg"></span>
                    </div>
                    <?php $param = $this->model_settings->getSettingDatabyName('youtube_tutorial_create_permission'); ?>
                    <div class="col-sm-12 for_overlay <?= $param && $param['status'] == 2 || empty($param['value']) ? 'hidden' : '' ?>">
                        <iframe class="ed_video" src="<?= $param && $param['status'] == 1 ? 'https://www.youtube.com/embed/'.$param['value'] : '' ?>" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                        <div class="overlay">
                            <button class="btn btn-primary" data-target=".modal-1" data-toggle="modal" data-the-video="<?= $param && $param['status'] == 1 ? 'https://www.youtube.com/embed/'.$param['value'].'?rel=0&amp;showinfo=0&amp;wmode=opaque&amp;html5=1' : '' ?>" >Assitir Vídeo tutorial</button>
                        </div>
                    </div>
                </div>
                <div class="form-group col-md-6 col-xs-6 ed_box_2">
                    <h2 class="text_one">Crie grupos de permissões para <br/> controlar os acessos de seus usuários</h2>
                    <h4 class="text-black ed_text">Um usuário vai poder acessar e utilizar somente as telas permitidas ao grupo <br/> de permissões o qual ele foi atribuído.</h4>
                    <ul class="ed_ul">
                        <li class="text-black ed_bool"><span class="bool">1</span> Temos alguns grupos padrões já criados para você utilizar</li>
                        <li class="text-black ed_bool"><span class="bool">2</span> Você pode criar novos grupos com suas próprias permissões</li>
                        <li class="text-black ed_bool"><span class="bool">3</span> Ao criar um usuário você seleciona o seu grupo de permissões</li>
                    </ul>
                    <div class="">
                        <a href="#" class="btn btn-default" id="viewlist">Ver grupos padrões</a>&nbsp;
                        <a href="javascript:void(0)" class="btn btn-success ed_btn_cg" id="creatGroup">Criar novo grupo</a>
                    </div>
                </div>
            </div>
        </div>
      <div class="row">
        <div class="col-md-12 col-xs-12" id="div_hide">
          <?php if($this->session->flashdata('success')): ?>
            <div class="alert alert-success alert-dismissible" role="alert">
              <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              <?= $this->session->flashdata('success'); ?>
            </div>
          <?php elseif($this->session->flashdata('error')): ?>
            <div class="alert alert-error alert-dismissible" role="alert">
              <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              <?= $this->session->flashdata('error'); ?>
            </div>
          <?php endif; ?>
          <form role="form" action="<?php echo $action ?>" method="post">
            <input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" />

            <div class="box">
              <div class="box-header">
              </div>
              <div class="box-body">
                <?php
                if (validation_errors()) {
                  foreach (explode("</p>",validation_errors()) as $erro) {
                    $erro = trim($erro);
                    if ($erro!="") { ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?= $erro."</p>"; ?>
                    </div>
                <?php
                    }
                  }
                } ?>
                <h4 style="margin: 0 0 15px 15px;"><span class="bool">1</span>Dados do grupo</h4>
                <div class="form-group col-md-3 col-xs-3 <?php echo (form_error('group_name')) ? 'has-error' : '';  ?>">
                  <label for="group_name"><?=$this->lang->line('application_name');?></label>
                  <input type="text" class="form-control" id="group_name" <?= $ro;?> name="group_name" placeholder="<?=$this->lang->line('application_enter_group_name');?>" value="<?= set_value('group_name', $group_data['group_name']) ?>" required>
                  <?php echo '<i style="color:red">'.form_error('group_name').'</i>'; ?>
                </div>
                <div class="form-group col-md-2 col-xs-2 <?php echo (form_error('only_admin')) ? 'has-error' : '';  ?>">
                  <label for="group_isadmin"><?=$this->lang->line('application_admin');?></label>
                  <select class="form-control" id="only_admin" name="only_admin">
                    <option value="0" <?= $di;?>  <?= set_select('only_admin', 0, $group_data['only_admin'] == 0)  ?> ><?=$this->lang->line('application_no')?></option>
                    <option value="1" <?= $di;?>  <?= set_select('only_admin', 1, $group_data['only_admin'] == 1)  ?> ><?=$this->lang->line('application_yes')?></option>
                  </select>
                  <?php echo '<i style="color:red">'.form_error('only_admin').'</i>'; ?>
                </div>
                  <div class="form-group col-md-7 col-xs-7 <?php echo (form_error('group_description')) ? 'has-error' : '';  ?>">
                      <label for="group_description">Descrição</label>
                      <input type="text" class="form-control" id="group_description" name="group_description" placeholder="Descrição do grupo" value="<?= set_value('group_name', $group_data['group_name']) ?>" >
                      <?php echo '<i style="color:red">'.form_error('group_description').'</i>'; ?>
                  </div>
              </div>
            </div>
            <div class="box">
              <div class="box-header">
                  <h4 style="margin: 15px 0 15px 15px;"><span class="bool">2</span>Selecione as permissões para cada funcionalidade</h4>
              </div>
                <div class="col-md-2">
                    <span><b>Selecione ao lado</b> o nome de cada funcionalidade e selecione as permissões que os usuários do grupo vão ter acesso.</span>
                </div>
                <div class="col-sm-10 form-group">
                    <input class="from-control pull-right" autocomplete="false" id="search" placeholder="Pesquisar">
                </div>
                <div class="col-md-10">
                    <div class="nav-tabs-custom">
                        <ul class="nav nav-tabs">
                            <li class="active"><a href="#tab_1" data-toggle="tab" ><span class="refil">Publicação de Produtos</span>
                                </a></li>
                            <li><a href="#tab_2" data-toggle="tab"><span class="refil">Pedidos</span></a></li>
                            <li><a href="#tab_22" data-toggle="tab"><span class="refil">Fluxo Financeiro</span></a></li>
                            <li><a href="#tab_3" data-toggle="tab"><span class="refil">Integrações</span></a></li>
                            <li><a href="#tab_4" data-toggle="tab"><span class="refil">Logística</span></a></li>
                            <li><a href="#tab_5" data-toggle="tab"><span class="refil">Cadastro</span></a></li>
                        </ul>
                        <div class="tab-content">
                            <div id="tab_1" class="tab-pane fade in active" >
                                <div class="row">
                                    <div class="box-header">
                                         <h3 class="box-title"><?=$this->lang->line('application_products_publish');?></h3>
                                    </div>
                                    <div class="box-body row">
                                        <div class="form-group col-md-12 col-xs-10">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th style="width:60%"><?=$this->lang->line('application_permission');?></th>
                                                        <th style="width:10%">
                                                            <?=$this->lang->line('application_create');?>
                                                            <i class="fa fa-fw fa-info-circle" data-toggle="tooltip" data-placement="top" title="Permite ao usuário adicionar/cadastrar um novo item"></i>
                                                        </th>
                                                        <th style="width:10%"><?=$this->lang->line('application_update');?>
                                                            <i class="fa fa-fw fa-info-circle" data-toggle="tooltip" data-placement="top" title="Permite ao usuário fazer uma alteração em um item já cadastrado anteriormente"></i>
                                                        </th>
                                                        <th style="width:10%"><?=$this->lang->line('application_view');?>
                                                            <i class="fa fa-fw fa-info-circle" data-toggle="tooltip" data-placement="top" title="Permite ao usuário visualizar o item já cadastrado"></i>
                                                        </th>
                                                        <th style="width:10%"><?=$this->lang->line('application_delete');?>
                                                            <i class="fa fa-fw fa-info-circle" data-toggle="tooltip" data-placement="top" title="Permite ao usuário excluir um item já cadastrado. Essa ação pode ser inrreversivel em alguns casos"></i>
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_products');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#publicationPermMult1" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="publicationPermMult1" class="collapse">
                                                            <span class="text-black">Gestão de produtos</span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createProduct" <?= set_checkbox('permission', 'createProduct',  in_array('createProduct', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateProduct" <?= set_checkbox('permission', 'updateProduct',  in_array('updateProduct', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewProduct" <?= set_checkbox('permission', 'viewProduct',  in_array('viewProduct', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteProduct" <?= set_checkbox('permission', 'deleteProduct',  in_array('deleteProduct', $serialize_permission)) ?>></td>
                                                </tr>

                                                <tr>
                                                    <td>
                                                        <span class="final"><?= $this->lang->line('application_trash'); ?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#publicationPermMult2" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="publicationPermMult2" class="collapse">
                                                            <span class="text-black">Exclusão de produtos</span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="cloneProdTrash" <?= set_checkbox('permission', 'cloneProdTrash', in_array('cloneProdTrash', $serialize_permission)) ?>><strong>Clonar</strong>                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="moveProdTrash" <?= set_checkbox('permission', 'moveProdTrash', in_array('moveProdTrash', $serialize_permission)) ?>><strong>Mover</strong></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewTrash" <?= set_checkbox('permission', 'viewTrash', in_array('viewTrash', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteProdTrash" <?= set_checkbox('permission', 'deleteProdTrash', in_array('deleteProdTrash', $serialize_permission)) ?>></td>
                                                </tr>

                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_publication_management');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#publicationPermMult3" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="publicationPermMult3" class="collapse">
                                                            <span class="text-black">
                                                                permite visualizar "gestão de publicação" no menu lateral para enviar produtos aos marketplaces
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createPublicationManagement" <?= set_checkbox('permission', 'createPublicationManagement',  in_array('createPublicationManagement', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updatePublicationManagement" <?= set_checkbox('permission', 'updatePublicationManagement',  in_array('updatePublicationManagement', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewPublicationManagement" <?= set_checkbox('permission', 'viewPublicationManagement',  in_array('viewPublicationManagement', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deletePublicationManagement" <?= set_checkbox('permission', 'deletePublicationManagement',  in_array('deletePublicationManagement', $serialize_permission)) ?>></td>
                                                </tr>

                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_promotions');?></span>
                                                    </td>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createPromotions" <?= set_checkbox('permission', 'createPromotions',  in_array('createPromotions', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updatePromotions" <?= set_checkbox('permission', 'updatePromotions',  in_array('updatePromotions', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewPromotions" <?= set_checkbox('permission', 'viewPromotions',  in_array('viewPromotions', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deletePromotions" <?= set_checkbox('permission', 'deletePromotions',  in_array('deletePromotions', $serialize_permission)) ?>></td>
                                                </tr>

                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_promotions_shared');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createPromotionsShare" <?= set_checkbox('permission', 'createPromotionsShare',  in_array('createPromotionsShare', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updatePromotionsShare" <?= set_checkbox('permission', 'updatePromotionsShare',  in_array('updatePromotionsShare', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewPromotionsShare" <?= set_checkbox('permission', 'viewPromotionsShare',  in_array('viewPromotionsShare', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deletePromotionsShare" <?= set_checkbox('permission', 'deletePromotionsShare',  in_array('deletePromotionsShare', $serialize_permission)) ?>></td>
                                                </tr>

                                                <td>
                                                    <span class="final"><?=$this->lang->line('application_campaigns');?></span>
                                                    <a href="javascript:void(0)" data-toggle="collapse" data-target="#publicationPermMult6" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                    <div id="publicationPermMult6" class="collapse">
                                                        <span class="text-black">
                                                            Criar e Aderir a campanhas de preço como Marketplace e Seller
                                                        </span>
                                                    </div>
                                                </td>
                                                <td><input type="checkbox" name="permission[]" id="permission" value="createCampaigns" class="minimal" <?php echo set_checkbox('permission', 'createCampaigns',  in_array('createCampaigns', $serialize_permission)) ?>></td>
                                                <td><input type="checkbox" name="permission[]" id="permission" value="updateCampaigns" class="minimal" <?php echo set_checkbox('permission', 'updateCampaigns',  in_array('updateCampaigns', $serialize_permission)) ?>></td>
                                                <td><input type="checkbox" name="permission[]" id="permission" value="viewCampaigns" class="minimal" <?php echo set_checkbox('permission', 'viewCampaigns',  in_array('viewCampaigns', $serialize_permission)) ?>></td>
                                                <td><input type="checkbox" name="permission[]" id="permission" value="deleteCampaigns" class="minimal" <?php echo set_checkbox('permission', 'deleteCampaigns',  in_array('deleteCampaigns', $serialize_permission)) ?>></td>
                                                </tr>

                                                <td>
                                                    <span class="final"><?=$this->lang->line('application_campaigns_products');?></span>
                                                    <a href="javascript:void(0)" data-toggle="collapse" data-target="#publicationPermMult7" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                    <div id="publicationPermMult7" class="collapse">
                                                        <span class="text-black">
                                                            Permite gerenciar os produtos das campanhas
                                                        </span>
                                                    </div>
                                                </td>
                                                <td><input type="checkbox" name="permission[]" id="permission" value="createCampaignsProducts" class="minimal" <?php echo set_checkbox('permission', 'createCampaignsProducts',  in_array('createCampaignsProducts', $serialize_permission)) ?>></td>
                                                <td><input type="checkbox" name="permission[]" id="permission" value="updateCampaignsProducts" class="minimal" <?php echo set_checkbox('permission', 'updateCampaignsProducts',  in_array('updateCampaignsProducts', $serialize_permission)) ?>></td>
                                                <td><input type="checkbox" name="permission[]" id="permission" value="viewCampaignsProducts" class="minimal" <?php echo set_checkbox('permission', 'viewCampaignsProducts',  in_array('viewCampaignsProducts', $serialize_permission)) ?>></td>
                                                <td><input type="checkbox" name="permission[]" id="permission" value="deleteCampaignsProducts" class="minimal" <?php echo set_checkbox('permission', 'deleteCampaignsProducts',  in_array('deleteCampaignsProducts', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_curation');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#publicationPermMult8" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="publicationPermMult8" class="collapse">
                                                            <span class="text-black">
                                                                Apenas para o marketplace - Tela para gestão, validação, aprovação e rejeição de produtos que serão enviados para o marketplace
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createCuration" <?= set_checkbox('permission', 'createCuration',  in_array('createCuration', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateCuration" <?= set_checkbox('permission', 'updateCuration',  in_array('updateCuration', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewCuration" <?= set_checkbox('permission', 'viewCuration',  in_array('viewCuration', $serialize_permission)) ?>></td>
                                                    <td>-</td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_catalogs');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#publicationPermMult9" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="publicationPermMult9" class="collapse">
                                                            <span class="text-black">
                                                                Apenas para o marketplace - tela para a criação de catalogos de produtos
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createCatalog" <?= set_checkbox('permission', 'createCatalog',  in_array('createCatalog', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateCatalog" <?= set_checkbox('permission', 'updateCatalog',  in_array('updateCatalog', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewCatalog" <?= set_checkbox('permission', 'viewCatalog',  in_array('viewCatalog', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteCatalog" <?= set_checkbox('permission', 'deleteCatalog',  in_array('deleteCatalog', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_catalogproducts');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#publicationPermMult10" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="publicationPermMult10" class="collapse">
                                                            <span class="text-black">
                                                                Tela para seleção de produtos que serão vendidos do catalogo
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="createProductsCatalog" class="minimal" <?php echo set_checkbox('permission', 'createProductsCatalog',  in_array('createProductsCatalog', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="updateProductsCatalog" class="minimal" <?php echo set_checkbox('permission', 'updateProductsCatalog',  in_array('updateProductsCatalog', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="viewProductsCatalog" class="minimal" <?php echo set_checkbox('permission', 'viewProductsCatalog',  in_array('viewProductsCatalog', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="deleteProductsCatalog" class="minimal" <?php echo set_checkbox('permission', 'deleteProductsCatalog',  in_array('deleteProductsCatalog', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_create_product_from_catalog');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="createFromCatalog" class="minimal" <?php echo set_checkbox('permission', 'createFromCatalog',  in_array('createFromCatalog', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="updateFromCatalog" class="minimal" <?php echo set_checkbox('permission', 'updateFromCatalog',  in_array('updateFromCatalog', $serialize_permission)) ?>></td>
                                                    <td> - </td>
                                                    <td> - </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                            <table class="table table-responsive">
                                                <thead>
                                                <tr>
                                                    <th style="width:60%"><?=$this->lang->line('application_permission');?> Única</th>
                                                    <th style="width:40%"><?=$this->lang->line('application_link');?></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td><span class="final"><?= $this->lang->line('application_add_on'); ?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#addOnPermSing" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="addOnPermSing" class="collapse">
                                                            <span class="text-black">
                                                                Adicionar serviço junto ao produto.
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="addOn" class="minimal" <?php echo set_checkbox('permission', 'addOn',  in_array('addOn', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_sync_published_sku');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#publicationPermSyncPublishedSku" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="publicationPermSyncPublishedSku" class="collapse">
                                                            <span class="text-black">
                                                                Realizar de para de skus já publicados.
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="syncPublishedSku" class="minimal" <?php echo set_checkbox('permission', 'syncPublishedSku',  in_array('syncPublishedSku', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_group_simple_sku');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#publicationPermGroupSimpleSku" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="publicationPermGroupSimpleSku" class="collapse">
                                                            <span class="text-black">
                                                                Realizar o agrupamento de produtos simples, tornando-os em um produto com variação.
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="groupSimpleSku" class="minimal" <?php echo set_checkbox('permission', 'groupSimpleSku',  in_array('groupSimpleSku', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_showcase_products_catalog');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#publicationPermSing1" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="publicationPermSing1" class="collapse">
                                                            <span class="text-black">
                                                                Tela para que o lojista escolha quais produtos irá vender
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="showcaseCatalog" class="minimal" <?php echo set_checkbox('permission', 'showcaseCatalog',  in_array('showcaseCatalog', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_change_crossdocking');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#publicationPermSing2" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="publicationPermSing2" class="collapse">
                                                            <span class="text-black">
                                                                Alterar prazo de expedição do produto
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="changeCrossdocking" class="minimal" <?php echo set_checkbox('permission', 'changeCrossdocking',  in_array('changeCrossdocking', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_disable_price_change');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#publicationPermSing3" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="publicationPermSing3" class="collapse">
                                                            <span class="text-black">
                                                                Alteração de preço
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="disablePrice" class="minimal" <?php echo set_checkbox('permission', 'disablePrice',  in_array('disablePrice', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_products_publish');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#publicationPermSing4" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="publicationPermSing4" class="collapse">
                                                            <span class="text-black">
                                                                Permite que o grupo publique produtos
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="doProductsPublish" class="minimal" <?php echo set_checkbox('permission', 'doProductsPublish',  in_array('doProductsPublish', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_enrich_product');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#publicationPermSing5" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="publicationPermSing5" class="collapse">
                                                            <span class="text-black">
                                                                Permite  liberar a tela de importar atributos , onde o seller sobe os atributos de forma massiva
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="enrichProduct" class="minimal" <?php echo set_checkbox('permission', 'enrichProduct',  in_array('enrichProduct', $serialize_permission)) ?>></td>
                                                </tr>
                                                <!--<tr hidden>
                                                    <td><span class="final"></?=$this->lang->line('application_productsmarketplace');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#publicationPermSing6" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="publicationPermSing6" class="collapse">
                                                            <span class="text-black"></span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="updateProductsMarketplace" class="minimal" </?php echo set_checkbox('permission', 'updateProductsMarketplace',  in_array('updateProductsMarketplace', $serialize_permission)) ?>></td>
                                                </tr>-->
                                                <!--<tr hidden="">
                                                    <td></?=$this->lang->line('application_manage_products_omnilogic_sent');?>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#publicationPermSing7" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="publicationPermSing7" class="collapse">
                                                            <span class="text-black"></span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="manageProductsOmnilogicSent" class="minimal" </?php echo set_checkbox('permission', 'manageProductsOmnilogicSent',  in_array('manageProductsOmnilogicSent', $serialize_permission)) ?>></td>
                                                </tr>-->
                                                <!---  Chines que faz aprovações produtos --->
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_products_approval');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#publicationPermSing8" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="publicationPermSing8" class="collapse">
                                                            <span class="text-black">
                                                                Apenas para o marketplace - Relacionado a curadoria para que além de ver os produtos, o usuário possa aprovar os produtos também
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="doProductsApproval" <?= set_checkbox('permission', 'doProductsApproval',  in_array('doProductsApproval', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('seller_campaign_creation');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#publicationPermSing9" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="publicationPermSing9" class="collapse">
                                                            <span class="text-black">
                                                                Seller pode criar campanhas
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="sellerCampaignCreation" class="minimal" <?php echo set_checkbox('permission', 'sellerCampaignCreation',  in_array('sellerCampaignCreation', $serialize_permission)) ?>></td>
                                                </tr>

                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_import_rd_skus');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="importRDskus" class="minimal" <?php echo set_checkbox('permission', 'importRDskus',  in_array('importRDskus', $serialize_permission)) ?>></td>
                                                </tr>

                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_approve_campaign_creation');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="approveCampaignCreation" class="minimal" <?php echo set_checkbox('permission', 'approveCampaignCreation',  in_array('approveCampaignCreation', $serialize_permission)) ?>></td>
                                                </tr>

                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="tab_2" class="tab-pane fade">
                                <div class="row">
                                    <div class="box-header">
                                        <h3 class="box-title"><?=$this->lang->line('application_orders');?></h3>
                                    </div>
                                    <div class="box-body row">
                                        <div class="form-group col-md-12 col-xs-10">
                                            <table class="table table-responsive">
                                                <thead>
                                                <tr>
                                                    <th style="width:60%"><?=$this->lang->line('application_permission');?></th>
                                                    <th style="width:10%"><?=$this->lang->line('application_create');?></th>
                                                    <th style="width:10%"><?=$this->lang->line('application_update');?></th>
                                                    <th style="width:10%"><?=$this->lang->line('application_view');?></th>
                                                    <th style="width:10%"><?=$this->lang->line('application_delete');?></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_orders');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#ordersMult1" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="ordersMult1" class="collapse">
                                                            <span class="text-black">
                                                                Permite gerenciar os pedidos do sistema
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createOrder" <?= set_checkbox('permission', 'createOrder',  in_array('createOrder', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateOrder" <?= set_checkbox('permission', 'updateOrder',  in_array('updateOrder', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewOrder" <?= set_checkbox('permission', 'viewOrder',  in_array('viewOrder', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteOrder" <?= set_checkbox('permission', 'deleteOrder',  in_array('deleteOrder', $serialize_permission)) ?>> <strong>Cancelar</strong></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_request_cancel');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#ordersMult2" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="ordersMult2" class="collapse">
                                                            <span class="text-black">
                                                                Permite que seja solicitado o cancelamento do pedido. Nesse processo, o marketplace precisa aprovar o cancelamento em seguida
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="createRequestCancelOrder"  class="minimal" <?=$serialize_permission && in_array('createRequestCancelOrder', $serialize_permission) ? 'checked' : '' ?>></td>
                                                    <td> - </td>
                                                    <td> - </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="deleteRequestCancelOrder"  class="minimal" <?=$serialize_permission && in_array('deleteRequestCancelOrder', $serialize_permission) ? 'checked' : '' ?>> <strong>Cancelar</strong></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_queue_orders');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#ordersMult3" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="ordersMult3" class="collapse">
                                                            <span class="text-black">
                                                                Reenvio para fila de fluxo os pedidos caso não tenha sido enviado para a integradora por algum motivo
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="createQueueOrderIntegration"  class="minimal" <?=$serialize_permission && in_array('createQueueOrderIntegration', $serialize_permission) ? 'checked' : '' ?>></td>
                                                    <td> - </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="viewQueueOrderIntegration"  class="minimal" <?=$serialize_permission && in_array('viewQueueOrderIntegration', $serialize_permission) ? 'checked' : '' ?>></td>
                                                    <td> - </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_return_order');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#ordersMult4" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="ordersMult4" class="collapse">
                                                            <span class="text-black">
                                                                Permite que seja criada uma devolução
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="createReturnOrder" class="minimal" <?= set_checkbox('permission', 'createReturnOrder', in_array('createReturnOrder', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="updateReturnOrder" class="minimal" <?= set_checkbox('permission', 'updateReturnOrder', in_array('updateReturnOrder', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="viewReturnOrder" class="minimal" <?= set_checkbox('permission', 'viewReturnOrder', in_array('viewReturnOrder', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="deleteReturnOrder" class="minimal" <?= set_checkbox('permission', 'deleteReturnOrder', in_array('deleteReturnOrder', $serialize_permission)) ?>></td>
                                                </tr>
                                                </tbody>
                                            </table>
                                            <table class="table table-responsive">
                                                <thead>
                                                <tr>
                                                    <th style="width:60%"><?=$this->lang->line('application_permission');?> Única</th>
                                                    <th style="width:40%"><?=$this->lang->line('application_link');?></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td><span class="final"><?= $this->lang->line('application_view_info_contact_user_order'); ?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#ordersGin1" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="ordersGin1" class="collapse">
                                                            <span class="text-black">
                                                                Permite que seja visualizado o contato do cliente no pedido
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="viewInfoContactUserOrder" class="minimal" <?= set_checkbox('permission', 'viewInfoContactUserOrder',  in_array('viewInfoContactUserOrder', $serialize_permission)) ?>></td>
                                                </tr>
                                                <!--<tr hidden>
                                                    <td></?=$this->lang->line('application_change_order_store');?> (Admin)
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#ordersGin2" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="ordersGin2" class="collapse">
                                                            <span class="text-black"></span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="changeOrderStore"  class="minimal" </?=$serialize_permission && in_array('changeOrderStore', $serialize_permission) ? 'checked' : '' ?>></td>
                                                </tr>-->
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_change_delivery_address');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#ordersGin3" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="ordersGin3" class="collapse">
                                                            <span class="text-black">
                                                                Permite que seja alterado o endereço de entrega
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="chageDeliveryAddress" class="minimal" <?php echo set_checkbox('permission', 'chageDeliveryAddress',  in_array('chageDeliveryAddress', $serialize_permission)) ?>></td>
                                                </tr>
                                                <!-- <tr hidden>
                                                    <td></?=$this->lang->line('application_order_mediation');?>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#ordersGin4" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="ordersGin4" class="collapse">
                                                            <span class="text-black"></span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="orderMediation" class="minimal" </?php echo set_checkbox('permission', 'orderMediation',  in_array('orderMediation', $serialize_permission)) ?>></td>
                                                </tr>-->
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_transaction_interactions');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#ordersGin5" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="ordersGin5" class="collapse">
                                                            <span class="text-black">
                                                                Exibe dentro de pedidos as atualizações de pagamento no Marketplace
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="transactionInteractionsOrder" class="minimal" <?php echo set_checkbox('permission', 'transactionInteractionsOrder',  in_array('transactionInteractionsOrder', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?= $this->lang->line('application_change_invoice'); ?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#ordersGin6" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="ordersGin6" class="collapse">
                                                            <span class="text-black">
                                                                Libera botão <b>Alterar Nota Fiscal</b> em pedido já faturado.
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="viewChangeInvoiceOrder" class="minimal" <?= set_checkbox('permission', 'viewChangeInvoiceOrder',  in_array('viewChangeInvoiceOrder', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?= $this->lang->line('application_orders_cancel_commission_charges'); ?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#ordersGin7" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="ordersGin7" class="collapse">
                                                            <span class="text-black">
                                                                Libera o botão de <b>Estornar a cobrança de comissão</b> em pedido cancelado.
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="updateOrdersCancelCommissionCharges" class="minimal" <?= set_checkbox('permission', 'updateOrdersCancelCommissionCharges',  in_array('updateOrdersCancelCommissionCharges', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?= $this->lang->line('application_partial_cancellation'); ?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#partialCancellationOrder" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="partialCancellationOrder" class="collapse">
                                                            <span class="text-black">
                                                                Realiza o cancelamento por itens do pedido.
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="partialCancellationOrder" class="minimal" <?= set_checkbox('permission', 'partialCancellationOrder',  in_array('partialCancellationOrder', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?= $this->lang->line('directly_order_as_send'); ?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#cadMult32" class="pull-right">
                                                            <i class="fa fa-chevron-down"></i>
                                                        </a>
                                                        <div id="cadMult32" class="collapse">
                                                            <span class="text-black">
                                                                Quando essa permissão estiver ativa será exibida uma tela para que o marketplace cadastre dados default para atualizar pedidos
                                                                diretamente para entregue quando o pedido já estiver entregue no ERP do seller.
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="checkbox" name="permission[]" value="sendOrderToDelivered" class="minimal"
                                                            <?= set_checkbox('permission', 'sendOrderToDelivered', in_array('sendOrderToDelivered', $serialize_permission)) ?>>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" data-search="tbl_logistica" id="tab_22">
                                <div class="row">
                                    <div class="box-header">
                                        <h3 class="box-title"><?=$this->lang->line('application_providers_finan_flow');?></h3>
                                    </div>
                                    <div class="box-body row">
                                        <div class="form-group col-md-12 col-xs-10">
                                            <table class="table table-responsive">
                                                <thead>
                                                <tr>
                                                    <th style="width:60%"><?=$this->lang->line('application_permission');?></th>
                                                    <th style="width:10%"><?=$this->lang->line('application_create');?></th>
                                                    <th style="width:10%"><?=$this->lang->line('application_update');?></th>
                                                    <th style="width:10%"><?=$this->lang->line('application_view');?></th>
                                                    <th style="width:10%"><?=$this->lang->line('application_delete');?></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_extract');?></span><span class="final"></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialMult1" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialMult1" class="collapse">
                                                            <span class="text-black">
                                                            Visualização dos pedidos da sua loja, extrato das vendas

                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createExtract" <?php if($serialize_permission) {
                                                            if(in_array('createExtract', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateExtract" <?php if($serialize_permission) {
                                                            if(in_array('updateExtract', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewExtract" <?php if($serialize_permission) {
                                                            if(in_array('viewExtract', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteExtract" <?php if($serialize_permission) {
                                                            if(in_array('deleteExtract', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_parameter_payment_forecast');?></span><span class="final"></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialMult2" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialMult2" class="collapse">
                                                            <span class="text-black">
                                                            Permite consultar no sistema todos os pedidos que tem a data de pagamento para o mês corrente e para o mês seguinte, usando a data atual como base dessas contas
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createPaymentForecast" <?php if($serialize_permission) {
                                                            if(in_array('createPaymentForecast', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updatePaymentForecast" <?php if($serialize_permission) {
                                                            if(in_array('updatePaymentForecast', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewPaymentForecast" <?php if($serialize_permission) {
                                                            if(in_array('viewPaymentForecast', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deletePaymentForecast" <?php if($serialize_permission) {
                                                            if(in_array('deletePaymentForecast', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_parameter_mktplace_ciclos');?></span><span class="final"></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialMult3" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialMult3" class="collapse">
                                                            <span class="text-black">
                                                            Permite que inclua ciclos de pagamentos
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createParamktplaceCiclo" <?php if($serialize_permission) {
                                                            if(in_array('createParamktplaceCiclo', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateParamktplaceCiclo" <?php if($serialize_permission) {
                                                            if(in_array('updateParamktplaceCiclo', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewParammktplaceCiclo" <?php if($serialize_permission) {
                                                            if(in_array('viewParammktplaceCiclo', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteParamktplaceCiclo" <?php if($serialize_permission) {
                                                            if(in_array('deleteParamktplaceCiclo', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                </tr>

                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_parameter_mktplace_ciclos_fiscal');?></span><span class="final"></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialMult4" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialMult4" class="collapse">
                                                            <span class="text-black">
                                                            Permite que inclua/edite/exclua ciclos fiscais de pagamentos
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createParamktplaceFiscal" <?php if($serialize_permission) {
                                                            if(in_array('createParamktplaceFiscal', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateParamktplaceFiscal" <?php if($serialize_permission) {
                                                            if(in_array('updateParamktplaceFiscal', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewParammktplaceFiscal" <?php if($serialize_permission) {
                                                            if(in_array('viewParammktplaceFiscal', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteParamktplaceFiscal" <?php if($serialize_permission) {
                                                            if(in_array('deleteParamktplaceFiscal', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                </tr>
                                                <!--<tr hidden>
                                                    <td>
                                                        <span class="final"></?=$this->lang->line('application_parameter_providers_ciclos');?></span><span class="final"></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialMult4" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialMult4" class="collapse">
                                                            <span class="text-black">

                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createParamktplaceCicloTransp" <//?php if($serialize_permission) {
                                                            if(in_array('createParamktplaceCicloTransp', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateParamktplaceCicloTransp" </?php if($serialize_permission) {
                                                            if(in_array('updateParamktplaceCicloTransp', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewParammktplaceCicloTransp" </?php if($serialize_permission) {
                                                            if(in_array('viewParammktplaceCicloTransp', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteParamktplaceCicloTransp" </?php if($serialize_permission) {
                                                            if(in_array('deleteParamktplaceCicloTransp', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                </tr>-->
                                                <!--<tr hidden>
                                                    <td>
                                                        <span class="final"></?=$this->lang->line('application_billets');?></span><span class="final"></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialMult5" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialMult5" class="collapse">
                                                            <span class="text-black">

                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createBillet" </?php if($serialize_permission) {
                                                            if(in_array('createBillet', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateBillet" </?php if($serialize_permission) {
                                                            if(in_array('updateBillet', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewBillet" </?php if($serialize_permission) {
                                                            if(in_array('viewBillet', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteBillet" </?php if($serialize_permission) {
                                                            if(in_array('deleteBillet', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                </tr>-->
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_payments');?></span><span class="final"></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createPayment" <?php if($serialize_permission) {
                                                            if(in_array('createPayment', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updatePayment" <?php if($serialize_permission) {
                                                            if(in_array('updatePayment', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewPayment" <?php if($serialize_permission) {
                                                            if(in_array('viewPayment', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deletePayment" <?php if($serialize_permission) {
                                                            if(in_array('deletePayment', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                </tr>
                                                <!--<tr hidden>
                                                    <td>
                                                        <span class="final"></?=$this->lang->line('application_iugu_panel');?></span><span class="final"></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialMult7" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialMult7" class="collapse">
                                                            <span class="text-black">

                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createIugu" </?php if($serialize_permission) {
                                                            if(in_array('createIugu', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateIugu" </?php if($serialize_permission) {
                                                            if(in_array('updateIugu', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewIugu" </?php if($serialize_permission) {
                                                            if(in_array('viewIugu', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteIugu" </?php if($serialize_permission) {
                                                            if(in_array('deleteIugu', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                </tr>-->
                                                <!--<tr hidden>
                                                    <td>
                                                        <span class="final"></?=$this->lang->line('application_log_iugu_view');?></span><span class="final"></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialMult8" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialMult8" class="collapse">
                                                            <span class="text-black">

                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createLogIUGU" </?php if($serialize_permission) {
                                                            if(in_array('createLogIUGU', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateLogIUGU" </?php if($serialize_permission) {
                                                            if(in_array('updateLogIUGU', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td>
                                                        <input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewLogIUGU" </?php if($serialize_permission) {
                                                            if(in_array('viewLogIUGU', $serialize_permission)) { echo "checked"; }
                                                        } ?>>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteLogIUGU" </?php if($serialize_permission) {
                                                            if(in_array('deleteLogIUGU', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                </tr>-->
                                                <!-- braun -->
                                                <!--<tr hidden>
                                                    <td>
                                                        <span class="final"></?=$this->lang->line('iugu_permission_title');?></span><span class="final"></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialMult9" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialMult9" class="collapse">
                                                            <span class="text-black">

                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createIuguPlans" </?php if($serialize_permission) {
                                                            if(in_array('createIuguPlans', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateIuguPlans" </?php if($serialize_permission) {
                                                            if(in_array('updateIuguPlans', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td>
                                                        <input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewIuguPlans" </?php if($serialize_permission) {
                                                            if(in_array('viewIuguPlans', $serialize_permission)) { echo "checked"; }
                                                        } ?>>
                                                    </td>
                                                    <td></td>
                                                </tr>-->
                                                <!--<tr hidden>
                                                    <td>
                                                        <span class="final"></?=$this->lang->line('application_extract_partner');?></span><span class="final"></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialMult10" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialMult10" class="collapse">
                                                            <span class="text-black">

                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createExtractParceiro" </?php if($serialize_permission) {
                                                            if(in_array('createExtractParceiro', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateExtractParceiro" </?php if($serialize_permission) {
                                                            if(in_array('updateExtractParceiro', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewExtractParceiro" </?php if($serialize_permission) {
                                                            if(in_array('viewExtractParceiro', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteExtractParceiro" </?php if($serialize_permission) {
                                                            if(in_array('deleteExtractParceiro', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                </tr>-->
                                                <!--<tr hidden>
                                                    <td>
                                                        <span class="final"></?=$this->lang->line('application_conciliacao');?></span><span class="final"></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialMult11" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialMult11" class="collapse">
                                                            <span class="text-black">

                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createBilletConcil" </?php if($serialize_permission) {
                                                            if(in_array('createBilletConcil', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateBilletConcil" </?php if($serialize_permission) {
                                                            if(in_array('updateBilletConcil', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewBilletConcil" </?php if($serialize_permission) {
                                                            if(in_array('viewBilletConcil', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteBilletConcil" </?php if($serialize_permission) {
                                                            if(in_array('deleteBilletConcil', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                </tr>-->
                                                <!-- <tr hidden>
                                                    <td>
                                                        <span class="final"></?=$this->lang->line('application_conciliacao_transp');?></span><span class="final"></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialMult12" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialMult12" class="collapse">
                                                            <span class="text-black">

                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createBilletTransp" </?php if($serialize_permission) {
                                                            if(in_array('createBilletTransp', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateBilletTransp" </?php if($serialize_permission) {
                                                            if(in_array('updateBilletTransp', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewBilletTransp" </?php if($serialize_permission) {
                                                            if(in_array('viewBilletTransp', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteBilletTransp" </?php if($serialize_permission) {
                                                            if(in_array('deleteBilletTransp', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                </tr> -->
                                                <!--<tr hidden>
                                                    <td>
                                                        <span class="final"></?=$this->lang->line('application_parameter_payment_forecast_concilia');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialMult13" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialMult13" class="collapse">
                                                            <span class="text-black">

                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createPaymentForcastConcil" </?php if($serialize_permission) {
                                                            if(in_array('createPaymentForcastConcil', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updatePaymentForcastConcil" </?php if($serialize_permission) {
                                                            if(in_array('updatePaymentForcastConcil', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewPaymentForcastConcil" </?php if($serialize_permission) {
                                                            if(in_array('viewPaymentForcastConcil', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deletePaymentForcastConcil" </?php if($serialize_permission) {
                                                            if(in_array('deletePaymentForcastConcil', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                </tr>-->
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_panel_fiscal');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialMult14" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialMult14" class="collapse">
                                                            <span class="text-black">
                                                            Incluir uma Nota fiscal de serviço para o seller podendo ser um link ou um arquivo PDF, de acordo com as configurações do sellercenter
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createNFS" <?php if($serialize_permission) {
                                                            if(in_array('createNFS', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateNFS" <?php if($serialize_permission) {
                                                            if(in_array('updateNFS', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewNFS" <?php if($serialize_permission) {
                                                            if(in_array('viewNFS', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deletetNFS" <?php if($serialize_permission) {
                                                            if(in_array('deletetNFS', $serialize_permission)) { echo "checked"; }
                                                        } ?></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_legal_panel');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialMult15" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialMult15" class="collapse">
                                                            <span class="text-black">
                                                            Incluir uma penalidade a alguma loja do sellercenter atrelada a algum pedido, em que é preciso descontar o valor de um seller
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createLegalPanel" <?= set_checkbox('permission', 'createLegalPanel',  in_array('createLegalPanel', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateLegalPanel" <?= set_checkbox('permission', 'updateLegalPanel',  in_array('updateLegalPanel', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewLegalPanel" <?= set_checkbox('permission', 'viewLegalPanel',  in_array('viewLegalPanel', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteLegalPanel" <?= set_checkbox('permission', 'deleteLegalPanel',  in_array('deleteLegalPanel', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_legal_panel_fiscal');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialMultFiscal" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialMultFiscal" class="collapse">
                                                            <span class="text-black">
                                                            Incluir uma penalidade Fiscal a alguma loja do sellercenter atrelada a algum pedido, em que é preciso descontar o valor de um seller
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createLegalPanelFiscal" <?= set_checkbox('permission', 'createLegalPanelFiscal',  in_array('createLegalPanelFiscal', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateLegalPanelFiscal" <?= set_checkbox('permission', 'updateLegalPanelFiscal',  in_array('updateLegalPanelFiscal', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewLegalPanelFiscal" <?= set_checkbox('permission', 'viewLegalPanelFiscal',  in_array('viewLegalPanelFiscal', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteLegalPanelFiscal" <?= set_checkbox('permission', 'deleteLegalPanelFiscal',  in_array('deleteLegalPanelFiscal', $serialize_permission)) ?>></td>
                                                </tr>
                                                <!--<tr hidden>
                                                    <td>
                                                        <span class="final"></?=$this->lang->line('application_discount_worksheet');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialMult16" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialMult16" class="collapse">
                                                            <span class="text-black">

                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createDiscountWorksheet" </?php if($serialize_permission) {
                                                            if(in_array('createDiscountWorksheet', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateDiscountWorksheet" </?php if($serialize_permission) {
                                                            if(in_array('updateDiscountWorksheet', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewDiscountWorksheet" </?php if($serialize_permission) {
                                                            if(in_array('viewDiscountWorksheet', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deletetDiscountWorksheet" </?php if($serialize_permission) {
                                                            if(in_array('deletetDiscountWorksheet', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                </tr>-->
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_payment_gateway_settings');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialMult17" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialMult17" class="collapse">
                                                            <span class="text-black">
                                                                Inserir parametros - financeiro
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createPaymentGatewayConfig" <?= set_checkbox('permission', 'createPaymentGatewayConfig',  in_array('createPaymentGatewayConfig', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updatePaymentGatewayConfig" <?= set_checkbox('permission', 'updatePaymentGatewayConfig',  in_array('updatePaymentGatewayConfig', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewPaymentGatewayConfig" <?= set_checkbox('permission', 'viewPaymentGatewayConfig',  in_array('viewPaymentGatewayConfig', $serialize_permission)) ?>></td>
                                                    <td> - </td>
                                                </tr>
                                                <!--<tr hidden>
                                                    <td>
                                                        <span class="final"></?=$this->lang->line('application_receivables');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialMult18" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialMult18" class="collapse">
                                                            <span class="text-black">

                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createReceivables" </?= set_checkbox('permission', 'createReceivables',  in_array('createReceivables', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateReceivables" </?= set_checkbox('permission', 'updateReceivables',  in_array('updateReceivables', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewReceivables" </?= set_checkbox('permission', 'viewReceivables',  in_array('viewReceivables', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteReceivables" </?= set_checkbox('permission', 'deleteReceivables',  in_array('deleteReceivables', $serialize_permission)) ?>></td>
                                                </tr>-->
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_setting_up_return_chargeback_rules');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialMult19" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialMult19" class="collapse">
                                                            <span class="text-black">
                                                                Permite incluir uma nova regra de estorno
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createSettingChargebackRule" <?= set_checkbox('permission', 'createSettingChargebackRule',  in_array('createSettingChargebackRule', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateSettingChargebackRule" <?= set_checkbox('permission', 'updateSettingChargebackRule',  in_array('updateSettingChargebackRule', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewSettingChargebackRule" <?= set_checkbox('permission', 'viewSettingChargebackRule',  in_array('viewSettingChargebackRule', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteSettingChargebackRule" <?= set_checkbox('permission', 'deleteSettingChargebackRule',  in_array('deleteSettingChargebackRule', $serialize_permission)) ?>></td>
                                                </tr>
                                                <?php
                                                if ($this->model_settings->getValueIfAtiveByName('allow_hierarchy_comission')){
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <span class="final"><?=$this->lang->line('application_hierarchy_comission');?></span>
                                                            <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialMult20" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                            <div id="financialMult20" class="collapse">
                                                                <span class="text-black">
                                                                    Cadastro de comissão por hierarquia
                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createHierarchyComission" <?= set_checkbox('permission', 'createHierarchyComission',  in_array('createHierarchyComission', $serialize_permission)) ?>></td>
                                                        <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateHierarchyComission" <?= set_checkbox('permission', 'updateHierarchyComission',  in_array('updateHierarchyComission', $serialize_permission)) ?>></td>
                                                        <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewHierarchyComission" <?= set_checkbox('permission', 'viewHierarchyComission',  in_array('viewHierarchyComission', $serialize_permission)) ?>></td>
                                                        <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteHierarchyComission" <?= set_checkbox('permission', 'deleteHierarchyComission',  in_array('deleteHierarchyComission', $serialize_permission)) ?>></td>
                                                    </tr>
                                                <?php
                                                }
                                                ?>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_payment_anticipation_management');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialMult20" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialMult20" class="collapse">
                                                            <span class="text-black">
                                                            Libera a possibilidade do seller solicitar a antecipação de pagamento de determinado pedido
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td colspan="2">
                                                        <input type="checkbox" name="permission[]" id="permission" class="minimal" value="createAnticipationSimulation" <?= set_checkbox('permission', 'createAnticipationSimulation',  in_array('createAnticipationSimulation', $serialize_permission)) ?>>
                                                        <strong><?php echo lang('application_payment_anticipation_management_create_accept_reject_simulation'); ?></strong>
                                                    </td>
                                                    <td colspan="2">
                                                        <input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewAnticipationSimulation" <?= set_checkbox('permission', 'viewAnticipationSimulation',  in_array('viewAnticipationSimulation', $serialize_permission)) ?>>
                                                        <strong><?php echo lang('application_payment_anticipation_management_view'); ?></strong>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                            <table class="table table-responsive">
                                                <thead>
                                                <tr>
                                                    <th style="width:60%"><?=$this->lang->line('application_permission');?> Única</th>
                                                    <th style="width:40%"><?=$this->lang->line('application_link');?></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_reports');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialSing1" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialSing1" class="collapse">
                                                            <span class="text-black">
                                                            Permite visualização a tela de relatórios sobre o desempenho do seller
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewReports" <?= set_checkbox('permission', 'viewReports',  in_array('viewReports', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_management_report');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewManagementReport" <?= set_checkbox('permission', 'viewManagementReport',  in_array('viewManagementReport', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_biller_module');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialSing3" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialSing3" class="collapse">
                                                            <span class="text-black">
                                                                Integração com o erp tiny apenas para faturar pedidos
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="createInvoice" class="minimal" <?php echo set_checkbox('permission', 'createInvoice',  in_array('createInvoice', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_payment_release');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialSing4" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialSing4" class="collapse">
                                                            <span class="text-black">
                                                                Permite criar conciliação dos pedidos realizados de acordo com o ciclo de pagamento
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="checkbox" name="permission[]" id="permission" value="createPaymentRelease" class="minimal" <?php echo set_checkbox('permission', 'createPaymentRelease',  in_array('createPaymentRelease', $serialize_permission)) ?>>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_payment_release_fiscal');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialFiscalSing4" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialFiscalSing4" class="collapse">
                                                            <span class="text-black">
                                                                Permite criar Liberação de Pagamento Fiscal dos pedidos realizados de acordo com o ciclo de pagamento fiscal
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="checkbox" name="permission[]" id="permission" value="createPaymentReleaseFiscal" class="minimal" <?php echo set_checkbox('permission', 'createPaymentReleaseFiscal',  in_array('createPaymentReleaseFiscal', $serialize_permission)) ?>>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_payment_report');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialSing5" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialSing5" class="collapse">
                                                            <span class="text-black">
                                                            Libera a visualização total do repasse, reembolsos e descontos por loja
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="createPaymentReport" class="minimal" <?php echo set_checkbox('permission', 'createPaymentReport',  in_array('createPaymentReport', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_transfer_report');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialSing6" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialSing6" class="collapse">
                                                            <span class="text-black">
                                                                É um pdf de todas as antecipações solicitadas
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="checkbox" name="permission[]" id="permission" value="createTransferReport" class="minimal" <?php echo set_checkbox('permission', 'createTransferReport',  in_array('createTransferReport', $serialize_permission)) ?>>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_external_antecipation');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialSing7" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialSing7" class="collapse">
                                                            <span class="text-black">
                                                                é um pdf de todas as antecipações solicitadas
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="checkbox" name="permission[]" id="permission" class="minimal" value="externalAntecipation" <?php if($serialize_permission) {
                                                            if(in_array('externalAntecipation', $serialize_permission)) { echo "checked"; }
                                                        } ?>>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_balances_transfers');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="balanceTransfers" class="minimal" <?php echo set_checkbox('permission', 'balanceTransfers',  in_array('balanceTransfers', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_transfer_anticipation');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialSing9" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialSing9" class="collapse">
                                                            <span class="text-black">

                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="transferAnticipationRelease" class="minimal" <?php echo set_checkbox('permission', 'transferAnticipationRelease',  in_array('transferAnticipationRelease', $serialize_permission)) ?>></td>
                                                </tr>
                                                <?php if (in_array($gatewayCode, [Model_gateway::TUNA])): ?>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_refund_order_value');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#financialSing10" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="financialSing10" class="collapse">
                                                            <span class="text-black">

                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="refundOrderValue" class="minimal" <?php echo set_checkbox('permission', 'refundOrderValue',  in_array('refundOrderValue', $serialize_permission)) ?>></td>
                                                </tr>
                                                <?php endif ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" data-search="tbl_logistica" id="tab_3">
                                <div class="row">
                                    <div class="box-header">
                                        <h3 class="box-title"><?=$this->lang->line('application_integrations');?></h3>
                                    </div>
                                    <div class="box-body row">
                                        <div class="form-group col-md-12 col-xs-10">
                                            <table class="table table-responsive">
                                                <thead>
                                                <tr>
                                                    <th style="width:60%"><?=$this->lang->line('application_permission');?></th>
                                                    <th style="width:10%"><?=$this->lang->line('application_create');?></th>
                                                    <th style="width:10%"><?=$this->lang->line('application_update');?></th>
                                                    <th style="width:10%"><?=$this->lang->line('application_view');?></th>
                                                    <th style="width:10%"><?=$this->lang->line('application_delete');?></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_integrations');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#integrationMult1" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="integrationMult1" class="collapse">
                                                            <span class="text-black">
                                                                Permite que seja incluída uma nova integração com erps e plataformas de ecommerce
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createIntegrations" <?= set_checkbox('permission', 'createIntegrations',  in_array('createIntegrations', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateIntegrations" <?= set_checkbox('permission', 'updateIntegrations',  in_array('updateIntegrations', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewIntegrations" <?= set_checkbox('permission', 'viewIntegrations',  in_array('viewIntegrations', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteIntegrations" <?= set_checkbox('permission', 'deleteIntegrations',  in_array('deleteIntegrations', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_integration_data_normalization');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#integrationMult2" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="integrationMult2" class="collapse">
                                                            <span class="text-black">
                                                                Tela para que os lojistas possam fazer o de/para dos dados do seu erp com o marketplace para que possa enviar os produtos sem editar o cadastro
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createIntegrationAttributeMap" <?= set_checkbox('permission', 'createIntegrationAttributeMap',  in_array('createIntegrationAttributeMap', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateIntegrationAttributeMap" <?= set_checkbox('permission', 'updateIntegrationAttributeMap',  in_array('updateIntegrationAttributeMap', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewIntegrationAttributeMap" <?= set_checkbox('permission', 'viewIntegrationAttributeMap',  in_array('viewIntegrationAttributeMap', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteIntegrationAttributeMap" <?= set_checkbox('permission', 'deleteIntegrationAttributeMap',  in_array('deleteIntegrationAttributeMap', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_runmarketplaces');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#integrationMult3" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="integrationMult3" class="collapse">
                                                            <span class="text-black">
                                                                Permite que envie uma loja pro MKTP novo
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="productsMarketplace" <?= set_checkbox('permission', 'productsMarketplace',  in_array('productsMarketplace', $serialize_permission)) ?>></td>
                                                    <td> - </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewMarketplace" <?= set_checkbox('permission', 'viewMarketplace',  in_array('viewMarketplace', $serialize_permission)) ?>></td>
                                                    <td> - </td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_parameter_mktplace');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createParamktplace" <?= set_checkbox('permission', 'createParamktplace',  in_array('createParamktplace', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateParamktplace" <?= set_checkbox('permission', 'updateParamktplace',  in_array('updateParamktplace', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewParammktplace" <?= set_checkbox('permission', 'viewParammktplace',  in_array('viewParammktplace', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteParamktplace" <?= set_checkbox('permission', 'deleteParamktplace',  in_array('deleteParamktplace', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_adm_troubleticket_mktplace');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createTTMkt" <?php if($serialize_permission) {
                                                            if(in_array('createTTMkt', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateTTMkt" <?php if($serialize_permission) {
                                                            if(in_array('updateTTMkt', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewTTMkt" <?php if($serialize_permission) {
                                                            if(in_array('viewTTMkt', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteTTMkt" <?php if($serialize_permission) {
                                                            if(in_array('deleteTTMkt', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                </tr>
                                                <tr hidden="">
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_shopify_requests');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#integrationMult6" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="integrationMult6" class="collapse">
                                                            <span class="text-black"></span>
                                                        </div>
                                                    </td>
                                                    <td> - </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateShopifyRequests" <?php if($serialize_permission) {
                                                            if(in_array('updateShopifyRequests', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewShopifyRequests" <?php if($serialize_permission) {
                                                            if(in_array('viewShopifyRequests', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteShopifyRequests" <?php if($serialize_permission) {
                                                            if(in_array('deleteShopifyRequests', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                </tr>

                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_manage_integration_erp');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#integrationMult7" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="integrationMult7" class="collapse">
                                                            <span class="text-black">
                                                                Tela para adicionar novas integrações que sejam desenvolvidas via API, ajuste de logo e nome de integrações existentes além de informar documentos de apoio.
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createManageIntegrationErp" <?= set_checkbox('permission', 'createManageIntegrationErp',  in_array('createManageIntegrationErp', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateManageIntegrationErp" <?= set_checkbox('permission', 'updateManageIntegrationErp',  in_array('updateManageIntegrationErp', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewManageIntegrationErp" <?= set_checkbox('permission', 'viewManageIntegrationErp',  in_array('viewManageIntegrationErp', $serialize_permission)) ?>></td>
                                                    <td>-</td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('answer_odoo_calls');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#integrationMult8" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="integrationMult8" class="collapse">
                                                                <span class="text-black">
                                                                    Permite que o usuario atenda chamados da plataforma Odoo dentro do seller center.
                                                                </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createOdooService" <?= set_checkbox('permission', 'createOdooService',  in_array('createOdooService', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateOdooService" <?= set_checkbox('permission', 'updateOdooService',  in_array('updateOdooService', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewOdooService" <?= set_checkbox('permission', 'viewOdooService',  in_array('viewOdooService', $serialize_permission)) ?>></td>
                                                </tr>
                                                </tbody>
                                            </table>
                                            <table class="table table-responsive">
                                                <thead>
                                                <tr>
                                                    <th style="width:60%"><?=$this->lang->line('application_permission');?> Única</th>
                                                    <th style="width:40%"><?=$this->lang->line('application_link');?></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_marketplaces_integrations');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="marketplaces_integrations" class="minimal" <?php echo set_checkbox('permission', 'marketplaces_integrations',  in_array('marketplaces_integrations', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_notification_center');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="notification_center" class="minimal" <?php echo set_checkbox('permission', 'notification_center',  in_array('notification_center', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_b2b_integration_via');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#integrationMult9" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="integrationMult9" class="collapse">
                                                            <span class="text-black">
                                                                Integração com a plataforma de B2B do marketplace Via
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="b2b_integration_via" class="minimal" <?php echo set_checkbox('permission', 'b2b_integration_via',  in_array('b2b_integration_via', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('config_integration_odoo');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="config_integration_odoo" class="minimal" <?php echo set_checkbox('permission', 'config_integration_odoo',  in_array('config_integration_odoo', $serialize_permission)) ?>></td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" data-search="tbl_logistica" id="tab_4">
                                <div class="row">
                                    <div class="box-header">
                                        <h3 class="box-title"><?=$this->lang->line('application_logistics');?></h3>
                                    </div>
                                    <div class="box-body row">
                                        <div class="form-group col-md-12 col-xs-10">
                                            <table class="table table-responsive">
                                                <thead>
                                                <tr>
                                                    <th style="width:60%"><?=$this->lang->line('application_permission');?></th>
                                                    <th style="width:10%"><?=$this->lang->line('application_create');?></th>
                                                    <th style="width:10%"><?=$this->lang->line('application_update');?></th>
                                                    <th style="width:10%"><?=$this->lang->line('application_view');?></th>
                                                    <th style="width:10%"><?=$this->lang->line('application_delete');?></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_logistics');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#logistcMult1" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="logistcMult1" class="collapse">
                                                            <span class="text-black">
                                                                Disponibilizar o menu de logistica do sistema
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>-</td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="updateLogistics" class="minimal" <?php echo set_checkbox('permission', 'updateLogistics',  in_array('updateLogistics', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="viewLogistics" class="minimal" <?php echo set_checkbox('permission', 'viewLogistics',  in_array('viewLogistics', $serialize_permission)) ?>></td>
                                                    <td>-</td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_integration_logistic');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#logistcMult2" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="logistcMult2" class="collapse">
                                                            <span class="text-black">
                                                                Permite que sejam incluídas e gerenciadas as integrações logisitcas do sistema
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="createIntegrationLogistic" class="minimal" <?php echo set_checkbox('permission', 'createIntegrationLogistic', in_array('createIntegrationLogistic', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="updateIntegrationLogistic" class="minimal" <?php echo set_checkbox('permission', 'updateIntegrationLogistic', in_array('updateIntegrationLogistic', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="viewIntegrationLogistic" class="minimal" <?php echo set_checkbox('permission', 'viewIntegrationLogistic', in_array('viewIntegrationLogistic', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="deleteIntegrationLogistic" class="minimal" <?php echo set_checkbox('permission', 'deleteIntegrationLogistic', in_array('deleteIntegrationLogistic', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_carrier_registration');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#logistcMult3" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="logistcMult3" class="collapse">
                                                            <span class="text-black">
                                                                Permite que sejam incluídas e gerenciadas as transportadoras
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="createCarrierRegistration" class="minimal" <?php echo set_checkbox('permission', 'createCarrierRegistration', in_array('createCarrierRegistration', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="updateCarrierRegistration" class="minimal" <?php echo set_checkbox('permission', 'updateCarrierRegistration', in_array('updateCarrierRegistration', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="viewCarrierRegistration" class="minimal" <?php echo set_checkbox('permission', 'viewCarrierRegistration', in_array('viewCarrierRegistration', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="deleteCarrierRegistration" class="minimal" <?php echo set_checkbox('permission', 'deleteCarrierRegistration', in_array('deleteCarrierRegistration', $serialize_permission)) ?>></td>
                                                </tr>
                                                <!--</?php /* ?>
                      <tr>
                        <td></?=$this->lang->line('application_logistic_promotion');?></td>
                        <td><input type="checkbox" name="permission[]" id="permission" value="createPromotionsLogistic" class="minimal" </?php echo set_checkbox('permission', 'createPromotionsLogistic', in_array('createPromotionsLogistic', $serialize_permission)) ?>></td>
                        <td><input type="checkbox" name="permission[]" id="permission" value="updatePromotionsLogistic" class="minimal" </?php echo set_checkbox('permission', 'updatePromotionsLogistic', in_array('updatePromotionsLogistic', $serialize_permission)) ?>></td>
                        <td><input type="checkbox" name="permission[]" id="permission" value="viewPromotionsLogistic" class="minimal" </?php echo set_checkbox('permission', 'viewPromotionsLogistic', in_array('viewPromotionsLogistic', $serialize_permission)) ?>></td>
                        <td><input type="checkbox" name="permission[]" id="permission" value="deletePromotionsLogistic" class="minimal" </?php echo set_checkbox('permission', 'deletePromotionsLogistic', in_array('deletePromotionsLogistic', $serialize_permission)) ?>></td>
                      </tr>
                      </?php  ?>-->
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_manage rules');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#logistcMult4" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="logistcMult4" class="collapse">
                                                            <span class="text-black">
                                                                Permite que sejam incluídas e gerenciadas as regras de leilão do sistema
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="createAuctionRules" class="minimal" <?php echo set_checkbox('permission', 'createAuctionRules', in_array('createAuctionRules', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="updateAuctionRules" class="minimal" <?php echo set_checkbox('permission', 'updateAuctionRules', in_array('updateAuctionRules', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="viewAuctionRules" class="minimal" <?php echo set_checkbox('permission', 'viewAuctionRules', in_array('viewAuctionRules', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="deleteAuctionRules" class="minimal" <?php echo set_checkbox('permission', 'deleteAuctionRules', in_array('deleteAuctionRules', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_tracking_order');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#logistcMult5" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="logistcMult5" class="collapse">
                                                            <span class="text-black">
                                                                Permite que seja incluída frete a contratar manualmente
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="createTrackingOrder" class="minimal" <?= set_checkbox('permission', 'createTrackingOrder', in_array('createTrackingOrder', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="updateTrackingOrder" class="minimal" <?= set_checkbox('permission', 'updateTrackingOrder', in_array('updateTrackingOrder', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="viewTrackingOrder" class="minimal" <?= set_checkbox('permission', 'viewTrackingOrder', in_array('viewTrackingOrder', $serialize_permission)) ?>></td>
                                                    <td> - </td>
                                                </tr>
                                                <?php if ($sellerCenter != 'conectala') { ?>
                                                    <tr>
                                                        <td><span class="final"><?=$this->lang->line('application_tracking_custom'); ?></span>
                                                            <a href="javascript:void(0)" data-toggle="collapse" data-target="#logistcMult6" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                            <div id="logistcMult6" class="collapse">
                                                                <span class="text-black">

                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td> - </td>
                                                        <td><input type="checkbox" name="permission[]" id="permission" value="updateTrackingPage" class="minimal" <?= set_checkbox('permission', 'updateTrackingPage', in_array('updateTrackingPage', $serialize_permission)) ?>></td>
                                                        <td><input type="checkbox" name="permission[]" id="permission" value="viewTrackingPage" class="minimal" <?= set_checkbox('permission', 'viewTrackingPage', in_array('viewTrackingPage', $serialize_permission)) ?>></td>
                                                        <td> - </td>
                                                    </tr>
                                                <?php } ?>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_shipping_pricing');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#logistcMult7" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="logistcMult7" class="collapse">
                                                            <span class="text-black">
                                                                Permite incluir valores a mais no valor final do frete
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="createPricingRules" class="minimal" <?php echo set_checkbox('permission', 'createPricingRules', in_array('createPricingRules', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="updatePricingRules" class="minimal" <?php echo set_checkbox('permission', 'updatePricingRules', in_array('updatePricingRules', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="viewPricingRules" class="minimal" <?php echo set_checkbox('permission', 'viewPricingRules', in_array('viewPricingRules', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="deletePricingRules" class="minimal" <?php echo set_checkbox('permission', 'deletePricingRules', in_array('deletePricingRules', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final"><?=$this->lang->line('application_pickup_point');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#logistcMult8" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="logistcMult8" class="collapse">
                                                            <span class="text-black">
                                                                Permite criar pontos de retirada para que, o consumidor final escolha onde deseja retirar.
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="createPickUpPoint" class="minimal" <?php echo set_checkbox('permission', 'createPickUpPoint', in_array('createPickUpPoint', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="updatePickUpPoint" class="minimal" <?php echo set_checkbox('permission', 'updatePickUpPoint', in_array('updatePickUpPoint', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="viewPickUpPoint" class="minimal" <?php echo set_checkbox('permission', 'viewPickUpPoint', in_array('viewPickUpPoint', $serialize_permission)) ?>></td>
                                                    <td> - </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                            <table class="table table-responsive">
                                                <thead>
                                                <tr>
                                                    <th style="width:60%"><?=$this->lang->line('application_permission');?> Única</th>
                                                    <th style="width:40%"><?=$this->lang->line('application_link');?></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td><?=$this->lang->line('application_send_freight_to_hire');?></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="sendFreightToHire" <?= set_checkbox('permission', 'sendFreightToHire',  in_array('sendFreightToHire', $serialize_permission)) ?>></td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" data-search="tbl_logistica" id="tab_5">
                                <div class="row">
                                    <div class="box-header">
                                        <h3 class="box-title"><?=$this->lang->line('application_register');?></h3>
                                    </div>
                                    <div class="box-body row">
                                        <div class="form-group col-md-12 col-xs-10">
                                            <table class="table table-responsive">
                                                <thead>
                                                <tr>
                                                    <th style="width:60%"><?=$this->lang->line('application_permission');?></th>
                                                    <th style="width:10%"><?=$this->lang->line('application_create');?></th>
                                                    <th style="width:10%"><?=$this->lang->line('application_update');?></th>
                                                    <th style="width:10%"><?=$this->lang->line('application_view');?></th>
                                                    <th style="width:10%"><?=$this->lang->line('application_delete');?></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_companies');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createCompany" <?= set_checkbox('permission', 'createCompany',  in_array('createCompany', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateCompany" <?= set_checkbox('permission', 'updateCompany',  in_array('updateCompany', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewCompany" <?= set_checkbox('permission', 'viewCompany',  in_array('viewCompany', $serialize_permission)) ?>></td>
                                                    <td> - </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_stores');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createStore" <?= set_checkbox('permission', 'createStore',  in_array('createStore', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateStore" <?= set_checkbox('permission', 'updateStore',  in_array('updateStore', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewStore" <?= set_checkbox('permission', 'viewStore',  in_array('viewStore', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteStore" <?= set_checkbox('permission', 'deleteStore',  in_array('deleteStore', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_groups');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createGroup" <?= set_checkbox('permission', 'createGroup',  in_array('createGroup', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateGroup" <?= set_checkbox('permission', 'updateGroup',  in_array('updateGroup', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewGroup" <?= set_checkbox('permission', 'viewGroup',  in_array('viewGroup', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteGroup" <?= set_checkbox('permission', 'deleteGroup',  in_array('deleteGroup', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_users');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createUser" <?= set_checkbox('permission', 'createUser', in_array('createUser', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateUser" <?= set_checkbox('permission', 'updateUser',  in_array('updateUser', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewUser" <?= set_checkbox('permission', 'viewUser',  in_array('viewUser', $serialize_permission)) ?>></td>
                                                    <td> - </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div>
                                                            <span class="final"><?=$this->lang->line('application_externalAuthentication');?></span>
                                                        </div>
                                                        <div><i style="color:red">><b><small><?=$this->lang->line('messages_role_only_for_admin_groups');?></small><b><i></div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createExternalAuthentication" <?= set_checkbox('permission', 'createExternalAuthentication', in_array('createExternalAuthentication', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateExternalAuthentication" <?= set_checkbox('permission', 'updateExternalAuthentication',  in_array('updateExternalAuthentication', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewExternalAuthentication" <?= set_checkbox('permission', 'viewExternalAuthentication',  in_array('viewExternalAuthentication', $serialize_permission)) ?>></td>
                                                    <td> - </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_brands');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#cadMult6" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="cadMult6" class="collapse">
                                                            <span class="text-black">
                                                                gestão de cadastro e chaves de integração de fornecedores que utilizem a api para fazer uma integração diferente de lojistas, para gerenciar dados especificos do markteplace, como conciliação, extrato, cadastros de lojas e usuário e etc.
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createBrand" <?= set_checkbox('permission', 'createBrand',  in_array('createBrand', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateBrand" <?= set_checkbox('permission', 'updateBrand',  in_array('updateBrand', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewBrand" <?= set_checkbox('permission', 'viewBrand',  in_array('viewBrand', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteBrand" <?= set_checkbox('permission', 'deleteBrand',  in_array('deleteBrand', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_providers_group');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createProviders" <?= set_checkbox('permission', 'createProviders',  in_array('createProviders', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateProviders" <?= set_checkbox('permission', 'updateProviders',  in_array('updateProviders', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewProviders" <?= set_checkbox('permission', 'viewProviders',  in_array('viewProviders', $serialize_permission)) ?>></td>
                                                    <td>-</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_categories');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createCategory" <?= set_checkbox('permission', 'createCategory',  in_array('createCategory', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateCategory" <?= set_checkbox('permission', 'updateCategory',  in_array('updateCategory', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewCategory" <?= set_checkbox('permission', 'viewCategory',  in_array('viewCategory', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteCategory" <?= set_checkbox('permission', 'deleteCategory',  in_array('deleteCategory', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_Banks');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createBank" <?= set_checkbox('permission', 'createBank',  in_array('createBank', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateBank" <?= set_checkbox('permission', 'updateBank',  in_array('updateBank', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewBank" <?= set_checkbox('permission', 'viewBank',  in_array('viewBank', $serialize_permission)) ?>></td>
                                                    <td>-</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_calendar');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createCalendar" <?= set_checkbox('permission', 'createCalendar', in_array('createCalendar', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateCalendar" <?= set_checkbox('permission', 'updateCalendar',  in_array('updateCalendar', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewCalendar" <?= set_checkbox('permission', 'viewCalendar',  in_array('viewCalendar', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteCalendar" <?= set_checkbox('permission', 'deleteCalendar',  in_array('deleteCalendar', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_phases');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#cadMult11" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="cadMult11" class="collapse">
                                                            <span class="text-black">
                                                                Grerenciamento de etapa do lojista dentro do marketplace
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createPhases" <?= set_checkbox('permission', 'createPhases',  in_array('createPhases', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updatePhases" <?= set_checkbox('permission', 'updatePhases',  in_array('updatePhases', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewPhases" <?= set_checkbox('permission', 'viewPhases',  in_array('viewPhases', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deletePhases" <?= set_checkbox('permission', 'deletePhases',  in_array('deletePhases', $serialize_permission)) ?>></td>
                                                </tr>
                                                <!--
                                                    <td>
                                                        </?=$this->lang->line('application_clients');?>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#cadMult12" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="cadMult12" class="collapse">
                                                            <span class="text-black"></span>
                                                        </div>
                                                    </td>
                                                    <td> - </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateClients" <?= set_checkbox('permission', 'updateClients',  in_array('updateClients', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewClients" <?= set_checkbox('permission', 'viewClients',  in_array('viewClients', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteClients" <?= set_checkbox('permission', 'deleteClients',  in_array('deleteClients', $serialize_permission)) ?>></td>
                                                </tr>-->
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_profile');?></span>
                                                    </td>
                                                    <td> - </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateProfile" <?= set_checkbox('permission', 'updateProfile',  in_array('updateProfile', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewProfile" <?= set_checkbox('permission', 'viewProfile',  in_array('viewProfile', $serialize_permission)) ?>></td>
                                                    <td> - </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_update_link');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="createLink" class="minimal" <?php echo set_checkbox('permission', 'createLink', in_array('createLink', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="updateLink" class="minimal" <?php echo set_checkbox('permission', 'updateLink', in_array('updateLink', $serialize_permission)) ?>></td>
                                                    <td> - </td>
                                                    <td> - </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_shopkeeper_form');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createFieldShopkeeperForm" <?php if($serialize_permission) {
                                                            if(in_array('createFieldShopkeeperForm', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateShopkeeperForm" <?php if($serialize_permission) {
                                                            if(in_array('updateShopkeeperForm', $serialize_permission)) { echo "checked"; }
                                                        } ?>></td>
                                                    <td> - </td>
                                                    <td> - </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <?=$this->lang->line('application_contracts');?>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="createContracts" class="minimal" <?= set_checkbox('permission', 'createContracts',  in_array('createContracts', $serialize_permission)) ?></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="updateContracts" class="minimal" <?= set_checkbox('permission', 'updateContracts',  in_array('updateContracts', $serialize_permission)) ?></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="viewContracts" class="minimal" <?= set_checkbox('permission', 'viewContracts',  in_array('viewContracts', $serialize_permission)) ?>></td>
                                                    <td> - </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                    <?=$this->lang->line('application_contract_signatures');?>
                                                    </td>
                                                    <td> - </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="updateContractSignatures" class="minimal" <?= set_checkbox('permission', 'updateContractSignatures',  in_array('updateContractSignatures', $serialize_permission)) ?></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="viewContractSignatures" class="minimal" <?= set_checkbox('permission', 'viewContractSignatures',  in_array('viewContractSignatures', $serialize_permission)) ?></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="deleteContractSignatures" class="minimal" <?= set_checkbox('permission', 'deleteContractSignatures',  in_array('deleteContractSignatures', $serialize_permission)) ?></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_attributes');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createAttribute" <?= set_checkbox('permission', 'createAttribute',  in_array('createAttribute', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateAttribute" <?= set_checkbox('permission', 'updateAttribute',  in_array('updateAttribute', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewAttribute" <?= set_checkbox('permission', 'viewAttribute',  in_array('viewAttribute', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteAttribute" <?= set_checkbox('permission', 'deleteAttribute',  in_array('deleteAttribute', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_systemconfig');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createConfig" <?= set_checkbox('permission', 'createConfig',  in_array('createConfig', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateConfig" <?= set_checkbox('permission', 'updateConfig',  in_array('updateConfig', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewConfig" <?= set_checkbox('permission', 'viewConfig',  in_array('viewConfig', $serialize_permission)) ?>></td>
                                                    <td> - </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_merchant');?></span>
                                                    </td>
                                                    <td> - </td>
                                                    <td> - </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewMerchant" <?= set_checkbox('permission', 'viewMerchant',  in_array('viewMerchant', $serialize_permission)) ?>></td>
                                                    <td> - </td>
                                                </tr>
                                                <tr>
                                                    <td><span class="final">Configuração de integração de marketplace</span></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createIntegrationsSettings" <?= set_checkbox('permission', 'createIntegrationsSettings',  in_array('createIntegrationsSettings', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateIntegrationsSettings" <?= set_checkbox('permission', 'updateIntegrationsSettings',  in_array('updateIntegrationsSettings', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="viewIntegrationsSettings" <?= set_checkbox('permission', 'viewIntegrationsSettings',  in_array('viewIntegrationsSettings', $serialize_permission)) ?>></td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="deleteIntegrationsSettings" <?= set_checkbox('permission', 'deleteIntegrationsSettings',  in_array('deleteIntegrationsSettings', $serialize_permission)) ?>></td>
                                                </tr>
                                                </tbody>
                                            </table>
                                            <table class="table table-responsive">
                                                <thead>
                                                <tr>
                                                    <th style="width:60%"><?=$this->lang->line('application_permission');?> Única</th>
                                                    <th style="width:40%"><?=$this->lang->line('application_link');?></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <!---  Ver Dashboaard Administrativo --->
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_parameter_dashboard_adm');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#cadMult21" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="cadMult21" class="collapse">
                                                            <span class="text-black">
                                                                Apenas para o marketplace - Permissão de acesso em algumas funções administrativas da pagina inicial libera também o botão de "incidencia" no pedido
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="admDashboard" <?= set_checkbox('permission', 'admDashboard',  in_array('admDashboard', $serialize_permission)) ?>></td>
                                                </tr>
                                                <!---  Ver Poder atuar como loja --->
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_change_store');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#cadMult22" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="cadMult22" class="collapse">
                                                            <span class="text-black">
                                                                Permite trocar de loja
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="changeStore" <?= set_checkbox('permission', 'changeStore',  in_array('changeStore', $serialize_permission)) ?>></td>
                                                </tr>
                                                <!---  Quem pode adicionar usuário do Frete Rápido dentro das Stores--->
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_parameter_create_freterapido');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#cadMult23" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="cadMult23" class="collapse">
                                                            <span class="text-black">
                                                                Cadastro de loja na Ferramenta
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="createUserFreteRapido" <?= set_checkbox('permission', 'createUserFreteRapido',  in_array('createUserFreteRapido', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_link_brands_marketplaces');?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="linkBrandsMarketplaces" class="minimal" <?php echo set_checkbox('permission', 'linkBrandsMarketplaces',  in_array('linkBrandsMarketplaces', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_init_store_migration');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#cadMult25" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="cadMult25" class="collapse">
                                                            <span class="text-black">
                                                                Permite iniciar migrações de sellers que já vendam no marketplace para reaproveitamento de catalogo.
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="initStoreMigration" class="minimal" <?php echo set_checkbox('permission', 'initStoreMigration',  in_array('initStoreMigration', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_migration_seller');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#cadMult26" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="cadMult26" class="collapse">
                                                            <span class="text-black">
                                                                Permite que o lojista realiza o de/para dos skus já publicados com os skus integrados no sistema (precisa ter uma migração iniciada)
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="storeMigration" class="minimal" <?php echo set_checkbox('permission', 'storeMigration',  in_array('storeMigration', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_report_problem');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#cadMult27" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="cadMult27" class="collapse">
                                                            <span class="text-black">
                                                                Permite reportar um problema ou abertura de bug no sistema
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="reportProblem" class="minimal" <?php echo set_checkbox('permission', 'reportProblem',  in_array('reportProblem', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_baton_pass');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#cadMult28" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="cadMult28" class="collapse">
                                                            <span class="text-black">
                                                                Habilita dentro da configuração da loja informações adicionais de gestão comercial do seller
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="baton_pass" class="minimal" <?php echo set_checkbox('permission', 'baton_pass',  in_array('baton_pass', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_manage_suggestions');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#cadMult29" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="cadMult29" class="collapse">
                                                            <span class="text-black">
                                                                Permite criar uma nova idea
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="delete_suggestions" class="minimal" <?php echo set_checkbox('permission', 'delete_suggestions',  in_array('delete_suggestions', $serialize_permission)) ?>></td>
                                                    <td> - </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_settings');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#cadMult30" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="cadMult30" class="collapse">
                                                            <span class="text-black">
                                                                Apenas para o marketplace - Permite alterar dados da sua conta
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="updateSetting" <?= set_checkbox('permission', 'updateSetting',  in_array('updateSetting', $serialize_permission)) ?>></td>
                                                </tr>
                                                <!---  Chines que faz Integracoes --->
                                                <tr>
                                                    <td>
                                                        <span class="final"><?=$this->lang->line('application_parameter_do_integration');?></span>
                                                        <a href="javascript:void(0)" data-toggle="collapse" data-target="#cadMult31" class="pull-right"><i class="fa fa-chevron-down"></i></a>
                                                        <div id="cadMult31" class="collapse">
                                                            <span class="text-black">
                                                                Apenas para o marketplace - Habilita a aba de processos do sistema com funções administrativas do seller center
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" class="minimal" value="doIntegration" <?= set_checkbox('permission', 'doIntegration',  in_array('doIntegration', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final">Desabilitar Alteração de Categoria</span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="disabledCategoryPermission" class="minimal" <?php echo set_checkbox('permission', 'disabledCategoryPermission',  in_array('disabledCategoryPermission', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?= $this->lang->line('application_enable_access_to_customization_screen') ?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="enableCustomizationScreen" class="minimal" <?php echo set_checkbox('permission', 'enableCustomizationScreen',  in_array('enableCustomizationScreen', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?= $this->lang->line('application_clean_cache') ?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="cleanCache" class="minimal" <?php echo set_checkbox('permission', 'cleanCache',  in_array('cleanCache', $serialize_permission)) ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span class="final"><?= $this->lang->line('application_vacation_button') ?></span>
                                                    </td>
                                                    <td><input type="checkbox" name="permission[]" id="permission" value="enableVacation" class="minimal" <?php echo set_checkbox('permission', 'enableVacation',  in_array('enableVacation', $serialize_permission)) ?>></td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-body">
                   <div class="form-group col-md-12 col-xs-12">
                    <div class="box-default">
                        <div class="box-body">
                            <!-- /.box-body -->
                            <div class="box-footer">
                                <?php if ($function == 'edit') { ?>
                                    <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_update_changes');?></button>
                                <?php } elseif ($function == 'create') { ?>
                                    <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                                <?php } ?>
                                <a href="<?= base_url('groups/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
          </form>
          <!-- /.box -->
        </div>
        <!-- col-md-12 -->
      </div>
      <!-- /.row -->


    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

<div aria-labelledby="modal-1-label" class="modal fade modal-media modal-video modal-slim modal-1" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><button aria-label="Close" class="close" data-dismiss="modal" type="button"><span aria-hidden="true">×</span></button></div>
            <div class="modal-body">
                <div class="embed-responsive embed-responsive-16by9"><iframe allowfullscreen="" frameborder="0" src="" width="100%"></iframe></div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
var readonly = "<?=isset($readonly); ?>"
  $(document).ready(function() {
    $("#mainGroupNav").addClass('active');
    $("#manageGroupNav").addClass('active');

    if (readonly) {
    	$('input[type="checkbox"]').on("click.readonly", function(event){event.preventDefault();}).css("opacity", "0.5");
    }
    else {
    	 $('input[type="checkbox"].minimal').iCheck({
	      checkboxClass: 'icheckbox_minimal-blue',
	      radioClass   : 'iradio_minimal-blue'
	  	});
    }

  });

$('#viewlist').click(function(){
    location.href = '<?php echo base_url('groups') ?>';
});

$('#creatGroup').click(function(){
    $('#div_apresentation').hide();
    $('#div_hide').show();
});
$(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip();
});

const tabs = document.querySelectorAll('div.tab-pane');
const normalizeString = (str) => str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
$('#search').on('input', (e) => {
    const inputValue = e.target.value;
    const terms = normalizeString(inputValue).toLowerCase().split(" ");
    const highlightedRows = document.querySelectorAll('tr.selected');
    highlightedRows.forEach(row => row.classList.remove('selected'));
    const highlightedTabs = document.querySelectorAll('div.tab-pane.selected');
    highlightedTabs.forEach(tab => tab.classList.remove('selected', 'result-found'));
    if (inputValue === '') {
        const allRows = document.querySelectorAll('table tr');
        allRows.forEach(row => {
            row.classList.remove('selected');
        });
        const allTabs = document.querySelectorAll('div.tab-pane');
        allTabs.forEach(tab => {
            tab.classList.remove('selected', 'result-found');
        });
        return;
    }
    for (const tab of tabs) {
        const rows = tab.querySelectorAll('table tr');
        let resultFound = false;
        rows.forEach(row => {
            const finalSpan = row.querySelector('span.final');
            if (!finalSpan) return;
            const normalizedSpanText = normalizeString(finalSpan.textContent).toLowerCase();
            if (terms.some(term => term && normalizedSpanText.includes(term))) {
                row.classList.add('selected');
                tab.classList.add('selected', 'result-found');
                $('a[href="#' + tab.id + '"]').tab('show');
                resultFound = true;
            }
        });
        if (!resultFound) {
            tab.classList.remove('result-found');
        }
    }
});

var url = window.location.href;
if(!(url.indexOf("groups/create") != -1)){
    $('#div_apresentation').hide();
    $('#div_hide').show();
}

$(document).on('click', '.pull-right', function(e) {
    var icon = $(this).find('i');
    if (icon.hasClass('fa-chevron-down')) {
        icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
    } else if (icon.hasClass('fa-chevron-up')) {
        icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
    }
});
$(document).on('mouseenter', '.pull-right', function() {
    $(this).append('<span class="pull-left text_show">Saiba mais</span>');
});

$(document).on('mouseleave', '.pull-right', function() {
    $(this).find('.text_show').remove();
});

$('.modal-video').on('hidden.bs.modal', function (e) {
    $('.modal-video iframe').attr('src', $('.modal-video iframe').attr('src'));
});
autoPlayYouTubeModal();
function autoPlayYouTubeModal() {
    var trigger = $("body").find('[data-the-video]');
    trigger.click(function () {
        var theModal = $(this).data("target"),
            videoSRC = $(this).attr("data-the-video"),
            videoSRCauto = videoSRC + "&autoplay=1";
        $(theModal + ' iframe').attr('src', videoSRCauto);
        $(theModal + ' button.close').click(function () {
            $(theModal + ' iframe').attr('src', videoSRC);
        });
        $('.modal-video').click(function () {
            $(theModal + ' iframe').attr('src', videoSRC);
        });
    });
}
$(window).on('load resize', function(){
    var $window = $(window);
    $('.modal-fill-vert .modal-body > *').height(function(){
        return $window.height()-60;
    });
});

$('#search').on('keydown', function(event) {
    if (event.key === "Enter") {
        event.preventDefault();
    }
});

</script>