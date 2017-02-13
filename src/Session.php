<?php

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

    public static function start(string $name = null)
    {
        #check if session name works
        if ((trim($name) != false) && (preg_match('/^[\w ]+$/', $name) < 1))
        {
            throw new InvalidArgumentException('Invalid Session name. (allows [a-z][0-9][space])');
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
            trigger_error("You don't have openssl enabled. So seve handler wont be encrypted.(comment out Session.php line:61 if you cant get openssl enabled and wonna get rid of this error) ", E_USER_NOTICE);
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
                # For GC in Debian
                ini_set('session.gc_probability', '1');
            }
            else
            {
               throw new RuntimeException(sprintf('Path %s does not exist', $save_path));
            }
        }
        return new $_namespace($name, $config);

    }


}