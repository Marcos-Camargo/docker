<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'auth/login';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
$route['Apicar/freight'] 	= 'Api/freteCarrefour';      		// Consulta frete do Carrefour
$route['Apivia/freight'] 	= 'Api/freteVIA';            		// Consulta frete Via Varejo
$route['Apib2w/freight'] 	= 'Api/freteB2W';            		// Consulta frete B2W
$route['Apib2wv2/freight']  = 'Api/freteB2WV2';                 // Consulta frete B2WV2
$route['Apib2wv3/freight']  = 'Api/freteB2WV3';                 // Consulta frete B2WV2
$route['Apimad/freight'] 	= 'Api/freteMAD';            		// Consulta frete Madeira Madeira 
$route['Apivs/freight'] 	= 'Api/freteVS';           			// Consulta frete Vertem Store
$route['Apigpa/freight'] 	= 'Api/freteGPA';           		// Consulta frete GPA
$route['Apisccl/freight'] 	= 'Api/freteConectalaMarketplace';	// Consulta frete vindo do Sellercenter Conectala
$route['Api/SellerCenter/Vtex/(:any)/pvt/orderForms/simulation'] = 'Api/SellerCenter/Vtex/Simulation/$1';
$route['Api/SellerCenter/Vtex/(:any)/pvt/orders'] = 'Api/SellerCenter/Vtex/Orders/$1';
$route['Api/SellerCenter/Vtex/(:any)/pvt/orders/(:any)/fulfill'] = 'Api/SellerCenter/Vtex/Orders/$1/$2';
$route['Api/SellerCenter/Vtex/(:any)/pvt/orders/(:any)/cancel'] = 'Api/SellerCenter/Vtex/Orders/cancel/$1/$2';
$route['Api/SellerCenter/Vtex/(:any)/pvt/orders/seller/cancel'] = 'Api/SellerCenter/Vtex/Orders/$1/cancel';
$route['Api/SellerCenter/Vtex/(:any)/api/pvt/orders/(:any)/cancel'] = 'Api/SellerCenter/Vtex/Orders/$1/$2/cancel';

// Redirecionar Webhook Antigo para o Novo Módulo
//Anymarket
// v2
$route['Api/Integration/AnyMarket/Remotes/sendProduct'] = 'Api/Integration_v2/anymarket/v2/Remotes/sendProduct';
// v1
$route['Api/Integration/AnyMarket/(:any)'] = 'Api/Integration_v2/anymarket/$1';
$route['Api/Integration/AnyMarket/(:any)/(:any)'] = 'Api/Integration_v2/anymarket/$1/$2';
$route['Api/Integration/AnyMarket/(:any)/(:any)/(:any)'] = 'Api/Integration_v2/anymarket/$1/$2/$3';
$route['Api/Integration/AnyMarket/(:any)/(:any)/(:any)/(:any)'] = 'Api/Integration_v2/anymarket/$1/$2/$3/$4';
$route['Api/Integration/AnyMarket/(:any)/(:any)/(:any)/(:any)/(:any)'] = 'Api/Integration_v2/anymarket/$1/$2/$3/$4/$5';
$route['Api/Integration/AnyMarket/(:any)/(:any)/(:any)/(:any)/(:any)/(:any)'] = 'Api/Integration_v2/anymarket/$1/$2/$3/$4/$5/$6';
//PluggTo
$route['Api/Integration/PluggTo/ControlProduct'] = 'Api/Integration_v2/pluggto/Notification';
$route['Api/Integration/PluggTo/ControlProduct/(:any)'] = 'Api/Integration_v2/pluggto/Notification/$1';
$route['Api/Integration/PluggTo/ControlProduct/(:any)/(:any)'] = 'Api/Integration_v2/pluggto/Notification/$1/$2';
$route['Api/Integration/PluggTo/ControlProduct/(:any)/(:any)/(:any)'] = 'Api/Integration_v2/pluggto/Notification/$1/$2/$3';
$route['Api/Integration/PluggTo/ControlProduct/(:any)/(:any)/(:any)/(:any)'] = 'Api/Integration_v2/pluggto/Notification/$1/$2/$3/$4';
$route['Api/Integration/PluggTo/ControlProduct/(:any)/(:any)/(:any)/(:any)/(:any)'] = 'Api/Integration_v2/pluggto/Notification/$1/$2/$3/$4/$5';
$route['Api/Integration/PluggTo/ControlProduct/(:any)/(:any)/(:any)/(:any)/(:any)/(:any)'] = 'Api/Integration_v2/pluggto/Notification/$1/$2/$3/$4/$5/$6';
//Bling
$route['Api/Integration/Bling/(:any)'] = 'Api/Integration_v2/bling/$1';
$route['Api/Integration/Bling/(:any)/(:any)'] = 'Api/Integration_v2/bling/$1/$2';
$route['Api/Integration/Bling/(:any)/(:any)/(:any)'] = 'Api/Integration_v2/bling/$1/$2/$3';
$route['Api/Integration/Bling/(:any)/(:any)/(:any)/(:any)'] = 'Api/Integration_v2/bling/$1/$2/$3/$4';
$route['Api/Integration/Bling/(:any)/(:any)/(:any)/(:any)/(:any)'] = 'Api/Integration_v2/bling/$1/$2/$3/$4/$5';
$route['Api/Integration/Bling/(:any)/(:any)/(:any)/(:any)/(:any)/(:any)'] = 'Api/Integration_v2/bling/$1/$2/$3/$4/$5/$6';
//Tiny
$route['Api/Integration/Tiny/UpdateStock'] = 'Api/Integration_v2/tiny/UpdatePriceStock';
$route['Api/Integration/Tiny/UpdatePrice'] = 'Api/Integration_v2/tiny/UpdatePriceStock';
$route['Api/Integration/Tiny/(:any)'] = 'Api/Integration_v2/tiny/$1';
$route['Api/Integration/Tiny/(:any)/(:any)'] = 'Api/Integration_v2/tiny/$1/$2';
$route['Api/Integration/Tiny/(:any)/(:any)/(:any)'] = 'Api/Integration_v2/tiny/$1/$2/$3';
$route['Api/Integration/Tiny/(:any)/(:any)/(:any)/(:any)'] = 'Api/Integration_v2/tiny/$1/$2/$3/$4';
$route['Api/Integration/Tiny/(:any)/(:any)/(:any)/(:any)/(:any)'] = 'Api/Integration_v2/tiny/$1/$2/$3/$4/$5';
$route['Api/Integration/Tiny/(:any)/(:any)/(:any)/(:any)/(:any)/(:any)'] = 'Api/Integration_v2/tiny/$1/$2/$3/$4/$5/$6';

