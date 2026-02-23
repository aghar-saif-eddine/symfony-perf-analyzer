<?php

namespace Aghar\SymfonyPerfAnalyzer\Analyzer;

use Aghar\SymfonyPerfAnalyzer\Model\Report;

interface RuleInterface
{
    public function analyze(string $projectPath, Report $report): void;
}
