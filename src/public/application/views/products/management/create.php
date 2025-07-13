<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>

<?php
$mainImage = lang('application_main_image') ?? 'Imagem principal';
$hasVariation = ($this->input->post('with_variation', true) ?? 0) == 0 ? 0 : 1;

$liberado = isset($product_data['prazo_fixo']) ? ($product_data['prazo_fixo']) : 0;// liberado maior prazo ou não
if ((in_array('changeCrossdocking', $this->permission))) { // Se Permitido Mostrar caixa de liberação de alteração de prazo
    $permission = true;
} else {
    $permission = false;
}

?>
<link rel="stylesheet" href="<?php echo base_url('assets/dist/css/views/products/hack/form.product.css') ?>">
<style>
    .file-preview-thumbnails.clearfix > div:first-child > div:first-child::before {
        content: '<?=$mainImage?>';
    }
</style>
<div class="content-wrapper" style="">
    <?php $data['pageinfo'] = "application_add";
    $this->load->view('templates/content_header', $data);
    $limite_imagens_aceitas_api = $this->CI->model_settings->getValueIfAtiveByName('limite_imagens_aceitas_api') ?? 5;
    if (!isset($limite_imagens_aceitas_api) || $limite_imagens_aceitas_api <= 0) {
        $limite_imagens_aceitas_api = 5;
    }

    $limite_variacoes = $this->CI->model_settings->getLimiteVariationActive();
    if(!empty($limite_variacoes)) {
        $limite_variacoes = 1;
    }else{
        $limite_variacoes = 0;
    }


    $numft = 0;
    $ln1 = array();
    $ln2 = array();
    if ($upload_image) {

        // Prefixo de url para buscar a imagem.
        $asset_prefix = "assets/images/product_image/" . $upload_image. "/";

        // Busca as imagens do produto já formatadas.
        $product_images = $this->bucket->getFinalObject($asset_prefix);

        // Caso tenha dado certo, busca o conteudo.
        if ($product_images['success']) {
            // Percorre cada elemento e verifica se não é imagem de variação.
            foreach ($product_images['contents'] as $key => $image_data) {
                // Monta a chave da imagem completa.
                $full_key = $upload_image . '/' . $image_data['key'];
                $numft++;
                $ln1[$numft] = $image_data['url'];
                $ln2[$numft] = '{width: "120px", key: "' . $full_key . '"}';
            }
        }
    }
    ?>
    <section class="content">
        <div class="col-md-12 no-padding">
            <div class="row" style="margin-top: 10px">
                <div class="col-md-12">
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
                <div class="col-md-12">
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
                </div>
            </div>
            <div class="row">
                <form action="<?php base_url('products/create') ?>" method="post" enctype="multipart/form-data"
                      id="formInsertProduct">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="box" style="min-height: 380px;">
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h2>
                                                    <?= lang('application_main_informations') ?>
                                                </h2>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="form-group col-md-12 col-xs-12">
                                                <label for="product_name"><?= lang('application_name'); ?>
                                                    (*)</label>
                                                <input type="text" class="form-control" id="product_name"
                                                       onkeyup="characterLimit(this)"
                                                       maxlength="<?= $product_length_name ?>" onchange="verifyWords()"
                                                       name="product_name" required
                                                       placeholder="<?= lang('application_enter_product_name'); ?>"
                                                       value="<?php echo set_value('product_name') ?>"
                                                       autocomplete="off"/>
                                                <span id="char_product_name"></span><br/>
                                                <span class="label label-warning" id="words_product_name"
                                                      data-toggle="tooltip" data-placement="top"
                                                      title="<?= lang('application_explanation_of_forbidden_words') ?>"></span>
                                            </div>

                                            <div class="form-group col-md-9 col-xs-12">
                                                <label for="sku"><?= lang('application_sku'); ?>(*)</label>
                                                <input type="text" class="form-control" id="sku" name="sku" required
                                                       placeholder="<?= lang('application_enter_sku'); ?>"
                                                       value="<?php echo set_value('sku') ?>" autocomplete="off"
                                                       onKeyUp="checkSpecialSku(event, this);characterLimit(this);"
                                                       onblur="checkSpecialSku(event, this);"
                                                       maxlength="<?= $product_length_sku ?>"/>
                                                <span id="char_sku"></span><br/>
                                            </div>
                                            <div class="form-group col-md-4 col-xs-6">
                                                <label for="status"><?= $this->lang->line('application_availability'); ?>
                                                    (*)</label>
                                                <input type="hidden" id="status" name="status"
                                                       value="<?= $this->input->post('status', true) ?? 1 ?>">
                                                <div id="ProductStatusComponent"
                                                     data-base-url="<?= base_url() ?>"
                                                     data-endpoint="products"
                                                     data-ref-element="$('#status')"
                                                     data-origin="products/create"
                                                     data-product-status="<?= $this->input->post('status', true) ?? 1 ?>"
                                                     data-product-id="0"
                                                     data-enable-delete="false"
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
                                                <label for="with_variation"><?= $this->lang->line('application_product_with_variation'); ?>?</label>
                                                <input type="hidden" id="with_variation" name="with_variation"
                                                       value="<?= $hasVariation ?>">
                                                <div id="RadioTwoOptionsComponent"
                                                     data-active-value="1"
                                                     data-enabled="1"
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
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="" id="addImagemForm">
                                    <div class="box">
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
                                                            <input type="file" id="prd_image" name="prd_image[]"
                                                                   accept="image/*" multiple>
                                                        </div>
                                                    </div>
                                                    <input type="hidden" name="product_image" id="product_image"
                                                           value="<?php echo set_value('product_image') ?>"/>
                                                </div>
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
                                                    <div class="form-check col-md-1 col-xs-12" style="display:none;">
                                                        <input type="checkbox" class="form-check-input" id="semvar"
                                                               name="semvar" <?= set_checkbox('semvar', 'on', true) ?>>
                                                        <label for="semvar"><?= lang('application_without_variations'); ?> </label>
                                                    </div>
                                                    <div class="form-check col-md-1 col-xs-12">
                                                        <input type="checkbox" class="form-check-input checkbox-limited" id="sizevar"
                                                               name="sizevar" <?= set_checkbox('sizevar', 'on', false) ?>>
                                                        <label for="sizevar"><?= lang('application_size'); ?></label>
                                                    </div>
                                                    <div class="form-check col-md-1 col-xs-12">
                                                        <input type="checkbox" class="form-check-input checkbox-limited" id="colorvar"
                                                               name="colorvar" <?= set_checkbox('colorvar', 'on', false) ?>>
                                                        <label for="colorvar"><?= lang('application_color'); ?></label>
                                                    </div>
                                                    <div class="form-check col-md-1 col-xs-12">
                                                        <input type="checkbox" class="form-check-input checkbox-limited" id="voltvar"
                                                               name="voltvar" <?= set_checkbox('voltvar', 'on', false) ?>>
                                                        <label for="voltvar"><?= lang('application_voltage'); ?></label>
                                                    </div>
                                                    <div class="form-check col-md-1 col-xs-12" <?= ($flavor_active == '' ? 'hidden' : '') ?>>
                                                        <input type="checkbox" class="form-check-input checkbox-limited" id="saborvar"
                                                               name="saborvar" <?= set_checkbox('saborvar', 'on', false) ?>>
                                                        <label for="saborvar"><?= lang('application_flavor'); ?></label>
                                                    </div>
                                                    <div class="form-check col-md-1 col-xs-12" <?= ($degree_active == '' ? 'hidden' : '') ?>>
                                                        <input type="checkbox" class="form-check-input checkbox-limited" id="grauvar"
                                                               name="grauvar" <?= set_checkbox('grauvar', 'on', false) ?>>
                                                        <label for="grauvar"><?= lang('application_degree'); ?></label>
                                                    </div>
                                                    <div class="form-check col-md-1 col-xs-12" <?= ($side_active == '' ? 'hidden' : '') ?>>
                                                        <input type="checkbox" class="form-check-input checkbox-limited" id="ladovar"
                                                               name="ladovar" <?= set_checkbox('ladovar', 'on', false) ?>>
                                                        <label for="ladovar"><?= lang('application_side'); ?></label>
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
                                            <!-- Variants DIV -->
                                            <div id="variantModal" class="col-md-12 col-xs-12" style="display:none;">
                                                <input type="hidden" name="numvar" id="numvar" value="<?php echo set_value('numvar', '1') ?>"/>
                                                <input type="hidden" name="from" value="allocate"/>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="row">
                                                            <div id="Ltvar" class="col-md-1"
                                                                 style="padding-right: 3px;">
                                                                <label><?= lang('application_size'); ?>
                                                                    (*)</label>
                                                            </div>
                                                            <div id="Lcvar" class="col-md-1"
                                                                 style="padding-right: 3px;">
                                                                <label><?= lang('application_color'); ?>
                                                                    (*)</label>
                                                            </div>
                                                            <div id="Lvvar" class="col-md-1"
                                                                 style="padding-right: 3px;">
                                                                <label><?= lang('application_voltage'); ?>
                                                                    (*)</label>
                                                            </div>
                                                            <div id="Lsvar" class="col-md-1"
                                                                 style="padding-right: 3px;">
                                                                <label><?= lang('application_flavor'); ?>
                                                                    (*)</label>
                                                            </div>
                                                            <div id="Lgvar" class="col-md-1"
                                                                 style="padding-right: 3px;">
                                                                <label><?= lang('application_degree'); ?>
                                                                    (*)</label>
                                                            </div>
                                                            <div id="Llvar" class="col-md-1"
                                                                 style="padding-right: 3px;">
                                                                <label><?= lang('application_side'); ?>
                                                                    (*)</label>
                                                            </div>
                                                            <div id="Lqvar" class="col-md-1"
                                                                 style="padding-right: 3px;">
                                                                <label><?= lang('application_stock'); ?>
                                                                    (*)</label>
                                                            </div>
                                                            <div id="Lskuvar" class="col-md-1"
                                                                 style="padding-right: 3px;">
                                                                <label><?= lang('application_sku'); ?>
                                                                    (*)</label>
                                                            </div>
                                                            <div id="Leanvar" class="col-md-2"
                                                                 style="padding-right: 3px;">
                                                                <label><?= lang('application_ean'); ?></label>
                                                            </div>
                                                            <?php if ($displayPriceByVariation == '1') { ?>
                                                                <div id="Lpricevar" class="col-md-1"
                                                                     style="padding-right: 3px;">
                                                                    <label><?= lang('application_list_price'); ?></label>
                                                                </div>
                                                                <div id="Lpricevar" class="col-md-1"
                                                                     style="padding-right: 3px;">
                                                                    <label><?= lang('application_new_price'); ?></label>
                                                                </div>
                                                            <?php } ?>
                                                            <div class="col-md-1" style="padding-right: 3px;">
                                                                <label style="white-space: nowrap"><?= lang('application_enter_images_optional'); ?></label>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="Itvar col-md-1"
                                                                 style="padding-right: 3px;">
                                                                <input type="text" class="form-control" id="T[]"
                                                                       name="T[]"
                                                                       autocomplete="off" placeholder="Tamanho"
                                                                       value="<?php echo set_value('T[0]') ?>"/>
                                                            </div>
                                                            <div class="Icvar col-md-1"
                                                                 style="padding-right: 3px;">
                                                                <input type="text" class="form-control" id="C[]"
                                                                       name="C[]"
                                                                       autocomplete="off"
                                                                       placeholder="<?= lang('application_color') ?>"
                                                                       value="<?php echo set_value('C[0]') ?>"/>
                                                            </div>
                                                            <div class="Ivvar col-md-1"
                                                                 style="padding-right: 3px;">
                                                                <input type="text" class="form-control" id="V[]" name="V[]" autocomplete="off" placeholder="<?= $this->lang->line('application_voltage'); ?>" value="<?php echo set_value('V[0]') ?>" />
                                                            </div>
                                                            <div class="Isvar col-md-1"
                                                                 style="padding-right: 3px;">
                                                                <input type="text" class="form-control" id="sb[]"
                                                                       name="sb[]"
                                                                       autocomplete="off"
                                                                       placeholder="<?= lang('application_flavor'); ?>"
                                                                       value="<?php echo set_value('sb[0]') ?>"/>
                                                            </div>
                                                            <div class="Igvar col-md-1"
                                                                 style="padding-right: 3px;">
                                                                <input type="text" class="form-control" id="gr[]"
                                                                       name="gr[]"
                                                                       autocomplete="off"
                                                                       placeholder="<?= lang('application_degree'); ?>"
                                                                       value="<?php echo set_value('gr[0]') ?>"/>
                                                            </div>
                                                            <div class="Ilvar col-md-1"
                                                                 style="padding-right: 3px;">
                                                                <input type="text" class="form-control" id="ld[]"
                                                                       name="ld[]"
                                                                       autocomplete="off"
                                                                       placeholder="<?= lang('application_side'); ?>"
                                                                       value="<?php echo set_value('ld[0]') ?>"/>
                                                            </div>
                                                            <div class="Iqvar col-md-1"
                                                                 style="padding-right: 3px;">
                                                                <input type="text" class="form-control" id="Q[]"
                                                                       name="Q[]"
                                                                       autocomplete="off" placeholder="Estoque"
                                                                       onKeyPress="return digitos(event, this);"
                                                                       value="<?php echo set_value('Q[0]') ?>"/>
                                                            </div>
                                                            <div class="Iskuvar col-md-1"
                                                                 style="padding-right: 3px;">
                                                                <input type="text" class="form-control" id="SKU_V_0"
                                                                       name="SKU_V[]"
                                                                       autocomplete="off" placeholder="SKU Variação"
                                                                       value="<?php echo set_value('SKU_V[0]') ?>"
                                                                       onKeyUp="checkSpecialSku(event, this);characterLimit(this);"
                                                                       onblur="checkSpecialSku(event, this);"
                                                                       maxlength="<?= $product_length_sku ?>"/>
                                                                <span id="char_SKU_V_<?= 0 ?>"></span><br/>
                                                            </div>
                                                            <div id="EANV0"
                                                                 class="Ieanvar col-md-2 <?php echo (form_error('EAN_V[0]')) ? 'has-error' : ''; ?>"
                                                                 style="padding-right: 3px;">
                                                                <input type="text" class="form-control" id="EAN_V[]"
                                                                       name="EAN_V[]"
                                                                       autocomplete="off" placeholder="EAN Variação"
                                                                       value="<?php echo set_value('EAN_V[0]') ?>"
                                                                       onchange="checkEAN(this.value,'EANV0')"/>
                                                                <span id="EANV0erro" style="display: none;"><i
                                                                            style="color:red"><?= $invalid_ean; ?></i></span>
                                                                <?php echo '<i style="color:red">' . form_error('EAN_V[0]') . '</i>'; ?>
                                                            </div>
                                                            <?php if ($displayPriceByVariation == '1') { ?>
                                                                <div class="Ilistpricevar col-md-1"
                                                                     style="padding-right: 3px;">
                                                                    <input type="text" class="form-control maskdecimal2"
                                                                           id="LIST_PRICE_V[]"
                                                                           name="LIST_PRICE_V[]" autocomplete="off"
                                                                           placeholder="Preço De - Variação"
                                                                           value="<?php echo set_value('LIST_PRICE_V[0]') ?>"/>
                                                                </div>
                                                                <div class="Ipricevar col-md-1"
                                                                     style="padding-right: 3px;">
                                                                    <input type="text" class="form-control maskdecimal2"
                                                                           id="PRICE_V[]"
                                                                           name="PRICE_V[]" autocomplete="off"
                                                                           placeholder="Preço Por - Variação"
                                                                           value="<?php echo set_value('PRICE_V[0]') ?>"/>
                                                                </div>
                                                            <?php } ?>
                                                            <div class="col-md-1" style="padding-right: 3px;">
                                                                <input type="hidden" id="IMAGEM0" name="IMAGEM[]"
                                                                       value="<?php echo set_value('IMAGEM[0]', $imagemvariant0) ?>"/>
                                                                <a href="#" onclick="AddImage(event,'0')"><img
                                                                            id="foto_variant0"
                                                                            alt="foto"
                                                                            src="<?php echo base_url('assets/images/system/sem_foto.png'); ?>"
                                                                            class="img-rounded"
                                                                            width="40"
                                                                            height="40"/><i
                                                                            class="fa fa-plus-circle"
                                                                            style="margin-left: 6px"></i></a>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="input_fields_wrap">

                                                </div>
                                                <?php echo '<i style="color:red">' . form_error('SKU_V[]') . '</i>'; ?>

                                                <small class="text-danger"><strong><?= lang('messages_warning_create_sku_variant') ?></strong></small>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <button type="button" class="btn btn-primary add_line"><i
                                                            class="fa fa-plus-square-o"></i> <?= lang('application_variation_add'); ?>
                                                </button>
                                                <button type="button" class="btn btn-danger" id="reset_variant"
                                                        name="reset_variant"><i
                                                            class="fa fa-trash"></i> <?= lang('application_clear'); ?>
                                                </button>
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
                                                <input type='hidden' id='name' name="name" value="">
                                                <input type='hidden' id='id_categoria' name="category[]"
                                                       value="<?php echo $idCategory_id = isset($idCategory_id) ? $idCategory_id : ""; ?>">
                                                <?php if((in_array('disabledCategoryPermission', $this->permission) == true)){ ?>
                                                    <span>A categorização dos produtos é realizada pelo marketplace</span>
                                                <?php } ?>
                                                <select class="form-control"
                                                        data-live-search="true"
                                                        data-actions-box="true" id='category'
                                                        custo='prazo_operacional_extra'
                                                        title="<?= lang('application_select'); ?>" <?= (in_array('disabledCategoryPermission', $this->permission) == true) ? 'disabled readonly' : '' ?>>
                                                    <option value=""><?= lang('application_select'); ?></option>
                                                    <?php foreach ($category as $k => $v) {
                                                        $category_data = isset($category_data) ? $category_data : array();
                                                        $disabledCategory = (isset($integracoes) && (!in_array($v['id'], $category_data)) && $notAdmin) ? 'disabled' : '';
                                                        $blocked_cross_docking = $v['blocked_cross_docking'];
                                                        $days_cross_docking = $v['days_cross_docking']; ?>
                                                        <option <?php echo set_select('category', $v['id'], in_array($v['id'], $category_data)) ?>
                                                                value="<?php echo $v['id'] . '-' . $blocked_cross_docking . '-' . $days_cross_docking . '-' . $v['name'] ?>" <?= $disabledCategory ?> <?php echo $v['id']; ?>>  <?php echo $v['name'] ?><?php echo ($v['blocked_cross_docking'] == 1) ? '( Com Bloqueio de Prazo de ' . $days_cross_docking . ' dias )' : '' ?></option>
                                                    <?php } ?>
                                                </select>
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
                                            <div class="form-group col-md-12 col-xs-12  <?php echo (form_error('description')) ? 'has-error' : ''; ?>">
                                            <textarea type="text" class="form-control" id="description"
                                                      maxlength="<?= $product_length_description ?>" name="description"
                                                      placeholder="<?= lang('application_enter_description'); ?>"><?php echo set_value('description') ?></textarea>
                                                <span id="char_description"></span><br/>
                                                <span class="label label-warning" id="words_description"
                                                      data-toggle="tooltip" data-placement="top"
                                                      title="<?= lang('application_explanation_of_forbidden_words'); ?>"></span>
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
                                            <div class="form-group col-lg-3 col-md-4 col-xs-12">
                                                <label for="price"
                                                       class="d-flex justify-content-between"><?= lang('application_list_price'); ?>
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-addon"><strong>R$</strong></span>
                                                    <input type="text" class="form-control maskdecimal2" id="list_price"
                                                           name="list_price"
                                                           placeholder="<?= lang('application_enter_price'); ?>"
                                                           value="<?php echo set_value('list_price') ?>"
                                                           autocomplete="off"/>
                                                </div>
                                            </div>
                                            <div class="form-group col-lg-3 col-md-4 col-xs-12">
                                                <label for="price"
                                                       class="d-flex justify-content-between"><?= lang('application_new_price'); ?>
                                                    (*)
                                                    <?php if ($displayPriceByVariation == '1') { ?>
                                                        <i style="color:red;" class="fa fa-info-circle"
                                                           aria-hidden="true"
                                                           data-toggle="tooltip" data-placement="top"
                                                           title="<?= lang('messages_price_variations_price_product'); ?>"></i>
                                                    <?php } ?>
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-addon"><strong>R$</strong></span>
                                                    <input type="text" class="form-control maskdecimal2" id="price"
                                                           name="price" required
                                                           placeholder="<?= lang('application_enter_price'); ?>"
                                                           value="<?php echo set_value('price') ?>" autocomplete="off"/>
                                                </div>
                                                <div class="input-group">
                                                    <?php
                                                    if (count($integrations) > 0) {
                                                        ?>
                                                        <table class="table table-striped table-hover responsive display table-condensed">
                                                            <thead>
                                                            <tr>
                                                                <th style="width: 40%"><?= lang('application_marketplace'); ?></th>
                                                                <th><?= lang('application_price_marketplace'); ?></th>
                                                            </tr>

                                                            <?php
                                                            foreach ($integrations as $integration) {
                                                                ?>
                                                                <tr>
                                                                    <td style="width: 40%"><?php echo $integration['int_to']; ?>
                                                                        <br>
                                                                        <input type="checkbox"
                                                                               name="samePrice_<?= $integration['id'] ?>" <?= set_checkbox('samePrice_' . $integration['id'], 'on', true) ?>
                                                                               id="samePrice_<?= $integration['id'] ?>"
                                                                               onchange="samePrice(<?= $integration['id'] ?>)">
                                                                        <small><?= lang('application_same_price'); ?></small>
                                                                    </td>
                                                                    <td>
                                                                        <div class="input-group">
                                                                            <span class="input-group-addon"><small><strong>R$</strong></small></span>
                                                                            <input type="text"
                                                                                   class="form-control maskdecimal2"
                                                                                   id="price_<?= $integration['id'] ?>"
                                                                                   name="price_<?= $integration['id'] ?>"
                                                                                   required
                                                                                   placeholder="<?= lang('application_enter_price'); ?>"
                                                                                   value="<?php echo set_value('price_' . $integration['id']); ?>"
                                                                                   autocomplete="off"/>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                                <?php
                                                            }
                                                            ?>
                                                            </thead>
                                                        </table>
                                                        <?php
                                                    }
                                                    ?>
                                                </div>

                                            </div>

                                            <div class="form-group col-lg-2 col-md-3 col-xs-12">
                                                <label for="qty"><?= lang('application_qty'); ?>(*)</label>
                                                <input type="text" class="form-control" id="qty" name="qty" required
                                                       placeholder="<?= lang('application_enter_qty'); ?>"
                                                       value="<?php echo set_value('qty') ?>" autocomplete="off"
                                                       onKeyPress="return digitos(event, this);"/>
                                                <div class="input-group" id="qty_marketplace">
                                                    <?php
                                                    $temHub = false;
                                                    foreach ($integrations as $integration) {
                                                        if ($integration['int_from'] == 'HUB') {
                                                            $temHub = true;
                                                            break;
                                                        }
                                                    }
                                                    $temHub = false;  // desligado por enquanto
                                                    if ($temHub) {
                                                        ?>
                                                        <table class="table table-striped table-hover responsive display table-condensed">
                                                            <thead>
                                                            <tr>
                                                                <th style="width: 40%"><?= lang('application_marketplace'); ?></th>
                                                                <th><?= lang('application_price_marketplace'); ?></th>
                                                            </tr>

                                                            <?php
                                                            foreach ($integrations as $integration) {
                                                                if ($integration['int_from'] == 'HUB') {
                                                                    ?>
                                                                    <tr>
                                                                        <td style="width: 40%"><?php echo $integration['int_to']; ?>
                                                                            <br>
                                                                            <input type="checkbox" class="sameqtychk"
                                                                                   name="sameQty_<?= $integration['id'] ?>" <?= set_checkbox('sameQty_' . $integration['id'], 'on', true) ?>
                                                                                   id="sameQty_<?= $integration['id'] ?>"
                                                                                   onchange="sameQty(<?= $integration['id'] ?>)">
                                                                            <small><?= lang('application_same_qty'); ?></small>
                                                                        </td>
                                                                        <td>
                                                                            <input type="text"
                                                                                   class="form-control sameqtyval"
                                                                                   id="qty_<?= $integration['id'] ?>"
                                                                                   onKeyPress="return digitos(event, this)"
                                                                                   onchange="changeQtyMkt(<?= $integration['id'] ?>)"
                                                                                   name="qty_<?= $integration['id'] ?>"
                                                                                   required
                                                                                   placeholder="<?= lang('application_enter_qty'); ?>"
                                                                                   value="<?php echo set_value('qty_' . $integration['id']); ?>"
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
                                                 class="form-group col-lg-4 col-md-5 col-xs-12 <?php echo (form_error('EAN')) ? "has-error" : ""; ?>">
                                                <label for="EAN"><?= lang('application_ean'); ?><?= ($require_ean) ? "*" : "" ?></label>
                                                <input type="text" class="form-control" id="EAN" name="EAN"
                                                       onchange="checkEAN(this.value,'EANDIV')" <?= ($require_ean) ? "required" : "" ?>
                                                       placeholder="<?= lang('application_enter_ean'); ?>"
                                                       value="<?php echo set_value('EAN') ?>" autocomplete="off"/>
                                                <?php echo '<i style="color:red">' . form_error('EAN') . '</i>'; ?>
                                                <div id="EANDIVerro" style="display: none;"><i
                                                            style="color:red"><?= $invalid_ean; ?></i></div>
                                            </div>

                                            <div class="form-group col-lg-4 col-md-5 col-xs-12">
                                                <label for="codigo_do_fabricante"><?= lang('application_brandcode'); ?></label>
                                                <input type="text" class="form-control" id="codigo_do_fabricante"
                                                       name="codigo_do_fabricante"
                                                       placeholder="<?= lang('application_enter_manufacturer_code'); ?>"
                                                       value="<?php echo set_value('codigo_do_fabricante') ?>"
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
                                            <div class="">
                                                <div class="form-group col-lg-3 col-md-5 col-sm-6 col-xs-12">
                                                    <label for="peso_liquido"><?= lang('application_net_weight'); ?>
                                                        (*)</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control maskdecimal3"
                                                               id="peso_liquido" required name="peso_liquido"
                                                               placeholder="<?= lang('application_enter_net_weight'); ?>"
                                                               value="<?php echo set_value('peso_liquido') ?>"
                                                               autocomplete="off"/>
                                                        <span class="input-group-addon"><strong>Kg</strong></span>
                                                    </div>
                                                </div>
                                                <div class="form-group col-lg-3 col-md-5 col-sm-6 col-xs-12 <?php echo (form_error('actual_width')) ? 'has-error' : ''; ?>">
                                                    <label for="actual_width"><?= lang('application_width'); ?></label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control maskdecimal2"
                                                               id="actual_width" name="actual_width"
                                                               placeholder="<?= lang('application_enter_actual_width'); ?>"
                                                               value="<?php echo set_value('actual_width') ?>"
                                                               autocomplete="off"
                                                               onKeyPress="return digitos(event, this);"/>
                                                        <span class="input-group-addon"><strong>cm</strong></span>
                                                    </div>
                                                    <?php echo '<i style="color:red">' . form_error('actual_width') . '</i>'; ?>
                                                </div>
                                                <div class="form-group col-lg-3 col-md-5 col-sm-6 col-xs-12 <?php echo (form_error('actual_height')) ? 'has-error' : ''; ?>">
                                                    <label for="actual_height"><?= lang('application_height'); ?></label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control maskdecimal2"
                                                               id="actual_height" name="actual_height"
                                                               placeholder="<?= lang('application_enter_actual_height'); ?>"
                                                               value="<?php echo set_value('actual_height') ?>"
                                                               autocomplete="off"
                                                               onKeyPress="return digitos(event, this);"/>
                                                        <span class="input-group-addon"><strong>cm</strong></span>
                                                    </div>
                                                    <?php echo '<i style="color:red">' . form_error('actual_height') . '</i>'; ?>
                                                </div>
                                                <div class="form-group col-lg-3 col-md-5 col-sm-6 col-xs-12 <?php echo (form_error('actual_depth')) ? 'has-error' : ''; ?>">
                                                    <label for="actual_depth"><?= lang('application_depth'); ?></label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control maskdecimal2"
                                                               id="actual_depth" name="actual_depth"
                                                               placeholder="<?= lang('application_enter_actual_depth'); ?>"
                                                               value="<?php echo set_value('actual_depth') ?>"
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
                                            <div class="">
                                                <div class="form-group col-lg-3 col-md-5 col-sm-6 col-xs-12">
                                                    <label for="peso_bruto"><?= lang('application_weight'); ?>
                                                        (*)</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control maskdecimal3"
                                                               id="peso_bruto" name="peso_bruto" required
                                                               placeholder="<?= lang('application_enter_gross_weight'); ?>"
                                                               value="<?php echo set_value('peso_bruto') ?>"
                                                               autocomplete="off"/>
                                                        <span class="input-group-addon"><strong>Kg</strong></span>
                                                    </div>
                                                </div>
                                                <div class="form-group col-lg-3 col-md-5 col-sm-6 col-xs-12 <?php echo (form_error('largura')) ? 'has-error' : ''; ?>">
                                                    <label for="largura"><?= lang('application_width'); ?>
                                                        (*)</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control maskdecimal2"
                                                               id="largura" name="largura" required
                                                               placeholder="<?= lang('application_enter_width'); ?>"
                                                               value="<?php echo set_value('largura') ?>"
                                                               autocomplete="off"
                                                               onKeyPress="return digitos(event, this);"/>
                                                        <span class="input-group-addon"><strong>cm</strong></span>
                                                    </div>
                                                    <?php echo '<i style="color:red">' . form_error('largura') . '</i>'; ?>
                                                </div>
                                                <div class="form-group col-lg-3 col-md-5 col-sm-6 col-xs-12 <?php echo (form_error('altura')) ? 'has-error' : ''; ?>">
                                                    <label for="altura"><?= lang('application_height'); ?>
                                                        (*)</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control maskdecimal2"
                                                               id="altura" name="altura" required
                                                               placeholder="<?= lang('application_enter_height'); ?>"
                                                               value="<?php echo set_value('altura') ?>"
                                                               autocomplete="off"
                                                               onKeyPress="return digitos(event, this);"/>
                                                        <span class="input-group-addon"><strong>cm</strong></span>
                                                    </div>
                                                    <?php echo '<i style="color:red">' . form_error('altura') . '</i>'; ?>
                                                </div>
                                                <div class="form-group col-lg-3 col-md-5 col-sm-6 col-xs-12 <?php echo (form_error('profundidade')) ? 'has-error' : ''; ?>">
                                                    <label for="profundidade"><?= lang('application_depth'); ?>
                                                        (*)</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control maskdecimal2"
                                                               id="profundidade" name="profundidade" required
                                                               placeholder="<?= lang('application_enter_depth'); ?>"
                                                               value="<?php echo set_value('profundidade') ?>"
                                                               autocomplete="off"
                                                               onKeyPress="return digitos(event, this);"/>
                                                        <span class="input-group-addon"><strong>cm</strong></span>
                                                    </div>
                                                    <?php echo '<i style="color:red">' . form_error('profundidade') . '</i>'; ?>
                                                </div>
                                                <div class="form-group col-lg-3 col-md-5 col-sm-6 col-xs-12">
                                                    <label for="products_package" data-toggle="tooltip"
                                                           data-placement="top"
                                                           title="<?= lang('application_how_many_units'); ?>"><?= lang('application_products_by_packaging'); ?>
                                                        (*)</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control maskdecimal3"
                                                               id="products_package" name="products_package"
                                                               required
                                                               placeholder="<?= lang('application_enter_quantity_products'); ?>"
                                                               value="<?php echo set_value('products_package', '1') ?>"
                                                               autocomplete="off"/>
                                                        <span class="input-group-addon"><strong>Qtd</strong></span>
                                                    </div>
                                                </div>
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
                                                    <div class="form-group col-lg-4 col-md-5 col-xs-12">
                                                        <label for="brands" class="d-flex justify-content-between">
                                                            <?= lang('application_brands'); ?>(*)
                                                            <?php if (!$disableBrandCreationbySeller) { ?>
                                                                <a href="#" onclick="AddBrand(event)"><i
                                                                            class="fa fa-plus-circle"></i> <?= lang('application_add_brand'); ?>
                                                                </a>
                                                            <?php } ?>
                                                        </label>
                                                        <?php $brand_data = $this->input->post('brands', true); ?>

                                                        <select class="form-control selectpicker show-tick"
                                                                data-live-search="true" data-actions-box="true"
                                                                id="brands"
                                                                name="brands[]"
                                                                title="<?= lang('application_select'); ?>">

                                                            <option value=""><?= lang('application_select'); ?></option>
                                                            <?php foreach ($brands as $k => $v) : ?>
                                                                <option value="<?php echo $v['id'] ?>" <?php echo set_select('brands', $v['id'], false); ?>><?php echo $v['name'] ?></option>
                                                            <?php endforeach ?>
                                                        </select>

                                                    </div>
                                                    <div class="form-group col-lg-4 col-md-5 col-xs-12">
                                                        <label for="store"><?= lang('application_store'); ?>
                                                            (*)</label>
                                                        <select class="form-control select_group" id="store"
                                                                name="store">
                                                            <?php foreach ($stores as $k => $v) : ?>
                                                                <option value="<?php echo $v['id'] ?>" <?php echo set_select('store', $v['id'], false); ?>><?php echo $v['name'] ?></option>
                                                            <?php endforeach ?>
                                                        </select>
                                                    </div>
                                                    <div class="form-group col-lg-4 col-md-8 col-xs-12">
                                                        <label for="origin"><?= lang('application_origin_product'); ?>
                                                            (*)</label>
                                                        <select class="form-control" name="origin" id="origin" required>
                                                            <?php foreach ($origins as $key => $origin) {
                                                                echo "<option value='{$key}' " . set_select('origin', $key) . ">{$origin}</option>";
                                                            } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <?php $att = $this->input->post('attributes_value_id', true);
                                                    $i = 0;
                                                    if ($attributes) : ?>
                                                        <?php foreach ($attributes as $k => $v) : ?>
                                                            <div class="form-group col-lg-4 col-md-5 col-xs-12">
                                                                <label for="groups"><?php echo $v['attribute_data']['name'] ?>
                                                                    (*)</label>
                                                                <select class="form-control select_group"
                                                                        id="attributes_value_id"
                                                                        name="attributes_value_id[]">
                                                                    <?php foreach ($v['attribute_value'] as $k2 => $v2) : ?>
                                                                        <option value="<?php echo $v2['id'] ?>" <?= set_select('attributes_value_id', $v2['id']) ?>><?php echo $v2['value'] ?></option>
                                                                    <?php endforeach ?>
                                                                </select>
                                                            </div>
                                                            <?php
                                                            $i++;
                                                        endforeach ?>
                                                    <?php endif; ?>
                                                    <div class="form-group col-lg-2 col-md-2 col-xs-12">
                                                        <label for="NCM"><?= lang('application_NCM'); ?></label>
                                                        <input type="text" class="form-control" id="NCM" name="NCM"
                                                               placeholder="<?= lang('application_enter_NCM'); ?>"
                                                               value="<?php echo set_value('NCM'); ?>" maxlength="10"
                                                               size="10"
                                                               onKeyPress="return digitos(event, this);"
                                                               onKeyDown="Mascara('NCM',this,event);"
                                                               autocomplete="off"/>
                                                    </div>
                                                    <div class="form-group col-lg-2 col-md-2 col-xs-12">
                                                        <label for="garantia"><?= lang('application_garanty'); ?>
                                                            (* <?= lang('application_in_months'); ?>)</label>
                                                        <input type="text" class="form-control" id="garantia"
                                                               name="garantia"
                                                               required
                                                               placeholder="<?= lang('application_enter_warranty'); ?>"
                                                               value="<?php echo set_value('garantia') ?>"
                                                               autocomplete="off"
                                                               onKeyPress="return digitos(event, this);"/>
                                                    </div>
                                                    <div class="form-group col-lg-2 col-md-2 col-xs-12">
                                                        <label for="days"><?= lang('application_extra_operating_time'); ?></label>
                                                        <?php $prazo = isset($prazo) ? $prazo : ""; ?>
                                                        <input type="number" class="form-control" id="days"
                                                               name="prazo_operacional_extra"
                                                               placeholder="<?= lang('application_extra_operating_time'); ?>"
                                                               value="<?php echo set_value('prazo_operacional_extra', $prazo) ?>"
                                                               autocomplete="off"
                                                               onKeyPress="return digitos(event, this);"/>
                                                        <div id="dv" style="display: none;">
                                                            <input onclick="liberar()" id="checkbox_liber"
                                                                   name="libera"
                                                                   value="1" type="checkbox">
                                                            <label for="checkbox_liber"><?php echo lang('application_liberar') ?></label>
                                                        </div>
                                                    </div>
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
                        <div class="row">
                            <div class="col-md-12">
                                <div class="col-md-3 col-xs-6">
                                    <a href="<?php echo base_url('products/') ?>"
                                       class="btn btn-warning col-md-12 col-xs-12"><?= lang('application_back'); ?></a>
                                </div>
                                <div class="col-md-3 col-xs-6">
                                    <button type="submit" id="letsave"
                                            class="btn btn-primary col-md-12 col-xs-12"><?= lang('application_save'); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="row" style="margin-top: 15px"></div>
        </div>
    </section>
</div>


<div class="modal fade" tabindex="-1" role="dialog" id="addBrandModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?= lang('application_add_brand'); ?></h4>
            </div>

            <form role="form" action="<?php echo base_url('brands/create') ?>" method="post" id="createBrandForm">

                <div class="modal-body">

                    <div class="form-group">
                        <label for="brand_name"><?= lang('application_name'); ?></label>
                        <input type="text" class="form-control" id="brand_name" name="brand_name"
                               placeholder="<?= lang('application_enter_brand_name') ?>"
                               autocomplete="off">
                    </div>
                    <input type="hidden" id="active" name="active" value="1"/>
                    <input type="hidden" id="fromproducts" name="fromproducts" value="fromproducts"/>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default"
                            data-dismiss="modal"><?= lang('application_close'); ?></button>
                    <button type="submit" id="brand_save_button" name="brand_save_button"
                            class="btn btn-primary"><?= lang('application_save'); ?></button>
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
                <h4 class="modal-title"><?= lang('application_uploadimages'); ?></h4>
            </div>
            <div class="modal-body">

                <div class="form-group col-md-12 col-xs-12">
                    <label for="product_image_variant"><?= lang('application_uploadimages'); ?>(*):</label>
                    <div class="kv-avatar">
                        <div class="imagem_variant_wrap">
                            <div id="showimage0" style="display:none">
                                <div class="file-loading">
                                    <input type="file" id="prd_image_variant0" name="prd_image_variant0[]"
                                           accept="image/*" multiple>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="variant_num" name="variant_num" value="0"/>
                </div>

            </div>

            <div class="modal-footer">
                <a href="#" onclick="UpdateVariantImage(event)"
                   class="btn btn-default"><?= lang('application_close'); ?></a>
            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<input type="hidden" id="category_blocked_cross_docking" value="0"/>
<input type="hidden" id="category_days_cross_docking" value="0"/>

<script type="text/javascript">
    var base_url = "<?php echo base_url(); ?>";
    var update_category = "<?php echo in_array('updateCategory', $this->permission) ?>"
    var require_ean = "<?php echo ($require_ean) ? ' required ' : '' ?>"
    var upload_url = "<?= base_url('/Products/saveImageProduct'); ?>";
    var delete_url = "<?= base_url('Products/removeImageProduct'); ?>";
    var token = '<?= $prdtoken; ?>'; // My Token

    // var varn = 1;
    $(document).ready(function () {

        var wrapper = $(".input_fields_wrap"); //Fields wrapper
        $('#product_image').val(token);

        var varn = $("#numvar").val();
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
            var i;
            checkEAN(eans[0], 'EANV0');
            for (i = 1; i < varn; i++) {

                $("#voltvar").prop("checked") == true ? check_volt = 'checked' : show_volt = 'style="display: none"';

                var linha = '<div class="row" id="variant' + i + '">';
                if ($("#sizevar").prop("checked") == true) {
                    linha = linha + '<div class="Itvar col-md-1" style="padding-right: 3px;"><input type="text" required class="form-control" id="T[]" name="T[]" autocomplete="off" placeholder="Tamanho" value="' + tamanhos[i] + '" /></div>';
                } else {
                    linha = linha + '<div class="Itvar col-md-1" style="display: none; padding-right: 3px;"><input type="text" class="form-control" id="T[]" name="T[]" autocomplete="off" placeholder="Tamanho" /></div>';
                }
                if ($("#colorvar").prop("checked") == true) {
                    linha = linha + '<div class="Icvar col-md-2" style="padding-right: 3px;"><input type="text" required class="form-control" id="C[]" name="C[]" autocomplete="off" placeholder="<?= lang('application_color') ?>" value="' + cores[i] + '" /></div>';
                } else {
                    linha = linha + '<div class="Icvar col-md-2" style="display: none; padding-right: 3px;"><input type="text" class="form-control" id="C[]" name="C[]" autocomplete="off" placeholder="<?= lang('application_color') ?>"  /></div>';
                }
                if ($("#voltvar").prop("checked") == true) {
                    linha = linha + '<div id="Icvar" class="col-md-2" style="padding-left: 0px;padding-right: 3px;"><input type="text" autocomplete="off" required class="form-control" id="V[]" name="V[]" placeholder="<?= $this->lang->line('application_voltage') ?>" value="' + voltagem[i] + '" /></div>';
                } else {
                    linha = linha + '<div id="Icvar" class="col-md-2" style="display: none; padding-left: 0px;padding-right: 3px;"><input type="text" autocomplete="off" class="form-control" id="V[]" name="V[]" placeholder="<?= $this->lang->line('application_voltage') ?>" /></div>';
                }
                linha = linha + '<div class="Iqvar col-md-1" style="padding-right: 3px;"><input type="text" required class="form-control" id="Q[]" name="Q[]" autocomplete="off" placeholder="Estoque" onKeyPress="return digitos(event, this);"  value="' + quantidades[i] + '" /></div>';
                linha = linha + '<div class="Iskuvar col-md-1" style="padding-right: 3px;"><input type="text" maxlength="<?= $product_length_sku ?>" required class="form-control" id="SKU_V_' + i + '" name="SKU_V[]" autocomplete="off" placeholder="SKU Variação" value="' + skus[i] + '" onKeyUp="checkSpecialSku(event, this);characterLimit(this);" onblur="checkSpecialSku(event, this);" /></div><span id="char_SKU_V_' + i + '"></span><br />';
                if ($("#saborvar").prop("checked") == true) {
                    linha = linha + '<div class="Isvar col-md-2" style="padding-right: 3px;"><input type="text" required class="form-control" id="sb[]" name="sb[]" autocomplete="off" placeholder="<?= lang('application_flavor'); ?>" value="' + sabor[i] + '" /></div>';
                } else {
                    linha = linha + '<div class="Isvar col-md-2" style="display: none; padding-right: 3px;"><input type="text" class="form-control" id="sb[]" name="sb[]" autocomplete="off" placeholder="<?= lang('application_flavor'); ?>"  /></div>';
                }
                if ($("#grauvar").prop("checked") == true) {
                    linha = linha + '<div class="Igvar col-md-2" style="padding-right: 3px;"><input type="text" required class="form-control" id="gr[]" name="gr[]" autocomplete="off" placeholder="<?= lang('application_degree'); ?>" value="' + grau[i] + '" /></div>';
                } else {
                    linha = linha + '<div class="Igvar col-md-2" style="display: none; padding-right: 3px;"><input type="text" class="form-control" id="gr[]" name="gr[]" autocomplete="off" placeholder="<?= lang('application_degree'); ?>"  /></div>';
                }
                if ($("#ladovar").prop("checked") == true) {
                    linha = linha + '<div class="Ilvar col-md-2" style="padding-right: 3px;"><input type="text" required class="form-control" id="ld[]" name="ld[]" autocomplete="off" placeholder="<?= lang('application_side'); ?>" value="' + lado[i] + '" /></div>';
                } else {
                    linha = linha + '<div class="Ilvar col-md-2" style="display: none; padding-right: 3px;"><input type="text" class="form-control" id="ld[]" name="ld[]" autocomplete="off" placeholder="<?= lang('application_side'); ?>"  /></div>';
                }
                linha = linha + '<div id="EANV' + i + '" class="Ieanvar  col-md-2" style="padding-right: 3px;"><input type="text" ' + require_ean + ' class="form-control" id="EAN_V[]" name="EAN_V[]" onchange="checkEAN(this.value,\'EANV' + i + '\')" autocomplete="off" placeholder="EAN Variação" value="' + eans[i] + '" /><span id="EANV' + i + 'erro" style="display: none;"><i style="color:red"><?= $invalid_ean; ?></i></span></div>';
                checkEAN(eans[i], 'EANV' + i);

                var displayPriceByVariation = $('.Ipricevar').length;
                if (displayPriceByVariation > 0) {
                    linha = linha + '<div class="Ilistpricevar col-md-1" style="padding-right: 3px;"><input type="text" class="form-control maskdecimal2" id="LIST_PRICE_V[]" name="LIST_PRICE_V[]" autocomplete="off" placeholder="Preço De - Variação" oninput="restrict(this)" value="' + listprices[i] + '" /></div>';

                    linha = linha + '<div class="Ipricevar col-md-1" style="padding-right: 3px;"><input type="text" class="form-control maskdecimal2" id="PRICE_V[]" name="PRICE_V[]" autocomplete="off" placeholder="Preço Por - Variação" oninput="restrict(this)" value="' + prices[i] + '" /></div>';
                }

                linha = linha + '<div class="col-md-2" style="padding-right: 3px;">';
                linha = linha + '<input type="hidden" id="IMAGEM' + i + '" name="IMAGEM[]" value="' + imagens[i] + '" />';
                linha = linha + '<a href="#" onclick="AddImage(event,\'' + i + '\')" >';
                linha = linha + '<img id="foto_variant' + i + '" src="' + base_url + 'assets/images/system/sem_foto.png' + '" class="img-rounded" width="40" height="40" />';
                linha = linha + '<i class="fa fa-plus-circle" style="margin-left: 6px"></i></a>';
                linha = linha + '<button type="button" onclick="RemoveVariant(event,' + i + ')" class="btn btn-danger" style="margin-left:3px"><i class="fa fa-trash"></i></button>';
                linha = linha + '</div>';

                // linha = linha + '<div class="form-group col-md-1" style="padding-right: 3px;"><button type="button" class="btn btn-default remove_field"><i class="fa fa-trash"></i></button></div></div>';
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
                    checkEAN(eans[0], 'EANV0');
                }
            }

        }

        initImage(0);

        $('.maskdecimal2').inputmask({
            alias: 'numeric',
            allowMinus: false,
            digits: 2,
            max: 999999999999.99
        });

        $('.maskdecimal3').inputmask({
            alias: 'numeric',
            allowMinus: false,
            digits: 3,
            max: 9999999999.999
        });

        $('#category').change(function () {
            var idcat = $('#category option:selected').val();
            changeCategory(idcat);
            verifyWords();
        });

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
        // $('#varprop').hide();
        // Controling Variants Options

        $('#semvar').change(function () {
            $('[id="T[]"').attr("required", false);
            $('[id="C[]"').attr("required", false);
            $('[id="Q[]"').attr("required", false);
            $('[id="sb[]"').attr("required", false);
            $('[id="gr[]"').attr("required", false);
            $('[id="ld[]"').attr("required", false);
            $('#sizevar').prop('checked', false);
            $('#colorvar').prop('checked', false);
            $('#voltvar').prop('checked', false);
            $('#saborvar').prop('checked', false);
            $('#grauvar').prop('checked', false);
            $('#ladovar').prop('checked', false);
            $("#variantModal").hide();
            $.fn.variantsclear();
            $('#qty').attr("disabled", false);
            $('#qty').attr("required", true);
            $('[id="EAN_V[]"').attr("required", false);
            $('#qty_marketplace').show();

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
            $('[id="gr[]"').attr("required", $("#grauvar").prop("checked"));
            $('[id="ld[]"').attr("required", $("#ladovar").prop("checked"));
            if ($("#sizevar").prop("checked") == false) {
                $('#Ltvar').hide();
                $('.Itvar').hide();
                num++;
            }
            if ($("#sizevar").prop("checked") == true) {
                $('#Ltvar').show();
                $('.Itvar').show();
            }
            if ($("#colorvar").prop("checked") == false) {
                $('#Lcvar').hide();
                $('.Icvar').hide();
                num++;
            }
            if ($("#colorvar").prop("checked") == true) {
                $('#Lcvar').show();
                $('.Icvar').show();
            }
            if ($("#voltvar").prop("checked") == false) {
                $('#Lvvar').hide();
                $('.Ivvar').hide();
                num++;
            }
            if ($("#voltvar").prop("checked") == true) {
                $('#Lvvar').show();
                $('.Ivvar').show();
            }
            if ($("#saborvar").prop("checked") == false) {
                $('#Lsvar').hide();
                $('.Isvar').hide();
                num++;
            }
            if ($("#saborvar").prop("checked") == true) {
                $('#Lsvar').show();
                $('.Isvar').show();
            }
            if ($("#grauvar").prop("checked") == false) {
                $('#Lgvar').hide();
                $('.Igvar').hide();
                num++;
            }
            if ($("#grauvar").prop("checked") == true) {
                $('#Lgvar').show();
                $('.Igvar').show();
            }
            if ($("#ladovar").prop("checked") == false) {
                $('#Llvar').hide();
                $('.Ilvar').hide();
                num++;
            }
            if ($("#ladovar").prop("checked") == true) {
                $('#Llvar').show();
                $('.Ilvar').show();
            }
            if (num == 6) {
                $('#qty').attr("disabled", false);
                $('#qty').attr("required", true);
                $('#semvar').prop('checked', true);
                $("#variantModal").hide();
                $.fn.variantsclear();
                $('[id="Q[]"').attr("required", false);
                $('[id="EAN_V[]"').attr("required", false);
                $('#qty_marketplace').show();
            } else {
                $('#qty').attr("disabled", true);
                $('#qty').attr("required", false);
                $('[id="Q[]"').attr("required", true);
                $('.sameqtychk').prop('checked', true);
                $('.sameqtyval').prop('disabled', true);
                $('.sameqtyval').prop('required', true);
                $('#qty').attr("disabled", true);
                $('[id="EAN_V[]"').attr("required", (require_ean == ' required '));
                $('#qty_marketplace').hide();
            }
        }


        var add_line = $(".add_line"); //Add button ID

        $(add_line).click(function (e) { //on add input button click
            e.preventDefault();
            varn++;
            var linha = '<div class="row" id="variant' + varn + '">';
            let check_size = '';
            let check_color = '';
            let check_volt = '';
            let check_sabor = '';
            let check_grau = '';
            let check_lado = '';
            let show_size = ' style="padding-right: 3px;" ';
            let show_color = ' style="padding-right: 3px;" ';
            let show_volt = ' style="padding-right: 3px;" ';
            let show_sabor = ' style="padding-right: 3px;" ';
            let show_grau = ' style="padding-right: 3px;" ';
            let show_lado = ' style="padding-right: 3px;" ';

            $("#sizevar").prop("checked") == true ? check_size = 'checked' : show_size = 'style="display: none; padding-right: 3px;"';
            $("#colorvar").prop("checked") == true ? check_color = 'checked' : show_color = 'style="display: none; padding-right: 3px;"';
            $("#voltvar").prop("checked") == true ? check_volt = 'checked' : show_volt = 'style="display: none; padding-right: 3px;"';
            $("#saborvar").prop("checked") == true ? check_sabor = 'checked' : show_sabor = 'style="display: none; padding-right: 3px;"';
            $("#grauvar").prop("checked") == true ? check_grau = 'checked' : show_grau = 'style="display: none; padding-right: 3px;"';
            $("#ladovar").prop("checked") == true ? check_lado = 'checked' : show_lado = 'style="display: none; padding-right: 3px;"';

            linha = linha + '<div class="Itvar col-md-1" ' + show_size + '><input type="text" ' + check_size + ' class="form-control" id="T[]" name="T[]" autocomplete="off"  placeholder="Tamanho"  /></div>';
            linha = linha + '<div class="Icvar col-md-1" ' + show_color + '><input type="text" ' + check_color + ' class="form-control" id="C[]" name="C[]" autocomplete="off"  placeholder="<?= lang('application_color') ?>"  /></div>';
            linha = linha + '<div class="Ivvar col-md-1" ' + show_volt + '><input type="text" ' + check_volt + ' class="form-control" id="V[]" name="V[]" autocomplete="off" placeholder="<?= $this->lang->line('application_voltage') ?>"  /></div>';

            linha = linha + '<div class="Isvar col-md-1" ' + show_sabor + '><input type="text" ' + check_sabor + ' class="form-control" id="sb[]" name="sb[]" autocomplete="off"  placeholder="<?= lang('application_flavor'); ?>"  /></div>';
            
            linha = linha + '<div class="Igvar col-md-1" ' + show_grau + '><input type="text" ' + check_grau + ' class="form-control" id="gr[]" name="gr[]" autocomplete="off"  placeholder="<?= lang('application_degree'); ?>"  /></div>';
            linha = linha + '<div class="Ilvar col-md-1" ' + show_lado + '><input type="text" ' + check_lado + ' class="form-control" id="ld[]" name="ld[]" autocomplete="off"  placeholder="<?= lang('application_side'); ?>"  /></div>';


            linha = linha + '<div class="Iqvar col-md-1" style="padding-right: 3px;"><input type="text" required class="form-control" id="Q[]" name="Q[]" autocomplete="off" placeholder="Estoque" onKeyPress="return digitos(event, this);" /></div>';
            linha = linha + '<div class="Iskuvar col-md-1" style="padding-right: 3px;"><input type="text" required class="form-control" id="SKU_V_' + i + '" name="SKU_V[]" autocomplete="off" placeholder="SKU Variação" onKeyUp="checkSpecialSku(event, this);characterLimit(this);" onblur="checkSpecialSku(event, this);" maxlength="<?= $product_length_sku ?>" /><span id="char_SKU_V_' + i + '"></span><br /></div>';
            linha = linha + '<div id="EANV' + varn + '" class="Ieanvar col-md-2" style="padding-right: 3px;"><input type="text" ' + require_ean + ' class="form-control" onchange="checkEAN(this.value,\'EANV' + varn + '\')" id="EAN_V[]" name="EAN_V[]" autocomplete="off" placeholder="EAN Variação" /><span id="EANV' + varn + 'erro" style="display: none;"><i style="color:red"><?= $invalid_ean; ?></i></span></div>';

            var displayPriceByVariation = $('.Ipricevar').length;

            if (displayPriceByVariation > 0) {
                linha = linha + '<div class="Ilistpricevar col-md-1 maskdecimal2" style="padding-right: 3px;"><input type="text" class="form-control maskdecimal2" id="LIST_PRICE_V[]" name="LIST_PRICE_V[]" autocomplete="off" oninput="restrict(this)" placeholder="Preço De - Variação" /></div>';

                linha = linha + '<div class="Ipricevar col-md-1 maskdecimal2" style="padding-right: 3px;"><input type="text" class="form-control maskdecimal2" id="PRICE_V[]" name="PRICE_V[]" autocomplete="off" oninput="restrict(this)" placeholder="Preço Por - Variação" /></div>';
            }

            linha = linha + '<div  class="col-md-1" style="padding-right: 3px;">';
            var charSet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            var randomString = '';
            for (var i = 0; i < 15; i++) {
                var randomPoz = Math.floor(Math.random() * charSet.length);
                randomString += charSet.substring(randomPoz, randomPoz + 1);
            }
            linha = linha + '<input type="hidden" id="IMAGEM' + varn + '" name="IMAGEM[]" value="' + randomString + '" />';
            linha = linha + '<a href="#" onclick="AddImage(event,\'' + varn + '\')" >';
            linha = linha + '<img id="foto_variant' + varn + '" src="' + base_url + 'assets/images/system/sem_foto.png' + '"  class="img-rounded" width="40" height="40" />';
            linha = linha + '<i class="fa fa-plus-circle" style="margin-left: 6px"></i></a>';
            linha = linha + '<button type="button" onclick="RemoveVariant(event,' + varn + ')" class="btn btn-danger" style="margin-left:3px"><i class="fa fa-trash"></i></button>';
            linha = linha + '</div>';

            //linha = linha + '<div class="col-md-1" style="padding-right: 3px;"><button type="button" class="btn btn-default remove_field"><i class="fa fa-trash"></i></button></div></div>';
            $(wrapper).append(linha);

            var wrapperimagem = $(".imagem_variant_wrap");
            var lin_image = '<div id="showimage' + varn + '" style="display:none"><div class="file-loading"><input type="file" id="prd_image_variant' + varn + '" name="prd_image_variant' + varn + '[]" accept="image/*" multiple></div></div>'
            wrapperimagem.append(lin_image);
            //console.log(lin_image);
            initImage(varn);

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
        })
        $('#reset_variant').click(function (e) { //on clear button click
            e.preventDefault();
            $.fn.variantsclear();
        });

        $.fn.variantsclear = function () {
            for (i = 0; i <= varn; i++) {
                div = 'div #variant' + i;
                $(div).remove();
            }
            varn = 1;
            $('#numvar').val(varn);
        }

        var btnCust = '<button type="button" class="btn btn-secondary" title="Add picture tags" ' +
            'onclick="alert(\'Call your custom code here.\')">' +
            '<i class="glyphicon glyphicon-tag"></i>' +
            '</button>';

        $("#prd_image").fileinput({
            uploadUrl: "<?= base_url('/Products/saveImageProduct'); ?>",
            language: 'pt-BR',
            autoOrientImage: false,
            allowedFileExtensions: ["jpg", "png"],
            uploadAsync: true,
            showUpload: false,
            enableResumableUpload: true,
            resumableUploadOptions: {
                // uncomment below if you wish to test the file for previous partial uploaded chunks
                // to the server and resume uploads from that point afterwards
                // testUrl: "http://localhost/test-upload.php"
            },
            uploadExtraData: {
                'uploadToken': token, // for access control / security
                'onBucket': 1 // Sempre que for cadastrar um novo produto envia como no bucket.
            },
            maxTotalFileCount: <?= $limite_imagens_aceitas_api;?>,
            allowedFileTypes: ['image'], // allow only images
            showCancel: true,
            initialPreviewAsData: true,
            overwriteInitial: false,
            showRemove: false,
            // initialPreview: [],          // if you have previously uploaded preview files
            // initialPreviewConfig: [],    // if you have previously uploaded preview files
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
            theme: 'fas',
            deleteUrl: "<?= base_url('Products/removeImageProduct'); ?>",
            minImageWidth: <?= $dimenssion_min_product_image ?? 'null' ?>,
            minImageHeight: <?= $dimenssion_min_product_image ?? 'null' ?>,
            maxImageWidth: <?= $dimenssion_max_product_image ?? 'null' ?>,
            maxImageHeight: <?= $dimenssion_max_product_image ?? 'null' ?>
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
                    onBucket: 1
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

        $("#category, input[type='checkbox']:checked").trigger('change');

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

                    console.log(response);

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
                            //    '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                            //    '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>' + response.messages +
                            //    '</div>');
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
        verifyWords();
    });

    function AddBrand(e) {
        e.preventDefault();
        $("#addBrandModal").modal('show');
    }

    function restrict(tis) { // so aceita numero com 2 digitos
        var prev = tis.getAttribute("data-prev");
        prev = (prev != '') ? prev : '';
        if (Math.round(tis.value * 100) / 100 != tis.value)
            tis.value = prev;
        tis.setAttribute("data-prev", tis.value)
    }

    $('#formInsertProduct').submit(function () {
        let variations = [];
        let exitApp = false;
        if ($('#semvar').is(':not(:checked)')) {
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
        }
    })

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
                        $("#days").val(0); // pego o dia atual da tabela linha 902
                        $("#days").removeAttr('readonly'); // removeo o bloqueio de campo
                        document.getElementById('days').style.display = 'block';
                        document.getElementById('dv').style.display = 'none';
                    }
                }
            });
        }
    }

    const verifyWords = () => {
        const brand = $('#brands').val();
        const category = $('#category').val();
        const store = $('#store').val();
        const sku = $('#sku').val();
        const product = parseInt(window.location.pathname.split('/').pop());
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

        $('#char_' + attribute).text(`<?= lang('application_type_char'); ?>${quantity}/${limit}`);
    }

    function checkEAN(ean, field) {

        var store = document.getElementById("store");
        $.ajax({
            type: "POST",
            enctype: 'multipart/form-data',
            data: {
                ean: ean,
                product_id: 0,
                store_id: store.value,
            },
            url: base_url + "products/checkEANpost",
            dataType: "json",
            async: true,
            success: function (response) {
                console.log(response)
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

        for (i = 0; i <= $("#numvar").val(); i++) {
            $("#showimage" + i).hide();
        }
        $('#variant_num').val(num);
        $("#showimage" + num).show();
    }

    function RemoveVariant(e, num) {
        e.preventDefault();
        //alert(num);
        $('#showimage' + num).remove();
        $('#variant' + num).remove();

        var varn = $("#numvar").val();
        varn--;
        if (varn == 0) {
            varn = 1;
        }
        $('#numvar').val(varn);
    }

    function UpdateVariantImage(e) {
        e.preventDefault();

        var num = $("#variant_num").val();
        $("#addImagemModal").modal('hide');

        var tokenimagem = token;
        tokenimagem = tokenimagem + "/" + $("#IMAGEM" + num).val();

        $.ajax({
            type: "POST",
            enctype: 'multipart/form-data',
            data: {
                tokenimagem: tokenimagem,
                onBucket: 1
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
        // var tokenimagem = $("#IMAGEM"+num).val();

        var tokenimagem = token;

        if (folder == null) {
            tokenimagem = tokenimagem + "/" + $("#IMAGEM" + num).val();
        } else {
            tokenimagem = tokenimagem + "/" + folder;
        }
        console.log("tokenimagem = " + tokenimagem);

        // alert(tokenimagem);
        $.ajax({
            type: "POST",
            enctype: 'multipart/form-data',
            data: {
                tokenimagem: tokenimagem,
                onBucket: 1
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
                        // minImageWidth: 800,
                        // minImageHeight: 800,
                        // maxImageWidth: 2000,
                        // maxImageHeight: 2000,
                        enableResumableUpload: true,
                        resumableUploadOptions: {
                            // uncomment below if you wish to test the file for previous partial uploaded chunks
                            // to the server and resume uploads from that point afterwards
                            // testUrl: "http://localhost/test-upload.php"
                        },
                        uploadExtraData: {
                            'uploadToken': tokenimagem, // for access control / security
                            'onBucket': 1
                        },
                        maxTotalFileCount: <?= $limite_imagens_aceitas_api;?>,
                        allowedFileTypes: ['image'], // allow only images
                        showCancel: true,
                        initialPreviewAsData: true,
                        overwriteInitial: false,
                        initialPreview: response.ln1,
                        initialPreviewConfig: response.ln2,

                        theme: 'fas',
                        deleteUrl: delete_url,
                        minImageWidth: <?= $dimenssion_min_product_image ?? 'null' ?>,
                        minImageHeight: <?= $dimenssion_min_product_image ?? 'null' ?>,
                        maxImageWidth: <?= $dimenssion_max_product_image ?? 'null' ?>,
                        maxImageHeight: <?= $dimenssion_max_product_image ?? 'null' ?>
                    }).on('filesorted', function (event, params) {
                        changeTheOrderOfImages(params)
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

        function changeTheOrderOfImages(params) {

            $.ajax({
                type: "POST",
                enctype: 'multipart/form-data',
                data: {
                    params: params,
                    onBucket: 1
                },
                url: base_url + "products/orderImagesVariant",
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
    }

    $('#brands, #store').change(function () {
        verifyWords();
    });
    $('#sku').blur(function () {
        verifyWords();
    });
</script>
<script>
$(document).ready(function () {
    document.getElementById('dv').style.display = 'none';
    $('#category').select2();
});

function liberar() {
    let checkbox_liber = $("#checkbox_liber").is(":checked"); // verifica se checkbox esta marcado
    let blocked_cross_docking   = $('#category_blocked_cross_docking').val();
    let days_cross_docking      = $('#category_days_cross_docking').val();

    document.getElementById('days').disabled = false;

    if (blocked_cross_docking) {
        if (checkbox_liber) { // se com bloqueio e normal
            document.getElementById('days').readOnly = false;
        } else { // se com bloqueio e normal
            document.getElementById('days').readOnly = true;
            document.getElementById('days').value = days_cross_docking;
        }
    } else {
        if (checkbox_liber) { // se com bloqueio e normal
            document.getElementById('days').readOnly = false;
        } else { // se com bloqueio e normal
            document.getElementById('days').disabled = true;
            document.getElementById('days').readOnly = false;
            document.getElementById('days').value = days_cross_docking;
        }
    }
}
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