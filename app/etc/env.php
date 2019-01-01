<?php
return [
  'backend' => [
    'frontName' => 'admin'
  ],
  'crypt' => [
    'key' => '5eb38a3e88319ee118ed78209e91f44c'
  ],
  'db' => [
    'table_prefix' => '',
    'connection' => [
      'default' => [
        'host' => '127.0.0.1',
        'dbname' => 'magento_aurinko',
        'username' => 'aurinko',
        'password' => 'odjosfl34RTfgfr',
        'active' => '1'
      ]
    ]
  ],
  'resource' => [
    'default_setup' => [
      'connection' => 'default'
    ]
  ],
  'x-frame-options' => 'SAMEORIGIN',
  'MAGE_MODE' => 'developer',
  'session' => [
    'save' => 'files'
  ],
  'cache_types' => [
    'config' => 1,
    'layout' => 1,
    'block_html' => 1,
    'collections' => 1,
    'reflection' => 1,
    'db_ddl' => 1,
    'eav' => 1,
    'customer_notification' => 1,
    'config_integration' => 1,
    'config_integration_api' => 1,
    'full_page' => 1,
    'translate' => 1,
    'config_webservice' => 1,
    'compiled_config' => 1
  ],
  'install' => [
    'date' => 'Sun, 08 Jul 2018 12:03:15 +0000'
  ],
  'system' => [
    'default' => [
      'dev' => [
        'debug' => [
          'debug_logging' => '0'
        ]
      ]
    ]
  ]
];
