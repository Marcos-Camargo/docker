<?php
/*

*/   
class AcertoImagens extends BatchBackground_Controller {
		
	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

   			$logged_in_sess = array(
   				'id' => 1,
		        'username'  => 'batch',
		        'email'     => 'batch@conectala.com.br',
		        'usercomp' => 1,
		        'userstore' => 0,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_orders','myorders');
		$this->load->model('model_nfes','mynfes');
		$this->load->model('model_quotes_ship','myquotesship');
		$this->load->model('Model_freights','myfreights');
		$this->load->model('model_clients','myclients');
    }

	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			get_instance()->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		get_instance()->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		/* faz o que o job precisa fazer */
		//$this->criaImagemPrincipal();
		// $this->acertaImagens();
		$this->acertaImagemPrincipal();
		
		/* encerra o job */
		get_instance()->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	
	}	

	function acertaImagemPrincipal() {
		$sql = "SELECT * FROM products where principal_image like '%conectala.tec.br%'";
		$query = $this->db->query($sql);
		$products = $query->result_array();
		foreach($products as $product)
		{
			$pi = str_replace('http://','https://',
				    str_replace('conectala.tec.br','conectala.com.br', $product['principal_image']));
			
			echo "Acertando de ".$product['id']." - ".$product['sku']."\n de  :".$product['principal_image']."\n para:".$pi."\n";			
			$sql = "update products set principal_image=? WHERE id = ? ";
        	$this->db->query($sql, array($pi,$product['id']));
		}

	}
	
	function criaImagemPrincipal()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$root = FCPATH . 'assets/images/product_image/';
		$folders = scandir($root);	
		$cnt = 0;
		foreach($folders as $folder) {
			if (($folder!=".") && ($folder!="..")) {
				
				$sql = 'SELECT * FROM products WHERE image = "'.$folder.'"';
				$query = $this->db->query($sql);
				$prd = $query->row_array();
				if (!empty($prd)) {
					//echo $folder.' do produto '.$prd['id']."\n"; 
					$fotos = scandir($root.$folder);
					foreach($fotos as $foto) {
						if (($foto!=".") && ($foto!="..")) {
							$principal_image = base_url('assets/images/product_image/'.$folder).'/'.$foto;
							//echo $principal_image;
							$sql = 'UPDATE products SET principal_image = "'.$principal_image.'" WHERE id ='.$prd['id'];
							$update = $this->db->query($sql);
							$cnt++;
							break;
						}
					}
				}
				else {
					//echo 'Remover '.$folder."\n";
					shell_exec("sudo /bin/rm -fr '".$root.$folder."'");
				}
			}
		}	
		echo 'foram alterados '.$cnt."\n";	
		
	}

	function acertaImagens()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$root = FCPATH . 'assets/images/product_image/';
		$folders = scandir($root);	
		$cnt = 0;
		foreach($folders as $folder) {
			if (($folder!=".") && ($folder!="..")) {
				
				$sql = 'SELECT * FROM products WHERE image = "'.$folder.'"';
				$query = $this->db->query($sql);
				$prd = $query->row_array();
				if (!empty($prd)) {
					//echo $folder.' do produto '.$prd['id']."\n"; 
					$fotos = scandir($root.$folder);
					//shell_exec("sudo chown ubuntu '".$root.$folder."'");
					shell_exec("sudo chmod 775 '".$root.$folder."'");
					
					foreach($fotos as $foto) {
						if (($foto!=".") && ($foto!="..")) {
						//	shell_exec("sudo chown ubuntu '".$root.$folder.'/'.$foto."'");
							shell_exec("sudo chmod 664 '".$root.$folder.'/'.$foto."'");
							if ($this->notJpeg($root.$folder.'/'.$foto)) {
								echo $prd['id'].' '.$root.$folder.'/'.$foto."\n"; 
								 
								// echo ' redimenciona ou muda para jpg '.$foto."\n";
								$path_parts = pathinfo($root.$folder.'/'.$foto);	
        						$nameImage = $root.$folder.'/'.$path_parts['filename'].'1.jpg';
								$upload = $this->sendImageForUrl($root.$folder.'/', $root.$folder.'/'.$foto); 
								if (($upload['success']) && file_exists($nameImage)) {
									shell_exec("sudo /bin/rm '".$root.$folder.'/'.$foto."'");
								}
								$cnt++;
								//die;
								//if ($cnt >200) die;
							}
							else {
								//echo ' ok '.$foto."\n";
							}
						}
					}
				}
				else {
					//echo 'Remover '.$folder."\n";
					shell_exec("sudo /bin/rm -fr '".$root.$folder."'");
				}
			}
		}	
		echo 'foram alterados '.$cnt."\n";	
		
	}
	public function notJpeg($fileUrl)  
	{
		try {
			list($width_orig, $height_orig, $tipo) = getimagesize($fileUrl);
		} catch (Exception $e) {
            echo" erro ao ler $fileUrl \n";
			die; 
        } 
		$path_parts = pathinfo($fileUrl);
		if ($path_parts['extension'] != 'jpg') return true;  
		$filesize = filesize($fileUrl); 
		if ($filesize > 700000) {
			echo 'Files '.$fileUrl.' size = '.$filesize."\n";
			return true;
		}
		$width      = $width_orig;
        $height     = $height_orig;
		//echo '$width '.$width.' $height '.$height.' tipo '.$tipo."\n";  
		if ($width_orig < 800 || $height_orig < 800) return true;
		if($width_orig > 1200 || $height_orig > 1200) return true;
		if ($tipo !=2) return true;
		return false ;
		
	}
	
 	public function sendImageForUrl($caminho, $fileUrl)
    {
        try {
            list($width_orig, $height_orig, $tipo) = getimagesize($fileUrl);
        } catch (Exception $e) {
            return array('success' => false, 'data' => "A imagem tem que ser um URL de imagem válida. URL: {$fileUrl}");
        }

        $width      = $width_orig;
        $height     = $height_orig;
        $resize     = false; // min = imagem muito pequena vai redimensionar para o tamanho mínimo, | max = imagem muito grande vai redimensionar para o tamanho máximo | false = não precisa redimensionar
        // $nameImage  = md5(microtime()) . md5($fileUrl); // Define novo nome para a imagem
		
		$path_parts = pathinfo($fileUrl);	
        $nameImage = $path_parts['filename'].'1';
		
        // Verifica limites de 800x800 a 1200x1200
        if ($width_orig < 800 || $height_orig < 800) $resize = 'min';
        elseif($width_orig > 1200 || $height_orig > 1200) $resize = 'max';

        // Precisa redimensionar
        if($resize !== false) {
            // largura maior que altura
            if ($width > $height) {
                if($resize == "min") {
                    $width = (800 / $height) * $width;
                    $height = 800;
                }
                else if($resize == "max") {
                    $height = (1200 / $width) * $height;
                    $width = 1200;
                }
            }
            // altura maior que largura
            elseif ($height > $width) {
                if($resize == "min") {
                    $height = (800 / $width) * $height;
                    $width = 800;
                }
                else if($resize == "max") {
                    $width = (1200 / $height) * $width;
                    $height = 1200;
                }
            } else {
                $width = $resize == "min" ? 800 : 1200;
                $height = $resize == "min" ? 800 : 1200;
            }

            //Caso não consiga redimensionar propocional entre 800x800 e 1200x1200, vai ser preciso distorcer a imagem
            if ($width < 800)   $width  = 800;
            if ($height < 800)  $height = 800;
            if ($width > 1200)  $width  = 1200;
            if ($height > 1200) $height = 1200;
        }
		// se o tamnaho do aquivo for grande, baixo a qualidade
		$quality = 100;
		if (filesize($fileUrl) > 700000) $quality = 75;
        try {
            $novaimagem = imagecreatetruecolor($width, $height);
            switch ($tipo) {
                // gif
                case 1:
                    $origem = imagecreatefromgif($fileUrl);
                    imagecopyresampled($novaimagem, $origem, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
                    imagejpeg($novaimagem, $caminho . $nameImage . '.jpg', $quality);
                    break;

                // jpg
                case 2:
                    $origem = imagecreatefromjpeg($fileUrl);
                    imagecopyresampled($novaimagem, $origem, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
                    imagejpeg($novaimagem, $caminho . $nameImage . '.jpg', $quality);
                    break;

                // png
                case 3:
					/*
                    imagesavealpha($novaimagem, true);
                    $cor_fundo = imagecolorallocatealpha($novaimagem, 0, 0, 0, 127);
                    imagefill($novaimagem, 0, 0, $cor_fundo);

                    $origem = imagecreatefrompng($fileUrl);
                    imagecopyresampled($novaimagem, $origem, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
                    imagepng($novaimagem, $caminho . $nameImage . '.png');
                    
					 * 
					 */
					$origem =imagecreatefrompng($fileUrl);
			    	$imageTmp = imagecreatetruecolor(imagesx($origem), imagesy($origem));
					imagefill($imageTmp, 0, 0, imagecolorallocate($imageTmp, 255, 255, 255));
					imagealphablending($imageTmp, TRUE);
				    imagecopy($imageTmp, $origem, 0, 0, 0, 0, imagesx($origem), imagesy($origem));
					
					imagecopyresampled($novaimagem, $imageTmp, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
                    imagejpeg($novaimagem, $caminho . $nameImage . '.jpg', $quality);
					imagedestroy($imageTmp);
					break;
                default:
					echo " tipo = ".$tipo."\n";
                    return array('success' => false, 'data' => "Tipo de imagem não suportado!");
            }

            imagedestroy($novaimagem);
            imagedestroy($origem);
        } catch (Exception $e) {
            return array('success' => false, 'data' => $e->getMessage());
        }

        return array('success' => true);
    }
	
}
?>
