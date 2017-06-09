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
use Session;


class Handler implements \SessionHandlerInterface
{
    private $config = [];

    private $conn = null;

    private $table = null;

    public function __construct(array $config)
    {
        if ( ! isset($config['memcached']))
        {
            throw new \RuntimeException('No memcached configuration found in config file.');
        }

        $config = $config['memcached'];
        $this->conn = new Memcache;
        $this->conn->connect($config['host'], $config['port'], $config['timeout']);
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
        return Session::decrypt($this->conn->get($id));
    }

    public function write($id, $data): bool
    {
        return $this->conn->set($id, Session::encrypt($data), MEMCACHE_COMPRESSED);
    }

    public function destroy($id): bool
    {
        return $this->conn->delete($id);
    }

    public function gc($max_life_time): bool
    {
    }
}