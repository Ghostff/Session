<?php
/**
 * Bittr
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2017, Hoa community. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

declare(strict_types=1);

namespace Handlers\File;

class FileSession implements \Handlers\SessionInterface
{

    /**
     * Active segment
     * @var null|string
     */
    public $segmented = null;

    /**
     * Current namespace
     * @var null|string
     */
    private $name = null;

    /**
     * Configuration buffer
     * @var null|\stdClass
     */
    private $config = null;

    /**
     * Active session identifier
     * @var string
     */
    private $sess_id = '';

    /**
     * Error handler referencer
     * @var null
     */
    private $error_handler = null;

    /**
     * Session data
     * @var array
     */
    private $session = [];

    /**
     * Session has been initialized
     * @var bool
     */
    private $initialized = false;

    /**
     * Last get was a flash
     * @var bool
     */
    private $flashed = false;

    /**
     * Last get was a remove
     * @var bool
     */
    private $remove = false;

    /**
     * FileSession constructor.
     *
     * @param string|null $name namespace
     * @param \stdClass $config session configurations
     */
    public function __construct(string $name = null, \stdClass $config)
    {
        $this->name = $name;
        $this->config = $config;
        $this->segmented = '__\raw';
        $this->init();
    }

    /**
     * Get the domain name
     *
     * @return string
     */
    private function getDomain(): string
    {
        $sp = 'SERVER_PORT';
        $sn = 'SERVER_NAME';
        $domain = (($_SERVER[$sp] != '80') && ($_SERVER[$sp] != '443')) ? $_SERVER[$sn] . ':' . $_SERVER[$sp] : $_SERVER[$sn];
        return '.' . str_replace('www.', '', $domain);
    }

    /**
     * Validates session data static data
     * @return bool
     */
    private function checkSession(): bool
    {
        if ($this->sess_id != '')
        {
            # Check if session is ruining ip it was initialized with
            if ($this->config->match_ip)
            {
                $type = 'REMOTE_ADDR';
                if (isset($_SESSION['__\prefab']['ip']) && ($_SESSION['__\prefab']['ip'] != $_SERVER[$type]))
                {
                    $this->newError('Session IP address mismatch', 1);
                    return false;
                }
                $_SESSION['__\prefab']['ip'] = $_SERVER[$type];
            }

            # Check if session is ruining at the browser it was initialized from
            if ($this->config->match_browser)
            {
                $type = 'HTTP_USER_AGENT';
                if (isset($_SESSION['__\prefab']['browser']) && ($_SESSION['__\prefab']['browser'] != $_SERVER[$type]))
                {
                    $this->newError('Session user agent string mismatch', 2);
                    return false;
                }
                $_SESSION['__\prefab']['browser'] = $_SERVER[$type];
            }

            # Check if session has expired but still being used
            if (($this->config->expiration !== 0) && ($this->config->expiration < time()))
            {
                $this->newError('Using expired session', 3);
                return false;
            }

            if (($this->config->rotate !== 0))
            {
                if ( ! isset($_SESSION['__\prefab']['rotate']))
                {
                    $_SESSION['__\prefab']['rotate'] = $this->config->rotate + time();
                }
                else
                {
                    if (time() >= $_SESSION['__\prefab']['rotate'])
                    {
                        $this->rotate(true, false);
                        # Restart rotate time
                        $_SESSION['__\prefab']['rotate'] = $this->config->rotate + time();
                    }
                }
            }
        }
        return true;
    }

    /**
     * Initializes a session
     */
    private function init(): void
    {
        #destroy session if unique identifier fails to sync
        if ( ! $this->checkSession())
        {
            $this->destroy();
            return;
        }

        if ( ! $this->initialized)
        {
            $this->initialized = true;
            session_cache_limiter($this->config->cache_limiter);

            $secured = ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS'] == 'on'));

            $expire =  ($this->config->expiration == 0) ? 0 : time() + $this->config->expiration;
            session_set_cookie_params($expire, $this->config->path, $this->getDomain(), $secured, $this->config->http_only);

            if (isset($this->config->name))
            {
                session_name($this->config->name);
            }
            session_start();
            setcookie(session_name(), session_id(), $expire, $this->config->path, $this->getDomain(), $secured, $this->config->http_only);
            # store current session ID
            $this->sess_id = session_id();
            $this->checkSession();
            $this->session = $_SESSION;
            # Remove the lock from the session file
            session_write_close();
        }
    }

    /**
     * Sets session data depending on namespace and segment
     *
     * @param string $type
     * @param string $name
     * @param $value
     */
    private function sessionType(string $type, string $name, $value): void
    {
        $this->session[$this->name][$this->segmented][$type][$name] = $value;
    }

    /**
     * pushes data to session file.
     */
    public function commit(): void
    {
        session_start();
        $_SESSION = $this->session;
        session_write_close();
        $this->segmented = '__\raw';
    }

    /**
     * Calls user provide error handler
     *
     * @param string $error
     * @param string $error_code
     */
    private function newError(string $error, string $error_code): void
    {
        call_user_func_array($this->error_handler, [$error, $error_code]);
    }

    /**
     * Generate a new session identifier
     *
     * @param bool $delete_old
     * @param bool $write_nd_close
     */
    public function rotate(bool $delete_old = false, bool $write_nd_close = true): void
    {
        if (headers_sent($filename, $line_num))
        {
            throw new \RuntimeException(
                sprintf('ID must be regenerated before any output is sent to the browser. (file: %s, line: %s)', $filename, $line_num)
            );
        }

        if (session_status() !== PHP_SESSION_ACTIVE)
        {
            @session_start();
        }
        session_regenerate_id($delete_old);
        $this->sess_id = session_id();
        if ($write_nd_close)
        {
            session_write_close();
        }
    }

    /**
     * @param string $name
     * @param bool $is_static
     * @return bool
     */
    public function exists(string $name, bool $is_static = true): bool
    {
        if ($is_static)
        {
            return isset($this->session[$this->name][$this->segmented]['static'][$name]);
        }
        else
        {
            return isset($this->session[$this->name][$this->segmented]['flash'][$name]);
        }
    }

    /**
     * Sets or retrieve session name
     *
     * @param string|null $name
     * @return string
     */
    public function name(string $name = null): string
    {
        if ( ! is_null($name))
        {
            if ( $this->sess_id != '' || (session_status() === PHP_SESSION_ACTIVE))
            {
                throw new \RuntimeException('Session is active. The session id must be set right after Session::start().');
            }
            elseif (preg_match('/^[a-zA-Z]([\w]*)$/', $name) < 1)
            {
                throw new \InvalidArgumentException('
                    Invalid Session name. (allows [\w] and can\'t consist of numbers only. must have a letter)
                ');
            }
            $this->config->name = $name;
            return '';
        }
        return $this->config->name;
    }

    /**
     * Adds a new item to the current session namespace or segment
     *
     * @param string $name
     * @param $value
     */
    public function __set(string $name, $value): void
    {
        if (in_array($name, ['flash', 'remove']))
        {
            throw new \RuntimeException($name .' is a reserved word amd cant\'t be used to declare as session variable');
        }

        $type = 'static';
        if ($this->flashed)
        {
            $type = 'flash';
            $this->flashed = false;
        }
        $this->sessionType($type, $name, $value);
    }

    /**
     * Gets an item from current session namespace of segment
     *
     * @param string $name
     * @return $this|null
     */
    public function __get(string $name)
    {
        $type = 'static';
        if ($name == 'flash')
        {
            $this->flashed = true;
            return $this;
        }
        elseif ($name == 'remove')
        {
            $this->remove = true;
            return $this;
        }

        if ($this->flashed)
        {
            $type = 'flash';
        }

        $this->init();
        # buffer session
        $session = $this->session[$this->name][$this->segmented][$type][$name] ?? null;
        # Remove flash from session
        if ($this->flashed || $this->remove)
        {
            $this->flashed = false;
            $this->remove = false;

            if (session_status() !== PHP_SESSION_ACTIVE)
            {
                @session_start();
            }
            unset($_SESSION[$this->name][$this->segmented][$type][$name]);
            $this->session = $_SESSION;
            session_write_close();
        }
        $this->segmented = '__\raw';
        return $session;
    }

    /**
     * Sub categorize session datas
     *
     * @param string $name
     * @return \Handlers\Segment
     */
    public function segment(string $name): \Handlers\Segment
    {
        return new \Handlers\Segment($name, $this);
    }

    /**
     * Clears all the data is a specific namespace
     */
    public function clear(): void
    {
        #TODO: Remove args from session clear doc
        if (session_status() !== PHP_SESSION_ACTIVE)
        {
            @session_start();
        }
        unset($_SESSION[$this->name]);
        $this->session = $_SESSION;
        session_write_close();
    }

    /**
     * Destroy the session data including namespace and segments
     */
    public function destroy(): void
    {
        @session_start();
        # Empty out session
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        session_destroy();

        # Reset the session data
        $this->initialized = false;
        $this->session = [];
    }

    /**
     * Get or set current session ID
     *
     * @param string|null $new_id
     * @return string
     */
    public function id(string $new_id = null): string
    {
        if ( ! is_null($new_id))
        {
            if ( $this->sess_id != '' || (session_status() === PHP_SESSION_ACTIVE))
            {
                throw new \RuntimeException('Session is active. The session id must be set right after Session::start().');
            }
            elseif (headers_sent($filename, $line_num))
            {
                throw new \RuntimeException(
                    sprintf('ID must be set before any output is sent to the browser (file: %s, line: %s)', $filename, $line_num)
                );
            }
            elseif (preg_match('/^[\w-,]{1,128}$/', $new_id) < 1)
            {
                throw new \InvalidArgumentException('Invalid Session ID provide');
            }
            else
            {
                session_id($new_id);
            }
            return '';
        }
        return $this->sess_id;
    }

    /**
     * Get all session data including namespace and segments
     *
     * @param string|null $name
     * @return array
     */
    public function all(string $name = null): array
    {
        if ( ! is_null($name))
        {
            if (isset($this->session[$name]))
            {
                throw new \RuntimeException(sprintf('%s does not exists in current session namespace', $name));
            }
            return $this->session[$name];
        }
        return $this->session;
    }

    /**
     * Allows error custom error handling
     *
     * @param callable $error_handler
     */
    public function registerErrorHandler(callable $error_handler): void
    {
        $this->error_handler = $error_handler;
    }

}
