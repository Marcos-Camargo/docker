<!--
SW Serviços de Informática 2019

Editar Produtos

-->
<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>
<?php
use App\Libraries\FeatureFlag\FeatureManager;

$mainImage = lang('application_main_image') ?? 'Imagem principal';
$hasVariation = ($product_data['has_variants'] == '') ? 0 : 1;
$hasVariation = $product_data['with_variation'] == 1 ? 1 : $hasVariation;

$limite_variacoes = $this->CI->model_settings->getLimiteVariationActive();
if(!empty($limite_variacoes)) {
    $limite_variacoes = 1;
}else{
    $limite_variacoes = 0;
}
?>
<link rel="stylesheet" href="<?php echo base_url('assets/dist/css/views/products/hack/form.product.css') ?>">
<style>
    .file-preview-thumbnails.clearfix>div:first-child>div:first-child::before {
        content: '<?=$mainImage?>';
    }
    .btn-tooltip-sku-integartion {
        border-right: 1px solid #ccc;
        padding: 9px 3px;
        border-top: 1px solid #ccc;
        border-bottom: 1px solid #ccc;
        cursor: pointer;
    }
</style>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper content-wrapper-edit">

    <?php $data['pageinfo'] = "application_edit";
    $this->load->view('templates/content_header', $data);
    $limite_imagens_aceitas_api = $this->CI->model_settings->getValueIfAtiveByName('limite_imagens_aceitas_api') ?? 5;
    if (!isset($limite_imagens_aceitas_api) || $limite_imagens_aceitas_api <= 0) {
        $limite_imagens_aceitas_api = 5;
    }
    ?>

    <!-- Main content -->
    <section class="content">
        <div class="col-md-12">
            <div class="row" style="margin-top: 10px">
                <div class="col-md-12 col-xs-12">
                    <div id="messages"></div>

                    <?php if ($this->session->flashdata('success')) : ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                            <?php echo $this->session->flashdata('success'); ?>
                        </div>
                    <?php elseif ($this->session->flashdata('error')) : ?>
                        <div class="alert alert-error alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                            <?php echo $this->session->flashdata('error'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 col-xs-12">
                    <?php
                    if (validation_errors()) {
                        foreach (explode("</p>", validation_errors()) as $erro) {
                            $erro = trim($erro);
                            if ($erro != "") { ?>
                                <div class="alert alert-error alert-dismissible" role="alert">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                                aria-hidden="true">&times;</span></button>
                                    <?php echo $erro . "</p>"; ?>
                                </div>
                            <?php }
                        }
                    } ?>

                    <?php
                    $numft = 0;
                    if (strpos(".." . $product_data['image'], "http") > 0) {
                        $fotos = explode(",", $product_data['image']);
                        foreach ($fotos as $foto) {
                            $numft++;
                            $ln1[$numft] = $foto;
                            $ln2[$numft] = '{width: "120px", key: "' . $foto . '", extra: function() {return loadAfterFileInputPreview($("#addImagemForm"));}}';
                        }
                    } else {
                        // Verifica se o produto que está sendo mostrado está ou não no bucket.
                        if (!$product_data["is_on_bucket"]) {
                            // Caso não esteja, aplica a lógica anterior.
                            $fotos = array();
                            if (is_dir(FCPATH . 'assets/images/product_image/' . $product_data['image'])) {
                                $fotos = scandir(FCPATH . 'assets/images/product_image/' . $product_data['image']);
                            }
                            foreach ($fotos as $foto) {
                                if (($foto != ".") && ($foto != "..") && ($foto != "") && (!is_dir(FCPATH . 'assets/images/product_image/' . $product_data['image'] . '/' . $foto))) {
                                    $numft++;
                                    $ln1[$numft] = base_url('assets/images/product_image/' . $product_data['image'] . '/' . $foto);
                                    $ln2[$numft] = '{width: "120px", key: "' . $product_data['image'] . '/' . $foto . '"}';
                                }
                            }
                        } else {
                            // Prefixo de url para buscar a imagem.
                            $asset_prefix = "assets/images/product_image/" . $product_data['image'] . "/";

                            // Busca as imagens do produto já formatadas.
                            $product_images = $this->bucket->getFinalObject($asset_prefix);

                            // Caso tenha dado certo, busca o conteudo.
                            if ($product_images['success']) {
                                // Percorre cada elemento e verifica se não é imagem de variação.
                                foreach ($product_images['contents'] as $key => $image_data) {
                                    // Monta a chave da imagem completa.
                                        $full_key = $product_data['image'] . '/' . $image_data['key'];
                                        $numft++;
                                        $ln1[$numft] = $image_data['url'];
                                        $ln2[$numft] = '{width: "120px", key: "' . $full_key . '"}';
                                }
                            }
                        }
                    }

                    ?>

                    <?php
                    $numft_variation = 0;
                    if (!$product_data["is_on_bucket"]) {
                        for ($i = 1; $i <= $product_variants['numvars']; $i++) {
                            $product_variant = $product_variants[$i];
                            $fotos = array();
                            if (is_dir(FCPATH . 'assets/images/product_image/' . $product_data['image'] . '/' . $product_variant['image'])) {
                                $fotos = scandir(FCPATH . 'assets/images/product_image/' . $product_data['image'] . '/' . $product_variant['image']);
                            }
                            foreach ($fotos as $foto) {
                                if (($foto != ".") && ($foto != "..") && ($foto != "") && (!is_dir(FCPATH . 'assets/images/product_image/' . $product_data['image'] . '/' . $product_variant['image'] . '/' . $foto))) {
                                    $numft_variation++;
                                }
                            }
                        }
                    } else {
                        // Para cada variante verifica se há algum objeto nela.
                        for ($i = 1; $i <= $product_variants['numvars']; $i++) {
                            $product_variant = $product_variants[$i];
                            //isDirectory irá retornar se ao menos um objeto existir com este prefixo.
                            if ($this->bucket->isDirectory('assets/images/product_image/' . $product_data['image'] . '/' . $product_variant['image'])) {
                                $numft_variation++;
                            }
                        }
                    }
                    // 1 - falta dados - foi cadastrado mas ainda faltam a foto ou a categoria
                    // 2 - novo - está com todos os dados mas ainda não está no bling (EAN novo)
                    // 3 - cadastro bling - já foi enviado ao Bling mas precisa ser dado o OK q foi associado aos marketplaces
                    // 4 - cadastrado completo - EAN velho.
                    $divclass = "alert-success";
                    if ($product_data['situacao'] == '1') {
                        $msg = $this->lang->line('messages_product_missing_information') . " :";
                        if ($numft == 0 && $numft_variation == 0) {
                            $msg .= " " . $this->lang->line('application_photos') . ",";
                        }
                        if ($product_data['category_id'] == '[""]') {
                            $msg .= " " . $this->lang->line('application_category') . ",";
                        }
                        if ($product_data['brand_id'] == '') {
                            $msg .= " " . $this->lang->line('application_brand') . ",";
                        }
                        $msg = substr($msg, 0, -1);
                        $divclass = "alert-danger";
                    } elseif ($product_data['situacao'] == '2') {
                        $msg = $this->lang->line('messages_complete_registration');

                    } elseif ($product_data['situacao'] == '3') {
                        $msg = $this->lang->line('messages_complete_registration');
                    } else {
                        $msg = $this->lang->line('messages_complete_registration');
                    }

                    ?>
                    <div class="alert <?php echo $divclass ?>  alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                        <?php echo $msg; ?>
                    </div>

                    <?php
                    if ($product_data['status'] == 4) { ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                        aria-hidden="true">&times;</span></button>
                            <?= $this->lang->line('application_blocked_product'); ?><br/>
                            <?php foreach ($product_data['rule'] as $rule) {
                                if ($this->session->userdata('usercomp') == 1) {
                                    echo $rule['blacklist_id'] . ' - ';
                                }
                                echo ucfirst($rule['sentence']) . "<br />";
                            } ?>
                        </div>
                    <?php }
                    ?>
                    <?php if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration") && count($sku_locks) > 0):?>
                        <div class="alert alert-error alert-dismissible" role="alert">
                            <?php foreach ($sku_locks as $lock) {
                                ?>
                                <p><?php echo "({$lock['sku_mkt']}) - " .$this->lang->line('application_blocked_sku').": ".$lock['note'];?></p>
                            <?php } ?>
                        </div>
                    <?php endif?>
                    <?php if (count($errors_transformation) > 0) : ?>
                        <div class="alert alert-error alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                        aria-hidden="true">&times;</span></button>
                            <a href="#errors-transformation"><?= $this->lang->line('application_errors_tranformation_msg') ?></a>

                        </div>
                    <?php endif
                    ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 col-xs-12">

                    <?php if ($product_data['id'] > 0) { ?>
                    <form action="<?php base_url('products/update') ?>" method="post" enctype="multipart/form-data"
                          id="formUpdateProduct">
                        <?php } else { ?>
                        <form action="<?= base_url('products/create') ?>" method="post"
                              enctype="multipart/form-data" id="formUpdateProduct">
                            <?php } ?>
                            <input type="hidden" name="copy_prod_data"
                                   value="<?= $product_data['copy_prod_data'] ?? false ?>">

                            <div class="row">
                                <div class="col-md-5">
                                    <div class="box">
                                        <div class="box-body">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h2>
                                                        <?= lang('application_main_informations') ?>
                                                    </h2>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <?php if (isset($prd_original)){ ?>
                                                    <div class="form-group col-md-12 col-xs-12">
                                                        <div class="alert alert-warning alert-dismissible">
                                                        <p><strong><?=$this->lang->line('application_multi_channel_fulfillment_store');?></strong></p>
                                                        </div>
                                                    </div>
                                                <?php  }  ?>

                                                <div class="form-group col-md-12 col-xs-12">
                                                    <label for="product_name"><?= $this->lang->line('application_name'); ?>
                                                        (*)</label>
                                                    <input type="text"
                                                           class="form-control" <?= (!$storeCanUpdateProduct && $notAdmin ? 'readonly' : '') ?>
                                                           id="product_name" onkeyup="characterLimit(this)"
                                                           maxlength="<?= $product_length_name ?>"
                                                           onchange="verifyWords()"
                                                           name="product_name" required
                                                           placeholder="<?= $this->lang->line('application_enter_product_name'); ?>"
                                                           value="<?php echo set_value('product_name', $product_data['name']); ?>"
                                                           autocomplete="off"/>
                                                    <span id="char_product_name"></span><br/>
                                                    <span class="label label-warning" id="words_product_name"
                                                          data-toggle="tooltip"
                                                          data-placement="top"
                                                          title="<?= $this->lang->line('application_explanation_of_forbidden_words'); ?>"></span>
                                                </div>

                                                <input type="hidden" name="has_integration"
                                                       value="<?= (!$storeCanUpdateProduct && $notAdmin ? true : false) ?>"/>

                                                <div class="form-group col-md-9 col-xs-12">
                                                    <label for="sku"><?= $this->lang->line('application_sku'); ?>
                                                        (*)</label>
                                                    <div class="d-flex flex-nowrap align-items-center">
                                                    <input type="text" <?= (!$storeCanUpdateProduct && $notAdmin ? 'readonly' : '') ?>
                                                           class="form-control" id="sku" name="sku"
                                                           maxlength="<?= $product_length_sku ?>" required
                                                           placeholder="<?= $this->lang->line('application_enter_sku'); ?>"
                                                           value="<?php echo set_value('sku', $product_data['sku']); ?>"
                                                           autocomplete="off"
                                                           onKeyUp="checkSpecialSku(event, this);characterLimit(this);"
                                                           onblur="checkSpecialSku(event, this);"/>
                                                    <?php if (!empty($product_data['product_id_erp'])): ?>
                                                        <i class="fa fa-info-circle btn-tooltip-sku-integartion" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="SKU Integração: <?=$product_data['product_id_erp']?>"></i>
                                                    <?php endif ?>
                                                    </div>
                                                    <span id="char_sku"></span>
                                                    <br/>
                                                </div>
                                                <div class="form-group col-md-5 col-xs-6">
                                                    <label for="status"><?= $this->lang->line('application_availability'); ?>
                                                        (*)</label>
                                                    <input type="hidden" id="status" name="status"
                                                           value="<?= $product_data['status'] ?>">
                                                    <div id="ProductStatusComponent"
                                                         data-base-url="<?= base_url() ?>"
                                                         data-endpoint="products"
                                                         data-ref-element="$('#status')"
                                                         data-origin="products/edit"
                                                         data-product-status="<?= $product_data['status'] ?>"
                                                         data-product-id="<?= $product_data['id'] ?>"
                                                         data-enable-delete="<?= in_array('moveProdTrash', $this->permission) ?>"
                                                         data-status='<?php echo json_encode([
                                                             [
                                                                 'code' => Model_products::ACTIVE_PRODUCT,
                                                                 'alias' => 'active',
                                                                 'description' => $this->lang->line('application_available'),
                                                             ],
                                                             [
                                                                 'code' => Model_products::INACTIVE_PRODUCT,
                                                                 'alias' => 'inactive',
                                                                 'description' => $this->lang->line('application_unavailable')
                                                             ],
                                                             [
                                                                 'code' => Model_products::DELETED_PRODUCT,
                                                                 'alias' => 'deleted',
                                                                 'description' => $this->lang->line('application_deleted')
                                                             ],
                                                             [
                                                                 'code' => Model_products::BLOCKED_PRODUCT,
                                                                 'alias' => 'blocked',
                                                                 'description' => $this->lang->line('application_under_analysis')
                                                             ]
                                                         ]); ?>'
                                                    >
                                                    </div>
                                                    <?php
                                                    include_once APPPATH . 'views/products/components/popup.update.status.product.php';
                                                    ?>
                                                    <script src="<?php echo base_url('assets/dist/js/components/products/product.status.component.js') ?>"></script>
                                                </div>
                                                <div class="form-group col-md-4 col-xs-6">
                                                    <label for="with_variation"><?= $this->lang->line('application_product_with_variation'); ?>
                                                        ?</label>
                                                    <input type="hidden" id="with_variation" name="with_variation"
                                                           value="<?= $hasVariation ?>">
                                                    <div id="RadioTwoOptionsComponent"
                                                         data-active-value="1"
                                                         data-enabled="<?=$storeCanUpdateProduct ? 1 : 0?>"
                                                         data-ref-element="$('#with_variation')"
                                                         data-active-option='<?php echo json_encode([
                                                             'value' => '1',
                                                             'alias' => 'with_variation',
                                                             'color' => 'info',
                                                             'description' => $this->lang->line('application_yes'),
                                                         ]); ?>'
                                                         data-inactive-option='<?php echo json_encode([
                                                             'value' => '0',
                                                             'alias' => 'without_variation',
                                                             'color' => 'default',
                                                             'description' => $this->lang->line('application_no'),
                                                         ]); ?>'
                                                    >
                                                    </div>
                                                    <script src="<?php echo base_url('assets/dist/js/components/radio/two.options.component.js') ?>"></script>
                                                </div>
                                                <div class="col-md-10 col-xs-12">
                                                    <?php if ($product_data['id'] > 0) { ?>
                                                        <div class="row">
                                                            <small><i>
                                                                    <div class="col-md-6 col-xs-6">
                                                                        <label><?= $this->lang->line('application_created_on'); ?></label>
                                                                        <span><?php echo date("d/m/Y H:i:s", strtotime($product_data['date_create'])); ?></span>
                                                                    </div>
                                                                    <div class="col-md-6 col-xs-6">
                                                                        <label><?= $this->lang->line('application_updated_on'); ?></label>
                                                                        <span><?php echo date("d/m/Y H:i:s", strtotime($product_data['date_update'])); ?></span>
                                                                    </div>
                                                                </i></small>
                                                        </div>
                                                    <?php } ?>
                                                </div>


                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="box" id="addImagemForm">
                                        <div class="box-body">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h2><?= lang('application_uploadimages'); ?></h2>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="form-group col-md-12 col-xs-12">
                                                    <div class="kv-avatar">
                                                        <div class="file-loading">
                                                            <input type="file" <?= (!$storeCanUpdateProduct && $notAdmin ? 'readonly' : '') ?>
                                                                   id="prd_image" name="prd_image[]"
                                                                   accept="image/png, image/jpeg"
                                                                   multiple>
                                                        </div>
                                                    </div>
                                                    <input type="hidden" name="product_image" id="product_image"
                                                           value="<?= $product_data['image']; ?>"/>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row" id="formVariations" style="display: none;">
                                <div class="col-md-12">
                                    <div class="box">
                                        <div class="box-body">
                                            <div class="row">
                                                <div class="form-group col-md-12 col-xs-12">
                                                    <div class="row">
                                                        <div class="col-md-2 col-xs-12">
                                                            <h2><?= lang('application_variations'); ?></h2>
                                                        </div>
                                                        <div class="form-check col-md-1 col-xs-12"
                                                             style="display:none;">
                                                            <?php if (!$storeCanUpdateProduct) { ?>
                                                                <input type="checkbox" disabled
                                                                       class="form-check-input" <?= set_checkbox('semvar', 'on', $product_data['has_variants'] == "") ?>>
                                                                <input type="hidden" id="semvar" name="semvar"
                                                                       value="<?= $product_data['has_variants'] == '' ? 'on' : 'off'; ?>">
                                                            <?php } else { ?>
                                                                <input type="checkbox" class="form-check-input"
                                                                       id="semvar"
                                                                       name="semvar" <?= set_checkbox('semvar', 'on', $product_data['has_variants'] == "") ?>>
                                                            <?php } ?>
                                                            <label for="semvar"><?= $this->lang->line('application_without_variations'); ?></label>
                                                        </div>
                                                        <div class="form-check col-md-1 col-xs-12">
                                                            <?php if (!$storeCanUpdateProduct) { ?>
                                                                <input type="checkbox" disabled
                                                                       class="form-check-input checkbox-limited" <?= set_checkbox('sizevar', 'on', (strpos($product_data['has_variants'], "TAMANHO") !== false)) ?>>
                                                                <input type="hidden" id="sizevar" name="sizevar"
                                                                       value="<?= (strpos($product_data['has_variants'], 'TAMANHO') !== false) ? 'on' : 'off'; ?>" <?= (strpos($product_data['has_variants'], 'TAMANHO') !== false) ? 'checked' : ''; ?>>
                                                            <?php } else { ?>
                                                                <input type="checkbox" class="form-check-input checkbox-limited"
                                                                       id="sizevar"
                                                                       name="sizevar" <?= set_checkbox('sizevar', 'on', (strpos($product_data['has_variants'], "TAMANHO") !== false)) ?>>
                                                            <?php } ?>
                                                            <label for="sizevar"><?= $this->lang->line('application_size'); ?></label>
                                                        </div>
                                                        <div class="form-check col-md-1 col-xs-12">
                                                            <?php if (!$storeCanUpdateProduct) { ?>
                                                                <input type="checkbox" disabled
                                                                       class="form-check-input checkbox-limited" <?= set_checkbox('colorvar', 'on', (strpos($product_data['has_variants'], "Cor") !== false)) ? 'checked' : ''; ?>>
                                                                <input type="hidden" id="colorvar" name="colorvar"
                                                                       value="<?= (strpos($product_data['has_variants'], 'Cor') !== false) ? 'on' : 'off'; ?>" <?= (strpos($product_data['has_variants'], 'Cor') !== false) ? 'checked' : ''; ?>>
                                                            <?php } else { ?>
                                                                <input type="checkbox" class="form-check-input checkbox-limited"
                                                                       id="colorvar"
                                                                       name="colorvar" <?= set_checkbox('colorvar', 'on', (strpos($product_data['has_variants'], "Cor") !== false)) ? 'checked' : ''; ?>>
                                                            <?php } ?>
                                                            <label for="colorvar"><?= $this->lang->line('application_color'); ?></label>
                                                        </div>
                                                        <div class="form-check col-md-1 col-xs-12">
                                                            <?php if (!$storeCanUpdateProduct) { ?>
                                                                <input type="checkbox" disabled
                                                                       class="form-check-input checkbox-limited" <?= set_checkbox('voltvar', 'on', (strpos($product_data['has_variants'], "VOLTAGEM") !== false)) ? 'checked' : ''; ?>>
                                                                <input type="hidden" id="voltvar" name="voltvar"
                                                                       value="<?= (strpos($product_data['has_variants'], "VOLTAGEM") !== false) ? 'on' : 'off'; ?>" <?= (strpos($product_data['has_variants'], "VOLTAGEM") !== false) ? 'checked' : ''; ?>>
                                                            <?php } else { ?>
                                                                <input type="checkbox" class="form-check-input checkbox-limited"
                                                                       id="voltvar"
                                                                       name="voltvar" <?= set_checkbox('voltvar', 'on', (strpos($product_data['has_variants'], "VOLTAGEM") !== false)) ? 'checked' : ''; ?>>
                                                            <?php } ?>
                                                            <label for="voltvar"><?= $this->lang->line('application_voltage'); ?></label>
                                                        </div>
                                                        <div class="form-check col-md-1 col-xs-12" <?= (isset($flavor_active) == '' ? 'hidden' : '') ?>>
                                                            <?php if (!$storeCanUpdateProduct) { ?>
                                                                <input type="checkbox" disabled
                                                                       class="form-check-input checkbox-limited" <?= set_checkbox('saborvar', 'on', (strpos($product_data['has_variants'], "SABOR") !== false)) ? 'checked' : ''; ?>>
                                                                <input type="hidden" id="saborvar" name="saborvar"
                                                                       value="<?= (strpos($product_data['has_variants'], "SABOR") !== false) ? 'on' : 'off'; ?>" <?= (strpos($product_data['has_variants'], "SABOR") !== false) ? 'checked' : ''; ?>>
                                                            <?php } else { ?>
                                                                <input type="checkbox" class="form-check-input checkbox-limited"
                                                                       id="saborvar"
                                                                       name="saborvar" <?= set_checkbox('saborvar', 'on', (strpos($product_data['has_variants'], "SABOR") !== false)) ? 'checked' : ''; ?>>
                                                            <?php } ?>
                                                            <label for="saborvar"><?= $this->lang->line('application_flavor'); ?></label>
                                                        </div>
                                                        <div class="form-check col-md-1 col-xs-12" <?= (isset($degree_active) == '' ? 'hidden' : '') ?>>
                                                            <?php if (!$storeCanUpdateProduct) { ?>
                                                                <input type="checkbox" disabled
                                                                       class="form-check-input checkbox-limited" <?= set_checkbox('grauvar', 'on', (strpos($product_data['has_variants'], "GRAU") !== false)) ? 'checked' : ''; ?>>
                                                                <input type="hidden" id="grauvar" name="grauvar"
                                                                       value="<?= (strpos($product_data['has_variants'], "GRAU") !== false) ? 'on' : 'off'; ?>" <?= (strpos($product_data['has_variants'], "Grau") !== false) ? 'checked' : ''; ?>>
                                                            <?php } else { ?>
                                                                <input type="checkbox" class="form-check-input checkbox-limited"
                                                                       id="grauvar"
                                                                       name="grauvar" <?= set_checkbox('grauvar', 'on', (strpos($product_data['has_variants'], "GRAU") !== false)) ? 'checked' : ''; ?>>
                                                            <?php } ?>
                                                            <label for="grauvar"><?= $this->lang->line('application_degree'); ?></label>
                                                        </div>
                                                        <div class="form-check col-md-1 col-xs-12" <?= (isset($side_active) == '' ? 'hidden' : '') ?>>
                                                            <?php if (!$storeCanUpdateProduct) { ?>
                                                                <input type="checkbox" disabled
                                                                       class="form-check-input checkbox-limited" <?= set_checkbox('ladovar', 'on', (strpos($product_data['has_variants'], "LADO") !== false)) ? 'checked' : ''; ?>>
                                                                <input type="hidden" id="ladovar" name="ladovar"
                                                                       value="<?= (strpos($product_data['has_variants'], "LADO") !== false) ? 'on' : 'off'; ?>" <?= (strpos($product_data['has_variants'], "Lado") !== false) ? 'checked' : ''; ?>>
                                                            <?php } else { ?>
                                                                <input type="checkbox" class="form-check-input checkbox-limited"
                                                                       id="ladovar"
                                                                       name="ladovar" <?= set_checkbox('ladovar', 'on', (strpos($product_data['has_variants'], "LADO") !== false)) ? 'checked' : ''; ?>>
                                                            <?php } ?>
                                                            <label for="ladovar"><?= $this->lang->line('application_side'); ?></label>
                                                        </div>
                                                        <div class="variation-alert col-md-6 col-xs-12">
                                                            <?php if ($displayPriceByVariation == '1') { ?>
                                                                <div class="form-group col-md-12">
                                                                    <span style="color:red; float: right; font-size: 12px"> <?= lang('messages_price_variations_price_product'); ?></span>
                                                                </div>
                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div id="variantModal" class="col-md-12 col-xs-12"
                                                     style="display:none;">
                                                     <input type="hidden" name="numvar" id="numvar"
                                                     value="<?php echo set_value('numvar', $product_variants['numvars'] + 1); ?>"/>
                                                    <input type="hidden" name="from" value="allocate"/>
                                                    <div class="container-fluid"
                                                         style="padding-left:0px;padding-right:0px;">
                                                        <div id="Lnvar" class="col-md-1 pd-no-left" style="width:auto;">
                                                            <label>Nº</label>
                                                        </div>
                                                        <div id="Ltvar" class="col-md-1 pd-person">
                                                            <label><?= $this->lang->line('application_size'); ?>
                                                                (*)</label>
                                                        </div>
                                                        <div id="Lcvar" class="col-md-1 pd-person">
                                                            <label><?= $this->lang->line('application_color'); ?>
                                                                (*)</label>
                                                        </div>
                                                        <div id="Lvvar" class="col-md-1 pd-person">
                                                            <label><?= $this->lang->line('application_voltage'); ?>
                                                                (*)</label>
                                                        </div>
                                                        <?php if ($flavor_active) { ?>
                                                            <div id="Lsvar" class="col-md-1 pd-person">
                                                                <label><?= $this->lang->line('application_flavor'); ?>
                                                                    (*)</label>
                                                            </div>
                                                        <?php } ?>
                                                        <?php if ($degree_active) { ?>
                                                            <div id="Lgvar" class="col-md-1 pd-person">
                                                                <label><?= $this->lang->line('application_degree'); ?>
                                                                    (*)</label>
                                                            </div>
                                                        <?php } ?>
                                                        <?php if ($side_active) { ?>
                                                            <div id="Llvar" class="col-md-1 pd-person">
                                                                <label><?= $this->lang->line('application_side'); ?>
                                                                    (*)</label>
                                                            </div>
                                                        <?php } ?>
                                                        <div id="Lqvar" class="col-md-1 pd-person">
                                                            <label><?= $this->lang->line('application_stock'); ?>
                                                                (*)</label>
                                                        </div>
                                                        <div id="Lskuvar" class="col-md-1 pd-person">
                                                            <label><?= $this->lang->line('application_sku'); ?>
                                                                (*)</label>
                                                        </div>
                                                        <div id="Leanvar" class="col-md-2 pd-person">
                                                            <label><?= $this->lang->line('application_ean'); ?></label>
                                                        </div>
                                                        <?php if ($displayPriceByVariation == '1') { ?>
                                                            <div id="Lpricevar" class="col-md-1 pd-person">
                                                                <label><?= $this->lang->line('application_list_price'); ?></label>
                                                            </div>
                                                            <div id="Lpricevar" class="col-md-1 pd-person">
                                                                <label><?= $this->lang->line('application_new_price'); ?></label>
                                                            </div>
                                                        <?php } ?>
                                                        <div class="col-md-1 pd-person">
                                                            <label style="white-space: nowrap"><?= $this->lang->line('application_enter_images_optional'); ?></label>
                                                        </div>
                                                    </div>
                                                    <div class="container-fluid col-person-one" id="variant1">
                                                        <?php
                                                        $number_status = (isset($product_variants[0]['status']) && $product_variants[0]['status'] == 1) ? 'success' : 'danger';
                                                        $duplicated_variation = true;
                                                        if (array_key_exists(0, $published_variations) && $published_variations[0]) {
                                                            $duplicated_variation = false;
                                                        }
                                                        ?>
                                                        <div id="Invar1" class="col-md-1"
                                                             style="padding-left: 0px;padding-right: 3px;width:auto;">
                                                            <span class="form-control label label-<?=$number_status?>">0</span>
                                                        </div>
                                                        <div id="Itvar" class="form-group col-md-1 "
                                                             style="padding-left: 0px;padding-right: 3px;">
                                                            <input type="text" class="form-control" id="T[]" name="T[]"
                                                                   placeholder="<?= $this->lang->line('application_size') ?>"
                                                                   autocomplete="off"
                                                                   value="<?php echo set_value('T[0]', (isset($product_variants[0]['TAMANHO'])) ? $product_variants[0]['TAMANHO'] : ""); ?>" <?= !$storeCanUpdateProduct && !$duplicated_variation ? 'readonly' : '' ?> />
                                                        </div>
                                                        <div id="Icvar" class="form-group col-md-1 "
                                                             style="padding-left: 0px;padding-right: 3px;">
                                                            <input type="text" class="form-control" id="C[]" name="C[]"
                                                                   placeholder="<?= $this->lang->line('application_color') ?>"
                                                                   autocomplete="off"
                                                                   value="<?php echo set_value('C[0]', (isset($product_variants[0]['Cor'])) ? $product_variants[0]['Cor'] : ""); ?>" <?= !$storeCanUpdateProduct && !$duplicated_variation ? 'readonly' : '' ?> />
                                                        </div>
                                                        <div id="Ivvar" class="form-group col-md-1 " style="padding-left: 0px;padding-right: 3px;">
                                                            <input type="text" class="form-control" id="V[]" name="V[]" autocomplete="off" placeholder="<?= $this->lang->line('application_voltage'); ?>" value="<?php echo set_value('V[0]', (isset($product_variants[0]['VOLTAGEM'])) ? $product_variants[0]['VOLTAGEM'] : ""); ?>" <?= !$storeCanUpdateProduct && !$duplicated_variation ? 'readonly' : '' ?> />
                                                        </div>
                                                        <?php if ($flavor_active) { ?>
                                                            <div id="Isvar" class="col-md-1"
                                                                 style="padding-left: 0px;padding-right: 3px;">
                                                                <input type="text" class="form-control" id="sb[]"
                                                                       name="sb[]"
                                                                       autocomplete="off"
                                                                       placeholder="<?= $this->lang->line('application_flavor'); ?>"
                                                                       value="<?php echo set_value('sb[0]', (isset($product_variants[0]['SABOR'])) ? $product_variants[0]['SABOR'] : ""); ?>"/>
                                                            </div>
                                                        <?php } ?>
                                                        <?php if ($degree_active) { ?>
                                                            <div id="Igvar" class="col-md-1"
                                                                 style="padding-left: 0px;padding-right: 3px;">
                                                                <input type="text" class="form-control" id="gr[]"
                                                                       name="gr[]"
                                                                       autocomplete="off"
                                                                       placeholder="<?= $this->lang->line('application_degree'); ?>"
                                                                       value="<?php echo set_value('gr[0]', (isset($product_variants[0]['GRAU'])) ? $product_variants[0]['GRAU'] : ""); ?>"/>
                                                            </div>
                                                        <?php } ?>
                                                        <?php if ($side_active) { ?>
                                                            <div id="Ilvar" class="col-md-1"
                                                                 style="padding-left: 0px;padding-right: 3px;">
                                                                <input type="text" class="form-control" id="ld[]"
                                                                       name="ld[]"
                                                                       autocomplete="off"
                                                                       placeholder="<?= $this->lang->line('application_side'); ?>"
                                                                       value="<?php echo set_value('ld[0]', (isset($product_variants[0]['LADO'])) ? $product_variants[0]['LADO'] : ""); ?>"/>
                                                            </div>
                                                        <?php } ?>
                                                        <div id="Iqvar" class="form-group col-md-1 "
                                                             style="padding-left: 0px;padding-right: 3px;">
                                                            <input type="text" class="form-control" id="Q[]" name="Q[]"
                                                                   placeholder="<?= $this->lang->line('application_stock') ?>"
                                                                   autocomplete="off"
                                                                   value="<?php echo set_value('Q[0]', (isset($product_variants[0]['qty'])) ? $product_variants[0]['qty'] : ""); ?>"
                                                                   onKeyPress="return digitos(event, this);"/>
                                                        </div>
                                                        <div id="Iskuvar" class="form-group col-md-1"
                                                             style="padding-left: 0px;padding-right: 3px;">
                                                            <div class="d-flex flex-nowrap align-items-center">
                                                                <input type="text" class="form-control" id="SKU_V_0"
                                                                       name="SKU_V[]"
                                                                       placeholder="SKU Variação" autocomplete="off"
                                                                       value="<?php echo set_value('SKU_V[0]', (isset($product_variants[0]['sku'])) ? $product_variants[0]['sku'] : ""); ?>"
                                                                       onKeyUp="checkSpecialSku(event, this);characterLimit(this);"
                                                                       onblur="checkSpecialSku(event, this);"
                                                                       maxlength="<?= $product_length_sku ?>"/>
                                                                <?php if (!empty($product_variants[0]['variant_id_erp'])): ?>
                                                                <i class="fa fa-info-circle btn-tooltip-sku-integartion" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="SKU Integração: <?=$product_variants[0]['variant_id_erp']?>"></i>
                                                                <?php endif ?>
                                                            </div>
                                                            <span id="char_SKU_V_<?= 0 ?>"></span>
                                                            <br/>
                                                        </div>
                                                        <div id="EANV0"
                                                             class="Ieanvar form-group col-md-2  <?php echo (form_error('EAN_V[0]')) ? 'has-error' : ''; ?>"
                                                             style="padding-left: 0px;padding-right: 3px;">
                                                            <input type="text" class="form-control" id="EAN_V[]"
                                                                   name="EAN_V[]"
                                                                   placeholder="EAN Variação" autocomplete="off"
                                                                   onchange="checkEAN(this.value,'EANV0','<?= $product_data['id']; ?>')"
                                                                   value="<?php echo set_value('EAN_V[0]', (isset($product_variants[0]['EAN'])) ? $product_variants[0]['EAN'] : ""); ?>"/>
                                                            <span id="EANV0erro" style="display: none;"><i
                                                                        style="color:red"><?= $this->lang->line('application_invalid_ean'); ?></i></span>
                                                            <?php echo '<i style="color:red">' . form_error('EAN_V[0]') . '</i>'; ?>
                                                        </div>
                                                        <?php if ($displayPriceByVariation == '1') { ?>
                                                            <div id="Ilistpricevar"
                                                                 class="form-group col-md-1 Ilistpricevar  <?php echo (form_error('LIST_PRICE_V[0]')) ? 'has-error' : ''; ?> "
                                                                 style="padding-left: 0px;padding-right: 3px;">
                                                                <input type="text" class="form-control maskdecimal2"
                                                                       id="LIST_PRICE_V[]" name="LIST_PRICE_V[]"
                                                                       placeholder="Preço De - Variação"
                                                                       autocomplete="off"
                                                                       value="<?php echo set_value('LIST_PRICE_V[0]', (isset($product_variants[0]['list_price'])) ? $product_variants[0]['list_price'] : ""); ?>"/>
                                                                <?php echo '<i style="color:red">' . form_error('LIST_PRICE_V[0]') . '</i>'; ?>
                                                            </div>
                                                            <div id="Ipricevar"
                                                                 class="form-group col-md-1 Ipricevar  <?php echo (form_error('PRICE_V[0]')) ? 'has-error' : ''; ?> "
                                                                 style="padding-left: 0px;padding-right: 3px;">
                                                                <input type="text" class="form-control maskdecimal2"
                                                                       id="PRICE_V[]"
                                                                       name="PRICE_V[]"
                                                                       placeholder="Preço Por - Variação"
                                                                       autocomplete="off"
                                                                       value="<?php echo set_value('PRICE_V[0]', (isset($product_variants[0]['price'])) ? $product_variants[0]['price'] : ""); ?>"/>
                                                                <?php echo '<i style="color:red">' . form_error('PRICE_V[0]') . '</i>'; ?>
                                                            </div>
                                                        <?php } ?>
                                                        <div class="col-md-1"
                                                             style="padding-left: 0px;padding-right: 3px;">
                                                            <?php
                                                            $imagem = base_url('assets/images/system/sem_foto.png');
                                                            if (isset($product_variants[0]['principal_image'])) {
                                                                $imagem = ($product_variants[0]['principal_image'] == '') ? base_url('assets/images/system/sem_foto.png') : $product_variants[0]['principal_image'];
                                                            }
                                                            $imagevariant0 = '';
                                                            $keys = array_merge(range('A', 'Z'), range('a', 'z'));

                                                            for ($w = 0; $w < 15; $w++) {
                                                                $imagevariant0 .= $keys[array_rand($keys)];
                                                            }
                                                            if (key_exists(0, $product_variants)) {
                                                                if ($product_variants[0]['image'] != '') {
                                                                    $imagevariant0 = $product_variants[0]['image'];
                                                                }
                                                            }
                                                            ?>
                                                            <input type="hidden" id="IMAGEM0" name="IMAGEM[]"
                                                                   value="<?php echo set_value('IMAGEM[0]', $imagevariant0) ?>"/>

                                                            <?php if (!$storeCanUpdateProduct && $notAdmin) { ?>
                                                                <a href="#" onclick="AddImage(event,'0')"><img
                                                                            alt="foto"
                                                                            id="foto_variant0"
                                                                            src="<?= $imagem; ?>"
                                                                            class="img-rounded"
                                                                            width="40"
                                                                            height="40"/></a>
                                                            <?php } else { ?>
                                                                <a href="#" onclick="AddImage(event,'0')"><img
                                                                            alt="foto"
                                                                            id="foto_variant0"
                                                                            src="<?= $imagem; ?>"
                                                                            class="img-rounded"
                                                                            width="40"
                                                                            height="40"/><i
                                                                            class="fa fa-plus-circle"
                                                                            style="margin-left: 6px"></i></a>
                                                            <?php } ?>

                                                        </div>

                                                    </div>
                                                    <div class="input_fields_wrap">
                                                        <?php
                                                        $duplicated_variations = [];
                                                        if ($product_variants['numvars']) {
                                                            $duplicated_variations[] = $product_variants[0]['name'];
                                                        }
                                                        for ($i = 1; $i <= $product_variants['numvars']; $i++) {
                                                            $number_status = $product_variants[$i]['status'] == 1 ? 'success' : 'danger';
                                                            $tag_variation = '';
                                                            $duplicated_variation = true;
                                                            if (in_array($product_variants[$i]['name'], $duplicated_variations)) {
                                                                $tag_variation = "<span class='label label-danger'>{$this->lang->line('application_duplicated')}</span><br/>";
                                                            } else {
                                                                $duplicated_variation = false;
                                                                $duplicated_variations[] = $product_variants[$i]['name'];
                                                            }

                                                            if (!$duplicated_variation && array_key_exists($i, $published_variations) && $published_variations[$i]) {
                                                                $duplicated_variation = false;
                                                            } else if (!$duplicated_variation) {
                                                                $duplicated_variation = true;
                                                            }
                                                            ?>
                                                            <div class="container-fluid col-person-two"
                                                                 id="variant<?php echo $i + 1; ?>">
                                                                <div id="Invar<?php echo $i + 1; ?>" class="col-md-1"
                                                                     style="padding-left: 0px;padding-right: 3px;width:auto;">
                                                                    <span class="form-control label label-<?=$number_status?>"><?php echo $i; ?></span>
                                                                </div>
                                                                <div id="Itvar" class="form-group col-md-1 "
                                                                     style="padding-left: 0px;padding-right: 3px;">
                                                                    <input type="text" class="form-control" id="T[]"
                                                                           placeholder="<?= $this->lang->line('application_size') ?>"
                                                                           name="T[]" autocomplete="off"
                                                                           value="<?php echo set_value('T[' . $i . ']', (isset($product_variants[$i]['TAMANHO'])) ? $product_variants[$i]['TAMANHO'] : ""); ?>" <?= !$storeCanUpdateProduct && !$duplicated_variation ? 'readonly' : '' ?> />
                                                                </div>
                                                                <div id="Icvar" class="form-group col-md-1 "
                                                                     style="padding-left: 0px;padding-right: 3px;">
                                                                    <input type="text" class="form-control" id="C[]"
                                                                           placeholder="<?= $this->lang->line('application_color') ?>"
                                                                           name="C[]" autocomplete="off"
                                                                           value="<?php echo set_value('C[' . $i . ']', (isset($product_variants[$i]['Cor'])) ? $product_variants[$i]['Cor'] : ""); ?>" <?= !$storeCanUpdateProduct && !$duplicated_variation ? 'readonly' : '' ?> />
                                                                </div>
                                                                <div id="Ivvar" class="form-group col-md-1 "
                                                                     style="padding-left: 0px;padding-right: 3px;">
                                                                    <input type="text" class="form-control" id="V[]" name="V[]" autocomplete="off" placeholder="<?= $this->lang->line('application_voltage'); ?>" value="<?php echo set_value('V[' . $i . ']', (isset($product_variants[$i]['VOLTAGEM'])) ? $product_variants[$i]['VOLTAGEM'] : ""); ?>" <?= !$storeCanUpdateProduct && !$duplicated_variation ? 'readonly' : '' ?> />
                                                                </div>
                                                                <?php if ($flavor_active) { ?>
                                                                    <div id="Isvar" class="col-md-1"
                                                                         style="padding-left: 0px;padding-right: 3px;" <?= ($flavor_active == '' ? 'hidden' : '') ?>>
                                                                        <input type="text" class="form-control"
                                                                               id="sb[]"
                                                                               name="sb[]" autocomplete="off"
                                                                               placeholder="<?= $this->lang->line('application_flavor'); ?>"
                                                                               value="<?php echo set_value('sb[' . $i . ']', (isset($product_variants[$i]['SABOR'])) ? $product_variants[$i]['SABOR'] : ""); ?>"/>
                                                                    </div>
                                                                <?php } ?>
                                                                <?php if ($degree_active) { ?>
                                                                    <div id="Igvar" class="col-md-1"
                                                                         style="padding-left: 0px;padding-right: 3px;" <?= ($degree_active == '' ? 'hidden' : '') ?>>
                                                                        <input type="text" class="form-control"
                                                                               id="gr[]"
                                                                               name="gr[]" autocomplete="off"
                                                                               placeholder="<?= $this->lang->line('application_degree'); ?>"
                                                                               value="<?php echo set_value('gr[' . $i . ']', (isset($product_variants[$i]['GRAU'])) ? $product_variants[$i]['GRAU'] : ""); ?>"/>
                                                                    </div>
                                                                <?php } ?>
                                                                <?php if ($side_active) { ?>
                                                                    <div id="Ilvar" class="col-md-1"
                                                                         style="padding-left: 0px;padding-right: 3px;" <?= ($side_active == '' ? 'hidden' : '') ?>>
                                                                        <input type="text" class="form-control"
                                                                               id="ld[]"
                                                                               name="ld[]" autocomplete="off"
                                                                               placeholder="<?= $this->lang->line('application_side'); ?>"
                                                                               value="<?php echo set_value('ld[' . $i . ']', (isset($product_variants[$i]['LADO'])) ? $product_variants[$i]['LADO'] : ""); ?>"/>
                                                                    </div>
                                                                <?php } ?>
                                                                <div id="Iqvar" class="form-group col-md-1"
                                                                     style="padding-left: 0px;padding-right: 3px;">
                                                                    <input type="text" class="form-control" id="Q[]"
                                                                           name="Q[]"
                                                                           placeholder="<?= $this->lang->line('application_stock') ?>"
                                                                           autocomplete="off"
                                                                           value="<?php echo set_value('Q[' . $i . ']', (isset($product_variants[$i]['qty'])) ? $product_variants[$i]['qty'] : ""); ?>"
                                                                           onKeyPress="return digitos(event, this);"/>
                                                                </div>
                                                                <div id="Iskuvar" class="form-group col-md-1"
                                                                     style="padding-left: 0px;padding-right: 3px;">
                                                                    <div class="d-flex flex-nowrap align-items-center">
                                                                        <input type="text" class="form-control"
                                                                               id="SKU_V_<?= $i ?>"
                                                                               name="SKU_V[]" placeholder="SKU Variação"
                                                                               autocomplete="off"
                                                                               value="<?php echo set_value('SKU_V[' . $i . ']', (isset($product_variants[$i]['sku'])) ? $product_variants[$i]['sku'] : ""); ?>"
                                                                               onKeyUp="checkSpecialSku(event, this);characterLimit(this);"
                                                                               onblur="checkSpecialSku(event, this);"
                                                                               maxlength="<?= $product_length_sku ?>"/>
                                                                        <?php if (!empty($product_variants[$i]['variant_id_erp'])): ?>
                                                                        <i class="fa fa-info-circle btn-tooltip-sku-integartion" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="SKU Integração: <?=$product_variants[$i]['variant_id_erp']?>"></i>
                                                                        <?php endif ?>
                                                                    </div>
                                                                    <span id="char_SKU_V_<?= $i ?>"></span><?=$tag_variation?>
                                                                    <br/>
                                                                </div>
                                                                <div id="EANV<?php echo $i; ?>"
                                                                     class="Ieanvar form-group col-md-2"
                                                                     style="padding-left: 0px;padding-right: 3px;">
                                                                    <input type="text" class="form-control" id="EAN_V[]"
                                                                           onKeyPress="return digitos(event, this);"
                                                                           name="EAN_V[]"
                                                                           placeholder="EAN Variação" autocomplete="off"
                                                                           onchange="checkEAN(this.value,'EANV<?php echo $i; ?>','<?= $product_data['id']; ?>')"
                                                                           value="<?php echo set_value('EAN_V[' . $i . ']', (isset($product_variants[$i]['EAN'])) ? $product_variants[$i]['EAN'] : ""); ?>"/>
                                                                    <span id="EANV<?php echo $i; ?>erro"
                                                                          style="display: none;"><i
                                                                                style="color:red"><?= $invalid_ean; ?></i></span>
                                                                    <?php echo '<i style="color:red">' . form_error('EAN_V[' . $i . ']') . '</i>'; ?>
                                                                </div>
                                                                <?php if ($displayPriceByVariation == '1') { ?>
                                                                    <div id="Ilistpricevar" class="form-group col-md-1 "
                                                                         style="padding-left: 0px;padding-right: 3px;">
                                                                        <input type="text"
                                                                               class="form-control maskdecimal2"
                                                                               id="LIST_PRICE_V[]" name="LIST_PRICE_V[]"
                                                                               placeholder="Preço De - Variação"
                                                                               autocomplete="off"
                                                                               value="<?php echo set_value('LIST_PRICE_V[' . $i . ']', (isset($product_variants[$i]['list_price'])) ? $product_variants[$i]['list_price'] : ""); ?>"/>
                                                                    </div>
                                                                    <div id="Ipricevar" class="form-group col-md-1 "
                                                                         style="padding-left: 0px;padding-right: 3px;">
                                                                        <input type="text"
                                                                               class="form-control maskdecimal2"
                                                                               id="PRICE_V[]" name="PRICE_V[]"
                                                                               placeholder="Preço Por - Variação"
                                                                               autocomplete="off"
                                                                               value="<?php echo set_value('PRICE_V[' . $i . ']', (isset($product_variants[$i]['price'])) ? $product_variants[$i]['price'] : ""); ?>"/>
                                                                    </div>
                                                                <?php } ?>
                                                                <div class="col-md-1 "
                                                                     style="padding-left: 0px;padding-right: 3px;width:auto;">

                                                                    <?php
                                                                    $imagem = base_url('assets/images/system/sem_foto.png');
                                                                    if (isset($product_variants[$i]['principal_image'])) {
                                                                        $imagem = ($product_variants[$i]['principal_image'] == '') ? base_url('assets/images/system/sem_foto.png') : $product_variants[0]['principal_image'];
                                                                    }
                                                                    if ($product_variants[$i]['image'] == '') {
                                                                        $keys = array_merge(range('A', 'Z'), range('a', 'z'));

                                                                        for ($w = 0; $w < 15; $w++) {
                                                                            $product_variants[$i]['image'] .= $keys[array_rand($keys)];
                                                                        }
                                                                    }
                                                                    ?>
                                                                    <input type="hidden" id="IMAGEM<?php echo $i; ?>"
                                                                           name="IMAGEM[]"
                                                                           value="<?php echo set_value('IMAGEM[<?php echo $i;?>]', $product_variants[$i]['image']) ?>"/>
                                                                   <?php if (!$storeCanUpdateProduct && $notAdmin) { ?>
                                                                        <a href="#"
                                                                           onclick="AddImage(event,'<?php echo $i; ?>')"><img
                                                                                    alt="foto"
                                                                                    id="foto_variant<?php echo $i; ?>"
                                                                                    src="<?= $imagem; ?>"
                                                                                    class="img-rounded"
                                                                                    width="40" height="40"/></a>
                                                                    <?php } else { ?>
                                                                        <a href="#"
                                                                           onclick="AddImage(event,'<?php echo $i; ?>')"><img
                                                                                    alt="foto"
                                                                                    id="foto_variant<?php echo $i; ?>"
                                                                                    src="<?= $imagem; ?>"
                                                                                    class="img-rounded"
                                                                                    width="40" height="40"/><i
                                                                                    class="fa fa-plus-circle"
                                                                                    style="margin-left: 6px"></i></a>
                                                                    <?php }
                                                                    ?>
                                                                </div>

                                                            </div>

                                                        <?php } ?>
                                                    </div>
                                                    <?php echo '<i style="color:red">' . form_error('SKU_V[]') . '</i>'; ?>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <button type="button" class="btn btn-primary add_line"><i
                                                                class="fa fa-plus-square-o"></i> <?= $this->lang->line('application_variation_add'); ?>
                                                    </button>
                                                    <?php if ($storeCanUpdateProduct) : ?>
                                                    <button type="button" class="btn btn-danger" id="reset_variant"
                                                            name="reset_variant"><i
                                                                class="fa fa-trash"></i> <?= $this->lang->line('application_clear'); ?>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="box">
                                        <div class="box-body">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h2><?= lang('application_categories'); ?>(*)</h2>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="form-group col-md-12 col-xs-12">
                                                    <?php
                                                    $category_data = json_decode($product_data['category_id']);
                                                    if (!isset($category_data)) {
                                                        $category_data = array();
                                                    }
                                                    ?>
                                                    <input type='hidden' id='name' name="name" value="">
                                                    <input type='hidden' id='id_categoria' name="category[]"
                                                           value="<?php echo $idCategory_id; ?>">

                                                    <?php if((in_array('disabledCategoryPermission', $this->permission) == true)){ ?>
                                                        <span>A categorização dos produtos é realizada pelo marketplace</span>
                                                    <?php } ?>
                                                    <select class="form-control"
                                                            data-live-search="true"
                                                            data-actions-box="true" id='category'
                                                            custo='prazo_operacional_extra'
                                                            title="<?= $this->lang->line('application_select'); ?>" <?= (in_array('disabledCategoryPermission', $this->permission) == true) ? 'disabled readonly' : '' ?>>
                                                        <option value=""><?= $this->lang->line('application_select'); ?></option>
                                                        <?php foreach ($category as $k => $v) {
                                                            $disabledCategory = (!$storeCanUpdateProduct && (!in_array($v['id'], $category_data)) && $notAdmin) ? 'disabled' : '';
                                                            $blocked_cross_docking = $v['blocked_cross_docking'];
                                                            $days_cross_docking = $v['days_cross_docking']; ?>
                                                            <option value="<?php echo $v['id'] . '-' . $blocked_cross_docking . '-' . $days_cross_docking . '-' . $v['name'] ?>" <?= $disabledCategory ?> <?php echo set_select('category', $v['id'], in_array($v['id'], $category_data)); ?>>   <?php echo $v['name'] ?><?php echo ($v['blocked_cross_docking'] == 1) ? '( Com Bloqueio de Prazo de ' . $days_cross_docking . ' dias )' : '' ?></option>
                                                        <?php } ?>
                                                    </select>
                                                    <?php
                                                    if ($product_data['category_imported'] != "") {
                                                        echo "<small><strong>{$this->lang->line('application_imported_category')}</strong>: {$product_data['category_imported']}</small>";
                                                    } ?>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12 col-xs-12">
                                                    <div id='linkcategory'></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="box">
                                        <div class="box-body">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h2 class="<?php echo (form_error('description')) ? 'has-error' : ''; ?>">
                                                        <?= lang('application_description'); ?>(*)
                                                    </h2>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="form-group col-md-12 col-xs-12 <?php echo (form_error('description')) ? 'has-error' : ''; ?>">
                                                    <textarea
                                                            type="text" <?= (!$storeCanUpdateProduct && $notAdmin ? 'readonly' : '') ?> class="form-control"
                                                            id="description"
                                                            maxlength="<?= $product_length_description ?>"
                                                            name="description"
                                                            placeholder="<?= $this->lang->line('application_enter_description'); ?>"><?php echo $product_data['description']; ?></textarea>
                                                    <span id="char_description"></span><br/>
                                                    <span class="label label-warning" id="words_description"
                                                          data-toggle="tooltip"
                                                          data-placement="top"
                                                          title="<?= $this->lang->line('application_explanation_of_forbidden_words'); ?>"></span>
                                                    <?php echo '<i style="color:red">' . form_error('description') . '</i>'; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="box">
                                        <div class="box-body">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h2>
                                                        <?= lang('application_prices_and_stock') ?>
                                                    </h2>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <?php if (!isset($promotion) && count($campaigns) == 0) { ?>
                                                    <div class="form-group col-lg-3 col-md-4 col-xs-12">
                                                        <label for="price"
                                                               class="d-flex justify-content-between"><?= $this->lang->line('application_list_price'); ?>
                                                        </label>
                                                        <div class="input-group">
                                                            <span class="input-group-addon"><strong>R$</strong></span>
                                                            <input type="text" class="form-control maskdecimal2"
                                                                   id="list_price"
                                                                   name="list_price"
                                                                   placeholder="<?= $this->lang->line('application_enter_price'); ?>"
                                                                   value="<?php echo set_value('list_price', $product_data['list_price']); ?>"
                                                                   autocomplete="off"/>
                                                        </div>
                                                    </div>
                                                    <div class="form-group col-lg-3 col-md-4 col-xs-12">

                                                        <label for="price" class="d-flex justify-content-between">
                                                            <?= $this->lang->line('application_new_price'); ?>(*)

                                                            <?php if ($displayPriceByVariation == '1') { ?>
                                                                <i style="color:red;" class="fa fa-info-circle"
                                                                   aria-hidden="true"
                                                                   data-toggle="tooltip" data-placement="top"
                                                                   title="<?= $this->lang->line('messages_price_variations_price_product'); ?>"></i>
                                                            <?php } ?>

                                                            <?php if (($product_data['status'] == 1) && ($product_data['situacao'] == 2)) { ?>
                                                                <a href="<?php echo base_url('promotions/createpromo/' . $product_data['id']) ?>"><i
                                                                            class="fa fa-plus-circle"> <?= $this->lang->line('application_add_promotion') ?></i></a>
                                                            <?php } ?>

                                                        </label>
                                                        <div class="input-group">
                                                            <span class="input-group-addon"><strong>R$</strong></span>
                                                            <input type="text" class="form-control maskdecimal2"
                                                                   id="price" name="price"
                                                                   required
                                                                   placeholder="<?= $this->lang->line('application_enter_price'); ?>"
                                                                   value="<?php echo set_value('price', number_format($product_data['price'], 2)); ?>"
                                                                   autocomplete="off"/>
                                                        </div>
                                                        <?php if ($product_data['competitiveness']) { ?>
                                                            <span class="label label-danger" data-toggle="tooltip"
                                                                  title="<?= $this->lang->line('application_reduce_product_value') ?>">Produto <?= number_format($product_data['competitiveness'], 2, ',', '.') ?>% acima do valor de mercado</span>
                                                        <?php } ?>
                                                        <div class="input-group">
                                                            <?php

                                                            if (count($products_marketplace) > 0) {
                                                                ?>
                                                                <table class="table table-striped table-hover responsive display table-condensed">
                                                                    <thead>
                                                                    <tr>
                                                                        <th style="width: 40%"><?= $this->lang->line('application_marketplace'); ?></th>
                                                                        <th><?= $this->lang->line('application_price_marketplace'); ?></th>
                                                                    </tr>

                                                                    <?php
                                                                    foreach ($products_marketplace as $product_marketplace) {
                                                                        // if ($product_marketplace['hub'] || ($product_marketplace['variant'] == '0') || ($product_marketplace['variant'] == ''))
                                                                        if (($product_marketplace['variant'] == '0') || ($product_marketplace['variant'] == '')) {
                                                                            $disablePrice = '';
                                                                            if ($product_marketplace['same_price'] == 1) {
                                                                                $disablePrice = ' disabled ';
                                                                            }
                                                                            ?>
                                                                            <tr>
                                                                                <td style="width: 40%"><?php echo $product_marketplace['int_to']; ?>
                                                                                    <br>
                                                                                    <input type="checkbox"
                                                                                           name="samePrice_<?= $product_marketplace['int_to'] ?>" <?= set_checkbox('samePrice_' . $product_marketplace['int_to'], 'on', $product_marketplace['same_price'] == 1) ?>
                                                                                           id="samePrice_<?= $product_marketplace['int_to'] ?>"
                                                                                           onchange="samePrice('<?= $product_marketplace['int_to'] ?>')">
                                                                                    <small><?= $this->lang->line('application_same_price'); ?></small>
                                                                                </td>
                                                                                <td>
                                                                                    <div class="input-group">
                                                                                        <span class="input-group-addon"><small><strong>R$</strong></small></span>
                                                                                        <input type="text" <?= $disablePrice ?>
                                                                                               class="form-control maskdecimal2"
                                                                                               id="price_<?= $product_marketplace['int_to'] ?>"
                                                                                               name="price_<?= $product_marketplace['int_to'] ?>"
                                                                                               required
                                                                                               placeholder="<?= $this->lang->line('application_enter_price'); ?>"
                                                                                               value="<?php echo set_value('price_' . $product_marketplace['int_to'], $product_marketplace['price']); ?>"
                                                                                               autocomplete="off"/>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                            <?php
                                                                        }
                                                                    }
                                                                    ?>
                                                                    </thead>
                                                                </table>
                                                                <?php
                                                            }
                                                            ?>
                                                        </div>

                                                    </div>
                                                <?php } else {
                                                    if (isset($promotion)) {
                                                        $buttons = '';
                                                        if ((in_array('updatePromotions', $this->permission)) && (($promotion['active'] == 3) || ($promotion['active'] == 4))) { // posso editar se está agendada ou em aprovação
                                                            $buttons .= '<a href="' . base_url('promotions/update/' . $promotion['id']) . '" class="btn btn-default" data-toggle="tooltip" title="' . $this->lang->line('application_edit') . '"><i class="fa fa-pencil-square-o"></i></a>';
                                                        }
                                                        if ((in_array('deletePromotions', $this->permission)) && ($promotion['active'] == 1)) { // Posso inativar se estiver ativo
                                                            $buttons .= '<button class="btn btn-danger" onclick="inactivePromotion(event,' . $promotion['id'] . ')" data-toggle="tooltip" title="' . $this->lang->line('application_inactivate') . '"><i class="fa fa-minus-square"></i></button>';
                                                        }
                                                        if ((in_array('updatePromotions', $this->permission)) && ($promotion['active'] == 3)) {  // Posso aprovar se está em aprovação
                                                            $buttons .= '<button class="btn btn-success" onclick="approvePromotion(event,' . $promotion['id'] . ')" data-toggle="tooltip" title="' . $this->lang->line('application_approve') . '"><i class="fa fa-check"></i></button>';
                                                        }
                                                        if ((in_array('deletePromotions', $this->permission)) && (($promotion['active'] == 3) || ($promotion['active'] == 4))) { // posso deletar se está em aprovação ou agendado
                                                            $buttons .= '<button class="btn btn-warning" onclick="deletePromotion(event,' . $promotion['id'] . ')" data-toggle="tooltip" title="' . $this->lang->line('application_delete') . '"><i class="fa fa-trash"></i></button>';
                                                        }

                                                        if ($promotion['type'] == 2) {
                                                            $msg = $this->lang->line('application_msg_product_promotion_1');
                                                            $msg = sprintf($msg, get_instance()->formatprice($product_data['price']), get_instance()->formatprice($promotion['price']), date('d/m/Y', strtotime($promotion['start_date'])), date('d/m/Y', strtotime($promotion['end_date'])));
                                                        } else {
                                                            $msg = $this->lang->line('application_msg_product_promotion_2');
                                                            $msg = sprintf($msg, get_instance()->formatprice($product_data['price']), get_instance()->formatprice($promotion['price']), date('d/m/Y', strtotime($promotion['start_date'])), date('d/m/Y', strtotime($promotion['end_date'])), $promotion['qty'], $promotion['qty_used']);
                                                        }
                                                        ?>
                                                        <div style="background-color: green; color:white; "
                                                             class="form-group col-md-12 col-xs-12">
                                                            <h5>
                                                                <span style="word-break: break-word; ">&nbsp;<?php echo $msg; ?></span>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $buttons; ?>
                                                            </h5>
                                                        </div>
                                                        <input type="hidden" id="price" name="price"
                                                               value="<?php echo $product_data['price']; ?>"/>

                                                    <?php } elseif (count($campaigns) > 0) {
                                                        foreach ($campaigns as $campaign) {
                                                            $msg = $this->lang->line('application_msg_product_campaign');
                                                            $msg = sprintf($msg, $campaign['name'], $campaign['marketplace'], date('d/m/Y', strtotime($campaign['start_date'])), date('d/m/Y', strtotime($campaign['end_date'])), get_instance()->formatprice($product_data['price']), get_instance()->formatprice($campaign['sale']));
                                                            ?>
                                                            <div style="background-color: green; color:white; "
                                                                 class="form-group col-md-12 col-xs-12">
                                                                <h5><span style="word-break: break-word; ">&nbsp;<?php echo $msg; ?>
                                                                </h5>
                                                            </div>
                                                        <?php } ?>
                                                        <input type="hidden" id="price" name="price"
                                                               value="<?php echo $product_data['price']; ?>"/>
                                                    <?php } ?>

                                                <?php } ?>
                                                <div class="form-group col-lg-2 col-md-3 col-xs-12">
                                                    <label for="qty"><?= $this->lang->line('application_qty'); ?>
                                                        (*)</label>
                                                    <input type="text" class="form-control" id="qty" name="qty" required
                                                           placeholder="<?= $this->lang->line('application_enter_qty'); ?>"
                                                           value="<?php echo set_value('qty', $product_data['qty']); ?>"
                                                           autocomplete="off"
                                                           onKeyPress="return digitos(event, this);"/>

                                                    <div class="input-group">
                                                        <?php
                                                        $temHub = false;
                                                        foreach ($products_marketplace as $product_marketplace) {
                                                            if ($product_marketplace['hub']) {
                                                                // if (($product_marketplace['hub']) && ($product_marketplace['variant'] == '' || $product_marketplace['variant'] == '0')) {
                                                                $temHub = true;
                                                                break;
                                                            }
                                                        }
                                                        $temHub = false; // desligado por enquanto
                                                        if ($temHub) {
                                                            ?>
                                                            <table class="table table-striped table-hover responsive display table-condensed">
                                                                <thead>
                                                                <tr>
                                                                    <th style="width: 40%"><?= $this->lang->line('application_marketplace'); ?></th>
                                                                    <th style="width: 20%">Var</th>
                                                                    <th><?= $this->lang->line('application_price_marketplace'); ?></th>
                                                                </tr>

                                                                <?php
                                                                foreach ($products_marketplace as $product_marketplace) {
                                                                    //if ($product_marketplace['hub'] && ($product_marketplace['variant'] == '' || $product_marketplace['variant'] == '0')) {
                                                                    if ($product_marketplace['hub']) {
                                                                        $disableQty = '';
                                                                        if ($product_marketplace['same_qty'] == 1) {
                                                                            $disableQty = ' disabled ';
                                                                        }
                                                                        ?>
                                                                        <tr>
                                                                            <td style="width: 40%"><?php echo $product_marketplace['int_to']; ?>
                                                                                <br>
                                                                                <input type="checkbox"
                                                                                       name="sameQty_<?= $product_marketplace['int_to'] ?>" <?= set_checkbox('sameQty_' . $product_marketplace['int_to'], 'on', $product_marketplace['same_qty'] == 1) ?>
                                                                                       id="sameQty_<?= $product_marketplace['int_to'] ?>"
                                                                                       onchange="sameQty('<?= $product_marketplace['int_to'] ?>')">
                                                                                <small><?= $this->lang->line('application_same_qty'); ?></small>
                                                                            </td>
                                                                            <td>
                                                                                <small><?= $product_marketplace['variant'] ?></small>
                                                                            </td>
                                                                            <td>
                                                                                <input type="text" <?= $disableQty ?>
                                                                                       class="form-control"
                                                                                       id="qty_<?= $product_marketplace['int_to'] ?>"
                                                                                       onKeyPress="return digitos(event, this)"
                                                                                       onchange="changeQtyMkt(<?= $product_marketplace['int_to'] ?>)"
                                                                                       name="qty_<?= $product_marketplace['int_to'] ?>"
                                                                                       required
                                                                                       placeholder="<?= $this->lang->line('application_enter_qty'); ?>"
                                                                                       value="<?php echo set_value('qty_' . $product_marketplace['int_to'], $product_marketplace['qty']); ?>"
                                                                                       autocomplete="off"/>
                                                                            </td>
                                                                        </tr>
                                                                        <?php
                                                                    }
                                                                }
                                                                ?>
                                                                </thead>
                                                            </table>
                                                            <?php
                                                        }
                                                        ?>
                                                    </div>

                                                </div>

                                                <div id="EANDIV"
                                                     class="form-group col-lg-4 col-md-5 col-xs-12 <?php echo (form_error('EAN')) ? 'has-error' : ''; ?>">
                                                    <label for="EAN"><?= $this->lang->line('application_ean'); ?><?= ($require_ean) ? "*" : "" ?></label>
                                                    <input type="text" <?= (!$storeCanUpdateProduct && $notAdmin ? 'readonly' : '') ?> <?= ($require_ean) ? "required" : "" ?>
                                                           class="form-control" id="EAN" name="EAN"
                                                           onchange="checkEAN(this.value,'EANDIV','<?= $product_data['id']; ?>')"
                                                           placeholder="<?= $this->lang->line('application_enter_ean'); ?>"
                                                           value="<?php echo set_value('EAN', $product_data['EAN']); ?>"
                                                           autocomplete="off"/>
                                                    <?php echo '<i style="color:red">' . form_error('EAN') . '</i>'; ?>
                                                    <div id="EANDIVerro" style="display: none;"><i
                                                                style="color:red"><?= $invalid_ean; ?></i></div>
                                                </div>
                                                <div class="form-group col-lg-4 col-md-5 col-xs-12">
                                                    <label for="codigo_do_fabricante"><?= $this->lang->line('application_brandcode'); ?></label>
                                                    <input type="text" <?= (!$storeCanUpdateProduct && $notAdmin ? 'readonly' : '') ?>
                                                           class="form-control" id="codigo_do_fabricante"
                                                           name="codigo_do_fabricante"
                                                           placeholder="<?= $this->lang->line('application_enter_manufacturer_code'); ?>"
                                                           value="<?php echo set_value('codigo_do_fabricante', $product_data['codigo_do_fabricante']); ?>"
                                                           autocomplete="off"/>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="box" style="min-height: 265px;">
                                        <div class="box-body">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h2><?= lang('application_product_dimensions'); ?></h2>
                                                    <h5><?= lang('application_out_of_the_package'); ?></h5>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="form-group col-lg-3 col-md-5 col-sm-6 col-xs-12">
                                                    <label for="peso_liquido"><?= $this->lang->line('application_net_weight'); ?>
                                                        (*)</label>
                                                    <div class="input-group">
                                                        <input type="text" <?= (!$storeCanUpdateProduct && $notAdmin ? 'readonly' : '') ?>
                                                               class="form-control maskdecimal3" id="peso_liquido"
                                                               name="peso_liquido" required
                                                               placeholder="<?= $this->lang->line('application_enter_net_weight'); ?>"
                                                               value="<?php echo set_value('peso_liquido', $product_data['peso_liquido']); ?>"
                                                               autocomplete="off"/>
                                                        <span class="input-group-addon"><strong>Kg</strong></span>
                                                    </div>
                                                </div>
                                                <div class="form-group col-lg-3 col-md-5 col-sm-6 col-xs-12 <?php echo (form_error('actual_width')) ? 'has-error' : ''; ?>">
                                                    <label for="actual_width"><?= $this->lang->line('application_width'); ?></label>
                                                    <div class="input-group">
                                                        <input type="text" <?= (!$storeCanUpdateProduct && $notAdmin ? 'readonly' : '') ?>
                                                               class="form-control maskdecimal2" id="actual_width"
                                                               name="actual_width"
                                                               placeholder="<?= $this->lang->line('application_enter_actual_width'); ?>"
                                                               value="<?php echo set_value('actual_width', $product_data['actual_width']) ?>"
                                                               autocomplete="off"
                                                               onKeyPress="return digitos(event, this);"/>
                                                        <span class="input-group-addon"><strong>cm</strong></span>
                                                    </div>
                                                    <?php echo '<i style="color:red">' . form_error('actual_width') . '</i>'; ?>
                                                </div>
                                                <div class="form-group col-lg-3 col-md-5 col-sm-6 col-xs-12 <?php echo (form_error('actual_height')) ? 'has-error' : ''; ?>">
                                                    <label for="actual_height"><?= $this->lang->line('application_height'); ?></label>
                                                    <div class="input-group">
                                                        <input type="text" <?= (!$storeCanUpdateProduct && $notAdmin ? 'readonly' : '') ?>
                                                               class="form-control maskdecimal2" id="actual_height"
                                                               name="actual_height"
                                                               placeholder="<?= $this->lang->line('application_enter_actual_height'); ?>"
                                                               value="<?php echo set_value('actual_height', $product_data['actual_height']) ?>"
                                                               autocomplete="off"
                                                               onKeyPress="return digitos(event, this);"/>
                                                        <span class="input-group-addon"><strong>cm</strong></span>
                                                    </div>
                                                    <?php echo '<i style="color:red">' . form_error('actual_height') . '</i>'; ?>
                                                </div>
                                                <div class="form-group col-lg-3 col-md-5 col-sm-6 col-xs-12 <?php echo (form_error('actual_depth')) ? 'has-error' : ''; ?>">
                                                    <label for="actual_depth"><?= $this->lang->line('application_depth'); ?></label>
                                                    <div class="input-group">
                                                        <input type="text" <?= (!$storeCanUpdateProduct && $notAdmin ? 'readonly' : '') ?>
                                                               class="form-control maskdecimal2" id="actual_depth"
                                                               name="actual_depth"
                                                               placeholder="<?= $this->lang->line('application_enter_actual_depth'); ?>"
                                                               value="<?php echo set_value('actual_depth', $product_data['actual_depth']) ?>"
                                                               autocomplete="off"
                                                               onKeyPress="return digitos(event, this);"/>
                                                        <span class="input-group-addon"><strong>cm</strong></span>
                                                    </div>
                                                    <?php echo '<i style="color:red">' . form_error('actual_depth') . '</i>'; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="box">
                                        <div class="box-body">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h2><?= lang('application_packaged_product_dimensions'); ?></h2>
                                                    <h5><?= lang('application_packaged_product_dimensions_explain'); ?></h5>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="form-group col-lg-3 col-md-5 col-sm-6 col-xs-12">
                                                    <label for="peso_bruto"><?= $this->lang->line('application_weight'); ?>
                                                        (*)</label>
                                                    <div class="input-group">
                                                        <input type="text" <?= (!$storeCanUpdateProduct && $notAdmin ? 'readonly' : '') ?>
                                                               class="form-control maskdecimal3" id="peso_bruto"
                                                               name="peso_bruto" required
                                                               placeholder="<?= $this->lang->line('application_enter_gross_weight'); ?>"
                                                               value="<?php echo set_value('peso_bruto', $product_data['peso_bruto']); ?>"
                                                               autocomplete="off"/>
                                                        <span class="input-group-addon"><strong>Kg</strong></span>
                                                    </div>
                                                </div>
                                                <div class="form-group col-lg-3 col-md-5 col-sm-6 col-xs-12 <?php echo (form_error('largura')) ? 'has-error' : ''; ?>">
                                                    <label for="largura"><?= $this->lang->line('application_width'); ?>
                                                        (*)</label>
                                                    <div class="input-group">
                                                        <input type="text" <?= (!$storeCanUpdateProduct && $notAdmin ? 'readonly' : '') ?>
                                                               class="form-control maskdecimal2" id="largura"
                                                               name="largura"
                                                               required
                                                               placeholder="<?= $this->lang->line('application_enter_width'); ?>"
                                                               value="<?php echo set_value('largura', $product_data['largura']); ?>"
                                                               autocomplete="off"
                                                               onKeyPress="return digitos(event, this);"/>
                                                        <span class="input-group-addon"><strong>cm</strong></span>
                                                    </div>
                                                    <?php echo '<i style="color:red">' . form_error('largura') . '</i>'; ?>
                                                </div>
                                                <div class="form-group col-lg-3 col-md-5 col-sm-6 col-xs-12 <?php echo (form_error('altura')) ? 'has-error' : ''; ?>">
                                                    <label for="altura"><?= $this->lang->line('application_height'); ?>
                                                        (*)</label>
                                                    <div class="input-group">
                                                        <input type="text" <?= (!$storeCanUpdateProduct && $notAdmin ? 'readonly' : '') ?>
                                                               class="form-control maskdecimal2" id="altura"
                                                               name="altura"
                                                               required
                                                               placeholder="<?= $this->lang->line('application_enter_height'); ?>"
                                                               value="<?php echo set_value('altura', $product_data['altura']); ?>"
                                                               autocomplete="off"
                                                               onKeyPress="return digitos(event, this);"/>
                                                        <span class="input-group-addon"><strong>cm</strong></span>
                                                    </div>
                                                    <?php echo '<i style="color:red">' . form_error('altura') . '</i>'; ?>
                                                </div>
                                                <div class="form-group col-lg-3 col-md-5 col-sm-6 col-xs-12 <?php echo (form_error('profundidade')) ? 'has-error' : ''; ?>">
                                                    <label for="profundidade"><?= $this->lang->line('application_depth'); ?>
                                                        (*)</label>
                                                    <div class="input-group">
                                                        <input type="text" <?= (!$storeCanUpdateProduct && $notAdmin ? 'readonly' : '') ?>
                                                               class="form-control maskdecimal2" id="profundidade"
                                                               name="profundidade" required
                                                               placeholder="<?= $this->lang->line('application_enter_depth'); ?>"
                                                               value="<?php echo set_value('profundidade', $product_data['profundidade']); ?>"
                                                               autocomplete="off"
                                                               onKeyPress="return digitos(event, this);"/>
                                                        <span class="input-group-addon"><strong>cm</strong></span>
                                                    </div>
                                                    <?php echo '<i style="color:red">' . form_error('profundidade') . '</i>'; ?>
                                                </div>
                                                <div class="form-group col-lg-3 col-md-5 col-sm-6 col-xs-12 <?php echo (form_error('products_package')) ? 'has-error' : ''; ?>">
                                                    <label for="products_package" data-toggle="tooltip"
                                                           data-placement="top"
                                                           title="<?= $this->lang->line('application_how_many_units'); ?>"><?= $this->lang->line('application_products_by_packaging'); ?>
                                                        (*)</label>
                                                    <div class="input-group">
                                                        <input type="text" <?= (!$storeCanUpdateProduct && $notAdmin ? 'readonly' : '') ?>
                                                               class="form-control maskdecimal3" id="products_package"
                                                               name="products_package" required
                                                               placeholder="<?= $this->lang->line('application_enter_quantity_products'); ?>"
                                                               value="<?php echo set_value('products_package', $product_data['products_package']) ?>"
                                                               autocomplete="off"/>
                                                        <span class="input-group-addon"><strong>Qtd</strong></span>
                                                    </div>
                                                    <?php echo '<i style="color:red">' . form_error('products_package') . '</i>'; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="box">
                                        <div class="box-body">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h2>
                                                        <?= lang('application_additional_information') ?>
                                                    </h2>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="row">
                                                        <?php if (!isset($brand_data)) {
                                                            $brand_data = array();
                                                        } ?>
                                                        <div class="form-group col-lg-4 col-md-5 col-xs-12">
                                                            <label for="brands" class="d-flex justify-content-between">
                                                                <?= $this->lang->line('application_brands'); ?>(*)
                                                                <?php if (!$disableBrandCreationbySeller) { ?>
                                                                    <a href="#" onclick="AddBrand(event)"><i
                                                                                class="fa fa-plus-circle"></i> <?= $this->lang->line('application_add_brand'); ?>
                                                                    </a>
                                                                <?php } ?>
                                                            </label>
                                                            <?php
                                                            $brand_data = json_decode($product_data['brand_id']);
                                                            if (!isset($brand_data)) {
                                                                $brand_data = array();
                                                            }
                                                            ?>
                                                            <select class="form-control selectpicker show-tick"
                                                                    data-live-search="true"
                                                                    data-actions-box="true" id="brands" name="brands[]"
                                                                    title="<?= $this->lang->line('application_select'); ?>">
                                                                <option value=""><?= $this->lang->line('application_select'); ?></option>
                                                                <?php foreach ($brands as $k => $v) {
                                                                    $disabledBrands = (!$storeCanUpdateProduct && (!in_array($v['id'], $brand_data)) && $notAdmin) ? 'disabled' : ''; ?>
                                                                    <option value="<?php echo $v['id'] ?>" <?= $disabledBrands ?> <?php echo set_select('brands', $v['id'], in_array($v['id'], $brand_data)) ?>><?php echo $v['name'] ?></option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>
                                                        <div class="form-group col-lg-4 col-md-5 col-xs-12">
                                                            <label for="store"><?= $this->lang->line('application_store'); ?>
                                                                (*)</label>
                                                            <?php
                                                            $nome_loja = '';
                                                            foreach ($stores as $store) {
                                                                if ($store['id'] == $product_data['store_id']) {
                                                                    $nome_loja = $store['name'];
                                                                    break;
                                                                }
                                                            }
                                                            ?>
                                                            <input type="hidden" class="form-control" id="store"
                                                                   name="store"
                                                                   value="<?php echo $product_data['store_id']; ?>"/>
                                                            <span class="form-control"><?php echo $nome_loja ?></span>
                                                        </div>
                                                        <div class="form-group col-lg-4 col-md-8 col-xs-12">
                                                            <label for="origin"><?= $this->lang->line('application_origin_product'); ?>
                                                                (*)</label>
                                                            <select class="form-control" name="origin" id="origin"
                                                                    required>
                                                                <?php foreach ($origins as $key => $origin) { ?>
                                                                    <option value="<?= $key ?>" <?= set_select('origin', $key, $product_data['origin'] == $key) ?>><?= $origin ?></option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <?php $attribute_id = json_decode($product_data['attribute_value_id']);
                                                if (is_null($attribute_id)) {
                                                    $attribute_id = array("[]");
                                                }
                                                ?>
                                                <?php if ($attributes) : ?>
                                                    <?php foreach ($attributes as $k => $v) : ?>
                                                        <div class="form-group col-lg-4 col-md-5 col-xs-12">
                                                            <label for="groups"><?php echo $v['attribute_data']['name'] ?>
                                                                (*)</label>
                                                            <select class="form-control select_group" style="width:80%"
                                                                    id="attributes_value_id"
                                                                    name="attributes_value_id[]">
                                                                <?php foreach ($v['attribute_value'] as $k2 => $v2) {
                                                                    $disabledAttributes = (!$storeCanUpdateProduct && (!in_array($v2['id'], $attribute_id))) ? 'disabled' : ''; ?>
                                                                    <option value="<?php echo $v2['id'] ?>" <?= $disabledAttributes ?> <?php echo set_select('attributes_value_id', $v2['id'], in_array($v2['id'], $attribute_id)) ?>><?php echo $v2['value'] ?></option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>
                                                    <?php endforeach ?>
                                                <?php endif; ?>
                                                <div class="form-group col-lg-2 col-md-2 col-xs-12">
                                                    <label for="NCM"><?= $this->lang->line('application_NCM'); ?></label>
                                                    <input type="text" class="form-control" id="NCM" name="NCM"
                                                           placeholder="<?= $this->lang->line('application_enter_NCM'); ?>"
                                                           value="<?php echo set_value('NCM', $product_data['NCM']); ?>"
                                                           maxlength="10"
                                                           size="10" onKeyPress="return digitos(event, this);"
                                                           onKeyDown="Mascara('NCM',this,event);" autocomplete="off"/>
                                                </div>
                                                <div class="form-group col-lg-2 col-md-2 col-xs-12">
                                                    <label for="garantia"><?= $this->lang->line('application_garanty'); ?>
                                                        (* <?= $this->lang->line('application_in_months'); ?>)</label>
                                                    <input type="text" <?= (!$storeCanUpdateProduct && $notAdmin ? 'readonly' : '') ?>
                                                           class="form-control" id="garantia" name="garantia" required
                                                           placeholder="<?= $this->lang->line('application_enter_warranty'); ?>"
                                                           value="<?php echo set_value('garantia', $product_data['garantia']); ?>"
                                                           autocomplete="off"
                                                           onKeyPress="return digitos(event, this);"/>
                                                </div>

                                                <?php
                                                if (isset($campoBlock)) {
                                                    $prazo = $product_data['prazo_operacional_extra'];
                                                } else {
                                                    $prazo = $days_cross_docking;
                                                }
                                                $liberado = isset($product_data['prazo_fixo']) ? ($product_data['prazo_fixo']) : 0;// liberado maior prazo ou não
                                                if ((in_array('changeCrossdocking', $this->permission))) { // Se Permitido Mostrar caixa de liberação de alteração de prazo
                                                    $permission = true;
                                                } else {
                                                    $permission = false;
                                                }
                                                ?>
                                                <script>
                                                    function liberar() {
                                                        let checkbox_liber = $("#checkbox_liber").is(":checked"); // verifica se checkbox esta marcado
                                                        let blocked_cross_docking   = $('#category_blocked_cross_docking').val();
                                                        let days_cross_docking      = $('#category_days_cross_docking').val();

                                                        if (blocked_cross_docking) {
                                                            if (checkbox_liber) { // se com bloqueio e normal
                                                                document.getElementById('days').disabled = false;
                                                                document.getElementById('days').readOnly = false;
                                                            } else { // se com bloqueio e normal
                                                                document.getElementById('days').disabled = false;
                                                                document.getElementById('days').readOnly = true;
                                                                document.getElementById('days').value = days_cross_docking;
                                                            }
                                                        } else {
                                                            if (checkbox_liber) { // se com bloqueio e normal
                                                                document.getElementById('days').disabled = false;
                                                                document.getElementById('days').readOnly = false;
                                                            } else { // se com bloqueio e normal
                                                                document.getElementById('days').disabled = true;
                                                                document.getElementById('days').readOnly = false;
                                                                document.getElementById('days').value = days_cross_docking;
                                                            }
                                                        }
                                                    }
                                                </script>
                                                <div class="form-group col-lg-2 col-md-2 col-xs-12">
                                                    <label for="days"><?= $this->lang->line('application_extra_operating_time'); ?></label>
                                                    <input type="number" class="form-control" maxlength="2" id="days"
                                                           name="prazo_operacional_extra"
                                                           placeholder="<?= $this->lang->line('application_extra_operating_time'); ?>"
                                                           value="<?php echo set_value('prazo_operacional_extra', $prazo) ?>"
                                                           autocomplete="off"
                                                           onKeyPress="return digitos(event, this);"/>

                                                    <div id="dv" style="display: none;">
                                                        <div id="text"></div>
                                                        <input onclick="liberar()" id="checkbox_liber" id="liber"
                                                               name="libera"
                                                               value="1" type="checkbox">
                                                        <label for="checkbox_liber"><?php echo $this->lang->line('application_liberar') ?></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="form-group col-md-12">
                                                    <div class="callout callout-warning mb-0" style="display: none"
                                                         id="listBlockView">
                                                        <h4 class="mt-0"><?= lang('application_blocked_product_future') ?></h4>
                                                        <ul></ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if (count($mykits)) : ?>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="box">
                                            <div class="box-body">
                                                <div class="row">
                                                    <div class="form-group col-md-12 col-xs-12">
                                                        <button onClick="toggleKits(event)" class="btn btn-default"><i
                                                                    class="fa fa-puzzle-piece"></i> <?= count($mykits) . ' ' . $this->lang->line('application_products_kit'); ?>
                                                        </button>
                                                        <div id="kits_products" style="display: none;">
                                                            <table class="table table-striped table-bordered">
                                                                <tr>
                                                                    <th scope="col"><?= $this->lang->line('application_id'); ?></th>
                                                                    <th scope="col"><?= $this->lang->line('application_seller_sku'); ?></th>
                                                                    <th scope="col"><?= $this->lang->line('application_name'); ?></th>
                                                                    <th scope="col"><?= $this->lang->line('application_price'); ?></th>
                                                                    <th scope="col"><?= $this->lang->line('application_qty'); ?></th>
                                                                    <th scope="col"><?= $this->lang->line('application_action'); ?></th>
                                                                </tr>
                                                                <?php foreach ($mykits as $kit) : ?>
                                                                    <tr>
                                                                        <th scope="row"><a target="__blank"
                                                                                           href="<?php echo base_url('productsKit/update/' . $kit['id']); ?>"><?php echo $kit['id']; ?></a>
                                                                        </th>
                                                                        <td><?php echo $kit['sku']; ?></td>
                                                                        <td><?php echo $kit['name']; ?></td>
                                                                        <td><?php echo get_instance()->formatprice($kit['price']); ?></td>
                                                                        <td><?php echo $kit['qty']; ?></td>
                                                                        <td><a target="__blank"
                                                                               href="<?php echo base_url('productsKit/update/' . $kit['id']); ?>"
                                                                               class="btn btn-default"><i
                                                                                        class="fa fa-eye"></i></a>
                                                                        </td>

                                                                    </tr>
                                                                <?php endforeach ?>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif ?>
                            <?php if (count($myorders)) : ?>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="box">
                                            <div class="box-body">
                                                <div class="row">
                                                    <div class="form-group col-md-12 col-xs-12">
                                                        <button onClick="toggleOrders(event)" class="btn btn-default"><i
                                                                    class="fa fa-dollar-sign"></i> <?= count($myorders) . ' ' . $this->lang->line('application_orders'); ?>
                                                        </button>
                                                        <div id="orders_product" style="display: none;">
                                                            <table class="table table-striped table-bordered">
                                                                <tr>
                                                                    <th scope="col"><?= $this->lang->line('application_id'); ?></th>
                                                                    <th scope="col"><?= $this->lang->line('application_marketplace'); ?></th>
                                                                    <th scope="col"><?= $this->lang->line('application_order_marketplace_full'); ?></th>
                                                                    <th scope="col"><?= $this->lang->line('application_name'); ?></th>
                                                                    <th scope="col"><?= $this->lang->line('application_total_amount'); ?></th>
                                                                    <th scope="col"><?= $this->lang->line('application_order_date'); ?></th>
                                                                    <th scope="col"><?= $this->lang->line('application_status'); ?></th>
                                                                    <th scope="col"><?= $this->lang->line('application_action'); ?></th>
                                                                </tr>
                                                                <?php foreach ($myorders as $order) : ?>
                                                                    <tr>
                                                                        <th scope="row"><a target="__blank"
                                                                                           href="<?php echo base_url('orders/update/' . $order['id']); ?>"><?php echo $order['id']; ?></a>
                                                                        </th>
                                                                        <td><?php echo $order['origin']; ?></td>
                                                                        <td><?php echo $order['numero_marketplace']; ?></td>
                                                                        <td><?php echo $order['customer_name']; ?></td>
                                                                        <td><?php echo get_instance()->formatprice($order['total_order']); ?></td>
                                                                        <td><?php echo date('d/m/Y', strtotime($order['date_time'])); ?></td>
                                                                        <td><?php echo $this->lang->line('application_order_' . $order['paid_status']); ?></td>
                                                                        <td><a target="__blank"
                                                                               href="<?php echo base_url('orders/update/' . $order['id']); ?>"
                                                                               class="btn btn-default"><i
                                                                                        class="fa fa-eye"></i></a>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach ?>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif ?>

                            <!--- adicionado tabela de itegrações -->
                            <?php if (isset($integracoes)) : ?>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="box">
                                            <div class="box-body">
                                                <div class="row">
                                                    <div class="form-group col-md-12 col-xs-12"
                                                         style="overflow-x: auto">
                                                        <label><?= $this->lang->line('application_integrations'); ?></label>
                                                        <table style="width:100%;"
                                                               class="table table-striped table-bordered">
                                                            <tr style="background-color: #f1f1c1; border: 1px solid black; border-collapse: collapse; font-size: smaller">
                                                                <th style="">Marketplace</th>
                                                                <th style="">SKU Local</th>
                                                                <th style="">SKU Marketplace</th>
                                                                <th style=""><?= $this->lang->line('application_status'); ?></th>
                                                                <th style=""><?= $this->lang->line('application_date'); ?></th>
                                                                <th style=""><?= $this->lang->line('application_advertisement_link'); ?></th>
                                                                <th style=""><?= $this->lang->line('application_quality'); ?></th>
                                                                <?php if (in_array('doProductsApproval', $this->permission)) : ?>
                                                                    <th style=""><?= $this->lang->line('application_products_approval'); ?></th>
                                                                <?php endif ?>
                                                            </tr>
                                                            <?php foreach ($integracoes as $integracao) :
                                                                $ad_link = '';
                                                                if (!is_null($integracao['ad_link'])) {
                                                                    $ad_links = json_decode($integracao['ad_link'], true);
                                                                    if (json_last_error() === 0) {
                                                                        foreach ($ad_links as $link) {
                                                                            $ad_link .= '<a target="__blank" href="' . $link['href'] . '" class="btn btn-default"><i class="fa fa-money"></i><small> ' . $link['name'] . '<small></a><br>';
                                                                        }
                                                                    } else {
                                                                        if (strpos($integracao['ad_link'], 'http') !== false) {
                                                                            $ad_link .= '<a target="__blank" href="' . $integracao['ad_link'] . '" class="btn btn-default"><i class="fa fa-money"></i><small> ' . $this->lang->line('application_goto_ad') . '<small></a>';
                                                                        }
                                                                    }
                                                                }
                                                                $quality = '';
                                                                if (!is_null($integracao['quality'])) {
                                                                    $perc = (float)$integracao['quality'] * 100;
                                                                    if ($perc == 100) {
                                                                        $desc = $this->lang->line('application_professional');
                                                                        $pd = "progress-bar-success";
                                                                    } elseif ($perc >= 80) {
                                                                        $desc = $this->lang->line('application_satisfactory');
                                                                        $pd = "progress-bar-info";
                                                                    } else {
                                                                        $desc = $this->lang->line('application_basic');
                                                                        $pd = "progress-bar-danger";
                                                                    }
                                                                    $quality = '<div class="progress-bar ' . $pd . '" role="progressbar" aria-valuenow="' . $perc . '" aria-valuemin="0" aria-valuemax="100" style="width:' . $perc . '%">' . $perc . '% ' . $desc . '</div>';
                                                                }

                                                                ?>
                                                                <tr style="background-color: white; border: 1px solid black; border-collapse: collapse; font-size: smaller">
                                                                    <td style=""><?php echo $integracao['name']; ?>
                                                                        <?php if (($integracao['status'] == 1) && (trim($integracao['skubling']) != '') && (trim($integracao['approved']) == 1)) : ?>
                                                                            <button onclick="sendToMarketplace(event,'<?= $integracao['int_to'] ?>','<?= $product_data['id'] ?>')"
                                                                                    class="pull-right btn btn-success btn-sm"><?= $this->lang->line('application_send'); ?></button>
                                                                        <?php endif ?>
                                                                    </td>
                                                                    <td style=""><?php echo $integracao['skubling']; ?></td>
                                                                    <td style=""><?php echo $integracao['skumkt']; ?></td>
                                                                    <?php if ($integracao['date_last_int'] != '') {
                                                                        $data_int = date("d/m/Y H:i:s", strtotime($integracao['date_last_int']));
                                                                    } else {
                                                                        $data_int = '--';
                                                                    }
                                                                    ?>
                                                                    <td style="max-width: 250px;overflow-x: auto"><?php echo $integracao['status_int']; ?>
                                                                        <?php if (($data_int != '--') && (!$notAdmin)): ?>
                                                                            <br>
                                                                            <a href="<?php echo base_url("products/log_integration_marketplace/" . $integracao['int_to'] . "/" . $product_data['id']) ?>"><?= $this->lang->line('application_integration_log_with') . ' ' . $integracao['int_to']; ?></a>
                                                                        <?php endif ?>
                                                                    </td>
                                                                    <td style=""><?php echo $data_int; ?></td>
                                                                    <td style=""><?php echo $ad_link; ?></td>
                                                                    <td style=""><?php echo $quality; ?></td>

                                                                    <?php if (in_array('doProductsApproval', $this->permission)) : ?>
                                                                        <?php if (!$integracao['auto_approve']) : ?>
                                                                            <td style="">
                                                                                <?php if ($integracao['approved'] != 4) : ?>
                                                                                    <?php if ($integracao['approved'] != 1) : ?>
                                                                                        <button onclick="changeIntegrationApproval(event,'<?= $integracao['id'] ?>','<?= $product_data['id'] ?>','1','<?= $integracao['approved'] ?>','<?= $integracao['int_to'] ?>')"
                                                                                                class="btn btn-success btn-sm"><?= $this->lang->line('application_approve'); ?></button>
                                                                                    <?php endif ?>
                                                                                    <?php if ($integracao['approved'] != 2) : ?>
                                                                                        <button onclick="changeIntegrationApproval(event,'<?= $integracao['id'] ?>','<?= $product_data['id'] ?>','2','<?= $integracao['approved'] ?>','<?= $integracao['int_to'] ?>')"
                                                                                                class="btn btn-danger btn-sm"><?= $this->lang->line('application_disapprove'); ?></button>
                                                                                    <?php endif ?>
                                                                                    <?php if ($integracao['approved'] != 3) : ?>
                                                                                        <button onclick="changeIntegrationApproval(event,'<?= $integracao['id'] ?>','<?= $product_data['id'] ?>','3','<?= $integracao['approved'] ?>','<?= $integracao['int_to'] ?>')"
                                                                                                class="btn btn-primary btn-sm"><?= $this->lang->line('application_mark_as_in_approval'); ?></button>
                                                                                    <?php endif ?>
                                                                                <?php endif ?>
                                                                            </td>

                                                                        <?php else : ?>
                                                                            <td style=""></td>
                                                                        <?php endif ?>
                                                                    <?php endif ?>
                                                                </tr>
                                                            <?php endforeach ?>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif ?>
                            <?php if (count($errors_transformation) > 0) : ?>
                                <div class="row" id="errors-transformation">
                                    <div class="col-md-12">
                                        <div class="box">
                                            <div class="box-body">
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <h2>
                                                            <span style="color: red"><?= $this->lang->line('application_errors_tranformation'); ?></span>
                                                        </h2>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="row">
                                                            <div class="form-group col-md-12 col-xs-12">
                                                                <table class="table table-bordered table-striped table-dark">
                                                                    <thead class="thead-light">
                                                                    <tr style="background-color: #f44336; border: 1px solid black; border-collapse: collapse; color:white">
                                                                        <th style="width: 10%"><?= $this->lang->line('application_marketplace'); ?></th>
                                                                        <?php if ($product_data['has_variants'] != ""): ?><th style="width: 10%"><?= $this->lang->line('application_variation'); ?></th><?php endif; ?>
                                                                        <th style="width: 10%"><?= $this->lang->line('application_step'); ?></th>
                                                                        <th style="width: 10%"><?= $this->lang->line('application_date'); ?></th>
                                                                        <th style="width: 70%"><?= $this->lang->line('application_error'); ?></th>
                                                                    </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                    <?php foreach ($errors_transformation as $error_transformation) : ?>
                                                                        <tr style="background-color: lightgray; border: 1px solid black; border-collapse: collapse;">
                                                                            <td style="width: 10%"><?php echo $error_transformation['int_to']; ?></td>
                                                                            <?php if ($product_data['has_variants'] != ""): ?><td style="width: 10%"><?=$error_transformation['variant']; ?></td><?php endif; ?>
                                                                            <td style="width: 10%"><?php echo $error_transformation['step']; ?></td>
                                                                            <td style="width: 10%"><?php echo date('d/m/Y H:i:s', strtotime($error_transformation['date_update'])); ?></td>
                                                                            <td style="width: 70%"><?php echo $error_transformation['message']; ?></td>
                                                                        </tr>
                                                                    <?php endforeach ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif ?>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="">
                                        <div class="box-footer form-action-btns">
                                            <div class="row">
                                                <div class="col-md-4 col-xs-12">
                                                    <div class="row">
                                                        <div class="col-md-5 col-xs-12">
                                                            <a href="<?php echo base_url('products/') ?>"
                                                               class="btn btn-warning"><?= $this->lang->line('application_back'); ?></a>
                                                        </div>
                                                        <div class="col-md-7 col-xs-12">
                                                            <button
                                                                    type="submit"
                                                                    class="btn btn-primary"
                                                                <?= $product_data['status'] == Model_products::DELETED_PRODUCT ? 'style="display:none;"' : '' ?>
                                                            >
                                                                <?= $this->lang->line('application_update_changes'); ?>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-8 col-xs-12">
                                                    <div class="row d-flex justify-content-end">
                                                        <?php if ($product_data['id'] > 0) { ?>
                                                            <?php if ((in_array('createCuration', $this->permission)) &&
                                                                ($product_data['status'] != Model_products::DELETED_PRODUCT)) {
                                                                if ($product_data['status'] == Model_products::BLOCKED_PRODUCT) { ?>
                                                                    <div class="col-md-3 col-xs-12">
                                                                        <button
                                                                            <?= $product_data['status'] == Model_products::DELETED_PRODUCT ? 'disabled="disabled"' : '' ?>
                                                                                type="submit"
                                                                                class="btn btn-success"
                                                                                formaction="<?= base_url('Whitelist/unlockProduct/' . $product_data['id']) ?>">
                                                                            Desbloquear Produto
                                                                        </button>
                                                                    </div>
                                                                <?php } else { ?>
                                                                    <div class="col-md-3 col-xs-12">
                                                                        <button
                                                                            <?= $product_data['status'] == Model_products::DELETED_PRODUCT ? 'disabled="disabled"' : '' ?>
                                                                                type="submit"
                                                                                class="btn btn-danger"
                                                                                formaction="<?= base_url('BlacklistWords/lockProduct/' . $product_data['id']) ?>">
                                                                            Bloquear Produto
                                                                        </button>
                                                                    </div>
                                                                <?php }

                                                            } ?>
                                                            <div class="col-md-2 col-xs-12">
                                                                <?php $attribute = preg_replace("/\D/", "", $product_data['category_id']); ?>
                                                                <a href="<?php echo base_url("products/attributes/edit/$product_data[id]/$attribute") ?>" class="btn btn-info">Atributos</a>
                                                            </div>

                                                            <?php if (in_array('addOn', $this->permission)) { ?>
                                                                <div class="col-md-2 col-xs-12">
                                                                    <a href="<?php echo base_url("AddOn/list/$product_data[id]") ?>" class="btn btn-info"><?= $this->lang->line('application_add_on'); ?></a>
                                                                </div>
                                                            <?php } ?>
                                                            
                                                            <?php if (($only_admin) && isset($integracoes)) { ?>
                                                                <div class="col-md-2 col-xs-12">
                                                                    <a href="<?php echo base_url("logQuotes/index/$product_data[id]") ?>" class="btn btn-warning"><?= $this->lang->line('application_quotations'); ?></a>
                                                                </div>
                                                            <?php } ?>
                                                        <?php } ?>
                                                        <div class="col-md-3 col-xs-12">
                                                            <a href="<?php echo base_url("products/log_products_view/$product_data[id]") ?>" class="pull-right btn btn-warning"><?= $this->lang->line('application_latest_changes'); ?></a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                </div>
            </div>
        </div>
    </section>
</div>
<!-- /.content-wrapper -->
<div class="modal fade" tabindex="-1" role="dialog" id="approvePromotion">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?= $this->lang->line('application_approve_promotion'); ?></h4>
            </div>
            <form role="form" action="<?php echo base_url('promotions/approvePromotion') ?>" method="post"
                  id="approvePromotionForm">
                <div class="modal-body">
                    <p><?= $this->lang->line('application_confirm_approve_promotion'); ?></p>
                    <input type="hidden" name="id_approve" id="id_approve" value="" autocomplete="off">
                    <input type="hidden" name="id_product" id="id_product" value="<?= $product_data['id']; ?>"
                           autocomplete="off">
                </div> <!-- modal-body -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-default"
                            data-dismiss="modal"><?= $this->lang->line('application_cancel'); ?></button>
                    <button type="submit" class="btn btn-primary" id="do_filter"
                            name="do_filter"><?= $this->lang->line('application_confirm'); ?></button>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="inactivePromotion">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?= $this->lang->line('application_inactivate_promotion'); ?></h4>
            </div>
            <form role="form" action="<?php echo base_url('promotions/inactivePromotion') ?>" method="post"
                  id="inactivePromotionForm">
                <div class="modal-body">
                    <p><?= $this->lang->line('application_confirm_inactivate_promotion'); ?></p>
                    <input type="hidden" name="id_inactive" id="id_inactive" value="" autocomplete="off">
                    <input type="hidden" name="id_product" id="id_product" value="<?= $product_data['id']; ?>"
                           autocomplete="off">
                </div> <!-- modal-body -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-default"
                            data-dismiss="modal"><?= $this->lang->line('application_cancel'); ?></button>
                    <button type="submit" class="btn btn-primary" id="do_filter"
                            name="do_filter"><?= $this->lang->line('application_confirm'); ?></button>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="removePromotion">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?= $this->lang->line('application_remove_promotion'); ?></h4>
            </div>
            <form role="form" action="<?php echo base_url('promotions/removePromotion') ?>" method="post"
                  id="removePromotionForm">
                <div class="modal-body">
                    <p><?= $this->lang->line('application_confirm_remove_promotion'); ?></p>
                    <input type="hidden" name="id_remove" id="id_remove" value="" autocomplete="off">
                    <input type="hidden" name="id_product" id="id_product" value="<?= $product_data['id']; ?>"
                           autocomplete="off">
                </div> <!-- modal-body -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-default"
                            data-dismiss="modal"><?= $this->lang->line('application_cancel'); ?></button>
                    <button type="submit" class="btn btn-primary" id="do_filter"
                            name="do_filter"><?= $this->lang->line('application_confirm'); ?></button>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" tabindex="-1" role="dialog" id="addBrandModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?= $this->lang->line('application_add_brand'); ?></h4>
            </div>

            <form role="form" action="<?php echo base_url('brands/create') ?>" method="post" id="createBrandForm">

                <div class="modal-body">

                    <div class="form-group">
                        <label for="brand_name"><?= $this->lang->line('application_name'); ?></label>
                        <input type="text" class="form-control" id="brand_name" name="brand_name"
                               placeholder="<?= $this->lang->line('application_enter_brand_name') ?>"
                               autocomplete="off">
                    </div>
                    <input type="hidden" id="active" name="active" value="1"/>
                    <input type="hidden" id="fromproducts" name="fromproducts" value="fromproducts"/>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default"
                            data-dismiss="modal"><?= $this->lang->line('application_close'); ?></button>
                    <button type="submit" id="brand_save_button" name="brand_save_button"
                            class="btn btn-primary"><?= $this->lang->line('application_save'); ?></button>
                </div>

            </form>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" tabindex="-1" role="dialog" id="addImagemModal">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?= $this->lang->line('application_uploadimages'); ?></h4>
            </div>
            <div class="modal-body">

                <div class="form-group col-md-12 col-xs-12">
                    <label for="product_image_variant"><?= $this->lang->line('application_uploadimages'); ?>(*):</label>
                    <div class="kv-avatar">
                        <div class="imagem_variant_wrap">
                            <?php
                            $tot = count($product_variants);
                            if ($tot == 0) {
                                $tot = 1;
                            }
                            for ($i = 0; $i < $tot; $i++) { ?>
                                <div id="showimage<?= $i; ?>" style="display:none">
                                    <div class="file-loading">
                                        <input type="file" id="prd_image_variant<?= $i; ?>"
                                               name="prd_image_variant<?= $i; ?>[]" accept="image/*" multiple>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                    <input type="hidden" id="variant_num" name="variant_num" value="0"/>
                </div>

            </div>

            <div class="modal-footer">
                <a href="#" onclick="UpdateVariantImage(event)"
                   class="btn btn-default"><?= $this->lang->line('application_close'); ?></a>
            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<input type="hidden" id="category_blocked_cross_docking" value="0"/>
<input type="hidden" id="category_days_cross_docking" value="0"/>
<input type="hidden" id="field-tooltip-sku-integartion" value="">
<script src="<?php echo base_url('assets/dist/js/components/products/product.disable.form.js') ?>"></script>
<script type="text/javascript">
    var base_url = "<?php echo base_url(); ?>";
    var varn_salvo = "<?php echo $product_variants['numvars'] + 1; ?>";
    var varnoriginal = "<?php echo $product_variants['numvars'] + 1; ?>";
    var varnsomado = varnoriginal;
    var update_category = "<?php echo in_array('updateCategory', $this->permission) ?>"
    var require_ean = "<?php echo ($require_ean) ? ' required ' : '' ?>"
    var product_id = "<?php echo $product_data['id']; ?>"
    var upload_url = "<?php echo (!$storeCanUpdateProduct && $notAdmin) ? '' : base_url('/Products/saveImageProduct'); ?>";
    var delete_url = "<?php echo (!$storeCanUpdateProduct && $notAdmin) ? '' : base_url($product_data['is_on_bucket']?'Products/removeImageProduct':'/assets/plugins/fileinput/products_variants/delete.php'); ?>";
    var productDeleted = '<?= $product_data['status'] == Model_products::DELETED_PRODUCT ?>';
    var flavor_active = '<?=!empty($flavor_active ?? '') ? 'true' : ''?>';
    var degree_active = '<?=!empty($degree_active ?? '') ? 'true' : ''?>';
    var side_active = '<?=!empty($side_active ?? '') ? 'true' : ''?>';


    $(document).ready(function () {
        if (productDeleted) {
            (new ProductDisableForm({
                form: $('#formUpdateProduct')
            })).disableForm();
        }
        $(".select_group").select2();
        $("#description").summernote({
            toolbar: [
                // [groupName, [list of button]]
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['view', ['fullscreen', 'codeview']]
            ],
            height: 150,
            disableDragAndDrop: true,
            lang: 'pt-BR',
            shortcuts: false,
            callbacks: {
                onBlur: function (e) {
                    verifyWords();
                },
                onKeyup: function (e) {
                    // var conteudo = $(".note-editable").text();
                    var conteudo = $(".note-editable").html();
                    var limit = $('#description').attr('maxlength');
                    if (conteudo.length > limit) {
                        // $(".note-editable").text(conteudo.slice(0,-(conteudo.length-limit)));
                        $(".note-editable").html(conteudo.slice(0, -(conteudo.length - limit)));
                    }
                    characterLimit(this);
                }
            }
        });
        $('#category').select2();

        if ($('#description').attr('readonly') == 'readonly') {
            $('#description').summernote('disable');
        }

        verifyWords();

        var wrapper = $(".input_fields_wrap"); //Fields wrapper
        var onBucket = <?php echo $product_data['is_on_bucket']?> // Flag para definir se o produto está ou não no bucket.

        initImage(0);
        for (i = 1; i < varnoriginal; i++) {
            initImage(i);
        }

        changeCategory($('#category option:selected').val());

        var varn = $("#numvar").val();
        // antigo if (varn > varn_salvo) {
        if (varnoriginal > 0) {
            var eansv = document.getElementsByName("EAN_V[]");
            for (i = 0; i < varnoriginal; i++) {
                checkEAN(eansv[i].value, 'EANV' + i, product_id);
            }
        }
        if (varn > 1) {
            if ($("#sizevar").prop("checked") == true) {
                var tamanhos = <?php echo json_encode($variacaotamanho); ?>;
            }
            if ($("#colorvar").prop("checked") == true) {
                var cores = <?php echo json_encode($variacaocor); ?>;
            }
            if ($("#voltvar").prop("checked") == true) {
                var voltagem = <?php echo json_encode($variacaovoltagem); ?>;
            }
            if ($("#saborvar").prop("checked") == true) {
                var sabor = <?php echo json_encode($variacaosabor); ?>;
            }
            if ($("#grauvar").prop("checked") == true) {
                var grau = <?php echo json_encode($variacaograu); ?>;
            }
            if ($("#ladovar").prop("checked") == true) {
                var lado = <?php echo json_encode($variacaolado); ?>;
            }
            var quantidades = <?php echo json_encode($variacaoquantidade); ?>;
            var skus = <?php echo json_encode($variacaosku); ?>;
            var eans = <?php echo json_encode($variacaoean); ?>;
            var prices = <?php echo json_encode($variacaoprice); ?>;
            var listprices = <?php echo json_encode($variacaolistprice); ?>;
            var imagens = <?php echo json_encode($variacaoimagem); ?>;

            var i = 0;


            for (i = varn_salvo; i < varn; i++) {
                //var linha = '<div class="row" id="variant'+varn+'">';
                var linha = '<div class="row" id="variant' + i + '">';
                linha = linha + '<div id="Invar' + i + '" class="col-md-1" style="padding-left: 0px;padding-right: 3px;"><span class="form-control label label-success">' + i + '</span></div>';

                if ($("#sizevar").prop("checked") == true) {
                    linha = linha + '<div id="Itvar" class="col-md-1" style="padding-left: 0px;padding-right: 3px;"><input type="text" required autocomplete="off" class="form-control" id="T[]" name="T[]" placeholder="<?= $this->lang->line('application_size') ?>" value="' + tamanhos[i] + '" /></div>';
                } else {
                    linha = linha + '<div id="Itvar" class="col-md-1" style="display: none; padding-left: 0px;padding-right: 3px;"><input type="text" autocomplete="off" class="form-control" id="T[]" name="T[]" placeholder="<?= $this->lang->line('application_size') ?>" /></div>';
                }
                if ($("#colorvar").prop("checked") == true) {
                    linha = linha + '<div id="Icvar" class="col-md-2" style="padding-left: 0px;padding-right: 3px;"><input type="text" autocomplete="off" required class="form-control" id="C[]" name="C[]" placeholder="<?= $this->lang->line('application_color') ?>" value="' + cores[i] + '" /></div>';
                } else {
                    linha = linha + '<div id="Icvar" class="col-md-2" style="display: none; padding-left: 0px;padding-right: 3px;"><input type="text" autocomplete="off" class="form-control" id="C[]" name="C[]" placeholder="<?= $this->lang->line('application_color') ?>" /></div>';
                }
                if ($("#voltvar").prop("checked") == true) {
                    linha = linha + '<div id="Icvar" class="col-md-2" style="padding-left: 0px;padding-right: 3px;"><input type="text" autocomplete="off" required class="form-control" id="V[]" name="V[]" placeholder="<?= $this->lang->line('application_voltage') ?>" value="' + voltagem[i] + '" /></div>';
                } else {
                    linha = linha + '<div id="Icvar" class="col-md-2" style="display: none; padding-left: 0px;padding-right: 3px;"><input type="text" autocomplete="off" class="form-control" id="V[]" name="V[]" placeholder="<?= $this->lang->line('application_voltage') ?>" /></div>';
                }
                if (!flavor_active == '') {
                    if ($("#saborvar").prop("checked") == true) {
                        linha = linha + '<div id="Isvar" class="col-md-2" style="padding-left: 0px;padding-right: 3px;"><input type="text" required class="form-control" id="sb[]" name="sb[]" autocomplete="off" placeholder="<?= $this->lang->line('application_flavor'); ?>" value="' + sabor[i] + '" /></div>';
                    } else {
                        linha = linha + '<div id="Isvar" class="col-md-2" style="display: none; padding-left: 0px;padding-right: 3px;"><input type="text" class="form-control" id="sb[]" name="sb[]" autocomplete="off" placeholder="<?= $this->lang->line('application_flavor'); ?>"  /></div>';
                    }

                }

                if (!degree_active == '') {
                    if ($("#grauvar").prop("checked") == true) {
                        linha = linha + '<div id="Igvar" class="col-md-2" style="padding-left: 0px;padding-right: 3px;"><input type="text" required class="form-control" id="gr[]" name="gr[]" autocomplete="off" placeholder="<?= $this->lang->line('application_degree'); ?>" value="' + grau[i] + '" /></div>';
                    } else {
                        linha = linha + '<div id="Igvar" class="col-md-2" style="display: none; padding-left: 0px;padding-right: 3px;"><input type="text" class="form-control" id="gr[]" name="gr[]" autocomplete="off" placeholder="<?= $this->lang->line('application_degree'); ?>"  /></div>';
                    }

                }

                if (!side_active == '') {
                    if ($("#ladovar").prop("checked") == true) {
                        linha = linha + '<div id="Ilvar" class="col-md-2" style="padding-left: 0px;padding-right: 3px;"><input type="text" required class="form-control" id="ld[]" name="ld[]" autocomplete="off" placeholder="<?= $this->lang->line('application_side'); ?>" value="' + lado[i] + '" /></div>';
                    } else {
                        linha = linha + '<div id="Ilvar" class="col-md-2" style="display: none; padding-left: 0px;padding-right: 3px;"><input type="text" class="form-control" id="ld[]" name="ld[]" autocomplete="off" placeholder="<?= $this->lang->line('application_side'); ?>"  /></div>';
                    }

                }

                linha = linha + '<div id="Iqvar" class="col-md-1" style="padding-left: 0px;padding-right: 3px;"><input type="text" required autocomplete="off" class="form-control" id="Q[]" name="Q[]" placeholder="<?= $this->lang->line('application_stock') ?>" onKeyPress="return digitos(event, this);"  value="' + quantidades[i] + '" /></div>';
                linha = linha + '<div id="Iskuvar" class="col-md-1" style="padding-left: 0px;padding-right: 3px;"><input type="text" autocomplete="off" class="form-control" id="SKU_V' + i + '" name="SKU_V[]" placeholder="SKU Variação" value="' + skus[i] + '"  onKeyUp="checkSpecialSku(event, this);" onblur="checkSpecialSku(event, this);" /></div><span id="char_SKU_V_' + i + '"></span><br />';
                linha = linha + '<div id="EANV' + i + '" class="Ieanvar form-group col-md-2" style="padding-left: 0px;padding-right: 3px;"><input type="text" autocomplete="off" ' + require_ean + ' class="form-control" id="EAN_V[]" name="EAN_V[]" placeholder="EAN Variação" onchange="checkEAN(this.value,\'EANV' + i + '\',\'' + product_id + '\')" value="' + eans[i] + '" /></div>';
                checkEAN(eans[i], 'EANV' + i, product_id);
                var displayPriceByVariation = $('.Ipricevar').length;
                if (displayPriceByVariation > 0) {
                    linha = linha + '<div id="Ilistpricevar" class="col-md-1 " style="padding-left: 0px;padding-right: 3px;width:auto;"><input type="text" autocomplete="off" class="form-control maskdecimal2" id="LIST_PRICE_V[]" name="LIST_PRICE_V[]" oninput="restrict(this)" placeholder="Preço Variação" value="' + listprices[i] + '" /></div>';

                    linha = linha + '<div id="Ipricevar" class="col-md-1 " style="padding-left: 0px;padding-right: 3px;width:auto;"><input type="text" autocomplete="off" class="form-control maskdecimal2" id="PRICE_V[]" name="PRICE_V[]" oninput="restrict(this)" placeholder="Preço Variação" value="' + prices[i] + '" /></div>';
                }

                linha = linha + '<div class="form-group col-md-2">';
                linha = linha + '<input type="hidden" id="IMAGEM' + i + '" name="IMAGEM[]" value="' + imagens[i] + '" />';
                linha = linha + '<a href="#" onclick="AddImage(event,\'' + i + '\')" >';
                linha = linha + '<img id="foto_variant' + i + '" src="' + base_url + 'assets/images/system/sem_foto.png' + '" class="img-rounded" width="40" height="40" />';
                linha = linha + '<i class="fa fa-plus-circle" style="margin-left: 6px"></i></a>';
                linha = linha + '<button type="button" onclick="RemoveVariant(event,' + i + ')" class="btn btn-danger" style="margin-left:6px"><i class="fa fa-trash"></i></button>'
                linha = linha + '</div>';

                //  linha = linha + '<div class="form-group col-md-1"><button type="button" class="btn btn-default remove_field"><i class="fa fa-trash"></i></button></div></div>';
                linha = linha + '</div>';
                $(wrapper).append(linha);

                var wrapperimagem = $(".imagem_variant_wrap");
                var lin_image = '<div id="showimage' + i + '" style="display:none"><div class="file-loading"><input type="file" id="prd_image_variant' + i + '" name="prd_image_variant' + i + '[]" accept="image/*" multiple></div></div>'
                wrapperimagem.append(lin_image);
                //console.log(lin_image);

                initImage(i);
            }

        } else {
            var eans = <?php echo json_encode($variacaoean); ?>;
            if (eans !== null) {
                if (eans.length > 0) {
                    checkEAN(eans[0], 'EANV0', product_id);
                }
            }

        }


        $('.maskdecimal3').inputmask({
            alias: 'numeric',
            allowMinus: false,
            digits: 3,
            max: 999999999.999
        });
        $('.maskdecimal2').inputmask({
            alias: 'numeric',
            allowMinus: false,
            digits: 2,
            max: 999999999.99
        });
        var btnCust = '<button type="button" class="btn btn-secondary" title="Add picture tags" ' +
            'onclick="alert(\'Call your custom code here.\')">' +
            '<i class="glyphicon glyphicon-tag"></i>' +
            '</button>';
        var token = '<?= $product_data['image']; ?>'; // My Token
        $("#prd_image").fileinput({
            uploadUrl: "<?= base_url('/Products/saveImageProduct'); ?>",
            language: 'pt-BR',
            autoOrientImage: false,
            allowedFileExtensions: ["jpg", "png"],
            uploadAsync: true,
            showUpload: false,
            enableResumableUpload: true,
            resumableUploadOptions: {},
            uploadExtraData: {
                'uploadToken': token, // for access control / security
                'onBucket': onBucket
            },
            maxTotalFileCount: <?= $limite_imagens_aceitas_api;?>,
            allowedFileTypes: ['image'], // allow only images
            showCancel: true,
            initialPreviewAsData: true,
            overwriteInitial: false,
            showRemove: false,
            theme: 'fas',
            deleteUrl: "<?= base_url($product_data['is_on_bucket']?'Products/removeImageProduct':'/assets/plugins/fileinput/examples/delete.php'); ?>",
            initialPreview: [
                <?php
                for ($i = 1; $i <= $numft; $i++) {
                    echo '"' . $ln1[$i] . '",';
                }
                ?>
            ],
            initialPreviewConfig: [
                <?php
                for ($i = 1; $i <= $numft; $i++) {
                    echo $ln2[$i] . ',';
                }
                ?>
            ],
            minImageWidth: <?=$dimenssion_min_product_image ?? 'null'?>,
            minImageHeight: <?=$dimenssion_min_product_image ?? 'null'?>,
            maxImageWidth: <?=$dimenssion_max_product_image ?? 'null'?>,
            maxImageHeight: <?=$dimenssion_max_product_image ?? 'null'?>
        }).on('filesorted', function (event, params) {
            changeTheOrderOfImages(params)
            console.log('File sorted params', params);
        }).on('fileuploaded', function (event, previewId, index, fileId) {
            loadAfterFileInputPreview($('#addImagemForm'));
            console.log('File Uploaded', 'ID: ' + fileId + ', Thumb ID: ' + previewId);
            $('#product_image').val(token);
        }).on('fileuploaderror', function (event, data, msg) {
            console.log('File Upload Error', 'ID: ' + data.fileId + ', Thumb ID: ' + data.previewId);
        }).on('filebatchuploadcomplete', function (event, preview, config, tags, extraData) {
            console.log('File Batch Uploaded', preview, config, tags, extraData);
        }).on('filebatchselected', function (event, files) {
            $("#prd_image").fileinput("upload");
        });

        function changeTheOrderOfImages(params) {
            $.ajax({
                type: "POST",
                enctype: 'multipart/form-data',
                data: {
                    params: params,
                    onBucket: onBucket
                },
                url: base_url + "index.php/products/orderimages",
                dataType: "json",
                async: true,
                // complete: function(response){
                // 	console.log(response)
                // },
                success: function (success) {
                    console.log(success)
                },
                error: function (error) {
                    console.log(error)
                }
            });
        }

        //new
        if ($("#semvar").prop("checked") == false && ($("#sizevar").prop("checked") || $("#colorvar").prop("checked") || $("#voltvar").prop("checked") || $("#saborvar").prop("checked") || $("#grauvar").prop("checked") || $("#ladovar").prop("checked"))) {
            $('#qty').attr("disabled", true);
            $('#qty').attr("required", false);
            $('[id="T[]"').attr("required", $("#sizevar").prop("checked"));
            $('[id="C[]"').attr("required", $("#colorvar").prop("checked"));
            $('[id="sb[]"').attr("required", $("#saborvar").prop("checked"));
            $('[id="gr[]"').attr("required", $("#grauvar").prop("checked"));
            $('[id="ld]"').attr("required", $("#ladovar").prop("checked"));
            $('[id="Q[]"').attr("required", true);
            if ($("#sizevar").prop("checked") == false) {
                $('#Ltvar').hide();
                $('[id=Itvar]').hide();
            }
            if ($("#colorvar").prop("checked") == false) {
                $('#Lcvar').hide();
                $('[id=Icvar]').hide();
            }
            if ($("#voltvar").prop("checked") == false) {
                $('#Lvvar').hide();
                $('[id=Ivvar]').hide();
            }
            if ($("#saborvar").prop("checked") == false) {
                $('#Lsvar').hide();
                $('[id=Isvar]').hide();
            }
            if ($("#grauvar").prop("checked") == false) {
                $('#Lgvar').hide();
                $('[id=Igvar]').hide();
            }
            if ($("#ladovar").prop("checked") == false) {
                $('#Llvar').hide();
                $('[id=Ilvar]').hide();
            }
            $('[id="EAN_V[]"').attr("required", (require_ean == ' required '));
            $("#variantModal").show();
        }
        $('#semvar').change(function () {
            $('[id="T[]"').attr("required", false);
            $('[id="C[]"').attr("required", false);
            $('[id="Q[]"').attr("required", false);
            $('[id="sb[]"').attr("required", false);
            $('[id="gr[]"').attr("required", false);
            $('[id="ld[]"').attr("required", false);
            $('[id="EAN_V[]"').attr("required", false);
            $('#sizevar').prop('checked', false);
            $('#colorvar').prop('checked', false);
            $('#voltvar').prop('checked', false);
            $('#saborvar').prop('checked', false);
            $("#variantModal").hide();
            $.fn.variantsclear();
            $('#qty').attr("disabled", false);
            $('#qty').attr("required", true);

        });
        $('#sizevar').change(function () {
            $('#semvar').prop('checked', false);
            $.fn.variants();
        });
        $('#colorvar').change(function () {
            $('#semvar').prop('checked', false);
            $.fn.variants();
        });
        $('#voltvar').change(function () {
            $('#semvar').prop('checked', false);
            $.fn.variants();
        });
        $('#saborvar').change(function () {
            $('#semvar').prop('checked', false);
            $.fn.variants();
        });
        $('#grauvar').change(function () {
            $('#semvar').prop('checked', false);
            $.fn.variants();
        });
        $('#ladovar').change(function () {
            $('#semvar').prop('checked', false);
            $.fn.variants();
        });
        $.fn.variants = function () {
            $("#variantModal").show();
            var num = 0;
            $('[id="T[]"').attr("required", $("#sizevar").prop("checked"));
            $('[id="C[]"').attr("required", $("#colorvar").prop("checked"));
            $('[id="sb[]"').attr("required", $("#saborvar").prop("checked"));
            $('[id="gr[]"').attr("required", $("#saborvar").prop("checked"));
            $('[id="ld[]"').attr("required", $("#saborvar").prop("checked"));
            if ($("#sizevar").prop("checked") == false) {
                $('#Ltvar').hide();
                $('[id=Itvar]').hide();
                num++;
            }
            if ($("#sizevar").prop("checked") == true) {
                $('#Ltvar').show();
                $('[id=Itvar]').show();
            }
            if ($("#colorvar").prop("checked") == false) {
                $('#Lcvar').hide();
                $('[id=Icvar]').hide();
                num++;
            }
            if ($("#colorvar").prop("checked") == true) {
                $('#Lcvar').show();
                $('[id=Icvar]').show();
            }
            if ($("#voltvar").prop("checked") == false) {
                $('#Lvvar').hide();
                $('[id=Ivvar]').hide();
                num++;
            }
            if ($("#voltvar").prop("checked") == true) {
                $('#Lvvar').show();
                $('[id=Ivvar]').show();
            }
            if ($("#saborvar").prop("checked") == false) {
                $('#Lsvar').hide();
                $('[id=Isvar]').hide();
                num++;
            }
            if ($("#saborvar").prop("checked") == true) {
                $('#Lsvar').show();
                $('[id=Isvar]').show();
            }
            if ($("#grauvar").prop("checked") == false) {
                $('#Lgvar').hide();
                $('[id=Igvar]').hide();
                num++;
            }
            if ($("#grauvar").prop("checked") == true) {
                $('#Lgvar').show();
                $('[id=Igvar]').show();
            }
            if ($("#ladovar").prop("checked") == false) {
                $('#Llvar').hide();
                $('[id=Ilvar]').hide();
                num++;
            }
            if ($("#ladovar").prop("checked") == true) {
                $('#Llvar').show();
                $('[id=Ilvar]').show();
            }
            if (num == 6) {
                $('#semvar').prop('checked', true);
                $("#variantModal").hide();
                $.fn.variantsclear();
                $('#qty').attr("disabled", false);
                $('#semvar').prop('checked', true);
                $('#qty').attr("required", true);
                $('[id="Q[]"').attr("required", false);
                $('[id="EAN_V[]"').attr("required", false);

            } else {
                $('#qty').attr("disabled", true);
                $('#qty').attr("required", false);
                $('[id="Q[]"').attr("required", true);

                $('[id="EAN_V[]"').attr("required", (require_ean == ' required '));
            }
        }

        $('#category').change(function () {
            var idcat = $('#category option:selected').val();
            changeCategory(idcat);
            verifyWords();
        });

        var wrapper = $(".input_fields_wrap"); //Fields wrapper
        var add_line = $(".add_line"); //Add button ID

        $(add_line).click(function (e) { //on add input button click
            e.preventDefault();
            varn = $("#numvar").val();
            linnum = varn;

            varn++;
            varnsomado++;

            var noshow = ' style="display: none; padding-left: 0px;padding-right: 3px;"';
            var show = ' style="padding-left: 0px;padding-right: 3px;" '
            var linha = '<div class="container-fluid col-person-tree" id="variant' + varnsomado + '">';

            var required = 'required';

            linha = linha + '<div id="Invar' + varnsomado + '" class="col-md-1" style="padding-left: 0px;padding-right: 3px;width:auto;"><span class="form-control label label-success">' + linnum + '</span></div>';

            var tag = show;
            if ($("#sizevar").prop("checked") == false) {
                tag = noshow;
                required = '';
            }
            linha = linha + '<div id="Itvar" class="col-md-1"' + tag + '><input type="text" ' + required + ' autocomplete="off" class="form-control" id="T[]" name="T[]" placeholder="<?= $this->lang->line('application_size') ?>"  /></div>';
            tag = show;
            required = 'required';
            if ($("#colorvar").prop("checked") == false) {
                tag = noshow;
                required = '';
            }
            linha = linha + '<div id="Icvar" class="col-md-1"' + tag + '><input type="text" ' + required + ' autocomplete="off" class="form-control" id="C[]" name="C[]" placeholder="<?= $this->lang->line('application_color') ?>"  /></div>';

            tag = show;
            required = 'required';
            if ($("#voltvar").prop("checked") == false) {
                tag = noshow;
                required = '';
            }

            tag2 = show;
            required = 'required';
            if ($("#saborvar").prop("checked") == false) {
                tag2 = noshow;
                required = '';
            }

            tag3 = show;
            required = 'required';
            if ($("#grauvar").prop("checked") == false) {
                tag3 = noshow;
                required = '';
            }

            tag4 = show;
            required = 'required';
            if ($("#ladovar").prop("checked") == false) {
                tag4 = noshow;
                required = '';
            }

            linha = linha + '<div id="Ivvar" class="col-md-1"' + tag + '><input type="text" ' + required + ' autocomplete="off" class="form-control" id="V[]" name="V[]" placeholder="<?= $this->lang->line('application_voltage') ?>"  /></div>';
            <?php if($flavor_active) { ?>
            linha = linha + '<div id="Isvar" class="col-md-1"' + tag2 + '><input type="text" class="form-control" id="sb[]" name="sb[]" autocomplete="off"  placeholder="<?= $this->lang->line('application_flavor'); ?>"  /></div>';
            <?php } ?>
            <?php if($degree_active) { ?>
            linha = linha + '<div id="Igvar" class="col-md-1"' + tag3 + '><input type="text" class="form-control" id="gr[]" name="gr[]" autocomplete="off"  placeholder="<?= $this->lang->line('application_degree'); ?>"  /></div>';
            <?php } ?>
            <?php if($side_active) { ?>
            linha = linha + '<div id="Ilvar" class="col-md-1"' + tag4 + '><input type="text" class="form-control" id="ld[]" name="ld[]" autocomplete="off"  placeholder="<?= $this->lang->line('application_side'); ?>"  /></div>';
            <?php } ?>
            linha = linha + '<div id="Iqvar" class="col-md-1" style="padding-left: 0px;padding-right: 3px;"><input type="text" required autocomplete="off" class="form-control" id="Q[]" name="Q[]" placeholder="<?= $this->lang->line('application_stock') ?>" onKeyPress="return digitos(event, this);" /></div>';
            linha = linha + '<div id="Iskuvar" class="col-md-1" style="padding-left: 0px;padding-right: 3px;"><input type="text" autocomplete="off" class="form-control" id="SKU_V_' + i + '" name="SKU_V[]" placeholder="SKU Variação" onKeyUp="checkSpecialSku(event, this);" onblur="checkSpecialSku(event, this);" /></div><span id="char_SKU_V_' + i + '"></span><br />';
            linha = linha + '<div id="EANV' + varnsomado + '" class="Ieanvar col-md-2" style="padding-left: 0px;padding-right: 3px;"><input style="margin-top:-20px;" type="text" autocomplete="off" ' + require_ean + ' class="form-control" id="EAN_V[]" name="EAN_V[]" onchange="checkEAN(this.value,\'EANV' + varnsomado + '\',\'' + product_id + '\')" placeholder="EAN Variação"/><span id="EANV' + varnsomado + 'erro" style="display: none;"><i style="color:red"><?= $invalid_ean; ?></i></span></div>';
            var displayPriceByVariation = $('.Ipricevar').length;

            if (displayPriceByVariation > 0) {
                linha = linha + '<div id="Ipricevar" class="col-md-1" style="padding-left: 0px;padding-right: 3px;"><input style="margin-top:-20px;" type="text" autocomplete="off" class="form-control maskdecimal2" id="LIST_PRICE_V[]" name="LIST_PRICE_V[]" oninput="restrict(this)" placeholder="Preço De - Variação"/></div>';
                linha = linha + '<div id="Ipricevar" class="col-md-1" style="padding-left: 0px;padding-right: 3px;"><input style="margin-top:-20px;" type="text" autocomplete="off" class="form-control maskdecimal2" id="PRICE_V[]" name="PRICE_V[]" oninput="restrict(this)" placeholder="Preço Por - Variação"/></div>';
            }

            linha = linha + '<div  class="col-md-1" style="padding-left: 0px;padding-right: 3px;margin-top: -22px;width: auto;">';
            var charSet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            var randomString = '';
            for (var i = 0; i < 15; i++) {
                var randomPoz = Math.floor(Math.random() * charSet.length);
                randomString += charSet.substring(randomPoz, randomPoz + 1);
            }
            linha = linha + '<input type="hidden" id="IMAGEM' + varnsomado + '" name="IMAGEM[]" value="' + randomString + '" />';
            linha = linha + '<a href="#" onclick="AddImage(event,\'' + varnsomado + '\')" >';
            linha = linha + '<img id="foto_variant' + varnsomado + '" src="' + base_url + 'assets/images/system/sem_foto.png' + '"  class="img-rounded" width="40" height="40" />';
            linha = linha + '<i class="fa fa-plus-circle" style="margin-left: 6px"></i></a>';
            linha = linha + '<button type="button" onclick="RemoveVariant(event,' + varnsomado + ')" class="btn btn-danger" style="margin-left:6px"><i class="fa fa-trash"></i></button>';
            linha = linha + '</div>';

            linha = linha + '</div>';
            //console.log(lin_image);

            // linha = linha + '<div class=""><button type="button" class="btn btn-default remove_field"><i class="fa fa-trash"></i></button></div></div>';

            $(wrapper).append(linha);

            var wrapperimagem = $(".imagem_variant_wrap");
            var lin_image = '<div id="showimage' + varnsomado + '" style="display:none"><div class="file-loading"><input type="file" id="prd_image_variant' + varnsomado + '" name="prd_image_variant' + varnsomado + '[]" accept="image/*" multiple></div></div>'
            wrapperimagem.append(lin_image);

            initImage(varnsomado, randomString);


            $('#numvar').val(varn);
        });

        $(wrapper).on("click", ".remove_field", function (e) { //user click on remove text
            e.preventDefault();
            varn--;
            if (varn == 0) {
                varn = 1;
            }
            $('#numvar').val(varn);
            $(this).parent('div').parent('div').remove();
            // antigo j=Number(varnoriginal);
            j = Number(1);
            for (i = j; i <= Number(varnsomado); i++) {
                if ($('#Invar' + i).length != 0) {
                    linnum = j - 1;
                    $('#Invar' + i).html('<span class="form-control label label-success">' + linnum + '</span>');
                    j++;
                }
            }
        })


        $('#reset_variant').click(function (e) { //on clear button click
            e.preventDefault();
            $.fn.variantsclear();
        });

        $.fn.variantsclear = function () {
            for (i = 2; i <= Number(varnsomado); i++) {
                div = 'div #variant' + i;
                $(div).remove();
            }
            // varn = varnoriginal;
            varn = 1;
            varnsomado = varnoriginal;
            $('#numvar').val(varn);
        }

        // submit the create from
        $("#createBrandForm").unbind('submit').on('submit', function () {
            var form = $(this);
            $('#brand_save_button').prop('disabled', true);

            // remove the text-danger
            $(".text-danger").remove();

            $.ajax({
                url: form.attr('action'),
                type: form.attr('method'),
                data: form.serialize(), // /converting the form data into array and sending it to server
                dataType: 'json',
                success: function (response) {

                    if (response.success === true) {
                        $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">' +
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                            '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>' + response.messages +
                            '</div>');

                        // hide the modal
                        $("#addBrandModal").modal('hide');

                        //adiciono a opção recem criada
                        $("#brands option[value='']").remove();
                        $('#brands option:selected').before($('<option>', {
                            value: response.id,
                            text: response.brand_name,
                            selected: "selected"
                        }));
                        $("#brands").selectpicker('refresh');
                        $("#brands").val(response.id).change();

                        // reset the form
                        $("#createBrandForm")[0].reset();
                        $("#createBrandForm .form-group").removeClass('has-error').removeClass('has-success');

                    } else {

                        if (response.messages instanceof Object) {
                            $.each(response.messages, function (index, value) {
                                var id = $("#" + index);

                                id.closest('.form-group')
                                    .removeClass('has-error')
                                    .removeClass('has-success')
                                    .addClass(value.length > 0 ? 'has-error' : 'has-success');

                                id.after(value);

                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: response.messages
                            });

                            //$("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">' +
                            //   '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                            //   '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>' + response.messages +
                            //   '</div>');
                        }
                    }
                    $('#brand_save_button').prop('disabled', false);
                },
                error: function (jqXHR, exception) {
                    var msg = '';
                    if (jqXHR.status === 0) {
                        msg = 'Not connect.\n Verify Network.';
                    } else if (jqXHR.status == 404) {
                        msg = 'Requested page not found. [404]';
                    } else if (jqXHR.status == 500) {
                        msg = 'Internal Server Error [500].';
                    } else if (exception === 'parsererror') {
                        msg = 'Requested JSON parse failed.';
                    } else if (exception === 'timeout') {
                        msg = 'Time out error.';
                    } else if (exception === 'abort') {
                        msg = 'Ajax request aborted.';
                    } else {
                        msg = 'Uncaught Error.\n' + jqXHR.responseText;
                    }
                    $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                        '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>' + msg +
                        '</div>');
                    // hide the modal
                    $("#addBrandModal").modal('hide');
                    Swal.fire({
                        icon: 'error',
                        title: msg
                    });
                },
            });

            return false;
        });

        characterLimit(document.getElementById('product_name'));
        characterLimit(document.getElementById('description'));
        characterLimit(document.getElementById('sku'));
    });

    function toggleOrders(e) {
        e.preventDefault();
        $("#orders_product").toggle();
    }

    function toggleKits(e) {
        e.preventDefault();
        $("#kits_products").toggle();
    }

    function AddBrand(e) {

        $("#addBrandModal").modal('show');
    }

    function approvePromotionxxxx(e, promotion_id) {
        e.preventDefault();

        $.ajax({
            type: "POST",
            data: {
                id: promotion_id
            },
            url: base_url + "promotions/approvePromotion",
            dataType: "json",
            async: true,
            success: function (data) {
            }
        });
        location.reload();
    }

    function approvePromotion(e, promotion_id) {
        e.preventDefault();
        document.getElementById('id_approve').value = promotion_id;
        $("#approvePromotion").modal('show');
    }

    function inactivePromotion(e, promotion_id) {
        e.preventDefault();
        document.getElementById('id_inactive').value = promotion_id;
        $("#inactivePromotion").modal('show');
    }

    function deletePromotion(e, promotion_id) {
        e.preventDefault();
        document.getElementById('id_remove').value = promotion_id;
        $("#removePromotion").modal('show');
    }

    function restrict(tis) { // so aceita numero com 2 digitos
        var prev = tis.getAttribute("data-prev");
        prev = (prev != '') ? prev : '';
        if (Math.round(tis.value * 100) / 100 != tis.value)
            tis.value = prev;
        tis.setAttribute("data-prev", tis.value)
    }

    function samePrice(id) {
        const samePrice = document.getElementById('samePrice_' + id).checked

        const fields = [{
            original: 'price',
            copy: 'price_' + id
        },]

        if (samePrice) {
            fields.forEach((item) => {
                $('#' + item.copy)[0].value = $('#' + item.original).val()
                $('#' + item.copy).attr('disabled', 'disabled')
            })
            $('#samePrice_' + id).attr('checked', 'checked')
        } else {
            fields.forEach((item) => {
                $('#' + item.copy)[0].value = $('#' + item.original).val()
                $('#' + item.copy).removeAttr('disabled')
            })
            $('#samePrice_' + id).removeAttr('checked')
        }
    }

    function sameQty(id) {
        const sameQty = document.getElementById('sameQty_' + id).checked

        const fields = [{
            original: 'qty',
            copy: 'qty_' + id
        },]

        if (sameQty) {
            fields.forEach((item) => {
                $('#' + item.copy)[0].value = $('#' + item.original).val()
                $('#' + item.copy).attr('disabled', 'disabled')
            })
            $('#sameQty_' + id).attr('checked', 'checked')
        } else {
            fields.forEach((item) => {
                $('#' + item.copy)[0].value = $('#' + item.original).val()
                $('#' + item.copy).removeAttr('disabled')
            })
            $('#sameQty_' + id).removeAttr('checked')
        }
    }

    function changeQtyMkt(id) {

        if ($('#qty_' + id).val() > $('#qty').val()) {
            Swal.fire({
                icon: 'error',
                title: "A quantidade para um marketplace não pode ser maior que o estoque do produto."
            }).then((result) => {
            });
            $('#qty_' + id)[0].value = $('#qty').val()
        }

    }

    function changeIntegrationApproval(e, id, prd_id, approve, old_approve, int_to) {
        e.preventDefault();
        $.ajax({
            url: base_url + "products/changeIntegrationApproval",
            type: "POST",
            data: {
                id: id,
                prd_id: prd_id,
                approve: approve,
                old_approve: old_approve,
                int_to: int_to
            },
            async: true,
            success: function (data) {
                location.reload();
            },
            error: function (data) {
                AlertSweet.fire({
                    icon: 'Error',
                    title: 'Houve um erro ao atualizar o produto!'
                });
            }
        });

    }

    $('#formUpdateProduct').submit(function () {
        let variations = [];
        let exitApp = false;
        $('input[name="SKU_V[]"]').each(function () {
            if (variations.includes($(this).val()) && $(this).val() != "") exitApp = true;
            variations.push($(this).val());
        });

        if (exitApp) {
            AlertSweet.fire({
                icon: 'warning',
                title: 'Não é permitido o mesmo SKU para mais que uma variação. <br><br>Faça o ajuste e tente novamente!'
            });
            return false;
        }

        variations = [];
        exitApp = false;
        $('input[name="EAN_V[]"]').each(function () {
            if (variations.includes($(this).val()) && $(this).val() != "") exitApp = true;
            variations.push($(this).val());
        });

        if (exitApp) {
            AlertSweet.fire({
                icon: 'warning',
                title: 'Não é permitido o mesmo EAN para mais que uma variação. <br><br>Faça o ajuste e tente novamente!'
            });
            return false;
        }
    });


    function toggleCatMktPlace(e) {
        e.preventDefault();
        $("#catlinkdiv").toggle();
    }

    function changeCategory(id) {
        let catlink = $("#linkcategory");
        let cattable = $("#catlinkbuttondiv");

        cattable.remove();
        $.ajax({
            type: 'GET',
            dataType: "json",
            url: base_url + 'category/getLinkCategory/',
            data: "idcat=" + id,
            success: function (data) {
                var cats = '<div id="catlinkbuttondiv" class="form-group col-md-12 col-xs-12 row"><button type="button" onClick="toggleCatMktPlace(event)" <?= (in_array('disabledCategoryPermission', $this->permission) == true) ? 'disabled readonly' : '' ?>> Categorias por Marketplace</button>';
                cats = cats + '<div id="catlinkdiv" style="display: none;" >'

                if (data.length === 0) {
                    cats = cats + '<span style="color:red">Categoria não foi ainda associada a nenhum marketplace</span><div class="row"></div>';
                } else {
                    cats = cats + '<table  class="table table-striped table-hover responsive display table-condensed"><thead><tr><th>Marketplace</th><th>Id</th><th>Categoria do Marketplace</th></tr>';
                    for (var campo of data) {
                        cats = cats + '<tr><td>' + campo.int_to + '</td><td>' + campo.category_marketplace_id + '</td><td>' + campo.nome + '</td></tr>';
                    }
                    cats = cats + '</thead></table>';
                }
                if ((update_category) && (id !== '')) {
                    cats = cats + '<a target="__blank" href="' + base_url + 'category/link/' + id + '" class="btn btn-success"><i class="fa fa-pencil"></i> Alterar as Associações da Categoria</a>';
                }
                cats = cats + '</div></div>';
                catlink.append(cats);
            }
        });

        let category_split = $('#category').val().split("-"); //pego o valor 2221-1-10 e separo em id_categoria - bloquio - dia
        let category_name = $('#category').val().replace(category_split[0] + '-' + category_split[1] + '-' + category_split[2] + '-', '');
        let category_id = category_split[0];

        $("#id_categoria").val(category_id); // joga o id da categoria no campo hidden name="category[]"
        $("#name").val(category_name); // joga o nome da categoria no campo name="name[]"

        $('#category_blocked_cross_docking').val(0);
        $('#category_days_cross_docking').val(0);
        $("#days").val(0); // pego o dia atual da tabela linha 902
        $("#days").removeAttr('readonly'); // removeo o bloqueio de campo
        document.getElementById('dv').style.display = 'none';
        if (id) {
            $.ajax({
                type: 'GET',
                dataType: "json",
                url: base_url + 'category/fetchCategoryDataById/' + id,
                success: function (data) {
                    $('#category_blocked_cross_docking').val(data.blocked_cross_docking);
                    $('#category_days_cross_docking').val(data.days_cross_docking);

                    let permission = "<?=$permission; ?>"; //permissão de liberação pelo admin

                    document.getElementById('checkbox_liber').checked = false;  //Cada Troca de categoria reseta o checkbox

                    if (data.blocked_cross_docking == '1') { // se bloqueio
                        $("#days").attr('readonly', true); // desabilita campo
                        $("#days").val(data.days_cross_docking); //mantem pega o dia da categoria bloqueada

                        if (permission == 1) { //se permitido motrar botão de liberar prazoz
                            document.getElementById('dv').style.display = 'block';
                        } else {
                            document.getElementById('dv').style.display = 'none';
                        }
                    } else {
                        $("#days").attr('readonly', false); //habilito campo
                        $("#days").val('<?=$product_data['prazo_operacional_extra'] ?? 0?>');
                        $("#days").removeAttr('readonly'); // removeo o bloqueio de campo
                        document.getElementById('dv').style.display = 'none';
                    }
                }
            });
        } else {
            $("#days").val('<?=$product_data['prazo_operacional_extra'] ?? 0?>');
        }
    }

    const verifyWords = () => {
        const brand = $('#brands').val();
        const category = $('#category').val();
        const store = $('#store').val();
        const sku = $('#sku').val();
        const product = window.location.pathname.indexOf('copy') >= 0 ? 0
            : parseInt(window.location.pathname.split('/').pop());
        const name = $('#product_name').val();
        const description = $('.note-editable').html();

        $.ajax({
            type: "POST",
            data: {
                name,
                description,
                brand,
                category,
                store,
                sku,
                product
            },
            url: base_url + "index.php/products/verifyWords",
            dataType: "json",
            async: true,
            success: function (response) {

                console.log(response);

                if (response.blocked) {
                    let messageBlock = '';

                    $(response.data).each(function (index, value) {
                        messageBlock += `<li>${value}</li>`;
                    });

                    $('#listBlockView ul').empty().html(messageBlock);
                    $('#listBlockView').show();
                } else {
                    $('#listBlockView ul').empty().html('');
                    $('#listBlockView').hide();
                }

            },
            error: function (error) {
                console.log(error)
            }
        });
    }

    function characterLimit(object) {
        var limit = object.getAttribute('maxlength');
        var attribute = object.getAttribute('id');

        if (attribute == 'description') {
            // var quantity = $(".note-editable").text().length;
            var quantity = $(".note-editable").html().length;
        } else {
            var quantity = object.value.length;
        }

        $('#char_' + attribute).text(`<?= $this->lang->line('application_type_char'); ?>${quantity}/${limit}`);
    }


    function checkEAN(ean, field, product_id) {

        var store = document.getElementById("store");
        $.ajax({
            type: "POST",
            enctype: 'multipart/form-data',
            data: {
                ean: ean,
                product_id: product_id,
                store_id: store.value,
            },
            url: base_url + "products/checkEANpost",
            dataType: "json",
            async: true,
            success: function (response) {
                //console.log(response)
                if (response.success) {
                    var id = $("#" + field);
                    id.removeClass('has-error');
                    var id = $("#" + field + 'erro');
                    $("#" + field + 'erro').hide();
                } else {
                    var id = $("#" + field);
                    id.addClass('has-error');
                    $("#" + field + 'erro').html('<i style="color:red">' + response.message + '</i>');
                    $("#" + field + 'erro').show();
                }

            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log(textStatus, errorThrown);
            }
        });

    }

    function AddImage(e, num) {
        e.preventDefault();

        initImage(num);

        $("#addImagemModal").modal('show');

        for (i = 0; i <= varnsomado + 1; i++) {
            $("#showimage" + i).hide();
        }
        $('#variant_num').val(num);
        $("#showimage" + num).show();
    }

    function RemoveVariant(e, num) {
        e.preventDefault();

        var varn = $("#numvar").val();
        varn--;
        if (varn == 0) {
            varn = 1;
        }
        $('#numvar').val(varn);
        $('#showimage' + num).remove();
        $('#variant' + num).remove();

        j = Number(varnoriginal);
        j = 1;
        for (i = j; i <= Number(varnsomado); i++) {
            if ($('#Invar' + i).length != 0) {
                linnum = j - 1;
                $('#Invar' + i).html('<span class="form-control label label-success">' + linnum + '</span>');
                j++;
            }
        }
    }

    function UpdateVariantImage(e) {
        e.preventDefault();

        var num = $("#variant_num").val();
        $("#addImagemModal").modal('hide');

        var tokenimagem = $("#product_image").val();
        tokenimagem = tokenimagem + "/" + $("#IMAGEM" + num).val();
        var onBucket = <?php echo $product_data['is_on_bucket']?> 

        console.log(" update  token " + tokenimagem);
        console.log(" variant_num" + num);
        $.ajax({
            type: "POST",
            enctype: 'multipart/form-data',
            data: {
                tokenimagem: tokenimagem,
                onBucket: onBucket,
            },
            url: base_url + "products/getImagesVariant",
            dataType: "json",
            async: true,
            success: function (response) {
                console.log(response)
                if (response.success) {

                    $("#foto_variant" + num).attr("src", base_url + 'assets/images/system/sem_foto.png');
                    if (response.ln1[0]) {
                        $("#foto_variant" + num).attr("src", response.ln1[0]);
                    }
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log(textStatus, errorThrown);
            }
        });

    }

    function initImage(num, folder = null) {

        var tokenimagem = $("#product_image").val();
        if (folder == null) {
            tokenimagem = tokenimagem + "/" + $("#IMAGEM" + num).val();
        } else {
            tokenimagem = tokenimagem + "/" + folder;
        }
        var onBucket = <?php echo $product_data['is_on_bucket']?>

        console.log('num = ' + num);
        //console.log("#IMAGEM"+num);
        console.log("upload token 1 " + tokenimagem);
        $.ajax({
            type: "POST",
            enctype: 'multipart/form-data',
            data: {
                tokenimagem: tokenimagem,
                variant: num,
                onBucket: onBucket,
            },
            url: base_url + "products/getImagesVariant",
            dataType: "json",
            async: true,
            success: function (response) {
                console.log(response)
                if (response.success) {

                    $("#foto_variant" + num).attr("src", base_url + 'assets/images/system/sem_foto.png');
                    if (response.ln1[0]) {
                        $("#foto_variant" + num).attr("src", response.ln1[0]);
                    }

                    response.ln2.forEach(function (element) {
                        element.extra = function () {
                            return loadAfterFileInputPreview($('#addImagemModal'));
                        };
                    });

                    $("#prd_image_variant" + num).fileinput('destroy').fileinput({
                        uploadUrl: upload_url,
                        language: 'pt-BR',
                        autoOrientImage: false,
                        allowedFileExtensions: ["jpg", "png"],
                        uploadAsync: true,
                        showUpload: false,
                        showRemove: false,
                        showPreview: true,
                        showUploadStats: false,
                        maxTotalFileCount: <?= $limite_imagens_aceitas_api;?>,
                        allowedFileTypes: ['image'], // allow only images
                        showCancel: true,
                        initialPreviewAsData: true,
                        overwriteInitial: false,
                        initialPreview: response.ln1,
                        initialPreviewConfig: response.ln2,
                        theme: 'fas',
                        deleteUrl: delete_url,
                        enableResumableUpload: true,
                        resumableUploadOptions: {},
                        uploadExtraData: {
                            'uploadToken': tokenimagem, // for access control / security
                            'onBucket': onBucket
                        }
                    }).on('filesorted', function (event, params) {
                        changeTheOrderOfImagesVariant(params)
                        console.log('File sorted params', params);
                    }).on('fileuploaded', function (event, previewId, index, fileId) {
                        loadAfterFileInputPreview($('#addImagemModal'));
                        console.log('File Uploaded', 'ID: ' + fileId + ', Thumb ID: ' + previewId);
                    }).on('fileuploaderror', function (event, data, msg) {
                        AlertSweet.fire({
                            icon: 'error',
                            title: 'Erro no upload do arquivo de imagem.<br>Garanta que a imagem seja um jpg com tamanho entre 800x800 e 1200x1200!<br>Faça o ajuste e tente novamente!'
                        });
                        console.log('File Upload Error', 'ID: ' + data.fileId + ', Thumb ID: ' + data.previewId);
                    }).on('filebatchuploadcomplete', function (event, preview, config, tags, extraData) {
                        console.log('File Batch Uploaded', preview, config, tags, extraData);
                    }).on('filebatchselected', function (event, files) {
                        $("#prd_image_variant" + num).fileinput("upload");
                    });
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log(textStatus, errorThrown);
            }
        });

        function changeTheOrderOfImagesVariant(params) {

            $.ajax({
                type: "POST",
                enctype: 'multipart/form-data',
                data: {
                    params: params,
                    onBucket: onBucket
                },
                url: base_url + "products/orderImagesVariant",
                dataType: "json",
                async: false,
                // complete: function(response){
                // 	console.log(response)
                // },
                success: function (success) {
                    console.log(success);
                },
                error: function (error) {
                    console.log(error)
                }
            });
        }

    }

    $('#brands').change(function () {
        verifyWords();
    });
    $('#sku').blur(function () {
        verifyWords();
    });

    function sendToMarketplace(e, int_to, prd_id) {
        e.preventDefault();

        AlertSweet.fire({
            title: '<?= $this->lang->line("do_you_want_to_send_the_product_back_to_the_marketplace") ?>&nbsp' + int_to + ' ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<?= $this->lang->line("application_send") ?>',
            cancelButtonText: '<?= $this->lang->line("application_cancel") ?>'
        }).then((result) => {
            //console.log(result);
            if (result.value) {
                $.ajax({
                    url: base_url + "products/sendToMarketplace",
                    type: "POST",
                    dataType: "json",
                    data: {
                        prd_id: prd_id,
                        int_to: int_to
                    },
                    async: true,
                    success: function (data) {
                        //console.log(data);
                        if (data.status == 'success') {
                            AlertSweet.fire({
                                icon: 'success',
                                title: '<?= $this->lang->line("the_product_has_been_queued_for_transmission_at") ?> ' + int_to,
                                text: '<?= $this->lang->line("within_a_few_moments_refresh_the_screen") ?> '
                            });
                        } else {
                            AlertSweet.fire({
                                icon: 'error',
                                title: '<?= $this->lang->line("there_was_an_error_placing_the_product_in_the_queue") ?>'
                            });
                        }

                    },
                    error: function (data) {
                        AlertSweet.fire({
                            icon: 'error',
                            title: '<?= $this->lang->line("there_was_an_error_placing_the_product_in_the_queue") ?>'
                        });
                    }
                });
            }
        });
    }

    $(document).on('click', '.kv-avatar .file-preview .file-actions .kv-file-remove', function(){
        setTimeout(() => {
            if (!$('.kv-avatar .file-preview .kv-fileinput-error.file-error-message ul li').length) {
                $('.kv-avatar .file-preview .kv-fileinput-error.file-error-message').hide();
            }
        }, 1250);
    })

    $(document).on('click', '.btn-tooltip-sku-integartion', function(){
        $('#field-tooltip-sku-integartion').val($(this).data('original-title').replace('SKU Integração: ', '')).attr('type', 'text').select();

        // Copia o conteudo selecionado
        const copy = document.execCommand('copy');
        if (copy) {
            Toast.fire({
                icon: 'success',
                title: "Código copiado com sucesso!"
            })
        } else {
            Toast.fire({
                icon: 'success',
                title: "Não foi possível copiar o código!"
            })
        }

        $(this).tooltip('enable')

        $('#field-tooltip-sku-integartion').attr('type', 'hidden');
    })
</script>
<?php if ($limite_variacoes == 1) : ?>
    <script>
        // Seletor para todos os checkboxes
        //var checkboxes = document.querySelectorAll('.form-check-input');
        var checkboxes = document.querySelectorAll('.checkbox-limited');
        var maxCheckboxes = 2;

        checkboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                var checkedCheckboxes = document.querySelectorAll('.checkbox-limited:checked').length;

                if (checkedCheckboxes >= maxCheckboxes) {
                    checkboxes.forEach(function (cb) {
                        if (!cb.checked) {
                            cb.disabled = true;
                        }
                    });
                } else {
                    checkboxes.forEach(function (cb) {
                        cb.disabled = false;
                    });
                }
            });
        });
    </script>
<?php endif; ?>
<script src="<?php echo base_url('assets/dist/js/components/products/hack/form.product.js') ?>"></script>