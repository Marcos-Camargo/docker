<?php
//Products.php
$lang['api_sku_not_informed'] = "SKU code not informed.";
$lang['api_resource_unavailable'] = "Resource unavailable.";
$lang['api_sku_not_found'] = "SKU not found.";
$lang['api_product_not_found'] = "Product not found.";
$lang['api_trash_product'] = "Trash product.";
$lang['api_trash_product_cannot_updated'] = "This product has been moved to the trash and cannot be updated.";
$lang['api_json_invalid'] = "JSON invalid.";
$lang['api_json_invalid_format'] = "JSON sent in invalid format.";
$lang['api_sku_code_not_informed'] = "SKU code not informed!";
$lang['api_feature_unavailable'] = "Feature unavailable.";
$lang['api_feature_unavailable_catalog'] = "This feature is not available to sellers who sell catalog products.";
$lang['api_attribute_updated'] = "Attribute successfully updated.";
$lang['api_product_updated'] = "Product successfully updated.";
$lang['api_attribute_inserted'] = "Attribute successfully inserted.";
$lang['api_product_inserted'] = "Product successfully inserted.";
$lang['api_product_synced'] = "Product successfully synced.";
$lang['api_trash_product_already'] = "This product has already been moved to the trash.";
$lang['api_product_removed'] = "Product successfully removed.";
$lang['api_deprecated_functionality'] = "Deprecated functionality.";
$lang['api_failure_communicate_database'] = "Failure to communicate to the database.";
$lang['api_not_remove_directory_img'] = "Could not remove directory of images.";
$lang['api_not_product_key'] = "Not found product key.";
$lang['api_not_addon_key'] = "Not found addon key.";
$lang['api_not_sku_addon_key'] = "Skus to add the Add-On not found.";
$lang['api_not_attribute_key'] = "Not found attribute key.";
$lang['api_no_data_create'] = "Found no data to create.";
$lang['api_no_data_update'] = "Found no data to update.";
$lang['api_list_price_price'] = "list_price cannot be less than price.";
$lang['api_parameter_not_match_field_insert'] = "Parameter entered does not match in an insert field: ";
$lang['api_parameter_not_match_field_update'] = "Parameter entered does not match in an update field: ";
$lang['api_all_fields_informed'] = "All fields need to be informed, missing field: ";
$lang['api_all_fields_filled'] = "All fields need to be filled in, field not informed: ";
$lang['api_type_variation_found'] = "Type of variation found, but variations were not found.";
$lang['api_variation_found'] = "Variation found, but types of variations were not found.";
$lang['api_not_allowed_send_than_images'] = "It is not allowed to send more than ";
$lang['api_images'] = "  images.";
$lang['api_images_in'] = "  images in ";
$lang['api_variation_th'] = "ª variation.";
$lang['api_not_allowed_send_than_images_variation'] = "It is not allowed to send more than ";
$lang['api_images_variations'] = "  images in the variation. ";
$lang['api_product_already_integrated'] = "Product already integrated with marketplace, cannot receive updates. Only stock, price and operational time allowed.";
$lang['api_product_contains_variation'] = "Product contains variation. Must update the stock of variations.";
$lang['api_qty_not_informed_variation'] = "Qty was not informed in the variation.";
$lang['api_sku_not_informed_variation'] = "Sku was not informed in the variation.";
$lang['api_sku_variation_different'] = "Variation SKU cannot be the same as the parent product sku.";
$lang['api_sku_in_use_other'] = "Product SKU is already in use in some other product or variation. "; 
$lang['api_sku_equal'] = "Variations SKU cannot be the equals. ";
$lang['api_variation_value_equal'] = "Variation value cannot be the equals";
$lang['api_type'] = "The type ";
$lang['api_type_not_declared'] = " was not declared in the product variation.";
$lang['api_voltage_variation'] = "The voltage of the variation must be 110, 220 or bivolt.";
$lang['api_type_variation'] = "Type of variation not found, valid variation: color, size, voltage, flavor.";
$lang['api_invalid_ean'] = "Invalid Variation EAN. ";
$lang['api_ean_same_variation'] = "The same EAN is not allowed for more than one variation.";
$lang['api_not_move_product_trash'] = "It's not possible to move the product to the trash through this resource.";
$lang['api_price_zero'] = "Price field cannot be zero or negative.";
$lang['api_sku_blank'] = "The SKU code must not be blank!";
$lang['api_sku_in_use'] = "The SKU code is already in use!";
$lang['api_active_field'] = "In the active field, only enabled/disabled is allowed";
$lang['api_invalid_ncm'] = "Code NCM invalid, must contain 8 characters!";
$lang['api_origin_product_code'] = "Product origin code must be between 0 and 8! http://legislacao.sef.sc.gov.br/html/regulamentos/icms/ricms_01_10.htm";
$lang['api_invalid_unit'] = "Informed unit not found, please enter a valid one. (UN/Kg)";
$lang['api_invalid_manufacturer'] = "Informed manufacturer not found, please enter a valid one.";
$lang['api_invalid_category'] = "Entered category not found, enter a valid one.";
$lang['api_net_weight_zero'] = "net_weight field cannot be less than 0.";
$lang['api_gross_weight_zero'] = "gross_weight field cannot be less than 0.";
$lang['api_width_zero'] = "width field cannot be less than 0.";
$lang['api_height_zero'] = "height field cannot be less than 0.";
$lang['api_depth_zero'] = "depth field cannot be less than 0.";
$lang['api_description_blank'] = "Description must not be blank!";
$lang['api_extra_operating_time'] = "extra_operating_time field cannot be greater than 99.";
$lang['api_no_results_where'] = "No results were found.";
$lang['api_trash_product_api'] = "This product has been moved to the trash and cannot be provide by API.";
$lang['api_no_attributes_product'] = "No attributes found for this product!";
$lang['api_field_not_identify_code'] = "The {code} field was not found to identify the attribute of the ";
$lang['api_th_item'] = "th item.";
$lang['api_attribute_code_not_match'] = "Attribute code ( ";
$lang['api_attribute_code_not_match_end'] = " ), does not match any attribute.";
$lang['api_attribute_code_not_found'] = "The attribute specified in the code  ";
$lang['api_attribute_code_not_found_end'] = " was not found. Query that same route by the [GET] method.";
$lang['api_field_not_found_attribute'] = "Field {value} not found to update code attribute ";
$lang['api_attribute_multiple_values'] = "The code attribute ";
$lang['api_attribute_multiple_values_end'] = ", does not receive multiple values, must be informed a string, not an array.";
$lang['api_value_attribute_not_allowed'] = "The value entered ( ";
$lang['api_value_attribute_not_allowed_half'] = " ) in the code attribute ";
$lang['api_value_attribute_not_allowed_end'] = ", was not found in the allowed values. Query that same route by the [GET] method.";
$lang['api_field_not_identify_sku'] = "The {sku} Field was not found to identify the product of the ";
$lang['api_field_not_identify_name'] = "The {name} field was not found to identify the attribute of the ";
$lang['api_field_not_identify_value'] = "The {value} field was not found to set the attribute value of the ";
$lang['api_product_item_not_found'] = "The product for the ";
$lang['api_item'] = "th item was not found.";
$lang['api_product'] = "Product ";
$lang['api_item_attribute_product'] = "th item is already a product attribute. If you want to update it, call the [PUT] method.";
$lang['api_payload_not_valid'] = "Payload did not return valid result";
$lang['api_marketplace'] = "Marketplace ";
$lang['api_marketplace_not_found'] = " not found.";
$lang['api_product_already_marketplace'] = "Product already published for the marketplace ";
$lang['api_variation_already_marketplace'] = "Variation already published for the marketplace ";
$lang['api_product_must_complete'] = "The product must be complete and active.";
$lang['api_not_found_store'] = " not found for store ";
$lang['api_field_store_not_found'] = "Store field not informed.";
$lang['api_field_marketplace_not_found'] = "Marketplace field not informed.";
$lang['api_field_sku_not_found'] = "Sku field not informed.";
$lang['api_cannot_publish_parent_product'] = "Cannot publish a parent product, tells which variation to publish.";
$lang['api_product_already_integrated_qty_price_update'] = "Product already integrated into the marketplace, only stock and price have been updated.";
$lang['api_variation_limit'] = "Variation limit activated, please select up to two variations.";

