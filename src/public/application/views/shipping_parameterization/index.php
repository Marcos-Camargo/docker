<!--
SW Serviços de Informática 2019

Index de Fornecedores

-->

<!-- Content Wrapper. Contains page content -->

<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage";
$this->load->view('templates/content_header', $data);?>


    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12" id="rowcol12">

                    <?php if ($this->session->flashdata('success')): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <?=$this->session->flashdata('success');?>
                        </div>
                    <?php elseif ($this->session->flashdata('error')): ?>
                        <div class="alert alert-error alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <?=$this->session->flashdata('error');?>
                        </div>
                    <?php endif;?>
                    
                    <form role="form" action="<?php echo base_url('shippingparameterization/save') ?>" method="post"  enctype="multipart/form-data">                    
                        <div class="box">
                            <div class="box-body">
                                <div id="console-event"></div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="group_name"><?=$this->lang->line('parameterization_shipping_conectaLa');?></label>
                                        <?php foreach($listConectala as $keyC => $value ) { ?>
                                            <div class="col-sm-offset-2 col-sm-10 checkbox">
                                                <input type="checkbox" name="conectala[]"value="<?php echo $value['id'];?>"> <?php echo $value['name'];?>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="group_name"><?php echo $sellerName;?></label>
                                        <?php foreach($listSeller as $keyS => $values ) { ?>
                                            <div class="col-sm-offset-2 col-sm-10 checkbox">
                                                <input type="checkbox" name="seller[transportadora][Norte][AC][" value="<?php echo $values['id'];?>"> <?php echo $values['name'];?>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                            <!-- /.box-body -->                        
                            <div class="box-footer">
                                <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                                <a href="<?php echo base_url('shippingparameterization/index') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
                            </div>                        
                        </div>
                        <!-- /.box -->
                    </form>
                    
                    
                <!-- col-md-12 -->
            </div>
            <!-- /.row -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->
