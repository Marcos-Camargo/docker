<body lang=PT-BR link='#0563C1' vlink='#954F72' style='tab-interval:35.4pt'>
    <div>
        <h3>Segue abaixo as informações referente ao processamento dos atributos da planilha <?= $filename ?> na data <?= $created_date ?></h3>

        <?php if (count($info_by_line) > 0) { ?>
        <table style="width:100%">
            <tr>
                <th>Detalhamento dos Erros</th>
            </tr>
            <?php foreach ($info_by_line as $key => $info) : ?>
                <tr <?= $info['type'] == 'err' ? 'bgcolor="#ff6666"' : '' ?>>
                    <td>
                        <?= $info['info'] ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php } else { ?>
            <h4> Arquivo Importado com sucesso. </h4>
        <?php } ?>
    </div>
</body>