<!DOCTYPE html>
<html lang="pt">

<head>
    <style>
        div.container {
            width: 100%;
            position: relative;
        }

        div.logo {
            padding-top: 10px;
            width: 100%;
            display: inline-block;
            text-align: left;
        }
        div.false {
            padding-top: 10px;
            width: 19%;
            display: inline-block;
            text-align: center;
        }
        div.titulo {
            width: 60%;
            display: inline-block;
            text-align: center;
        }

        div.aditional-info {
            width: 19%;
            display: inline-block;
            text-align: center;
        }

        th {
            color:#404040
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }
        .dica-conecta-la{
            font-size: 1.17em;
        }
        th,
        td {
            padding: 8px;
            color:#404040
        }
        td{
            text-align: left;
        }
        th{
            text-align: center;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .aditional-info>p{
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div>
        <div class="logo"><img width=210 height=63 src="<?= $logo?>" alt="ConectaLá"></div>
        <div class="container">
            <div class="false"></div>
            <div class="titulo">
                <h3>Relatório de pedidos por Seller</h3>
            </div>
            <div class="aditional-info">
                <p>Data: <?= date('d/M/Y'); ?></p>
                <p>Total de pedidos: <?= count($orders) ?></p>
            </div>
        </div>
        <br>
        <br>
        <table style="width:100%">
            <tr>
                <th>Pedido</th>
                <th>Nome do cliente</th>
                <th>Incluído</th>
                <th>Expedir</th>
                <th>Entrega</th>
                <th>Valor</th>
                <th>Status</th>
            </tr>
            <?php foreach ($orders as $key => $order) : ?>
                <tr>
                    <td>
                        <a href="<?= base_url('orders/update/' . $order['id']) ?>"><?= $order['id'] ?></a>
                    </td>
                    <td>
                        <?= $order["customer_name"] ?>
                    </td>
                    <td>
                        <?= date('d/m/Y', strtotime($order["date_time"])) ?>
                    </td>
                    <td>
                        <?= is_null($order['data_limite_cross_docking']) ? '' : date('d/m/Y', strtotime($order['data_limite_cross_docking'])) ?>
                    </td>
                    <td>
                        <?= ($order['ship_company'] == '' ? ($order['ship_company_preview'] == 'CORREIOS' ? 'CORREIOS' : 'TRANSPORTADORA') : ($order['ship_company'] == 'CORREIOS' ? 'CORREIOS' : 'TRANSPORTADORA')) ?>
                    </td>
                    <td>
                        R$: <?= $order["gross_amount"] ?>
                    </td>
                    <td>
                        <?= $order['status'] ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <br>
        <br>
        <div class="dica-conecta-la">
            <p><b>Dica do time Conecta Lá</b></p>
            <p><b>-Esteja sempre atento(a) aos prazos de envio para que a sua loja mantenha uma otima avaliação.</b></p>
            <p><b>-Além dos e-mails, saiba cada detalhe das suas vendas acessando o nosso sistema, em <a href="<?= base_url() ?>"><?= base_url() ?></a>.</b></p>
        </div>
    </div>
</body>

</html>