//Api.php
$lang['api_not_headers'] = "Not all headers were sent. Missing header: ";
$lang['api_x-provider-key_api'] = "x-provider-key doens't match Key_API.";
$lang['api_cnpj_do'] = "Provider CNPJ ";
$lang['api_cnpj_do_end'] = " doens't match with no one company.";
$lang['api_cnpj_do_main'] = "Provider CNPJ ";
$lang['api_cnpj_do_main_end'] = " doens't match with main company.";
$lang['api_store_key_api'] = "Store code does not match Key_API.";
$lang['api_mail_key_api'] = "User email does not match Key_API.";
$lang['api_store_not_found'] = "Store not found.";
$lang['api_unknown_error'] = "Unknown error";
$lang['api_error_mail'] = "There was an error in the email. Debug: ";
$lang['api_success_mail'] = "Email successfully sent.";
$lang['api_x-store-seller-key_not'] = "x-store-seller-key not informed to store admin.";

//Attributes.php
$lang['api_category_not_informed'] = "Category Id code not informed.";
$lang['api_mkt_not_informed'] = "Mkt code not informed.";

//Brands.php
$lang['api_brand_inserted'] = "Brand successfully inserted.";
$lang['api_key_brand_not_found'] = "Not found brand key.";
$lang['api_found_no_data'] = "Found no data to insert in the brand.";
$lang['api_name_needs'] = "The name field the brand needs to be informed.";
$lang['api_name_in_use'] = "Name the brand is already in use.";

