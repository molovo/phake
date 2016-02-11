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
        // Create the parent exception
        parent::__construct($message, $code, $previous);

        // Output the message to stdout
        $msg = ANSI::fg($message, ANSI::RED);
        Prompt::output($msg);

        // Exit with error status
        exit(1);
    }
}
