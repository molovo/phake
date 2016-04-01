<?php

namespace Phake;

class Help
{
    public static function render(Runner $runner)
    {
        echo $runner->output->yellow->render('Usage:');
        echo $runner->output->render('  phake [options] [command|task|group]');

        echo $runner->output->render('');
        echo $runner->output->yellow->render('Options:');
        echo $runner->output->render('  -h, --help               Show help text and exit.');
        echo $runner->output->render('  -v, --version            Show version information and exit.');
        echo $runner->output->render('  -d, --dir <dir>          Specify a custom working directory.');
        echo $runner->output->render('  -f, --phakefile <file>   Specify a custom Phakefile.');
        echo $runner->output->render('  -t, --tasks              List all tasks defined in the Phakefile and exit.');
        echo $runner->output->render('  -g, --groups             List all groups defined in the Phakefile and exit.');

        if (file_exists($runner->phakefile)) {
            require_once $runner->phakefile;

            echo $runner->output->render('');
            echo $runner->output->yellow->render('Tasks:');

            $runner->tasks('  ');

            echo $runner->output->render('');
            echo $runner->output->yellow->render('Groups:');

            $runner->output->setGlobalIndent(2);
            $runner->groups();
        }

        exit;
    }
}
