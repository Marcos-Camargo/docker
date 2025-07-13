<div class="content-wrapper">
    <?php $data['pageinfo'] = "application_edit";
    $this->load->view('templates/content_header', $data); ?>
    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="active">
                            <a href="#nav-general"
                               data-toggle="tab">
                                <?=$this->lang->line('application_general')?>
                            </a>
                        </li>
                        <?php if (!empty($integration->configuration_form ?? [])) { ?>
                            <li>
                                <a href="#nav-configurations"
                                   data-toggle="tab">
                                    <?= $this->lang->line('application_settings') ?>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                    <form role="form" action="" method="post" enctype="multipart/form-data">
                        <div class="tab-content col-md-12">
                            <div class="tab-pane active" id="nav-general">
                                <div class="box">
                                    <div class="box-body">
                                        <?php
                                        if (validation_errors()) {
                                            foreach (explode("</p>", validation_errors()) as $erro) {
                                                $erro = trim($erro);
                                                if ($erro != "") { ?>
                                                    <div class="alert alert-error alert-dismissible" role="alert">
                                                        <button type="button" class="close" data-dismiss="alert"
                                                                aria-label="Close"><span
                                                                    aria-hidden="true">&times;</span></button>
                                                        <?=$erro . "</p>"; ?>
                                                    </div>
                                                <?php }
                                            }
                                        } ?>
                                        <?php if ($this->session->flashdata('success')): ?>
                                            <div class="alert alert-success alert-dismissible" role="alert">
                                                <button type="button" class="close" data-dismiss="alert"
                                                        aria-label="Close"><span aria-hidden="true">&times;</span>
                                                </button>
                                                <?=$this->session->flashdata('success'); ?>
                                            </div>
                                        <?php elseif ($this->session->flashdata('error')): ?>
                                            <div class="alert alert-error alert-dismissible" role="alert">
                                                <button type="button" class="close" data-dismiss="alert"
                                                        aria-label="Close"><span aria-hidden="true">&times;</span>
                                                </button>
                                                <?=$this->session->flashdata('error'); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="row">
                                            <div class="form-group col-md-12 col-xs-12">
                                                <label><?= lang('application_integration_erp_banner') ?> (*)</label>
                                                <input id="image" name="image" type="file">
                                                <input type="hidden" id="imagePreLoad"
                                                       value="<?= $integration->image ?>">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="form-group">
                                                <div class="col-md-3">
                                                    <label for="description"><?= lang('application_name') ?> (*)</label>
                                                    <input type="text" class="form-control" id="description"
                                                           name="description"
                                                           placeholder="<?= lang('application_enter_name') ?>"
                                                           value="<?= set_value('name', $integration->description) ?>"
                                                           required/>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="type"><?= lang('application_type') ?> (*)</label>
                                                    <select class="form-control" id="type" name="type" required readonly disabled="disabled">
                                                        <option value="1" <?=set_select('type', 1, $integration->type == 1) ?>><?=$this->lang->line('application_type_integration_erp_1')?></option>
                                                        <option value="2" <?=set_select('type', 2, $integration->type == 2) ?>><?=$this->lang->line('application_type_integration_erp_2')?></option>
                                                        <option value="3" <?=set_select('type', 3, $integration->type == 3) ?>><?=$this->lang->line('application_type_integration_erp_3')?></option>
                                                    </select>
                                                </div>
                                                <?php if ($integration->type == 3 && !empty($logsitc)): ?>
                                                    <div class="col-md-3">
                                                        <label for="label_required"><?= lang('application_label_required') ?> (*)</label>
                                                        <select class="form-control" id="label_required" name="label_required" required>
                                                            <option value="0" <?=set_select('type', 0, $integration->label_required == 0) ?>><?=$this->lang->line('application_no')?></option>
                                                            <option value="1" <?=set_select('type', 1, $integration->label_required == 1) ?>><?=$this->lang->line('application_yes')?></option>
                                                        </select>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="col-md-3">
                                                        <label for="hash"><?= lang('application_hash_api_header_partner') ?> (*)</label>
                                                        <input type="text" class="form-control" id="hash" name="hash"
                                                               placeholder="<?= lang('application_enter_name') ?>"
                                                               value="<?= set_value('hash', $integration->hash) ?>"
                                                               disabled/>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="col-md-3">
                                                    <label for="active"><?= lang('application_visible_in_the_system') ?>
                                                        (*)</label>
                                                    <select class="form-control" id="active" name="active" required>
                                                        <option value="1" <?= set_select('active', 1, $integration->active == 1) ?>><?= lang('application_yes') ?></option>
                                                        <option value="0" <?= set_select('active', 0, $integration->active == 0) ?>><?= lang('application_no') ?></option>
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
                                                            echo "<option value='$provider[id]' ".set_select('provider', $provider['id'], $integration->provider_id == $provider['id']).">$provider[name]</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="form-group">
                                                <div class="col-md-12">
                                                    <h3>Links de Apoio</h3>
                                                    <p>Insira abaixo links de apoio para os seus usuários acessarem
                                                        tutoriais ou textos de ajuda para utilizar a integração.</p>
                                                    <div class="input-group d-flex justify-content-between">
                                                        <input style="flex: 3" type="text" class="form-control"
                                                               id="title"
                                                               placeholder="<?= lang('application_title') ?>">
                                                        <input style="flex: 3" type="url" class="form-control" id="link"
                                                               placeholder="Link">
                                                        <span style="flex: 1" class="input-group-btn">
                                                <button type="button" class="btn btn-success btn-flat col-md-12"
                                                        id="addLinkSupport"><i
                                                            class="fa fa-plus"></i> <?= lang('application_add'); ?></button>
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
                                        <div id="listLinksSupport">
                                            <?php if ($integration->support_link): ?>
                                                <?php $linksSupport = json_decode($integration->support_link); ?>
                                                <?php foreach ($linksSupport as $linkSupport): ?>
                                                    <div class="row mt-1">
                                                        <div class="form-group">
                                                            <div class="col-md-12">
                                                                <div class="input-group d-flex justify-content-between">
                                                                    <span style="flex: 3" class="mr-2"><input
                                                                                type="text" class="form-control"
                                                                                name="title[]"
                                                                                value="<?= $linkSupport->title ?>"/></span>
                                                                    <span style="flex: 3" class="mr-2"><input type="url"
                                                                                                              class="form-control"
                                                                                                              name="link[]"
                                                                                                              value="<?= $linkSupport->link ?>"/></span>
                                                                    <span style="flex: 1"><button type="button"
                                                                                                  class="btn btn-danger btn-flat removeLinkSupport"><i
                                                                                    class="fa fa-trash"></i></button></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if (!empty($integration->configuration_form ?? [])) { ?>
                            <div class="tab-pane" id="nav-configurations">
                                <div class="box">
                                    <div class="row" style="margin-left: 0px; margin-right: 0px;">
                                        <div class="row" style="margin-top: 20px">
                                            <div class="col-md-12">
                                                <div class="panel panel-default">
                                                    <div class="panel-heading">
                                                        <?=$this->lang->line('application_integration_configuration')?>
                                                    </div>
                                                    <div class="panel-body">
                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <?php
                                                                foreach ($integration->configuration_form ?? [] as $field => $value) {
                                                                    $fieldValue = $integration->configuration->{$value->name} ?? '';
                                                                    switch ($value->type) {
                                                                        case 'text':
                                                                        case 'number':
                                                                        case 'url':
                                                                            echo '<div class="form-group col-md-12 no-padding">
                                                                                <label for="' . $value->name . '">' . $value->label . '</label>
                                                                                <input type="'.$value->type.'" class="form-control" name="configurations[' . $value->name . ']" id="configurations[' . $value->name . ']" value="'.$fieldValue.'" required>
                                                                              </div>';
                                                                            break;
                                                                        case 'radio':
                                                                            echo '<div><label>'.$value->label.'</label></div>';
                                                                            echo '<div class="btn-group col-md-12">';
                                                                            foreach ($value->values ?? [] as $opt) {
                                                                                $checked = $fieldValue == $opt->value ? 'checked="checked"' : '';
                                                                                echo '<label class="col-md-1"><input '.$checked.' type="radio" id="configurations['.$value->name.'_'.$opt->value.']" name="configurations[' . $value->name . ']" value="'.$opt->value.'"> '.$opt->label.'</label>';
                                                                            }
                                                                            echo '</div>';
                                                                            break;
                                                                        case 'select':
                                                                            echo '<div><label>' . $value->label . '</label>';
                                                                            echo '<div class="form-group col-md-12 no-padding">';
                                                                            echo '<select class="form-control selectpicker" name="configurations[' . $value->name . ']" id="configurations[' . $value->name . ']" value="' . $fieldValue . '">';
                                                                            foreach ($value->values ?? [] as $opt) {
                                                                                $checked = $fieldValue == $opt->value ? 'selected' : '';
                                                                                echo '<option value="' . $opt->value . '" '.$checked.'>' . $opt->label . '</option>';
                                                                            }
                                                                            echo '</div></select></div>';
                                                                            break;
                                                                        case 'link':
                                                                            echo "<div>
                                                                                    <label>$value->label</label>
                                                                                    <div class='form-group col-md-12 no-padding'>
                                                                                        <a href='".base_url($value->name)."'>Clique para ser redirecionado</a>
                                                                                    </div>
                                                                                </div>";
                                                                            break;
                                                                    }
                                                                }
                                                                ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!--div class="row">
                                            <div class="col-md-12">
                                                <div class="panel panel-default">
                                                    <div class="panel-heading">
                                                        Campos Integração com Loja
                                                    </div>
                                                    <div class="panel-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div-->
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                        <div class="row">
                            <div class="col-md-12 col-xs-12">
                                <div class="box-footer">
                                    <div class="row">
                                        <div class="col-md-12 d-flex justify-content-between">
                                            <a href="<?= base_url('integrations/manageIntegration') ?>" class="btn btn-default col-md-2 ml-2"><?= lang('application_cancel'); ?></a>
                                            <?php if ($canUpdate): ?>
                                                <button type="submit" class="btn btn-primary col-md-2"><?= lang('application_save'); ?></button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" id="canUpdate" value="<?= $canUpdate ?>">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<script type="text/javascript">

    const imagePreLoad = `<?=base_url('assets/images/integration_erps')?>/${$('#imagePreLoad').val()}`;

    $(document).ready(function () {
        $("#mainIntegrationApiNav").addClass('active');
        $("#manageIntegrationErp").addClass('active');

        if ($('#listLinksSupport div').length === 0) {
            $('#head-listLinksSupport').hide();
        }

        $("#image").fileinput({
            initialPreview: [imagePreLoad],
            allowedFileExtensions: ["jpg", "png", "jpeg", "gif"],
            msgPlaceholder: "Selecione o banner para enviar",
            initialCaption: $('#imagePreLoad').val(),
            showCaption: true,
            showUpload: false,
            initialPreviewAsData: true,
            overwriteInitial: true,
            showUploadedThumbs: false,
            initialPreviewShowDelete: false,
            showRemove: false,
            showClose: false
        });

        if (parseInt($('#canUpdate').val()) === 0) {
            $('form input, form select, form button').prop('disabled', true);
        }

        $('#type').trigger('change');
        $("#provider").select2();
    });

    $('#addLinkSupport').on('click', function () {
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

    $(document).on('click', '.removeLinkSupport', function () {
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