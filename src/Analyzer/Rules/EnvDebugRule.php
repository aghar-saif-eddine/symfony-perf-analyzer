<?php

namespace Aghar\SymfonyPerfAnalyzer\Analyzer\Rules;

use Aghar\SymfonyPerfAnalyzer\Analyzer\RuleInterface;
use Aghar\SymfonyPerfAnalyzer\Model\Report;
use Aghar\SymfonyPerfAnalyzer\Model\Violation;

class EnvDebugRule implements RuleInterface
{
    public function analyze(string $projectPath, Report $report): void
    {
        $envFile = $projectPath . '/.env';

        if (file_exists($envFile)) {
            $content = file_get_contents($envFile);

            if (preg_match('/APP_DEBUG\s*=\s*[\'"]?(1|true)[\'"]?/i', $content)) {
                $report->addViolation(new Violation(
                    'DebugConfig',
                    '🚨 APP_DEBUG is enabled! This slows down your app in production.',
                    '.env',
                    0,
                    'error'
                ));
            }
        }
    }
}
