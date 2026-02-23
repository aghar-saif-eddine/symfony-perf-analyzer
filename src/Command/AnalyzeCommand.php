<?php

namespace Aghar\SymfonyPerfAnalyzer\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use Aghar\SymfonyPerfAnalyzer\Analyzer\Runner;
use Aghar\SymfonyPerfAnalyzer\Analyzer\Rules\EnvDebugRule;
use Aghar\SymfonyPerfAnalyzer\Analyzer\Rules\NPlusOneRule;
use Aghar\SymfonyPerfAnalyzer\Analyzer\Rules\NamingConventionRule;
use Aghar\SymfonyPerfAnalyzer\Analyzer\Rules\SlowQueryProfilerRule;

use Aghar\SymfonyPerfAnalyzer\Formatter\ConsoleFormatter;
use Aghar\SymfonyPerfAnalyzer\Formatter\JsonFormatter;

#[AsCommand(
    name: 'analyze',
    description: 'Scans a Symfony project for performance issues'
)]
class AnalyzeCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('path', InputArgument::OPTIONAL, 'Path to the Symfony project', getcwd());
        $this->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format (console or json)', 'console');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $projectPath = $input->getArgument('path');
        $format = $input->getOption('format');

        if ($format === 'console') {
            $io->title('Symfony Performance Analyzer 🚀');
            $io->text("Scanning project at: <info>$projectPath</info>");
        }

        $runner = new Runner();
        $runner->addRule(new EnvDebugRule());
        $runner->addRule(new NPlusOneRule());
        $runner->addRule(new NamingConventionRule());
        $runner->addRule(new SlowQueryProfilerRule());

        $report = $runner->run($projectPath);

        $formatter = $format === 'json' ? new JsonFormatter() : new ConsoleFormatter();

        return $formatter->format($report, $io);
    }
}
