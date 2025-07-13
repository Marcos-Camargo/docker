<?php
use Intervention\Image\ImageManagerStatic as Image;

require '../../../../system/libraries/Vendor/autoload.php';
// example of a PHP server code that is called in `uploadUrl` above
// file-upload.php script
header('Content-Type: application/json'); // set json response headers
// $mylog = fopen("/var/www/html/app/upload.log", "w") or die("Unable to open file!");

// $mylog = fopen(FCPATH."upload.log", "w") or die("Unable to open file!");
//$mylog = fopen("/var/www/html/app/upload.log", "w") or die("Unable to open file!");
//fwrite($mylog, $targetfile);
$outData = upload(); // a function to upload the bootstrap-fileinput files
echo json_encode($outData); // return json data
//fwrite($mylog, $outdata);
//fclose($mylog);
exit(); // terminate


// generate and fetch thumbnail for the file
function getThumbnailUrl($path, $fileName,$home) {
    // assuming this is an image file or video file
    // generate a compressed smaller version of the file
    // here and return the status
    $sourceFile = $path . '/' . $fileName;
    $targetFile = $path . '/thumbs/' . $fileName;
    //
    // generateThumbnail: method to generate thumbnail (not included)
    // using $sourceFile and $targetFile
    //
    if (generateThumbnail($sourceFile, $targetFile) === true) {
        return $home.'assets/images/product_image/thumbs/' . $fileName;
    } else {
        return $home.'assets/images/product_image/' . $fileName; // return the original file
    }
}

// main upload function used above
// upload the bootstrap-fileinput files
// returns associative array
function upload() {
    //$mylog = fopen("/var/www/html/app/upload.log", "w") or die("Unable to open file!");
    //	$mylog = fopen(FCPATH."upload.log", "w") or die("Unable to open file!");
    $serverpath = $_SERVER['SCRIPT_FILENAME'];
    $pos = strpos($serverpath,'assets');
    $serverpath = substr($serverpath,0,$pos);
    $home = $_SERVER['SCRIPT_NAME'];
    $pos = strpos($home,'assets');
    $home = substr($home,0,$pos);
    
    //write($mylog, $serverpath);
    //fwrite($mylog, $home);
    
    $preview = $config = $errors = [];
    $targetDir = $serverpath . 'assets/images/product_image';
    if (!file_exists($targetDir)) {
        @mkdir($targetDir,0775);
    }
    $fileBlob = 'fileBlob';                      // the parameter name that stores the file blob
    if (isset($_FILES[$fileBlob]) && isset($_POST['uploadToken'])) {
        $token = $_POST['uploadToken'];          // gets the upload token
        $targetDir .= "/".$token;
        
		//echo $targetDir; 
        //fwrite($mylog, $targetdir);
       
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0775 ,TRUE);
			chmod($targetDir,0775);
        }
        /*
         if (!validateToken($token)) {            // your access validation routine (not included)
         return [
         'error' => 'Access not allowed'  // return access control error
         ];
         }
         */
        //fwrite($mylog, $targetdir);
        $file = $_FILES[$fileBlob]['tmp_name'];  // the path for the uploaded file chunk
        // $fileName = $_POST['fileName'];          // you receive the file name as a separate post data
        $extension= pathinfo($_POST['fileName'], PATHINFO_EXTENSION);          // you receive the file name as a separate post data
        $fileName = microtime(true)*10000 . "." . $extension;          // you receive the file name as a separate post data
        $fileSize = $_POST['fileSize'];          // you receive the file size as a separate post data
        // $fileId = $_POST['fileId'];              // you receive the file identifier as a separate post data
        $fileId = microtime(true)*10000 . "." . $extension;              // you receive the file identifier as a separate post data
        $index =  $_POST['chunkIndex'];          // the current file chunk index
        $totalChunks = $_POST['chunkCount'];     // the total number of chunks for this file
        $targetFile = $targetDir.'/'.$fileName;  // your target file path
        if ($totalChunks > 1) {                  // create chunk files only if chunks are greater than 1
            $targetFile .= '_' . str_pad($index, 4, '0', STR_PAD_LEFT);
        }
        //fwrite($mylog, $targetfile);
        //fclose($mylog);
        $thumbnail = 'unknown.jpg';
        // if(move_uploaded_file($file, $targetFile)) {
        $upload = sendImageForUrl($targetDir, $file);
        if($upload['success']){
            // get list of all chunks uploaded so far to server
            $chunks = glob("{$targetDir}/{$upload['path']}_*");
            // check uploaded chunks so far (do not combine files if only one chunk received)
            $allChunksUploaded = $totalChunks > 1 && count($chunks) == $totalChunks;
            if ($allChunksUploaded) {           // all chunks were uploaded
                $outFile = $targetDir.'/'.$upload['path'];
                // combines all file chunks to one file
                combineChunks($chunks, $outFile);
            }
            // if you wish to generate a thumbnail image for the file
            // $targetUrl = getThumbnailUrl($path, $upload['path'],$home);
            // separate link for the full blown image file
            $zoomUrl = $home . 'assets/images/product_image/' . $token ."/" . $upload['path'];
            $fileSize = filesize('./../../../images/product_image/' . $token ."/" . $upload['path']);
            return [
                'chunkIndex' => $index,         // the chunk index processed
                'initialPreview' => $zoomUrl, // the thumbnail preview data (e.g. image)
                'initialPreviewConfig' => [
                    [
                        'type' => 'image',      // check previewTypes (set it to 'other' if you want no content preview)
                        'caption' => $upload['path'], // caption
                        'key' => $token ."/" . $upload['path'],       // keys for deleting/reorganizing preview
                        'fileId' => $upload['path'],    // file identifier
                        'size' => $fileSize,    // file size
                        'zoomData' => $zoomUrl, // separate larger zoom data
                        'token' => $token,		// token (file subdir)
                    ]
                ],
                'append' => true
            ];
        } else {
            return [
                'error' => 'Error uploading chunk ' . $_POST['chunkIndex']
            ];
        }
    }
    return [
        'error' => 'No file found'
    ];
}

