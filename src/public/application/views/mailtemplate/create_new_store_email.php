<body lang=PT-BR link='#0563C1' vlink='#954F72' style='tab-interval:35.4pt'>
	<div>
		<p style='text-align:center;'><img width=210 height=63 src='<?=$logo?>' ></p><br>
        <p style='text-align:center;'>Olá time de operações!</p>
	    <p style='text-align:center;'><span style='font-family:'Helvetica',sans-serif;color:#404040;background:white'></span></p>
	    <p style='text-align:center;'><span style='font-family:'Helvetica',sans-serif;color:#404040;background:white'>A empresa <?=$company_name?> solicitou a criação de uma nova loja através do Shopify!</span></p>
		<p style='text-align:center;'></p>
	    <p style='text-align:center;'><span style='font-family:'Helvetica',sans-serif;color:#404040;background:white'>Seguem os dados enviados pela Shopify para a criação da nova loja:</span></p>
	    <p style='text-align:center;'><span style='font-family:'Helvetica',sans-serif;color:#404040;background:white'>CNPJ: <?=$company_CNPJ?></span></p>
        <p style='text-align:center;'><span style='font-family:'Helvetica',sans-serif;color:#404040;background:white'>Telefone 1: <?=$tel1?></span></p>
        <p style='text-align:center;'><span style='font-family:'Helvetica',sans-serif;color:#404040;background:white'>Telefone 2: <?=$tel2?></span></p>
	    <p style='text-align:center;'><span style='font-family:'Helvetica',sans-serif;color:#404040;background:white'>Email: <?=$responsible_email?></span></p>
	    <p style='text-align:center;'><span style='font-family:'Helvetica',sans-serif;color:#404040;background:white'>Responsável: <?=$responsible_name?></span></p>
	    <p style='text-align:center;'><span style='font-family:'Helvetica',sans-serif;color:#404040;background:white'>Clique <a href='<?=base_url($url)?>'>aqui</a> para ser direcionado a pagina de criação de lojas:</span></p>
		<p style='text-align:center;'></p>
		<p style='text-align:center;'><span style='font-family:'Helvetica',sans-serif;color:#404040;background:white'>Para verificar as lojas que já foram criadas, clique <a href='<?=base_url('shopify/shopify_requests')?>'>aqui</a>.</span></p>
	</div>
	<div style='width: 100%; height: 100px; background-color: #b9cec5'>
        <table style="width: 100%;padding-top: 20px;">
            <tr>
                <td style="width: 60%;padding-left:10px">
                    <a href="https://www.conectala.com.br" target="_blank">
                        <img src="<?=$logo?>" width="150px">
                    </a>
                </td>
                <td style="width: 10%;text-align:center">
                    <a href="https://www.instagram.com/conecta_la/" target="_blank">
                        <img src="<?=base_url('assets/images/system/instagram.png')?>" width="50px">
                    </a>
                </td>
                <td style="width: 10%;text-align:center">
                    <a href="https://www.linkedin.com/company/11693409" target="_blank">
                        <img src="<?=base_url('assets/images/system/linkedin.png')?>" width="50px">
                    </a>
                </td>
                <td style="width: 10%;text-align:center">
                    <a href="https://www.facebook.com/conectalaa" target="_blank">
                        <img src="<?=base_url('assets/images/system/facebook.png')?>" width="50px">
                    </a>
                </td>
                <td style="width: 10%;text-align:center;padding-right:10px">
                    <a href="https://www.youtube.com/channel/UC4CFf6T4s971Hcw0KHWTs4w" target="_blank">
                        <img src="<?=base_url('assets/images/system/youtube.png')?>" width="50px">
                    </a>
                </td>
            </tr>
        </table>
    </div>
</body>