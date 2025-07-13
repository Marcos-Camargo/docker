<style>
    select.selectpicker {
        display: block !important;
    }

    body {
        color: #0066CC;
    }

    a,
    a.btn:link {
        text-decoration: none !important
    }

    .btn-alin {
        margin-top: 25px;
    }

    .text-white {
        color: white !important;
    }

    .m-top {
        margin-top: 20px;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #0066CC;
    }

    .select2-container--default .select2-selection--multiple {
        border: 1px solid #d2d6de !important;
        border-radius: 0;
        padding-left: 5px;
    }

    .validate::after {
        color: #dd4b39;
        content: '<?=$this->lang->line('application_migration_new_33');?>';
    }
</style>

<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage";
    $this->load->view('templates/content_header', $data); ?>
    <section class="content">
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
                <br>
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="active"><a href="#tab_1" data-toggle="tab">Integração</a></li>
                    </ul>
                    <div class="col-sm-12" <?php echo ($notIntegration == '0' ?: ' hidden') ?>>
                        <div class="callout callout-warning">
                            <h4>Atenção</h4>
                            <p>Você não tem a integração vinculada a nova tabela. Por favor, preencha todos os campos pois é necessário para atualizar</p>
                        </div>
                    </div>
                    <div class="tab-content">
                        <div class="tab-pane active" id="tab_1">
                            <form action="<?php echo base_url('IntegrationsSettings/save') ?>" method="POST">
                                <input type="text" name="id" hidden value="<?= @($integration['integration_id']) ?>">
                                <div class="box-body">
                                    <div class="row">
                                        <div class="col-sm-3">
                                            <div class="form-group" id="div_name">
                                                <label for="name"><?=$this->lang->line('application_migration_new_01');?></label>
                                                <input type="text" name="name" class="form-control" id="name" value="<?= @$integration['name'] ?>" placeholder="" required readonly>
                                            </div>
                                        </div>
                                        <div class="col-sm-3">
                                            <div class="form-group" id="div_int_to">
                                                <label for="int_to"><?=$this->lang->line('application_migration_new_02');?> <i class="fa fa-info-circle" data-toggle="tooltip" title="Nome do marketplace sem espaço, acentos e caracteres especiais."></i></label>
                                                <input type="text" name="int_to" class="form-control" id="int_to" value="<?= @$integration['int_to'] ?>" placeholder="" required readonly>
                                                <!-- <span class="help-block int_to_msg">Campo Obrigatório</span> -->
                                            </div>
                                        </div>
                                        <div class="col-sm-3" id="div_adlink_hide">
                                            <div class="form-group" id="div_adlink">
                                                <label for="adlink"><?=$this->lang->line('application_migration_new_03');?>  <i class="fa fa-info-circle" data-toggle="tooltip"
                                                                                                                                title="O campo de link do site é usado para gerar os links dos produtos no site com os produtos publicados"></i></label>
                                                <input type="text" name="adlink" class="form-control" value="<?= @$integration['adlink'] ?>" id="adlink" placeholder="">
                                            </div>
                                        </div>
                                        <div class="col-sm-3">
                                            <div class="form-group">
                                                <div class="form-check form-check-inline">
                                                    <label for="exampleInputEmail1"><?=$this->lang->line('application_migration_new_04');?></label><br>
                                                    <select class="form-control" name="active" id="active" style="width: 100%;" required>
                                                        <option value="0" <?= @$integration['active'] == 0 ? 'selected' : '' ?>><?=$this->lang->line('application_migration_new_04_V1');?></option>
                                                        <option value="1" <?= @$integration['active'] == 1 ? 'selected' : '' ?>><?=$this->lang->line('application_migration_new_04_v2');?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row content-type-skumkt-default" style="display: none">
                                        <div class="col-sm-3">
                                            <div class="form-group">
                                                <div class="form-check form-check-inline">
                                                    <label for="skumkt_default"><?=$this->lang->line('application_skumkt_default');?></label>
                                                    <select class="form-control" name="skumkt_default" id="skumkt_default" style="width: 100%;" required <?=$exist_product_published ? 'disabled' : '' ?>>
                                                        <option value="conectala" <?= @$integration['skumkt_default'] == 'conectala' ? 'selected' : '' ?>><?=$this->lang->line('application_sellercenter_default');?></option>
                                                        <option value="sequential_id" <?= @$integration['skumkt_default'] == 'sequential_id' ? 'selected' : '' ?>><?=$this->lang->line('application_sequential_id');?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-3 content-skumkt-default" style="display: none">
                                            <div class="form-group">
                                                <div class="form-check form-check-inline">
                                                    <label for="skumkt_sequential_initial_value"><?=$this->lang->line('application_initial_value');?></label>
                                                    <input type="number" name="skumkt_sequential_initial_value" class="form-control" value="<?= @$integration['skumkt_sequential_initial_value'] ?>" id="skumkt_sequential_initial_value" placeholder="" <?=$exist_product_published ? 'disabled' : '' ?>>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <span style="font-size:18px;"><b><?=$this->lang->line('application_migration_new_05');?></b></span><br>
                                            <small style="margin-top:-2px;"><?=$this->lang->line('application_migration_new_06');?></small>
                                        </div>
                                    </div>
                                    <br>

                                    <ul class="nav nav-tabs">
                                    <?php if(@$integration['mkt_type'] == "vtex") : ?>
                                        <li class="active"><a data-toggle="tab" href="#vtex-tab">Vtex</a></li>
                                    <?php  endif; ?>    
                                    <?php if(@$integration['mkt_type'] == "conectala") : ?>    
                                        <li class="active"><a data-toggle="tab" href="#conectala-tab">Conectala</a></li>
                                    </ul>
                                    <?php  endif; ?>    
                                    <div class="tab-content">
                                        <?php if(@$integration['mkt_type'] == "vtex") : ?>
                                            <div id="vtex-tab" class="tab-pane active">
                                                <div class="">                                               
                                                    <?php $this->load->view('IntegrationsSettings/partials/vtexUpdate.php'); ?>
                                                </div>
                                            </div>
                                         <?php  endif; ?>    
                               
                                        <?php if(@$integration['mkt_type'] == "conectala") : ?>      
                                            <div id="conectala-tab" class="tab-pane active" >
                                            <div class="">                                                
                                                    <?php $this->load->view('IntegrationsSettings/partials/ConectalaUpdate.php'); ?>
                                                </div>
                                            </div>
                                        <?php  endif; ?>    
                                    </div>
                                </div>
                            <div class="row" id="">
                                <div class="col-sm-6">
                                    <a href="<?php echo base_url('IntegrationsSettings/') ?>" class="btn btn-default">Voltar</a>
                                </div>
                                <div class="col-sm-6" id="disabledUpdate">
                                    <button class="btn btn-success pull-right" id="update_form" style="margin-right:10px"><?=$this->lang->line('application_migration_new_34');?></button>
                                </div>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
    </section>
