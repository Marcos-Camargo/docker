<!--
SW Serviços de Informática 2019

Editar Integracoes

-->


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_edit";  $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">

        <div id="messages"></div>

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
          <form role="form" action="<?php base_url('integrations/update') ?>" method="post" enctype="multipart/form-data">
              <div class="box-body">

            <?php 
            if (validation_errors()) { 
	          foreach (explode("</p>",validation_errors()) as $erro) { 
		        $erro = trim($erro);
		        if ($erro!="") { ?>
				<div class="alert alert-error alert-dismissible" role="alert">
					<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	                <?php echo $erro."</p>"; ?>
				</div>
		<?php	}
			  }
	       	} ?>

                <div class="form-group">

                <div class="form-group">
                  <label for="int_name"><?=$this->lang->line('application_name');?></label>
                  <input type="text" class="form-control" id="int_name" name="int_name" placeholder="<?=$this->lang->line('application_enter_name')?>" value="<?php echo set_value('int_name', $int_data['name']) ?>" autocomplete="off"/>
                </div>

                <?php if($integrations_info): ?>
                  <?php foreach ($integrations_info as $k => $v): ?>
                    <div class="form-group">
                      <label for="groups"><?=$this->lang->line('application_'.trim($v['attribute_data']['name'])); ?></label>
                      <select class="form-control select_group" id="attributes_value_id" name="<?php echo trim($v['attribute_data']['name']); ?>" >
                        <?php foreach ($v['attribute_value'] as $k2 => $v2): ?>
                          <option value="<?php echo $v2['value'] ?>" <?php echo set_select(trim($v['attribute_data']['name']), $v2['value'], $v2['value'] == $int_data[trim($v['attribute_data']['name'])]) ?>><?php echo $v2['value'] ?></option>
                        <?php endforeach ?>
                      </select>
                    </div>    
                  <?php endforeach ?>
                <?php endif; ?>

                <div class="form-group">
                  <label for="int_auth_data"><?=$this->lang->line('application_auth_data');?></label>
                  <textarea type="text" class="form-control" id="int_auth_data" name="int_auth_data" placeholder="<?=$this->lang->line('application_enter_description')?>" autocomplete="off"><?php echo set_value('int_auth_data', $int_data['auth_data']); ?></textarea>
                </div>

                <div class="form-group">
                  <label for="int_active"><?=$this->lang->line('application_active');?></label>
                  <select class="form-control" id="int_active" name="int_active">
                    <option value="1" <?=set_select('int_active', 1)?>><?=$this->lang->line('application_yes')?></option>
                    <option value="2" <?=set_select('int_active', 2)?>><?=$this->lang->line('application_no')?></option>
                  </select>
                </div>

              </div>
              <!-- /.box-body -->

              <div class="box-footer">
                <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                <a href="<?php echo base_url('integrations/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
              </div>

            </form>
          <!-- /.box-body -->
        </div>
        <!-- /.box -->
      </div>
      <!-- col-md-12 -->
     </div>
    <!-- /.row -->
    </div>
    

  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<script type="text/javascript">
  
  $(document).ready(function() {
    $(".select_group").select2();
    $("#description").wysihtml5();

    $("#mainIntegrationNav").addClass('active');
    $("#manageIntegrationNav").addClass('active');
    
    var btnCust = '<button type="button" class="btn btn-secondary" title="Add picture tags" ' + 
        'onclick="alert(\'Call your custom code here.\')">' +
        '<i class="glyphicon glyphicon-tag"></i>' +
        '</button>'; 
    $("#product_image").fileinput({
        overwriteInitial: true,
        maxFileSize: 1500,
        showClose: false,
        showCaption: false,
        browseLabel: '',
        removeLabel: '',
        browseIcon: '<i class="glyphicon glyphicon-folder-open"></i>',
        removeIcon: '<i class="glyphicon glyphicon-remove"></i>',
        removeTitle: 'Cancel or reset changes',
        elErrorContainer: '#kv-avatar-errors-1',
        msgErrorClass: 'alert alert-block alert-danger',
        // defaultPreviewContent: '<img src="/uploads/default_avatar_male.jpg" alt="Your Avatar">',
        layoutTemplates: {main2: '{preview} ' +  btnCust + ' {remove} {browse}'},
        allowedFileExtensions: ["jpg", "png", "gif"]
    });

  });
</script>