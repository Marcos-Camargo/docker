<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->db->query('DROP FUNCTION IF EXISTS `data_cancelamento_marketplace`');
        $this->db->query("CREATE FUNCTION `data_cancelamento_marketplace`(id_pedido INT(10)) RETURNS varchar(255) BEGIN 

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

-- função para descobrir se o calculo é para o mês seguinte ou atual 1 é mês seguinte e 0 é mês atual
SELECT STATUS INTO @mesCiclo
FROM ( SELECT VALUE, STATUS FROM settings WHERE NAME = 'cycle_next_month' UNION SELECT 0, 1 ) a 
LIMIT 1;

-- busca as informações do pedido, qual data usar, mês e ano da data e se já existe alguma data para esse pedido cadastrada
SELECT 
CASE WHEN DTU.data_usada = \"Data Envio\" THEN STR_TO_DATE(LEFT(date_cancel,10), '%Y-%m-%d') ELSE STR_TO_DATE(LEFT(date_cancel,10), '%Y-%m-%d') END AS data_usada, 
CASE WHEN DTU.data_usada = \"Data Envio\" THEN DATE_FORMAT(STR_TO_DATE(LEFT(date_cancel,10), '%Y-%m-%d'),'%Y%m') ELSE DATE_FORMAT(STR_TO_DATE(LEFT(date_cancel,10), '%Y-%m-%d'),'%Y%m') END AS DATA, 
CASE WHEN DTU.data_usada = \"Data Envio\" THEN YEAR(STR_TO_DATE(LEFT(date_cancel,10), '%Y-%m-%d')) ELSE YEAR(STR_TO_DATE(LEFT(date_cancel,10), '%Y-%m-%d')) END AS ano , 
CASE WHEN DTU.data_usada = \"Data Envio\" THEN MONTH(STR_TO_DATE(LEFT(date_cancel,10), '%Y-%m-%d')) ELSE MONTH(STR_TO_DATE(LEFT(date_cancel,10), '%Y-%m-%d')) END AS mes ,
DATE_FORMAT(OPD.data_cancelamento_marketplace, '%d/%m/%Y')
INTO @dataEntrega, @AnoMesdataEntrega, @AnodataEntrega, @MesdataEntrega , @dataRetorno
FROM orders O
INNER JOIN (SELECT DISTINCT MAX(PMC.data_usada) AS data_usada, apelido FROM `stores_mkts_linked` SML INNER JOIN `param_mkt_ciclo` PMC ON PMC.integ_id = SML.id_mkt GROUP BY apelido) DTU ON DTU.apelido = O.origin
LEFT JOIN orders_payment_date OPD ON OPD.order_id = O.id
WHERE O.id = id_pedido
limit 1;

-- se não existir nenhuma data a ser usada a função retorna nulo
IF(@AnoMesdataEntrega IS NULL) then
SET @dataRetorno = NULL;
else
-- se já existir a data de pagamento calculada, a função retorna esta data e não faz mais nenhum cálculo
IF(@dataRetorno IS NOT NULL) then
RETURN @dataRetorno;
else
-- se não existir data cadastra ele verifica o parâmetro, se for 1 ele vai calcular a data para o mês seguinte
SET @dataRetorno = (
select 
-- Regra para verificar a data de pagamento no mês atual ou mês seguinte
-- se o parâmetro está ativo ele sempre jogará ao mês seguinte, se não considerará o mês atual
-- 									DATE_FORMAT(case when @mesCiclo = 1 then
DATE_FORMAT(case when A.data_fim > A.data_pagamento then
CASE WHEN DATE(concat(left( DATE_ADD(date( concat( left(@dataEntrega,7), '-01 00:00:00') ), interval 1 month) ,7), concat(concat('-',A.data_pagamento, ' 00:00:00')))) is null then
CASE WHEN DATE(concat(left( DATE_ADD(date( concat( left(@dataEntrega,7), '-01 00:00:00') ), interval 1 month) ,7), concat(concat('-',A.data_pagamento - 1, ' 00:00:00')))) is null then
CASE WHEN DATE(concat(left( DATE_ADD(date( concat( left(@dataEntrega,7), '-01 00:00:00') ), interval 1 month) ,7), concat(concat('-',A.data_pagamento - 2, ' 00:00:00')))) is null then
DATE(concat(left( DATE_ADD(date( concat( left(@dataEntrega,7), '-01 00:00:00') ), interval 1 month) ,7), concat(concat('-',A.data_pagamento - 3, ' 00:00:00'))))
else
DATE(concat(left( DATE_ADD(date( concat( left(@dataEntrega,7), '-01 00:00:00') ), interval 1 month) ,7), concat(concat('-',A.data_pagamento - 2, ' 00:00:00'))))
end
else
DATE(concat(left( DATE_ADD(date( concat( left(@dataEntrega,7), '-01 00:00:00') ), interval 1 month) ,7), concat(concat('-',A.data_pagamento - 1, ' 00:00:00'))))
end
else
DATE(concat(left( DATE_ADD(date( concat( left(@dataEntrega,7), '-01 00:00:00') ), interval 1 month) ,7), concat(concat('-',A.data_pagamento, ' 00:00:00'))))
end
else
CASE WHEN DATE(concat(left( DATE_ADD(date( concat( left(@dataEntrega,7), '-01 00:00:00') ), interval 0 month) ,7), concat(concat('-',A.data_pagamento, ' 00:00:00')))) is null then
CASE WHEN DATE(concat(left( DATE_ADD(date( concat( left(@dataEntrega,7), '-01 00:00:00') ), interval 0 month) ,7), concat(concat('-',A.data_pagamento - 1, ' 00:00:00')))) is null then
CASE WHEN DATE(concat(left( DATE_ADD(date( concat( left(@dataEntrega,7), '-01 00:00:00') ), interval 0 month) ,7), concat(concat('-',A.data_pagamento - 2, ' 00:00:00')))) is null then
DATE(concat(left( DATE_ADD(date( concat( left(@dataEntrega,7), '-01 00:00:00') ), interval 0 month) ,7), concat(concat('-',A.data_pagamento - 3, ' 00:00:00'))))
else
DATE(concat(left( DATE_ADD(date( concat( left(@dataEntrega,7), '-01 00:00:00') ), interval 0 month) ,7), concat(concat('-',A.data_pagamento - 2, ' 00:00:00'))))
end
else
DATE(concat(left( DATE_ADD(date( concat( left(@dataEntrega,7), '-01 00:00:00') ), interval 0 month) ,7), concat(concat('-',A.data_pagamento - 1, ' 00:00:00'))))
end
else
DATE(concat(left( DATE_ADD(date( concat( left(@dataEntrega,7), '-01 00:00:00') ), interval 0 month) ,7), concat(concat('-',A.data_pagamento, ' 00:00:00'))))
end
end, '%d/%m/%Y') as dataPagamento
from (
-- Neste bloco montamos uma tabela com 31 dias e encaixamos os ciclos dentro destes 31 dias
-- com isso filtramos o dia da data gatilho nessa tabela de dias e com isso, teremos a linha com a informação de data de pagamento
select 	PMC.id,
PMC.data_pagamento_conecta,
PMC.data_pagamento,
SML.apelido,
PMC.data_inicio ,
PMC.data_fim,
D.diasMesTotal
FROM `param_mkt_ciclo` PMC
INNER JOIN stores_mkts_linked SML ON SML.id_mkt = PMC.integ_id
left join (select '01' as diasMesTotal union select '02' union select '03' union select '04' union select '05' union select '06' union select '07' union select '08' union select '09' union select '10' union select '11' union select '12' union select '13' union select '14' union select '15' union select '16' union select '17' union select '18' union select '19' union select '20' union select '21' union select '22' union select '23' union select '24' union select '25' union select '26' union select '27' union select '28' union select '29' union select '30' union select '31') D
on 
case when PMC.data_inicio < PMC.data_fim then 
PMC.data_inicio <= D.diasMesTotal and PMC.data_fim >= D.diasMesTotal 
else
PMC.data_inicio <= D.diasMesTotal  or PMC.data_fim >= D.diasMesTotal
end
WHERE PMC.ativo = 1 AND apelido IN (SELECT DISTINCT origin FROM orders WHERE id = id_pedido) and day(date(@dataEntrega)) = D.diasMesTotal limit 1) A
);
-- se não existir data cadastra ele verifica o parâmetro, se for 0 ele vai calcular a data para o mês atual
END IF;

END IF;

IF(@dataRetorno IS NOT NULL) THEN
SELECT COUNT(*) AS qtd INTO @checkDate FROM orders_payment_date WHERE order_id = id_pedido;
IF(@checkDate = 0) THEN
INSERT INTO orders_payment_date (order_id, data_cancelamento_marketplace) VALUE (id_pedido, STR_TO_DATE(@dataRetorno, '%d/%m/%Y'));
ELSE
UPDATE orders_payment_date SET data_cancelamento_marketplace = STR_TO_DATE(@dataRetorno, '%d/%m/%Y') WHERE order_id = id_pedido;
END IF;
END IF;

RETURN @dataRetorno;
END");
	}

	public function down()	{
	}
};