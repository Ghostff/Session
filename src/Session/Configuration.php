<?php

declare(strict_types=1);

namespace Session;

class Configuration
{
    /** @var \Session\Configuration  */
    private static $instance = null;
    /** @var array  */
    private $config;

    protected function __construct(array $config)
    {
        $this->config = $config;
    }

    public static function loadDefaultConfiguration(string $config_path = __DIR__ . '/default_config.php')
    {
        self::$instance = new self(include($config_path));
    }

    public static function getConfigurations(): Configuration
    {
        if (self::$instance == null)
        {
            self::loadDefaultConfiguration();
        }

        return self::$instance;
    }

    public static function set(array $key_value): Configuration
    {
        $instance         = self::getConfigurations();
        $instance->config = $key_value + $instance->config;

        return $instance;
    }

    public function check(): array
    {
        $driver = $this->config['driver'];
        session_set_save_handler(new $driver($this->config), false);

        return $this->config['start_options'];
    }
}
