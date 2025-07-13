<!--<body lang=PT-BR link='#0563C1' vlink='#954F72' style='tab-interval:35.4pt'>
	<div>
	    <p style='text-align:center;'><img width=210 height=63 src='</?=$logo?>' ></p>
	    <p style='text-align:center;'>Olá, </?=$user['firstname']?> </?=$user['lastname']?>, bem-vindo ao Coopera SellerCenter!</p>
	    <p style='text-align:center;'><span style='font-family:'Helvetica',sans-serif;color:#404040;background:white'></span></p>
	    <p style='text-align:center;'><span style='font-family:'Helvetica',sans-serif;color:#404040;background:white'>Nossa missão é tornar sua experiência de vendas nos nossos canais simples, fácil e de muito resultado. Conte conosco!</span></p>
	    <p style='text-align:center;'></p>
	    <p style='text-align:center;'><span style='font-family:'Helvetica',sans-serif;color:#404040;background:white'>Aqui estão seu login, senha e link de acesso ao portal:</span></p>
	    <p style='text-align:center;'><span style='font-family:'Helvetica',sans-serif;color:#404040;background:white'>Link: <a href='</?=base_url()?>'></?=$url?></a></span></p>
	    <p style='text-align:center;'><span style='font-family:'Helvetica',sans-serif;color:#404040;background:white'>Login: </?=$user['email']?></span></p>
	    <p style='text-align:center;'><span style='font-family:'Helvetica',sans-serif;color:#404040;background:white'>Senha: </?=$pass['temp_pass']?></span></p>
	    <p style='text-align:center;'><span style='font-family:'Helvetica',sans-serif;color:#404040;background:white'></span></p>
	    <p style='text-align:center;'><span style='font-family:'Helvetica',sans-serif;color:#404040;background:white'>Qualquer dúvida estamos a disposição!</span><br>
	    <span style='background:white'>Boas vendas,<br>
	    <span style='background:white'>Coopera SellerCenter</span></p>
	</div>
</body>-->

