<?php

namespace Phake\Exceptions;

use Molovo\Graphite\Graphite;

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
        echo(new Graphite)->red->render($this->message);

        // Exit with error status
        exit(1);
    }
}
