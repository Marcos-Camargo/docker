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
                <div class="col-md-12">
                    <div class="white-background-content small-shadow-top">
                        <?php
                        if ($this->data['result']['errors']){
                        ?>
                            <div class="row">
                                <div class="col-md-12">
                                    <span class="text-success pull-right">
                                        <i class="fas fa-check-circle"></i> <?= $this->lang->line('line_without_errors_cycle') ?>: <?php echo count($this->data['result']['success_itens']); ?>
                                    </span>
                                    <span style="width: 20px;" class="pull-right">
                                        &nbsp;
                                    </span>
                                    <span class="text-danger pull-right">
                                        <i class="fas fa-times-circle"></i> <?= $this->lang->line('line_with_errors_cycle') ?>: <?php echo count($this->data['result']['errors']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="row">
                                <table class="table table-bordered table-condensed table-hover">
                                    <thead>
                                        <tr>
                                            <th class="col-md-1"><?= $this->lang->line('line_cycle') ?></th>
                                            <th class="col-md-11"><?= $this->lang->line('error_description_cycle') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach ($this->data['result']['errors'] as $line => $errors){
                                        ?>
                                            <tr>
                                                <td class="text-center"><?php echo $line; ?></td>
                                                <td>
                                                    <?php
                                                    echo implode('<br>', $errors);
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="row">
                                <div><?= $this->lang->line('total_itens') ?>: <?php echo count($this->data['result']['rows']); ?></div>
                                <a href="<?php echo base_url('cycles/add_massive/'.$this->data['by_type']); ?>" class="btn btn-default mt-5" style="width: 240px;"><i class="fas fa-times-circle"></i> <?= $this->lang->line('cancel_import') ?></a>
                            </div>
                        <?php
                        }else{
                        ?>

                            <div class="row">
                                <div class="text-success pull-left">
                                    <i class="fas fa-check-circle fa-3x"></i>
                                </div>
                                <div class="col-md-11 pull-left">
                                    <span class="text-success" style="font-size: 20px;"><?= $this->lang->line('all') ?> <?php echo count($this->data['result']['rows']); ?> <?= strtolower($this->lang->line('line_without_errors_cycle')) ?></span>
                                    <br>
                                    <?= $this->lang->line('click_button_start_import_cycle') ?>
                                </div>
                            </div>

                            <div class="row">
                                <a href="<?php echo base_url('cycles/add_massive/'.$this->data['by_type']); ?>" class="btn btn-default mt-5 pull-left"><i class="fas fa-times-circle"></i> <?= $this->lang->line('cancel_import') ?></a>
                                <form action="<?php echo base_url('cycles/upload_massive/'.$this->data['by_type'].'/success'); ?>" method="post" enctype="multipart/form-data" style="margin-left: 20px; width: 280px;" class="pull-left mt-5">
                                    <textarea name="json" style="display: none;">
                                        <?php echo json_encode($this->data['result']['rows']); ?>
                                    </textarea>
                                    <button type="submit" class="btn btn-success" style="width: 240px;">
                                        <i class="fas fa-upload"></i> <?= $this->lang->line('start_import_cycle') ?>
                                    </button>
                                </form>
                            </div>

                        <?php
                        }
                        ?>
                    </div>
                </div>
                <!-- col-md-12 -->
            </div>

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
                textbox.html('<h4> <?= $this->lang->line('file_loaded') ?></h4><span class="text-black">'+fileName+'</span>');
                $('.btn-select-file').removeClass('btn-primary').addClass('btn-default');
            } else {
                textbox.text(filesCount + ' arquivos selecionado');
            }
            $('.btn-validate-file').show();
        });
    </script>

</div>
<!-- /.content-wrapper -->
