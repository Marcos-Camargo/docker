<?php

require_once 'system/libraries/Vendor/autoload.php';
require_once 'system/libraries/Vendor/aws/aws-autoloader.php';

use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\S3\Transfer;
use GuzzleHttp\Psr7\Stream;

/**
 * Biblioteca para tratamento de arquivos armazenados no Bucket.
 * Apresenta métodos variados de tratamento e manipulação dos objetos.
 * Grande parte dos métodos apresenta flag para adicionar ou não o prefixo do seller center ao prefixo/url dos objetos.
 * Utilizar como falso caso a URL passada já tenha vindo de algum método que retorne a URL com o prefixo.
 * 
 * As chaves são definidas através da concatenação do nome do sellercenter ao local do arquivo armazenado na estrutura anterior em disco.
 * Ex: decathlon/assets/images...
 * Todos sellercenters irão compartilhar o mesmo bucket, portanto é necessário essa separação via prefixo.
 * Chamadas entre métodos desta classe não devem adicionar o prefixo, deve ser sempre adicionado no ponto de entrada.
 * 
 * Padronizar definição de classe, utilizar tipagem para as funções via PHPDocs, manter métodos em ordem alfabética.
 * Utilizar parâmetros como snake_case e funções como camel_case.
 * Evitar métodos para especificidades. Ex: Método que funcione apenas para estruturas de imagens, abstração deve ser feita nas chamadas.
 *  
 * @property	 CI_Config 		$config
 * @property	 S3Client 		$clientS3
 * @property	 Model_settings $model_settings
 * @property	 CI_Loader 		$load
 * @property	 string			$bucket_name
 * @property	 array 			$sellercenter
 */
class Bucket
{
	protected $clientS3;
	protected $bucket_name;

	// Instancia o CI para ter acesso a config.
	public function __construct()
	{
		// Carrega os dados do seller center para definir a estrutura de pastas.
		$this->load->model("model_settings");
		$this->sellercenter = $this->model_settings->getSettingDatabyName('sellercenter');
		$this->bucket_name = $this->config->item('Bucket_Name');
		$this->setClientBucket();
	}

	/**
	 * Método mágico para utilização do CI_Controller
	 * @param	 string			$var Propriedade para consulta
	 * @return	 mixed			Objeto da propriedade
	 */
	public function __get(string $var)
	{
		return get_instance()->$var;
	}

	/**
	 * Copia um arquivo no bucket para uma nova chave. 
	 * @param	 string			$original_url URL original do objeto.
	 * @param	 string			$destination_url URL de destino do objeto.
	 * @param	 bool			$add_sellercenter Flag para definir se adiciona ou não o nome do sellercenter ao url passado.
	 * 
	 * @return	 array{success:bool,message:mixed} Retorna um array contendo chave para indicar sucesso e a mensagem de erro ou suceso.
	 */
	function copy($original_url, $destination_url, $add_sellercenter = true)
	{
		// Apenas adiciona o seller center se passado de forma explicita.
		if ($add_sellercenter) {
			$original_url = $this->bucket_name . '/' . $this->sellercenter['value'] . '/' . $original_url;
			$destination_url = $this->sellercenter['value'] . '/' . $destination_url;
		}

		try {
			// Realiza a cópia do objeto. 
			$this->clientS3->copyObject([
				'Bucket' 		=>  $this->bucket_name,
				'Key' 			=> 	$destination_url,
				'CopySource'	=> 	$original_url,
			]);
		} catch (Exception $e) {
			return [
				"success" => false,
				"message" => $e->getMessage()
			];
		}

		// Tudo deu certo.
		return [
			"success" => true,
			"message" => "Objeto copiado com sucesso."
		];
	}

