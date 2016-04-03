# Phake

[![Join the chat at https://gitter.im/molovo/phake](https://badges.gitter.im/molovo/phake.svg)](https://gitter.im/molovo/phake?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

A PHP task runner inspired by Make, Rake et al.

## Installation and Usage

#### Installing phake globally

```sh
composer global require molovo/phake

# Ensure ~/.composer/vendor/bin is in your path, then in a directory
# with a Phakefile, run:
phake task
```

#### Installing per Project:

```sh
composer require molovo/phake

vendor/bin/phake task
```

#### Installing ZSH Completion

Rename the file `phake.zsh-completion` to `_phake`, and move it somewhere in your `$fpath`. `/usr/local/share/zsh/site-functions/` is usually a good choice.

## Usage

#### Default Task

Create a file called `Phakefile` in the root of your project. This is just a simple PHP file. The first task you should define is called `default`. This is the task that is performed if you run `phake` without any arguments.

For now, we'll use it to execute a simple shell command by passing it a string:

```php
<?php

task('default', 'echo "Hello World!"');
```

Now you can run `phake` in the same directory as your `Phakefile`, and you will see `Hello World!` printed to the console.

#### Naming tasks

The first parameter is the name of the task.

```php
<?php

task('my_awesome_task', 'echo "Doing awesome things..."');
```

To run that task, run `phake my_awesome_task`.

#### Multiple commands

Most tasks in phake will perform more than one simple shell command. To do that, pass an array of commands as the second parameter.

```php
<?php

task('test', [
  'echo "Starting Tests"',
  './my_tests.sh',
  'echo "Tests finished."'
]);
```

#### Using closures

Tasks can also contain a PHP closure, so that you can include PHP logic in your tasks. Phake uses Composer for autoloading, so that your entire project's PHP code is available to you.

```php
<?php

task('php_example', function () {
  my_php_function(); // Your PHP code is available here
});
```

#### Running other tasks

If you use the PHP callback method, you can call other tasks from within your tasks's callback. Just use the `run()` helper.

```php
<?php

task('task_one', 'echo "Hello World!"');

task('task_two', function () {
  run('task_one');
});
```

#### Groups

Groups can be defined using the `group()` helper. Just use the `task()` helper inside the group's closure, and the task will automagically be assigned to that group.

```php
<?php

group('my_group', function() {
  task('task_one', 'echo "Task One!"');
  task('task_two', 'echo "Task Two!"');
})
```

You can run an individual task by calling `phake group:task`, or run all tasks in the group by calling `phake group`.

```sh
phake my_group
# Output:
#   Task One!
#   Task Two!

phake my_group:task_one
# Output:
#   Task One!
```

Groups can be nested indefinitely, and running a group will run all tasks and groups within that group.

```php
<?php

group('group', function() {
  task('first_task', 'echo "I\'m in the parent group"');

  group('subgroup', function() {
    task('second_task', 'echo "I\'m in the subgroup"');
  });
});
```

```sh
phake group
# Ouptut:
#   I'm in the parent group
#   I'm in the subgroup
```

#### Running multiple tasks

Each argument to `phake` is a task, and multiple tasks can be run by simply passing them all to phake. Based on the groups example above, this would give the same results as `phake group`:

```sh
phake group:first_task group:subgroup:second_task
# Ouptut:
#   I'm in the parent group
#   I'm in the subgroup
```

## Options

By default phake looks for a `Phakefile` in the directory in which it is called.
To use a custom directory, pass a path to the `--dir` (`-d`) option.

```sh
phake --dir=/path/to/root
phake -d/path/to/root
```

To use a different filename, or a Phakefile outside of the directory, use the
`--phakefile` (`-f`) option.

```sh
phake --phakefile=/your/custom/file
phake -f/your/custom/file
```

To list all tasks or groups in a Phakefile, use the `--tasks` or `--groups` options.

```sh
phake --tasks
phake --groups
```

To hide any output produced by your tasks, use the `--quiet` (`-q`) option.

```sh
phake --quiet
```
