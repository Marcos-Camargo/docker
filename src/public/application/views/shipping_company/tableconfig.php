<!--
SW Serviços de Informática 2019

Index de Fornecedores

-->

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php
        $data['pageinfo'] = "application_manage";
        $this->load->view('templates/content_header', $data);
    ?>

    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <?php if($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?=$this->session->flashdata('success'); ?>
                    </div>
                <?php elseif($this->session->flashdata('error')): ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?=$this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>

                <div class="box box-primary">
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="callout callout-warning">
                                    <h3 class="no-margin font-weight-bold"><?=$this->lang->line('application_warning')?>!</h3>
                                    <h4><?=$this->lang->line('messages_read_import_rules_carefully')?> <button type="button" class="btn btn-warning ml-3" data-toggle="collapse" data-target="#collapseRules" aria-expanded="false" aria-controls="collapseRules"><?=$this->lang->line('application_view_rulese')?></button></h4>
                                    <div class="collapse" id="collapseRules">
                                        <hr>
                                        <h5 class="font-weight-bold"><?=$this->lang->line('messages_completed_fields_and_rules')?></h5>
                                        <?php if ($limit_line): ?>
                                            <h4 class="font-weight-bold text-center text-black">Permitido enviar arquivo com apenas <?=$limit_line?> linhas.</h4>
                                        <?php endif; ?>
                                        <table class="table table-striped table-warning table-bordered">
                                            <thead>
                                                <tr>
                                                    <th><?=$this->lang->line('application_field')?></th>
                                                    <th><?=$this->lang->line('application_rule')?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>regiao</td>
                                                    <td>Obrigatório, informar a região de destino</td>
                                                </tr>
                                                <tr>
                                                    <td>cep_inicial</td>
                                                    <td>Obrigatório, CEP inicial de entrega ao destino</td>
                                                </tr>
                                                <tr>
                                                    <td>cep_final</td>
                                                    <td>Obrigatório, CEP final de entrega ao destino</td>
                                                </tr>
                                                <tr>
                                                    <td>peso_mim</td>
                                                    <td>Obrigatório, peso inicial do volume</td>
                                                </tr>
                                                <tr>
                                                    <td>peso_max</td>
                                                    <td>Obrigatório, peso final do volume</td>
                                                </tr>
                                                <tr>
                                                    <td>preco</td>
                                                    <td>Obrigatório, preço de entrega ao destino. Aceito número com até duas casas decimais. Caso contrário, será convertido para duas casas decimais</td>
                                                </tr>
                                                <tr>
                                                    <td>qtd_dias</td>
                                                    <td>Obrigatório, tempo em dias de entrega ao destino. Aceito apenas número inteiro. Caso contrário, será convertido para inteiro</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <h4 class="text-center"><?=sprintf($this->lang->line('messages_import_file_freight_1'), base_url('FileProcess/index'))?></h4>
                                        <h4 class="text-center"><?=$this->lang->line('messages_import_file_freight_2')?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                       <div id="console-event"></div>
                       <form role="form" action="<?php echo base_url("shippingcompany/uploadconfig/{$idProvider}/{$store_id}");?>" method="post" enctype="multipart/form-data">
                        <div class="box-body">
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label for="shippingCompanyId"><?=$this->lang->line('application_shipping_company');?></label>
                                    <select class="form-control" id="shippingCompanyId" required name="shippingCompanyId" readonly>
                                        <option value="<?php echo $results['id']?>"><?php echo $results['name']?></option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="tableconfig"><?=$this->lang->line('application_shipping_tableconfig');?></label>
                                    <input type="file" class="form-control" id="tableconfig" name="tableconfig" required placeholder="<?=$this->lang->line('application_shipping_tableconfig')?>" accept=".csv,.txt">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="dt_fim"><?=$this->lang->line('application_shipping_table_dt_fim');?></label>
                                    <input type="text" class="form-control pull-right datepicker" id="dt_fim" required name="dt_fim"  autocomplete="off" maxlength="10">
                                </div>
                            </div>
                            <div class="row">
                            </div>
                        </div>
                        <div class="box-footer d-flex justify-content-between">
                            <a href="<?=base_url("shippingcompany/tableshippingcompany/{$idProvider}/{$store_id}") ?>" class="btn btn-warning col-md-3"><?=$this->lang->line('application_back');?></a>
                            <a href="<?php echo $fileExem;?>" class="btn btn-primary col-md-3"><?=$this->lang->line('application_shipping_tableconfig_ex');?></a>
                            <button type="submit" class="btn btn-success col-md-3"><?=$this->lang->line('application_add_new_tableshipping_company');?></button>
                        </div>
                       </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    .box-footer:before,
    .box-footer:after {
        display: none;
    }
</style>

<script>
$(document).ready(function() {

    $('#dt_fim').datepicker({
        format: "dd/mm/yyyy",
        autoclose: true,
        language: 'pt-BR'
    });


    $("#mainLogisticsNav").addClass('active');
    $("#carrierRegistrationNav").addClass('active');
		
    $('#dt_inicio').datepicker({
		format: "dd/mm/yyyy",
		autoclose: true,
		language: "pt-BR", 
		startDate: new Date(),
		todayBtn: true, 
		todayHighlight: true
	});
		
});
	
</script>
