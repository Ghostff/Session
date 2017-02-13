<?php
namespace Handlers;

interface SessionInterface
{
    public function __set(string $name, $value): void;

    public function __get(string $name);

    public function destroy(): void;

    public function id(): ?string;

    public function registerErrorHandler(callable $error_handler): void;
}