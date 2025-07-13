<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "";
    $data['page_now'] = $this->data['page_now'];
    $this->load->view('templates/content_header', $data); ?>

    <div id="appMarketplace" class="mt-4">

        <!-- Main content -->
        <section class="content">
            <!-- Small boxes (Stat box) -->
            <div class="row">
                <div class="col-md-3">
                    <div class="alert alert-warning small-shadow-top" role="alert">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <i class="fas fa-exclamation-circle fa-4x"></i>
                            </div>
                            <div class="col-md-8">
                                <h4 class="alert-heading"><?= $this->lang->line('massive_rules') ?> <?php echo $this->data['by_type']; ?></h4>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <p>&nbsp;</p>
                                <p>
                                    <?= $this->lang->line('massive_rules_line_1') ?>
                                </p>
                                <p>
                                    <?= $this->lang->line('massive_rules_line_2') ?>
                                </p>
                                <p>
                                    <?= $this->lang->line('massive_rules_line_3') ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="white-background-content small-shadow-top">
                        <div class="row">
                            <div class="col-md-4">
                                <h3 style="color: #007CFF;">
                                    <?= $this->lang->line('step') ?>
                                    <span class="badge" style="background-color: #007CFF !important;">1</span>
                                </h3>
                                <h4><?= $this->lang->line('download_example_xls') ?></h4>
                                <p>
                                    <?= $this->lang->line('xls_model_cycle') ?>
                                </p>
                                <div><hr></div>
                                <?php
                                $link = '';
                                if ($this->data['sellercenter'] == 'conectala'){
                                    if ($this->data['by_type'] == 'marketplace'){
                                        $link = base_url('assets/files/sample_cycles_conectala_marketplace.csv');
                                    }else{
                                        $link = base_url('assets/files/sample_cycles_conectala_marketplace_store.csv');
                                    }
                                }else{
                                    if ($this->data['by_type'] == 'marketplace'){
                                        $link = base_url('assets/files/sample_cycles_marketplace.csv');
                                    }else{
                                        $link = base_url('assets/files/sample_cycles_marketplace_store.csv');
                                    }
                                }
                                ?>
                                <a href="<?=$link?>" class="text-decoration-none"><i class="fa fa-download"></i> <?= $this->lang->line('download_example_xls') ?></a>
                            </div>
                            <div class="col-md-4">
                                <h3 style="color: #007CFF;"><?= $this->lang->line('step') ?>
                                    <span class="badge" style="background-color: #007CFF !important;">2</span>
                                </h3>
                                <h4><?= $this->lang->line('download_data') ?></h4>
                                <p>
                                    <?= $this->lang->line('export_data_registered') ?>
                                </p>
                                <div><hr></div>
                                <div class="mb-5">
                                    <a href="<?php echo base_url('cycles/download_cycles/'.$this->data['by_type']); ?>" class="text-decoration-none">
                                        <i class="fa fa-download"></i>
                                        <?php
                                        if ($this->data['by_type'] == 'marketplace'){
                                        ?>
                                        <?= $this->lang->line('expor_all_cycles_by_marketplace') ?>
                                        <?php
                                        }else{
                                            ?>
                                            <?= $this->lang->line('expor_all_cycles_by_store') ?>
                                        <?php
                                        }
                                        ?>
                                    </a>
                                </div>
                                <div>
                                    <a href="<?php echo base_url('export/lojaxls'); ?>" class="text-decoration-none">
                                        <i class="fa fa-download"></i>
                                        <?= $this->lang->line('export_all_stores') ?>
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h3 style="color: #007CFF;">
                                    <?= $this->lang->line('step') ?>
                                    <span class="badge" style="background-color: #007CFF !important;">3</span>
                                </h3>
                                <h4><?= $this->lang->line('import_files') ?></h4>
                                <p>
                                    <?= $this->lang->line('import_xls') ?>
                                </p>
                                <div><hr></div>
                                <form action="<?php echo base_url('cycles/upload_massive/'.$this->data['by_type'].'/validate'); ?>" method="post" enctype="multipart/form-data">
                                    <div class="file-drop-area small-shadow-top mb-4">
                                        <span class="file-message"><?= $this->lang->line('drag_drop_file') ?></span>
                                        <input class="file-input-hidden" type="file" name="file">
                                    </div>
                                    <button type="button" class="btn btn-select-file btn-primary" onclick="selectFile()">
                                        <i class="fas fa-plus-circle"></i>
                                        <?= $this->lang->line('select_file') ?>
                                    </button>
                                    <button type="submit" class="btn btn-primary btn-validate-file ml-1" style="display: none;"><?= $this->lang->line('validate_file') ?></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- col-md-12 -->
            </div>

            <a href="<?php echo base_url('cycles') ?>" class="btn btn-default"><?= $this->lang->line('application_back'); ?></a>

            <!-- /.row -->
        </section>
        <!-- /.content -->

    </div>

    <script>

        function selectFile(){
            $('.file-input-hidden').click();
        }

        $(document).on('change', '.file-input-hidden', function() {

            var filesCount = $(this)[0].files.length;

            var textbox = $('.file-message');

            if (filesCount === 1) {
                var fileName = $(this).val().split('\\').pop();
                textbox.html('<h4>Arquivo Carregado</h4><span class="text-black">'+fileName+'</span>');
                $('.btn-select-file').removeClass('btn-primary').addClass('btn-default');
            } else {
                textbox.text(filesCount + ' <?= $this->lang->line('selected_files'); ?>');
            }
            $('.btn-validate-file').show();
        });
    </script>

</div>
<!-- /.content-wrapper -->
