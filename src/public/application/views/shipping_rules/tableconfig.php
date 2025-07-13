<!--
SW Serviços de Informática 2019

Index de Fornecedores

-->

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
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

                    <div class="box">
                        <div class="box-body">                          
                           <div id="console-event"></div>
                           <form role="form" action="<?php echo base_url('shippingcompany/uploadconfig');?>" method="post" enctype="multipart/form-data">
                            <div class="box-body">
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="name"><?=$this->lang->line('application_shipping_company');?></label>
                                        <select class="form-control" id="shippingCompanyId" required name="shippingCompanyId">
                                            <option value=""><?=$this->lang->line('application_shipping_company_empty_name');?></option>
                                            <?php foreach ($results as $result) { ?>
                                                <option value="<?php echo $result['id']?>"><?php echo $result['name']?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="raz_soc"><?=$this->lang->line('application_shipping_tableconfig');?></label>
                                        <input type="file" class="form-control" id="tableconfig" name="tableconfig" required placeholder="<?=$this->lang->line('application_shipping_tableconfig')?>">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="raz_soc"><?=$this->lang->line('application_shipping_tableconfig_ex');?></label><br>
                                        <a href="<?php echo $fileExem;?>" class="btn btn-success"><?=$this->lang->line('application_download');?></a>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="datainicio"><?=$this->lang->line('application_shipping_table_dt_inicio');?></label>
                                        <input type="text" class="form-control pull-right datepicker" id="dt_inicio" name="dt_inicio">                                     
                                    </div>
                                    
                                    <div class="form-group col-md-4">
                                        <label for="datainicio"><?=$this->lang->line('application_shipping_table_dt_fim');?></label>
                                        <input type="text" class="form-control pull-right datepicker" id="dt_fim" name="dt_fim">                                     
                                    </div>
                                    <div class="form-group col-md-4"></div>                                    
                                </div>
                            </div>
                            <div class="box-footer">
                                <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                                <a href="<?=base_url('shippingcompany/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
                            </div>
                           </form>
                        </div>
                        </div>
                        <!-- /.box-body -->
                    </div>
                    <!-- /.box -->
                </div>
                <!-- col-md-12 -->
            </div>
            <!-- /.row -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->
<script>

$(document).ready(function() {
		
    $('#dt_inicio').datepicker({
		format: "dd/mm/yyyy",
		autoclose: true,
		language: "pt-BR", 
		startDate: new Date(+new Date() + 86400000),
		todayBtn: true, 
		todayHighlight: true
	});
    $('#dt_fim').datepicker({
		format: "dd/mm/yyyy",
		autoclose: true,
		language: "pt-BR", 
		startDate: new Date(+new Date() + 86400000),
		todayBtn: true, 
		todayHighlight: true
	});
		
});
	
</script>