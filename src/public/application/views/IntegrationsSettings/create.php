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
    .m-top {
        margin-bottom: 25px;
    }
    .m-button {
        margin-bottom: 25px;
    }
    #int_to{
        text-transform: capitalize;
    }
    #searchButton{
        display: inherit;
        float: right;
        margin-top: -34px;
        z-index: 55!important;
        position: inherit;
        height: 34px;
        padding: 11px 23px 10px 10px;
    }
    #searchButton:hover{
        cursor: pointer;
    }
    text-person{
        color:#dd4b39;
        font-family: 'Source Sans Pro','Helvetica Neue',Helvetica,Arial,sans-serif;
        font-weight: 400;
    }
</style>

<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage";
    $this->load->view('templates/content_header', $data); ?>

    <section class="content" id="appIntegration">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <br>
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="active"><a href="#tab_1" data-toggle="tab">Integração</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="tab_1">
                            <form action="<?php echo base_url('IntegrationsSettings/save') ?>" method="POST">
                                <div class="box-body">
                                    <div class="row">
                                        <div class="col-sm-3">
                                            <div class="form-group" id="div_name">
                                                <label for="name"><?= $this->lang->line('application_migration_new_01'); ?>
                                                    <i class="fa fa-info-circle" data-toggle="tooltip"
                                                       title="Nome do marketplace"></i></label>
                                                <input type="text" name="name" class="form-control" id="name"
                                                       placeholder="" required @keyup="suggestIntTo" v-model.trim="name">
                                            </div>
                                        </div>
                                        <div class="col-sm-3">
                                            <div class="form-group" id="div_int_to">
                                                    <label for="int_to">
                                                        <?= $this->lang->line('application_migration_new_02'); ?>
                                                        <i class="fa fa-info-circle" id="verifyIfExistNumber" data-toggle="tooltip" title="Nome do marketplace sem espaço, acentos e caracteres especiais."></i>
                                                    </label>
                                                    <input type="text" name="int_to" class="form-control" id="int_to" placeholder="" required v-model.trim="int_to">
                                                    <span class="input-group-addon" id="searchButton"><i class="fa fa-search"></i></span>
                                                    <span class="hint">Não será modificado após criar a integração.</span>
                                            </div>
                                        </div>
                                        <div class="col-sm-3" id="div_adlink_hide">
                                            <div class="form-group" id="div_adlink">
                                                <label for="adlink"><?= $this->lang->line('application_migration_new_03'); ?>
                                                    <i class="fa fa-info-circle" data-toggle="tooltip"
                                                       title="O campo de link do site é usado para gerar os links dos produtos no site com os produtos publicados"></i></label>
                                                <input type="text" name="adlink" class="form-control" id="adlink"
                                                       placeholder="" :readonly="disableFields">
                                            </div>
                                        </div>
                                        <div class="col-sm-3">
                                            <div class="form-group">
                                                <div class="form-check form-check-inline">
                                                    <label for="exampleInputEmail1"><?= $this->lang->line('application_migration_new_04'); ?>
                                                        <i class="fa fa-info-circle" data-toggle="tooltip"
                                                           title="Status da integração"></i></label><br>
                                                    <select class="form-control" name="active" id="active"
                                                            style="width: 100%;" required>
                                                        <option value="0"><?= $this->lang->line('application_migration_new_04_V1'); ?></option>
                                                        <option value="1"><?= $this->lang->line('application_migration_new_04_v2'); ?></option>
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
                                                    <select class="form-control" name="skumkt_default" id="skumkt_default" style="width: 100%;" required>
                                                        <option value="conectala"><?=$this->lang->line('application_sellercenter_default');?></option>
                                                        <option value="sequential_id"><?=$this->lang->line('application_sequential_id');?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-3 content-skumkt-default" style="display: none">
                                            <div class="form-group">
                                                <div class="form-check form-check-inline">
                                                    <label for="skumkt_sequential_initial_value"><?=$this->lang->line('application_initial_value');?></label>
                                                    <input type="number" name="skumkt_sequential_initial_value" class="form-control" value="" id="skumkt_sequential_initial_value" placeholder="">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <span style="font-size:18px;"><b><?= $this->lang->line('application_migration_new_05'); ?></b></span><br>
                                            <small style="margin-top:-2px;"><?= $this->lang->line('application_migration_new_06'); ?></small>
                                        </div>
                                    </div>
                                    <br>

                                    <button class="filter-button btn btn-default" id="btn_vtex"
                                            data-filter="vtex" type="button">VTEX
                                    </button>
                                    <button class="filter-button btn btn-default" id="btn_conectala" data-filter="conectala" type="button">
                                        Conectala
                                    </button>
                                    <br><br><br><br>
                                    <div class="row" id="form_int">
                                        <div class="gallery_product  filter conectala">
                                            <div class="tab-pane ">
                                                <div class="content change_form">
                                                <?php $this->load->view("IntegrationsSettings/partials/conectalaCreate.php"); ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="gallery_product filter vtex">
                                            <div class="tab-pane">
                                                <div class="content change_form">
                                                    <?php $this->load->view('IntegrationsSettings/partials/vtexCreate.php'); ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                            <div class="row" id="">
                                <div class="col-sm-6">
                                    <a href="<?php echo base_url('IntegrationsSettings/') ?>" class="btn btn-default">Voltar</a>
                                </div>
                                <div class="col-sm-6" id="disabledCreate">
                                    <button class="btn btn-success pull-right" id="update_form" style="margin-right:10px"><?=$this->lang->line('application_migration_new_31');?></button>
                                </div>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
    </section>
