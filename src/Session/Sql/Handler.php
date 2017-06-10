<?php

/**
 * Bittr
 *
 * @license
 *
 * New BSD License
 *
 * Copyright (c) 2017, ghostff community
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *      1. Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *      2. Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *      3. All advertising materials mentioning features or use of this software
 *      must display the following acknowledgement:
 *      This product includes software developed by the ghostff.
 *      4. Neither the name of the ghostff nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY ghostff ''AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL GHOSTFF COMMUNITY BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */


declare(strict_types=1);

namespace Session\Sql;
use PDO, Session;


class Handler implements \SessionHandlerInterface
{

    private $conn = null;

    private $table = null;
    
    private $persistent = false;
    

    public function __construct(array $config)
    {
        if ( ! isset($config['sql']))
        {
            throw new \RuntimeException('No sql configuration found in config file.');
        }

        $config = $config['sql'];
        $table = $config['table'] ?? 'session';
        $this->table = $table;
        $this->persistent = $config['persistent_conn'];
        
        $dsn = $config['driver'] . ':host=' . $config['host'] . ';dbname=' . $config['db_name'];

        $this->conn = new PDO($dsn,  $config['db_user'], $config['db_pass'], [
            PDO::ATTR_PERSISTENT => $this->persistent,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        try
        {
            $this->conn->query('SELECT 1 FROM `' . $table . '` LIMIT 1');
        }
        catch (\PDOException  $e)
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
        $data = '';
        $statement = $this->conn->prepare('SELECT `data` FROM `' . $this->table . '` WHERE `id` = :id');
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        if ($statement->execute())
        {
            $result = $statement->fetch();
            $data = $result['data'] ?? '';
        }
        #close
        $statement = null;
        return ($data == '') ? '' : Session::decrypt($data);
    }

    public function write($id, $data): bool
    {
        if ( ! Session::$write)
        {
            return true;
        }
        
        $statement = $this->conn->prepare('REPLACE INTO `' . $this->table . '` (`id`, `data`, `time`) VALUES (:id, :data, :time)');
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        $statement->bindValue(':data', Session::encrypt($data), PDO::PARAM_STR);
        $statement->bindValue(':time', time(), PDO::PARAM_INT);
        $completed = $statement->execute();
        #close
        $statement = null;
        Session::$write = false;
        return $completed;
    }

    public function destroy($id): bool
    {
        $statement = $this->conn->prepare('DELETE FROM `' . $this->table . '` WHERE `id` = :id');
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        $completed = $statement->execute();
        #close
        $statement = null;
        return $completed;
    }

    public function gc($max_life_time): bool
    {
        $max_life_time = time() - $max_life_time;
        $statement = $this->conn->prepare('DELETE FROM `' . $this->table . '` WHERE `time` < :time');
        $statement->bindParam(':time', $max_life_time, PDO::PARAM_INT);
        $completed = $statement->execute();
        #close
        $statement = null;
        return $completed;
    }
}