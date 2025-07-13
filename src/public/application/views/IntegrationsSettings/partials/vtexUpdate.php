
<div class="row">
    <div class="col-sm-12">
        <div class="callout callout-info">
            <h4><?=$this->lang->line('application_migration_new_07');?></h4>
            <p><?=$this->lang->line('application_migration_new_08');?></p>
        </div>
    </div>
    <div class="container-fluid row">
        <div class="box-body">
            <blockquote style="margin-top:6em;margin-left: 14px;">
                <span><b style="font-weight: 400;font-size: 15px;">A url é formada por 3 fraguimentos: o AccountName, enviroment e sufix. <br/> Ex: conectala.myvtex.com</b></span><br>
            </blockquote>
        </div>
    <div class="col-sm-3">
        <div class="form-group" id="div_accountName">
            <label for="exampleInputEmail1"><?=$this->lang->line('application_migration_new_09');?></label>
            <input type="text" name="accountName" value="<?= @$auth_data['accountName'] ?>" class="form-control" id="accountName" placeholder="">
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group" id="div_environment">
            <label for="exampleInputEmail1"><?=$this->lang->line('application_migration_new_10');?></label>
            <input type="text" name="environment" value="<?= @$auth_data['environment'] ?>" class="form-control" id="environment" placeholder="">
        </div>
    </div>
    <div class="col-sm-3" aria-checked="false" aria-disabled="false">
        <div class="form-group" id="div_suffixdns">
            <label><?=$this->lang->line('application_migration_new_11');?></label>
            <select class="form-control select2" name="suffixdns" id="suffixdns" data-placeholder="" style="width: 100%;" required>
                <option value=".com" <?=  @$auth_data['suffixDns'] == '.com' ? 'selected' : '' ?>><?=$this->lang->line('application_migration_new_11_v1');?></option>
                <option value=".com.br" <?=  @$auth_data['suffixDns'] == '.com.br' ? 'selected' : '' ?>><?=$this->lang->line('application_migration_new_11_v2');?></option>
            </select>
        </div>
    </div>
</div>
<div class="container-fluid row">
    <div class="col-sm-5">
        <div class="form-group" id="div_api_key">
            <label for="exampleInputEmail1"><?=$this->lang->line('application_migration_new_12');?></label>
            <input type="password" name="api_key" value="<?= @$auth_data['X_VTEX_API_AppKey'] ?>" class="form-control" id="api_key" placeholder="">
        </div>
    </div>
    <div class="col-sm-5">
        <div class="form-group" id="div_api_token">
             <label for="exampleInputEmail1"><?=$this->lang->line('application_migration_new_13');?></label>
            <input type="password" name="api_token" value="<?= @$auth_data['X_VTEX_API_AppToken'] ?>" class="form-control" id="api_token">
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
    <div class="row" id="inabled" style="margin-top:1em;">
        <br><br><br><br>
        <div class="col-sm-12">
            <div class="col-sm-12">
                <span style="font-size:18px;"><b><?=$this->lang->line('application_migration_new_17');?></b></span><br>
                <small style="margin-top:-2px;"><?=$this->lang->line('application_migration_new_18');?></small>
            </div>
        </div>
        <br><br><br><br>
        <div class="container-fluid">
            <div class="col-sm-2">
                <div class="form-group" id="">
                    <label for="minimum_stock"><?=$this->lang->line('application_migration_new_19');?></label>
                    <input type="number" name="minimum_stock" value="<?= @$integration['minimum_stock'] ?>" class="form-control" id="minimum_stock">
                </div>
            </div>
            <div class="col-sm-2">
                <div class="form-group" id="">
                    <label for="ref_id"><?=$this->lang->line('application_migration_new_20');?></label>