</div>

<script src="<?php echo base_url('assets/vue/vue.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/vue-resource.min.js') ?>" type="text/javascript"></script>

<script type="application/javascript">

    var base_url = "<?php echo base_url(); ?>";

    const app = new Vue({
        el: '#appIntegration',
        data: {
            disableFields: true,
            updating: false,
            generate: false,
            name: '',
            int_to: '',
            accountName: '',
            environment: '',
            suffixdns: '',
            showUrl: false,
            api_url: '',
            x_user_email: '',
            x_api_key: '',
            x_application_id: '',
            x_store_key:''
        },
        computed: {},
        mounted() {},
        ready: function () {},
        methods: {
            suggestIntTo(){
                console.log(this.name)
                this.int_to = this.name.replace(/[^a-zA-Z]+/g, "");
            },
            mountUrl(){
                this.showUrl = true;
                document.getElementById("complete-url").innerText = "URL: " + this.accountName + "." + this.environment + this.suffixdns;
            }
        }
    });

    $("#MainsettingNav").addClass('active menu-open');
    $("#IntegrationsettingNav").addClass('active');
    $('#msg_success').hide();
    $('#msg_error').hide();
    $('#msg_timeout').hide();
    $('#inabled').hide();
    $('#disabledCreate').hide();
    $('.conectala').hide();
    $('.vtex').hide();
    
    

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
        templateSelection: formatState
    });

    //$('input[type="checkbox"].flat-red').prop('checked', true);

    $('input[type="checkbox"].flat-red, input[type="radio"].flat-red').iCheck({
        checkboxClass: 'icheckbox_flat-blue',
        radioClass: 'iradio_flat-blue'
    })

    $('#submit_form_vtex').click(function(e) {        
        e.preventDefault();
        submitVerify(); // CHAMA O BATCH PARA ATUALIZAR OS TRADE POLICIES
    });

    $('#submit_form_conectala').click(function(e) {
        
        e.preventDefault();
        submitVerifyConectala(); // CHAMA O BATCH PARA ATUALIZAR OS TRADE POLICIES
    });

    function submitVerifyConectala(){
    var name = $("#name").val();
    var int_to = $("#int_to").val();
    var api_url = $("#api_url").val();
    var x_user_email = $("#x_user_email").val();
    var x_api_key = $("#x_api_key").val();
    var x_store_key = $("#x_store_key").val();

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

    if (x_user_email == '') {
        $('#div_api_url').addClass('has-error').addClass('validate');
    } else {
        $('#div_api_url').removeClass('has-error').removeClass('validate');
    }

    if (x_api_key == '') {
        $('#div_x_api_key').addClass('has-error').addClass('validate');
    } else {
        $('#div_x_api_key').removeClass('has-error').removeClass('validate');
    }

    if (x_store_key== '') {
        $('#div_x_store_key').addClass('has-error').addClass('validate');
    } else {
        $('#div_x_store_key').removeClass('has-error').removeClass('validate');
    }

    if(name === '' || int_to === '' || x_user_email === '' || x_api_key === '' || api_url === '' || x_store_key === ''){
        app.updating = false;
        return;
    }else{
        app.updating = true;
        submitVerifyAjaxConectala();
    }

    }

    function submitVerify(){
        
        var name = $("#name").val();
        var int_to = $("#int_to").val();
        var adlink = $("#adlink").val();
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

        if(name === '' || int_to === '' || adlink === '' || accountName === '' || environment === '' || api_key === '' || api_token === ''){
            app.updating = false;
            return;
        }else{
            app.updating = true;
            submitVerifyAjax();
        }

    }

    function submitVerifyAjaxConectala(){
        app.generate = true;
        var btn = $(this);
        $(btn).buttonLoader('start');
        setTimeout(function() {
            $(btn).buttonLoader('stop');
        }, 3000);

        var cols = "";
        $.ajax({
            url: base_url + 'IntegrationsSettings/verifyConectala',
            type: 'POST',
            dataType: 'json',
            data: {
                name: $('#name').val(),
                int_to: $('#int_to').val(),
                api_url: $('#api_url').val(),
                x_user_email: $('#x_user_email').val(),
                x_api_key: $('#x_api_key').val(),
                x_store_key: $('#x_store_key').val(),
                x_application_id: $('#x_application_id').val(),
                active: $('#active option').filter(':selected').val(),
            },
            success: function(result) {
                console.log(result)
                app.generate = false;
                app.updating = false;
                if (result == 200) {
                    $('#msg_success, #inabled, #disabledCreate').show();
                    $('#msg_error').hide();
                    $('#msg_timeout').hide();
                }else if (result == 408){
                    $('#msg_timeout').show();
                    $('#msg_error').hide();
                    $('#msg_success, #inabled, #disabledCreate').hide();
                }else {
                    $('#msg_error').show();
                    $('#msg_timeout').hide();
                    $('#msg_success, #inabled, #disabledCreate').hide();
                }
            },
            error: function(error){
                app.generate = false;
                app.updating = false;
                console.log(error)
            }
        });
    }

    function submitVerifyAjax(){
        app.generate = true;
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
                console.log(result)
                app.generate = false;
                app.updating = false;
                $('#tradesPolicies').html('');
                if (result.statusCode == 200) {
                    for(var i = 0; i < result.data.length; i++){
                        $('#tradesPolicies').append('<option value="' + result.data[i]['Id'] + '">' + result.data[i]['Id'] + ' - ' + result.data[i]['Name'] + '</option>');
                    }
                    $('#msg_success, #inabled, #disabledCreate').show();
                    $('#msg_error').hide();
                    $('#msg_timeout').hide();
                }else if (result.statusCode == 408){
                    $('#msg_timeout').show();
                    $('#msg_error').hide();
                    $('#msg_success, #inabled, #disabledCreate').hide();
                }else {
                    $('#msg_error').show();
                    $('#msg_timeout').hide();
                    $('#msg_success, #inabled, #disabledCreate').hide();
                }
            },
            error: function(error){
                app.generate = false;
                app.updating = false;
                console.log(error)
            }
        });
    }

    $('#create_form').click(function() {
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
        //     if (value == "all") {
        //         $('.filter').show('1000');
        //     } else {
        //         $(".filter").not('.' + value).hide('3000');
        //         $('.filter').filter('.' + value).show('3000');
        //     }
        //     if ($(".filter-button").removeClass("active")) {
        //         $(this).removeClass("active");
        //     }
        //     $(this).addClass("active");
        // });
        $('#skumkt_default').trigger('change');
    });

    $('#btn_vtex , #btn_conectala').click(function(event) {
        
        if(event.currentTarget.id == 'btn_conectala'){
            $('#btn_vtex').hide();
            $('.conectala').show();
            //$('#form_int').html(`<div class="gallery_product active filter conectala"><div class="tab-pane active"><div class="content change_form"><?php //$this->load->view("IntegrationsSettings/partials/conectalaCreate.php"); ?></div></div></div>`);
            $('#div_adlink_hide').hide();
            $('.vtex').remove();
            app.generate = false;
            app.updating = false;
        }else{
            $('#btn_conectala').hide();
            $('.vtex').show();
            $('.conectala').remove();
            //$('#form_int').html(`<div class="gallery_product active filter vtex"><div class="tab-pane active"><div class="content change_form"><?php //$this->load->view('IntegrationsSettings/partials/vtexCreate.php'); ?></div></div></div>`);
            app.generate = false;
            app.updating = false;
        }
        
    });

    // SE EXISTIR CARACTER ESPECIAL OU ESPAÇO
    $('#searchButton').click(function(){        
        let value = $('#int_to').val();
        if(!value){
            return ;
        }

        if(!isNaN(value)){
            $('#verifyIfExistNumber').html(`<span class="text-person"> Não pode ser apenas números</span>`);
            $('#div_int_to').addClass('has-error');
            $('#int_to').focus();
            return false;
        }

        var letters = /^[a-zA-Z0-9]*$/;
        if(!value.match(letters))
        {
            $('#verifyIfExistNumber').html(`<span class="text-person"> Sem espaço, acentos e caracteres especiais.</span>`);
            $('#div_int_to').addClass('has-error');
            $('#int_to').focus();
            return false;
        }else{
            $('#verifyIfExistNumber').empty();
            $('#div_int_to').removeClass('has-error').removeClass('validate');
            verifyIntTo( value );
        }
    });

    // VERIFICAR SE JÁ EXISTE O INT_TO NO BANCO
    function verifyIntTo(value) {
        $.ajax({
            url: base_url + "integrationsSettings/getVerifyIntTo",
            type: "POST",
            data: {
                name: value,
            },
            success: function (data) {
                var result = JSON.parse(data);
                if (result !== null && result.int_to.toUpperCase() === value.toUpperCase()) {
                    AlertSweet.fire({
                        icon: 'Error',
                        title: 'Esse nome já está sendo usado!'
                    });
                    $('#div_int_to').addClass('has-error');
                    $('#verifyIfExistNumber').empty();
                    // $('#int_to').val('').focus();
                    return false;
                }else{
                    app.disableFields = false
                    $('#verifyIfExistNumber').empty();
                    $('#verifyIfExistNumber').html('&nbsp;<span style="color: #00dd00;"> Disponível</span');
                }
            }
        });
        $('#verifyIfExistNumber').html(`&nbsp;<span style="font-family:'Source Sans Pro','Helvetica Neue',Helvetica,Arial,sans-serif;font-weight: 400;"> Verificando...</span>`);
    }

    $('#skumkt_default').on('change', function(){
        if ($(this).val() === 'sequential_id') {
            $('.content-skumkt-default').show();
        } else {
            $('.content-skumkt-default').hide();
        }
    });

    $('#btn_vtex').on('click', function(){
        $('.content-type-skumkt-default').show();
    });
</script>