<body style="margin:0;">
	<table border="0" cellspacing="0" cellpadding="0" align="center"
		style="width:100%; max-width:600px; background-color: ##e1e5e6; border: 0;">
		<tbody>
			<!-- Barra topo mensagem -->
			<tr>
				<td align="center" style="width: 100%; height: 538px; background-color: #006469; " colspan="2">

					<img src="<?php echo base_url('assets/skins/sicoob/banner_1.png'); ?>" style=" display: block;"
						alt="BEM-VINDO ao universo de bons negócios do Coopera">
				</td>
			</tr>
			<!-- Barra topo mensagem -->
			<!-- espaço -->
			<tr>
				<td align="center" style="width: 100%; height: 30px; background-color: #006469; " colspan="2">
				</td>
			</tr>
			<!-- espaço  -->
			<!-- campo link -->
			<tr>
				<td align="left" style="width: 20%; height: 50px; background-color: #006469;">
					<p
						style="font-family: montserrat, arial, helvetica; font-size:20px; color:#96dc00; margin-top: 0px; margin-bottom: 0px; padding-left: 50px;">
						<strong>Link<strong>
					</p>
				</td>
				<!-- divisão cell -->
				<td align="left" style="width: 80%; height: 50px; background-color: #006469; ">

					<div
						style="border: 1px solid #96dc00; height: 36px; width: 430px; border-radius: 5px;  margin-top: 0px; margin-bottom: 0px; display: block;">
						<p
							style="font-family: montserrat, arial, helvetica; font-size:18px; color:#96dc00; padding-left: 20px; margin-top: 6px; margin-bottom: 0px;">
							<strong><a href="<?=base_url()?>" class="bg-rollover"
									target="_blank" style=" text-decoration:none; color: #96dc00"><?=$url?></a><strong>

						</p>
					</div>
				</td>
			</tr>
			<!-- campo link -->
			<!-- campo login -->
			<tr>
				<td align="left" style="width: 20%; height: 50px; background-color: #006469;">
					<p
						style="font-family: montserrat, arial, helvetica; font-size:20px; color:#96dc00; margin-top: 0px; margin-bottom: 0px; padding-left: 50px;">
						<strong>login<strong>
					</p>
				</td>
				<!-- divisão cell -->
				<td align="left" style="width: 80%; height: 50px; background-color: #006469; ">
					<div
						style="border: 1px solid #96dc00; height: 36px; width: 430px; border-radius: 5px;  margin-top: 0px; margin-bottom: 0px; display: block;">
						<p
							style="font-family: montserrat, arial, helvetica; font-size:18px; color:#96dc00; padding-left: 20px; margin-top: 6px; margin-bottom: 0px;">
							<strong><?=$user['email']?><strong>
						</p>
					</div>
				</td>
			</tr>
			<!-- campo login -->
			<!-- campo senha -->
			<tr>
				<td align="left" style="width: 20%; height: 50px; background-color: #006469;">
					<p
						style="font-family: montserrat, arial, helvetica; font-size:20px; color:#96dc00; margin-top: 0px; margin-bottom: 0px; padding-left: 50px;">
						<strong>senha<strong>
					</p>
				</td>
				<!-- divisão cell -->
				<td align="left" style="width: 80%; height: 50px; background-color: #006469; ">
					<div
						style="border: 1px solid #96dc00; height: 36px; width: 430px; border-radius: 5px;  margin-top: 0px; margin-bottom: 0px; display: block;">
						<p
							style="font-family: montserrat, arial, helvetica; font-size:18px; color:#96dc00; padding-left: 20px; margin-top: 6px; margin-bottom: 0px;">
							<strong><?=$pass['temp_pass']?><strong>
						</p>
					</div>
				</td>
			</tr>
			<!-- campo senha -->
			<!-- espaço -->
			<tr>
				<td align="center" style="width: 100%; height: 30px; background-color: #006469; " colspan="2">
				</td>
			</tr>
			<!-- espaço  -->
			<!-- texto -->
			<tr>
				<td align="left" style="width: 100%; height: 190px; background-color: #96dc00; " colspan="2">
					<p
						style="font-family: montserrat, arial, helvetica; font-size:26px; color:#006469; margin-top: 0px; margin-bottom: 0px; padding-left:50px;">
						Estamos felizes por sua empresa<br>
						fazer parte do Coopera.<br>
						<strong>Sabemos o quanto<br></strong>
						<strong>a nossa parceria é forte.<br></strong>
					</p>
				</td>
			</tr>
			<!-- texto  -->
			<!-- vantagens -->
			<tr>
				<td align="center" style="width: 100%; height: 810px; background-color: #e1e5e6; " colspan="2">
					<p
						style="font-family: montserrat, arial, helvetica; font-size:20px; color:#006469; margin-top: 0px; margin-bottom: 40px;">
						<strong>Veja quantas vantagens esperam por você:</strong>
					</p>
					<!-- vantagens tabela -->
					<table border="0" cellspacing="0" cellpadding="0" align="center" style="width: 522px;">
						<tbody>
							<!-- primeira linha -->
							<tr align="center" style=" width:100%;">
								<td style="height: 150px; width:260px; ">
									<p
										style="font-family: montserrat, arial, helvetica;font-size:14px; color:#0f666c; padding-right: 10px;">
										<img style="margin-bottom: 10px; " src="<?php echo base_url('assets/skins/sicoob/v1.png'); ?>"
											alt="icone">
										</br>
										Divulgação da sua loja em<br>diversos canais e mídia.
									</p>
								</td>
								<!-- divisão -->
								<td style="height: 150px; width:2px; background-color:#96dc00;">
								</td>
								<!-- divisão -->
								<td style="height: 150px; width:260px; ">
									<p
										style="font-family: montserrat, arial, helvetica;font-size:14px; color:#0f666c; padding-left: 10px;">
										<img style="margin-bottom: 10px;" src="<?php echo base_url('assets/skins/sicoob/v2.png'); ?>"
											alt="icone">
										</br>
										Fortalecimento<br>da sua marca.
									</p>
								</td>
							</tr>
							<!-- primeira linha -->
							<!-- segunda linha -->
							<tr align="center" style=" width:100%;">
								<td style="height: 150px; width:260px; ">
									<p
										style="font-family: montserrat, arial, helvetica;font-size:14px; color:#0f666c; padding-right: 10px;">
										<img style="margin-bottom: 10px; " src="<?php echo base_url('assets/skins/sicoob/v3.png'); ?>"
											alt="icone">
										</br>
										Sem investimento inicial.
									</p>
								</td>
								<!-- divisão -->
								<td style="height: 150px; width:2px; background-color:#96dc00;">
								</td>
								<!-- divisão -->
								<td style="height: 150px; width:260px; ">
									<p
										style="font-family: montserrat, arial, helvetica;font-size:14px; color:#0f666c; padding-left: 10px;">
										<img style="margin-bottom: 10px;" src="<?php echo base_url('assets/skins/sicoob/v4.png'); ?>"
											alt="icone">
										</br>
										Relação transparente.
									</p>
								</td>
							</tr>
							<!-- segunda linha -->
							<!-- terceira linha -->
							<tr align="center" style=" width:100%;">
								<td style="height: 150px; width:260px; ">
									<p
										style="font-family: montserrat, arial, helvetica;font-size:14px; color:#0f666c; padding-right: 10px;">
										<img style="margin-bottom: 10px; " src="<?php echo base_url('assets/skins/sicoob/v5.png'); ?>"
											alt="icone">
										</br>
										Aumento das vendas<br>pela internet.
									</p>
								</td>
								<!-- divisão -->
								<td style="height: 150px; width:2px; background-color:#96dc00;">
								</td>
								<!-- divisão -->
								<td style="height: 150px; width:260px; ">
									<p
										style="font-family: montserrat, arial, helvetica;font-size:14px; color:#0f666c; padding-left: 10px;">
										<img style="margin-bottom: 10px;" src="<?php echo base_url('assets/skins/sicoob/v6.png'); ?>"
											alt="icone">
										</br>
										Alcance de diversos<br>perfis de consumidores.
									</p>
								</td>
							</tr>
							<!-- terceira linha -->
							<!-- quarta linha -->
							<tr align="center" style=" width:100%;">
								<td style="height: 150px; width:260px; ">
									<p
										style="font-family: montserrat, arial, helvetica;font-size:14px; color:#0f666c; padding-right: 10px;">

										<img style="margin-bottom: 10px; " src="<?php echo base_url('assets/skins/sicoob/v7.png'); ?>"
											alt="icone">
										</br>
										Sem risco de<br>fraude/chargeback.
									</p>
								</td>
								<!-- divisão -->
								<td style="height: 150px; width:2px; background-color:#96dc00;">
								</td>
								<!-- divisão -->
								<td style="height: 150px; width:260px; ">
									<p
										style="font-family: montserrat, arial, helvetica;font-size:14px; color:#0f666c; padding-left: 10px;">

										<img style="margin-bottom: 10px;" src="<?php echo base_url('assets/skins/sicoob/v8.png'); ?>"
											alt="icone">
										</br>
										Forte base de clientes<br>com pontos a resgatar.
									</p>
								</td>
							</tr>
							<!-- quarta linha -->
						</tbody>
					</table>
					<!-- vantagens tabela -->
					<br>
					<br>
					<a href="<?=$url?>" class="bg-rollover" target="_blank"
						style="font-family: montserrat, arial, helvetica; font-size:18px; color:#006469; margin-top: 100px; padding: 5px 20px 5px 20px; border: 2px solid #006469; border-radius: 8px; text-decoration:none; text-transform: uppercase;"><strong>Veja
							como acessar</strong>
					</a>
				</td>
			</tr>
			<!-- vantagens  -->
			<!-- texto rodapé -->
			<tr>
				<td align="center" style="width: 100%; height: 40px; background-color: #96dc00; " colspan="2">
					<p
						style="font-family: montserrat, arial, helvetica; font-size:16px; color:#006469; margin-top: 0px; margin-bottom: 0px;">
						<strong>Conte sempre com o Coopera para vender e crescer mais.</strong>
					</p>
				</td>
			</tr>
			<!-- texto rodapé  -->
			<!-- rodapé -->
			<tr>
				<td align="center" style="width: 100%; height: 20px; background-color: #006469; " colspan="2">
				</td>
			</tr>
			<tr>
				<td align="left" style="width: 100%; height: 100px; background-color: #006469; " colspan="2">
					<p
						style="font-family: montserrat, arial, helvetica; font-size:14px; color:#ffffff; margin-top: 0px; margin-bottom: 0px; margin-left: 50px;">
						Em caso de dúvidas,<br>
						acesse <strong><a href="https://www.shopcoopera.com.br/institucional/duvidas-frequentes" class="bg-rollover"
								target="_blank"
								style=" text-decoration:none; color: #ffffff;">parceiros.shopcoopera.com.br</a></strong><br>
						ou procure sua cooperativa.
					</p>
				</td>
			</tr>
			<!-- rodapé -->
			<tr>
				<td align="right" style="width: 100%; height: 100px; background-color: #006469; " colspan="2">
					<img style="margin-right: 50px; " src="<?php echo base_url('assets/skins/sicoob/logo_coopera.png') ?>" alt="icone">
				</td>
			</tr>
			<tr>
				<td align="center" style="width: 100%; height: 20px; background-color: #006469; " colspan="2">
				</td>
			</tr>
			<!-- rodapé  -->
		</tbody>
	</table>
</body>