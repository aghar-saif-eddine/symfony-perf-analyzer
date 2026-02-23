<?php

namespace Aghar\SymfonyPerfAnalyzer\Formatter;

use Aghar\SymfonyPerfAnalyzer\Model\Report;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command;

class JsonFormatter implements FormatterInterface
{
    public function format(Report $report, SymfonyStyle $io): int
    {
        $data = [
            'score' => $report->getScore(),
            'issues_count' => count($report->getViolations()),
            'violations' => array_map(fn($v) => [
                'rule' => $v->ruleName,
                'message' => $v->message,
                'file' => $v->file,
                'line' => $v->line,
                'severity' => $v->severity
            ], $report->getViolations())
        ];

        $io->writeln(json_encode($data, JSON_PRETTY_PRINT));

        return count($report->getViolations()) > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
