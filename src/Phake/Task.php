<?php

namespace Phake;

use Closure;
use Molovo\Prompt\ANSI;
use Molovo\Prompt\Prompt;
use ReflectionFunction;

class Task
{
    /**
     * The name of the task.
     *
     * @var string
     */
    public $name;

    /**
     * A callback to be executed when the task is run.
     *
     * @var Closure
     */
    private $callback;

    /**
     * Create a new task instance.
     *
     * @param string               $name     The task name
     * @param string|array|Closure $callback The command to run
     */
    public function __construct($name, $callback)
    {
        $this->name     = $name;
        $this->callback = $this->parseCallback($callback);
    }

    /**
     * Turn the passed callback into something we can execute.
     *
     * @param string|array|Closure $callback The callback
     *
     * @return Closure
     */
    private function parseCallback($callback)
    {
        $task = $this;

        // If the passed callback is a string, execute it as
        // a shell command directly
        if (is_string($callback)) {
            return function () use ($callback, $task) {
                $task->executeProcess($callback);
            };
        }

        // If the passed callback is an array, execute each item
        // within it as a shell command
        if (is_array($callback)) {
            return function () use ($callback, $task) {
                foreach ($callback as $cmd) {
                    $task->executeProcess($cmd);
                }
            };
        }

        // If the passed callback is already a closure, we can just
        // use it as it is
        if ($callback instanceof Closure) {
            return $callback;
        }

        // If nothing has been returned yet, then we throw an exception
        throw new InvalidCallbackException;
    }

    /**
     * Execute a shell command.
     *
     * @param string $cmd The command to execute
     */
    private function executeProcess($cmd)
    {
        $cwd    = $_SERVER['PWD'];
        $status = 0;

        // Create the descriptor spec for the command
        $descriptorspec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('file', $cwd.'phake_error.out', 'a'),
        );

        // An array to store out pipes in
        $pipes = [];

        // Open the process
        $process = proc_open($cmd, $descriptorspec, $pipes, $cwd);

        // If process is not a resource, then opening it failed,
        // so we set the exit status to 1
        if (!is_resource($process)) {
            // Echo a failure message to the screen
            $cmd = ANSI::fg($cmd, ANSI::YELLOW);
            $msg = 'Phake was unable to run the command '.$cmd;
            Prompt::output(ANSI::fg($msg, ANSI::RED));
            $status = 1;
        }

        // If the process is a resource, it was created successfully
        if (is_resource($process)) {
            // Echo the command output to the screen
            echo stream_get_contents($pipes[1]);

            // Close each of the pipes
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }

            // Get the exit code from the process
            $status = proc_close($process);
        }

        // If command exited with status > 0 we should exit with
        // the same status
        if ($status > 0) {
            $msg = 'Task '.$this->name.' exited with status '.$status;
            Prompt::output(ANSI::fg($msg, ANSI::RED));
            exit((int) $status);
        }
    }

    /**
     * Run the task.
     *
     * @param array $args Arguments to pass to the callback
     */
    public function run(array $args = [])
    {
        // Get the start time of the process
        $time = microtime(true);

        // Output a message to the screen
        $msg = 'Running task '.$this->name.'...';
        Prompt::output(ANSI::fg($msg, ANSI::GRAY));

        // Invoke the callback
        $func   = new ReflectionFunction($this->callback);
        $status = $func->invokeArgs($args);

        // Output a success message to the screen
        $time = ceil((microtime(true) - $time) * 1000);
        $msg  = 'Task '.$this->name.' finished in '.$time.'ms';
        Prompt::output($msg);
        Prompt::output('');
    }
}
