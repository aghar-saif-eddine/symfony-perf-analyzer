<?php

namespace Aghar\SymfonyPerfAnalyzer\Formatter;

use Aghar\SymfonyPerfAnalyzer\Model\Report;
use Symfony\Component\Console\Style\SymfonyStyle;

interface FormatterInterface
{
    public function format(Report $report, SymfonyStyle $io): int;
}
