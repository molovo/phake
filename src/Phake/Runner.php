<?php

namespace Phake;

use Closure;
use Molovo\Prompt\ANSI;
use Molovo\Prompt\Prompt;
use Phake\Exceptions\GroupNotFoundException;
use Phake\Exceptions\PhakefileNotFoundException;
use Phake\Exceptions\TaskNotFoundException;

class Runner
{
    /**
     * An array containing this runner's defined tasks.
     *
     * @var Task[]
     */
    private $tasks = [];

    /**
     * An array containing this runner's defined groups.
     *
     * @var Runner[]
     */
    private $groups = [];

    /**
     * The name of the runner.
     *
     * @var string|null
     */
    private $name = null;

    /**
     * This runner's parent.
     *
     * @var Runner|null
     */
    public $parent = null;

    /**
     * The current runner context.
     *
     * @var Runner|null
     */
    private static $current = null;

    /**
     * Get the current runner context.
     *
     * @return Runner|null
     */
    public static function current()
    {
        return static::$current;
    }

    /**
     * Create the task runner instance.
     */
    public function __construct($name = null)
    {
        $this->name = $name;

        if ($name === null) {
            $this->pwd = $_SERVER['PWD'];
            $phakefile = $this->pwd.'/Phakefile';

            // Check that the phakefile exists
            if (!file_exists($phakefile)) {
                throw new PhakefileNotFoundException;
            }

            static::$current = $this;

            // Require the phakefile
            require_once $phakefile;
        }
    }

    /**
     * Run tasks.
     */
    public function run(array $args = [])
    {
        if ($this->name === null) {
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
                $task = $this->task($taskname);
                $task->run($args);
            }

            // Output a success message
            $msg = ANSI::fg('Tasks finished successfully', ANSI::GREEN);
            Prompt::output($msg);
            exit(0);
        }

        foreach ($this->groups as $group) {
            $group->run($args);
        }

        foreach ($this->tasks as $task) {
            $task->run($args);
        }
    }

    /**
     * Check for and return a task.
     *
     * @param string $name The task name
     *
     * @return Task
     */
    public function task($name)
    {
        if (strstr($name, ':')) {
            $runner = $this;

            $taskList = explode(':', $name);

            while (count($taskList) > 1) {
                $runner = $runner->group(array_shift($taskList));
            }

            return $runner->task(implode(':', $taskList));
        }

        if (isset($this->groups[$name])) {
            return $this->groups[$name];
        }

        // If the task doesn't exist, throw an exception
        if (!isset($this->tasks[$name])) {
            throw new TaskNotFoundException('The task "'.$name.'" could not be found in Phakefile.');
        }

        return $this->tasks[$name];
    }

    /**
     * Check for and return a group.
     *
     * @param string $name The group name
     *
     * @return Runner
     */
    public function group($name)
    {
        // If the task doesn't exist, throw an exception
        if (!isset($this->groups[$name])) {
            throw new GroupNotFoundException('The group "'.$name.'" could not be found in Phakefile.');
        }

        return $this->groups[$name];
    }

    /**
     * Store a task in the tasks array.
     *
     * @param Task $task The task to store
     */
    public function registerTask(Task $task)
    {
        $this->tasks[$task->name] = $task;
    }

    /**
     * Store a group runner in the groups array.
     *
     * @param Runner $group The group to store
     */
    public function registerGroup(Runner $group, Closure $callback)
    {
        static::$current            = $group;
        $this->groups[$group->name] = $group;
        $callback();
        static::$current = $this;
    }
}
