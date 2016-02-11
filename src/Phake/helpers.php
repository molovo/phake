<?php

use Phake\Run;
use Phake\Task;

if (!function_exists('task')) {
    /**
     * Define a task.
     *
     * @param string               $name     The task name
     * @param string|array|Closure $callback The callback to execute
     */
    function task($name, $callback)
    {
        $task = new Task($name, $callback);
        Run::registerTask($task);
    }
}

if (!function_exists('run')) {
    /**
     * Run a task.
     *
     * @param string $name The task to run
     * @param array  $args An array of arguments
     */
    function run($name, array $args = [])
    {
        $task = Run::task($name);

        return $task->run($args);
    }
}