	/**
	 * Copia multiplos arquivos em determinado prefixo para um novo prefixo. 
	 * @param	 string			$original_prefix Prefixo original do objeto.
	 * @param	 string			$destination_prefix Prefixo de destino do objeto.
	 * @param	 bool			$add_sellercenter Flag para definir se adiciona ou não o nome do sellercenter ao prefixo passado.
	 * 
	 * @return	 array{success:bool,contents:array{},message:mixed} Retorna um array contendo chave para indicar sucesso e os novos urls.
	 */
	function copyMany($original_prefix, $destination_prefix,  $add_sellercenter = true)
	{

		// Apenas adiciona o seller center se passado de forma explicita.
		if ($add_sellercenter) {
			$original_prefix = $this->sellercenter['value'] . '/' . $original_prefix;
			$destination_prefix = $this->sellercenter['value'] . '/' . $destination_prefix;
		}

		// Busca os objetos a partir do prefixo.
		$objects = $this->listObjects($original_prefix, false);

		// Caso tenha dado sucesso, copia.
		if ($objects['success']) {

			$copyErrors = [];
			$copyUrls = [];

			// Percorre cada objeto.
			foreach ($objects['contents'] as $key => $value) {

				// Monta o url da chave no bucket.
				$bucket_url_file = $this->bucket_name . '/' . $value['Key'];

				// Monta o URL de destino.
				$url_dest = str_replace($original_prefix, $destination_prefix, $value['Key']);

				try {
					// Realiza a cópia do objeto. 
					$this->clientS3->copyObject([
						'Bucket' 		=>  $this->bucket_name,
						'Key' 			=> 	$url_dest,
						'CopySource'	=> 	$bucket_url_file,
					]);

					$copyUrls[] = $url_dest;
				} catch (Exception $e) {
					// Adiciona o erro ao array.
					$copyErrors[] = $e->getMessage();
				}
			}

			// Retorna falso caso qualquer erro tenha ocorrido.
			if (count($copyErrors) > 0) {
				return [
					"success" => false,
					"contents" => $copyUrls,
					"message" => "Um erro ocorreu, apenas " . count($copyUrls) . " arquivos foram copiados."
				];
			}

			return [
				"success" => true,
				"contents" => $copyUrls,
				"message" => "Todos os arquivos foram copiados."
			];
		} else {
			return [
				"success" => false,
				"contents" => [],
				"message" => "Não conseguiu pegar a lista de objetos do prefixo:" . $objects['message']
			];
		}
	}

	/**
	 * Cria uma instância de transferencia de pastas.
	 * Sempre irá inserir o prefixo do seller center, vistoq ue está saindo do disco, não há a estrutura do prefixo do seller center.
	 * @param	 string 		$root Diretório raiz no servidor.
	 * @param	 string 		$dest Diretório destino no bucket. (Apenas a estrutura de pastas)
	 * @param	 bool 			$add_sellercenter Flag para definir se adiciona ou não o nome do sellercenter ao prefixo passado.
	 * @return	 Transfer 		Retorna uma instância de transfer.
	 */
	function createTransfer($root, $dest, $add_sellercenter = true)
	{
		// Apenas adiciona o seller center se passado de forma explicita.
		if ($add_sellercenter) {
			$dest = $this->sellercenter['value'] . "/" . $dest;
		}

		// Cria a URL do bucket para realizar o envio.
		$bucket_name = $this->bucket_name;
		$bucket_url = "s3://$bucket_name/$dest";
		return new Transfer($this->clientS3, $root, $bucket_url, ['concurrency' => 15, 'debug' => true]);
	}

