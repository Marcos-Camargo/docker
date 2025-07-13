<!--
SW Serviços de Informática 2019

Ver Profile

-->


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_myprofile";  $this->load->view('templates/content_header',$data); ?>
    <?php $conectala_api_callback = $this->data["conecta_la_api_url"];?>
    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <?php if($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('success'); ?>
                    </div>
                <?php elseif($this->session->flashdata('error')): ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>
                <?php if(!empty($credentials->revoke)): ?>
                <div class="alert alert-error alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <?=sprintf($this->lang->line('messages_access_integration_revoked'), $dataIntegration['description_integration'])?>
                </div>
                <?php endif; ?>
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title"><?=$this->lang->line('application_integration')?></h3>
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        <?php
                        if (validation_errors()) {
                            foreach (explode("</p>",validation_errors()) as $erro) {
                                $erro = trim($erro);
                                if ($erro!="") { ?>
                                    <div class="alert alert-error alert-dismissible" role="alert">
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        <?php echo $erro."</p>"; ?>
                                    </div>
                                <?php	}
                            }
                        } ?>
                        <div class="row mb-5">
                            <div class="form-group col-md-12 text-center">
                                <h3 class="text-uppercase"><?=$this->lang->line('application_integrations')?></h3>
                            </div>
                        </div>
                        <div class="row d-flex justify-content-center mb-5" style="display: <?=count($storesView) == 1 ? 'none' : 'flex'?>">
                            <div class="form-group col-md-6">
                                <label>Seleciona a loja que irá integrar</label>
                                <select class="form-control select2" name="storeFilter" id="storeFilter" required>
                                    <?php
                                    foreach ($storesView as $storeView)
                                        echo "<option value='{$storeView['id']}' " . set_select('store', $storeView['id'], $storeView['id'] == $storeId) . ">{$storeView['name']}</option>";
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="row d-flex justify-content-center flex-wrap group-integration">
                            <?php foreach ($integrationsErp as $integrationErp): ?>
                                <?php if($integrationErp->type == 1 && $integrationErp->name === 'via'): ?>
                                    <?php if (!in_array('b2b_integration_via', $user_permission)) { continue; } ?>
                                <?php endif ?>

                                <?php if ($integrationErp->type == 1): ?>
                                    <div class="col-md-4">
                                        <div class="box box-widget default-integration widget-user">
                                            <div class="widget-user-header text-center <?=!empty($credentials->revoke) && ($dataIntegration['integration'] ?? '') == $integrationErp->name ? 'bg-yellow' : ''?>">
                                                <?php if(!empty($credentials->revoke) && ($dataIntegration['integration'] ?? '') == $integrationErp->name): ?>
                                                    <i class="fa fa-warning warning-revoke-left"></i>
                                                    <i class="fa fa-warning warning-revoke-right"></i>
                                                <?php endif; ?>
                                                <h3 class="widget-user-username"></h3>
                                            </div>
                                            <div class="widget-user-image">
                                                <img class="" src="<?=base_url("assets/images/integration_erps/$integrationErp->image")?>" alt="<?=$integrationErp->description?>">
                                            </div>
                                            <div class="box-footer">
                                                <div class="row">
                                                    <div class="col-sm-12">
                                                        <div class="description-block">
                                                            <h5 class="description-header"><i class="fas fa-plug" aria-hidden="true"></i> <?=$this->lang->line('application_integrate') . ' ' . $integrationErp->description . ' ' . $this->lang->line('application_and')?> <?=$nameSellerCenter?> </h5>
                                                            <?php
                                                            
                                                            switch ($integrationErp->name) {
                                                                case 'bling':
                                                                    if(isset($credentials->apikey_bling)) {
                                                                       
                                                                        echo '<button class="btn btn-warning mt-4" data-toggle="modal" data-target="#integrationBling">' . $this->lang->line('application_see_integration') . '</button>';
                                                                        echo '<br/><button class="btn btn-link mt-4 text-red" data-toggle="modal" data-target="#removeIntegration"><strong>' . $this->lang->line('application_integration_cancel') . '</strong></button>';
                                                                    } else {
                                                                        echo '<button ' . ($erpDisabled['bling'] ?? ($integration === null ? "" : "disabled")) . ' class="btn btn-success mt-4" data-toggle="modal" data-target="#integrationBling">' . $this->lang->line('application_i_want_to_integrate') . '</button>';
                                                                    }
                                                                    break;
                                                                case 'bling_v3':
                                                                    if(isset($credentials->access_token)) {
                                                                        echo '<button class="btn btn-warning mt-4" data-toggle="modal" data-target="#integrationBlingV3">' . $this->lang->line('application_see_integration') . '</button>';
                                                                        echo '<br/><button class="btn btn-link mt-4 text-red" data-toggle="modal" data-target="#removeIntegration"><strong>' . $this->lang->line('application_integration_cancel') . '</strong></button>';
                                                                    } else {
                                                                        echo '<button ' . ($erpDisabled['bling_v3'] ?? ($integration === null ? "" : "disabled")) . ' class="btn btn-success mt-4" data-toggle="modal" data-target="#integrationBlingV3">' . $this->lang->line('application_i_want_to_integrate') . '</button>';
                                                                    }
                                                                    break;
                                                                case 'tiny':
                                                                    if(isset($credentials->token_tiny)) {
                                                                        echo '<button class="btn btn-warning mt-4" data-toggle="modal" data-target="#integrationTiny">' . $this->lang->line('application_see_integration') . '</button>';
                                                                        echo '<br/><button class="btn btn-link mt-4 text-red" data-toggle="modal" data-target="#removeIntegration"><strong>' . $this->lang->line('application_integration_cancel') . '</strong></button>';
                                                                    } else {
                                                                        echo '<button ' . ($erpDisabled['tiny'] ?? ($integration === null ? "" : "disabled")) . ' class="btn btn-success mt-4" data-toggle="modal" data-target="#integrationTiny">' . $this->lang->line('application_i_want_to_integrate') . '</button>';
                                                                    }
                                                                    break;
                                                                case 'vtex':
                                                                    if(isset($credentials->token_vtex) && !isset($credentials->base_url_external)) {
                                                                        echo '<button class="btn btn-warning mt-4" data-toggle="modal" data-target="#integrationVtex">' . $this->lang->line('application_see_integration') . '</button>';
                                                                        echo '<br/><button class="btn btn-link mt-4 text-red" data-toggle="modal" data-target="#removeIntegration"><strong>' . $this->lang->line('application_integration_cancel') . '</strong></button>';
                                                                    } else {
                                                                        echo '<button ' . ($erpDisabled['vtex'] ?? ($integration === null ? "" : "disabled")) . ' class="btn btn-success mt-4" data-toggle="modal" data-target="#integrationVtex">' . $this->lang->line('application_i_want_to_integrate') . '</button>';
                                                                    }
                                                                    break;
                                                                case 'mevo':
                                                                    if(isset($credentials->base_url_external)) {
                                                                        echo '<button class="btn btn-warning mt-4" data-toggle="modal" data-target="#integrationVtexExternal">' . $this->lang->line('application_see_integration') . '</button>';
                                                                        echo '<br/><button class="btn btn-link mt-4 text-red" data-toggle="modal" data-target="#removeIntegration"><strong>' . $this->lang->line('application_integration_cancel') . '</strong></button>';
                                                                    } else {
                                                                        echo '<button ' . ($erpDisabled['mevo'] ?? ($integration === null ? "" : "disabled")) . ' class="btn btn-success mt-4" data-toggle="modal" data-target="#integrationVtexExternal">' . $this->lang->line('application_i_want_to_integrate') . '</button>';
                                                                    }
                                                                    break;
                                                                case 'eccosys':
                                                                    if(isset($credentials->token_eccosys)) {
                                                                        echo '<button class="btn btn-warning mt-4" data-toggle="modal" data-target="#integrationEccosys">' . $this->lang->line('application_see_integration') . '</button>';
                                                                        echo '<br/><button class="btn btn-link mt-4 text-red" data-toggle="modal" data-target="#removeIntegration"><strong>' . $this->lang->line('application_integration_cancel') . '</strong></button>';
                                                                    } else {
                                                                        echo '<button ' . ($erpDisabled['eccosys'] ?? ($integration === null ? "" : "disabled")) . ' class="btn btn-success mt-4" data-toggle="modal" data-target="#integrationEccosys">' . $this->lang->line('application_i_want_to_integrate') . '</button>';
                                                                    }
                                                                    break;
                                                                case 'jn2':
                                                                    if(isset($credentials->token_jn2)) {
                                                                        echo '<button class="btn btn-warning mt-4" data-toggle="modal" data-target="#integrationJN2">' . $this->lang->line('application_see_integration') . '</button>';
                                                                        echo '<br/><button class="btn btn-link mt-4 text-red" data-toggle="modal" data-target="#removeIntegration"><strong>' . $this->lang->line('application_integration_cancel') . '</strong></button>';
                                                                    } else {
                                                                        echo '<button ' . ($erpDisabled['jn2'] ?? ($integration === null ? "" : "disabled")) . ' class="btn btn-success mt-4" data-toggle="modal" data-target="#integrationJN2">' . $this->lang->line('application_i_want_to_integrate') . '</button>';
                                                                    }
                                                                    break;
                                                                case 'pluggto':
                                                                    if(isset($credentials->user_id)) {
                                                                        echo '<button class="btn btn-warning mt-4" data-toggle="modal" data-target="#integrationPluggto">' . $this->lang->line('application_see_integration') . '</button>';
                                                                        echo '<br/><button class="btn btn-link mt-4 text-red" data-toggle="modal" data-target="#removeIntegration"><strong>' . $this->lang->line('application_integration_cancel') . '</strong></button>';
                                                                    } else {
                                                                        echo '<button ' . ($erpDisabled['pluggto'] ?? ($integration === null ? "" : "disabled")) . ' class="btn btn-success mt-4" data-toggle="modal" data-target="#integrationPluggto">' . $this->lang->line('application_i_want_to_integrate') . '</button>';
                                                                    }
                                                                    break;
                                                                case 'bseller':
                                                                    if(isset($credentials->token_bseller)) {
                                                                        echo '<button class="btn btn-warning mt-4" data-toggle="modal" data-target="#integrationBSeller">' . $this->lang->line('application_see_integration') . '</button>';
                                                                        echo '<br/><button class="btn btn-link mt-4 text-red" data-toggle="modal" data-target="#removeIntegration"><strong>' . $this->lang->line('application_integration_cancel') . '</strong></button>';
                                                                    } else {
                                                                        echo '<button ' . ($erpDisabled['bseller'] ?? ($integration === null ? "" : "disabled")) . ' class="btn btn-success mt-4" data-toggle="modal" data-target="#integrationBSeller">' . $this->lang->line('application_i_want_to_integrate') . '</button>';
                                                                    }
                                                                    break;
                                                                case 'anymarket':
                                                                    if(isset($credentials->token2)) {
                                                                        echo '<button class="btn btn-warning mt-4" data-toggle="modal" data-target="#integrationAnyMarket">' . $this->lang->line('application_see_integration') . '</button>';
                                                                        echo '<br/><button class="btn btn-link mt-4 text-red" data-toggle="modal" data-target="#removeIntegration"><strong>' . $this->lang->line('application_integration_cancel') . '</strong></button>';
                                                                    } else {
                                                                        echo '<button ' . ($erpDisabled['anymarket'] ?? ($integration === null ? "" : "disabled")) . ' class="btn btn-success mt-4" data-toggle="modal" data-target="#integrationAnyMarket">' . $this->lang->line('application_i_want_to_integrate') . '</button>';
                                                                    }
                                                                    break;
                                                                case 'lojaintegrada':
                                                                    if(isset($credentials->chave_api)) {
                                                                        echo '<button class="btn btn-warning mt-4" data-toggle="modal" data-target="#integrationLojaIntegrada">' . $this->lang->line('application_see_integration') . '</button>';
                                                                        echo '<br/><button class="btn btn-link mt-4 text-red" data-toggle="modal" data-target="#removeIntegration"><strong>' . $this->lang->line('application_integration_cancel') . '</strong></button>';
                                                                    } else {
                                                                        echo '<button ' . ($erpDisabled['lojaintegrada'] ?? ($integration === null ? "" : "disabled")) . ' class="btn btn-success mt-4" data-toggle="modal" data-target="#integrationLojaIntegrada">' . $this->lang->line('application_i_want_to_integrate') . '</button>';
                                                                    }
                                                                    break;
                                                                case 'via':
                                                                    if(in_array($integration, array('viavarejo_b2b_pontofrio', 'viavarejo_b2b_extra', 'viavarejo_b2b_casasbahia'))) {
                                                                        echo '<button class="btn btn-warning mt-4" data-toggle="modal" data-target="#integration_viavarejo_b2b">' . $this->lang->line('application_see_integration') . '</button>';
                                                                        echo '<br/><button class="btn btn-link mt-4 text-red" data-toggle="modal" data-target="#removeIntegration"><strong>' . $this->lang->line('application_integration_cancel') . '</strong></button>';
                                                                    } else {
                                                                        echo '<button '.($erpDisabled['viavarejo_b2b_pontofrio'] ?? $erpDisabled['viavarejo_b2b_extra'] ?? $erpDisabled['viavarejo_b2b_casasbahia'] ?? ($integration === null ? "" : "disabled")).' class="btn btn-success mt-4" data-toggle="modal" data-target="#integration_viavarejo_b2b">' . $this->lang->line('application_i_want_to_integrate') . '</button>';
                                                                    }
                                                                    break;
                                                                case 'tray':
                                                                    if($integration === 'tray') {
                                                                        echo '<button class="btn btn-warning mt-4" data-toggle="modal" data-target="#integration_tray">' . $this->lang->line('application_see_integration') . '</button>';
                                                                        echo '<br/><button class="btn btn-link mt-4 text-red" data-toggle="modal" data-target="#removeIntegration"><strong>' . $this->lang->line('application_integration_cancel') . '</strong></button>';
                                                                    }
                                                                    else {
                                                                        echo '<button ' . ($erpDisabled['tray'] ?? ($integration === null ? "" : "disabled")) . ' class="btn btn-success mt-4" data-toggle="modal" data-target="#integration_tray">' . $this->lang->line('application_i_want_to_integrate') . '</button>';
                                                                    }
                                                                    break;
                                                                case 'hub2b':
                                                                    if ($integration === 'hub2b') {
                                                                        echo '<button class="btn btn-warning mt-4" data-toggle="modal" data-target="#integration_hub2b">' . $this->lang->line('application_see_integration') . '</button>';
                                                                        echo '<br/><button class="btn btn-link mt-4 text-red" data-toggle="modal" data-target="#removeIntegration"><strong>' . $this->lang->line('application_integration_cancel') . '</strong></button>';
                                                                    } else {
                                                                        echo '<button ' . ($erpDisabled['hub2b'] ?? ($integration === null ? "" : "disabled")) . ' class="btn btn-success mt-4" data-toggle="modal" data-target="#integration_hub2b">' . $this->lang->line('application_i_want_to_integrate') . '</button>';
                                                                    }
                                                                    break;
                                                                case 'ideris':
                                                                    if($integration === 'ideris') {
                                                                        echo '<button class="btn btn-warning mt-4" data-toggle="modal" data-target="#integration_ideris">' . $this->lang->line('application_see_integration') . '</button>';
                                                                        echo '<br/><button class="btn btn-link mt-4 text-red" data-toggle="modal" data-target="#removeIntegration"><strong>' . $this->lang->line('application_integration_cancel') . '</strong></button>';
                                                                    }
                                                                    else {
                                                                        echo '<button ' . ($erpDisabled['ideris'] ?? ($integration === null ? "" : "disabled")) . ' class="btn btn-success mt-4" data-toggle="modal" data-target="#integration_ideris">' . $this->lang->line('application_i_want_to_integrate') . '</button>';
                                                                    }
                                                                    break;
                                                                case 'magalu':
                                                                    if(isset($credentials->magalu_username)) {
                                                                        echo '<button class="btn btn-warning mt-4" data-toggle="modal" data-target="#integrationMagalu">' . $this->lang->line('application_see_integration') . '</button>';
                                                                        echo '<br/><button class="btn btn-link mt-4 text-red" data-toggle="modal" data-target="#removeIntegration"><strong>' . $this->lang->line('application_integration_cancel') . '</strong></button>';
                                                                    } else {
                                                                        echo '<button ' . ($erpDisabled['magalu'] ?? ($integration === null ? "" : "disabled")) . ' class="btn btn-success mt-4" data-toggle="modal" data-target="#integrationMagalu">' . $this->lang->line('application_i_want_to_integrate') . '</button>';
                                                                    }
                                                                    break;
                                                                case 'microvix':
                                                                    if($integration === 'microvix') {
                                                                        echo '<button class="btn btn-warning mt-4" data-toggle="modal" data-target="#integration_microvix">' . $this->lang->line('application_see_integration') . '</button>';
                                                                        echo '<br/><button class="btn btn-link mt-4 text-red" data-toggle="modal" data-target="#removeIntegration"><strong>' . $this->lang->line('application_integration_cancel') . '</strong></button>';
                                                                    }
                                                                    else {
                                                                        echo '<button ' . ($erpDisabled['microvix'] ?? ($integration === null ? "" : "disabled")) . ' class="btn btn-success mt-4" data-toggle="modal" data-target="#integration_microvix">' . $this->lang->line('application_i_want_to_integrate') . '</button>';
                                                                    }
                                                                    break;
                                                            }
                                                            ?>
                                                        </div>
                                                        <div class="description-block">
                                                            <?php if ($integrationErp->support_link): ?>
                                                                <?php $linksSupport = json_decode($integrationErp->support_link); ?>
                                                                <?php foreach ($linksSupport as $linkSupport): ?>
                                                                    <a href="<?=$linkSupport->link?>" target="_blank" class="pt-3"><i class="fa fa-info-circle"></i> <?=$linkSupport->title?></a><br/>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                <?php endif; ?>
                                <?php if ($integrationErp->type == 2): ?>
                                    <div class="col-md-4">
                                        <div class="box box-widget default-integration widget-user">
                                            <div class="widget-user-header text-center"></div>
                                            <div class="widget-user-image">
                                                <img class="" src="<?=base_url("assets/images/integration_erps/$integrationErp->image")?>" alt="<?=$integrationErp->description?>">
                                            </div>
                                            <div class="box-footer">
                                                <div class="row">
                                                    <div class="col-sm-12">
                                                        <div class="description-block">
                                                            <h5 class="description-header"><i class="fas fa-plug" aria-hidden="true"></i> <?=$this->lang->line('application_integrate')?> <?=$integrationErp->description?> <?=$this->lang->line('application_and')?> <?=$nameSellerCenter?> </h5>
                                                            <?php
                                                            if (($dataIntegration['integration_erp_id'] ?? 0) === ($integrationErp->id ?? null)) {
                                                                echo '<button class="btn btn-warning mt-4" data-toggle="modal" data-target="#integrationExterno" data-integration="'.$integrationErp->name.'">' . $this->lang->line('application_see_integration') . '</button>';
                                                                echo '<br/><button class="btn btn-link mt-4 text-red" data-toggle="modal" data-target="#removeIntegration"><strong>' . $this->lang->line('application_integration_cancel') . '</strong></button>';
                                                            } else {
                                                                $disabledIntegration = $disabledIntegration ? 'disabled' : '';
                                                                echo '<button ' . $disabledIntegration . ' class="btn btn-success mt-4" data-toggle="modal" data-target="#integrationExterno" data-integration="'.$integrationErp->name.'">' . $this->lang->line('application_i_want_to_integrate') . '</button>';
                                                            }
                                                            ?>
                                                        </div>
                                                        <div class="description-block">
                                                            <?php if ($integrationErp->support_link): ?>
                                                                <?php $linksSupport = json_decode($integrationErp->support_link); ?>
                                                                <?php foreach ($linksSupport as $linkSupport): ?>
                                                                    <a href="<?=$linkSupport->link?>" target="_blank" class="pt-3"><i class="fa fa-info-circle"></i> <?=$linkSupport->title?></a><br/>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="removeIntegration">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Cancelar Integração</span></h4>
                </div>
                <div class="modal-body text-center">
                    <div class="row">
                        <div class="col-md-12">
                            <h4>Solicitação de cancelamento de integração.</h4>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">                            
                            <h5>Entre em contato com o <strong>administrador do painel</strong> para que possa ser feita a remoção da integração informando sua loja e o motivo do cancelamento.</h5>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-end">
                    <button type="button" class="btn btn-primary col-md-4" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="integrationBling">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_integration') . ' Bling'?></span></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Api Key</label>
                                <input type="text" class="form-control" name="token" value="<?=$credentials->apikey_bling ?? "";?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?=$this->lang->line('application_store_code') . ' (<i>' . $this->lang->line('application_optional') . '</i>)'?></label>
                                <input type="text" class="form-control" name="store_code" value="<?=$credentials->loja_bling ?? "";?>">
                                <small>O código Multiloja é utilizado para consultar preços promocionais.</small>
                                <small>Caso não seja configurado, o preço considerado será do produto.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?=$this->lang->line('application_stock') . ' (<i>' . $this->lang->line('application_optional') . '</i>)'?></label>
                                <input type="text" class="form-control" name="stock_bling" value="<?=$credentials->stock_bling ?? "";?>">
                                <small>Iremos buscar apenas esse estoque nos produtos.</small>
                                <small>Deixe em branco para buscarmos todos os estoques.</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>URL Callback Estoque</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?=base_url("Api/Integration_v2/bling/UpdateStock?apiKey={$dataIntegration['token_callback']}")?>" readonly>
                                    <span class="input-group-btn">
                                        <button type="button" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>URL Callback NF-e</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?=base_url("Api/Integration_v2/bling/UpdateNFe?apiKey={$dataIntegration['token_callback']}")?>" readonly>
                                    <span class="input-group-btn">
                                        <button type="button" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger col-md-4" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-success col-md-4"><?=$this->lang->line('application_send');?></button>
                </div>
                <input type="hidden" name="integration" value="bling">
                <input type="hidden" name="store" value="<?=$storeId?>">
            </div>
        </form>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="integrationBlingV3">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_integration') . ' Bling'?></span></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?=$this->lang->line('application_store_code') . ' (<i>' . $this->lang->line('application_optional') . '</i>)'?></label>
                                <input type="text" class="form-control" name="store_code" value="<?=$credentials->loja_bling ?? "";?>">
                                <small>O código Multiloja é utilizado para consultar preços promocionais.</small>
                                <small>Caso não seja configurado, o preço considerado será do produto.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?=$this->lang->line('application_stock') . ' (<i>' . $this->lang->line('application_optional') . '</i>)'?></label>
                                <input type="text" class="form-control" name="stock_bling" value="<?=$credentials->stock_bling ?? "";?>">
                                <small>Iremos buscar apenas esse estoque nos produtos.</small>
                                <small>Deixe em branco para buscarmos todos os estoques.</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>URL de Callback de Estoque</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?=$conectala_api_callback."Api/Integration_v2/bling_v3/UpdateStock?apiKey={$dataIntegration['token_callback']}"?>" readonly>
                                    <span class="input-group-btn">
                                        <button type="button" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>URL de Callback de NF-e</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?=$conectala_api_callback."Api/Integration_v2/bling_v3/UpdateNFe?apiKey={$dataIntegration['token_callback']}"?>" readonly>
                                    <span class="input-group-btn">
                                        <button type="button" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger col-md-4" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-success col-md-4"><?=isset($credentials->access_token) && empty($credentials->revoke) ? $this->lang->line('application_send') : $this->lang->line('application_request_authorization');?></button>
                </div>
                <input type="hidden" name="integration" value="bling_v3">
                <input type="hidden" name="store" value="<?=$storeId?>">
            </div>
        </form>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="integrationTiny">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_integration') . ' Tiny'?></span></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Token</label>
                                <input type="text" class="form-control" name="token" value="<?=$credentials->token_tiny ?? "";?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?=$this->lang->line('application_price_list') . ' (<i>' . $this->lang->line('application_optional') . '</i>)'?></label>
                                <input type="text" class="form-control" name="price_list" value="<?=$credentials->lista_tiny ?? "";?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?=$this->lang->line('application_deposit') . ' (<i>' . $this->lang->line('application_optional') . '</i>)'?></label>
                                <input type="text" class="form-control" name="stock_tiny" value="<?=$credentials->stock_bling ?? "";?>">
                                <small>Iremos buscar apenas esse depósito nos produtos.</small>
                                <small>Deixe em branco para buscarmos todos os depósitos.</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Endpoint para cotação de fretes</label>
                                <input type="text" class="form-control" name="endpoint_quote" value="<?=$credentials->endpoint_quote ?? "";?>">
                                <small class="font-weight-bold">Disponível apenas para logística própria.</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Notificação de Estoque</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?=$conectala_api_callback."Api/Integration_v2/tiny/UpdatePriceStock?apiKey={$dataIntegration['token_callback']}"?>" readonly>
                                    <span class="input-group-btn">
                                        <button type="button" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Notificação de Produtos</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?=$conectala_api_callback."Api/Integration_v2/tiny/CreateProduct?apiKey={$dataIntegration['token_callback']}"?>" readonly>
                                    <span class="input-group-btn">
                                        <button type="button" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Notificação de Nota Fiscal</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?=$conectala_api_callback."Api/Integration_v2/tiny/UpdateNFe?apiKey={$dataIntegration['token_callback']}"?>" readonly>
                                    <span class="input-group-btn">
                                        <button type="button" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Notificação de Etiqueta</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?=$conectala_api_callback."Api/Integration/Tiny/Label?apiKey={$dataIntegration['token_callback']}"?>" readonly>
                                    <span class="input-group-btn">
                                        <button type="button" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Notificação de Preço</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?=$conectala_api_callback."Api/Integration_v2/tiny/UpdatePriceStock?apiKey={$dataIntegration['token_callback']}"?>" readonly>
                                    <span class="input-group-btn">
                                    <button type="button" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                                </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Notificação Para Envio do Rastreio</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?=$conectala_api_callback."Api/Integration_v2/tiny/UpdateTracking?apiKey={$dataIntegration['token_callback']}"?>" readonly>
                                    <span class="input-group-btn">
                                    <button type="button" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                                </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Notificação Para Envio de Alteração na Situação de Pedidos</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?=$conectala_api_callback."Api/Integration_v2/tiny/UpdateStatus?apiKey={$dataIntegration['token_callback']}"?>" readonly>
                                    <span class="input-group-btn">
                                <button type="button" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                            </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger col-md-4" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-success col-md-4"><?=$this->lang->line('application_send');?></button>
                </div>
                <input type="hidden" name="integration" value="tiny">
                <input type="hidden" name="store" value="<?=$storeId?>">
            </div>
        </form>
    </div>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="integrationEccosys">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_integration') . ' Eccosys'?></span></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Token</label>
                                <input type="text" class="form-control" name="token" value="<?=$credentials->token_eccosys ?? "";?>" required>
                            </div>
                            <div class="form-group">
                                <label>URL loja</label>
                                <input type="text" class="form-control" name="url_eccosys" value="<?=$credentials->url_eccosys ?? "";?>" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger col-md-4" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-success col-md-4"><?=$this->lang->line('application_send');?></button>
                </div>
                <input type="hidden" name="integration" value="eccosys">
                <input type="hidden" name="store" value="<?=$storeId?>">
            </div>
        </form>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="integrationJN2">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_integration') . ' JN2'?></span></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Token</label>
                                <input type="text" class="form-control" name="token" value="<?=$credentials->token_jn2 ?? "";?>" required>
                            </div>
                            <div class="form-group">
                                <label>URL loja</label>
                                <input type="text" class="form-control" name="url_jn2" value="<?=$credentials->url_jn2 ?? "";?>" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger col-md-4" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-success col-md-4"><?=$this->lang->line('application_send');?></button>
                </div>
                <input type="hidden" name="integration" value="jn2">
                <input type="hidden" name="store" value="<?=$storeId?>">
            </div>
        </form>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="integrationPluggto">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_integration') . ' Plugg to'?></span></h4>
                </div>
                <div class="modal-body">
                    <div class="row">                    
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>ID da conta - User ID</label>
                                <input type="text" class="form-control" name="user_id" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');" value="<?=$credentials->user_id ?? "";?>" required>
                            </div>
                        </div>
                    </div>                    
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger col-md-4" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-success col-md-4"><?=$this->lang->line('application_send');?></button>
                </div>
                <input type="hidden" name="integration" value="pluggto">
                <input type="hidden" name="store" value="<?=$storeId?>">
            </div>
        </form>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="integrationBSeller">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_integration') . ' BSeller'?></span></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Token</label>
                                <input type="text" class="form-control" name="token" value="<?=$credentials->token_bseller ?? "";?>" required>
                            </div>
                            <div class="form-group">
                                <label>URL loja</label>
                                <input type="text" class="form-control" name="url_bseller" value="<?=$credentials->url_bseller ?? "";?>" required>
                            </div>
                            <div class="form-group">
                                <label>Interface</label>
                                <input type="text" class="form-control" name="interface" value="<?=$credentials->interface ?? "";?>" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger col-md-4" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-success col-md-4"><?=$this->lang->line('application_send');?></button>
                </div>
                <input type="hidden" name="integration" value="bseller">
                <input type="hidden" name="store" value="<?=$storeId?>">
            </div>
        </form>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="integrationAnyMarket">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_integration') . ' Anymarket'?></span></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info" role="alert">
                            <span style="text-align:center;width: 100%;display: block;font-size: 15px;">
                                A configuração desta integração é realizada dentro do painel do parceiro.
                            </span>
                            </div>
                        </div>
                    </div>
                    <div class="body-form-integration"></div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger col-md-4" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                </div>
                <input type="hidden" name="integration" value="anymarket">
                <input type="hidden" name="store" value="<?=$storeId?>">
            </div>
        </form>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="integrationPrecode">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_integration') . ' Precode'?></span></h4>
                    <p>Dados para integração Precode, informe estes dados na tela do modulo de configuração.</p>
                </div>
                <div class="modal-body">
                    <div class="body-form-integration"></div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger col-md-4" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                </div>
                <input type="hidden" name="integration" value="precode">
                <input type="hidden" name="store" value="<?=$storeId?>">
            </div> 
        </form>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="integrationAton">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_integration') . ' Aton'?></span></h4>
                    <p>Dados para integração Aton, informe estes dados na tela do modulo de configuração.</p>
                </div>
                <div class="modal-body">
                    <div class="body-form-integration"></div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger col-md-4" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                </div>
                <input type="hidden" name="integration" value="aton">
                <input type="hidden" name="store" value="<?=$storeId?>">
            </div>
        </form>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="integrationHubsell">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_integration') . ' Hubsell'?></span></h4>
                    <p>Dados para integração Hubsell, informe estes dados na tela do modulo de configuração.</p>
                </div>
                <div class="modal-body">
                    <div class="body-form-integration"></div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger col-md-4" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                </div>
                <input type="hidden" name="integration" value="hubsell">
                <input type="hidden" name="store" value="<?=$storeId?>">
            </div>
        </form>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="integrationLojaIntegrada">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_integration') . ' LojaIntegrada'?></span></h4>
                    <?php if(!$chave_application_setted):?>
                        <div class="alert alert-warning alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            Chave de aplicação(<i>chave_aplicacao_loja_integrada</i>) não disponivel nas configurações. Solicite a configuração da chave de aplicação.
                        </div>
                    <?php endif?>
                    
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Chave de integração informada pela LojaIntegrada(chave_api)</label>
                                <input type="text" class="form-control" name="chave_api" value=" <?=$credentials->chave_api??''?>" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger col-md-4" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-success col-md-4"><?=$this->lang->line('application_send');?></button>
                </div>
                <input type="hidden" name="integration" value="lojaintegrada">
                <input type="hidden" name="store" value="<?=$storeId?>">
            </div>
        </form>
    </div>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="integrationMagalu">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_integration') . ' Magalu'?></span></h4>
                    <?php if(!$chave_application_setted):?>
                        <div class="alert alert-warning alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                    <?php endif?>

                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Usuário</label>
                                <input type="text" class="form-control" name="magalu_username" value=" <?=$credentials->magalu_username??''?>" required>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Senha</label>
                                <input type="text" class="form-control" name="magalu_password" value=" <?=$credentials->magalu_password??''?>" required>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Quantidade Em Estoque Para Produtos</label>
                                <input type="text" class="form-control" name="magalu_default_stock" value=" <?=$credentials->magalu_default_stock??'1'?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Notificação de pedidos</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?=$conectala_api_callback."Api/Integration_v2/magalu/Order"?>" readonly>
                                    <span class="input-group-btn">
                                        <button type="button" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Notificação de produtos</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?=$conectala_api_callback."Api/Integration_v2/magalu/Product"?>" readonly>
                                    <span class="input-group-btn">
                                        <button type="button" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Cabeçalho de autenticação</label>
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        token
                                    </div>
                                    <div>
                                        <div class="input-group">
                                            <input type="text" class="form-control" value="<?=$dataIntegration['token_callback']?>" readonly>
                                            <span class="input-group-btn">
                                        <button type="button" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                                    </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger col-md-4" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-success col-md-4"><?=$this->lang->line('application_send');?></button>
                </div>
                <input type="hidden" name="integration" value="magalu">
                <input type="hidden" name="store" value="<?=$storeId?>">
                <input type="hidden" name="old_value_save_images_in_father_product" value="<?=($credentials->save_images_in_father_product ?? 0) ? 1 : 0?>">
            </div>
        </form>
    </div>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="integrationVtex">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_integration') . ' VTEX'?></span></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Secret key</label>
                                <input type="text" class="form-control" name="token_vtex" value="<?=$credentials->token_vtex ?? "";?>" required>
                                <small>Configurações > Gerenciamento da conta > Conta</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Access key</label>
                                <input type="text" class="form-control" name="appkey_vtex" value="<?=$credentials->appkey_vtex ?? "";?>" required>
                                <small>Configurações > Gerenciamento da conta > Conta</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nome da conta</label>
                                <input type="text" class="form-control" name="account_name_vtex" value="<?=$credentials->account_name_vtex ?? "";?>" required>
                                <small>Configurações > Gerenciamento da conta > Conta</small>
                            </div>
                        </div>
                    </div>
                    <!--
                    <div class="row"> 
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Ambiente</label>
                                <input type="text" class="form-control" name="environment_vtex" value="<?=$credentials->environment_vtex ?? "";?>" required>
                                <small>vtexcommercestable ou vtexcommmercebeta</small>
                            </div>
                        </div>
                    </div>-->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Política Comercial</label>
                                <input type="number" class="form-control" name="sales_channel_vtex" value="<?=$credentials->sales_channel_vtex ?? "";?>" required>
                                <small>Configurações da loja > Políticas comerciais</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>ID Afiliado</label>
                                <input type="text" class="form-control" name="affiliate_id_vtex" value="<?=$credentials->affiliate_id_vtex ?? "";?>" required>
                                <small>Pedidos > Gerenciamento de pedidos > Configurações</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Endpoint de Search (<i>Cadastro Afiliado</i>):</label>

                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?=$conectala_api_callback."Api/Integration_v2/vtex/ControlProduct?apiKey={$dataIntegration['token_callback']}"?>" readonly>
                                    <span class="input-group-btn">
                                            <button type="button" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                                        </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Método de Envio</label>
                                <input type="text" class="form-control" name="shipping_method_vtex" value="<?=$credentials->shipping_method_vtex ?? "";?>" required>
                                <small>Pedidos > Estoque & entrega > Estratégia de envio</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>ID Pagamento</label>
                                <input type="number" class="form-control" name="payment_id_vtex" value="<?=$credentials->payment_id_vtex ?? "";?>" required>
                                <small>Transações > Pagamentos > Configurações</small>
                            </div>
                        </div>
                    </div>-->
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger col-md-4" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-success col-md-4"><?=$this->lang->line('application_send');?></button>
                </div>
                <input type="hidden" name="integration" value="vtex">
                <input type="hidden" name="store" value="<?=$storeId?>">
            </div>
        </form>
    </div>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="integrationVtexExternal">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_integration') . ' VTEX'?></span></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Url base</label>
                                <input type="text" class="form-control" name="base_url_external" value="<?=$credentials->base_url_external ?? "";?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Secret key</label>
                                <input type="text" class="form-control" name="token_vtex" value="<?=$credentials->token_vtex ?? "";?>" required>
                                <small>Configurações > Gerenciamento da conta > Conta</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Access key</label>
                                <input type="text" class="form-control" name="appkey_vtex" value="<?=$credentials->appkey_vtex ?? "";?>" required>
                                <small>Configurações > Gerenciamento da conta > Conta</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nome da conta</label>
                                <input type="text" class="form-control" name="account_name_vtex" value="<?=$credentials->account_name_vtex ?? "";?>" required>
                                <small>Configurações > Gerenciamento da conta > Conta</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Política Comercial</label>
                                <input type="number" class="form-control" name="sales_channel_vtex" value="<?=$credentials->sales_channel_vtex ?? "";?>" required>
                                <small>Configurações da loja > Políticas comerciais</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>ID Afiliado</label>
                                <input type="text" class="form-control" name="affiliate_id_vtex" value="<?=$credentials->affiliate_id_vtex ?? "";?>" required>
                                <small>Pedidos > Gerenciamento de pedidos > Configurações</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Endpoint de Search (<i>Cadastro Afiliado</i>):</label>

                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?=$conectala_api_callback."Api/Integration_v2/vtex/ControlProduct?apiKey={$dataIntegration['token_callback']}"?>" readonly>
                                    <span class="input-group-btn">
                                            <button type="button" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                                        </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger col-md-4" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-success col-md-4"><?=$this->lang->line('application_send');?></button>
                </div>
                <input type="hidden" name="integration" value="mevo">
                <input type="hidden" name="store" value="<?=$storeId?>">
            </div>
        </form>
    </div>
</div>
<?php if(in_array('b2b_integration_via', $user_permission)): ?>
<div class="modal fade" tabindex="-1" role="dialog" id="integration_viavarejo_b2b">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_integration') . ' VIA'?></span></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>ID Parceiro (*)</label>
                                <input type="number" class="form-control" name="partnerId" value="<?=set_value('partnerId', $credentials->partnerId ?? "")?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Token (*)</label>
                                <input type="text" class="form-control" name="token" value="<?=set_value('token', $credentials->token_b2b_via ?? "")?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <h3>Campanhas</h3>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Ponto Frio (*)</label>
                                <input type="number" class="form-control" name="campaign_pontofrio" value="<?=set_value('campaign_pontofrio', $integration_b2b_viavarejo['viavarejo_b2b_pontofrio']['credentials']->campaign ?? '')?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Extra (*)</label>
                                <input type="number" class="form-control" name="campaign_extra" value="<?=set_value('campaign_extra', $integration_b2b_viavarejo['viavarejo_b2b_extra']['credentials']->campaign ?? '')?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Casas Bahia (*)</label>
                                <input type="number" class="form-control" name="campaign_casasbahia" value="<?=set_value('campaign_casasbahia', $integration_b2b_viavarejo['viavarejo_b2b_casasbahia']['credentials']->campaign ?? '')?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <h3>CNPJ</h3>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Ponto Frio (*)</label>
                                <input type="text" class="form-control" name="cnpj_pontofrio" value="<?=set_value('cnpj_pontofrio', $integration_b2b_viavarejo['viavarejo_b2b_pontofrio']['credentials']->cnpj ?? '')?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Extra (*)</label>
                                <input type="text" class="form-control" name="cnpj_extra" value="<?=set_value('cnpj_extra', $integration_b2b_viavarejo['viavarejo_b2b_extra']['credentials']->cnpj ?? '')?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Casas Bahia (*)</label>
                                <input type="text" class="form-control" name="cnpj_casasbahia" value="<?=set_value('cnpj_casasbahia', $integration_b2b_viavarejo['viavarejo_b2b_casasbahia']['credentials']->cnpj ?? '')?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <h3>Relacionar Lojas</h3>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Ponto Frio (*)</label>
                                <select class="form-control select2" name="related_store_pontofrio" required>
                                    <option value=""><?=$this->lang->line('application_select')?></option>
                                    <?php foreach ($stores_company as $store): ?>
                                        <option value="<?=$store['id']?>" <?=set_select('related_store_pontofrio', $store['id'], $store['id'] == (isset($integration_b2b_viavarejo['viavarejo_b2b_pontofrio']['store']) ? $integration_b2b_viavarejo['viavarejo_b2b_pontofrio']['store'] : ''))?>><?=$store['name']?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Extra (*)</label>
                                <select class="form-control select2" name="related_store_extra" required>
                                    <option value=""><?=$this->lang->line('application_select')?></option>
                                    <?php foreach ($stores_company as $store): ?>
                                        <option value="<?=$store['id']?>" <?=set_select('related_store_extra', $store['id'], $store['id'] == (isset($integration_b2b_viavarejo['viavarejo_b2b_extra']['store']) ? $integration_b2b_viavarejo['viavarejo_b2b_extra']['store'] : ''))?>><?=$store['name']?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Casas Bahia (*)</label>
                                <select class="form-control select2" name="related_store_casasbahia" required>
                                    <option value=""><?=$this->lang->line('application_select')?></option>
                                    <?php foreach ($stores_company as $store): ?>
                                        <option value="<?=$store['id']?>" <?=set_select('related_store_casasbahia', $store['id'], $store['id'] == (isset($integration_b2b_viavarejo['viavarejo_b2b_casasbahia']['store']) ? $integration_b2b_viavarejo['viavarejo_b2b_casasbahia']['store'] : ''))?>><?=$store['name']?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <h3>WebHooks</h3>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Auth Token</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="webhookAuthToken"
                                           value="<?= set_value('webhookAuthToken', $credentials->webhookAuthToken ?? "") ?>"
                                           readonly>
                                    <span class="input-group-btn">
                                        <button type="button" data-toggle="tooltip" title="Copiar"
                                                class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i>
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Endpoint Atualização de Rastreamento</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="trackingUrl"
                                           value="<?= set_value('trackingUrl', $credentials->trackingUrl ?? "") ?>"
                                           readonly>
                                    <span class="input-group-btn">
                                        <button type="button" data-toggle="tooltip" title="Copiar"
                                                class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i>
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Endpoint Atualização de Produto</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="partialProductUrl"
                                           value="<?= set_value('partialProductUrl', $credentials->partialProductUrl ?? "") ?>"
                                           readonly>
                                    <span class="input-group-btn">
                                        <button type="button" data-toggle="tooltip" title="Copiar"
                                                class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i>
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Endpoint Atualização de Estoque</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="stockProductUrl"
                                           value="<?= set_value('stockProductUrl', $credentials->stockProductUrl ?? "") ?>"
                                           readonly>
                                    <span class="input-group-btn">
                                        <button type="button" data-toggle="tooltip" title="Copiar"
                                                class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i>
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Endpoint Atualização de Preço/Disponibilidade</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="availabilityProductUrl"
                                           value="<?= set_value('availabilityProductUrl', $credentials->availabilityProductUrl ?? "") ?>"
                                           readonly>
                                    <span class="input-group-btn">
                                        <button type="button" data-toggle="tooltip" title="Copiar"
                                                class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i>
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Endpoint Atualização de Categorias</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="updateCategoryUrl"
                                           value="<?= set_value('updateCategoryUrl', $credentials->updateCategoryUrl ?? "") ?>"
                                           readonly>
                                    <span class="input-group-btn">
                                        <button type="button" data-toggle="tooltip" title="Copiar"
                                                class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i>
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger col-md-4" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-success col-md-4"><?=$this->lang->line('application_send');?></button>
                </div>
                <input type="hidden" name="integration" value="viavarejo_b2b">
                <input type="hidden" name="store" value="<?=$storeId?>">
            </div>
        </form>
    </div>
</div>
<?php endif ?>
<div class="modal fade" tabindex="-1" role="dialog" id="integrationExterno">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_integration')?></span></h4>
                    <p>Dados para integração, informe estes dados na tela do módulo de configuração.</p>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info" role="alert">
                            <span style="text-align:center;width: 100%;display: block;font-size: 15px;">
                                A configuração desta integração é realizada dentro do painel do parceiro.
                            </span>
                            </div>
                        </div>
                    </div>
                    <div class="body-form-integration"></div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger col-md-4" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-success col-md-4"><?=$this->lang->line('application_send');?></button>
                </div>
                <input type="hidden" name="integration" value="">
                <input type="hidden" name="store" value="<?=$storeId?>">
            </div>
        </form>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="integration_tray">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_integration') . ' tray'?></span></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                        <div class="alert alert-info" role="alert">
                            <span style="text-align:center;width: 100%;display: block;font-size: 15px;">
                                A configuração desta integração é realizada dentro do painel do parceiro.
                            </span>
                            </div>
                            <div class="form-group">
                                <label>Domínio da Loja (URL)</label>
                                <input
                                        type="text"
                                        class="form-control"
                                        name="storeUrl"
                                        id="storeUrl"
                                        value="<?= set_value('storeUrl', $credentials->storeUrl ?? "") ?>"
                                        required
                                        readonly
                                >
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>ID da Loja</label>
                                <input
                                        type="text"
                                        class="form-control"
                                        name="storeId"
                                        id="storeId"
                                        value="<?= set_value('storeId', $credentials->storeId ?? "") ?>"
                                        readonly
                                >
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Token de autenticação / Access token</label>
                                <input type="text" class="form-control" name="accessToken" id="accessToken" value="<?= set_value('accessToken', $credentials->accessToken ?? "") ?>" readonly>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger col-md-4" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-success col-md-4"><?=$this->lang->line('application_send');?></button>
                </div>
                <input type="hidden" name="integration" value="tray">
                <input type="hidden" name="store" value="<?=$storeId?>">
                <input type="hidden" name="refreshToken" id="refreshToken" value="<?= set_value('refreshToken', $credentials->refreshToken ?? "") ?>">
                <input type="hidden" name="expirationAccessToken" id="expirationAccessToken" value="<?= set_value('expirationAccessToken', $credentials->expirationAccessToken ?? "") ?>">
                <input type="hidden" name="expirationRefreshToken" id="expirationRefreshToken" value="<?= set_value('expirationRefreshToken', $credentials->expirationRefreshToken ?? "") ?>">
                <input type="hidden" name="apiAddress" id="apiAddress" value="<?= set_value('apiAddress', $credentials->apiAddress ?? "") ?>">
                <input type="hidden" name="code" id="code" value="<?= set_value('code', $credentials->code ?? "") ?>">
                <input type="hidden" value='<?= json_encode($credentials ?? (object)[]) ?>'>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="integration_hub2b">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_integration') . ' hub2b'?></span></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>ID Loja
                                    <i
                                            class="fa fa-info-circle"
                                            aria-hidden="true"
                                            data-toggle="tooltip"
                                            data-placement="top"
                                            title="ID da Loja na HUB2B (idTenant)"
                                            data-original-title="ID da Loja na HUB2B (idTenant)">

                                    </i>
                                </label>
                                <input type="text" class="form-control" name="idTenant" required
                                       value="<?= set_value('idTenant', $credentials->idTenant ?? "") ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Auth Token (API V1)
                                    <i
                                            class="fa fa-info-circle"
                                            aria-hidden="true"
                                            data-toggle="tooltip"
                                            data-placement="top"
                                            title="Solicitar o token ao suporte HUB2B"
                                            data-original-title="Solicitar o token ao suporte HUB2B">

                                    </i>
                                </label>
                                <input type="text" class="form-control" name="authToken" required
                                       value="<?= set_value('authToken', $credentials->authToken ?? "") ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Username
                                    <i
                                            class="fa fa-info-circle"
                                            aria-hidden="true"
                                            data-toggle="tooltip"
                                            data-placement="top"
                                            title="Solicitar o Username ao suporte HUB2B"
                                            data-original-title="Solicitar o Username ao suporte HUB2B">

                                    </i>
                                </label>
                                <input type="text" class="form-control" name="username" required
                                       value="<?= set_value('username', $credentials->username ?? "") ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Password
                                    <i
                                            class="fa fa-info-circle"
                                            aria-hidden="true"
                                            data-toggle="tooltip"
                                            data-placement="top"
                                            title="Solicitar o Password ao suporte HUB2B"
                                            data-original-title="Solicitar o Password ao suporte HUB2B">

                                    </i>
                                </label>
                                <input type="password" class="form-control" name="password" required
                                       value="<?= set_value('password', $credentials->password ?? "") ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Access Token (API V2)
                                    <i
                                            class="fa fa-info-circle"
                                            aria-hidden="true"
                                            data-toggle="tooltip"
                                            data-placement="top"
                                            title="O token de acesso é gerado automaticamente através do username e password informados acima"
                                            data-original-title="O token de acesso é gerado automaticamente através do username e password informados acima">

                                    </i>
                                </label>
                                <input type="text" class="form-control" name="accessToken" readonly
                                       value="<?= set_value('accessToken', $credentials->accessToken ?? "") ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger col-md-4" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-success col-md-4"><?=$this->lang->line('application_send');?></button>
                </div>
                <input type="hidden" name="integration" value="hub2b">
                <input type="hidden" name="store" value="<?= $storeId ?>">
                <input type="hidden" name="refreshToken"
                       value="<?= set_value('refreshToken', $credentials->refreshToken ?? "") ?>">
                <input type="hidden" name="expirationAccessToken"
                       value="<?= set_value('expirationAccessToken', $credentials->expirationAccessToken ?? "") ?>">
                <input type="hidden" name="expirationRefreshToken"
                       value="<?= set_value('expirationRefreshToken', $credentials->expirationRefreshToken ?? "") ?>">
                <input type="hidden" value='<?= json_encode($credentials ?? (object)[]) ?>'>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="integration_ideris">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_integration') . ' ideris'?></span></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Token de autenticação
                                    <i
                                            class="fa fa-info-circle"
                                            aria-hidden="true"
                                            data-toggle="tooltip"
                                            data-placement="top"
                                            title="Solicitar token ao suporte da Ideris"
                                            data-original-title="Solicitar token ao suporte da Ideris">

                                    </i>
                                </label>
                                <input
                                        type="text"
                                        class="form-control"
                                        name="authToken"
                                        id="authToken"
                                        value="<?= set_value('authToken', $credentials->authToken ?? "") ?>"
                                        required
                                >
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Access token
                                    <i
                                            class="fa fa-info-circle"
                                            aria-hidden="true"
                                            data-toggle="tooltip"
                                            data-placement="top"
                                            title="Gerado após a autenticação"
                                            data-original-title="Gerado após a autenticação">

                                    </i>
                                </label>
                                <input type="text" class="form-control" name="accessToken" id="accessToken" value="<?= set_value('accessToken', $credentials->accessToken ?? "") ?>" readonly>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="integration" value="ideris">
                    <input type="hidden" name="expirationAccessToken" id="expirationAccessToken"
                           value="<?= set_value('expirationAccessToken', $credentials->expirationAccessToken ?? "") ?>">
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger col-md-4" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-success col-md-4"><?=$this->lang->line('application_send');?></button>
                </div>
                <input type="hidden" name="integration" value="ideris">
                <input type="hidden" name="store" value="<?=$storeId?>">
            </div>
        </form>
    </div>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="integration_microvix">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><?=$this->lang->line('application_integration') . ' Microvix'?></span></h4>
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <!-- Campo Usuário -->
                            <div class="form-group">
                                <label for="usuario">Usuário</label>
                                <input type="text" id="microvix_usuario" class="form-control" name="microvix_usuario" value="<?=$credentials->microvix_usuario??''?>">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <!-- Campo Senha -->
                            <div class="form-group">
                                <label for="senha">Senha</label>
                                <input type="password" id="microvix_senha" class="form-control" name="microvix_senha" value="<?=$credentials->microvix_senha??''?>">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <!-- Campo CNPJ -->
                            <div class="form-group">
                                <label for="senha">CNPJ</label>
                                <input type="text" id="microvix_cnpj" class="form-control" name="microvix_cnpj" value="<?=$credentials->microvix_cnpj??''?>">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <!-- Campo Id Portal -->
                            <div class="form-group">
                                <label for="id_portal">Id Portal</label>
                                <input type="text" id="microvix_id_portal" class="form-control" name="microvix_id_portal" value="<?=$credentials->microvix_id_portal??''?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger col-md-4" data-dismiss="modal">
                        <?=$this->lang->line('application_close');?>
                    </button>
                    <button type="submit" class="btn btn-success col-md-4">
                        <?=$this->lang->line('application_send');?>
                    </button>
                </div>
                <input type="hidden" name="integration" value="microvix">
                <input type="hidden" name="store" value="<?=$storeId?>">
            </div>
        </form>
    </div>
</div>



<div id="MODALNEWINTEGRATION"></div>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>
<script>
    var action = "<?= $_GET['action'] ?? ''?>";
    var integration = "<?=$integration ?? 'precode'?>";
    var integrations_erps = <?=$this->data['integration_logistic_by_name']?>;
    var integrations_backoffice = <?=$this->data['integrations_backoffice']?>;


    $(document).ready(function () {
        if (action == 'modal-open') {
            if ($('button[data-target="#integration_' + integration + '"]').length > 0) {
                $('button[data-target="#integration_' + integration + '"]').click();
            }
        }

        setTimeout(() => {
            setFormCredentialsStore();
            setOptionsLogistic();
        }, 500);
    });

    const baseUrl = "<?=base_url('stores/integration')?>";

    $(function(){
        $('#mainIntegrationApiNav').addClass('active');
        $('#requestIntegration').addClass('active');
        $('[data-toggle="tooltip"]').tooltip();
        $('.select2').select2();
    });

    $('[name="storeFilter"]').change(function () {
        const value = $(this).val();
        window.location.href = `${baseUrl}/${value}`;
    });

    $(document).on('click', '.copy-input', function() {
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

    $(document).on('change', '[name="quote_via_integration"]', function(){
        const checked = $(this).is(':checked');
        $(this).parents('.modal-body').find('[name="url_quote_via_integration"]').val('').prop('disabled', !checked).prop('required', checked);

        if (checked) {
            $(this).parents('.modal-body').find('[name="use_logistics_equals_integration"]').prop('checked', false);
            $(this).parents('.modal-body').find('[name="update_order_to_in_transit_and_delivery"]').prop('checked', true);
        }
    });

    $(document).on('change', '[name="price_not_update"]', function(){
        const checked = $(this).is(':checked');
        if (checked) {
            $(this).parents('.modal-body').find('[name="price_not_update"]').val('1').prop('checked', true);
        }
    });

    $(document).on('change', '[name="update_order_to_in_transit_and_delivery"]', function(){
        const checked = $(this).is(':checked');

        if (!checked) {
            $(this).parents('.modal-body').find('[name="quote_via_integration"]').prop('checked', false).trigger('change');
        } else {
            $(this).parents('.modal-body').find('[name="use_logistics_equals_integration"]').prop('checked', false);
        }
    });

    $(document).on('change', '[name="use_logistics_equals_integration"]', function(){
        const checked = $(this).is(':checked');

        $(this).parents('.modal-body').find('[name="quote_via_integration"], [name="update_order_to_in_transit_and_delivery"]').prop('disabled', checked).trigger('change');

        if (checked) {
            $(this).parents('.modal-body').find('[name="url_quote_via_integration"]').val('').prop('disabled', true);
            $(this).parents('.modal-body').find('[name="quote_via_integration"], [name="update_order_to_in_transit_and_delivery"]').prop('checked', false);
        }

    });

    const setOptionsLogistic = () => {

        $('.modal:not(#removeIntegration) .modal-body').append(`
            <div class="row row-general-config">
                <div class="col-md-12">
                    <div class="form-group">
                        <h4>Configurações Gerais</h4>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 d-none form_use_logistics_equals_integration">
                    <div class="form-group">
                        <label><input type="checkbox" name="use_logistics_equals_integration"> Usar a logística <span class="integration_name"></span></label> <i class="fa fa-info-circle" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="Nesta opção, a atualização de pedidos e cotação de frete serão realizadas pela plataforma."></i>
                    </div>
                </div>
                <div class="col-md-12 d-none form_price_not_update">
                    <div class="form-group">
                        <label><input type="checkbox" name="price_not_update" value=<?php if(isset($this->data['price_not_update'])) echo 1; ?> <?php if(isset($this->data['price_not_update'])) echo "checked";?>> Não Atualizar Preços via integração <span class="integration_name"></span></label> <i class="fa fa-info-circle" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="Nesta opção, a atualização de preços dos produtos deverá ser realizada de forma manual."></i>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label><input type="checkbox" name="update_order_to_in_transit_and_delivery"> Atualizar pedidos para enviado e entregue via integração</label>
                    </div>
                </div>
                <div class="col-md-12 content-custom-options">
                </div>
                <div class="form-group col-md-5">
                    <label><input type="checkbox" name="quote_via_integration"> Cotar frete via integração</label>
                </div>
                <div class="form-group col-md-7">
                    <input type="url" class="form-control" name="url_quote_via_integration" value="" placeholder="Insira a URL para cotação via integração" disabled>
                </div>
            </div>
        `);

        $('[data-integration]').on('click', function() {
            const integration = $(this).data('integration');
            $('#integrationExterno [name="integration"]').val(integration);
        });

        $('.modal form').each(function (key, value) {
            let integration = integrations_erps[$(value).find('[name="integration"]').val()];
            let integration_backoffice = integrations_backoffice.includes($(value).find('[name="integration"]').val());

            //console.log(integration_backoffice);

            let url_quote_via_integration = false;
            let quote_via_integration = false;
            let update_order_to_in_transit_and_delivery = false;

            $(this).find('.form_use_logistics_equals_integration').hide();
            $(this).find('[name="url_quote_via_integration"]').closest('.form-group').hide();
            $(this).find('[name="quote_via_integration"]').closest('.form-group').hide();
            $(this).find('[name="update_order_to_in_transit_and_delivery"]').closest('.form-group').hide();


            if (typeof integration !== "undefined") {
               $(this).find('.form_use_logistics_equals_integration .integration_name').text(integration);

                if (!integration_backoffice) {
                    url_quote_via_integration = true;
                    quote_via_integration = true;
                    update_order_to_in_transit_and_delivery = true;
                } else {
                    $(this).find('.form_use_logistics_equals_integration').show();
                }

                if (integration === 'Magalu') {
                    $(this).find('.content-custom-options').append(`<div class="form-group"><label><input type="checkbox" name="save_images_in_father_product"> Integrar imagens da variação no produto Pai Magalu</label> <i class="fa fa-info-circle" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="Quando ativo, a integração irá integrar as imagens da primeira no produto pai"></i></div>`);
                    if ($(this).find('[name="old_value_save_images_in_father_product"]').length) {
                        $(this).find('.content-custom-options input[name="save_images_in_father_product"]').prop('checked', $(this).find('[name="old_value_save_images_in_father_product"]').val() === '1' ? true : false);
                    }
                }
            } else {
                if (integration_backoffice) {
                    $(this).find('.integration_name').text(integration);
                    $(this).find('.form_use_logistics_equals_integration').show();
                } else {
                    url_quote_via_integration = true;
                    quote_via_integration = true;
                    update_order_to_in_transit_and_delivery = true;
                }
            }

            if (url_quote_via_integration) {
                $(this).find('[name="url_quote_via_integration"]').closest('.form-group').show();
            }
            if (quote_via_integration) {
                $(this).find('[name="quote_via_integration"]').closest('.form-group').show();
            }
            if (update_order_to_in_transit_and_delivery) {
                $(this).find('[name="update_order_to_in_transit_and_delivery"]').closest('.form-group').show();
            }

            if (
                $(this).find('.form_use_logistics_equals_integration').is(':not(:visible)') &&
                $(this).find('[name="url_quote_via_integration"]').is(':not(:visible)') &&
                $(this).find('[name="quote_via_integration"]').is(':not(:visible)') &&
                $(this).find('[name="update_order_to_in_transit_and_delivery"]').is(':not(:visible)')
            ) {
                $(this).find('.form_price_not_update').show();
                //$(this).find('.row-general-config').hide();
            }
        });

        if ($('.group-integration .btn.btn-warning[data-toggle="modal"]').length) {
            const integration = $($('.group-integration .btn.btn-warning[data-toggle="modal"]').data('target')).find('form [name="integration"]').val();
            const store_id = $('[name="storeFilter"]').val();
            const type_integration = "0";
            $.get(`<?=base_url("logistics/getDataIntegration/precode")?>/${type_integration}/${store_id}`, response => {
                let credentials;
                if (response !== null) {
                    credentials = JSON.parse(response.credentials);
                    $('[name="update_order_to_in_transit_and_delivery"]').prop('checked', true);
                    if (Object.keys(credentials).length) {
                        $('[name="quote_via_integration"]').prop('checked', true).trigger('change');
                        $('[name="url_quote_via_integration"]').val(credentials.endpoint);
                    }
                } else {
                    $.get(`<?=base_url("logistics/getDataIntegration")?>/${integration}/${type_integration}/${store_id}`, response => {
                        if (response !== null) {
                            $('[name="use_logistics_equals_integration"]').prop('checked', true).trigger('change');
                        }
                    }).fail(e => {
                        console.log(e);
                    })
                }
            }).fail(e => {
                console.log(e);
            })
        }

    }

    const setFormCredentialsStore = () => {
        $(`
            #integrationAnyMarket .modal-body .body-form-integration,
            #integrationPrecode .modal-body .body-form-integration,
            #integrationAton .modal-body .body-form-integration,
            #integrationHubsell .modal-body .body-form-integration,
            #integrationExterno .modal-body .body-form-integration
        `).empty().append(`
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>Login/E-mail</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="interface" value="<?=$user_email?>" readonly>
                        <span class="input-group-btn">
                            <button type="button" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                        </span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Token de acesso da loja</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?=$store['token_api']??''?>" readonly>
                        <span class="input-group-btn">
                            <button type="button" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                        </span>
                    </div>
                </div>
                <div class="form-group">
                    <label>ID da loja</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?=$storeId?>" readonly>
                        <span class="input-group-btn">
                            <button type="button" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        `);
    }

    $('#integrationBlingV3 form').on('submit', function(e){            
        e.preventDefault();
        const datastring = $(this).serialize();

        $.ajax({
            type: "POST",
            url: `<?=base_url("Integrations/saveIntegrationOauth")?>`,
            data: datastring,
            success: function(data) {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    window.location.href = '<?="https://www.bling.com.br/Api/v3/oauth/authorize?response_type=code&client_id=" . ($integration_configuration['bling_v3']['client_id'] ?? '') . "&state="?>' + data.token;
                }
            }
        });


    });

</script>
<style>
    .widget-user .box-footer{
        border-left: 1px solid #eee;
        border-right: 1px solid #eee;
    }
    .widget-user-image img{
        border: 0px !important;
    }
    .box-widget .widget-user-header h3{
        color: #fff;
    }
    .widget-user .widget-user-header{
        height: 115px;
    }
    .widget-user .widget-user-image{
        top: 40px
    }
    .modal-footer::before,
    .modal-footer::after{
        display: none;
    }
    .group-integration:not(:nth-child(3)) {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px dashed #8d8d8d;
        width: 98%;
        margin-left: 1%;
    }

    /* colors ERP */
    .box-widget.default-integration .widget-user-header{
        background-color: #fff;
        border: 1px solid #eee;
    }
    .box-widget.default-integration .widget-user-image {
        display: flex;
        justify-content: center;
        left: unset;
        margin-left: unset;
        top: 45px;
        position: absolute;
        width: 100%;
    }
    .box-widget.default-integration .widget-user-image>img {
        max-height: 50px;
    }



    .box-widget.bling .widget-user-header{
        background-color: #3ca710
    }
    .box-widget.tiny .widget-user-header{
        background-color: #81aaf3
    }
    .box-widget.vtex .widget-user-header{
        background-color: #ffa2c1
    }
    .box-widget.shopify .widget-user-header{
        background-color: #6b872f
    }
    .box-widget.bseller .widget-user-header{
        background-color: #17A086
    }
    .box-widget.novomundo .widget-user-header{
        background-color: #fff;
    }
    .box-widget.jn2 .widget-user-header{
        background-color: #1a212d;
    }
    .box-widget.anymarket .widget-user-header{
        background-color: rgb(39,86,179);
    }
    .box-widget.lojaintegrada .widget-user-header{
        background-color: #E4F6F7;
    }
    .box-widget.precode .widget-user-header{
        background-color: #FFFFFF;
    }
    .box-widget.viavarejo_b2b .widget-user-header{
        background-color: #aec5dd;
    }
    .box-widget.tray .widget-user-header{
        background-color: #FFFFFF;
    }
    .box-widget.hub2b .widget-user-header{
        background-color: #008800;
    }
    .box-widget.ideris .widget-user-header{
        background-color: #008800;
    }
    .box-widget.NEWINTEGRATION .widget-user-header{
        background-color: #FFFFFF;
    }
    
    .center {
        display: block;
        margin-left: auto;
        margin-right: auto;
        width: 50%;
    }
    .warning-revoke-left {
        position: absolute;
        left: 10%;
        top: 15%;
        font-size: 35px;
    }
    .warning-revoke-right {
        position: absolute;
        right: 10%;
        top: 15%;
        font-size: 35px;
    }
</style>
