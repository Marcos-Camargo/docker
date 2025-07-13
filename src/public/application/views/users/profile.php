<!--
SW Serviços de Informática 2019

Ver Profile

-->


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_myprofile";
    $this->load->view('templates/content_header', $data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
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
                <a type="button" href="<?= base_url('users/notification_config') ?>" class="btn btn-primary">
                    <?= $this->lang->line('application_notification'); ?>
                </a>
                <?php if (is_null($user_data['external_authentication_id'])) { // se tiver external não troca senha ?>
                    <a type="button" href="<?= base_url('users/changepassword') ?>" class="btn btn-primary">
                        <?= $this->lang->line('application_change_password'); ?>
                    </a>
                <?php } ?>

                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title"><?= $this->lang->line('application_profile') ?></h3>
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        <?php
                        if (validation_errors()) {
                            foreach (explode("</p>", validation_errors()) as $erro) {
                                $erro = trim($erro);
                                if ($erro != "") { ?>
                                    <div class="alert alert-error alert-dismissible" role="alert">
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span></button>
                                        <?php echo $erro . "</p>"; ?>
                                    </div>
                        <?php }
                            }
                        } ?>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <a href="<?= base_url('users/request_biller') ?>" class="btn btn-primary pull-right"><?= $this->lang->line('application_request_biller_module') ?></a>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label for="monthly_store">Plano Mensalista
                                    <button id="info_plan" class="btn-link"></button>
                                </label>
                                <select class="form-control" name="monthly_plan" disabled id="monthly_plan">
                                    <option value="">Sem plano</option>
                                    <?php foreach ($plans as $plan) {
                                        if ($plan['id'] === $user_company['plan_id']) $selected = 'selected';
                                        else $selected = ''; ?>
                                        <option <?php echo $selected ?> data-prices="<?= $plan['id'] ?>" value="<?= $plan['id'] ?>" <?= set_select('monthly_plan', $plan['id'], $store_data['monthly_plan'] == $plan['id']) ?>><?= $plan['description'] ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label><?= $this->lang->line('application_username'); ?></label>
                                <input type="text" class="form-control" value="<?php echo $user_data['username']; ?>" disabled />
                            </div>
                            <div class="form-group col-md-6">
                                <label><?= $this->lang->line('application_email'); ?></label>
                                <input type="text" class="form-control" value="<?php echo $user_data['email']; ?>" disabled />
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label><?= $this->lang->line('application_firstname'); ?></label>
                                <input type="text" class="form-control" value="<?php echo $user_data['firstname']; ?>" disabled />
                            </div>
                            <div class="form-group col-md-6">
                                <label><?= $this->lang->line('application_lastname'); ?></label>
                                <input type="text" class="form-control" value="<?php echo $user_data['lastname']; ?>" disabled />
                            </div>
                        </div>
                        <!-- <div class="form-group col-md-6">
                  <label>Gender</label>
                  <input type="text" class="form-control" value="<?php echo ($user_data['gender'] == 1) ? 'Male' : 'Gender'; ?>" disabled />
                </div> -->
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label><?= $this->lang->line('application_phone'); ?></label>
                                <input type="text" class="form-control" value="<?php echo $user_data['phone']; ?>" disabled />
                            </div>
                            <div class="form-group col-md-6">
                                <label><?= $this->lang->line('application_groups'); ?></label>
                                <input type="text" class="form-control" value="<?php echo $user_group['group_name']; ?>" disabled />
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label><?= $this->lang->line('application_company'); ?></label>
                                <input type="text" class="form-control" value="<?php echo $user_company['id'] . " - " . $user_company['name']; ?>" disabled />
                            </div>
                            <div class="form-group col-md-6">
                                <label><?php echo $this->lang->line('application_stores'); ?></label>
                                <select multiple="" class="form-control" readonly>
                                    <?php
                                    foreach ($user_stores as $store) {
                                        echo "<option>{$store['id']} - {$store['name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <?php if ($bank[0]['retorno'] == "1"){ ?>
                        <div class="row">
                            <hr>
                        </div>
                            <?php foreach($bank as $indice => $valorBancario) {?>
                            
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label><?= $this->lang->line('application_store'); ?></label>
                                    <input type="text" class="form-control" id="loja<?php echo $indice;?>" name="loja<?php echo $indice;?>" value="<?php echo $valorBancario['name']?>" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label><?= $this->lang->line('application_bank'); ?></label>
                                    <input type="text" class="form-control" id="banco<?php echo $indice;?>" name="banco<?php echo $indice;?>" value="<?php echo $valorBancario['bank']?>" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label><?= $this->lang->line('application_agency'); ?></label>
                                    <input type="text" class="form-control" id="agencia<?php echo $indice;?>" name="agencia<?php echo $indice;?>" value="<?php echo $valorBancario['agency']?>" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label><?= $this->lang->line('application_type_account'); ?></label>
                                    <input type="text" class="form-control" id="tipoconta<?php echo $indice;?>" name="tipoconta<?php echo $indice;?>" value="<?php echo $valorBancario['account_type']?>" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label><?= $this->lang->line('application_account'); ?></label>
                                    <input type="text" class="form-control" id="conta<?php echo $indice;?>" name="conta<?php echo $indice;?>" value="<?php echo $valorBancario['account']?>" disabled />
                                </div>
                            </div>
                            <?php   } ?>
                        <div class="row">
                            <hr>
                        </div>
                             
                        <?php }
                        foreach ($user_stores as $store) {
                        ?>
                            <div class="row">
                                <div class="form-group">
                                    <div class="form-group col-md-2">
                                        <label>Código</label>
                                        <input type="text" class="form-control" value="<?= $store['id'] ?>" readonly>
                                    </div>
                                    <div class="form-group col-md-10">
                                        <label>Chave de Integração - API - <?= $store['name'] ?></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" value="<?= $store['token_api'] ?>" readonly>
                                            <span class="input-group-btn">
                                                <button type="button" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                                            </span>
                                        </div>
                                    </div>
                                    <!--<div class="form-group col-md-4">
                            <label>URL Callback - API - <?= $store['name'] ?></label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="<?= $store['url_callback_integracao'] ?>" readonly>
                                <span class="input-group-btn">
                                    <button type="button"" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                                </span>
                            </div>
                        </div>-->
                                </div>
                            </div>
                        <?php
                        }
                        ?>
               
                    </div>
                    <!-- /.box-body -->
                </div>
                <!-- /.box -->
            </div>
            <!-- col-md-12 -->
        </div>
        <!-- /.row -->
    </section>
    <!-- /.content -->
</div>
<script>

var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>',
    csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
    
    $(function() {
        $('#profileNav').addClass('active');
        $('[data-toggle="tooltip"]').tooltip();
    });
    $('#verificaDispon').click(function() {
        const btn = $(this);
        const url = "<?= base_url('orders/fetchOrdersInvoiceData') ?>";

        btn.attr('disabled', true);
        $('#resultVerificaDispon').html('<i class="fa fa-spin fa-spinner"></i> Verificando...');

        $.ajax({
            url,
            type: "POST",
            data: {
                verify: true, 
                [csrfName]: csrfHash
            },
            dataType: 'json',
            success: response => {

                btn.attr('disabled', false);
                $('#resultVerificaDispon').empty();

            }
        });
    });
    $('.copy-input').click(function() {
        // Seleciona o conteúdo do input
        $(this).closest('.input-group').find('input').select();
        // Copia o conteudo selecionado
        const copy = document.execCommand('copy');
        if (copy) {
            Toast.fire({
                icon: 'success',
                title: "Conteúdo copiado com sucesso!"
            })
        } else {
            Toast.fire({
                icon: 'success',
                title: "Não foi possível copiar o conteúdo!"
            })
        }
    });

    $(document).ready(function() {
        plan = $('#monthly_plan').val();
        if (plan != '') {
            $('#info_plan').show();
        } else {
            $('#info_plan').hide();
        }
        $('#monthly_plan').on('click', () => {
            // if ()
            plan = $('#monthly_plan').val();
            if (plan != '') {
                $('#info_plan').show();
                $('#info_plan').attr('title', $('#monthly_plan').find(':selected').data('prices'));
            } else {
                $('#info_plan').hide();
            }
        })
    })
</script>
<style>
    label[for=new_password] {
        display: flex;
        justify-content: space-between;
    }

    label[for=new_password] i {
        cursor: pointer;
    }
</style>