	/**
	 * Deleta um objeto do bucket.
	 * @param	 string 		$url_prefix URL do pseudo diretório para ser deletado.
	 * @param	 bool 			$add_sellercenter Flag para definir se adiciona ou não o nome do sellercenter ao prefixo passado.
	 * 
	 * @return	 array{success:bool,message:mixed}
	 */
	function deleteDirectory($url_prefix, $add_sellercenter = true)
	{
		// Pega todos objetos que devem ser deletados.
		$list = $this->listObjects($url_prefix, $add_sellercenter);

		// Caso não consiga buscar a lista retorna o erro da mesma.
		if (!$list['success']) {
			return [
				'success' 		=> false,
				'message'		=> $list['message']
			];
		}

		// Inicializa um array para inserir os objetos que não foram deletados.
		$not_deleted = [];
		foreach ($list['contents'] as $data) {

			// Deleta cada item da lista.
			try {
				$result = $this->clientS3->deleteObject([
					'Bucket'	=> $this->bucket_name,
					'Key'		=> $data['Key']
				]);
			} catch (S3Exception $e) {
				// Retorna o erro do Object Storage e a lista de objetos não deletados.
				$not_deleted[] = $data['Key'];
				return [
					'success' 	=> false,
					'contents'	=> $e->getMessage(),
					'errors' 	=> $not_deleted
				];
			}

			if (!$result['DeleteMarker']) {
				$not_deleted[] = $data['Key'];
			}
		}

		// Retorna os itens não deletados.
		if (count($not_deleted) > 0) {
			return [
				'success'		=> false,
				'message'		=> 'Não foi possível deletar todos objetos.',
				'errors'		=> $not_deleted
			];
		}

		// Tudo deu certo.
		return [
			'success'			=> true,
			'message'			=> 'Todos objetos foram deletados com sucesso.',
		];
	}

	/**
	 * Deleta um objeto do bucket.
	 * @param	 string			$url_object URL do objeto que deve ser deletado.
	 * @param	 bool 			$add_sellercenter Flag para definir se adiciona ou não o nome do sellercenter ao prefixo passado.
	 * 
	 * @return	 array{success:bool,message:mixed}
	 */
	function deleteObject($url_object, $add_sellercenter = true)
	{
		// Apenas adiciona o seller center se passado de forma explicita.
		if ($add_sellercenter) {
			$url_object = $this->sellercenter['value'] . "/" . $url_object;
		}

		try {
			// Realiza a exclusão do objeto.
			$this->clientS3->deleteObject([
				'Bucket'	=> $this->bucket_name,
				'Key'		=> $url_object
			]);

			// O SDK da AWS não traz corretamente se um objeto foi ou não deletado com sucesso.
			// A principio, apenas retornamos que sim.
			// Podemos implementar o objectExists aqui para confirmar, embora irá adicionar uma chamada extra.
			return [
				'success'	=> true,
				'message'	=> "Objeto deletado com sucesso."
			];
		} catch (S3Exception $e) {
			// Ocorreu um erro, retorna falso.
			return [
				'success'	=> false,
				'message'	=> $e->getMessage()
			];
		}
	}

	/**
	 * Retorna a chave completa de um asset.
	 * @param	 string		 	$url_object URL do asset para ser completada. 
	 * @param	 bool			$add_sellercenter Flag opcional (Padrão: Falso) para definir se adiciona ou não o nome do sellercenter ao url.
	 * @param	 bool			$add_bucket_name Flag opcional (Padrão: Verdadeiro) para definir se adiciona ou não o nome do bucket a URL.
	 * Útil para situações em que o nome será adicionado por padrão, como pre-signed, mas necessitamos do sellercenter e do temp.
	 * @param	 bool			$temp Se o arquivo deve ser temporário ou não. 
	 * 
	 * @return	 string URL completa do asset.
	 */
	function getAssetKey($url_object, $add_sellercenter = true, $add_bucket = true, $temp = false)
	{
		// Apenas adiciona o seller center se passado de forma explicita.
		if ($add_sellercenter) {
			$url_object = $this->sellercenter['value'] . '/' . $url_object;
		}

		// Apenas adiciona o seller center se passado de forma explicita.
		if ($temp) {
			$url_object = 'tmp' . '/' . $url_object;
		}

		// Concatena a chave ao endpoint e bucket name.
		if ($add_bucket) {
			$url_object = $this->bucket_name . "/" . $url_object;
		}

		return $url_object;
	}

