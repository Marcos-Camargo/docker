<div class="content-wrapper">
    <?php $data['pageinfo'] = "application_edit";  $this->load->view('templates/content_header',$data); ?>
    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div class="box">
                    <form role="form" action="" method="post" enctype="multipart/form-data" >
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

                            <div class="row">
                                <div class="form-group col-md-12 col-xs-12">
                                    <label><?=lang('application_integration_erp_banner')?> (*)</label>
                                    <input id="image" name="image" type="file" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group">
                                    <div class="col-md-4">
                                        <label for="description"><?=lang('application_name')?> (*)</label>
                                        <input type="text" class="form-control" id="description" name="description" placeholder="<?=lang('application_enter_name')?>" value="<?=set_value('name')?>" required/>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="type"><?=lang('application_type')?> (*)</label>
                                        <select class="form-control" id="type" name="type">
                                            <option value="2" <?=set_select('type', 2) ?>><?=$this->lang->line('application_type_integration_erp_2')?></option>
                                            <option value="3" <?=set_select('type', 3) ?>><?=$this->lang->line('application_type_integration_erp_3')?></option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="active"><?=lang('application_visible_in_the_system')?> (*)</label>
                                        <select class="form-control" id="active" name="active" required>
                                            <option value="1" <?=set_select('active', 1) ?>><?=lang('application_yes')?></option>
                                            <option value="0" <?=set_select('active', 0) ?>><?=lang('application_no')?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row" id="head-list-providers">
                                <div class="form-group">
                                    <div class="col-md-12">
                                        <label for="provider">Fornecedor</label>
                                        <select name="provider" id="provider" class="form-control select2">
                                            <option value=""><?= $this->lang->line('application_select'); ?></option>
                                            <?php
                                            foreach ($providers as $provider) {
                                                echo "<option value='$provider[id]'>$provider[name]</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row" id="info-listLinksSupport">
                                <div class="form-group">
                                    <div class="col-md-12">
                                        <h3>Links de Apoio</h3>
                                        <p>Insira abaixo links de apoio para os seus usuários acessarem tutoriais ou textos de ajuda para utilizar a integração.</p>
                                        <div class="input-group d-flex justify-content-between">
                                            <input style="flex: 3" type="text" class="form-control" id="title" placeholder="<?=lang('application_title')?>">
                                            <input style="flex: 3" type="url" class="form-control" id="link" placeholder="Link">
                                            <span style="flex: 1" class="input-group-btn">
                                                <button type="button" class="btn btn-success btn-flat col-md-12" id="addLinkSupport"><i class="fa fa-plus"></i> <?=lang('application_add');?></button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-5" id="head-listLinksSupport">
                                <div class="form-group">
                                    <div class="col-md-12">
                                        <div class="input-group d-flex justify-content-between">
                                            <span style="flex: 3"><label>Título do Link</label></span>
                                            <span style="flex: 3"><label>Link</label></span>
                                            <span style="flex: 1"><label>Ação</label></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="listLinksSupport"></div>
                        </div>
                        <div class="box-footer d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary col-md-2"><?=lang('application_save');?></button>
                            <a href="<?=base_url('integrations/manageIntegration') ?>" class="btn btn-default col-md-2 ml-2"><?=lang('application_cancel');?></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<script type="text/javascript">

    const imagePreLoad = `<?=base_url('assets/images/integration_erps')?>/${$('#imagePreLoad').val()}`;
  
    $(document).ready(function() {
        $("#mainIntegrationApiNav").addClass('active');
        $("#manageIntegrationErp").addClass('active');

        $('#head-listLinksSupport').hide();

        $("#image").fileinput({
            allowedFileExtensions: ["jpg", "png", "jpeg", "gif"],
            msgPlaceholder: "Selecione o banner para enviar",
            showUpload: false,
            initialPreviewAsData: true,
            overwriteInitial: true,
            showUploadedThumbs: false,
            initialPreviewShowDelete: false,
            showRemove: false,
            showClose: false
        });

        $('#type').trigger('change');
        $("#provider").select2();
    });

    $('#addLinkSupport').on('click', function(){
        const el = $(this).closest('.row');
        const title = el.find('input[id="title"]').val();
        const link = el.find('input[id="link"]').val();

        if (title === '' || link === '') {
            Toast.fire({
                icon: 'error',
                title: 'Preencha o título e o link de apoio, para adicionar.',
            });
            return false;
        }

        $('#head-listLinksSupport').show();

        el.find('input[id="title"]').val('');
        el.find('input[id="link"]').val('');

        $('#listLinksSupport').append(`
        <div class="row mt-1">
            <div class="form-group">
                <div class="col-md-12">
                    <div class="input-group d-flex justify-content-between">
                        <span style="flex: 3" class="mr-2"><input type="text" class="form-control" name="title[]" value="${title}"/></span>
                        <span style="flex: 3" class="mr-2"><input type="url" class="form-control" name="link[]" value="${link}"/></span>
                        <span style="flex: 1"><button type="button" class="btn btn-danger btn-flat removeLinkSupport"><i class="fa fa-trash"></i></button></span>
                    </div>
                </div>
            </div>
        </div>
        `);
    });

    $(document).on('click', '.removeLinkSupport', function(){
        $(this).closest('.row').remove();

        if ($('#listLinksSupport div').length === 0) {
            $('#head-listLinksSupport').hide();
        }
    });

    $('#type').on('change', function(){
        const is_logistic = parseInt($(this).val()) === 3;
        const info = $('#info-listLinksSupport')
        const head = $('#head-listLinksSupport')
        const body = $('#listLinksSupport')
        const provider = $('#head-list-providers')

        if (is_logistic) {
            info.hide();
            head.hide();
            body.hide();
            provider.show();
        } else {
            info.show();
            head.show();
            body.show();
            provider.hide();
        }
    });
</script>