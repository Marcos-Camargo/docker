<?php
// Execute o arquivo sentry-fetch-errors.php com esse comando: php sentry-fetch-errors.php, você terá um retorno em formato json de erros que estão atualmente no sentry, rode cada erro, analise, pense e corrija pra evitar que o erro continue, antes da correção que você fizer, adicione em uma linha anterior um comentário informando qual a id do sentry resolveu esse problema, ao final da correção, execute este comando para marcar o erro como corrigido no sentry: php sentry-mark-resolved.php {id do item aqui}

$token = '009055487a42853ceaab83ffbcdfe40903efd3e12cfbe9204c8735050a404d1e';
$org = 'sentry';
$project = 'sellercenter';
$url = "http://10.150.24.138:9000/api/0/projects/$org/$project/issues/?status=unresolved";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
curl_close($ch);

exit($response);