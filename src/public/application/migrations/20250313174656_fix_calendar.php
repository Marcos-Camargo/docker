<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if (ENVIRONMENT == 'development') {
            $setting = $this->db->get_where('settings', array('name' => 'fix_calendar_oep_1812'))->row_array();
            if (empty($setting)) {
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='AtivaPromocoes', ce.module_method ='run' WHERE ce.title LIKE 'Ativa Promocoes';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='Intelipost/CancelOrder', ce.module_method ='run' WHERE ce.title LIKE 'Cancelar pedido de entrega na intelipost';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='CancelPlpCorreios', ce.module_method ='run' WHERE ce.title LIKE 'Cancelar PLP expirada';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='CountProductsByCategory', ce.module_method ='run' WHERE ce.title LIKE 'Conta Produtos por Categoria';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='FreteContratar', ce.module_method ='run' WHERE ce.title LIKE 'Contratar Frete';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='NotifyImportationAttributes', ce.module_method ='run' WHERE ce.title LIKE 'Enviar email com notificação de erro na importação de atributos por planilha';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='Automation/GenerationCSVToExports', ce.module_method ='run' WHERE ce.title LIKE 'Export de planilha csv';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='Automation/ImportCSVAutomation', ce.module_method ='run' WHERE ce.title LIKE 'Ler carga de produto importada pelo usuário';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='LerPlanilhaAtributosProdutos', ce.module_method ='run' WHERE ce.title LIKE 'Ler Planilha Atributos Produto';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='FreteRastrear', ce.module_method ='run' WHERE ce.title LIKE 'Rastrear frete';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='CSVFileProcessing/TableFreight', ce.module_method ='run' WHERE ce.title LIKE 'Importar tabela de frete';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='FreteCadastrar', ce.module_method ='run' WHERE ce.title LIKE 'Cadastrar lojas nas plataformas logísticas.';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='Automation/UpdateSetDeadlineNovo', ce.module_method ='run' WHERE ce.title LIKE 'Atualização de prazo operacional nos produtos';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='AgideskCriarContatos', ce.module_method ='run' WHERE ce.title LIKE 'Criar Usuário Agidesk';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='DeleteImagesProductsTrash', ce.module_method ='run' WHERE ce.title LIKE 'Remove as imagens dos produtos na lixeira depois de 2 semanas';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='CSVFileProcessing/ChangeProductCategory', ce.module_method ='run' WHERE ce.title LIKE 'Atualizar categoria de produtos em massa';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='CSVFileProcessing/ExportLabelTracking', ce.module_method ='run' WHERE ce.title LIKE 'Gera etiquetas de transportadoras';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='UpdateMSSettings', ce.module_method ='run' WHERE ce.title LIKE 'Atualizar tabela de parâmetros no MS a cada 4 horas.';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='Automation/ImportFilesViaB2B', ce.module_method ='run' WHERE ce.title LIKE 'Importação automática dos arquivos de cadastro de produtos, disponibilidade, estoque e preço da Via Varejo';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='SellerCenter/Vtex/BrandsDownload', ce.module_method ='run' WHERE ce.title LIKE 'Baixa Marcas da Vtex';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='SellerCenter/Vtex/VtexOrders', ce.module_method ='run' WHERE ce.title LIKE 'Baixar atualização de pedido';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='SellerCenter/Vtex/VtexOrdersStatus', ce.module_method ='run' WHERE ce.title LIKE 'Enviar atualização de pedido';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='SellerCenter/Vtex/SellerV2', ce.module_method ='run' WHERE ce.title LIKE 'Cadastra Loja na Vtex (Seller)';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='SellerCenter/Vtex/VtexOrders', ce.module_method ='updateSalesChannel' WHERE ce.title LIKE 'Atualizar pedidos sem canal de venda';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='CountProductsByCategory', ce.module_method ='run' WHERE ce.title LIKE 'Conta Produtos por Categoria das %';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='Getnet/GetnetBatch', ce.module_method ='getaccesstokens' WHERE ce.title LIKE 'Getnet - Chamada de atualização de Token';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='Getnet/GetnetBatch', ce.module_method ='runSyncStoresWithoutSubaccount' WHERE ce.title LIKE 'Getnet - Chamada de criação de subconta';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='Getnet/GetnetBatch', ce.module_method ='callbacksubaccount' WHERE ce.title LIKE 'Getnet - Chamada de criação callback da subconta';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='Getnet/GetnetBatch', ce.module_method ='runSyncStoresUpdated' WHERE ce.title LIKE 'Getnet - Chamada de edição da subconta';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='Getnet/GetnetBatch', ce.module_method ='geramdr' WHERE ce.title LIKE 'Getnet - Gera MDR Cartão';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='BatchC/Automation/ImportFilesViaB2B', ce.module_method ='run' WHERE ce.title LIKE 'Importação automática dos arquivos de cadastro de produtos, disponibilidade, estoque e preço da Via Varejo';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='SellerCenter/GrupoSoma/GrupoSoma_Seller', ce.module_method ='run' WHERE ce.title LIKE 'Cria Loja na Vtex Todos';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='SellerCenter/GrupoSoma/GrupoSomaProduct', ce.module_method ='run' WHERE ce.title LIKE 'Enviar Produtos para %';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='ProductsCatalogVerifyChanges', ce.module_method ='run' WHERE ce.title LIKE 'Verifica e atualiza produtos baseados em Catalogos';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='SendEmailAlertPriceCatalog', ce.module_method ='run' WHERE ce.title LIKE 'Enviar email alteracao de preco do catalogo';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='AgideskCriarContatos', ce.module_method ='run' WHERE ce.title LIKE 'Cria usuários no Agidesk';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='TinyInvoice', ce.module_method ='run' WHERE ce.title LIKE 'Faturar Pedidos - Tiny';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='Automation/GenerationCSVToExports', ce.module_method ='run' WHERE ce.title LIKE 'Export de planilha csv.';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='ConciliationInstallmentsBatch', ce.module_method ='run' WHERE ce.title LIKE 'Job de Calculo da Data de Pagamento/Cancelamento dos Pedidos';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='Publication/SendProductsWithTransformationError', ce.module_method ='run' WHERE ce.title LIKE 'Adicionar na fila todos os produtos com erros de transformações.';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='BatchC/MoipBatch_Vertem', ce.module_method ='runPayments' WHERE ce.title LIKE 'Efetuar o pagamento das conciliações pelo Pagar.me';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='SellerCenter/Vertem/VSOrdersStatus', ce.module_method ='run' WHERE ce.title LIKE 'Enviar atualização de pedido VertemStore';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='Marketplace/Conectala/OrdersStatus', ce.module_method ='run' WHERE ce.title LIKE 'Enviar atualização de pedido %';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='Marketplace/Conectala/GetOrders', ce.module_method ='run' WHERE ce.title LIKE 'Buscar pedidos %';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='Marketplace/Conectala/CreateIntegrations', ce.module_method ='run' WHERE ce.title LIKE 'Cria Integrações das Lojas Novas para a %';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='AgideskCriarContatos', ce.module_method ='run' WHERE ce.title LIKE 'Criar usuarios no Agidesk';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='PagarMe/PagarmeBatch', ce.module_method ='runSyncStoresWithSubaccounts' WHERE ce.title LIKE 'Sincronizar as subcontas com o Pagar.me - Lojas com contas para atualizar dados';");
                $this->db->query("UPDATE calendar_events ce SET ce.module_path ='PagarMe/PagarmeBatch', ce.module_method ='runAntecipations' WHERE ce.title LIKE 'Efetuar consulta de antecipações externas e importar no painel jurídico';");
            }

            $this->db->delete('settings', array('name' => 'fix_calendar_oep_1812'));
        }
    }

	public function down()	{}
};