	/**
	 * Retorna a URL completa de um asset.
	 * @param	 string		 	$url_object URL do asset para ser completada. 
	 * @param	 bool			$add_sellercenter Flag opcional (Padrão: Falso) para definir se adiciona ou não o nome do sellercenter ao url.
	 * @param	 bool			$temp Se o arquivo deve ser temporário ou não. 
	 * 
	 * @return	 string URL completa do asset.
	 */
	function getAssetUrl($url_object, $add_sellercenter = true, $temp = false)
	{
		// Apenas adiciona o seller center se passado de forma explicita.
		if ($add_sellercenter) {
			$url_object = $this->sellercenter['value'] . '/' . $url_object;
		}

		// Apenas adiciona o seller center se passado de forma explicita.
		if ($temp) {
			$url_object = 'tmp' . '/' . $url_object;
		}

		// Concatena a chave ao endpoint e bucket name.
		$bucket_name = $this->bucket_name;
		$endpoint = $this->config->item('Bucket_Endpoint');
		return $endpoint . "/" . $bucket_name . "/" . $url_object;
	}

	/**
	 * Verifica se um objeto está direatemente no pseudo diretório do prefixo e não em um sub diretório.
	 * Ex: Com o prefixo 'teste/', 'teste/planilha.csv' é válido, já 'teste/planilhas/planilha1.csv' não.
	 * @param	 string 		$url_key Chave do objeto a ser verificada.
	 * @param	 string		 	$url_prefix Chave prefixo do objeto.
	 * @param	 bool 			$add_sellercenter Flag para definir se adiciona ou não o nome do sellercenter ao prefixo passado.
	 * 
	 * @return	 string			Retorna a chave do objeto caso esteja diretamente no diretório, se não, retorna uma string vazia.
	 */
	function getDirectObjectKey($url_key, $url_prefix, $add_sellercenter = true)
	{
		// Apenas adiciona o seller center se passado de forma explicita.
		// url_key já é o caminho completo do objeto dentro do bucket, então não é necessário adicionar nada a ele.
		// url_prefix pode ser passado sem o valor do seller center.
		if ($add_sellercenter) {
			$url_prefix = $this->sellercenter['value'] . "/" . $url_prefix;
		}

		// Remove o prefixo da chave.
		$url_key = str_replace($url_prefix, "", $url_key);

		// Caso a chave comece com /, remove a /.
		if (strpos($url_key, '/') === 0) {
			$url_key = substr($url_key, 1);
		}

		// Caso encontre '/' na chave, então é pseudo sub diretório.
		$isSubDirectory = strpos($url_key, '/') !== false;

		// Caso seja pseudo sub diretório, retorna vazio.
		return !$isSubDirectory ? $url_key : "";
	}

