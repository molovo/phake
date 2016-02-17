<?php

namespace Phake\Exceptions;

use Molovo\Prompt\ANSI;
use Molovo\Prompt\Prompt;

class Base extends \Exception
{
    /**
     * Create the exception.
     *
     * @param string    $message  The message
     * @param int       $code     The exception code
     * @param Exception $previous The previous exception
     */
    public function __construct($message = null, $code = 0, Exception $previous = null)
    {
        $this->message = $message ?: $this->message;

        // Output the message to stdout
        $msg = ANSI::fg($this->message, ANSI::RED);
        Prompt::output($msg);

        // Exit with error status
        exit(1);
    }
}
