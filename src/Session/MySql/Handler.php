<?php

declare(strict_types=1);

namespace Session\MySql;

use PDO, PDOException, Session, SessionHandlerInterface;

class Handler extends Session\SetGet implements SessionHandlerInterface
{
    private $conn;
    private $table;
    private $persistent;

    public function __construct(array $config)
    {
        parent::__construct($config['encrypt_data'], $config['salt_key']);

        $config           = $config['mysql'];
        $table            = $config['table'] ?? 'session';
        $this->table      = $table;
        $this->persistent = $config['persistent_conn'];
        $dsn              = "{$config['driver']}:host={$config['host']};dbname={$config['db_name']}";
        $this->conn       = new PDO($dsn, $config['db_user'], $config['db_pass'], [
            PDO::ATTR_PERSISTENT => $this->persistent,
            PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION
        ]);

        try
        {
            $this->conn->query("SELECT 1 FROM `{$table}` LIMIT 1");
        }
        catch (PDOException $e)
        {
            $this->conn->query('CREATE TABLE `' . $table . '` (
              `id` varchar(250) NOT NULL,
              `data` text NOT NULL,
              `time` int(11) unsigned NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;');
        }
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
        $data      = '';
        $statement = $this->conn->prepare("SELECT `data` FROM `{$this->table}` WHERE `id` = :id");
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        if ($statement->execute())
        {
            $result = $statement->fetch();
            $data   = $result['data'] ?? '';
        }
        #close
        $statement = null;
        return ($data == '') ? '' : $this->get($data);
    }

    public function write($id, $data): bool
    {
        $statement = $this->conn->prepare("REPLACE INTO `{$this->table}` (`id`, `data`, `time`) VALUES (:id, :data, :time)");
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        $statement->bindValue(':data', $this->set($data), PDO::PARAM_STR);
        $statement->bindValue(':time', time(), PDO::PARAM_INT);
        $completed = $statement->execute();
        #close
        $statement = null;

        return $completed;
    }

    public function destroy($id): bool
    {
        $statement = $this->conn->prepare("DELETE FROM `{$this->table}` WHERE `id` = :id");
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        $completed = $statement->execute();
        #close
        $statement = null;

        return $completed;
    }

    public function gc($max_life_time): bool
    {
        $max_life_time = time() - $max_life_time;
        $statement = $this->conn->prepare("DELETE FROM `{$this->table}` WHERE `time` < :time");
        $statement->bindParam(':time', $max_life_time, PDO::PARAM_INT);
        $completed = $statement->execute();
        #close
        $statement = null;

        return $completed;
    }
}