//Catalogs.php
$lang['api_sku_provider'] = "SKU code of the provider not informed.";
$lang['api_sku_manufacture_unique'] = "SKU manufacturer must be unique.";
$lang['api_ean_invalid'] = "Code EAN invalid!";

//CreatePromotion.php
$lang['api_received_data'] = "Received data.";
$lang['api_error_formatted'] = "ERROR - Wrongly formatted data received.";
$lang['api_data_not_json'] = "Data not in Json format.";
$lang['api_promotion_cretaed'] = "Promotion Cretaed.";
$lang['api_problem'] = "Problem.";

//Financial.php
$lang['api_operation_not_accepted'] = "Operation not accepted.";
$lang['api_parameter_not_provider'] = "Parameter ";
$lang['api_parameter_not_provider_end'] = " not provided.";
$lang['api_cycle_not_found'] = "Cycle not found for: ";
$lang['api_cycle_invalide_date'] = "Invalid Date";
$lang['api_internal_error_url'] = "Internal error inserting nfs url: ";
$lang['api_internal_error_group'] = "Internal error inserting nfs group: ";
$lang['api_no_order_generated'] = "No order generated to current request.";
$lang['api_conciliation_not_generated'] = "Conciliation not generated.";

//Omnilogic.php
$lang['api_id_code_not_informed'] = "Id code not informed.";
$lang['api_offer_successfully'] = "Offer successfully inserted.";

//GetCatalogs.php
$lang['api_catalog_no_found'] = "No registered catalog found.";
$lang['api_not_diplayed'] = "The requested data could not be displayed. Contact support.";

//Companies.php
$lang['api_company_id_numeric'] = "company_id must have a numeric value.";
$lang['api_company_created'] = "Company created.";
$lang['api_updated_data'] = "Updated data.";
$lang['api_company_not_found'] = "Company not found.";
$lang['api_company_not_informed'] = "Company not informed.";
$lang['api_missing_key_company'] = "Missing key 'company'.";
$lang['api_valid_date_field'] = "Enter a valid date for field ";
$lang['api_valid_uf'] = "Enter a valid UF for field ";
$lang['api_invalid_value_field'] = "Invalid value for field ";
$lang['api_valid_cnpj'] = "Enter a valid CNPJ for field ";
$lang['api_valid_ie'] = "Enter a valid IE for field ";
$lang['api_valid_cpf'] = "Enter a valid CPF for field ";
$lang['api_valid_value_field'] = "Enter a valid value for field ";
$lang['api_field_must_char'] = " must be ";

