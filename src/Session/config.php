<?php

/**
 * Bittr
 *
 * @license
 *
 * New BSD License
 *
 * Copyright (c) 2017, ghostff community
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *      1. Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *      2. Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *      3. All advertising materials mentioning features or use of this software
 *      must display the following acknowledgement:
 *      This product includes software developed by the ghostff.
 *      4. Neither the name of the ghostff nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY ghostff ''AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL GHOSTFF COMMUNITY BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

return [

    'driver'        => 'file',              # Name of session driver to use: [file|sql|cookie|redis|memcache]
    
    'encrypt_data'  => false,               # Allow encryption of session data.
    'key'           => 'secret_salt_key',   # Encryption key. ineffective is 'encrypt_data' = false

    #referre to: http://php.net/manual/en/function.setcookie.php
    'path'          => '/',                 # Change if you want the cookie to be only valid to a certain path. default is global
    'rotate'        => 0,                   # Regenerates id every 0 = (never unless explicitly called with rotate).
    'domain'        => null,                # The domain for which the session cookies are valid
    'http_only'     => true,                # Allow all cookie transaction over HTTP. prevents Javascript cookie modification.
    'secure'        => null,                # If set to true, cookies are transmitted over HTTPS. null = auto dictate
    'expiration'    => 0,                   # Number of seconds of after which the session will expire. 0 = after browser closes
    
    'name'          => '_Bittr_SESSID',     # session name
    'match_ip'      => false,               # If set to true, IP address will be stored and validated on each I/O.
    'match_browser' => false,               # If set to true, browser will be stored and validated on each I/O.
    'save_path'     => __DIR__ . '/Tmp',    # Path where your session files will be store. Ineffective if driver is not file
    'cache_limiter' => 'none',              # http://php.net/manual/en/function.session-cache-limiter.php
    
    'sql'           => [
        'driver'    => 'mysql',             # Database driver
        'host'      => '127.0.0.1',         # Database host
        'db_name'   => 'session',           # Database name
        #'db_table' => 'session',           # Database table
        'db_user'   => 'root',              # Database username
        'db_pass'   => '',                  # Database password
        'persistent_conn'=> false,           # Avoid the overhead of establishing a new connection every time a script needs to talk to a database, resulting in a faster web application. . FIND THE BACKSIDE YOURSELF
    ]


];
