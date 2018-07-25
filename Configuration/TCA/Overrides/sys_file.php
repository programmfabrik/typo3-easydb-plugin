<?php
$GLOBALS['TCA']['sys_file']['columns'] = array_replace_recursive(
    $GLOBALS['TCA']['sys_file']['columns'],
    [
        'easydb_uid' => [
            'label' => 'easydb uid',
            'config' => [
                'readOnly' => true,
                'type' => 'input',
                'size' => 255,
            ],
        ],
        'easydb_asset_id' => [
            'label' => 'easydb uid',
            'config' => [
                'readOnly' => true,
                'type' => 'input',
                'size' => 255,
            ],
        ],
        'easydb_asset_version' => [
            'label' => 'easydb uid',
            'config' => [
                'readOnly' => true,
                'type' => 'input',
                'size' => 255,
            ],
        ],
        'easydb_system_object_id' => [
            'label' => 'easydb uid',
            'config' => [
                'readOnly' => true,
                'type' => 'input',
                'size' => 255,
            ],
        ],
        'easydb_objecttype' => [
            'label' => 'easydb uid',
            'config' => [
                'readOnly' => true,
                'type' => 'input',
                'size' => 255,
            ],
        ],
        'easydb_object_id' => [
            'label' => 'easydb uid',
            'config' => [
                'readOnly' => true,
                'type' => 'input',
                'size' => 255,
            ],
        ],
        'easydb_object_version' => [
            'label' => 'easydb uid',
            'config' => [
                'readOnly' => true,
                'type' => 'input',
                'size' => 255,
            ],
        ],
        'easydb_uuid' => [
            'label' => 'easydb uid',
            'config' => [
                'readOnly' => true,
                'type' => 'input',
                'size' => 255,
            ],
        ],
    ]
);
