<?php
namespace Handlers;

interface SessionInterface
{
    public function __set(string $name, $value): void;

    public function __get(string $name);

    public function destroy(): void;

    public function id(string $id = null): string;
	
	public function clear(string $namespace = null, bool $suppress_error = false): void;

    public function registerErrorHandler(callable $error_handler): void;
}