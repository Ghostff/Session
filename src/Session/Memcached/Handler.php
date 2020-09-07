<?php

declare(strict_types=1);

namespace Session\Memcached;

use Memcached, Session, SessionHandlerInterface;
use RuntimeException;

class Handler extends Session\SetGet implements SessionHandlerInterface
{
    private $conn;
    private $name;

    public function __construct(array $config)
    {
        if (! extension_loaded('memcached'))
        {
            throw new RuntimeException('The \'memcached\' extension is needed in order to use this session handler');
        }

        parent::__construct($config['encrypt_data'], $config['salt_key']);

        $this->name     = $config['name'];
        $config         = $config['memcached'];

        ini_set('session.save_handler', 'memcached');
        ini_set('session.save_path', $config['save_path']);

        $conn = new Memcached($config['persistent_conn'] ? $config['name'] : null);
        $conn->setOptions([Memcached::OPT_LIBKETAMA_COMPATIBLE => true, Memcached::OPT_COMPRESSION => $config['compress']]);

        if (! count($conn->getServerList()))
        {
            $conn->addServers($config['servers']);
        }

        $this->conn = $conn;
        $conn = null;
    }

    public function open($savePath, $sessionName): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read($id): string
    {
        $data = $this->conn->get("{$this->name}{$id}");
        return ($data == '' || $data == false) ? '' : $this->get($data);
    }

    public function write($id, $data): bool
    {
        return $this->conn->set("{$this->name}{$id}", $this->get($data), (int) ini_get('session.gc_maxlifetime'));
    }

    public function destroy($id): bool
    {
        return $this->conn->delete("{$this->name}{$id}");
    }

    public function gc($max_life_time): bool
    {
        return true;
    }
}
