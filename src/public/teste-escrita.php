<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Caminho completo que você quer testar (edite conforme necessário)
$filepath = __DIR__ . '/application/logs/test_file.txt';

// Cria pasta se não existir
$dir = dirname($filepath);
if (!is_dir($dir)) {
    echo "Criando diretório: $dir\n";
    if (!mkdir($dir, 0777, true)) {
        die("ERRO: Falha ao criar diretório.\n");
    } else {
        echo "Diretório criado com sucesso.\n";
    }
} else {
    echo "Diretório já existe.\n";
}

// Mostra permissões da pasta
$dirPerms = substr(sprintf('%o', fileperms($dir)), -4);
echo "Permissões do diretório: $dirPerms\n";

// Teste de abertura do arquivo
echo "Tentando abrir arquivo para escrita (modo 'ab')...\n";
$fp = @fopen($filepath, 'ab');

if (!$fp) {
    die("não consigo abrir\n");
}

$stat   = fstat($fp);          // metadados do handle já aberto
$isNew  = ($stat['size'] === 0 && $stat['ctime'] === $stat['mtime']);

if (!$fp) {
    echo "ERRO: Não foi possível abrir o arquivo.\n";

    // Diagnóstico detalhado
    if (file_exists($filepath)) {
        echo "Arquivo existe.\n";
        $filePerms = substr(sprintf('%o', fileperms($filepath)), -4);
        echo "Permissões do arquivo: $filePerms\n";
    } else {
        echo "Arquivo não existe.\n";
    }

    echo "Owner do diretório (ID): " . fileowner($dir) . "\n";
    echo "Grupo do diretório (ID): " . filegroup($dir) . "\n";

    die("Encerrando script.\n");
}

echo "Arquivo aberto com sucesso.\n";

// Teste de escrita
$data = "Teste de escrita em " . date('Y-m-d H:i:s') . "\n";
echo "Escrevendo no arquivo...\n";
if (fwrite($fp, $data) === false) {
    echo "ERRO: Falha ao escrever no arquivo.\n";
} else {
    echo "Escrita realizada com sucesso.\n";
}

// Fechando o arquivo
fclose($fp);
if ($isNew) {
    chmod($filepath, 0664);
    echo "Permissão 0664 aplicada.\n";
} elseif ($isNew) {
    echo "Arquivo é novo, mas não sou o owner; chmod() ignorado.\n";
}
echo "Arquivo fechado.\n";

// Verificando permissões finais
clearstatcache();
$filePerms = substr(sprintf('%o', fileperms($filepath)), -4);
echo "Permissões finais do arquivo: $filePerms\n";

echo "Owner do arquivo (ID): " . fileowner($filepath) . "\n";
echo "Grupo do arquivo (ID): " . filegroup($filepath) . "\n";

echo "Teste finalizado.\n";