<!--                    <input type="text" name="ref_id" value="--><?php //= @$integration['ref_id'] ?><!--" class="form-control" id="ref_id">-->
                    <select class="form-control select2" name="ref_id" id="ref_id" style="width: 100%;">
                        <option value="SKUMKT" <?= @$integration['ref_id'] == 'SKUMKT' ? 'selected' : '' ?>>SKUMKT</option>
                        <option value="ONLYID" <?= @$integration['ref_id'] == 'ONLYID' ? 'selected' : ''  ?>>ONLYID</option>
                        <option value="FORCEREFID" <?= @$integration['ref_id'] == 'FORCEREFID' ? 'selected' : ''  ?>>FORCEREFID</option>
                    </select>
                </div>
            </div>

            <div class="col-sm-2" aria-checked="false" aria-disabled="false">
                <div class="form-group" id="div_tradesPolicies">
                    <label><?=$this->lang->line('application_migration_new_24');?></label>
                    <select class="form-control select2 tradePolicies" name="tradesPolicies[]" id="tradesPolicies" multiple="multiple" data-placeholder="" style="width: 100%;" required>
                        <?php foreach ($trade_policies as $trade_policy) : ?>
                            <option value="<?= $trade_policy['trade_policy_id'] ?>" <?= in_array($trade_policy['trade_policy_id'], $integration['tradesPolicies']) ? 'selected' : '' ?>><?= $trade_policy['trade_policy_id'] ?> - <?= $trade_policy['trade_policy_name'] ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>

        </div>
        <br>
        <div class="col-sm-4">
            <div class="checkbox" aria-checked="false">
                <label data-placement="top" data-toggle="popover" title="" data-content="">
                    <input type="checkbox" name="auto_approve" id="" class="flat-red" <?=  @$integration['auto_approve'] == 0 ? 'checked' : '' ?>>&nbsp; <?=$this->lang->line('application_migration_new_25');?>
                </label>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="checkbox" aria-checked="false">
                <label>
                    <input type="checkbox" name="update_product_specifications" id="update_product_specifications" class="flat-red" <?=  @$integration['update_product_specifications'] == 1 ? 'checked' : '' ?>>&nbsp; <?=$this->lang->line('application_migration_new_26');?> <i class="fa fa-info-circle" data-toggle="tooltip" title="Atualizará os atributos do produto se marcado (atributos da categoria do produto)."></i>
                </label>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="checkbox" aria-checked="false">
                <label>
                    <input type="checkbox" name="update_sku_specifications" id="update_sku_specifications" class="flat-red" <?=  @$integration['update_sku_specifications'] == 1 ? 'checked' : '' ?>>&nbsp; <?=$this->lang->line('application_migration_new_27');?> <i class="fa fa-info-circle" data-toggle="tooltip" title="Atualizará os atributos do sku se marcado (atributos com carater de variação)."></i>
                </label>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="checkbox" aria-checked="false">
                <label>
                    <input type="checkbox" name="auto_approve_two" id="auto_approve_two" class="flat-red" <?=  @$integration['auto_approve_two'] == 1 ? 'checked' : '' ?>>&nbsp; <?=$this->lang->line('application_migration_new_30');?> <i class="fa fa-info-circle" data-toggle="tooltip" title="Remove a necessidade que o produto seja aprovado manualmente na vtex."></i>
                </label>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="checkbox" aria-checked="false">
                <label>
                    <input type="checkbox" name="update_product_vtex" id="update_product_vtex" class="flat-red" <?=  @$integration['update_product_vtex'] == 1 ? 'checked' : '' ?>>&nbsp; <?=$this->lang->line('application_migration_new_29');?> <i class="fa fa-info-circle" data-toggle="tooltip" title="Atualizará dados do produto na vtex se marcado."></i>
                </label>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="checkbox" aria-checked="false">
                <label>
                    <input type="checkbox" name="update_sku_vtex" id="update_sku_vtex" class="flat-red" <?=  @$integration['update_sku_vtex'] == 1 ? 'checked' : '' ?>>&nbsp; <?=$this->lang->line('application_migration_new_28');?> <i class="fa fa-info-circle" data-toggle="tooltip" title="Atualizará dados do sku na vtex se marcado."></i>
                </label>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="checkbox" aria-checked="false">
                <label>
                    <input type="checkbox" name="update_images_specifications" id="update_images_specifications" class="flat-red" <?=  @$integration['update_images_specifications'] == 1 ? 'checked' : '' ?>>&nbsp; <?=$this->lang->line('application_migration_new_35');?> <i class="fa fa-info-circle" data-toggle="tooltip" title="Atualizará imagens do sku na vtex se marcado."></i>
                </label>
            </div>
        </div>

        <input type="hidden" name="mkt_type" value="vtex">
    </div>
</div>