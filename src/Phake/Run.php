<?php

namespace Phake;

use Molovo\Prompt\ANSI;
use Molovo\Prompt\Prompt;
use Phake\Exceptions\PhakefileNotFoundException;
use Phake\Exceptions\TaskNotFoundException;

class Run
{
    /**
     * An array containing all defined tasks.
     *
     * @var Task[]
     */
    private static $tasks = [];

    /**
     * Create the task runner instance.
     */
    public function __construct()
    {
        $pwd       = $_SERVER['PWD'];
        $phakefile = $pwd.'/Phakefile';

        // Check that the phakefile exists
        if (!file_exists($phakefile)) {
            throw new PhakefileNotFoundException;
        }

        // Require the phakefile
        require_once $phakefile;

        // Get the arguments passed.
        $args = $_SERVER['argv'];

        // The first argument is the script name, so drop it
        array_shift($args);

        // Get the task name and check it is defined
        if (count($args) === 0) {
            $args = ['default'];
        }

        // Run the tasks
        foreach ($args as $taskname) {
            $task = static::task($taskname);
            $task->run($args);
        }

        // Output a success message
        $msg = ANSI::fg('Tasks finished successfully', ANSI::GREEN);
        Prompt::output($msg);
        exit(0);
    }

    /**
     * Check for and return a task.
     *
     * @param string $name The task name
     *
     * @return Task
     */
    public static function task($name)
    {
        // If the task doesn't exist, throw an exception
        if (!isset(static::$tasks[$name])) {
            throw new TaskNotFoundException('The task "'.$name.'" could not be found in Phakefile.');
        }

        return static::$tasks[$name];
    }

    /**
     * Store a task in the tasks array.
     *
     * @param Task $task The task to store
     */
    public static function registerTask(Task $task)
    {
        static::$tasks[$task->name] = $task;
    }
}
