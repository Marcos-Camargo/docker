<?php

namespace App\Libraries\Queue\Tools;

use App\Libraries\Cache\CacheManager;
use App\Libraries\Cache\RedisCacheHandler;

class RedisQueueMonitor
{
    protected $redis;

    public function __construct()
    {
        $this->redis = new RedisCacheHandler();
    }

    public function live(int $refreshSeconds = 2)
    {
        get_instance()->load->model('model_settings');
        $sellercenter = get_instance()->model_settings->getValueIfAtiveByName('sellercenter');

        while (true) {
            if (PHP_OS_FAMILY !== 'Windows') {
                system('clear');
            } else {
                system('cls');
            }

            echo "\n=== MONITORAMENTO AO VIVO - TODAS AS FILAS REDIS (Atualiza a cada {$refreshSeconds}s) ===\n";

            $allKeys = CacheManager::getAllKeysByPrefix("{$sellercenter}:queue:");
            if ($allKeys) {

                $allQueues = [];

                foreach ($allKeys as $key) {
                    if (preg_match("/^{$sellercenter}:queue:([^:]+):schedule$/", $key, $matches)) {
                        $allQueues[] = $matches[1];
                    } elseif (preg_match("/^{$sellercenter}:queue:([^:]+):/", $key, $matches)) {
                        $allQueues[] = $matches[1];
                    }
                }

                $allQueues = array_unique($allQueues);

                if (empty($allQueues)) {
                    echo "Nenhuma fila encontrada.\n";
                }

                echo "\nResumo das filas:\n";
                $header = sprintf("%-50s│ %-12s│ %-12s\n", 'Fila', 'Agendados', 'Imediatos');
                echo $header;
                echo str_repeat('─', 50).'┼'.str_repeat('─', 13).'┼'.str_repeat('─', 13)."\n";

                foreach ($allQueues as $queue) {
                    $agendados = $this->redis->zrange("{$sellercenter}:queue:{$queue}:schedule", 0, -1);
                    $scheduleSet = array_flip($agendados);

                    $allKeys = CacheManager::getAllKeysByPrefix("{$sellercenter}:queue:{$queue}:");
                    $imediatos = array_filter($allKeys, function ($k) use ($scheduleSet) {
                        return strpos($k, ':schedule') === false && !isset($scheduleSet[$k]);
                    });

                    $countAgendados = count($agendados);
                    $countImediatos = count($imediatos);

                    $corAgendados = $countAgendados === 0 ? "\033[33m" : ($countAgendados > 5 ? "\033[31m" : "\033[32m");
                    $corImediatos = $countImediatos === 0 ? "\033[33m" : ($countImediatos > 5 ? "\033[31m" : "\033[32m");

                    $linha = "%-50s│ %s%12s%s│ %s%12s%s".PHP_EOL;
                    printf(
                        $linha,
                        $queue,
                        $corAgendados, $countAgendados, "\033[0m",
                        $corImediatos, $countImediatos, "\033[0m"
                    );
                }
            }

            sleep($refreshSeconds);
            
        }
    }
}

// Execução ao vivo:
// (new \App\Libraries\Queue\Tools\RedisQueueMonitor())->live();
