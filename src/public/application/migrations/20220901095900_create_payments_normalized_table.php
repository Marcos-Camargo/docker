<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {


        ## Create Table shipping_pricing_history
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE,
                'auto_increment' => TRUE
            ),
            'original_payment' => array(
                'type' => 'VARCHAR',
                'constraint' => ('256'),
                'null' => FALSE
            ),
            'normalized_payment' => array(
                'type' => 'VARCHAR',
                'constraint' => ('256'),
                'null' => FALSE
            ),
            'complement' => array(
                'type' => 'VARCHAR',
                'constraint' => ('256'),
                'null' => TRUE
            ),
            '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("payments_normalized", TRUE);

        $this->db->insert_batch('payments_normalized', array(
            array(
                "original_payment" => "account_money",
                "normalized_payment" => "Conta Digital",
                "complement" => "Banco"
            ),
            array(
                "original_payment" => "AmeDigital",
                "normalized_payment" => "Conta Digital",
                "complement" => "Ame"
            ),
            array(
                "original_payment" => "American Express",
                "normalized_payment" => "Cartão de Crédito",
                "complement" => "American Express"
            ),
            array(
                "original_payment" => "bank_transfer",
                "normalized_payment" => "Transferência Bancária",
                "complement" => "Banco"
            ),
            array(
                "original_payment" => "Boleto Bancário",
                "normalized_payment" => "Boleto",
                "complement" => "Banco"
            ),
            array(
                "original_payment" => "Boletocreditorder",
                "normalized_payment" => "Boleto",
                "complement" => "Banco"
            ),
            array(
                "original_payment" => "Boletocreditordercashback",
                "normalized_payment" => "Boleto",
                "complement" => "Banco"
            ),
            array(
                "original_payment" => "Cartão de Crédito - POScreditorder",
                "normalized_payment" => "Cartão de Crédito",
                "complement" => "Não Informado"
            ),
            array(
                "original_payment" => "Cartão de Crédito - POScreditordercashback",
                "normalized_payment" => "Cartão de Crédito",
                "complement" => "Não Informado"
            ),
            array(
                "original_payment" => "Cartão de Débito - POScreditorder",
                "normalized_payment" => "Cartão de Crédito",
                "complement" => "Não Informado"
            ),
            array(
                "original_payment" => "CasaEVideo",
                "normalized_payment" => "Pagamento do Marketplace",
                "complement" => "Não Informado"
            ),
            array(
                "original_payment" => "Conta a receber/pagar",
                "normalized_payment" => "Transferência Bancária",
                "complement" => "Não Informado"
            ),
            array(
                "original_payment" => "creditorder",
                "normalized_payment" => "Cartão de Crédito",
                "complement" => "Não Informado"
            ),
            array(
                "original_payment" => "credit_card",
                "normalized_payment" => "Cartão de Crédito",
                "complement" => "Não Informado"
            ),
            array(
                "original_payment" => "debit_card",
                "normalized_payment" => "Cartão de Débito",
                "complement" => "Não Informado"
            ),
            array(
                "original_payment" => "digital_currency",
                "normalized_payment" => "Conta Digital",
                "complement" => "Não Informado"
            ),
            array(
                "original_payment" => "Dinheiro",
                "normalized_payment" => "Dinheiro",
                "complement" => "Dinheiro"
            ),
            array(
                "original_payment" => "Elo",
                "normalized_payment" => "Cartão de Crédito",
                "complement" => "Elo"
            ),
            array(
                "original_payment" => "Elocreditorder",
                "normalized_payment" => "Cartão de Crédito",
                "complement" => "Elo"
            ),
            array(
                "original_payment" => "EloVisacreditorder",
                "normalized_payment" => "Cartão de Crédito",
                "complement" => "Elo"
            ),
            array(
                "original_payment" => "Financiamentocreditorder",
                "normalized_payment" => "Cartão de Crédito",
                "complement" => "Não Informado"
            ),
            array(
                "original_payment" => "Financiamentocreditordercashback",
                "normalized_payment" => "Cashback",
                "complement" => "Não Informado"
            ),
            array(
                "original_payment" => "Hipercard",
                "normalized_payment" => "Cartão de Crédito",
                "complement" => "Hipercard"
            ),
            array(
                "original_payment" => "Mastercard",
                "normalized_payment" => "Cartão de Crédito",
                "complement" => "Mastercard"
            ),
            array(
                "original_payment" => "Mastercreditorder",
                "normalized_payment" => "Cartão de Crédito",
                "complement" => "Mastercard"
            ),
            array(
                "original_payment" => "Mastercreditordercashback",
                "normalized_payment" => "Cartão de Crédito",
                "complement" => "Mastercard"
            ),
            array(
                "original_payment" => "MasterMastercreditorder",
                "normalized_payment" => "Cartão de Crédito",
                "complement" => "Mastercard"
            ),
            array(
                "original_payment" => "MasterMastercreditordercashback",
                "normalized_payment" => "Cashback",
                "complement" => "Mastercard"
            ),
            array(
                "original_payment" => "PicPay",
                "normalized_payment" => "Conta Digital",
                "complement" => "Não Informado"
            ),
            array(
                "original_payment" => "Pix",
                "normalized_payment" => "Transferência Bancária",
                "complement" => "Pix"
            ),
            array(
                "original_payment" => "Pixcreditorder",
                "normalized_payment" => "Transferência Bancária",
                "complement" => "Pix"
            ),
            array(
                "original_payment" => "Pixcreditordercashback",
                "normalized_payment" => "Transferência Bancária",
                "complement" => "Pix"
            ),
            array(
                "original_payment" => "ticket",
                "normalized_payment" => "Ticket",
                "complement" => "Não Informado"
            ),
            array(
                "original_payment" => "Vale",
                "normalized_payment" => "Ticket",
                "complement" => "Não Informado"
            ),
            array(
                "original_payment" => "Visa",
                "normalized_payment" => "Cartão de Crédito",
                "complement" => "Visa"
            ),
            array(
                "original_payment" => "Visacreditorder",
                "normalized_payment" => "Cartão de Crédito",
                "complement" => "Visa"
            ),
            array(
                "original_payment" => "Visacreditordercashback",
                "normalized_payment" => "Cartão de Crédito",
                "complement" => "Visa"
            ),
            array(
                "original_payment" => "VisaMastercreditorder",
                "normalized_payment" => "Cartão de Crédito",
                "complement" => "Visa"
            )
        ));
	 }

	public function down()	{
        $this->dbforge->drop_table("payments_normalized", TRUE);
	}
};