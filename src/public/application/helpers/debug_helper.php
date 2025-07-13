<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

function saveSlowQueries()
{
    $queries = get_instance()->db->queries;
    $querieTimes = get_instance()->db->query_times;

    if ($queries) {

        foreach ($querieTimes as $key => $time) {
            if ($time >= 0.1) {

                $query = $queries[$key];
                $query = str_replace(array("\r\n", "\n"), " ", $query);

                $item = [
                    'execution_time' => number_format($time, 4),
                    'date' => dateNow()->format(DATETIME_BRAZIL),
                    'query' => $query,
                    'uri' => $_SERVER['REQUEST_URI']
                ];

                if (!isset($sellercenter)) {
                    get_instance()->load->model('model_settings');
                    $sellercenter = get_instance()->model_settings->getValueIfAtiveByName('sellercenter');
                }

                $txt = json_encode($item);

                $key = "$sellercenter:slow_queries:" . md5($txt);
                \App\libraries\Cache\CacheManager::setex($key, $txt, 60 * 60 * 24 * 7);

            }
        }

    }
}