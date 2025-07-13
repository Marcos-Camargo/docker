 
 <?php include_once(APPPATH . '/third_party/zipcode.php'); ?>
<!--
SW Serviços de Informática 2019

Criar Empresa
 
-->  
<!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_add";  $this->load->view('templates/content_header',$data) ?>

    <!-- Main content -->
    <section class="content">
      <!-- Small boxes (Stat box) -->
      <div class="row">
        <div class="col-md-12 col-xs-12">
          
          <?php if($this->session->flashdata('success')): ?>
            <div class="alert alert-success alert-dismissible" role="alert">
              <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              <?php echo $this->session->flashdata('success') ?>
            </div>
          <?php elseif($this->session->flashdata('error')): ?>
            <div class="alert alert-error alert-dismissible" role="alert">
              <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              <?php echo $this->session->flashdata('error') ?>
            </div>
          <?php endif; ?>

          <div class="box">
            <div class="box-header">
              <h3 class="box-title"><?=$this->lang->line('application_add_brand')?></h3>
            </div>
            <form role="form" action="<?php echo base_url('brands/brands_marketplaces_update') ?>" method="post" id="updateBrandForm">
				<input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" />
				<div class="modal-body">
					<div class="row">
						<div class="form-group col-md-2">
							<label for="int_to"><?=$this->lang->line('application_marketplace');?></label>
							<select class="form-control" id="int_to" name="int_to" required disabled>
								<option><?=$this->lang->line('application_select');?></option>
								<?php foreach ($integrations as $k): ?>
								<option value="<?php echo ($k['id'])?>" <?php echo ($brand_marketplace['int_to']==$k['int_to']?'selected="selected"':'')?>><?php echo ($k['name'])?></option>
								<?php endforeach ?>
							</select>
							<?php echo '<i style="color:red">'.form_error('int_to').'</i>'; ?>   
						</div>
						<div class="form-group col-md-4">
							<label for="name"><?=$this->lang->line('application_name');?></label>
							<input type="text" class="form-control" id="name" name="name" placeholder="<?=$this->lang->line('application_enter_brand_name')?>" autocomplete="off" value="<?php echo set_value('name', $brand_marketplace['name']) ?>" required>
							<?php echo '<i style="color:red">'.form_error('name').'</i>'; ?>      
						</div>
						<div class="form-group col-md-4 hidden">
							<label for="brand_id"><?=$this->lang->line('application_name');?></label>
							<input type="text" class="form-control" id="brand_id" name="brand_id" placeholder="<?=$this->lang->line('application_enter_brand_name')?>" autocomplete="off" value="<?php echo set_value('name', $brand['id']) ?>" required>
							<?php echo '<i style="color:red">'.form_error('brand_id').'</i>'; ?>      
						</div>
                        <div class="form-group col-md-4 hidden">
							<label for="int_to"><?=$this->lang->line('application_name');?></label>
							<input type="text" class="form-control" id="int_to" name="int_to" placeholder="<?=$this->lang->line('application_enter_brand_name')?>" autocomplete="off" value="<?php echo set_value('int_to', $brand_marketplace['int_to']) ?>" required>
							<?php echo '<i style="color:red">'.form_error('int_to').'</i>'; ?>      
						</div>
						<div class="form-group col-md-6">
							<label for="title"><?=$this->lang->line('application_title');?></label>
							<input type="text" class="form-control" id="title" name="title" placeholder="<?=$this->lang->line('application_enter_brand_title')?>" autocomplete="off"  value="<?php echo set_value('title', $brand_marketplace['title']) ?>" required>
							<?php echo '<i style="color:red">'.form_error('title').'</i>'; ?>   
						</div>
						
					</div>
					<div class="row">
						<div class="form-group col-md-2">
							<label for="isActive"><?=$this->lang->line('application_status');?></label>
							<select class="form-control" id="isActive" name="isActive" required>
								<option value="1" <?php echo ($brand_marketplace['isActive']=='1'?'selected="selected"':'')?>><?=$this->lang->line('application_active');?></option>
								<option value="0" <?php echo ($brand_marketplace['isActive']=='0'?'selected="selected"':'')?>><?=$this->lang->line('application_inactive');?></option>
							</select>
							<?php echo '<i style="color:red">'.form_error('isActive').'</i>'; ?>   
						</div>
						<div class="form-group col-md-10">
							<label for="metaTagDescription"><?=$this->lang->line('application_meta_tag_description');?></label>
							<input type="text" class="form-control" id="metaTagDescription" name="metaTagDescription" placeholder="<?=$this->lang->line('application_enter_meta_tag_description')?>" autocomplete="off" value="<?php echo set_value('metaTagDescription', $brand_marketplace['metaTagDescription']) ?>" required>
							<?php echo '<i style="color:red">'.form_error('imageUrl').'</i>'; ?>   
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md-2">
							<label for="MenuHome"><?=$this->lang->line('application_menu_home');?></label>
							<select class="form-control" id="MenuHome" name="MenuHome">
								<option value="1" <?php echo ($brand_marketplace['MenuHome']=='1'?'selected="selected"':'')?>><?=$this->lang->line('application_active');?></option>
								<option value="0" <?php echo ($brand_marketplace['MenuHome']=='0'?'selected="selected"':'')?>><?=$this->lang->line('application_inactive');?></option>
							</select>
							<?php echo '<i style="color:red">'.form_error('MenuHome').'</i>'; ?>
						</div>
						<div class="form-group col-md-1">
							<label for="Score"><?=$this->lang->line('application_score');?></label>
							<input type="text" class="form-control" id="Score" name="Score" autocomplete="off" value="<?php echo set_value('Score', $brand_marketplace['Score']) ?>">
							<?php echo '<i style="color:red">'.form_error('Score').'</i>'; ?>
						</div>
						<div class="form-group col-md-4">
							<label for="LomadeeCampaignCode"><?=$this->lang->line('application_lomadee_campaign_code');?></label>
							<input type="text" class="form-control" id="LomadeeCampaignCode" name="LomadeeCampaignCode" autocomplete="off" value="<?php echo set_value('LomadeeCampaignCode', $brand_marketplace['LomadeeCampaignCode'])?>">
							<?php echo '<i style="color:red">'.form_error('LomadeeCampaignCode').'</i>'; ?>
						</div>
						<div class="form-group col-md-5">
							<label for="AdWordsRemarketingCode"><?=$this->lang->line('application_adWords_remarketing_code');?></label>
							<input type="text" class="form-control" id="AdWordsRemarketingCode" name="AdWordsRemarketingCode" autocomplete="off" value="<?php echo set_value('AdWordsRemarketingCode', $brand_marketplace['AdWordsRemarketingCode'])?>">
							<?php echo '<i style="color:red">'.form_error('AdWordsRemarketingCode').'</i>'; ?>
						</div>
					</div>
				</div>

				<div class="modal-footer">
				<a type="button" class="btn btn-default" data-dismiss="modal"  href="<?=base_url('brands/');?>"><?=$this->lang->line('application_close');?></a>
				<button type="submit" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
				</div>

			</form>
          </div>
          <!-- /.box -->
		  <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
             <!--- 	<th><?=$this->lang->line('application_id');?></th> --->
                <th><?=$this->lang->line('application_name');?></th>
                <th><?=$this->lang->line('application_status');?></th>
                <th><?=$this->lang->line('application_title');?></th>
                <th><?=$this->lang->line('application_meta_tag_description');?></th>
                <th><?=$this->lang->line('application_marketplace');?></th>
                <th><?=$this->lang->line('application_action');?></th>
              </tr>
              </thead>

            </table>
          </div>
          <!-- /.box-body -->
        </div>
        </div>
        <!-- col-md-12 -->
      </div>
      <!-- /.row -->
      

    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
  
<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">

var manageTable;
var base_url = "<?php echo base_url(); ?>";
// para csrf 
var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>',
    csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
    
$(document).ready(function() {
  $("#brandNav").addClass('active');
  
  // initialize the datatable 
   manageTable = $('#manageTable').DataTable({
 	"language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"	 },
	"processing": true,
	"serverSide": true,
	"sortable": true,
	"serverMethod": "post",
	'order': [],
	'ajax': $.fn.dataTable.pipeline({
            url: base_url + 'brands/fetchBrandsLinkData/'+"<?php echo $brand_marketplace['brand_id'] ?>",
            data: { [csrfName]: csrfHash }
        } )
   });
});

</script>

