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


namespace Session\File;
use Session;


class Handler implements \SessionHandlerInterface
{

    private $savePath;

    public function open($savePath, $sessionName): bool
    {
        $this->savePath = $savePath;
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read($id): string
    {
        $data = (string) @file_get_contents($this->savePath . '/sess_' . $id);
        return ($data == '') ? '' : Session::decrypt($data);
    }

    public function write($id, $data): bool
    {
        if (! Session::$write)
        {
            return true;
        }
        Session::$write = false;
        return (file_put_contents($this->savePath . '/sess_' . $id, Session::encrypt($data)) !== false);
    }

    public function destroy($id): bool
    {
        $file = $this->savePath . '/sess_' . $id;
        if (file_exists($file))
        {
            unlink($file);
        }

        return true;
    }

    public function gc($max_life_time): bool
    {
        $time = time();
        foreach (glob($this->savePath . '/sess_*') as $file)
        {
            if (filemtime($file) + $max_life_time < $time && file_exists($file))
            {
                unlink($file);
            }
        }

        return true;
    }
}