<?php
/**
 * Bittr
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2017, Hoa community. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

declare(strict_types=1);

class Session
{
    public const DS = DIRECTORY_SEPARATOR;

    private static $config = [];

    public static $config_path = null;

    public static function loadConfig(): array
    {
        if (empty(static::$config))
        {
            # check if users specified a custom path else load default config
            static::$config = include(static::$config_path ?? __DIR__ . self::DS . 'Config.php');
        }
        return static::$config;
    }

    public static function start(string $name = '__btr')
    {
        #check if session name works
        if ((trim($name) != false) && (preg_match('/^[\w]+$/', $name) < 1))
        {
            throw new InvalidArgumentException('Invalid Session namespace. (allows alphanumrics and underscors)');
        }

        $config = static::loadConfig();
        if (isset($config['driver']))
        {
            $diver = $config['driver'];
            $handler_path = __DIR__ . self::DS . 'Handlers' . self::DS . ucfirst($diver);
            if (is_dir($handler_path))
            {
                if (isset($config[$diver]))
                {
                    $config[$diver]['driver'] = $diver;
                    $config = (object) $config[$diver];
                }
                else
                {
                    throw new RuntimeException(sprintf('%s does not exist in Config.php index', $diver));
                }
            }
            else
            {
                throw new RuntimeException(sprintf('No session driver for %s found', $diver));
            }
        }
        else
        {
            throw new RuntimeException('driver does not exist in Config.php index');
        }

        #make save handler config dependent
        ini_set('session.save_handler', ($config->driver == 'file') ? 'files' : $config->driver);
        # check if openssl is enabled since SessionHandler class uses openssl to encrypt and decrypt session content
        if ( ! extension_loaded('openssl'))
        {
            trigger_error("You don't have openssl enabled. So seve handler wont be encrypted.(comment out Session.php line:61 if you cant get openssl enabled and wanna get rid of this error)", E_USER_NOTICE);
        }
        else
        {
            $namespace = sprintf('Handlers\%s\SessionHandler', ucfirst($config->driver));
            session_set_save_handler(new $namespace($config->salt), true);
        }

        $_namespace = sprintf('Handlers\%1$s\%1$sSession', ucfirst($config->driver));

        if (($config->save_path) != false)
        {
            #check if user has a custom save path for session cookies
            $save_path = ($config->save_path == 'Temp/') ? __DIR__ . self::DS . 'Temp' . self::DS : $config->save_path;
            if (is_dir($save_path))
            {
                session_save_path($save_path);
                ini_set('session.gc_probability', $config->gc_probability);
            }
            else
            {
               throw new RuntimeException(sprintf('Path %s does not exist', $save_path));
            }
        }
        return new $_namespace($name, $config);

    }


}