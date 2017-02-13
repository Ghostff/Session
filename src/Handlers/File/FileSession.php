<?php

declare(strict_types=1);

namespace Handlers\File;

class FileSession implements \Handlers\SessionInterface
{
    public $config = null;
    private $name = null;
    private $sess_id = null;
    private $session = [];
    private $error_handler = null;
    private $initialized = false;

    private $flashed = false;
    private $remove = false;
    public $segmented = null;


    public function __construct(string $name = null, \stdClass $config)
    {
        $this->name = $name;
        $this->config = $config;
        $this->segmented = '__\raw';
    }

    private function getDomain(): string
    {
        $sp = 'SERVER_PORT';
        $sn = 'SERVER_NAME';
        $domain = (($_SERVER[$sp] != '80') && ($_SERVER[$sp] != '443')) ? $_SERVER[$sn] . ':' . $_SERVER[$sp] : $_SERVER[$sn];
        return '.' . str_replace('www.', '', $domain);
    }

    private function checkSession(): bool
    {
        if ($this->sess_id != null)
        {
            if ($this->config->match_ip)
            {
                $type = 'REMOTE_ADDR';
                if (isset($_SESSION['__\prefab']['ip']) && ($_SESSION['__\prefab']['ip'] != $_SERVER[$type]))
                {
                    $this->newError('Session: IP address mismatch');
                    return false;
                }
                $_SESSION['__\prefab']['ip'] = $_SERVER[$type];
            }
            if ($this->config->match_browser)
            {
                $type = 'HTTP_USER_AGENT';
                if (isset($_SESSION['__\prefab']['browser']) && ($_SESSION['__\prefab']['browser'] != $_SERVER[$type]))
                {
                    $this->newError('Session: User Agent string mismatch');
                    return false;
                }
                $_SESSION['__\prefab']['browser'] = $_SERVER[$type];
            }
        }
        return true;
    }

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
            if ( ! is_null($this->name))
            {
                session_name($this->name);
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

    private function sessionType(string $type, string $name, $value): void
    {
        $this->init();
        $session_status = isset($this->session[$this->segmented][$type][$name]);
        # Check that at least one value has been changed before starting up the session
        if ( ! $session_status || ($session_status && ($this->session[$this->segmented][$type][$name] != $value)))
        {
            @session_start();
            $_SESSION[$this->segmented][$type][$name] = $value;
            $this->session = $_SESSION;
            session_write_close();
        }
        $this->segmented = '__\raw';
    }

    public function __set(string $name, $value): void
    {
        $type = 'static';
        if ($this->flashed)
        {
            $type = 'flash';
            $this->flashed = false;
        }
        $this->sessionType($type, $name, $value);
    }

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
        $session = $this->session[$this->segmented][$type][$name] ?? null;
        # Remove flash from session
        if ($this->flashed || $this->remove)
        {
            $this->flashed = false;
            $this->remove = false;
            unset($this->session[$this->segmented][$type][$name]);
        }
        $this->segmented = '__\raw';
        return $session;
    }

    public function segment(string $name): \Handlers\Segment
    {
        return new \Handlers\Segment($name, $this);
    }

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

    public function id(): ?string
    {
        return $this->sess_id;
    }

    public function getAll(): array
    {
        return $this->session;
    }

    public function registerErrorHandler(callable $error_handler): void
    {
        $this->error_handler = $error_handler;
    }

    private function newError(string $message): void
    {
        call_user_func_array($this->error_handler, [$message]);
    }

    public function refresh()
    {

    }
}