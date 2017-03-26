<?php

return [
    'driver' => 'file', # session save handler

    'file'  => [
        'salt'              => 'secret_salt_key',   # session data encryption salt. set this to null will store session data unencrypted
        'path'              => '/',                 # on the domain where the cookie will work. Use a single slash ('/') for all paths on the domain
        'rotate'	        => 1800,		        # change session id every 30 min(60 * 30).
        'domain'            => '',                  # Cookie domain. (http://php.net/manual/en/function.session-set-cookie-params.php)
        'http_only'         => true,                # If set to TRUE then PHP will attempt to send the httponly flag when setting the session cookie.
        'secure'            => null,                # If true cookie will only be sent over secure(https) connections. set to null for auto dictate
        'expiration'        => 0,                   # of idle time after which the session will expire. 0 expires on browser shutdown

        'name'		        => '_Bittr_SESSID',	    # Default session name
        'match_ip'          => false,               # If true, the IP address will be stored and gets validated on each request (not recommended)
        'match_browser'     => false,               # If true, users browsers information  will be stored and validated on each request (not recommended)
        'save_path'         => '/tmp',     	        # Directory were session data's will be said. set to null to use PHP default
        'cache_limiter'     => 'nocache',           # (http://php.net/manual/en/function.session-cache-limiter.php)
        'gc_probability'    => '1',                 # (http://php.net/manual/en/session.configuration.php#ini.session.gc-probability)
    ],

    'cookie' => [
        # ...
    ]
];
