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
              <?php echo $this->session->flashdata('success'); ?>
            </div>
          <?php elseif($this->session->flashdata('error')): ?>
            <div class="alert alert-error alert-dismissible" role="alert">
              <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              <?php echo $this->session->flashdata('error'); ?>
            </div>
          <?php endif; ?>

          <div class="box">
            <div class="box-header">
              <h3 class="box-title"><?=$this->lang->line('application_conciliacao_tolerance_title');?></h3>
            </div>
            <form role="form" action="<?php base_url('billet/divergenceTolerance') ?>" method="post">
              <div class="box-body">


                <div class="form-group col-md-3">
                  	<label for="value_lower"><?=$this->lang->line('application_conciliacao_tolerance_field_value_lower');?></label>
                      <div style="position: absolute; top: 32px; left: 26px;"><strong>R$ </strong></div>
                  	<input type="text" class="form-control" style="padding-left: 36px;" id="value_lower" required name="value_lower" placeholder="0.1" value="<?php echo set_value('value_lower', number_format(str_replace(',', '.', $tolerance['value_lower']), 2, ",", ".")) ?>">
                </div>

                
                <div class="form-group col-md-3">
                  	<label for="value_lower"><?=$this->lang->line('application_conciliacao_tolerance_field_value_higher');?></label>
                    <div style="position: absolute; top: 32px; left: 26px;"><strong>R$ </strong></div>
                  	<input type="text" class="form-control" style="padding-left: 36px;" id="value_higher" required name="value_higher" placeholder="0.1" value="<?php echo set_value('value_higher', number_format(str_replace(',', '.', $tolerance['value_higher']), 2, ",", ".")) ?>">
                </div>


              </div>
              <!-- /.box-body -->

              <div class="box-footer">
                <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                <a href="<?php echo base_url('billet/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
              </div>
            </form>
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