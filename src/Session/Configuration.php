<?php

declare(strict_types=1);

namespace Session;

class Configuration
{
    private static ?Configuration $instance = null;
    private array                 $config;

    protected function __construct(array $config)
    {
        $this->config = $config;
    }

    public static function loadDefaultConfiguration(string $config_path = __DIR__ . '/default_config.php'): void
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
        $instance->config = $instance->arrayMergeRecursiveDistinct($instance->config, $key_value);

        return $instance;
    }

    /**
     * Merges arrays recursively returns distinct values.
     *
     * @param array $array1
     * @param array $array2
     *
     * @return array
     */
    private function arrayMergeRecursiveDistinct(array &$array1, array &$array2): array
    {
        $merged = $array1;
        foreach ($array2 as $key => &$value)
        {
            if (\is_array($value) && isset($merged[$key]) && \is_array($merged[$key])) {
                $merged[$key] = $this->arrayMergeRecursiveDistinct($merged[$key], $value);
            } else {
                if (\is_numeric($key)) {
                    if (! \in_array($value, $merged)) {
                        $merged[] = $value;
                    }
                } else {
                    $merged[$key] = $value;
                }
            }
        }

        return $merged;
    }

    public function check(): array
    {
        $driver = $this->config['driver'];
        session_set_save_handler(new $driver($this->config), false);

        return $this->config['start_options'];
    }
}
