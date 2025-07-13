<div class="row">
    <div class="col-sm-12">
        <div class="callout callout-info">
            <h4><?=$this->lang->line('application_migration_new_07');?></h4>
            <p><?=$this->lang->line('application_migration_new_08');?></p>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="box-body">
            <blockquote>
                <span><b style="font-weight: 400;font-size: 15px;">A url é formada por 3 fraguimentos: o AccountName, enviroment e sufix. <br/> Ex: conectala.myvtex.com</b></span><br>
            </blockquote>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group" id="div_accountName">
            <label for="exampleInputEmail1">
                <?=$this->lang->line('application_migration_new_09');?>
<!--                <i class="fa fa-info-circle" data-toggle="tooltip" title="Nome do marketplace"></i>-->
            </label>
            <input type="text" name="accountName" class="form-control" id="accountName" placeholder="" @keydown="mountUrl" v-model.trim="accountName" >
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group" id="div_environment">
            <label for="exampleInputEmail1">
                <?=$this->lang->line('application_migration_new_10');?>
<!--                <i class="fa fa-info-circle" data-toggle="tooltip" title="Nome do marketplace"></i>-->
            </label>
<!--            <input type="text" name="environment" class="form-control" id="environment" placeholder="">-->
            <select class="form-control" name="environment" id="environment" data-placeholder="" style="width: 100%;" required @change="mountUrl" v-model.trim="environment" >
                <option value="myvtex">myvtex</option>
                <option value="vtexcommercestable">vtexcommercestable</option>
            </select>
        </div>
    </div>
    <div class="col-sm-3" aria-checked="false" aria-disabled="false">
        <div class="form-group" id="div_suffixdns">
            <label>
                <?=$this->lang->line('application_migration_new_11');?>
<!--                <i class="fa fa-info-circle" data-toggle="tooltip" title="Nome do marketplace"></i>-->
            </label>
            <select class="form-control" name="suffixdns" id="suffixdns" data-placeholder="" style="width: 100%;" required @change="mountUrl" v-model.trim="suffixdns" >
                <option value=".com"><?=$this->lang->line('application_migration_new_11_v1');?></option>
                <option value=".com.br"><?=$this->lang->line('application_migration_new_11_v2');?></option>
            </select>
        </div>
    </div>
</div>

<div class="row" v-show="showUrl">
    <div class="col-sm-12">
        <blockquote>
            <strong>
                <span id="complete-url" style="font-size:15px;"> </span>
            </strong>
        </blockquote>
    </div>
</div>
<div class="row">
    <div class="col-sm-5">
        <div class="form-group" id="div_api_key">
            <label for="exampleInputEmail1">
                <?=$this->lang->line('application_migration_new_12');?>
<!--                <i class="fa fa-info-circle" data-toggle="tooltip" title="Nome do marketplace"></i>-->
            </label>
            <input type="password" name="api_key" class="form-control" id="api_key" placeholder="" >
        </div>
    </div>
    <div class="col-sm-5">
        <div class="form-group" id="div_api_token">
            <label for="exampleInputEmail1">
                <?=$this->lang->line('application_migration_new_13');?>
<!--                <i class="fa fa-info-circle" data-toggle="tooltip" title="Nome do marketplace"></i>-->
            </label>
            <input type="password" name="api_token" class="form-control" id="api_token" >
        </div>
    </div>
    <div class="col-sm-2">
        <div class="form-group">
            <button :disabled="updating" class="btn btn-primary btn-alin" id="submit_form_vtex" ><?=$this->lang->line('application_migration_new_14');?></button>
        </div>
    </div>
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
            <div class="col-sm-12 m-top m-button">
                <div class="col-sm-12">
                    <span style="font-size:18px;"><b><?=$this->lang->line('application_migration_new_17');?></b></span><br>
                    <small style="margin-top:-2px;"><?=$this->lang->line('application_migration_new_18');?></small>
                </div>
            </div>
            <br/><br/><br/><br/>
            <div class="container-fluid">
                <div class="col-sm-2">
                    <div class="form-group" id="">
                        <label for="minimum_stock"><?=$this->lang->line('application_migration_new_19');?> <i class="fa fa-info-circle" data-toggle="tooltip" title="Quantidade mínima para um produto ser publicado. Quando o produto atingir o estoque mínimo o produto fica indisponível no front."></i></label>
                        <input type="number" name="minimum_stock" class="form-control" id="minimum_stock" >
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="form-group" id="">
                        <label for="ref_id"><?=$this->lang->line('application_migration_new_20');?></label>
                        <select class="form-control select2" name="ref_id" id="ref_id" style="width: 100%;" >
                            <option value="SKUMKT" selected>SKUMKT</option>
                            <option value="ONLYID">ONLYID</option>
                            <option value="FORCEREFID">FORCEREFID</option>
                        </select>
                    </div>
                </div>