//Traking.php
$lang['api_order_not_informed'] = "Order ID not informed.";
$lang['api_tracking_successfully'] = "Tracking successfully inserted.";
$lang['api_tracking_not_informed'] = "Tracking code not informed.";
$lang['api_occurrence_successfully'] = "Occurrence successfully inserted.";
$lang['api_unauthorized_request'] = "Unauthorized request.";
$lang['api_order_not_found'] = "Order not found.";
$lang['api_order_already_tracking'] = "Order already has a tracking.";
$lang['api_order_cannot_tracking_40'] = "Order cannot accept tracking, must be in status = 40.";
$lang['api_not_tracking_key_occurrence'] = "Key 'occurrence' not found. {occurrence:{...}}";
$lang['api_not_tracking_key'] = "Not found tracking key.";
$lang['api_carrier_not_informed'] = "The carrier was not informed.";
$lang['api_url_not_informed'] = "The tracking url was not informed.";
$lang['api_date_tracking_misinformed'] = "date_tracking was not informed or misinformed.";
$lang['api_url_valid'] = "The tracking url must be a valid url.";
$lang['api_item_sku_not_informed'] = "Item sku code was not informed.";
$lang['api_item_qty_zero'] = "Item quantity must be greater than zero.";
$lang['api_item_tracking_not_informed'] = "Item tracking code was not informed.";
$lang['api_item_value_zero'] = "Item tracking value must be greater than zero.";
$lang['api_item_method'] = "Item method was not informed.";
$lang['api_item_service_id'] = "Item service_id code was not informed.";
$lang['api_delivery_date_correctly'] = "delivery date needs to be sent correctly (YYYY-mm-dd).";
$lang['api_order_5_55'] = "The order cannot accept occurrences, it must be in status 5, 45 or 55.";
$lang['api_name_occurrence'] = "Occurrence name not informed.";
$lang['api_date_occurrence_not_informed'] = "Date occurrence was not informed or misinformed.";
$lang['api_Tracking_not_found'] = "Tracking code not found for the order.";
$lang['api_existing_occurrence'] = "Existing occurrence.";

//Users.php
$lang['api_id_supplied'] = "Id must be supplied.";
$lang['api_user_id_numeric'] = "user_id must have a numeric value.";
$lang['api_user_created'] = "User created.";
$lang['api_user_not_found'] = "User not found.";
$lang['api_user_missing_key'] = "Missing key 'user'.";
$lang['api_user_cpf_required'] = "Field cpf is required.";
$lang['api_change_cpf_adm'] = "Change 'cpf' is only valid if the user is legal administrator.";
$lang['api_field_invalid'] = " invalid.";
$lang['api_updating_field'] = "Updating field ";
$lang['api_updating_field_end'] = " is not allowed.";
$lang['api_field'] = "Field ";
$lang['api_field_numeric'] = " must be numeric.";
$lang['api_field_required'] = " is required.";
$lang['api_field_must_least'] = " must be at least ";
$lang['api_field_characters'] = " characters.";
$lang['api_field_must_maximum'] = "must be a maximum of ";
$lang['api_existing'] = "Existing ";
$lang['api_choose_new'] = ", choose new ";
$lang['api_company_not_exist'] = "Informed company does not exist.";
$lang['api_store_not_exist'] = "Informed store does not exist.";
$lang['api_group_name_not_exist'] = "Informed group name does not exist.";
$lang['api_group_id_not_exist'] = "Informed group id does not exist.";

//Store.php
$lang['api_no_content_found'] = "No content was found for the parameters entered.";
$lang['api_store_id_numeric'] = "store_id must have a numeric value.";
$lang['api_store_created'] = "Store created.";
$lang['api_invalid_json_format'] = "Invalid json format.";
$lang['api_missing_key_store'] = "Missing key 'store'.";
$lang['api_provide_catalog'] = "Please provide at least one valid catalog.";
$lang['api_catalog_array'] = "Catalog must be an array.";
$lang['api_misinformed_logistics'] = "Misinformed Logistics Module, please inform true ou false!";
$lang['api_valid_associate_type'] = "Enter a valid associate type for field";
$lang['api_logistics_not_informed'] = "Logistics not informed!";
$lang['api_logistics'] = "Logistics ";
$lang['api_not_found'] = " not found.";
$lang['api_unmapped'] = " unmapped.";

