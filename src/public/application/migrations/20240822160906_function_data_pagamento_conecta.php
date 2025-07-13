<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->db->query('DROP FUNCTION IF EXISTS `data_pagamento_conecta`');
        $query = "CREATE FUNCTION `data_pagamento_conecta`(id_pedido INT(10)) RETURNS varchar(255) CHARSET latin1
BEGIN 

DECLARE idTeste INT(10) DEFAULT 0;
DECLARE checkDate INT(10) DEFAULT 0;
DECLARE dataRetorno VARCHAR(255) DEFAULT '';
DECLARE dataEntrega DATE;
DECLARE AnoMesdataEntrega VARCHAR(15) DEFAULT '';
DECLARE AnodataEntrega VARCHAR(15) DEFAULT '';
DECLARE MesdataEntrega VARCHAR(15) DEFAULT '';
DECLARE mesTratado VARCHAR(15) DEFAULT '';
DECLARE mesTratadoPagamento1 VARCHAR(15) DEFAULT '';
DECLARE mesTratadoPagamento2 VARCHAR(15) DEFAULT '';
DECLARE dataConecta VARCHAR(255) DEFAULT '';
DECLARE paramStatusLibPag INT ( 1 ) DEFAULT 0;
DECLARE diasLibPag VARCHAR ( 10 ) DEFAULT '';
DECLARE mesCiclo varchar(1) default 0;

SELECT VALUE, STATUS INTO @diasLibPag, @paramStatusLibPag 
FROM ( SELECT VALUE, STATUS FROM settings WHERE NAME = 'days_to_release_payment_by_card' UNION SELECT 0, 2 ) a 
LIMIT 1;

SELECT STATUS INTO @mesCiclo
FROM ( SELECT VALUE, STATUS FROM settings WHERE NAME = 'cycle_next_month' UNION SELECT 0, 1 ) a 
LIMIT 1;

SELECT 
CASE WHEN DTU.data_usada = 'Data Envio' THEN STR_TO_DATE(LEFT(data_mkt_sent,10), '%Y-%m-%d') ELSE STR_TO_DATE(LEFT(data_mkt_delivered,10), '%Y-%m-%d') END AS data_usada, 
CASE WHEN DTU.data_usada = 'Data Envio' THEN DATE_FORMAT(STR_TO_DATE(LEFT(data_mkt_sent,10), '%Y-%m-%d'),'%Y%m') ELSE DATE_FORMAT(STR_TO_DATE(LEFT(data_mkt_delivered,10), '%Y-%m-%d'),'%Y%m') END AS DATA, 
CASE WHEN DTU.data_usada = 'Data Envio' THEN YEAR(STR_TO_DATE(LEFT(data_mkt_sent,10), '%Y-%m-%d')) ELSE YEAR(STR_TO_DATE(LEFT(data_mkt_delivered,10), '%Y-%m-%d')) END AS ano , 
CASE WHEN DTU.data_usada = 'Data Envio' THEN MONTH(STR_TO_DATE(LEFT(data_mkt_sent,10), '%Y-%m-%d')) ELSE MONTH(STR_TO_DATE(LEFT(data_mkt_delivered,10), '%Y-%m-%d')) END AS mes ,
DATE_FORMAT(OPD.data_pagamento_conectala, '%d/%m/%Y')
INTO @dataEntrega, @AnoMesdataEntrega, @AnodataEntrega, @MesdataEntrega , @dataRetorno
FROM orders O
INNER JOIN (SELECT DISTINCT MAX(PMC.data_usada) AS data_usada, apelido FROM `stores_mkts_linked` SML INNER JOIN `param_mkt_ciclo` PMC ON PMC.integ_id = SML.id_mkt GROUP BY apelido) DTU ON DTU.apelido = O.origin
LEFT JOIN orders_payment_date OPD ON OPD.order_id = O.id
WHERE O.id = id_pedido
LIMIT 1;