	/**
	 * Busca objetos no bucket, retornando a URL já montada e a chave do arquivo.
	 * Retorna, por padrão, todos objetos que estejam diretamente dentro da pseudo pasta prefixo.
	 * Não retorna nenhum objeto que esteja dentro de um pseudo diretório após o prefixo.
	 * 
	 * Ex: Se o prefixo for 'teste/', irá retornar 'teste/image.jpg', mas não retornará 'teste/images/image.jpg'.
	 * 
	 * @param	 string 		$url_prefix Prefixo da url do produto a ser buscado.
	 * @param	 bool 			$add_sellercenter Flag para definir se adiciona ou não o nome do sellercenter ao prefixo passado.
	 * 
	 * @return	 array{success:bool,contents:array{array{key:string,url:string}}} Retorna array contendo os resultados.
	 */
	function getFinalObject($url_prefix, $add_sellercenter = true)
	{
		// Apenas adiciona o seller center se passado de forma explicita.
		if ($add_sellercenter) {
			$url_prefix = $this->sellercenter['value'] . '/' . $url_prefix;
		}

		// Busca os objetos no bucket. Não repassa o seller center.
		$listObjects = $this->bucket->listObjects($url_prefix, false);

		// Cria o array a ser retornado.
		$returnArr = [];

		// Caso tenha dado certo, busca o conteudo.
		if ($listObjects['success']) {
			// Percorre cada elemento. 
			foreach ($listObjects['contents'] as $key => $object_data) {
				// Busca a chave do objeto. Ex: nomeDaImagem.jpg
				$key = $this->bucket->getDirectObjectKey($object_data['Key'], $url_prefix, false);

				// Verifica se não é de subdiretório.
				if ($key) {
					// Monta o URL do objeto no bucket.
					$obj_url = $this->bucket->getAssetUrl($object_data['Key'], false);

					// Array contendo a chave e url do do objeto.
					$object = ['key' => $key, 'url' => $obj_url];

					// Insiro o URL no array para ser retornado.
					array_push($returnArr, $object);
				};
			}
		}

		// Retorna o array com os resultados.
		return [
			'success'	=> $listObjects['success'],
			'contents'	=> $returnArr
		];
	}

	/**
	 * Busca o tamanho de determinado objeto.
	 * @param	 string 		$url_object Url do objeto cujo tamanho deve ser buscado. Deve ser o URL completo incluindo seller center.
	 * @param	 bool 			$add_sellercenter Flag para definir se adiciona ou não o nome do sellercenter ao prefixo passado.
	 * 
	 * @return	 array{success:bool,size:mixed} Retorna o status e o tamanho do objeto. Em caso de erro, retorna a mensagem.
	 */
	function getObjectSize($url_object, $add_sellercenter = true)
	{
		// Apenas adiciona o seller center se passado de forma explicita.
		if ($add_sellercenter) {
			$url_object = $this->sellercenter['value'] . "/" . $url_object;
		}

		try {
			// Verifica se o objeto existe ou não.
			$result = $this->clientS3->headObject([
				'Bucket'	=> $this->bucket_name,
				'Key'	=> $url_object
			]);

			// Retorna true caso o objeto exista.
			return [
				'success'	=> true,
				'size'		=> $result['ContentLength'],
			];
		} catch (AwsException $e) {
			// Caso não tenha sido encontrado, retorna false.
			if ($e->getAwsErrorCode() == 'NotFound') {
				return [
					'success'	=> false,
					'size'		=> "Objeto não encontrado."
				];
			}

			// Ocorreu um erro, retorna falso.
			return [
				'success'	=> false,
				'size'		=> $e->getMessage()
			];
		}
	}

	/**
	 * Verifica se um pseudo-diretório existe.
	 * @param	 string 		$url_prefix Prefixo do diretório.
	 * @param	 bool 			$add_sellercenter Flag para definir se adiciona ou não o nome do sellercenter ao prefixo passado.
	 * 
	 * @return	 bool
	 */
	function isDirectory($url_prefix, $add_sellercenter = true)
	{
		// Apenas adiciona o seller center se passado de forma explicita.
		if ($add_sellercenter) {
			$url_prefix = $this->sellercenter['value'] . "/" . $url_prefix;
		}

		try {

            if (!$this->clientS3){
                return false;
            }

			// Verifica se existe qualquer objeto neste prefixo.
			$list = $this->clientS3->listObjectsV2([
				'Bucket' 	=> $this->bucket_name,
				'Prefix' 	=> $url_prefix,
				'MaxKeys'	=> 1 // Limita para um pois apenas precisamos saber se existe ou não.
			]);

			// Verifica se o diretório existe.
			if (!isset($list['Contents']) || count($list['Contents']) <= 0) {
				return  false;
			}

			// Retorna true caso o diretório exista.
			return 				true;
		} catch (AwsException $e) {
			// Caso não tenha sido encontrado, retorna false.
			if ($e->getAwsErrorCode() == 'NotFound') {
				return  false;
			}

			// Ocorreu um erro, retorna falso.
			return false;
		}
	}

