<?php

declare(strict_types=1);

namespace Session\Redis;

use Redis, Session, SessionHandlerInterface;
use RuntimeException;

class Handler extends Session\SetGet implements SessionHandlerInterface
{
    private $conn;
    private $name;

    public function __construct(array $config)
    {
        if (! extension_loaded('redis'))
        {
            throw new RuntimeException('The \'redis\' extension is needed in order to use this session handler');
        }

        parent::__construct($config['encrypt_data'], $config['salt_key']);

        $this->name = $config['name'];
        $config     = $config['redis'];

        ini_set('session.save_handler', 'memcached');
        ini_set('session.save_path', $config['save_path']);
        $conn = new Redis();

        if ($config['persistent_conn'])
        {
            $conn->pconnect($config['host'], $config['port'], $config['port'] ?? 2.5, $this->name);
        }
        else
        {
            $conn->connect($config['host'], $config['port']);
        }

        $this->conn = $conn;
        $conn       = null;
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
        return $this->conn->setEx("{$this->name}{$id}", (int) ini_get('session.gc_maxlifetime'), $this->set($data));
    }

    public function destroy($id): bool
    {
        return $this->conn->del("{$this->name}{$id}") > 0;
    }

    public function gc($max_life_time): bool
    {
        return true;
    }
}