IF(@AnoMesdataEntrega IS NULL) THEN
SET @dataRetorno = NULL;
ELSE
IF(@dataRetorno IS NOT NULL) THEN
RETURN @dataRetorno;
ELSE
SET @dataRetorno = (
SELECT 
DATE_FORMAT(
CASE 
WHEN anoMesPagamento LIKE 'Next%' THEN
CASE WHEN DATE(CONCAT(LEFT(DATE_ADD(DATE(CONCAT(LEFT(@dataEntrega,7), '-01 00:00:00')), INTERVAL 1 MONTH),7), CONCAT(CONCAT('-',A.data_pagamento_conecta, ' 00:00:00')))) IS NULL THEN
CASE WHEN DATE(CONCAT(LEFT(DATE_ADD(DATE(CONCAT(LEFT(@dataEntrega,7), '-01 00:00:00')), INTERVAL 1 MONTH),7), CONCAT(CONCAT('-',A.data_pagamento_conecta - 1, ' 00:00:00')))) IS NULL THEN
CASE WHEN DATE(CONCAT(LEFT(DATE_ADD(DATE(CONCAT(LEFT(@dataEntrega,7), '-01 00:00:00')), INTERVAL 1 MONTH),7), CONCAT(CONCAT('-',A.data_pagamento_conecta - 2, ' 00:00:00')))) IS NULL THEN
DATE(CONCAT(LEFT(DATE_ADD(DATE(CONCAT(LEFT(@dataEntrega,7), '-01 00:00:00')), INTERVAL 1 MONTH),7), CONCAT(CONCAT('-',A.data_pagamento_conecta - 3, ' 00:00:00'))))
ELSE
DATE(CONCAT(LEFT(DATE_ADD(DATE(CONCAT(LEFT(@dataEntrega,7), '-01 00:00:00')), INTERVAL 1 MONTH),7), CONCAT(CONCAT('-',A.data_pagamento_conecta - 2, ' 00:00:00'))))
END
ELSE
DATE(CONCAT(LEFT(DATE_ADD(DATE(CONCAT(LEFT(@dataEntrega,7), '-01 00:00:00')), INTERVAL 1 MONTH),7), CONCAT(CONCAT('-',A.data_pagamento_conecta - 1, ' 00:00:00'))))
END
ELSE
DATE(CONCAT(LEFT(DATE_ADD(DATE(CONCAT(LEFT(@dataEntrega,7), '-01 00:00:00')), INTERVAL 1 MONTH),7), CONCAT(CONCAT('-',A.data_pagamento_conecta, ' 00:00:00'))))
END
ELSE
CASE WHEN DATE(CONCAT(LEFT(DATE_ADD(DATE(CONCAT(LEFT(@dataEntrega,7), '-01 00:00:00')), INTERVAL 0 MONTH),7), CONCAT(CONCAT('-',A.data_pagamento_conecta, ' 00:00:00')))) IS NULL THEN
CASE WHEN DATE(CONCAT(LEFT(DATE_ADD(DATE(CONCAT(LEFT(@dataEntrega,7), '-01 00:00:00')), INTERVAL 0 MONTH),7), CONCAT(CONCAT('-',A.data_pagamento_conecta - 1, ' 00:00:00')))) IS NULL THEN
CASE WHEN DATE(CONCAT(LEFT(DATE_ADD(DATE(CONCAT(LEFT(@dataEntrega,7), '-01 00:00:00')), INTERVAL 0 MONTH),7), CONCAT(CONCAT('-',A.data_pagamento_conecta - 2, ' 00:00:00')))) IS NULL THEN
DATE(CONCAT(LEFT(DATE_ADD(DATE(CONCAT(LEFT(@dataEntrega,7), '-01 00:00:00')), INTERVAL 0 MONTH),7), CONCAT(CONCAT('-',A.data_pagamento_conecta - 3, ' 00:00:00'))))
ELSE
DATE(CONCAT(LEFT(DATE_ADD(DATE(CONCAT(LEFT(@dataEntrega,7), '-01 00:00:00')), INTERVAL 0 MONTH),7), CONCAT(CONCAT('-',A.data_pagamento_conecta - 2, ' 00:00:00'))))
END
ELSE
DATE(CONCAT(LEFT(DATE_ADD(DATE(CONCAT(LEFT(@dataEntrega,7), '-01 00:00:00')), INTERVAL 0 MONTH),7), CONCAT(CONCAT('-',A.data_pagamento_conecta - 1, ' 00:00:00'))))
END
ELSE
DATE(CONCAT(LEFT(DATE_ADD(DATE(CONCAT(LEFT(@dataEntrega,7), '-01 00:00:00')), INTERVAL 0 MONTH),7), CONCAT(CONCAT('-',A.data_pagamento_conecta, ' 00:00:00'))))
END
END, '%d/%m/%Y') AS dataPagamento
FROM (
SELECT PMC.id,
SML.apelido,
PMC.data_inicio ,
PMC.data_fim,
PMC.data_pagamento,
CASE WHEN IFNULL(PMC.data_pagamento_conecta,0) < 1 OR IFNULL(PMC.data_pagamento_conecta,0) > 31 THEN PMC.data_pagamento ELSE PMC.data_pagamento_conecta END AS data_pagamento_conecta,
D.diasMesTotal,
CASE
WHEN PMC.data_inicio = PMC.data_fim THEN 'Next3'
WHEN PMC.data_fim < PMC.data_pagamento AND D.diasMesTotal > PMC.data_fim THEN 'Next2'
WHEN PMC.data_fim <= PMC.data_pagamento AND D.diasMesTotal <= PMC.data_fim THEN 'Current2'
WHEN PMC.data_fim > PMC.data_pagamento THEN 'Next1'
WHEN PMC.data_fim <= PMC.data_pagamento THEN 'Current1'
ELSE 
'Padrão'
END AS anoMesPagamento
FROM `param_mkt_ciclo` PMC
INNER JOIN stores_mkts_linked SML ON SML.id_mkt = PMC.integ_id
LEFT JOIN (SELECT '01' AS diasMesTotal UNION SELECT '02' UNION SELECT '03' UNION SELECT '04' UNION SELECT '05' UNION SELECT '06' UNION SELECT '07' UNION SELECT '08' UNION SELECT '09' UNION SELECT '10' UNION SELECT '11' UNION SELECT '12' UNION SELECT '13' UNION SELECT '14' UNION SELECT '15' UNION SELECT '16' UNION SELECT '17' UNION SELECT '18' UNION SELECT '19' UNION SELECT '20' UNION SELECT '21' UNION SELECT '22' UNION SELECT '23' UNION SELECT '24' UNION SELECT '25' UNION SELECT '26' UNION SELECT '27' UNION SELECT '28' UNION SELECT '29' UNION SELECT '30' UNION SELECT '31') D
ON 
CASE WHEN PMC.data_inicio < PMC.data_fim THEN 
PMC.data_inicio <= D.diasMesTotal AND PMC.data_fim >= D.diasMesTotal 
ELSE
PMC.data_inicio <= D.diasMesTotal  OR PMC.data_fim >= D.diasMesTotal
END
WHERE PMC.ativo = 1 AND apelido IN (SELECT DISTINCT origin FROM orders WHERE id = id_pedido) AND DAY(DATE(@dataEntrega)) = D.diasMesTotal LIMIT 1) A
);

END IF;

END IF;

IF(@dataRetorno IS NOT NULL) THEN
SELECT COUNT(*) AS qtd INTO @checkDate FROM orders_payment_date WHERE order_id = id_pedido;
IF(@checkDate = 0) THEN
INSERT INTO orders_payment_date (order_id, data_pagamento_conectala) VALUE (id_pedido, STR_TO_DATE(@dataRetorno, '%d/%m/%Y'));
ELSE
UPDATE orders_payment_date SET data_pagamento_conectala = STR_TO_DATE(@dataRetorno, '%d/%m/%Y') WHERE order_id = id_pedido;
END IF;
END IF;

RETURN @dataRetorno;
END
";
        $this->db->query($query);
	}

	public function down()	{
	}
};