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

namespace Session\Memcached;

use Memcached, RuntimeException, Session, SessionHandlerInterface;

class Handler implements SessionHandlerInterface
{
    private $conn = null;
    private $expire = 0;
    private $name = null;

    public function __construct(array $config)
    {
        if (! isset($config['memcached']))
        {
            throw new RuntimeException('No memcached configuration found in config file.');
        }

        $this->expire = $config['expiration'];
        $this->name = $config['name'];
        $config = $config['memcached'];

        $conn = new Memcached(($config['persistent_conn']) ? $config['name'] : null);
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
        $data = $this->conn->get($this->name . $id);
        return ($data == '' || $data == false) ? '' : Session::decrypt($data);
    }

    public function write($id, $data): bool
    {
        if (! Session::$write)
        {
            return true;
        }
        Session::$write = false;
        return $this->conn->set($this->name . $id, Session::encrypt($data), $this->expire);
    }

    public function destroy($id): bool
    {
        return $this->conn->delete($this->name . $id);
    }

    public function gc($max_life_time): bool
    {
        return true;
    }
}
