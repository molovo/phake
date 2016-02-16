<?php

namespace Phake;

use Molovo\Prompt\ANSI;
use Molovo\Prompt\Prompt;

class Help
{
    public static function render(Runner $runner)
    {
        Prompt::output(ANSI::fg('Usage:', ANSI::YELLOW));
        Prompt::output('  phake [options] [command|task|group]');

        Prompt::output('');
        Prompt::output(ANSI::fg('Options:', ANSI::YELLOW));
        Prompt::output('  -h, --help               Show help text and exit.');
        Prompt::output('  -v, --version            Show version information and exit.');
        Prompt::output('  -d, --dir <dir>          Specify a custom working directory.');
        Prompt::output('  -f, --phakefile <file>   Specify a custom Phakefile.');

        Prompt::output('');
        Prompt::output(ANSI::fg('Commands:', ANSI::YELLOW));
        foreach ($runner->commands as $command => $description) {
            Prompt::output('  '.str_pad($command, 10).$description);
        }

        Prompt::output('');
        Prompt::output(ANSI::fg('Tasks:', ANSI::YELLOW));
        $runner->tasks();

        Prompt::output('');
        Prompt::output(ANSI::fg('Groups:', ANSI::YELLOW));
        $runner->groups();
    }
}