	/**
	 * Verifica se uma URL especifica existe como prefixo.
	 * Pode ser utilizada para verificar se determinado arquivo ou pseudo-diretório existe.
	 * @param	 string 		$url_prefix Prefixo da chave do objeto.
	 * @param	 bool 			$add_sellercenter Flag para definir se adiciona ou não o nome do sellercenter ao prefixo passado.
	 * 
	 * @return	 array{success:bool,contents:array{},message:mixed} Retorna o status e o conteudo. Em caso de erro, retorna a mensagem.
	 */
	function listObjects($url_prefix, $add_sellercenter = true)
	{
		// Apenas adiciona o seller center se passado de forma explicita.
		if ($add_sellercenter) {
			$url_prefix = $this->sellercenter['value'] . "/" . $url_prefix;
		}

		try {

            if ($this->clientS3){
                // Pega a lista de objetos com este prefixo.
                $contents = $this->clientS3->listObjectsV2([
                    'Bucket'	=> $this->bucket_name,
                    'Prefix'	=> $url_prefix
                ]);

                // Verifica se há algum conteúdo e retorna caso haja.
                if ($contents['Contents']) {
                    return [
                        'success'	=> true,
                        'contents'	=> $contents['Contents'],
                        'message' => "Listagem realizada com sucesso."
                    ];
                }
            }

			// Não há nenhum conteudo.
			return [
				'success'	=> false,
				'contents'	=> [],
				'message' => "Não há nenhum conteúdo."
			];
		} catch (Exception $e) {
			// Ocorreu um erro, retorna falso.
			return [
				'success'	=> false,
				'contents'	=> [],
				'message' => $e->getMessage()
			];
		}
	}

	/**
	 * Verifica se uma URL especifica existe como prefixo.
	 * Pode ser utilizada para verificar se determinado arquivo ou pseudo-diretório existe.
	 * @param	 string 		$url_prefix Prefixo da chave do objeto.
	 * @param	 bool 			$add_sellercenter Flag para definir se adiciona ou não o nome do sellercenter ao prefixo passado.
	 * 
	 * @return	 array{success:bool,contents:mixed} Retorna o status e o conteudo. Em caso de erro, retorna a mensagem.
	 */
	function listObjectsUrl($url_prefix, $add_sellercenter = true)
	{
		// Apenas adiciona o seller center se passado de forma explicita.
		if ($add_sellercenter) {
			$url_prefix = $this->sellercenter['value'] . "/" . $url_prefix;
		}

		try {
			// Pega a lista de objetos com este prefixo.
			$contents = $this->clientS3->listObjectsV2([
				'Bucket'	=> $this->bucket_name,
				'Prefix'	=> $url_prefix
			]);

			// Verifica se há algum conteúdo e retorna caso haja.
			if ($contents['Contents']) {

				// Percorre cada objeto e cria a URL completa.
				foreach ($contents['Contents'] as $key => &$value) {
					$value['Key'] = $this->getAssetUrl($value['Key'], false);
				}

				return [
					'success'	=> true,
					'contents'	=> $contents['Contents']
				];
			}

			// Não há nenhum conteudo.
			return [
				'success'	=> false,
				'contents'	=> []
			];
		} catch (Exception $e) {
			// Ocorreu um erro, retorna falso.
			return [
				'success'	=> false,
				'contents'	=> $e->getMessage()
			];
		}
	}

