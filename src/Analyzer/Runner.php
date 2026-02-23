<?php

namespace Aghar\SymfonyPerfAnalyzer\Analyzer;

use Aghar\SymfonyPerfAnalyzer\Model\Report;

class Runner
{
    private array $rules = [];

    public function addRule(RuleInterface $rule): void
    {
        $this->rules[] = $rule;
    }

    public function run(string $projectPath): Report
    {
        $report = new Report();

        foreach ($this->rules as $rule) {
            $rule->analyze($projectPath, $report);
        }

        return $report;
    }
}
