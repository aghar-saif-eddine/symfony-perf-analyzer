<?php

namespace Aghar\SymfonyPerfAnalyzer\Analyzer\Rules;

use Aghar\SymfonyPerfAnalyzer\Analyzer\RuleInterface;
use Aghar\SymfonyPerfAnalyzer\Model\Report;
use Aghar\SymfonyPerfAnalyzer\Model\Violation;
use Symfony\Component\Finder\Finder;

class SlowQueryProfilerRule implements RuleInterface
{
    public function analyze(string $projectPath, Report $report): void
    {
        $profilerDir = $projectPath . '/var/cache/dev/profiler';

        if (!is_dir($profilerDir)) {
            return;
        }

        $finder = new Finder();
        $finder->files()
            ->in($profilerDir)
            ->name('/^[a-f0-9]{6}$/')
            ->date('since 2 days ago');

        foreach ($finder as $file) {
            $content = $file->getContents();
            if (preg_match_all('/"sql";s:\d+:"([^"]+)".*?"executionMS";d:([0-9.]+);/sU', $content, $matches, PREG_SET_ORDER)) {

                foreach ($matches as $match) {
                    $sql = $match[1];
                    $timeMs = (float) $match[2];

                    if ($timeMs > 50) {
                        $querySnippet = substr(trim($sql), 0, 60) . '...';
                        $token = $file->getFilename();

                        $report->addViolation(new Violation(
                            'Slow Query (Profiler)',
                            sprintf('Query took %.2f ms! (Token: %s) SQL: %s', $timeMs, $token, $querySnippet),
                            'Profiler Token: ' . $token,
                            0,
                            'warning'
                        ));
                    }
                }
            }
        }
    }
}
