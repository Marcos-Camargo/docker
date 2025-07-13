<?php
$token = '009055487a42853ceaab83ffbcdfe40903efd3e12cfbe9204c8735050a404d1e';
$issueId = $argv[1];
$url = "http://10.150.24.138:9000/api/0/issues/$issueId/";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PUT',
    CURLOPT_POSTFIELDS => json_encode(['status' => 'resolved']),
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
curl_close($ch);

echo "Erro $issueId marcado como resolvido.\n";
