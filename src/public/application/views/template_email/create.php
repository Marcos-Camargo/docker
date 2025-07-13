<style>
.modal-dialog {
	width: 650px;
}
</style>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

	<?php $data['pageinfo'] = "application_add";
	$this->load->view('templates/content_header', $data); ?>

	<!-- Main content -->
	<section class="content">
		<!-- Small boxes (Stat box) -->
		<div class="row">
			<div class="col-md-12 col-xs-12">

				<div id="messages"></div>

				<?php if ($this->session->flashdata('success')) : ?>
					<div class="alert alert-success alert-dismissible" role="alert">
						<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<?php echo $this->session->flashdata('success'); ?>
					</div>
				<?php elseif ($this->session->flashdata('error')) : ?>
					<div class="alert alert-error alert-dismissible" role="alert">
						<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<?php echo $this->session->flashdata('error'); ?>
					</div>
				<?php endif; ?>

				<div class="box">
					<form action="<?=base_url('templateEmail/create') ?>" method="post" enctype="multipart/form-data" id="formInsertTemplateEmail">
						<div class="box-body">
							<?php
							if (validation_errors()) {
								foreach (explode("</p>", validation_errors()) as $erro) {
									$erro = trim($erro);
									if ($erro != "") { ?>
										<div class="alert alert-error alert-dismissible" role="alert">
											<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
											<?php echo $erro . "</p>"; ?>
										</div>
							<?php	}
								}
							} ?>

							<div id="form_title" class="form-group col-md-12 col-xs-12">
								<label for="template_email_title"><?= 'Título' // $this->lang->line('application_name'); ?>(*)</label>
								<input type="text" class="form-control" id="template_email_title" maxlength="<?= $template_email_length_title ?>" name="template_email_title"  placeholder="<?= 'Digite o título do template de email' //$this->lang->line('application_enter_product_name'); ?>" value="<?php echo set_value('template_email_title') ?>" autocomplete="off" />
								<span id="char_template_email_title"></span><br />
								<span class="label label-warning" id="words_template_email_title" data-toggle="tooltip" data-placement="top" title="<?= 'dfvfdvfv' //$this->lang->line('application_explanation_of_forbidden_words') ?>"></span>
								<?php echo '<i style="color:red">' . form_error('template_email_title') . '</i>'; ?>
							</div>

							<div id="form_subject" class="form-group col-md-12 col-xs-12">
								<label for="template_email_subject"><?= 'Assunto' // $this->lang->line('application_name'); ?>(*)</label>
								<input type="text" class="form-control" id="template_email_subject" maxlength="<?= $template_email_length_subject ?>" name="template_email_subject"  placeholder="<?= 'Digite o assunto do template de email' //$this->lang->line('application_enter_product_name'); ?>" value="<?php echo set_value('template_email_subject') ?>" autocomplete="off" />
								<span id="char_template_email_subject"></span><br />
								<span class="label label-warning" id="words_template_email_subject" data-toggle="tooltip" data-placement="top" title="<?= 'Assunto' //$this->lang->line('application_explanation_of_forbidden_words') ?>"></span>
								<?php echo '<i style="color:red">' . form_error('template_email_subject') . '</i>'; ?>
							</div>

							<div id="form_description" class="form-group col-md-12 col-xs-12 <?php echo (form_error('template_email_description')) ? 'has-error' : '';  ?>">
								<label for="template_email_description"><?= 'Corpo do email' //$this->lang->line('application_description'); ?>(*)</label>
								<textarea type="text" class="form-control" id="template_email_description" name="template_email_description"  placeholder="<?= $this->lang->line('application_enter_description'); ?>"><?php echo set_value('template_email_description') ?></textarea>
								<span id="char_template_email_description"></span><br />
								<span class="label label-warning" id="words_template_email_description" data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_explanation_of_forbidden_words'); ?>"></span>
								<?php echo '<i style="color:red">' . form_error('template_email_description') . '</i>'; ?>
							</div>
						</div>

						<!-- /.box-body -->
						<div class="box-footer">
							<div class="row">
								<div class="form-group col-md-12">
								<label class="switch" for="template_email_status"><?=$this->lang->line('application_status') . " :";?>
								<input id="template_email_status" name="template_email_status" type="checkbox" checked data-toggle="toggle" data-on="Ativo" data-off="Inativo" data-onstyle="success" data-offstyle="danger" onclick="toggleChenge()">
								</label>
								<?php echo '<i style="color:red">' . form_error('template_email_status') . '</i>'; ?>
								</div>
							</div>
							<a href="javascript:return void();" class="btn btn-info" id="btnTemplatePreview" data-toggle="modal" data-target="#templatePreview">Pré-visualizar</a>
							<br>
							<hr>
							<button type="submit" id="letsave" class="btn btn-primary col-md-2"><?= $this->lang->line('application_save'); ?></button>
							<!-- button type="button" id="varprop" class="btn btn-primary col-md-2"><?= $this->lang->line('application_variantproperties'); ?></button -->
							<a href="<?php echo base_url('templateEmail/index') ?>" class="btn btn-warning col-md-2"><?= $this->lang->line('application_back'); ?></a>
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

