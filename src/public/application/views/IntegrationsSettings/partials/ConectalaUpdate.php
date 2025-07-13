
<div class="row">
    <div class="col-sm-12">
        <div class="callout callout-info">
            <h4><?=$this->lang->line('application_migration_new_07');?></h4>
            <p><?=$this->lang->line('application_migration_new_08');?></p>
        </div>
    </div>
    <div class="container-fluid row">
    <div class="col-sm-3">
        <div class="form-group" id="div_api_url">
            <label>Api Url</label>
            <input type="text" name="api_url" value="<?= @$auth_data['api_url'] ?>" class="form-control" id="api_url" placeholder="">
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group" id="div_x_user_email">
            <label>Api Email</label>
            <input type="text" name="x-user-email" value="<?= @$auth_data['x-user-email'] ?>" class="form-control" id="x_user_email" placeholder="">
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group" id="div_x_api_key">
            <label>Api Key</label>
            <input type="text" name="x-api-key" value="<?= @$auth_data['x-api-key'] ?>" class="form-control" id="x_api_key" placeholder="">
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group" id="div_x_store_key">
            <label>Store Key</label>
            <input type="text" name="x-store-key" value="<?= @$auth_data['x-store-key'] ?>" class="form-control" id="x_store_key" placeholder="">
        </div>
    </div>
</div>
<div class="container-fluid row">
    <div class="col-sm-3">
        <div class="form-group" id="div_x_application_id">
            <label>x-application-id</label>
            <input type="text" name="x-application-id" value="<?= @$auth_data['x-application-id'] ?>" class="form-control" id="x_application_id" placeholder="">
        </div>
    </div>
    <div class="col-sm-2">
        <div class="checkbox" aria-checked="false">
            <label data-placement="top" data-toggle="popover" title="" data-content="">
                <input type="checkbox" name="auto_approve" id="" class="flat-red" <?=  @$integration['auto_approve'] == 0 ? 'checked' : '' ?>>&nbsp; <?=$this->lang->line('application_migration_new_25');?>
            </label>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group" id="div_int_to">
            <label>Endpoint Logística</label>
            <input type="text" name="logistic" class="form-control" id="logistic" value="<?= @$integration['logistic'] ?>" placeholder=""  readonly>                                                
        </div>
    </div>
    <div class="col-sm-2">
        <div class="form-group">
            <a class="btn btn-primary btn-alin" id="submit_form">
                <i class="fa fa-key"></i>&nbsp;&nbsp; <?=$this->lang->line('application_migration_new_14');?></a>
        </div>
    </div>
    <div class="col-sm-12" id="msg_success" style="display:none">
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h4><i class="icon fa fa-check"></i> <?=$this->lang->line('application_migration_new_15');?> <small class="text-white"><?=$this->lang->line('application_migration_new_16');?></small></h4>
        </div>
    </div>
    <div class="col-sm-12" id="msg_error" style="display:none">
        <div class="alert alert-danger alert-dismissible">
            <h4><i class="icon fa fa-times"></i><small class="text-white">Dados inválidos</small></h4>
        </div>
    </div>
    <div class="row" id="enabled" style="margin-top:1em;">
        <br><br><br><br>
        <!-- <div class="col-sm-12">
            <div class="col-sm-12">
                <span style="font-size:18px;"><b><?=$this->lang->line('application_migration_new_17');?></b></span><br>
                <small style="margin-top:-2px;"><?=$this->lang->line('application_migration_new_18');?></small>
            </div>
        </div> -->
        <br><br><br><br>
        <input type="hidden" name="mkt_type" value="conectala">
    </div>
</div>