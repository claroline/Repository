<?php

namespace Claroline\Command;

use Claroline\Handler\ParametersHandler;
use Claroline\Manager\PackageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PackageGeneratorCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('github:authenticate')
            ->setDescription('github oauth authentication')
            ->addOption(
               'show',
               null,
               InputOption::VALUE_NONE,
               'Show the current accesses.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('show')) {
        }
    }
}
