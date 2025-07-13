<div class="row" id="form_conectala" >
    <div class="col-sm-12">
        <div class="callout callout-info">
            <h4><?=$this->lang->line('application_migration_new_07');?></h4>
            <p><?=$this->lang->line('application_migration_new_08');?></p>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="box-body">
        </div>
    </div>
    <div class="col-sm-2">
        <div class="form-group" id="div_api_url">
            <label for="exampleInputEmail1">
                  api_url
                <i class="fa fa-info-circle" data-toggle="tooltip"
                                                       title="Ex: http://teste.conectala.com.br/app/Api/V1/"></i></label>
            </label>
            <input type="text" name="api_url" class="form-control" id="api_url" placeholder=""  v-model.trim="api_url" >
        </div>
    </div>
    <div class="col-sm-2">
        <div class="form-group" id="div_x-user-email">
            <label>
                   x-user-email
<!--                <i class="fa fa-info-circle" data-toggle="tooltip" title="Nome do marketplace"></i>-->
            </label>
<!--            <input type="text" name="environment" class="form-control" id="environment" placeholder="">-->
        <input type="text" name="x-user-email" class="form-control" id="x_user_email" placeholder=""  v-model.trim="x_user_email" >
        </div>
    </div>
    <div class="col-sm-2" aria-checked="false" aria-disabled="false">
        <div class="form-group" id="div_x-api-key">
            <label>
                    x-api-key
            </label>
            <input type="text" name="x-api-key" class="form-control" id="x_api_key" placeholder=""  v-model.trim="x_api_key" >

        </div>
    </div>
    <div class="col-sm-2" aria-checked="false" aria-disabled="false">
        <div class="form-group" id="div_x_store_key">
            <label>
                x-store-key
            </label>
            <input type="text" name="x-store-key" class="form-control" id="x_store_key" placeholder=""  v-model.trim="x_store_key" >

        </div>
    </div>

    <div class="col-sm-2" aria-checked="false" aria-disabled="false">
        <div class="form-group" id="div_x_application_id">
            <label>
                x-application-id
            </label>
            <input type="text" name="x-application-id" class="form-control" id="x_application_id" placeholder=""  v-model.trim="x_application_id" >

        </div>
    </div>
    <div class="col-sm-2">
        <div class="form-group">
            <button :disabled="updating" type="button" class="btn btn-primary btn-alin" id="submit_form_conectala" ><?=$this->lang->line('application_migration_new_14');?></button>
        </div>
    </div>
 
    <div class="col-sm-3">
        <div class="checkbox" aria-checked="false">
            <label data-placement="top" data-toggle="popover" title="" data-content="">
                <input type="checkbox" name="auto_approve" id="auto_approve" class="flat-red">&nbsp; <?=$this->lang->line('application_migration_new_25');?> <i class="fa fa-info-circle" data-toggle="tooltip" title="Quando marcado, ao realizar a criação de lojas, já define que o lojista utilizará curadoria no seller center. Não altera sellers antigos."></i>
            </label>
        </div>
    </div>
        

   
</div>
<div class="row">
    <div class="col-sm-12" id="msg_success" style="display:none">
        <div class="alert alert-success alert-dismissible">
            <h4><i class="icon fa fa-check"></i><small class="text-white">Dados validados com sucesso.</small></h4>
        </div>
    </div>
    <div class="col-sm-12" id="msg_error" style="display:none">
        <div class="alert alert-danger alert-dismissible">
            <h4><i class="icon fa fa-times"></i><small class="text-white">Dados inválidos</small></h4>
        </div>
    </div>
    <div class="col-sm-12" id="msg_timeout" style="display:none">
        <div class="alert alert-default alert-dismissible">
            <h4><i class="icon fa fa-heartbeat"></i><small class="text-white">Servidor não está respondendo</small></h4>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="overlay-wrapper">
            <div class="overlay" v-show="generate">
                <i class="fas fa-3x fa-sync-alt fa-spin"></i>
                <div class="text-bold pt-2 pb-4">Aguarde, finalizando verificação...</div>

            </div>
            <div class="row" id="inabled" style="margin-top:1em;">
            <br><br><br><br>
            <br/><br/><br/><br/>           
            <input type="hidden" name="mkt_type" value="conectala">
        </div>
        </div>
    </div>
    <br>
</div>