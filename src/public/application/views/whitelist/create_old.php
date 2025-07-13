<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php 
		$data['pageinfo'] = "application_manage";  
		$this->load->view('templates/content_header',$data); 
	?>

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
					<form role="form" action="<?php base_url('blacklistWords/create') ?>" method="post">
						<div class="box-body">
							<div class="row">
								<div class="form-group col-md-6" <?php echo (form_error('word')) ? "has-error" : "";?>>
									<label for="word"><?= $this->lang->line('application_forbidden_word') ?></label>
									<input type="text" class="form-control" id="word" name="word" value="<?php echo set_value('word');?>" placeholder="<?= $this->lang->line('application_forbidden_word_here') ?>" autocomplete="off">
									<?php echo '<i style="color:red">'.form_error('word').'</i>';  ?> 
								</div>
							</div>
							<button type="submit" class="btn btn-primary col-md-1">Salvar</button>
							<a href="<?php echo base_url('blacklistWords/') ?>" class="btn btn-warning col-md-1">Voltar</a>
						</div>
					</form>
				</div>
			</div>
		</div>
  	</section>
</div>