	/**
	 * Verifica se um objeto existe ou não no bucket.
	 * @param	 string 		$url_object URL do objeto para ser buscada no bucket.
	 * @param	 bool		 	$add_sellercenter Adiciona o seller center como prefixo da url.
	 * 
	 * @return	 array{success:bool,message:mixed}
	 */
	function objectExists($url_object, $add_sellercenter = true)
	{
		// Apenas adiciona o seller center se passado de forma explicita.
		if ($add_sellercenter) {
			$url_object = $this->sellercenter['value'] . "/" . $url_object;
		}

		try {
			// Verifica se o objeto existe ou não.
			$this->clientS3->headObject([
				'Bucket'	=> $this->bucket_name,
				'Key'		=> $url_object
			]);

			// Retorna true caso o objeto exista.
			return [
				'success'	=> true,
				'message'	=> "O objeto existe."
			];
		} catch (AwsException $e) {
			// Caso não tenha sido encontrado, retorna false.
			if ($e->getAwsErrorCode() == 'NotFound') {
				return [
					'success'	=> false,
					'message'	=> "Objeto não encontrado."
				];
			}

			return [
				'success'	=> false,
				'message'	=> $e->getMessage()
			];
		}
	}

	/**
	 * Pseudo forma de renomear um objeto.
	 * Não há forma nativa de renomear objetos nos buckets, portanto iremos criar uma cópia e deletar o antigo.
	 * @param	 string	 		$url_file Chave do objeto a ser 'renomeado'.
	 * @param	 string 		$url_dest Nova chave do objeto.
	 * @param	 bool 			$add_sellercenter Flag para definir se adiciona ou não o nome do sellercenter ao prefixo passado.
	 * 
	 * @return	 array{success:bool,message:mixed} Bool com sucesso e mensagem de retorno.
	 */
	function renameObject($url_file, $url_dest, $add_sellercenter = true)
	{
		// Caso não mude nada, apenas retorna.
		if ($url_file == $url_dest) {
			return false;
		}

		// Adiciona o prefixo do seller center aos arquivos.
		if ($add_sellercenter) {
			$url_file = $this->sellercenter['value'] . "/" . $url_file;
			$url_dest = $this->sellercenter['value'] . "/" . $url_dest;
		}
		// Monta a url do source e dest.
		$bucket_url_file = $this->bucket_name . "/" . $url_file;
		try {
			// Realiza a cópia do objeto. 
			$this->clientS3->copyObject([
				'Bucket'	=>  $this->bucket_name,
				'Key'		=> $url_dest,
				'CopySource' => $bucket_url_file,
			]);

			// Deleta o objeto anterior.
			// Passa add_sellercenter como falso pois já há o seller center na URL.
			$deleted = $this->deleteObject($url_file, false);
			// Caso não tenha conseguido deletar, então deleta o objeto de destino.
			// Não há como ele ter sido criado a partir de um objeto inexistente, portanto não conseguiu deletar por algum outro tipo de erro.
			if (!$deleted['success']) {
				$this->deleteObject($url_dest);
				return [
					"success" => false,
					"message" => $deleted['message']
				];
			}

			// Tudo certo.
			return [
				"success" => true,
				"message" => "Renomeado com sucesso."
			];
		} catch (Exception $e) {
			// Algum erro ocorreu.
			return [
				"success" => false,
				"message" => $e->getMessage()
			];
		}
	}

	/**
	 * Pseudo forma de renomear multiplos objetos.
	 * @param	 string	 		$url_prefix Prefixo dos objetos a serem 'renomeados'.
	 * @param	 string 		$url_prefix_dest Novo prefixo dos objetos.
	 * @param	 bool 			$add_sellercenter Flag para definir se adiciona ou não o nome do sellercenter ao prefixo passado.
	 * 
	 * @return	 array{success:bool,message:mixed} Bool com sucesso e mensagem de retorno.
	 */
	function renameDirectory($url_prefix, $url_prefix_dest, $add_sellercenter = true)
	{
		// Caso não mude nada, apenas retorna.
		if ($url_prefix == $url_prefix_dest) {
			return false;
		}

		$result = $this->copyMany($url_prefix, $url_prefix_dest, $add_sellercenter);
		if ($result["success"]) {
			$this->deleteDirectory($url_prefix, $add_sellercenter);
		} else {
			return [
				"message" => "Não foi possível copiar o diretório: {$result['message']}",
				"success" => $result["success"]
			];
		}

		return [
			"success" => true,
			"message" => "Diretório renomeado com sucesso."
		];
	}

