<?php

namespace Phake\Exceptions;

class PhakefileNotFoundException extends Base
{
    protected $message = 'A Phakefile could not be found.';
}
