<style>
	.gutter-13.row {
		margin-right: -13px;
		margin-left: -13px;
	}

	.gutter-13>[class^="col-"],
	.gutter-3>[class^=" col-"] {
		padding-right: 2px;
		padding-left: 2px;
	}
</style>

<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>

<div class="content-wrapper">

	<?php $data['pageinfo'] = "application_edit";
	$data['page_now'] = 'products_kit';
	$this->load->view('templates/content_header', $data); ?>

	<!-- Main content -->
	<section class="content">
		<!-- Small boxes (Stat box) -->
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
				<br />
				<div class="box">
					<form role="form" id="formedit" id="name" action="<?php base_url('productsKit/update') ?>" method="post">
						<?php
						if (validation_errors()) {
							foreach (explode("</p>", validation_errors()) as $erro) {
								$erro = trim($erro);
								if ($erro != "") { ?>
									<div class="alert alert-error alert-dismissible" role="alert">
										<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
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
								$ln2[$numft] = '{width: "120px", key: "' . $foto . '"}';
							}
						} else {
							if (!$product_data['is_on_bucket']) {
								$fotos = array();
								if (is_dir(FCPATH . 'assets/images/product_image/' . $product_data['image'])) {
									$fotos = scandir(FCPATH . 'assets/images/product_image/' . $product_data['image']);
								}
								foreach ($fotos as $foto) {
									if (($foto != ".") && ($foto != "..") && ($foto != "")) {
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
						// 1 - falta dados - foi cadastrado mas ainda faltam a foto ou a categoria  
						// 2 - novo - está com todos os dados mas ainda não está no bling (EAN novo)
						// 3 - cadastro bling - já foi enviado ao Bling mas precisa ser dado o OK q foi associado aos marketplaces
						// 4 - cadastrado completo - EAN velho.
						$divclass = "alert-success";
						if ($product_data['situacao'] == '1') {
							$msg = $this->lang->line('messages_product_missing_information') . " :";
							if ($numft == 0) {
								$msg .= " " . $this->lang->line('application_photos') . ",";
							}
							if ($product_data['category_id'] == '[""]') {
								$msg .= " " . $this->lang->line('application_category') . ",";
							}
							$msg = substr($msg, 0, -1);
							$divclass = "alert-danger";
						} elseif ($product_data['situacao'] == '2') {
							$msg = $this->lang->line('messages_complete_registration');
							/*if($usergroup<5) {
						$divclass = "alert-info"; 
						$msg = "Não enviado para o Bling ainda";
					 }*/
						} elseif ($product_data['situacao'] == '3') {
							$msg = $this->lang->line('messages_complete_registration');
							/*if($usergroup<5) {
						$divclass = "alert-warning"; 
						$msg = "Necessita da integração no bling";
					}*/
						} else {
							$msg = $this->lang->line('messages_complete_registration');
						}
						?>

						<div class="alert <?php echo $divclass ?> alert-dismissible" role="alert">
							<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<?php echo $msg; ?>
						</div>

						<?php
						if ($product_data['status'] == 4) { ?>
							<div class="alert alert-danger alert-dismissible" role="alert">
								<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
								<?= $this->lang->line('application_forbidden_word_alert'); ?>
							</div>
						<?php }
						?>

						<?php if (count($errors_transformation) > 0) : ?>
							<div class="alert alert-error alert-dismissible" role="alert">
								<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
								<a href="#errors-transformation"><?= $this->lang->line('application_errors_tranformation_msg') ?></a>

							</div>
						<?php endif ?>
						<div class="box-header">
							<h3 class="box-title"><?= $this->lang->line('application_selected_products') ?></h3>
						</div>
						<!-- /.box-header -->
						<div class="box-body">
							<div>
								<input type="hidden" id="numprod" name="numprod" value="0">
							</div>
							<div class="container">
								<div class="row gutter-13">
									<div id="divid" class="col-sm-1">
										<label><?= $this->lang->line('application_sku'); ?></label>
									</div>
									<div id="divname" class="col-sm-4">
										<label><?= $this->lang->line('application_product'); ?></label>
									</div>
									<div id="divprice" class="col-sm-2">
										<label><?= $this->lang->line('application_price'); ?></label>
									</div>
									<div id="divqty" class="col-sm-1">
										<label><?= $this->lang->line('application_qty'); ?></label>
									</div>
									<div id="divde" class="col-sm-1">
										<label><?= $this->lang->line('application_price_from'); ?></label>
									</div>
									<div id="divpor" class="col-sm-1">
										<label><?= $this->lang->line('application_price_sale'); ?></label>
									</div>
									<div id="divaction" class="col-sm-1">
										<label><?= $this->lang->line('application_action'); ?></label>
									</div>
								</div>
								<?php
								$total_qty = 0;
								$total_price = 0;
								$total_price_original = 0;
								foreach ($productskit as $productkit) {

									$product_info = $this->model_products->getProductById($productkit['product_id']);

									$total_line = $productkit['qty'] * $productkit['price'];
									$total_line_original = $productkit['qty'] * $productkit['original_price'];
									$total_price += $total_line;
									$total_price_original += $total_line_original;
									$total_qty += $productkit['qty'];
									$inativo = '';
									$perc = number_format(((1 - $productkit['price'] / $productkit['original_price']) * 100), 2, '.', '');
									if ($productkit['status'] != 1) {
										$inativo = '&nbsp<span class="label label-danger">' . $this->lang->line('application_inactive') . '</span>';
									}
								?>
									<div class="row gutter-13 mb-4">
										<div id="divid" class="col-sm-1">
											<a target="__blank" href="<?php echo base_url('products/update/' . $productkit['product_id_item']); ?>"><?php echo $productkit['sku'] . $inativo; ?></a>
										</div>
										<div id="divname" class="col-sm-4">
											<input type="text" disabled class="form-control" value="<?php echo $productkit['name']; ?>" />										
											<?= ($productkit['price'] > $productkit['original_price']) ? '<small class="text-danger" style="font-size:12px;font-weight:bold;">'.(str_replace(
                            '{price}',
                            number_format($productkit['original_price'], 2, ',', ''),
														$this->lang->line('application_kit_price_warning')
                        )).'</small>' : '' ?>
										</div>
										<div id="divprice" class="col-md-2">
											<input type="text" disabled class="form-control" style="text-align:right;" value="<?php echo get_instance()->formatprice($productkit['price']) . ' (' . $perc . '%)'; ?>" />
										</div>
										<div id="divqty" class="col-sm-1">
											<input type="text" disabled class="form-control" style="text-align:right;" value="<?php echo $productkit['qty']; ?>" />
										</div>
										<div id="divde" class="col-sm-1">
											<input type="text" disabled class="form-control" style="text-align:right;" value="<?php echo get_instance()->formatprice($total_line_original); ?>" />
										</div>
										<div id="divpor" class="col-sm-1">
											<input type="text" disabled class="form-control" style="text-align:right;" value="<?php echo get_instance()->formatprice($total_line); ?>" />
										</div>
										<div id="divaction" class="col-md-1">
											<a target="__blank" href="<?php echo base_url('products/update/' . $productkit['product_id_item']); ?>" class="btn btn-default"><i class="fa fa-eye"></i></a>
										</div>									
									</div>

								<?php	}

								?>

								<div class="row gutter-13 mt-4">
									<div id="divid" class="col-sm-1">
									</div>
									<div id="divname" class="col-sm-4">
									</div>
									<div id="divprice" class="col-sm-2">
										<label>Total :</label>
									</div>
									<div id="divqty" class="col-sm-1">
										<input type="text" disabled id="total_qty" style="text-align:right;" class="form-control" name="total_qty" value="<?php echo $total_qty; ?>">
									</div>
									<div id="divde" class="col-sm-1">
										<input type="text" disabled id="total_price" style="text-align:right;" class="form-control" name="total_price" value="<?php echo get_instance()->formatprice($total_price_original); ?>">
									</div>
									<div id="divpor" class="col-sm-1">
										<input type="text" disabled id="total_price" style="text-align:right;" class="form-control" name="total_price" value="<?php echo get_instance()->formatprice($total_price); ?>">
									</div>
									<div class="col-md-1">
										<button type="button" class="btn btn-primary" onclick="updatePrice(event,<?php echo $product_data['id']; ?>)" data-toggle="modal" data-target="#updatePriceModal"><i class="fa fa-pencil"></i></button>
									</div>
								</div>
							</div>
							<div class="form-group col-md-10 col-xs-12">
								<label for="product_image"><?= $this->lang->line('application_uploadimages'); ?>(*):</label>
								<div class="kv-avatar">
									<div class="file-loading">
										<input type="file" <?= (isset($integracoes) && $notAdmin ? 'readonly' : '') ?> id="prd_image" name="prd_image[]" accept="image/png, image/jpeg" multiple>
									</div>
								</div>
								<input type="hidden" name="product_image" id="product_image" value="<?= $product_data['image']; ?>" />
							</div>

							<div class="form-group col-md-6 col-xs-12 <?php echo (form_error('product_name')) ? 'has-error' : ''; ?>">
								<label for="product_name"><?= $this->lang->line('application_name'); ?>(*)</label>
								<input type="text" class="form-control" <?= (isset($integracoes) && $notAdmin ? 'readonly' : '') ?> id="product_name" onkeyup="characterLimit(this)" maxlength="60" onchange="verifyWords()" name="product_name" required placeholder="<?= $this->lang->line('application_enter_product_name'); ?>" value="<?php echo set_value('product_name', $product_data['name']); ?>" autocomplete="off" />
								<span id="char_product_name"></span><br />
								<span class="label label-warning" id="words_product_name" data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_explanation_of_forbidden_words'); ?>"></span>
								<?php echo '<i style="color:red">' . form_error('product_name') . '</i>'; ?>
							</div>

							<input type="hidden" name="has_integration" value="<?= (isset($integracoes) && $notAdmin ? true : false) ?>" />

							<div class="form-group col-md-3 col-xs-12 <?php echo (form_error('sku')) ? 'has-error' : ''; ?>">
								<label for="sku"><?= $this->lang->line('application_sku'); ?>(*)</label>
								<input type="text" <?= (isset($integracoes) && $notAdmin ? 'readonly' : '') ?> class="form-control" id="sku" name="sku" required placeholder="<?= $this->lang->line('application_enter_sku'); ?>" value="<?php echo set_value('sku', $product_data['sku']); ?>" autocomplete="off" onKeyUp="checkSpecialSku(event, this);" onblur="checkSpecialSku(event, this);" />
								<?php echo '<i style="color:red">' . form_error('sku') . '</i>'; ?>
							</div>

							<div class="form-group col-md-3 col-xs-12">
								<label for="status"><?= $this->lang->line('application_status'); ?>(*)</label>
								<input type="hidden" id="status" name="status" value="<?= $product_data['status'] ?>">
								<div id="ProductStatusComponent" data-base-url="<?= base_url() ?>" data-endpoint="products" data-ref-element="$('#status')" data-origin="productsKit/update" data-product-status="<?= $product_data['status'] ?>" data-product-id="<?= $product_data['id'] ?>" data-enable-delete="<?= in_array('moveProdTrash', $this->permission) ?>" data-status='<?php echo json_encode([
																																																																																																																																																																																				[
																																																																																																																																																																																					'code' => Model_products::ACTIVE_PRODUCT,
																																																																																																																																																																																					'alias' => 'active',
																																																																																																																																																																																					'description' => $this->lang->line('application_active'),
																																																																																																																																																																																				],
																																																																																																																																																																																				[
																																																																																																																																																																																					'code' => Model_products::INACTIVE_PRODUCT,
																																																																																																																																																																																					'alias' => 'inactive',
																																																																																																																																																																																					'description' => $this->lang->line('application_inactive')
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
																																																																																																																																																																																			]); ?>'>
								</div>
								<?php
								include_once APPPATH . 'views/products/components/popup.update.status.product.php';
								?>
								<script src="<?php echo base_url('assets/dist/js/components/products/product.status.component.js') ?>"></script>
							</div>
							<small><i>
									<div class="form-group col-md-3 col-xs-12">
										<label><?= $this->lang->line('application_created_on'); ?></label>
										<span><?php echo date("d/m/Y H:i:s", strtotime($product_data['date_create'])); ?></span>
									</div>
									<div class="form-group col-md-3 col-xs-12">
										<label><?= $this->lang->line('application_updated_on'); ?></label>
										<span><?php echo date("d/m/Y H:i:s", strtotime($product_data['date_update'])); ?></span>
									</div>
								</i></small>

							<div class="col-md-12 col-xs-12">
								<hr>
							</div>

							<div class="form-group col-md-12 col-xs-12 <?php echo (form_error('description')) ? 'has-error' : '';  ?>">
								<label for="description"><?= $this->lang->line('application_description'); ?>(*)</label>
								<textarea type="text" <?= (isset($integracoes) && $notAdmin ? 'readonly' : '') ?> class="form-control" id="description" maxlength="<?= Model_products::CHARACTER_LIMIT_IN_FIELD_DESCRIPTION ?>" name="description" placeholder="<?= $this->lang->line('application_enter_description'); ?>" autocomplete="off"> <?php echo $product_data['description']; ?></textarea>
								<span id="char_description"></span><br />
								<span class="label label-warning" id="words_description" data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_explanation_of_forbidden_words'); ?>"></span>
								<?php echo '<i style="color:red">' . form_error('description') . '</i>'; ?>
							</div>
							<div class="row"></div>

							<div class="form-group col-md-3 col-xs-12">
								<label for="price"><?= $this->lang->line('application_price'); ?>(*) <button type="button" class="btn btn-primary" onclick="updatePrice(event,<?php echo $product_data['id']; ?>)" data-toggle="modal" data-target="#updatePriceModal"><i class="fa fa-pencil"></i></button> </label>
								<input type="text" class="form-control maskdecimal2" id="price" name="price" disabled value="<?php echo set_value('price', $product_data['price']); ?>" autocomplete="off" />
							</div>

							<div class="form-group col-md-3 col-xs-12">
								<label for="qty"><?= $this->lang->line('application_qty'); ?>(*)</label>
								<input type="text" class="form-control" id="qty" name="qty" disabled required placeholder="<?= $this->lang->line('application_enter_qty'); ?>" value="<?php echo set_value('qty', $product_data['qty']); ?>" autocomplete="off" onKeyPress="return digitos(event, this);" />
							</div>

							<div class="row"></div>

							<!-- dimensões do produto embalado -->
							<div class="panel panel-primary">
								<div class="panel-heading"><?= $this->lang->line('application_packaged_product_dimensions') ?> &nbsp
									<span class="h6"> <?= $this->lang->line('application_packaged_product_dimensions_explain') ?></span>
								</div>

								<div class="panel-body">
									<div class="form-group col-md-3 col-xs-12 <?php echo (form_error('peso_bruto')) ? 'has-error' : '';  ?>">
										<label for="peso_bruto"><?= $this->lang->line('application_weight'); ?>(*)</label>
										<div class="input-group">
											<input type="text" <?= (isset($integracoes) && $notAdmin ? 'readonly' : '') ?> class="form-control maskdecimal3" id="peso_bruto" name="peso_bruto" required placeholder="<?= $this->lang->line('application_enter_gross_weight'); ?>" value="<?php echo set_value('peso_bruto', $product_data['peso_bruto']); ?>" autocomplete="off" />
											<span class="input-group-addon"><strong>Kg</strong></span>
										</div>
										<?php echo '<i style="color:red">' . form_error('peso_bruto') . '</i>'; ?>
									</div>

									<div class="form-group col-md-2 col-xs-12 <?php echo (form_error('largura')) ? 'has-error' : '';  ?>">
										<label for="largura"><?= $this->lang->line('application_width'); ?>(*)</label>
										<div class="input-group">
											<input type="text" <?= (isset($integracoes) && $notAdmin ? 'readonly' : '') ?> class="form-control maskdecimal2" id="largura" name="largura" required placeholder="<?= $this->lang->line('application_enter_width'); ?>" value="<?php echo set_value('largura', $product_data['largura']); ?>" autocomplete="off" onKeyPress="return digitos(event, this);" />
											<span class="input-group-addon"><strong>cm</strong></span>
										</div>
										<?php echo '<i style="color:red">' . form_error('largura') . '</i>'; ?>
									</div>

									<div class="form-group col-md-2 col-xs-12 <?php echo (form_error('altura')) ? 'has-error' : '';  ?>">
										<label for="altura"><?= $this->lang->line('application_height'); ?>(*)</label>
										<div class="input-group">
											<input type="text" <?= (isset($integracoes) && $notAdmin ? 'readonly' : '') ?> class="form-control maskdecimal2" id="altura" name="altura" required placeholder="<?= $this->lang->line('application_enter_height'); ?>" value="<?php echo set_value('altura', $product_data['altura']); ?>" autocomplete="off" onKeyPress="return digitos(event, this);" />
											<span class="input-group-addon"><strong>cm</strong></span>
										</div>
										<?php echo '<i style="color:red">' . form_error('altura') . '</i>'; ?>
									</div>

									<div class="form-group col-md-2 col-xs-12 <?php echo (form_error('profundidade')) ? 'has-error' : '';  ?>">
										<label for="profundidade"><?= $this->lang->line('application_depth'); ?>(*)</label>
										<div class="input-group">
											<input type="text" <?= (isset($integracoes) && $notAdmin ? 'readonly' : '') ?> class="form-control maskdecimal2" id="profundidade" name="profundidade" required placeholder="<?= $this->lang->line('application_enter_depth'); ?>" value="<?php echo set_value('profundidade', $product_data['profundidade']); ?>" autocomplete="off" onKeyPress="return digitos(event, this);" />
											<span class="input-group-addon"><strong>cm</strong></span>
										</div>
										<?php echo '<i style="color:red">' . form_error('profundidade') . '</i>'; ?>
									</div>

									<div class="form-group col-md-3 col-xs-12 <?php echo (form_error('products_package')) ? 'has-error' : '';  ?>">
										<label for="products_package" data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_how_many_units'); ?>"><?= $this->lang->line('application_products_by_packaging'); ?>(*)</label>
										<div class="input-group">
											<input type="text" <?= (isset($integracoes) && $notAdmin ? 'readonly' : '') ?> class="form-control maskdecimal3" id="products_package" name="products_package" required placeholder="<?= $this->lang->line('application_enter_quantity_products'); ?>" value="<?php echo set_value('products_package', $product_data['products_package']) ?>" autocomplete="off" />
											<span class="input-group-addon"><strong>Qtd</strong></span>
										</div>
										<?php echo '<i style="color:red">' . form_error('products_package') . '</i>'; ?>
									</div>
								</div>
							</div>
							<!-- fim das dimensões do produto embalado -->

							<!-- dimensões do produto fora da embalagem -->
							<div class="panel panel-primary">
								<div class="panel-heading"><?= $this->lang->line('application_product_dimensions') ?> &nbsp
									<span class="h6"> <?= $this->lang->line('application_out_of_the_package') ?></span>
								</div>
								<div class="panel-body">
									<div class="form-group col-md-3 col-xs-12 <?php echo (form_error('peso_liquido')) ? 'has-error' : '';  ?>">
										<label for="peso_liquido"><?= $this->lang->line('application_net_weight'); ?>(*)</label>
										<div class="input-group">
											<input type="text" <?= (isset($integracoes) && $notAdmin ? 'readonly' : '') ?> class="form-control maskdecimal3" id="peso_liquido" name="peso_liquido" required placeholder="<?= $this->lang->line('application_enter_net_weight'); ?>" value="<?php echo set_value('peso_liquido', $product_data['peso_liquido']); ?>" autocomplete="off" />
											<span class="input-group-addon"><strong>Kg</strong></span>
										</div>
										<?php echo '<i style="color:red">' . form_error('peso_liquido') . '</i>'; ?>
									</div>

									<div class="form-group col-md-3 col-xs-12 <?php echo (form_error('actual_width')) ? 'has-error' : '';  ?>">
										<label for="actual_width"><?= $this->lang->line('application_width'); ?></label>
										<div class="input-group">
											<input type="text" <?= (isset($integracoes) && $notAdmin ? 'readonly' : '') ?> class="form-control maskdecimal2" id="actual_width" name="actual_width" placeholder="<?= $this->lang->line('application_enter_actual_width'); ?>" value="<?php echo set_value('actual_width', $product_data['actual_width']) ?>" autocomplete="off" onKeyPress="return digitos(event, this);" />
											<span class="input-group-addon"><strong>cm</strong></span>
										</div>
										<?php echo '<i style="color:red">' . form_error('actual_width') . '</i>'; ?>
									</div>

									<div class="form-group col-md-3 col-xs-12 <?php echo (form_error('actual_height')) ? 'has-error' : '';  ?>">
										<label for="actual_height"><?= $this->lang->line('application_height'); ?></label>
										<div class="input-group">
											<input type="text" <?= (isset($integracoes) && $notAdmin ? 'readonly' : '') ?> class="form-control maskdecimal2" id="actual_height" name="actual_height" placeholder="<?= $this->lang->line('application_enter_actual_height'); ?>" value="<?php echo set_value('actual_height', $product_data['actual_height']) ?>" autocomplete="off" onKeyPress="return digitos(event, this);" />
											<span class="input-group-addon"><strong>cm</strong></span>
										</div>
										<?php echo '<i style="color:red">' . form_error('actual_height') . '</i>'; ?>
									</div>

									<div class="form-group col-md-3 col-xs-12 <?php echo (form_error('actual_depth')) ? 'has-error' : '';  ?>">
										<label for="actual_depth"><?= $this->lang->line('application_depth'); ?></label>
										<div class="input-group">
											<input type="text" <?= (isset($integracoes) && $notAdmin ? 'readonly' : '') ?> class="form-control maskdecimal2" id="actual_depth" name="actual_depth" placeholder="<?= $this->lang->line('application_enter_actual_depth'); ?>" value="<?php echo set_value('actual_depth', $product_data['actual_depth']) ?>" autocomplete="off" onKeyPress="return digitos(event, this);" />
											<span class="input-group-addon"><strong>cm</strong></span>
										</div>
										<?php echo '<i style="color:red">' . form_error('actual_depth') . '</i>'; ?>
									</div>
								</div>
							</div>
							<!-- fim das dimensões do produto fora da embalagem -->

							<div class="row"></div>

							<div class="form-group col-md-3 col-xs-12 <?php echo (form_error('garantia')) ? 'has-error' : ''; ?>">
								<label for="garantia"><?= $this->lang->line('application_garanty'); ?>(* <?= $this->lang->line('application_in_months'); ?>)</label>
								<input type="text" <?= (isset($integracoes) && $notAdmin ? 'readonly' : '') ?> class="form-control" id="garantia" name="garantia" required placeholder="<?= $this->lang->line('application_enter_warranty'); ?>" value="<?php echo set_value('garantia', $product_data['garantia']); ?>" autocomplete="off" onKeyPress="return digitos(event, this);" />
								<?php echo '<i style="color:red">' . form_error('garantia') . '</i>'; ?>
							</div>

							<?php $attribute_id = json_decode($product_data['attribute_value_id']);
							if (is_null($attribute_id)) {
								$attribute_id = array("[]");
							}
							?>
							<?php if ($attributes) : ?>
								<?php foreach ($attributes as $k => $v) : ?>
									<div class="form-group col-md-3 col-xs-12">
										<label for="groups"><?php echo $v['attribute_data']['name'] ?>(*)</label>
										<select class="form-control select_group" style="width:80%" id="attributes_value_id" name="attributes_value_id[]">
											<?php foreach ($v['attribute_value'] as $k2 => $v2) {
												$disabledAttributes = (isset($integracoes) && (!in_array($v2['id'], $attribute_id)) && $notAdmin) ? 'disabled' : ''; ?>
												<option value="<?php echo $v2['id'] ?>" <?= $disabledAttributes ?> <?php echo set_select('attributes_value_id', $v2['id'], in_array($v2['id'], $attribute_id)) ?>><?php echo $v2['value'] ?></option>
											<?php } ?>
										</select>
									</div>
								<?php endforeach ?>
							<?php endif; ?>

							<div class="form-group col-md-3 col-xs-12 ">
								<label for="store"><?= $this->lang->line('application_store'); ?>(*)</label>
								<span class="form-control"><?php echo $store['name']; ?></span>
								<input type="hidden" class="form-control" id="store" name="store" value="<?php echo $product_data['store_id']; ?>" />
							</div>

							<div class="form-group col-md-3 col-xs-12 <?php echo (form_error('prazo_operacional_extra')) ? 'has-error' : ''; ?>">
								<label for="prazo_operacional_extra"><?= $this->lang->line('application_extra_operating_time'); ?></label>
								<input type="text" class="form-control" maxlength="2" id="prazo_operacional_extra" name="prazo_operacional_extra" placeholder="<?= $this->lang->line('application_extra_operating_time'); ?>" value="<?php echo set_value('prazo_operacional_extra', $product_data['prazo_operacional_extra']) ?>" autocomplete="off" onKeyPress="return digitos(event, this);" />
								<?php echo '<i style="color:red">' . form_error('prazo_operacional_extra') . '</i>'; ?>
							</div>

							<?php if (!isset($brand_data)) {
								$brand_data = array();
							} ?>
							<div class="form-group col-md-5 col-xs-12 <?php echo (form_error('brands[]')) ? 'has-error' : '';  ?>">

								<label for="brands" class="d-flex justify-content-between">
									<?= $this->lang->line('application_brands'); ?>(*)
									<?php if (!$disableBrandCreationbySeller) { ?>
										<a href="#" onclick="AddBrand(event)"><i class="fa fa-plus-circle"></i> <?= $this->lang->line('application_add_brand'); ?></a>
									<?php } ?>
								</label>
								<?php
								$brand_data = json_decode($product_data['brand_id']);
								if (!isset($brand_data)) {
									$brand_data = array();
								}
								?>
								<!---- <select class="form-control select_group" id="brands" name="brands[]" > ---->
								<select class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" id="brands" name="brands[]" title="<?= $this->lang->line('application_select'); ?>">
									<option value=""><?= $this->lang->line('application_select'); ?></option>
									<?php foreach ($brands as $k => $v) {
										$disabledBrands = (isset($integracoes) && (!in_array($v['id'], $brand_data)) && $notAdmin) ? 'disabled' : ''; ?>
										<option value="<?php echo $v['id'] ?>" <?= $disabledBrands ?> <?php echo set_select('brands', $v['id'], in_array($v['id'], $brand_data)) ?>><?php echo $v['name'] ?></option>
									<?php } ?>
								</select>
								<?php echo '<i style="color:red">' . form_error('brands[]') . '</i>'; ?>
							</div>

							<div class="form-group col-md-12 col-xs-12">
								<label for="category"><?= $this->lang->line('application_categories'); ?>(*)</label>
								<?php
								$category_data = json_decode($product_data['category_id']);
								if (!isset($category_data)) {
									$category_data =  array();
								}
								?>
								<select class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" id="category" name="category[]" title="<?= $this->lang->line('application_select'); ?>">
									<option value=""><?= $this->lang->line('application_select'); ?></option>
									<?php foreach ($category as $k => $v) {
										$disabledCategory = (isset($integracoes) && (!in_array($v['id'], $category_data)) && $notAdmin) ? 'disabled' : ''; ?>
										<option value="<?php echo $v['id'] ?>" <?= $disabledCategory ?> <?php echo set_select('category', $v['id'], in_array($v['id'], $category_data)); ?>><?php echo $v['name'] ?></option>
									<?php } ?>
								</select>
								<?php
								if ($product_data['category_imported'] != "")
									echo "<small><strong>{$this->lang->line('application_imported_category')}</strong>: {$product_data['category_imported']}</small>"
								?>
							</div>

							<div id='linkcategory'></div>

							<div class="row"></div>

							<div class="form-group col-md-12">
								<div class="callout callout-warning mb-0" style="display: none" id="listBlockView">
									<h4 class="mt-0"><?= $this->lang->line('application_blocked_product') ?></h4>
									<ul></ul>
								</div>
							</div>

							<?php if (isset($integracoes)) : ?>
								<div class="form-group col-md-12 col-xs-12" style="overflow-x: auto">
									<label for="Integracoes"><?= $this->lang->line('application_integrations'); ?></label>
									<table style="width:100%;" class="table table-striped table-bordered">
										<tr style="background-color: #f1f1c1; border: 1px solid black; border-collapse: collapse; font-size: smaller">
											<th style="">Marketplace</th>
											<th style="">SKU ConectaLá</th>
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
														$ad_link .= '<a target="__blank" href="' . $link['href'] . '" class="btn btn-default"><i class="fa fa-money"></i> <small>' . $link['name'] . '</small></a><br>';
													}
												} else {
													if (strpos($integracao['ad_link'], 'http') !== false) {
														$ad_link .= '<a target="__blank" href="' . $integracao['ad_link'] . '" class="btn btn-default"><i class="fa fa-money"></i> <small>' . $this->lang->line('application_goto_ad') . '</small></a>';
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
														<button onclick="sendToMarketplace(event,'<?= $integracao['int_to'] ?>','<?= $product_data['id'] ?>')" class="pull-right btn btn-success btn-sm"><?= $this->lang->line('application_send'); ?></button>
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
													<?php if (($data_int != '--') && (!$notAdmin)) : ?>
														<br><a href="<?php echo base_url("products/log_integration_marketplace/" . $integracao['int_to'] . "/" . $product_data['id']) ?>"><?= $this->lang->line('application_integration_log_with') . ' ' . $integracao['int_to']; ?></a>
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
																	<button onclick="changeIntegrationApproval(event,'<?= $integracao['id'] ?>','<?= $product_data['id'] ?>','1','<?= $integracao['approved'] ?>','<?= $integracao['int_to'] ?>')" class="btn btn-success"><small><?= $this->lang->line('application_approve'); ?></small></button>
																<?php endif ?>
																<?php if ($integracao['approved'] != 2) : ?>
																	<button onclick="changeIntegrationApproval(event,'<?= $integracao['id'] ?>','<?= $product_data['id'] ?>','2','<?= $integracao['approved'] ?>','<?= $integracao['int_to'] ?>')" class="btn btn-danger"><small><?= $this->lang->line('application_disapprove'); ?></small></button>
																<?php endif ?>
																<?php if ($integracao['approved'] != 3) : ?>
																	<button onclick="changeIntegrationApproval(event,'<?= $integracao['id'] ?>','<?= $product_data['id'] ?>','3','<?= $integracao['approved'] ?>','<?= $integracao['int_to'] ?>')" class="btn btn-primary"><small><?= $this->lang->line('application_mark_as_in_approval'); ?></small></button>
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
							<?php endif ?>

							<?php if (count($errors_transformation) > 0) : ?>
								<div class="form-group col-md-12 col-xs-12">
									<h3><span id="errors-transformation" class="label label-danger"><?= $this->lang->line('application_errors_tranformation'); ?></span></h3>
									<table class="table table-bordered table-striped table-dark">
										<thead class="thead-light">
											<tr style="background-color: #f44336; border: 1px solid black; border-collapse: collapse; color:white">
												<th width="10%"><?= $this->lang->line('application_marketplace'); ?></th>
												<th width="10%"><?= $this->lang->line('application_step'); ?></th>
												<th width="10%"><?= $this->lang->line('application_date'); ?></th>
												<th width="70%"><?= $this->lang->line('application_error'); ?></th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ($errors_transformation as $error_transformation) :   ?>
												<tr style="background-color: lightgray; border: 1px solid black; border-collapse: collapse;">
													<td width="10%"><?php echo $error_transformation['int_to']; ?></td>
													<td width="10%"><?php echo $error_transformation['step']; ?></td>
													<td width="10%"><?php echo  date('d/m/Y H:i:s', strtotime($error_transformation['date_update'])); ?></td>
													<td width="70%"><?php echo $error_transformation['message']; ?></td>
												</tr>
											<?php endforeach ?>
										</tbody>
									</table>
								</div>
							<?php endif ?>

							<div class="row"></div>
							<div class="box-footer">
								<button type="submit" class="btn btn-primary" <?= $product_data['status'] == Model_products::DELETED_PRODUCT ? 'style="display:none;"' : '' ?>><?= $this->lang->line('application_update_changes'); ?></button>
								<a href="<?php echo base_url('products/') ?>" class="btn btn-warning"><?= $this->lang->line('application_back'); ?></a>
								<?php if ($allow_delete) : ?>
									<button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteProductKitModal"><i class="fa fa-trash"></i>&nbsp;<?= $this->lang->line('application_delete_product_kit'); ?></button>
								<?php endif ?>
								<?php if (!$show_attributes_button) {
									$attribute = preg_replace("/[^0-9]/", "", $product_data['category_id']); ?>
									<a href="<?php echo base_url("products/attributes/edit/$product_data[id]/$attribute") ?>" class="btn btn-info">Atributos</a>
								<?php } ?>
								<a href="<?php echo base_url("products/log_products_view/$product_data[id]") ?>" class="pull-right btn btn-warning"><?= $this->lang->line('application_latest_changes'); ?></a>
							</div>

						</div>
						<!-- /.box-body -->

					</form>
				</div>
				<!-- /.box -->
			</div>
			<!-- col-md-12 -->
		</div>
		<!-- /.row -->


	</section>
	<!-- /.content -->
</div>
<!-- /.content-wrapper -->


<div class="modal fade" tabindex="-1" role="dialog" id="updatePriceModal">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" style="color:blue;"><?php echo $this->lang->line('application_change_campaign_product'); ?></span></h4>
			</div>
			<form role="form" action="<?php echo base_url('productsKit/updatePrice') ?>" method="post" id="updatePrice">
				<div class="modal-body">
					<div class="table-responsive-sm">
						<table class="table">
							<tr>
								<th style=""><?= $this->lang->line('application_sku'); ?></th>
								<th style=""><?= $this->lang->line('application_product'); ?></th>
								<th style=""><?= $this->lang->line('application_item_qty'); ?></th>
								<th style=""><?= $this->lang->line('application_price'); ?></th>
							</tr>
							<?php foreach ($productskit as $productkit) :   ?>
								<tr>
									<input type="hidden" name="id_product[]" style="text-align:right;" id="id_product[]" required class="form-control" value="<?php echo $productkit['product_id_item']; ?>" autocomplete="off" />
									<input type="hidden" name="qty_product_<?php echo $productkit['product_id_item']; ?>" style="text-align:right;" id="qty_product_<?php echo $productkit['product_id_item']; ?>" required class="form-control" value="<?php echo $productkit['qty']; ?>" autocomplete="off" />

									<td style=""><?php echo $productkit['sku']; ?></td>
									<td style=""><?php echo $productkit['name']; ?></td>
									<td style=""><?php echo $productkit['qty']; ?></td>
									<td>
										<input type="text" required name="price_product_<?php echo $productkit['product_id_item']; ?>" style="text-align:right;" id="price_product_<?php echo $productkit['product_id_item']; ?>" required class="form-control maskdecimal2" value="<?php echo set_value('price_product_' . $productkit['product_id_item'], $productkit['price']) ?>" autocomplete="off" />
									</td>
								</tr>
							<?php endforeach ?>
						</table>
					</div>
				</div> <!-- modal-body -->
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal"><?= $this->lang->line('application_close'); ?></button>
					<button type="submit" class="btn btn-primary"><?= $this->lang->line('application_update_changes'); ?></button>
				</div>
			</form>
			</form>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" tabindex="-1" role="dialog" id="deleteProductKitModal">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" style="color:red;"><?= $this->lang->line('application_delete_product_kit'); ?></h4>
			</div>
			<form role="form" action="<?php echo base_url('productsKit/removeProductKit/' . $product_data['id']) ?>" method="post" id="deleteProductKitForm">
				<div class="modal-body">
					<p><?= $this->lang->line('application_confirm_delete_product_kit'); ?></p>
				</div> <!-- modal-body -->
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal"><?= $this->lang->line('application_cancel'); ?></button>
					<button type="submit" class="btn btn-primary" id="do_filter" name="do_filter"><?= $this->lang->line('application_confirm'); ?></button>
				</div>
			</form>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" tabindex="-1" role="dialog" id="addBrandModal">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title"><?= $this->lang->line('application_add_brand'); ?></h4>
			</div>

			<form role="form" action="<?php echo base_url('brands/create') ?>" method="post" id="createBrandForm">

				<div class="modal-body">

					<div class="form-group">
						<label for="brand_name"><?= $this->lang->line('application_name'); ?></label>
						<input type="text" class="form-control" id="brand_name" name="brand_name" placeholder="<?= $this->lang->line('application_enter_brand_name') ?>" autocomplete="off">
					</div>
					<input type="hidden" id="active" name="active" value="1" />
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal"><?= $this->lang->line('application_close'); ?></button>
					<button type="submit" class="btn btn-primary"><?= $this->lang->line('application_save'); ?></button>
				</div>

			</form>

		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script type="text/javascript" src="<?= HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script src="<?php echo base_url('assets/dist/js/components/products/product.disable.form.js') ?>"></script>

<script type="text/javascript">
	var manageTable;
	var base_url = "<?php echo base_url(); ?>";
	var total_prod = 0;
	var total_price = 0;
	var total_qty = 0;
	var price_format = 0;
	var wrapper = $(".input_fields_wrap"); //Fields wrapper
	var update_category = "<?php echo in_array('updateCategory', $this->permission) ?>"
	var productDeleted = '<?= $product_data['status'] == Model_products::DELETED_PRODUCT ?>';
    var onBucket = <?php echo $product_data['is_on_bucket']?> // Flag para definir se o produto está ou não no bucket.

	$(document).ready(function() {

		if (productDeleted) {
			(new ProductDisableForm({
				form: $('#formedit')
			})).disableForm();
		}

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
				onBlur: function(e) {
					verifyWords();
				},
				onKeyup: function(e) {
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

		if ($('#description').attr('readonly') == 'readonly') {
			$('#description').summernote('disable');
		}

		verifyWords();

		$('.maskdecimal2').inputmask({
			alias: 'numeric',
			allowMinus: false,
			digits: 2,
			max: 999999999.99
		});

		changeCategory($('#category option:selected').val());

		var token = '<?= $product_data['image']; ?>'; // My Token
		$("#prd_image").fileinput({
			uploadUrl: "<?= base_url('/Products/saveImageProduct'); ?>",
			language: 'pt-BR',
			allowedFileExtensions: ["jpg", "png"],
			minImageWidth: <?= $dimenssion_min_product_image ?? 'null' ?>,
			minImageHeight: <?= $dimenssion_min_product_image ?? 'null' ?>,
			maxImageWidth: <?= $dimenssion_max_product_image ?? 'null' ?>,
			maxImageHeight: <?= $dimenssion_max_product_image ?? 'null' ?>,
			enableResumableUpload: true,
			autoOrientImage: false,
			resumableUploadOptions: {
				// uncomment below if you wish to test the file for previous partial uploaded chunks
				// to the server and resume uploads from that point afterwards
				// testUrl: "http://localhost/test-upload.php"
			},
			uploadExtraData: {
				'uploadToken': token, // for access control / security
				'onBucket': onBucket 
			},
			maxFileCount: 5,
			allowedFileTypes: ['image'], // allow only images
			showCancel: true,
			initialPreviewAsData: true,
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
			overwriteInitial: false,
			// initialPreview: [],          // if you have previously uploaded preview files
			// initialPreviewConfig: [],    // if you have previously uploaded preview files
			theme: 'fas',
			deleteUrl: "<?= base_url($product_data['is_on_bucket']?'Products/removeImageProduct':'/assets/plugins/fileinput/examples/delete.php'); ?>",
		}).on('filesorted', function(event, params) {
			changeTheOrderOfImages(params)
			console.log('File sorted params', params);
		}).on('fileuploaded', function(event, previewId, index, fileId) {
			console.log('File Uploaded', 'ID: ' + fileId + ', Thumb ID: ' + previewId);
			$('#product_image').val(token);
		}).on('fileuploaderror', function(event, data, msg) {
			console.log('File Upload Error', 'ID: ' + data.fileId + ', Thumb ID: ' + data.previewId);
		}).on('filebatchuploadcomplete', function(event, preview, config, tags, extraData) {
			console.log('File Batch Uploaded', preview, config, tags, extraData);
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
				success: function(success) {
					console.log(success)
				},
				error: function(error) {
					console.log(error)
				}
			});
		}

		$('#category').change(function() {
			var idcat = $('#category option:selected').val();
			changeCategory(idcat);
			verifyWords();
		});

		characterLimit(document.getElementById('product_name'));
		characterLimit(document.getElementById('description'));
	});

	function updatePrice(e, id) {
		e.preventDefault();

		// submit the edit from 
		$("#updatePrice").unbind('submit').bind('submit', function() {
			var form = $(this);

			// remove the text-danger
			$(".text-danger").remove();

			$.ajax({
				url: form.attr('action') + '/' + id,
				type: form.attr('method'),
				data: form.serialize(), // /converting the form data into array and sending it to server
				dataType: 'json',
				success: function(response) {
					if (response.success === true) {
						//alert(response.messages);

						$("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">' +
							'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
							'<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>' + response.messages +
							'</div>');

						// hide the modal
						$("#updatePriceModal").modal('hide');
						// reset the form 
						$("#updatePrice .form-group").removeClass('has-error').removeClass('has-success');
						Swal.fire({
							icon: 'success',
							title: response.messages,
							showCancelButton: false,
							confirmButtonText: "Ok",
						}).then((result) => {
							location.reload()
						});


					} else {

						if (response.messages instanceof Object) {
							$.each(response.messages, function(index, value) {
								var id = $("#" + index);
								id.closest('.form-group')
									.removeClass('has-error')
									.removeClass('has-success')
									.addClass(value.length > 0 ? 'has-error' : 'has-success');

								id.after(value);

							});
						} else {
							$("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">' +
								'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
								'<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>' + response.messages +
								'</div>');
						}
					}
				}
			});

			return false;
		});

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
			success: function(data) {
				location.reload();
			},
			error: function(data) {
				AlertSweet.fire({
					icon: 'Error',
					title: 'Houve um erro ao atualizar o produto!'
				});
			}
		});

	}

	function toggleCatMktPlace(e) {
		e.preventDefault();
		$("#catlinkdiv").toggle();
	}

	function changeCategory(id) {
		var catlink = $("#linkcategory");
		var cattable = $("#catlinkbuttondiv");
		cattable.remove();
		$.ajax({
			type: 'GET',
			dataType: "json",
			url: base_url + 'category/getLinkCategory/',
			data: "idcat=" + id,
			success: function(data) {
				var cats = '<div id="catlinkbuttondiv" class="form-group col-md-12 col-xs-12"><button type="button" onClick="toggleCatMktPlace(event)" > Categorias por Marketplace</button>';
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
	}

	function verifyWords() {
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
			success: function(response) {

				console.log(response);

				if (response.blocked) {
					let messageBlock = '';

					$(response.data).each(function(index, value) {
						messageBlock += `<li>${value}</li>`;
					});

					$('#listBlockView ul').empty().html(messageBlock);
					$('#listBlockView').show();
				} else {
					$('#listBlockView ul').empty().html('');
					$('#listBlockView').hide();
				}
			},
			error: function(error) {
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

		$('#char_' + attribute).text(`Caracteres digitados: ${quantity}/${limit}`);
	}

	function AddBrand(e) {
		e.preventDefault();
		$("#addBrandModal").modal('show');
	}

	// submit the create from
	$("#createBrandForm").unbind('submit').on('submit', function() {
		var form = $(this);

		// remove the text-danger
		$(".text-danger").remove();

		$.ajax({
			url: form.attr('action'),
			type: form.attr('method'),
			data: form.serialize(), // /converting the form data into array and sending it to server
			dataType: 'json',
			success: function(response) {

				// manageTable.ajax.reload(null, false);

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
						$.each(response.messages, function(index, value) {
							var id = $("#" + index);

							id.closest('.form-group')
								.removeClass('has-error')
								.removeClass('has-success')
								.addClass(value.length > 0 ? 'has-error' : 'has-success');

							id.after(value);

						});
					} else {
						alert(response.messages);
						$("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">' +
							'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
							'<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>' + response.messages +
							'</div>');
					}
				}
			}
		});

		return false;
	});

	$('#brands').change(function() {
		verifyWords();
	});
	$('#sku').blur(function() {
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
					success: function(data) {
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
					error: function(data) {
						AlertSweet.fire({
							icon: 'error',
							title: '<?= $this->lang->line("there_was_an_error_placing_the_product_in_the_queue") ?>'
						});
					}
				});
			}
		});
	}
</script>