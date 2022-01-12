<?php declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Session\Configuration;

class TestConfiguration extends TestCase
{
    public function test_that_config_check_only_returns_only_start_options()
    {
        $all_config = include __DIR__ . '/../src/Session/default_config.php';
        $config     = Configuration::getConfigurations()->check();

        $this->assertSame($config, $all_config['start_options']);
    }

    public function test_that_configuration_file_can_be_set()
    {
        Configuration::loadDefaultConfiguration(__DIR__ . '/test_config.php');
        $config = Configuration::getConfigurations();

        $property = new ReflectionProperty(Configuration::class, 'config');
        $property->setAccessible(true);

        $this->assertSame(include(__DIR__ . '/test_config.php'), $property->getValue($config));
    }


    public function test_that_config_property_can_be_modified()
    {
        $private_key = '3c6e0b8a9c15224a8228b9a98ca1531d';
        $config      = Configuration::set(['salt_key' => $private_key]);

        $property = new ReflectionProperty(Configuration::class, 'config');
        $property->setAccessible(true);

        $this->assertSame($property->getValue($config)['salt_key'], $private_key);
    }
}