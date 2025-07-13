<body lang=PT-BR link='#0563C1' vlink='#954F72' style='tab-interval:35.4pt'>
    <div>
        <h3>Segue abaixo as informações referente ao processamento dos <?= $type ?> importados via csv pelo usuario <?= $username ?> na data <?= $created_date ?></h3>

        <table style="width:100%">
            <tr>
                <th>Linha</th>
                <th>Mensagem</th>
            </tr>
            <?php foreach ($info_by_line as $key => $info) : ?>
                <tr <?= $info['type'] == 'err' ? 'bgcolor="#ff6666"' : '' ?>>
                    <td>
                        <?= $info['line'] ?>
                    </td>
                    <td>
                        <?= $info['info'] ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>