//Variations.php
$lang['api_sku_not_match'] = "SKU variation and/or SKU do not match.";
$lang['api_code_sku_not_match'] = "Code and/or SKU do not match.";
$lang['api_sku_code_not_found'] = "SKU code not found.";
$lang['api_variation_updated'] = "Product variation updated successfully.";
$lang['api_variation_inserted'] = "product variation successfully inserted.";
$lang['api_product_not_variation'] = "Product does not exist variation.";
$lang['api_not_variation_key'] = "Not found variation key.";
$lang['api_product_variations_blank'] = "product_variations not found or blank.";
$lang['api_product_has_no_variation'] = "The product has no variation, you must enter the types_variations field.";
$lang['api_contain_field'] = "ª variation, does not contain the field ";
$lang['api_two_equal'] = "There should not be two equal sku code.";
$lang['api_variation_volt'] = "ª variation must be 110, 220 or bivolt.";
$lang['api_the_volt'] = "The voltage of ";
$lang['api_price_negative'] = "Price cannot be zero or negative in ";
$lang['api_variation'] = "ª  variation.";
$lang['api_ean_of'] = "EAN of ";
$lang['api_invalid_variation'] = "ª variation invalid.";
$lang['api_same_ean_variation'] = "The same EAN is not allowed for more than one variation in the ";
$lang['api_variation_ean'] = "ª  variation. EAN: ";
$lang['api_no_type_variation_found'] = "No type of variation found. Enter the types_variations field.";
$lang['api_variation_price'] = "Variation price cannot be zero or negative.";
$lang['api_invalid_ean_variation'] = "Invalid Variation EAN.";
$lang['api_same_ean_variation_ean'] = "The same EAN is not allowed for more than one variation. EAN: ";
$lang['api_qty_informed_variation'] = "Was not informed qty.";
$lang['api_sku_variation_correctly'] = "Informed sku_variation correctly.";
$lang['api_sku_variation_incorrectly'] = "Informed sku_variation incorrectly.";
$lang['api_sku_variation_missing_attribute'] = "ª variation there is a missing attribute: ";
$lang['api_type_not_declared_product'] = " type was not declared in the product variation. ";
$lang['api_the'] = "The ";

