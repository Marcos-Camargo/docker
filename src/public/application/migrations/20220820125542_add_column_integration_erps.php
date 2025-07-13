<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_DB_driver $db
 */

return new class extends CI_Migration
{

	public function up() {

        /*$fieldNew = array(
            'description' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => FALSE,
                'after' => 'name'
            )
        );
        $fieldUpdate = array(
            'name' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => TRUE,

            ),
            'hash' => array(
                'type' => 'VARCHAR',
                'constraint' => ('512'),
                'null' => TRUE,

            )
        );
        $this->dbforge->add_column('integration_erps', $fieldNew);
        $this->dbforge->modify_column('integration_erps', $fieldUpdate);
        $this->db->query("UPDATE integration_erps SET `description` = `name`, `name` = null");
        $this->db->query("INSERT INTO integration_erps (`description`,`name`,`type`,`hash`,`active`,`support_link`,`image`) VALUES
        ('Bling','bling',1,NULL,1,'[{\"title\":\"Passo 1: Como obter minhas credenciais\",\"link\":\"https://conectala.agidesk.com/br/central-de-ajuda/como-obter-as-chaves-de-integracao\"},{\"title\":\"Passo 2: Como integrar a URL de callback?\",\"link\":\"https://conectala.agidesk.com/br/central-de-ajuda/como-integrar-a-url-de-callback\"},{\"title\":\"Passo 3: Como Integrar meus produtos\",\"link\":\"https://conectala.agidesk.com/br/central-de-ajuda/integracao-de-produtos\"}]','bling.png'),
        ('Tiny','tiny',1,NULL,1,'[{\"title\":\"Passo 1: Como obter minhas credenciais?\",\"link\":\"https://conectala.agidesk.com/br/central-de-ajuda/como-obter-o-token-de-integracao\"},{\"title\":\"Passo 2: Como integrar meus produtos?\",\"link\":\"https://conectala.agidesk.com/br/central-de-ajuda/integracao-das-urls-de-notificacao-e-produto\"}]','tiny.png'),
        ('VTEX','vtex',1,NULL,1,'[{\"title\":\"Passo 1: Como obter minhas credenciais?\",\"link\":\"https://conectala.agidesk.com/br/central-de-ajuda/como-obter-os-dados-para-integracao\"}]','vtex.png'),
        ('Eccosys','eccosys',1,NULL,1,'[{\"title\":\"Passo 1: Como obter minhas credenciais?\",\"link\":\"https://eccosys.tomticket.com/kb/api/obtendo-credenciais-para-api\"}]','eccosys.png'),
        ('JN2','jn2',1,NULL,1,'[{\"title\":\"Passo 1: Como obter minhas credenciais?\",\"link\":\"https://beta1.boostcommerce.com.br/swagger#/integrationAdminTokenServiceV1\"}]','jn2.png'),
        ('PluggTo','pluggto',1,NULL,1,'[{\"title\":\" Passo 1: Como obter minhas credenciais?\",\"link\":\"https://conectala.agidesk.com/br/central-de-ajuda/como-integrar-meus-produtos\"},{\"title\":\"Passo 2: Como integrar meus produtos?\",\"link\":\"https://conectala.agidesk.com/br/central-de-ajuda/como-integrar-meus-produtos\"},{\"title\":\"Passo 3: Como utilizar log\u00edstica pr\u00f3pria?\",\"link\":\"https://conectala.agidesk.com/br/painel/central-de-ajuda/como-configurar-sua-logistica-na-plugg-to\"}]','pluggto.png'),
        ('BSeller','bseller',1,NULL,1,'[{\"title\":\"Passo 1: Como obter minhas credenciais?\",\"link\":\"https://conectala.agidesk.com/br/central-de-ajuda/como-obter-minhas-credenciais\"},{\"title\":\"Passo 2: Como integrar meus produtos?\",\"link\":\"https://conectala.agidesk.com/br/central-de-ajuda/como-integrar-produtos\"}]','bseller.png'),
        ('AnyMarket','anymarket',1,NULL,1,NULL,'anymarket.png'),
        ('Loja Integrada','lojaintegrada',1,NULL,1,NULL,'lojaintegrada.png'),
        ('Via','via',1,NULL,1,NULL,'via.png');");*/
	}

	public function down()	{
		### Drop table integration_erps ##
		//$this->dbforge->drop_column("integration_erps", 'description');

	}
};