<!--                <div class="col-sm-2">-->
<!--                    <div class="form-group" id="">-->
<!--                        <label for="reserve_stock">--><?php //=$this->lang->line('application_migration_new_22');?><!--</label>-->
<!--                        <input type="number" name="reserve_stock" class="form-control" id="reserve_stock">-->
<!--                    </div>-->
<!--                </div>-->
<!--                <div class="col-sm-2">-->
<!--                    <div class="form-group">-->
<!--                        <div class="form-check form-check-inline">-->
<!--                            <label for="hasAuction">--><?php //=$this->lang->line('application_migration_new_23');?><!--</label><br>-->
<!--                            <select class="form-control" name="hasAuction" id="hasAuction" style="width: 100%;">-->
<!--                                <option value="">--><?php //=$this->lang->line('application_migration_new_23_v1');?><!--</option>-->
<!--                                <option value="1">--><?php //=$this->lang->line('application_migration_new_23_v2');?><!--</option>-->
<!--                            </select>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->
                <div class="col-sm-2" aria-checked="false" aria-disabled="false">
                    <div class="form-group" id="div_tradesPolicies">
                        <label><?=$this->lang->line('application_migration_new_24');?></label>
                        <select class="form-control select2 tradePolicies" name="tradesPolicies[]" id="tradesPolicies" multiple="multiple" data-placeholder="" style="width: 100%;" required >
                        </select>
                    </div>
                </div>
            </div>
            <br>
            <div class="col-sm-4">
                <div class="checkbox" aria-checked="false">
                    <label data-placement="top" data-toggle="popover" title="" data-content="">
                        <input type="checkbox" name="auto_approve" id="auto_approve" class="flat-red" >&nbsp; <?=$this->lang->line('application_migration_new_25');?> <i class="fa fa-info-circle" data-toggle="tooltip" title="Quando marcado, ao realizar a criação de lojas, já define que o lojista utilizará curadoria no seller center. Não altera sellers antigos."></i>
                    </label>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="checkbox" aria-checked="false">
                    <label>
                        <input type="checkbox" name="update_product_specifications" id="update_product_specifications" class="flat-red" >&nbsp; <?=$this->lang->line('application_migration_new_26');?> <i class="fa fa-info-circle" data-toggle="tooltip" title="Atualizará os atributos do produto se marcado (atributos da categoria do produto)."></i>
                    </label>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="checkbox" aria-checked="false">
                    <label>
                        <input type="checkbox" name="update_sku_specifications" id="update_sku_specifications" class="flat-red" >&nbsp; <?=$this->lang->line('application_migration_new_27');?> <i class="fa fa-info-circle" data-toggle="tooltip" title="Atualizará os atributos do sku se marcado (atributos com carater de variação)."></i>
                    </label>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="checkbox" aria-checked="false">
                    <label>
                        <input type="checkbox" name="auto_approve_two" id="auto_approve_two" class="flat-red" >&nbsp; <?=$this->lang->line('application_migration_new_30');?> <i class="fa fa-info-circle" data-toggle="tooltip" title="Remove a necessidade que o produto seja aprovado manualmente na vtex."></i>
                    </label>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="checkbox" aria-checked="false">
                    <label>
                        <input type="checkbox" name="update_product_vtex" id="update_product_vtex" class="flat-red" >&nbsp; <?=$this->lang->line('application_migration_new_29');?> <i class="fa fa-info-circle" data-toggle="tooltip" title="Atualizará dados do produto na vtex se marcado."></i>
                    </label>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="checkbox" aria-checked="false">
                    <label>
                        <input type="checkbox" name="update_sku_vtex" id="update_sku_vtex" class="flat-red" >&nbsp; <?=$this->lang->line('application_migration_new_28');?> <i class="fa fa-info-circle" data-toggle="tooltip" title="Atualizará dados do sku na vtex se marcado."></i>
                    </label>
                </div>
                <div class="checkbox" aria-checked="false">
                    <label>
                        <input type="checkbox" name="update_images_specifications" id="update_images_specifications" class="flat-red" >&nbsp; <?=$this->lang->line('application_migration_new_35');?> <i class="fa fa-info-circle" data-toggle="tooltip" title="Atualizará imagens do sku na vtex se marcado."></i>
                    </label>
                </div>
            </div>
            <input type="hidden" name="mkt_type" value="vtex">
        </div>
        </div>
    </div>
    <br>
</div>