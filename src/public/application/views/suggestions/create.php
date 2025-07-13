<?php include_once(APPPATH . '/third_party/zipcode.php'); ?>
<!--
SW Serviços de Informática 2019

Criar Lojas
 
-->
<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_add";
    $this->load->view('templates/content_header', $data); ?>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12">

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
                    <div class="box-header">
                        <h3 class="box-title"><?= $this->lang->line('application_new_suggestion'); ?></h3>
                    </div>
                    <form role="form" action="<?= base_url('suggestions/create') ?>" method="post">
                        <div class="box-body">
                            <div class="col-md-6">
                                <div class="container-fluid">
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <div class="form-group">
                                                <label for="title"><?= $this->lang->line('application_type_label'); ?>(*)</label>
                                                <select class="form-control" name="type" id="type" required>
                                                    <?php foreach ($types as $type) : ?>
                                                        <!-- <option>Fazer uma Pergunta</option> -->
                                                        <option <?= $type['selected'] ? 'selected="true"' : '' ?> value="<?= $type['value'] ?>"><?= $type['text'] ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <div class="form-group">
                                                <label for="title"><?= $this->lang->line('application_categories_label'); ?>(*)</label>
                                                <select class="form-control" name="categorie" id="categorie" required>
                                                    <?php foreach ($categories as $category) : ?>
                                                        <!-- <option>Fazer uma Pergunta</option> -->
                                                        <option <?= $category['selected'] ? 'selected="true"' : '' ?> <?= isset($category['disabled']) ? 'disabled="disabled"' : '' ?> <?= $category['value'] == '' ? '' : "value=\"" . $category['value'] . "\"" ?>><?= $category['text'] ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-12 col-xs-12 <?php echo (form_error('title')) ? 'has-error' : '';  ?>">
                                            <label for="title"><?= $this->lang->line('application_title'); ?>(*)</label>
                                            <input type="text" class="form-control" id="title" name="title" placeholder="<?= $this->lang->line('application_title') ?>" required />
                                            <?php echo '<i style="color:red">' . form_error('title') . '</i>'; ?>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-12 col-xs-12 <?php echo (form_error('description')) ? 'has-error' : '';  ?>">
                                            <label for="description"><?= $this->lang->line('application_description'); ?>(*)</label>
                                            <textarea type="text" class="form-control" id="description" maxlength="1000" name="description" placeholder="<?= $this->lang->line('application_enter_description'); ?>" required></textarea>
                                            <span id="char_description"></span><br />
                                            <span class="label label-warning" id="words_description" data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_explanation_of_forbidden_words'); ?>"></span>
                                            <?php echo '<i style="color:red">' . form_error('description') . '</i>'; ?>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-12 col-xs-12 <?php echo (form_error('tags')) ? 'has-error' : '';  ?>">
                                            <label for="tags"><?= $this->lang->line('application_tags'); ?>(*)</label>
                                            <input type="text" class="form-control" id="tags" name="tags" placeholder="<?= $this->lang->line('application_tags_label') ?>" required />
                                            <?php echo '<i style="color:red">' . form_error('tags') . '</i>'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary"><?= $this->lang->line('application_save'); ?></button>
                            <a href="<?php echo base_url('suggestions') ?>" class="btn btn-warning"><?= $this->lang->line('application_back'); ?></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
<script>
    $(document).ready(function() {
        $("#description").summernote({
            toolbar: [
                // [groupName, [list of button]]
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['font', ['strikethrough', 'superscript', 'subscript']],
                ['fontsize', ['fontsize']],
                ['para', ['ul', 'ol', 'paragraph']],
            ],
            height: 150,
            disableDragAndDrop: true,
            lang: 'pt-BR',
            shortcuts: false,
            callbacks: {
                onBlur: function(e) {
                    verifyWords(this);
                },
                onKeyup: function(e) {
                    // var conteudo = $(".note-editable").text();
                    var conteudo = $(".note-editable").html();
                    var limit = $('#description').attr('maxlength');
                    if (conteudo.length > limit) {
                        // $(".note-editable").text(conteudo.slice(0,-(conteudo.length-limit)));
                        $(".note-editable").html(conteudo.slice(0, -(conteudo.length - limit)));
                    }
                    characterLimit(this);
                }
            }
        });
    });
</script>