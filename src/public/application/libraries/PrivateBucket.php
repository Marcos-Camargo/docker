<?php

require APPPATH . "libraries/Bucket.php";

/**
 * Biblioteca para tratamento de arquivos armazenados em Bucket privado.
 * Implementa todos outros métodos da classe base, adicionando especificidades para buckets privados.
 * Deve ser carregado ao invés da biblioteca bucket sempre que for necessário interagir com bucket privado.
 * Também deve ser utilizado para criação de arquivos no bucket privado.
 */
class PrivateBucket extends Bucket
{
	protected $clientS3;

	// Instancia o CI para ter acesso a config.
	public function __construct()
	{
		parent::__construct();

		// Carrega o nome do bucket como o bucket privado.
		$this->bucket_name = $this->config->item("Private_Bucket_Name");
	}

	/**
	 * Cria uma URL pre-signed para determinado objeto armazenado em bucket privado
	 * @param	 string 		$object_key	A chave do objeto da qual a URL será gerada.
	 * @param	 bool 			$expiration Tempo de expiração, passado como string para ser convertido. Por padrão, '+7 days'.
	 * O valor máximo aceito é 7 dias, ao passar, sempre utilizar strings.
	 * 
	 * @return	 array{success:bool,url:mixed} Retorna o status e a URL. Em caso de erro, retorna a mensagem.
	 */
	public function generatePreSignedRequest($object_key, $expiration = '+7 days')
	{
		// Cria a URL pre-signed.
		try {
			// Cria o comando a ser executado na criação.
			$cmd = $this->clientS3->getCommand(
				'GetObject',
				[
					'Bucket' => $this->bucket_name,
					'Key'	 => $object_key
				]
			);

			// Cria a request.
			$request = $this->clientS3->createPresignedRequest($cmd, $expiration);

			return [
				'success'	=> true,
				'url'		=> (string)$request->getUri()
			];
		} catch (Exception $e) {
			// Um erro ocorreu, apenas retorna a mensagem de erro da AWS e sem sucesso.
			return [
				'success'	=> false,
				'url'		=> $e->getMessage()
			];
		}
	}
}