	/**
	 * Realiza o envio de um arquivo para o S3/OCI através da sua Stream.
	 * Fecha a Stream de forma automática.
	 * @param	 Stream 		$file Stream do arquivo para ser inserido.
	 * @param	 string 		$url_object URL do asset para inserir no bucket.
	 * @param	 bool			$temp Se o arquivo deve ser temporário ou não.
	 * 
	 * @return	 array{success:bool,url:mixed} URL caso bem sucedido ou mensagem de erro.
	 */
	function sendFileToObjectStorage($file, $url_object, $temp = false)
	{
		// Sempre envia com o seller center, visto que ele não deve apresentar ele antes da inserção.
		$url_object = $this->sellercenter['value'] . "/" . $url_object;

		// Envia o arquivo para o diretório temporário do bucket.
		// Será excluido após determinado tempo baseado em politica de prefixo. 
		if ($temp) {
			$url_object = 'tmp' . "/" . $url_object;
		}

		try {
			// Realiza o envio dos dados e recebe a resposta.
			$response = $this->clientS3->putObject([
				'Bucket'	=>  $this->bucket_name,
				'Key'		=> 	$url_object,
				'Body'		=>  $file,
			]);

			// Retorna a url.
			return [
				'success' => true,
				'url'     => $response['ObjectURL']
			];
		} catch (Exception $e) {
			return [
				'success' => false,
				'url'     => $e->getMessage()
			];
		}
	}

	/**
	 * Cria uma instância do cliente S3/OCI.
	 */
	function setClientBucket()
	{
        if (!$this->config->item('Bucket_Region')
        || !$this->config->item('Bucket_Endpoint')
        || !$this->config->item('Bucket_Key')
        || !$this->config->item('Bucket_Secret')){
            return;
        }
		$this->clientS3 = new S3Client([
			'version'	=> 'latest',
			'region'	=> $this->config->item('Bucket_Region'),
			'endpoint'	=> $this->config->item('Bucket_Endpoint'),
			'use_path_style_endpoint' => true,
			'credentials'	=> [
				'key'	=>  $this->config->item('Bucket_Key'),
				'secret' => $this->config->item('Bucket_Secret')
			],
			'http'	=> [
				'verify' => false
			]
		]);
	}

	/**
	 * Utiliza a classe Transfer para transferir um diretório por inteiro.
	 * @param	 string			$dir Nome do diretório a ser enviado.
	 * @param	 bool 			$add_sellercenter Flag para definir se adiciona ou não o nome do sellercenter ao prefixo passado.
	 * 
	 * @return	 array{success:bool,message:mixed} Bool com sucesso e mensagem de retorno.
	 */
	function transferDirectory($dir, $add_sellercenter = true)
	{
		try {
			// Verifica se a pasta existe.
			if (!is_dir(FCPATH . $dir)) {
				return [
					'success'	=> false,
					'message'	=> "Pasta $dir não existe."
				];
			}
			// Cria uma instância para transferência.
			$manager = $this->bucket->createTransfer(FCPATH . $dir, $dir, $add_sellercenter);
			$manager->transfer();
			return [
				'success'	=> true,
				'message'	=> "Transferência concluida."
			];
		} catch (AwsException $e) {
			// Trata erro da AWS.
			return [
				'success'	=> false,
				'message'	=> $e->getAwsErrorMessage()
			];
			return false;
		} catch (Exception $e) {
			// Trata qualquer outro tipo de erro.
			return [
				'success'	=> false,
				'message'	=> $e->getMessage()
			];
		}

		// Não deveria bater.
		return [
			'success'	=> false,
			'message'	=> "Não concluiu corretamente."
		];
	}
}
