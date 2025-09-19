<?php declare(strict_types=1);

namespace Ghostff\Session\Drivers;

use Ghostff\Session\Session;
use SessionHandlerInterface;

class Memcached extends SetGet implements SessionHandlerInterface
{
    private \Memcached $conn;
    private string     $name;

    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->name  = $config[Session::CONFIG_START_OPTIONS][Session::CONFIG_START_OPTIONS_NAME];
        $connections = $config[Session::CONFIG_USER_CONNECTION];
        $config      = $config[Session::CONFIG_MEMCACHED_DS];

        ini_set('session.save_handler', 'memcached');
        ini_set('session.save_path', $config['save_path']);

        $this->conn = $connections[Session::CONFIG_MEMCACHED_DS] ?? $this->getConnection($config);

        if (! count($this->conn->getServerList())) {
            $this->conn->addServers($config['servers']);
        }
    }

    protected function getConnection(array $config): \Memcached
    {
        $conn = new \Memcached($config['persistent_id']);
        $conn->setOptions([
            \Memcached::OPT_LIBKETAMA_COMPATIBLE => true,
            \Memcached::OPT_COMPRESSION          => $config['compress'],
        ]);

        return $conn;
    }

    public function open($path, $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function read($id): string
    {
        return $this->get($this->conn->get("{$this->name}{$id}") ?: '');
    }

    public function write($id, $data): bool
    {
        return $this->conn->set("{$this->name}{$id}", $this->get($data), (int) ini_get('session.gc_maxlifetime'));
    }

    public function destroy($id): bool
    {
        return $this->conn->delete("{$this->name}{$id}");
    }

    #[\ReturnTypeWillChange]
    public function gc($max_lifetime)
    {
        return 0;
    }
}