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


declare(strict_types=1);


class Session
{
    private static $initialized = [];

    private static $started = false;

    private static $class = null;

    private static $ssl_enabled = true;
    
    public static $write = false;
    
    public static $id = '';

    private static function init()
    {
        $DS = DIRECTORY_SEPARATOR;
        $path = __DIR__ . $DS . 'Session' . $DS;
        $config = include($path . 'config.php');

        self::$initialized = $config;
        $driver = $config['driver'];
        self::$class = ucfirst($driver);

        if( ! is_dir($path . self::$class))
        {
            throw new RuntimeException('No driver found for ' . self::$class);
        }

        self::$ssl_enabled = self::$initialized['encrypt_data'];
        if (self::$ssl_enabled && ! extension_loaded('openssl'))
        {
            throw new \RuntimeException('The openssl extension is missing. Please check your PHP configuration.');
        }

        
        if (self::$class != 'File' && self::$class != 'Cookie')
        {
            if ( ! extension_loaded(self::$class))
            {
                throw new \RuntimeException('The ' . self::$class . ' extension is missing. Please check your PHP configuration.');
            }
        }

        session_cache_limiter($config['cache_limiter']);
        $secured = $config['secure'];
        if ($secured !== true && $secured !== false && $secured !== null)
        {
            throw new RuntimeException('config.secure expected value to be a boolean or null');
        }
        
        if ($secured == null)
        {
            $secured = ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS'] == 'on'));
        }


        ini_set('session.save_handler', ($driver == 'file') ? 'files' : (($driver == 'memcached' || $driver == 'redis') ? $driver : 'user'));
        ini_set('session.use_cookies', '1');
        ini_set('session.gc_maxlifetime', $config['max_life_time']);
        ini_set('session.gc_probability', $config['probability']);
        $current = $config['expiration'];
        $config['expiration'] = ($current == 0) ? 0 : time() + $current;
        session_set_cookie_params($config['expiration'], $config['path'], $config['domain'], $secured, $config['http_only']);

        if (self::$class == 'File')
        {
            $save_path = $config[$driver]['save_path'];
            if (is_dir($save_path))
            {
                session_save_path($save_path);
            }
            else
            {
                throw new RuntimeException(sprintf('save_path (%s) does not exist', $save_path));
            }
        }
        elseif ( self::$class == 'Memcached' || self::$class == 'Redis')
        {
             session_save_path($config[$driver]['save_path']);
        }
        
        $class = '\Session\\' . self::$class . '\Handler';
        session_set_save_handler(new $class(self::$initialized), true);

    }

    /**
     * Sets new session id
     *
     * @param string $id
     */
    public static function id(string $id = ''): string
    {
        if (empty(self::$initialized))
        {
            self::init();
        }

        if ($id != '')
        {
            if (self::$started)
            {
                throw new \RuntimeException('Session is active. The session id must be set before Session::start().');
            }
            elseif (headers_sent($filename, $line_num))
            {
                throw new \RuntimeException(sprintf('ID must be set before any output is sent to the browser (file: %s, line: %s)', $filename, $line_num));
            }
            elseif (preg_match('/^[-,a-zA-Z0-9]{1,128}$/', $id) < 1)
            {
                throw new \InvalidArgumentException('Invalid Session ID.');
            }
            else
            {
                session_id($id);
            }
        }
        
        return self::$id;
    }


    /**
     * starts a new session
     *
     * @param string $namespace
     * @return \Session\Save
     */
    public static function start(string $namespace = '__GLOBAL'): \Session\Save
    {
        if (empty(self::$initialized))
        {
            self::init();
        }

        self::$started = true;
        self::$initialized['namespace'] = $namespace;
        return new \Session\Save(self::$initialized);
    }

    /**
     * Reset all session configuration settings.
     */
    public static function reset(): self
    {
        self::$initialized = [];
        return new self;
    }

    /**
     * Allows error custom error handling
     *
     * @param callable $error_handler
     */
    public static function registerErrorHandler(callable $error_handler)
    {
        if (empty(self::$initialized))
        {
            self::init();
        }

        self::$initialized['error_handler'] = $error_handler;
    }

    /**
     * decrypt AES 256
     *
     * @param string $data
     * @return string data
     */
    public static function decrypt(string $data): string
    {
        if ( ! self::$initialized['encrypt_data'] || ! self::$ssl_enabled)
        {
            return $data;
        }

        $password = self::$initialized['key'];
        $data = base64_decode($data);
        $salt = substr($data, 0, 16);
        $ct = substr($data, 16);

        $rounds = 3; // depends on key length
        $data00 = $password . $salt;
        $hash = [];
        $hash[0] = hash('sha256', $data00, true);
        $result = $hash[0];
        for ($i = 1; $i < $rounds; $i++)
        {
            $hash[$i] = hash('sha256', $hash[$i - 1] . $data00, true);
            $result .= $hash[$i];
        }
        $key = substr($result, 0, 32);
        $iv  = substr($result, 32,16);
        $decrypted = openssl_decrypt($ct, 'AES-256-CBC', $key, 1, $iv);

        return ( ! $decrypted) ? '' : $decrypted;
    }

    /**
     * crypt AES 256
     *
     * @param string $data
     * @return string encrypted data
     */
    public static function encrypt(string $data): string
    {
        if ( ! self::$initialized['encrypt_data'] || ! self::$ssl_enabled)
        {
            return $data;
        }

        $password = self::$initialized['key'];
        // Set a random salt
        $salt = openssl_random_pseudo_bytes(16);
        $salted = '';
        $dx = '';
        // Salt the key(32) and iv(16) = 48
        while (strlen($salted) < 48)
        {
            $dx = hash('sha256', $dx . $password . $salt, true);
            $salted .= $dx;
        }

        $key = substr($salted, 0, 32);
        $iv  = substr($salted, 32,16);

        $encrypted_data = openssl_encrypt($data, 'AES-256-CBC', $key, 1, $iv);
        return base64_encode($salt . $encrypted_data);
    }
}