//Orders.php
$lang['api_invoice_inserted'] = "Invoice successfully inserted.";
$lang['api_order_updated'] = "Order successfully updated.";
$lang['api_order_code_not'] = "Order code not informed.";
$lang['api_order_removed_queue'] = "Order successfully removed from queue.";
$lang['api_order_change_status_new_order'] = "Order changed status to new order.";
$lang['api_no_records_found'] = "No records found.";
$lang['api_not_found_nfe_key'] = "Not found nfe key.";
$lang['api_mandatory_fields_not_informed'] = " There are mandatory fields not informed, field:";
$lang['api_import_error'] = "Import error. Try again.";
$lang['api_order'] = "Order ";
$lang['api_order_no_existent'] = " no existent.";
$lang['api_order_not_exist_company'] = " does not exist in this company.";
$lang['api_order_no_updated_invoices'] = " can no longer be updated with invoices.";
$lang['api_key_must_char'] = "The access key must be 44 characters long.";
$lang['api_order_date_invalid_format_valid'] = " in invalid format! Format valid: dd/mm/yyyy hh:ii:ss";
$lang['api_order_date_invalid_format'] = "Date in invalid format!";
$lang['api_invalid_format'] = " in invalid format.";
$lang['api_not_shipment_key'] = "Not found shipment key.";
$lang['api_no_accept_shipping_date'] = "Order can no longer accept the shipping date, date already exists.";
$lang['api_order_cannot_shipped'] = "Order cannot be shipped, must be in status = 43.";
$lang['api_shipped_date_correctly'] = "shipped_date needs to be sent correctly (YYYY-mm-dd).";
$lang['api_no_accept_delivery_date'] = "Order can no longer accept the delivery date, date already exists.";
$lang['api_order_cannot_delivered'] = "Order cannot be delivered, must be in status = 45.";
$lang['api_delivered_date_correctly'] = "delivered_date needs to be sent correctly (YYYY-mm-dd).";
$lang['api_user_group_not_found'] = "User group not found.";
$lang['api_user_not_allowed_cancel'] = "User is not allowed to cancel orders.";
$lang['api_not_order_key'] = "Not found order key.";
$lang['api_date_needs_correctly'] = "date needs to be sent correctly (YYYY-mm-dd HH:ii:ss).";
$lang['api_not_cancellation_reason'] = "Cancellation reason not found.";
$lang['api_cannot_canceled_order'] = "Order cannot be canceled, it must not be in status = (51,52,55,60,90,95,96,97,98,99).";
$lang['api_incorrect_start_date'] = "Incorrect start date. (yyyy-mm-dd)";
$lang['api_incorrect_final_date'] = "Incorrect final date. (yyyy-mm-dd)";
$lang['api_final_date_greater'] = "Final date cannot be greater than start date.";
$lang['api_shipped_date_incorrectly'] = "shipping_date must be greater than the order payment date.";
$lang['api_delivered_date_incorrectly'] = "delivered_date must be greater than the order shipping_date.";

$lang['api_app_store_already_integrated'] = "Store [%s] - %s already integrated with %s";
$lang['api_app_not_match_store_integration'] = "Informed application-id [%s], doesn't match with integration %s configured to store [%s] - %s";

$lang['api_log_title_create_product'] = "Product %s successfully inserted";
$lang['api_log_title_create_product_error'] = "Error inserting the product %s";
$lang['api_log_title_create_order'] = "Order %s successfully entered";
$lang['api_log_title_create_order_error'] = "Error entering order %s";

$lang['api_log_title_update_product'] = "Product %s successfully updated";
$lang['api_log_title_update_product_error'] = "Error updating product %s";

$lang['api_log_title_create_invoiced_order'] = "Order %s successfully invoiced";
$lang['api_log_title_create_invoiced_order_error'] = "Error invoicing order %s";
$lang['api_log_title_update_delivered_order'] = "Order %s updated to successfully delivered";
$lang['api_log_title_update_delivered_order_error'] = "Error updating order %s to delivered";
$lang['api_log_title_update_canceled_order'] = "Order %s successfully canceled";
$lang['api_log_title_update_canceled_order_error'] = "Error canceling order %s";
$lang['api_log_title_update_shipped_order'] = "Order %s updated to sent successfully";
$lang['api_log_title_update_shipped_order_error'] = "Error updating order %s to shipped";


$lang['api_collection_not_found'] = "Collection %s not found.";
$lang['api_collection_updated_successfully'] = "Product collection updated successfully.";
$lang['api_collection_must_be_numeric'] = "Collection %s must be numeric.";

$lang['application_order_1'] = "Waiting Payment";
$lang['application_order_2'] = "Processing Payment";
$lang['application_order_3'] = "Waiting Invoice";
$lang['application_order_4'] = "Ready to Ship";
$lang['application_order_5'] = "Shipped";
$lang['application_order_6'] = "Received";
$lang['application_order_9'] = "Processing invoice";
$lang['application_order_40'] = "Awaiting Tracking - External";
$lang['application_order_41'] = "Waiting Issue Label - External";
$lang['application_order_43'] = "Awaiting Shipment - External";
$lang['application_order_45'] = "Shipped - External";
$lang['application_order_50'] = "Waiting for Seller to Issue Label";
$lang['application_order_51'] = "Sending Freight->Marketplace";
$lang['application_order_52'] = "Sending Invoice->Marketplace";
$lang['application_order_53'] = "Checking Shipping Info";
$lang['application_order_54'] = "Waiting for Seller to Issue Label";
$lang['application_order_55'] = "Shipped";
$lang['application_order_56'] = "Processing Invoice";
$lang['application_order_57'] = "Error in invoice";
$lang['application_order_58'] = "Awaiting Withdrawal";
$lang['application_order_59'] = "Loss/Return";
$lang['application_order_60'] = "Received";
$lang['application_order_70'] = "Seller Change";
$lang['application_order_80'] = "Freight Hiring Problem";
$lang['application_order_90'] = "Cancellation Requested";
$lang['application_order_96'] = "Cancel Pre";    // Cancelado em definitivo antes de ser pago
$lang['application_order_97'] = "Cancel";
$lang['application_order_98'] = "To Cancel on Frete Rápido";
$lang['application_order_99'] = "To Cancel on Marketplace";
$lang['application_order_101'] = "Manual contracting";
$lang['api_view_sent_call'] = "View sent call";

