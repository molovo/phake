<?php

namespace Phake;

use Accidents\ExceptionHandler;
use Closure;
use Exception;
use Molovo\Graphite\Graphite;
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
     * The parent runner for this test.
     *
     * @var Runner
     */
    private $runner;

    /**
     * Create a new task instance.
     *
     * @param string               $name     The task name
     * @param string|array|Closure $callback The command to run
     * @param Runner|null          $runner   The parent task runner
     */
    public function __construct($name, $callback, Runner $runner = null)
    {
        $this->name     = $name;
        $this->callback = $this->parseCallback($callback);
        $this->runner   = $runner;
        $this->output   = new Graphite;
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
                return $task->executeProcess($callback);
            };
        }

        // If the passed callback is an array, execute each item
        // within it as a shell command
        if (is_array($callback)) {
            return function () use ($callback, $task) {
                foreach ($callback as $cmd) {
                    $status = $task->executeProcess($cmd);

                    if ($status > 0) {
                        return $status;
                    }
                }
            };
        }

        // If the passed callback is already a closure, we can just
        // use it as it is
        if ($callback instanceof Closure) {
            return function () use ($callback, $task) {
                try {
                    $callback();

                    return 0;
                } catch (Exception $e) {
                    $handler = new ExceptionHandler;
                    $handler($e);

                    return 1;
                }
            };
        }

        // If nothing has been returned yet, then we throw an exception
        throw new InvalidCallbackException;
    }

    /**
     * Execute a shell command.
     *
     * @param string $cmd The command to execute
     */
    public function executeProcess($cmd)
    {
        $cwd    = $_SERVER['PWD'];
        $status = 0;

        // Create the descriptor spec for the command
        $descriptorspec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );

        // An array to store out pipes in
        $pipes = [];

        // Open the process
        $process = proc_open($cmd, $descriptorspec, $pipes, $cwd);

        // If process is not a resource, then opening it failed,
        // so we set the exit status to 1
        if (!is_resource($process)) {
            // Echo a failure message to the screen
            $this->output->red->render('  x Task '.$this->name.' failed to launch external process '.$cmd);

            return 1;
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

        return $status;
    }

    /**
     * Get the line width of the current terminal.
     *
     * @return int
     */
    private function getLineWidth()
    {
        $line_width = isset($_ENV['COLUMNS']) && $_ENV['COLUMNS']
                    ? $_ENV['COLUMNS']
                    : 0;

        if ($line_width === 0) {
            $line_width = exec('tput cols');
        }

        return $line_width;
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

        // Invoke the callback
        $func = new ReflectionFunction($this->callback);

        ob_start();
        $status = $func->invokeArgs($args);

        $output = '';
        while (ob_get_level() > 0) {
            $output .= ob_get_clean();
        }

        if (!$this->runner->quiet) {
            echo $output;
        }

        if ($status > 0) {
            $errorMessage = $this->output->red('  x Task '.$this->name.' failed...');
            echo $this->output->render($errorMessage);

            return;
        }

        // Output a success message to the screen
        $time = ceil((microtime(true) - $time) * 1000);

        $successMessage = $this->output->green('  ✓ ');
        $successMessage .= $this->output->white('Finished '.$this->name.' in ').$this->output->yellow($time.'ms');
        echo $this->output->render($successMessage);
    }
}
