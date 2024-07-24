<?php

declare(strict_types=1);

namespace Ghostff\Session\Drivers;

use Ghostff\Session\Session;
use PDO;
use PDOException;
use RuntimeException;
use SessionHandlerInterface;


class SQLite extends SetGet implements SessionHandlerInterface
{
    private PDO $conn;
    private string $table;

    public function __construct(array $config)
    {
        if (!extension_loaded('pdo_sqlite')) {
            throw new RuntimeException('\'pdo_sqlite\' extension is needed to use this driver.');
        }

        parent::__construct($config);
        $config = $config[Session::CONFIG_SQLITE_DS];
        $dsn = "{$config['driver']}:{$config['db_path']}";
        $this->table = $table = $config['db_table'];

        try {
            $this->conn = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // Enable Write-Ahead Logging (WAL) mode
            $this->conn->exec('PRAGMA journal_mode = WAL');

            $this->conn->query("SELECT 1 FROM `{$table}` LIMIT 1");
        } catch (PDOException $e) {
            // Debug information
            throw new RuntimeException("Error connecting to SQLite database or checking table: " . $e->getMessage());

            $this->conn->query('CREATE TABLE `' . $table . '` (
                `id` TEXT PRIMARY KEY,
                `data` TEXT NOT NULL,
                `time` INTEGER NOT NULL
            )');
        }
    }

    public function open($path, $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read($id): string
    {
        $data = '';
        $statement = $this->conn->prepare("SELECT `data` FROM `{$this->table}` WHERE `id` = :id");
        $statement->bindParam(':id', $id, PDO::PARAM_STR);

        if ($statement->execute()) {
            $result = $statement->fetch();
            $data = $result['data'] ?? '';
        } else {
            // Debug information
            throw new RuntimeException("Failed to execute read statement for ID: {$id}");
        }

        $statement = null; // close
        return $this->get($data);
    }

    public function write($id, $data): bool
    {
        $statement = $this->conn->prepare("REPLACE INTO `{$this->table}` (`id`, `data`, `time`) VALUES (:id, :data, :time)");
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        $statement->bindValue(':data', $this->set($data), PDO::PARAM_STR);
        $statement->bindValue(':time', time(), PDO::PARAM_INT);

        $completed = $statement->execute();
        if (!$completed) {
            // Debug information
            throw new RuntimeException("Failed to execute write statement for ID: {$id}");
        }

        $statement = null; // close
        return $completed;
    }

    public function destroy($id): bool
    {
        $statement = $this->conn->prepare("DELETE FROM `{$this->table}` WHERE `id` = :id");
        $statement->bindParam(':id', $id, PDO::PARAM_STR);

        $completed = $statement->execute();
        if (!$completed) {
            // Debug information
            throw new RuntimeException("Failed to execute destroy statement for ID: {$id}");
        }

        $statement = null; // close
        return $completed;
    }

    #[\ReturnTypeWillChange]
    public function gc(int $max_lifetime): int|false
    {
        $max_lifetime = time() - $max_lifetime;
        $statement = $this->conn->prepare("DELETE FROM `{$this->table}` WHERE `time` < :time");
        $statement->bindParam(':time', $max_lifetime, PDO::PARAM_INT);

        $statement->execute();
        $count = $statement->rowCount();
        $statement = null; // close

        return $count;
    }
}