$lang['api_legalpanel_amount_greater'] = "Greater Than Value cannot be greater than the Less Than Value";
$lang['api_legalpanel_list_error'] =    "There was an error builiding the List";
$lang['api_legalpanel_post_error_notification_type'] = "'notification_type' field is mandatory and must contain 'others' or 'order'";
$lang['api_legalpanel_post_error_store_id'] = "'store_id' field is mandatory if 'notification_type' is set to 'others'";
$lang['api_legalpanel_post_error_order_id'] = "'order_id' field is mandatory if 'notification_type' is set to 'order'";
$lang['api_legalpanel_post_error_status'] = "'status' field is mandatory and must contain 'open' or 'closed'";
$lang['api_legalpanel_post_error_amount'] = "'amount' field must contain a value greater or less than zero";
$lang['api_legalpanel_post_error_active'] = "'status' field must contain 1 or 0";
$lang['api_legalpanel_post_error_date'] = "Field 'datetime' was not informed correctly";
$lang['api_legalpanel_post_error_empty'] = "The Payload has not delivered valid data";
$lang['api_legalpanel_post_error_saving'] = "An Error has ocurred during the Register storing. Check the Data sent. The following operations are halted";
$lang['api_legalpanel_post_error_editing'] = "An Error has ocurred during the Register Updating. Check the Data sent. The following operations are halted";
$lang['api_legalpanel_put_error_id'] = "For Update purpose it is mandatory do inform the 'id' field on each item";
$lang['api_legalpanel_put_error_empty'] = "For Update purpose it is mandatory to inform ate least 1 field (other than 'id') to request to change";

$lang['api_legalpanel_post_success_saving'] = "Success storing the Registers in Legal Panel";
$lang['api_legalpanel_post_success_editing'] = "Success updating the Registers in Legal Panel";
$lang['api_filter_order_by_bad_informed'] = "Misinformed 'order_by' filter. Report in FIELD:ORDER format.";
$lang['api_filter_order_by_direction_bad_informed'] = "Misinformed 'order_by' filter. Enter the direction as ASC or DESC.";
$lang['api_filter_order_by_field_bad_informed'] = "Misinformed 'order_by' filter. Field not found, please report: %s";

$lang['api_invoices_from_different_stores'] = "Invoices from different stores, enter invoices from the same store.";

$lang['application_prices_error'] = "Zero or null prices are not accepted. Submit a valid value for the price of and price per";
$lang['application_prices_error_de'] = "Zero or null prices are not accepted. Submit a valid value for the price (list_price)";
$lang['application_prices_error_por'] = "Zero or null prices are not accepted. Submit a valid value for the price per (price)";
$lang['application_prices_error_bigger_then'] = "Price of cannot be less than prece per";
$lang['api_item_zero_value_not_accetable'] = "It is not allowed to create orders where one of the items has the value 0 (zero).";
$lang['api_item_discount_value_not_accetable'] = "The discount given on this product is greater than the allowed amount.";
$lang['api_product_already_published_only_updates_some_fields'] = "Product already published with the marketplace, only updated: %s";
$lang['api_product_variation_type_invalid'] = "Variation type informed is not valid.";
$lang['api_product_variation_is_already_in_use'] = "Reported variation already exists in another sku.";