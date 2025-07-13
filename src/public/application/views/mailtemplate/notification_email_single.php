<html lang="pt">

<head>
    <style>
        body {
            color:#404040;
        }

        th {
            color:#404040
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            text-align: left;
            padding: 8px;
            color:#404040
        }
        .dica-conecta-la{
            font-size: 1.17em;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body lang=PT-BR link='#0563C1' vlink='#954F72' style='tab-interval:35.4pt' style="font-family:'Helvetica',sans-serif;color:#404040;background:white">
    <div>
        <p style='text-align:center;'><img width=210 height=63 src='<?= $logo?>' ></p>
        <h3>Olá, <?= $userName ?>.</h3>
        <p>Você recebeu um novo pedido!:)</p>
        <p>Pedido: <a href="<?= base_url('orders/update/' . $order['id']) ?>"><?= $order['id'] ?></a></p>
        <p>Status do seu pedido: <?= $order['status'] ?></p>
        <p>A data limite para a expedição deste pedido é: <?= is_null($order['data_limite_cross_docking']) ? '' : date('d/m/Y', strtotime($order['data_limite_cross_docking'])) ?></p>
        <p>Resumo do pedido:</p>
        <h3>Itens do pedido</h3>
        <table style="width:100%">
            <tr>
                <th>SKU</th>
                <th>Nome</th>
                <th>Quantidade</th>
            </tr>
            <?php foreach ($order["itens"] as $key => $item) : ?>
                <tr>
                    <td>
                        <?= $item["sku"] ?>
                    </td>
                    <td>
                        <?= $item["name"] ?>
                    </td>
                    <td>
                        <?= intval($item["qty"]) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <p class="dica-conecta-la">Valor total do pedido: <?= $order['total_order'] ?></p>
        <br>
        <div class="dica-conecta-la">
            <p><b>Dica do time Conecta Lá</b></p>
            <p><b>-Esteja sempre atento(a) aos prazos de envio para que a sua loja mantenha uma otima avaliação.</b></p>
            <p><b>-Além dos e-mails, saiba cada detalhe das suas vendas acessando o nosso sistema, em <a href="<?= base_url() ?>"><?= base_url() ?></a>.</b></p>
        </div>
    </div>
</body>

</html>