$route['Apicar/freight/auction'] = 'Api/leilaoCarrefour/auction'; // consulta leilao de frete B2W
$route['Apivia/freight/auction'] = 'Api/leilaoVIA/auction'; // consulta leilao de frete B2W
$route['Apiml/freight']  = 'Api/freteML';            // Consulta frete Mercado livre
$route['Apib2w/freight/auction'] = 'Api/leilaoB2W/auction'; // consulta leilao de frete B2W
$route['Apimag/freight'] = 'Api/freteMagalu';        // Consulta frete Magazine Luiza
$route['Apivte/(:any)/freight/auction'] = 'Api/leilaoVtex/auction/$1'; // consulta leilao de frete B2W

$route['products/trash'] = 'Trash';
$route['products/trash/fetchProductData'] = 'Trash/fetchProductData';
$route['products/trash/view/(:num)'] = 'Trash/view/$1';
$route['products/trash/copy/(:num)'] = 'Trash/copy/$1';
$route['products/trash/deletePermanently/(:num)'] = 'Trash/deletePermanently/$1';

$route['Api/Intelipost/StatusReceipt'] = 'Api/Logistic/Intelipost/StatusReceipt'; // migrar requisição intelipost para nova rota.

// Routes Antecipação de Repasse
$route['Api/V1/financeiro/antecipacao/stores'] = 'Api/V1/financeiro/antecipacao/Stores/index_get';
$route['Api/V1/financeiro/antecipacao/orders'] = 'Api/V1/financeiro/antecipacao/Orders/index_get';
$route['Api/V1/financeiro/antecipacao/orders/anticipate'] = 'Api/V1/financeiro/antecipacao/Orders/index_post';

// Routes API extrato
$route['Api/V1/financeiro/extrato/extrato'] = 'Api/V1/financeiro/extrato/Extrato/index_get';

$route['integrations/configuration/attributes'] = 'IntegrationAttributeMap';
$route['integrations/configuration/attributes/fetchAttributeData'] = 'IntegrationAttributeMap/fetchAttributeData';
$route['integrations/configuration/attributes/fetchVariationData'] = 'IntegrationAttributeMap/fetchVariationData';
$route['integrations/configuration/attributes/addUpdateAttrMap'] = 'IntegrationAttributeMap/addUpdateAttrMap';
$route['integrations/configuration/attributes/addUpdateAttrMap/(:any)'] = 'IntegrationAttributeMap/addUpdateAttrMap/$1';
$route['integrations/configuration/attributes/deleteAttrMap/(:any)'] = 'IntegrationAttributeMap/deleteAttrMap/$1';
$route['integrations/configuration/attributes/edit/(:any)'] = 'IntegrationAttributeMap/edit/$1';
$route['integrations/configuration/attributes/edit_attribute/(:any)/(:any)'] = 'IntegrationAttributeMap/edit_attribute/$1/$2';
$route['integrations/configuration/attributes/fetchAttributeMappedValues/(:any)'] = 'IntegrationAttributeMap/fetchAttributeMappedValues/$1';
$route['integrations/configuration/attributes/(:any)'] = 'IntegrationAttributeMap';
$route['integrations/configuration/attributes/(:any)/(:any)'] = 'IntegrationAttributeMap';

// Routes API cycles
$route['Api/V1/financeiro/cycles/cycles_marketplace'] = 'Api/V1/financeiro/cycles/Cycles';
$route['Api/V1/financeiro/cycles/cycles_models'] = 'Api/V1/financeiro/cycles/Models';

//Routes to Legal Panel API
$route['Api/V1/financeiro/legalpanel'] = 'Api/V1/financeiro/legalpanel/LegalPanel';

//$route['Api/V1/orders/(:any)'] = "Api/V1/Orders/index/$1";

$route['orderToDelivered'] = 'OrderToDelivered';
$route['orderToDelivered/update'] = 'orderToDelivered/update';
$route['orderToDelivered/getLojasByMarketplace'] = 'OrderToDelivered/getLojasByMarketplace';