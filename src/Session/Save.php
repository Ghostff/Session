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


namespace Session;

class Save
{
    private $config = [];

    private function ip(): string
    {
        return $_SERVER['HTTP_CLIENT_IP'] ?? ($_SERVER['HTTP_X_FORWARDE‌​D_FOR'] ?? $_SERVER['REMOTE_ADDR']);
    }

    private function browser()
    {
        #you can make this more sophisticated lol
        return $_SERVER['HTTP_USER_AGENT'];
    }

    private function checkSession(): bool
    {

        if ($this->config['match_ip'])
        {
            $ip = $this->ip();
            if (isset($this->config['session']['_validate:ip']))
            {
                if ($this->config['session']['_validate:ip'] != $ip)
                {
                    $this->error('Session IP address mismatch', 1);
                    return false;
                }
            }
            $this->config['session']['_validate:ip'] = $ip;
        }

        if ($this->config['match_browser'])
        {
            $browser = $this->browser();
            if (isset($this->config['session']['_validate:browser']))
            {
                if ($this->config['session']['_validate:browser'] != $browser)
                {
                    $this->error('Session user agent string mismatch', 2);
                    return false;
                }
            }
            $this->config['session']['_validate:browser'] = $browser;
        }
        
        if ($this->config['rotate'] > 0)
        {
            $time = time();
            if (isset($this->config['session']['_validate:time']))
            {
                if (($time - $this->config['session']['_validate:time']) >= $this->config['rotate'])
                {
                    $this->rotate(true);
                    $this->config['session']['_validate:time'] = $time;
                }
            }
            else
            {
                $this->config['session']['_validate:time'] = $time;
            }
        }

        return true;
    }

    /**
     * Calls user provide error handler
     *
     * @param string $error
     * @param int $error_code
     */
    private function error(string $error, int $error_code): void
    {
        if (isset($this->config['error_handler']))
        {
            call_user_func_array($this->config['error_handler'], [$error, $error_code]);
        }
    }

    /**
     * Initializes a session
     */
    private function init()
    {
        
        if (trim($this->config['name']) != '')
        {
            session_name($this->config['name']);
        }

        session_start();

        # store current session ID
        $this->config['sess_id'] = session_id();

        $_ = session_get_cookie_params();
        setcookie($this->config['name'], $this->config['sess_id'], $_['lifetime'], $_['path'], $_['domain'], $_['secure'], $_['httponly']);
        $this->config['session_params'] = $_;

        $this->config['session'] = $_SESSION;
        # Remove the lock from the session file
        session_write_close();

        #destroy session if unique identifier fails to sync
        if ( ! $this->checkSession())
        {
            $this->destroy();
            return;
        }
    }

    /**
     * Starts session
     *
     * Save constructor.
     * @param \stdClass $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->config['last'] = 'set';
        $this->config['segment'] = 'segment:static';
        $this->init();
    }

    /**
     * Adds or update data in active session or segment
     *
     * @param string $name
     * @param $value
     */
    public function __set(string $name, $value)
    {
        $last = $this->config['last'];
        $namespace = $this->config['namespace'];
        $segment = $this->config['segment'];
        $this->config['segment'] = 'segment:static';
        $this->config['last'] = 'set';

        if ($last == 'remove')
        {
            throw new \RuntimeException('you cant use remove to set a value');
        }
        else
        {
            $this->config['session'][$namespace][$segment][$last][$name] = $value;
        }
    }

    /**
     * Retrieves value from active session or segment
     *
     * @param string $name
     * @return $this
     */
    public function __get(string $name)
    {
        if (in_array($name, ['flash', 'remove']))
        {
            $this->config['last'] = $name;
            return $this;
        }
        else
        {
            $last = $this->config['last'];
            $namespace = $this->config['namespace'];
            $segment = $this->config['segment'];
            $this->config['segment'] = 'segment:static';
            $this->config['last'] = 'set';

            $_last = ($last == 'remove') ? 'set' : $last;
            if ( ! isset($this->config['session'][$namespace][$segment][$_last][$name]))
            {
                throw new \RuntimeException($name . ' does not exist. or has been removed');
            }

            if ($last == 'set')
            {
                return $this->config['session'][$namespace][$segment][$last][$name];
            }
            elseif ($last == 'flash')
            {
                $value = $this->config['session'][$namespace][$segment][$last][$name];
                unset($this->config['session'][$namespace][$segment][$last][$name]);
                return $value;
            }
            if ($last == 'remove')
            {
                if (isset($this->config['session'][$namespace][$segment]['set'][$name]))
                {
                    unset($this->config['session'][$namespace][$segment]['set'][$name]);
                }
                return $this;
            }
        }
    }

    /**
     * Retrieves all the data is active session or segment if specified
     *
     * @param string $segment
     * @return array
     */
    public function all(string $segment = ''): array
    {
        $namespace = $this->config['namespace'];
        if ($segment == '')
        {
            return $this->config['session'][$namespace] ?? [];
        }
        else
        {
            $_segment = 'segment:' . $segment;
            if (isset($this->config['session'][$namespace][$_segment]))
            {
                return $this->config['session'][$namespace][$_segment];
            }
            else
            {
                throw new \RuntimeException('segment ' . $segment . 'does not exist.');
            }
        }
    }

    /**
     * Sub categorizes session data
     *
     * @param string $name
     * @return Segment
     */
    public function segment(string $name): Segment
    {
        return new Segment($name, $this->config, $this);
    }

    /**
     * pushes data to session file.
     */
    public function commit()
    {
        session_start();
        $_SESSION = $this->config['session'];
        session_write_close();
    }

    /**
     * Generate a new session identifier
     *
     * @param bool $delete_old
     * @param bool $write_nd_close
     */
    public function rotate(bool $delete_old = true, bool $write_nd_close = true)
    {
        if (headers_sent($filename, $line_num))
        {
            throw new \RuntimeException(sprintf('ID must be regenerated before any output is sent to the browser. (file: %s, line: %s)', $filename, $line_num));
        }

        session_start();
        session_regenerate_id($delete_old);
        $this->config['sess_id'] = session_id();
        if ($write_nd_close)
        {
            session_write_close();
        }
    }

    /**
     * Clears all the data is a specific namespace or segment
     * @param string $segment
     */
    public function clear(string $segment = '')
    {
        $namespace = $this->config['namespace'];
        if ($segment !== '')
        {
            $segment = 'segment:' . $segment;
            if (isset($this->config['session'][$namespace][$segment]))
            {
                unset($this->config['session'][$namespace][$segment]);
            }
            else
            {
                throw new RuntimeException('segment:' . $segment . ' does not exist in current session.');
            }
        }
        else
        {
            if (isset($this->config['session'][$namespace]))
            {
                unset($this->config['session'][$namespace]);
            }
        }

        unset($this->config['session'][$this->config['namespace']]);
        $this->commit();
    }

    /**
     * @param string $name
     * @param bool $in_flash
     * @return bool
     */
    public function exists(string $name, bool $in_flash = false): bool
    {
        $namespace = $this->config['namespace'];
        $segment = $this->config['segment'];
        return isset($this->config['session'][$namespace][$segment][$in_flash ? 'flash' : 'set'][$name]);
    }

    /**
     * Destroy the session data including namespace and segments
     */
    public function destroy()
    {
        @session_start();
        # Empty out session
        $_SESSION = [];
        session_destroy();
        session_write_close();
        $_ = $this->config['session_params'];
        setcookie($this->config['name'], '', -1, $_['path'], $_['domain'], $_['secure'], $_['httponly']);
        # Reset the session data
        $this->config['session'] = [];
    }

}