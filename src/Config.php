<?php

return [
    'driver' => 'file',

    'file'  => [
        'salt'              => 'secret_salt_key',   #
        'path'              => '/',                 #
        'rotate'	        => 1800,		        # Rotate every 30 min(60 * 30).
        'domain'            => null,                #
        'http_only'         => true,                #
        'expiration'        => 0,                   #

        'name'		        => '_Bittr_SESSID',	    #
        'match_ip'          => true,                #
        'match_browser'     => true,                #
        'save_path'         => 'Temp/',     	    #
        'cache_limiter'     => 'nocache',           #
        'gc_probability'    => '1',                 #
    ],

    'cookie' => [
        # ...
    ]


];
