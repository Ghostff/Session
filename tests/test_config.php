<?php return [
    'driver'        => Session\File\Handler::class, # Name of session driver to use: Session\[File|MySql|Cookie|Redis|Memcached]\Handler::class
    'encrypt_data'  => false,                       # Allow encryption of session data.
    'salt_key'      => 'test_secret_salt_key',      # Encryption key. ineffective if 'encrypt_data' = false

    # https://www.php.net/manual/en/session.configuration.php
    'start_options' => [
        'name'              => 'TEST_SESS_ID',      # Session name
        'save_path'         => __DIR__ . '/sess',   # This is the path where the files are created.
        'cache_limiter'     => 'private',           # Cache control method used for session pages.
        'cookie_secure'     => '',                  # Specifies whether cookies should only be sent over secure connections.
        'cookie_domain'     => '',                  # Specifies the domain to set in the session cookie.
        'cookie_path'       => '/',                 # Specifies path to set in the session cookie.
        'cookie_lifetime'   => '0',                 # Specifies the lifetime of the cookie in seconds which is sent to the browser. The value 0 means "until the browser is closed."
        'gc_maxlifetime'    => '1440',              # Specifies the number of seconds after which data will be seen as 'garbage' and potentially cleaned up.
        'use_strict_mode'   => '1',                 # Specifies whether the module will use strict session id mode.
        'gc_probability'    => '1',                 #
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