<!-- Modal Template Preview -->
<div class="modal fade" id="templatePreview" tabindex="-1" role="dialog" aria-labelledby="templatePreviewTitle">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="templatePreviewSubject"></h4>
      </div>
      <div class="modal-body">
		<span id="templatePreviewDescription"></span>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
	var base_url = "<?php echo base_url(); ?>";
	var HaserrorTitle = "<?php echo form_error('template_email_title') ?>";
	var HaserrorSubject = "<?php echo form_error('template_email_subject') ?>";
	var HaserrorBody = "<?php echo form_error('template_email_description') ?>";
	$("#template_email_rule_status").val();
	$(document).ready(function() {
        var EventData = function (context) {
			var ui = $.summernote.ui;
			var event = ui.buttonGroup([
				ui.button({
					contents: 'Variáveis',
					data: {
							toggle: 'dropdown'
					}
				}),
				ui.dropdown({
					items: [
						'{{NomeDaLoja}}', '{{NomeDoResponsavelLoja}}'
					],
					callback: function (items) {
						$(items, 'div.note-btn-group.btn-group.note-eventButton > div > ul > li').click(function(e) 
    					{
							context.invoke("editor.insertText", $(e.target).text());
    					});
					}
				}),
			]);

			return event.render();   // return button as jquery object
		}

		$("#template_email_description").summernote({
			tabsize: 2,
			toolbar: [
				['style', ['style']],
				['font', ['bold', 'italic', 'underline', 'clear']],
				['fontname', ['fontname']],
				['color', ['color']],
				['para', ['ul', 'ol', 'paragraph', 'height']],
				['table', ['table']],
				['insert', ['link', 'picture']],
				['view', ['fullscreen', 'codeview', 'undo', 'help']],
				['eventButton', ['event']]
			],
			height: 550,
			disableDragAndDrop: true,
			lang: 'pt-BR',
			shortcuts: false,
			callbacks: {
				onKeyup: function(e) {
					// var conteudo = $(".note-editable").text();
					var conteudo = $(".note-editable").html();
					var limit = $('#template_email_description').attr('maxlength');
					if (conteudo.length > limit) {
						// $(".note-editable").text(conteudo.slice(0,-(conteudo.length-limit)));
						$(".note-editable").html(conteudo.slice(0, -(conteudo.length - limit)));
					}
					characterLimit(this);
				},
				onChange: function (contents, $editable) {
					var markup = $('#template_email_description').summernote('code');
				}
			},
			buttons: {
				event: EventData
			}
		});
		checkDanger({ HaserrorTitle, HaserrorSubject, HaserrorBody })

		function characterLimit(object) {
			var limit = object.getAttribute('maxlength');
			var attribute = object.getAttribute('id');

			if (attribute == 'template_email_description') {
				// var quantity = $(".note-editable").text().length;
				var quantity = $(".note-editable").html().length;
			} else {
				var quantity = object.value.length;
			}
		}

		characterLimit(document.getElementById('template_email_subject'));

		$( "#template_email_subject" ).keyup(function() {
			characterLimit(document.getElementById('template_email_subject'));
		});
		characterLimit(document.getElementById('template_email_description'));

		// Template Preview
		$('#btnTemplatePreview').click(function() {
			$('#templatePreviewSubject').html($('#template_email_subject').val());
			$('#templatePreviewDescription').html($('#template_email_description').val());
		});
		
	});
	function checkDanger({ HaserrorTitle, HaserrorSubject, HaserrorBody }){
    	const p = document.querySelector('p');
		if(p){
			if(HaserrorTitle){
				$("#form_title").addClass('has-error')
			}
			if(HaserrorSubject){
				$("#form_subject").addClass('has-error')
			}
			if(HaserrorBody){
				$("#form_description").addClass('has-error')
			}
		}
  	}
	  
</script>