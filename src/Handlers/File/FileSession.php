<?php

declare(strict_types=1);

namespace Handlers\File;

class FileSession implements \Handlers\SessionInterface
{
	
	public $segmented = null;
	
	private $name = null;
	private $config = null;
    private $sess_id = '';
    private $error_handler = null;
	
	private $session = [];
	
    private $initialized = false;
    private $flashed = false;
    private $remove = false;

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
        if ($this->sess_id != '')
        {
            # Check if session is ruining ip it was initialized with
            if ($this->config->match_ip)
            {
                $type = 'REMOTE_ADDR';
                if (isset($_SESSION['__\prefab']['ip']) && ($_SESSION['__\prefab']['ip'] != $_SERVER[$type]))
                {
                    $this->newError('Session IP address mismatch');
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
                    $this->newError('Session user agent string mismatch');
                    return false;
                }
                $_SESSION['__\prefab']['browser'] = $_SERVER[$type];
            }

            # Check if session has expired but still being used
            if (($this->config->expiration !== 0) && ($this->config->expiration < time()))
            {
                $this->newError('Using expired session');
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
	
    private function sessionType(string $type, string $name, $value): void
    {
        $this->init();
        $status = isset($this->session[$this->name][$this->segmented][$type][$name]);
        # Check that at least one value has been changed before starting up the session
        if ( ! $status || ($status && ($this->session[$this->name][$this->segmented][$type][$name] != $value)))
        {
			# No need suppressing error because there won't be a case of it being open.
			session_start();
            $_SESSION[$this->name][$this->segmented][$type][$name] = $value;
            $this->session = $_SESSION;
            session_write_close();
        }
        $this->segmented = '__\raw';
    }
	
	private function newError(string $message): void
    {
        call_user_func_array($this->error_handler, [$message]);
    }




    public function rotate(bool $delete_old = false, bool $write_nd_close = true)
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
        $session = $this->session[$this->name][$this->segmented][$type][$name] ?? null;
        # Remove flash from session
        if ($this->flashed || $this->remove)
        {
            $this->flashed = false;
            $this->remove = false;
            unset($this->session[$this->name][$this->segmented][$type][$name]);
        }
        $this->segmented = '__\raw';
        return $session;
    }

    public function segment(string $name): \Handlers\Segment
    {
        return new \Handlers\Segment($name, $this);
    }
	
	public function clear(string $namespace, bool $if_exists = false): void
    {
		if (isset($this->session[$namespace]))
		{
			unset($this->session[$namespace]);
		}
		else
		{
			if ( ! $if_exists)
			{
				throw new \RuntimeException(sprintf('%s does not exists in current session namespace', $namespace));
			}
		}
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

    public function getAll(string $name = null): array
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

    public function registerErrorHandler(callable $error_handler): void
    {
        $this->error_handler = $error_handler;
    }

}