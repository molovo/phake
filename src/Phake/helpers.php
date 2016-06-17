<?php

use Phake\Runner;
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
        $runner = Runner::current();
        $task   = new Task($name, $callback, $runner);
        $runner->registerTask($task);
    }
}

if (!function_exists('group')) {
    /**
     * Define a group.
     *
     * @param string  $name     The group name
     * @param Closure $callback A closure containing groups
     */
    function group($name, Closure $callback)
    {
        $runner = Runner::current();
        $group  = new Runner($name, $runner);
        $runner->registerGroup($group, $callback);
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
        $task = Runner::current()->task($name);

        return $task->run($args);
    }
}