</div>

<script type="text/javascript">
    var base_url = "<?php echo base_url(); ?>";
    $('#msg_success').hide();
    $('#msg_error').hide();
    //$('#inabled').show();
    $('#disabledUpdate').show();


    (function($) {
        $.fn.buttonLoader = function(action) {
            var self = $(this);
            if (action == 'start') {
                if ($(self).attr("disabled") == "disabled") {
                    e.preventDefault();
                }
                $('.has-spinner').attr("disabled", true);
                $(self).attr("disabled", true);
                $(self).attr('data-btn-text', $(self).text());
                $(self).html('<span class="spinner disabled"><i class="fa fa-spinner fa-spin"></i></span>&nbsp;&nbsp; Aguarde');
                $(self).addClass('active');
            }
            if (action == 'stop') {
                $(self).attr("disabled", false);
                $(self).html('<span class="spinner disabled"></span>&nbsp;&nbsp;Validar dados');
                $(self).removeClass('active');
                $('.has-spinner').removeAttr("disabled");
            }
        }
    })(jQuery);

    $('.select2').select2();

    function formatState (state) {
        if (!state.id) {
            return state.text;
        }
        const $state = $(
            '<span><span></span></span>'
        );
        let optionText = state.text;
        let newText = optionText.split(" - ");
        $state.find("span").text(newText[0]);
        $state.val(newText[0]);

        return $state;
    };

    $(".tradePolicies").select2({
        templateSelection: formatState,
        theme: "classic"
    });

    $("#MainsettingNav").addClass('active menu-open');
    $("#IntegrationsettingNav").addClass('active');
    $('[data-toggle="popover"]').popover();

    $('input[type="checkbox"].flat-red, input[type="radio"].flat-red').iCheck({
        checkboxClass: 'icheckbox_flat-blue',
        radioClass: 'iradio_flat-blue'
    })

    $('#submit_form').click(function() {

        var name = $("#name").val();
        var int_to = $("#int_to").val();
        var accountName = $("#accountName").val();
        var environment = $("#environment").val();
        var suffixdns = $("#suffixdns").val();
        var api_key = $("#api_key").val();
        var api_token = $("#api_token").val();

        if (name == '') {
            $('#div_name').addClass('has-error').addClass('validate');
        } else {
            $('#div_name').removeClass('has-error').removeClass('validate');
        }

        if (int_to == '') {
            $('#div_int_to').addClass('has-error').addClass('validate');
        } else {
            $('#div_int_to').removeClass('has-error').removeClass('validate');
        }

        if (adlink == '') {
            $('#div_adlink').addClass('has-error').addClass('validate');
        } else {
            $('#div_adlink').removeClass('has-error').removeClass('validate');
        }

        if (accountName == '') {
            $('#div_accountName').addClass('has-error').addClass('validate');
        } else {
            $('#div_accountName').removeClass('has-error').removeClass('validate');
        }

        if (environment == '') {
            $('#div_environment').addClass('has-error').addClass('validate');
        } else {
            $('#div_environment').removeClass('has-error').removeClass('validate');
        }

        if (tradesPolicies == '') {
            $('#div_tradesPolicies').addClass('has-error').addClass('validate');
        } else {
            $('#div_tradesPolicies').removeClass('has-error').removeClass('validate');
        }

        if (api_key == '') {
            $('#div_api_key').addClass('has-error').addClass('validate');
        } else {
            $('#div_api_key').removeClass('has-error').removeClass('validate');
        }

        if (api_token == '') {
            $('#div_api_token').addClass('has-error').addClass('validate');
        } else {
            $('#div_api_token').removeClass('has-error').removeClass('validate');
        }


        var btn = $(this);
        $(btn).buttonLoader('start');
        setTimeout(function() {
            $(btn).buttonLoader('stop');
        }, 3000);
        var cols = "";
        $.ajax({
            url: base_url + 'IntegrationsSettings/verify',
            type: 'POST',
            dataType: 'json',
            data: {
                name: $('#name').val(),
                int_to: $('#int_to').val(),
                adlink: $('#adlink').val(),
                accountName: $('#accountName').val(),
                environment: $('#environment').val(),
                tradesPolicies: $('#tradesPolicies').val(),
                suffixdns: $('#suffixdns').val(),
                active: $('#active option').filter(':selected').val(),
                api_key: $('#api_key').val(),
                api_token: $('#api_token').val()
            },
            success: function(result) {
                if (result.statusCode == 200) {
                    $('#msg_success, #inabled, #disabledCreate').show();
                    $('#msg_error').hide();
                    $('#msg_timeout').hide();
                }else{
                    $('#msg_error').show();
                    $('#msg_timeout').hide();
                    $('#msg_success, #inabled, #disabledCreate').hide();
                }
            },
        });
    });

    $('#update_form').click(function() {
        var tradesPolicies = $("#tradesPolicies").val();
        if (tradesPolicies == '') {
            $('#div_tradesPolicies').addClass('has-error').addClass('validate');
        } else {
            $('#div_tradesPolicies').removeClass('has-error').removeClass('validate');
        }
    });

    $(document).ready(function() {
        // $(".filter-button").click(function() {
        //     var value = $(this).attr('data-filter');
        //     if (value == "vtex") {
        //         $('.filter').show('vtex');
        //     }
        //     if (value == "conectala") {
        //         $('.filter').show('conectala-tab');
        //         $('.filter').filter('#vtex-tab').hide()
        //     }
        //      else {
        //         $(".filter").not('.' + value).hide('3000');
        //         $('.filter').filter('.' + value).show('3000');
        //     }
        //     if ($(".filter-button").removeClass("active")) {
        //         $(this).removeClass("active");
        //     }
        //     $(this).addClass("active");
        // });
        $('#skumkt_default').trigger('change');

        if ($('.nav.nav-tabs li.active [href="#vtex-tab"]').is(':visible')) {
            $('.content-type-skumkt-default').show();
        }
    });

    //$('.occ').hide();
    $('#btn_vtex , #btn_conectala').click(function(event) {
        event.preventDefault();
    });

    $('#skumkt_default').on('change', function(){
        if ($(this).val() === 'sequential_id') {
            $('.content-skumkt-default').show();
        } else {
            $('.content-skumkt-default').hide();
        }
    });
</script>