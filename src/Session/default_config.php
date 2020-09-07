<?php return [
    'driver'        => Session\File\Handler::class, # Name of session driver to use: [file|pdo|cookie|redis|memcached]
    'encrypt_data'  => false,                       # Allow encryption of session data.
    'salt_key'      => 'secret_salt_key',           # Encryption key. ineffective if 'encrypt_data' = false

    'start_options' => [
        'name'              => 'SESS_ID',           # session name
        'save_path'         => '',
        'cache_limiter'     => 'private',
        'cookie_secure'     => '',
        'cookie_domain'     => '',
        'cookie_path'       => '/',
        'cookie_lifetime'   => '0',
        'gc_maxlifetime'    => '1440',
        'use_strict_mode'   => '1',
        'gc_probability'    => '1',
    ],

    'mysql'         => [
        'driver'    => 'mysql',             # Database driver for PDO dns eg(mysql:host=...;dbname=...)
        'host'      => '127.0.0.1',         # Database host
        'db_name'   => 'five9',             # Database name
        #'db_table' => 'session',           # Database table
        'db_user'   => 'root',              # Database username
        'db_pass'   => '',                  # Database password
        'persistent_conn'=> false,          # Avoid the overhead of establishing a new connection every time a script needs to talk to a database, resulting in a faster web application. FIND THE BACKSIDE YOURSELF
    ],
    'memcached'     => [
        'servers'   => [
            ['127.0.0.1', 11211, 0]
        ],
        'compress'  => true,
        'save_path' => '127.0.0.1:11211',  #comma separated of hostname:port entries to use for session server pool.
        'persistent_conn' => false,
    ],
    'redis'         => [
        'host'      => '127.0.0.1',
        'port'      => 6379,
        'save_path' => 'tcp://127.0.0.1:6379',  #comma separated of hostname:port entries to use for session server pool.
        'persistent_conn' => false,
    ]
];
