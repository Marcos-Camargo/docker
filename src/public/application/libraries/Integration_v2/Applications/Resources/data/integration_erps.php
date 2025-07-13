<?php

require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/FlagMapper.php";

use Integration_v2\Applications\Resources\IntegrationERPResource as IntegrationERP;
use Integration_v2\viavarejo_b2b\Resources\Mappers\FlagMapper as ViaFlagMapper;

return [
    [
        'name' => 'bling',
        'description' => 'Bling',
        'hash' => IntegrationERP::generateKeyApp('bling', IntegrationERP::GLOBAL_ENCRYPT_KEY),
        'active' => 1
    ],
    [
        'name' => 'tiny',
        'description' => 'Tiny',
        'hash' => IntegrationERP::generateKeyApp('tiny', IntegrationERP::GLOBAL_ENCRYPT_KEY),
        'active' => 1
    ],
    [
        'name' => 'vtex',
        'description' => 'VTEX',
        'hash' => IntegrationERP::generateKeyApp('vtex', IntegrationERP::GLOBAL_ENCRYPT_KEY),
        'active' => 1
    ],
    [
        'name' => 'eccosys',
        'description' => 'Eccosys',
        'hash' => IntegrationERP::generateKeyApp('eccosys', IntegrationERP::GLOBAL_ENCRYPT_KEY),
        'active' => 1
    ],
    [
        'name' => 'jn2',
        'description' => 'JN2',
        'hash' => IntegrationERP::generateKeyApp('jn2', IntegrationERP::GLOBAL_ENCRYPT_KEY),
        'active' => 1
    ],
    [
        'name' => 'pluggto',
        'description' => 'PluggTo',
        'hash' => IntegrationERP::generateKeyApp('pluggto', IntegrationERP::GLOBAL_ENCRYPT_KEY),
        'active' => 1
    ],
    [
        'name' => 'bseller',
        'description' => 'BSeller',
        'hash' => IntegrationERP::generateKeyApp('bseller', IntegrationERP::GLOBAL_ENCRYPT_KEY),
        'active' => 1
    ],
    [
        'name' => 'anymarket',
        'description' => 'AnyMarket',
        'hash' => IntegrationERP::generateKeyApp('anymarket', IntegrationERP::GLOBAL_ENCRYPT_KEY),
        'active' => 1
    ],
    [
        'name' => 'lojaintegrada',
        'description' => 'Loja Integrada',
        'hash' => IntegrationERP::generateKeyApp('lojaintegrada', IntegrationERP::GLOBAL_ENCRYPT_KEY),
        'active' => 1
    ],
    [
        'name' => 'precode',
        'description' => 'Precode',
        'hash' => IntegrationERP::generateKeyApp('precode', IntegrationERP::GLOBAL_ENCRYPT_KEY),
        'active' => 1
    ],
    [
        'name' => 'aton',
        'description' => 'Aton',
        'hash' => IntegrationERP::generateKeyApp('aton', IntegrationERP::GLOBAL_ENCRYPT_KEY),
        'active' => 1
    ],
    [
        'name' => 'hubsell',
        'description' => 'Hubsell',
        'hash' => IntegrationERP::generateKeyApp('hubsell', IntegrationERP::GLOBAL_ENCRYPT_KEY),
        'active' => 1
    ],
    [
        'name' => 'viavarejo_b2b',
        'description' => 'Via',
        'hash' => IntegrationERP::generateKeyApp('viavarejo_b2b', IntegrationERP::GLOBAL_ENCRYPT_KEY),
        'active' => 1,
        'aliases' => [
            ViaFlagMapper::getIntegrationNameFromFlag(ViaFlagMapper::FLAG_CASASBAHIA),
            ViaFlagMapper::getIntegrationNameFromFlag(ViaFlagMapper::FLAG_EXTRA),
            ViaFlagMapper::getIntegrationNameFromFlag(ViaFlagMapper::FLAG_PONTOFRIO),
        ]
    ],
    [
        'name' => 'tray',
        'description' => 'Tray',
        'hash' => IntegrationERP::generateKeyApp('tray', IntegrationERP::GLOBAL_ENCRYPT_KEY),
        'active' => 0
    ],
    [
        'name' => 'internal_api_integration',
        'description' => 'API INTERNA',
        'hash' => IntegrationERP::generateKeyApp('internal_api_integration', IntegrationERP::GLOBAL_ENCRYPT_KEY),
        'active' => 0,
        'visible' => 0,
    ]
];

