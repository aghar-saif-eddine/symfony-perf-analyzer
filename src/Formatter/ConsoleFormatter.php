<?php

namespace Aghar\SymfonyPerfAnalyzer\Formatter;

use Aghar\SymfonyPerfAnalyzer\Model\Report;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command;

class ConsoleFormatter implements FormatterInterface
{
    public function format(Report $report, SymfonyStyle $io): int
    {
        $violations = $report->getViolations();

        if (empty($violations)) {
            $io->success('Great job! No performance issues found.');
            return Command::SUCCESS;
        }

        $io->error(sprintf('Found %d performance issue(s)!', count($violations)));

        $tableRows = [];
        foreach ($violations as $v) {
            $tableRows[] = [$v->severity, $v->ruleName, $v->message, $v->file];
        }

        $io->table(['Severity', 'Rule', 'Message', 'File'], $tableRows);

        $score = $report->getScore();
        $io->section('📊 Final Performance Score');

        if ($score >= 90) {
            $io->success("Score: $score/100 - Excellent! Your code is blazing fast 🚀");
        } elseif ($score >= 70) {
            $io->warning("Score: $score/100 - Good, but needs some optimization 🛠️");
        } else {
            $io->error("Score: $score/100 - Critical performance issues detected! 🚨");
        }

        return Command::FAILURE;
    }
}
