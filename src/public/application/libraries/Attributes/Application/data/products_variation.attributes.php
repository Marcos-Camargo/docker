<?php

return [
    [
        'name' => 'Cor',
        'code' => 'color',
        'module' => Model_attributes::ATTR_TYPE_PRODUCT_VARIATION,
        'active' => Model_attributes::ACTIVE,
        'system' => Model_attributes::IS_SYSTEM_ATTR,
        'visible' => Model_attributes::INVISIBLE,
        'values' => [
            [
                'value' => 'Padrão',
                'code' => 'default',
                'visible' => Model_attributes::INVISIBLE
            ],
        ]
    ],
    [
        'name' => 'Tamanho',
        'code' => 'size',
        'module' => Model_attributes::ATTR_TYPE_PRODUCT_VARIATION,
        'active' => Model_attributes::ACTIVE,
        'system' => Model_attributes::IS_SYSTEM_ATTR,
        'visible' => Model_attributes::INVISIBLE,
        'values' => [
            [
                'value' => 'Padrão',
                'code' => 'default',
                'visible' => Model_attributes::INVISIBLE
            ],
        ]
    ],
    [
        'name' => 'Sabor',
        'code' => 'flavor',
        'module' => Model_attributes::ATTR_TYPE_PRODUCT_VARIATION,
        'active' => isset($settingsModel) ? (!empty($settingsModel->getFlavorActive()) ? Model_attributes::ACTIVE : Model_attributes::INACTIVE) : Model_attributes::INACTIVE,
        'system' => Model_attributes::IS_SYSTEM_ATTR,
        'visible' => Model_attributes::INVISIBLE,
        'values' => [
            [
                'value' => 'Padrão',
                'code' => 'default',
                'visible' => Model_attributes::INVISIBLE
            ],
        ]
    ],
    [
        'name' => 'Voltagem',
        'code' => 'voltage',
        'module' => Model_attributes::ATTR_TYPE_PRODUCT_VARIATION,
        'active' => Model_attributes::ACTIVE,
        'system' => Model_attributes::IS_SYSTEM_ATTR,
        'visible' => Model_attributes::INVISIBLE,
        'values' => [
            [
                'value' => '110V',
                'code' => '110v'
            ],
            [
                'value' => '220V',
                'code' => '220v'
            ],
            [
                'value' => 'Bivolt',
                'code' => 'bivolt'
            ],
        ]
    ],
    [
        'name' => 'Grau',
        'code' => 'degree',
        'module' => Model_attributes::ATTR_TYPE_PRODUCT_VARIATION,
        'active' => isset($settingsModel) ? (!empty($settingsModel->getDegreeActive()) ? Model_attributes::ACTIVE : Model_attributes::INACTIVE) : Model_attributes::INACTIVE,
        'system' => Model_attributes::IS_SYSTEM_ATTR,
        'visible' => Model_attributes::INVISIBLE,
        'values' => [
            [
                'value' => 'Padrão',
                'code' => 'default',
                'visible' => Model_attributes::INVISIBLE
            ],
        ]
    ],
    [
        'name' => 'Lado',
        'code' => 'side',
        'module' => Model_attributes::ATTR_TYPE_PRODUCT_VARIATION,
        'active' => isset($settingsModel) ? (!empty($settingsModel->getSideActive()) ? Model_attributes::ACTIVE : Model_attributes::INACTIVE) : Model_attributes::INACTIVE,
        'system' => Model_attributes::IS_SYSTEM_ATTR,
        'visible' => Model_attributes::INVISIBLE,
        'values' => [
            [
                'value' => 'Padrão',
                'code' => 'default',
                'visible' => Model_attributes::INVISIBLE
            ],
        ]
    ]
];