<?php

namespace Phake;

use Closure;
use Molovo\Graphite\Graphite;
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
     * An array of command line options.
     *
     * @var array
     */
    private $opts = [];

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
     * Whether quiet mode is on.
     *
     * @var bool
     */
    public $quiet = false;

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
        $this->name   = $name;
        $this->output = new Graphite;

        if ($name === null) {
            $this->pwd = $_SERVER['PWD'];

            static::$current = $this;

            $this->phakefile = $this->pwd.'/Phakefile';

            $this->parseOpts();

            // Check that the phakefile exists
            if (!file_exists($this->phakefile)) {
                throw new PhakefileNotFoundException;
            }

            // Require the phakefile
            require_once $this->phakefile;

            // Get the arguments passed.
            $args = $_SERVER['argv'];

            // The first argument is the script name, so drop it
            array_shift($args);

            foreach ($args as $index => $task) {
                if (strpos($task, '-') === 0) {
                    // Argument is an option, so we remove it
                    unset($args[$index]);

                    // If the next argument is the option's value,
                    // remove that as well.
                    $option = preg_replace('/^[-]{1,2}/', '', $task);
                    if (isset($args[$index + 1]) && $args[$index + 1] === $this->opts[$option]) {
                        unset($args[$index + 1]);
                    }
                    continue;
                }
            }

            // Get the task name and check it is defined
            if (count($args) === 0) {
                $args = ['default'];
            }

            $this->run($args);
        }
    }

    /**
     * Outputs a list of tasks to the screen.
     *
     * @param string|null $prefix Any group prefix
     */
    public function tasks($prefix = null)
    {
        foreach ($this->tasks as $name => $task) {
            echo $this->output->strip($this->output->render($prefix.$name));
        }

        foreach ($this->groups as $group) {
            $group->tasks($prefix.$group->name.':');
        }
    }

    /**
     * Outputs a list of groups to the screen.
     *
     * @param string|null $prefix Any group prefix
     */
    public function groups($prefix = null)
    {
        foreach ($this->groups as $name => $group) {
            $prefix = $this->name !== null ? $this->name.':' : '';
            echo $this->output->strip($this->output->render($prefix.$name));
            $group->groups($prefix.$group->name.':');
        }
    }

    /**
     * Parse command line options.
     */
    private function parseOpts()
    {
        $this->opts = $opts = getopt('hvqd:f:tg', [
            'help',
            'version',
            'quiet',
            'dir:',
            'phakefile:',
            'tasks',
            'groups',
        ]);

        if ((isset($opts['dir']) && ($dir = $opts['dir'])) || (isset($opts['d']) && ($dir = $opts['d']))) {
            $this->pwd       = $dir;
            $this->phakefile = $this->pwd.'/Phakefile';
        }

        if ((isset($opts['phakefile']) && ($file = $opts['phakefile'])) || (isset($opts['f']) && ($file = $opts['f']))) {
            $this->phakefile = $file;

            // If Phakefile does not exist as an absolute pathname, try a
            // relative path.
            if (!file_exists($this->phakefile)) {
                $this->phakefile = $this->pwd.'/'.$this->phakefile;
            }

            // If Phakefile still does not exist, throw an exception.
            if (!file_exists($this->phakefile)) {
                throw new PhakefileNotFoundException;
            }
        }

        if (isset($opts['version']) || isset($opts['v'])) {
            echo $this->output->yellow->render('Phake');
            echo $this->output->render('  Version 1.1.1');
            exit;
        }

        if (isset($opts['help']) || isset($opts['h'])) {
            Help::render($this);
            exit;
        }

        if (isset($opts['quiet']) || isset($opts['h'])) {
            $this->quiet = true;
        }

        if (isset($opts['tasks']) || isset($opts['t'])) {
            // Require the phakefile
            require_once $this->phakefile;

            $this->tasks();
            exit;
        }

        if (isset($opts['groups']) || isset($opts['g'])) {
            // Require the phakefile
            require_once $this->phakefile;

            $this->groups();
            exit;
        }
    }

    /**
     * Run tasks.
     *
     * @param array $args An array of arguments
     */
    public function run(array $args = [])
    {
        if ($this->name === null) {
            // Run the tasks
            foreach ($args as $taskname) {
                $task = $this->task($taskname);

                echo $this->output->gray->render('    Running '.$taskname.'...');
                $task->run($args);
            }

            // Output a success message
            echo $this->output->render('');
            echo $this->output->green->render('    All tasks finished successfully');
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
