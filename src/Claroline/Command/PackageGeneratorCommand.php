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
            ->setName('package:generate')
            ->setDescription('Generate the list of claroline packages in output directory specified')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Wich package do you want to generate ?'
            )
            ->addOption(
                'tag',
                null,
                InputOption::VALUE_REQUIRED,
                'The package version.'
            )
            ->addOption(
               'a',
               null,
               InputOption::VALUE_NONE,
               'If set, all packages in packages.ini will be generated.'
            )
            ->addOption(
               'test',
               null,
               InputOption::VALUE_NONE,
               'If set, the testing directory output will be used'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repositories = array();
        $prodDir = ParametersHandler::getParameter('output_dir');
        $testDir = ParametersHandler::getParameter('test_dir');
        $outputDir = $input->getOption('test') ? $testDir: $prodDir;
        $manager = new PackageManager($outputDir, $output);

        if ($name = $input->getArgument('name')) {
            $repositories[] = $name;
        }

        $tag = $name = $input->getOption('tag');

        if ($input->getOption('a')) {
            $repositories = ParametersHandler::getHandledPackages();
        }

        foreach ($repositories as $repository) {
            $manager->create($repository, $tag);
        }
    }
}
