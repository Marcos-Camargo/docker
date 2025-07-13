<table border="1" style="width: 100%;">
    <thead>
        <tr>
            <th>Pedido</th>
            <th>Loja</th>
            <th>Data Pedido</th>
            <th><?php echo utf8_decode('Nº Parcelas');?></th>
            <th>Parcelas Antecipadas</th>
            <th>Total Repasse</th>
            <th>Total a Repassar</th>
            <th>Total Antecipado</th>
            <th><?php echo utf8_decode('Total Taxas Antecipação');?></th>
            <th><?php echo utf8_decode('Taxas Antecipação');?></th>
            <th>Taxa de MDR</th>
            <th><?php echo utf8_decode('Responsável da Antecipação');?></th>
            <th><?php echo utf8_decode('Data da Antecipação');?></th>
            <th>Status Fluxo</th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($orders as $order){
        ?>
            <tr>
                <td><?=$order['id']?></td>
                <td><?=utf8_decode($order['store'])?></td>
                <td><?=datetimeBrazil($order['date_time'])?></td>
                <td style="text-align: center;"><?=$order['total_installments']?></td>
                <td style="text-align: center;"><?=$order['installments_anticipated']?></td>
                <td style="text-align: center;"><?=$order['initial_transfer_formated']?></td>
                <td style="text-align: center;"><?=$order['value_not_paid_formated']?></td>
                <td style="text-align: center;"><?=$order['anticipated'] ? $order['value_paid_anticipated'] : '-'?></td>
                <td style="text-align: center;"><?=$order['anticipated'] ? $order['anticipation_taxes_formated'] : '-'?></td>
                <td style="text-align: center;"><?=$order['total_anticipation_fee'] && $order['anticipated'] ? money($order['total_anticipation_fee']) : '-'?></td>
                <td style="text-align: center;"><?=$order['total_fee'] && $order['anticipated'] ? money($order['total_fee']) : '-'?></td>
                <td style="text-align: center;"><?=$order['anticipated'] ? $order['user_name'] : '-'?></td>
                <td style="text-align: center;"><?=$order['anticipated'] ? $order['anticipation_date'] : '-'?></td>
                <td style="text-align: center;"><?=$order['status']?></td>
            </tr>
        <?php
        }
        ?>
    </tbody>

</table>
<?php
$filename = "anticipation_" . date("Y-m-d-H-i-s") . ".xls";
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');