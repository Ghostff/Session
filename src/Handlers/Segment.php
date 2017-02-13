<?php

declare(strict_types=1);

namespace Handlers;

class Segment
{
    private $session;
    private $name = null;
    private $last_flash = false;

    public function __construct(string $name, \Handlers\File\FileSession $session)
    {
        # prevent overriding of default session data storage
        if ($name == '__\raw' || $name == '__\prefab')
        {
            throw new \InvalidArgumentException(sprintf('%s is a reserved name', $name));
        }
        $this->name = $name;
        $this->session = $session;
    }

    public function __set(string $name, $value)
    {
        $this->session->segmented = $this->name;
        $this->session->{$name} = $value;
    }

    public function __get(string $name)
    {
        $this->session->segmented = $this->name;
        if ($name == 'flash')
        {
            $this->session->{$name};
            return $this;
        }
        return $this->session->{$name};
    }
}