// combine all chunks
// no exception handling included here - you may wish to incorporate that
function combineChunks($chunks, $targetFile) {
    // open target file handle
    $handle = fopen($targetFile, 'a+');
    
    foreach ($chunks as $file) {
        fwrite($handle, file_get_contents($file));
    }
    
    // you may need to do some checks to see if file
    // is matching the original (e.g. by comparing file size)
    
    // after all are done delete the chunks
    foreach ($chunks as $file) {
        @unlink($file);
    }
    
    // close the file handle
    fclose($handle);
}


function sendImageForUrl(string $caminho, string $fileUrl): array
{
    // timeout de 3min
    set_time_limit(180);

    try {
        list($width_orig, $height_orig, $tipo) = getimagesize($fileUrl);
        $fileSize = strlen(file_get_contents($fileUrl));
    } catch (\Exception $e) {
        echo $e->getMessage();
        return array('success' => false, 'data' => "A imagem tem que ser um URL de imagem válida. URL: {$fileUrl} <br> {$e->getMessage()}");
    }

    $width      = $width_orig;
    $height     = $height_orig;
    $resize     = false;
    $nameImage  = microtime(true) * 10000; // Define novo nome para a imagem

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

    $quality = 100;
    // se a imagem é grande, abaixo a qualidade dela que diminiu bastente o tamanho
    if ($fileSize> 700000)
        $quality = 75;

    try{
        $image = Image::make($fileUrl)->resize($width, $height);

        Image::canvas($image->width(), $image->height(), 'ffffff')
            ->insert($image)
            ->save("{$caminho}/{$nameImage}.jpg", $quality, 'jpg');

    } catch (Exception $e) {
        return array('success' => false, 'data' => $e->getMessage());
    }


    return array('success' => true, 'path' => $nameImage.'.jpg');
}

?>	