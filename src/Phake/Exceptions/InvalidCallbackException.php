<?php

namespace Phake\Exceptions;

class InvalidCallbackException extends Base
{
    protected $message = 'The callback passed to a task must be a shell command, an array of shell commands or a closure';
}
