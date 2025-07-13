<?php

/**
 * Drivers suportados:
 * sync: o job é executado imediatamente com $job->handle(), sem enfileirar.
 * database: o job é serializado e gravado numa tabela via Job_model, para ser processado depois.
 * oracle: o job é enviado para uma fila OCI usando o QueueService (métodos sendOCIMessageQueue, etc.).
 * redis: o job é enviado para uma fila no redis (em memória)
 */
$config['default_driver'] = getenv('QUEUE_DRIVER') ?: \App\libraries\Enum\QueueDriverEnum::ORACLE;
