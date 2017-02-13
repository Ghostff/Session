<?php
return [
    'driver' => 'file',

    'file'  => [
        'save_path'		=> 'Temp/',             #
        'salt'          => 'secret_salt_key',   #
        'path'          => '/',                 #
        'domain'        => null,                #
        'http_only'     => true,                #
        'expiration'    => 0,                   # at browser close

        'match_ip'      => true,                #
        'match_browser' => true,                #
        'cache_limiter' => 'nocache'            #
    ],

    'cookie' => [
        # ...
    ]


];