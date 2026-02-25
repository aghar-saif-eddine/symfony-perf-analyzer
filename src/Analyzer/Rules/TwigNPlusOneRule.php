<?php
namespace Aghar\SymfonyPerfAnalyzer\Analyzer\Rules;

use Aghar\SymfonyPerfAnalyzer\Analyzer\RuleInterface;
use Aghar\SymfonyPerfAnalyzer\Model\Report;
use Aghar\SymfonyPerfAnalyzer\Model\Violation;
use Symfony\Component\Finder\Finder;

class TwigNPlusOneRule implements RuleInterface
{
    public function analyze(string $projectPath, Report $report): void
    {
        $templatesDir = $projectPath . '/templates';
        if (!is_dir($templatesDir)) return;

        $finder = new Finder();
        $finder->files()->in($templatesDir)->name('*.twig');

        foreach ($finder as $file) {
            $content = $file->getContents();
            $filename = $file->getRelativePathname();
            $lines = explode("\n", $content);

            $inLoop = false;
            $loopVar = '';

            foreach ($lines as $lineNumber => $line) {
                // Detect start of a loop: {% for item in items %}
                if (preg_match('/\{%\s*for\s+([a-zA-Z0-9_]+)\s+in/', $line, $matches)) {
                    $inLoop = true;
                    $loopVar = $matches[1];
                }

                // Detect end of a loop
                if (preg_match('/\{%\s*endfor\s*%\}/', $line)) {
                    $inLoop = false;
                    $loopVar = '';
                }

                // If inside a loop, check if we access a relation (e.g., item.category.name)
                // We look for {{ loopVar.something }} which might trigger Lazy Loading
                if ($inLoop && $loopVar !== '') {
                    if (preg_match('/\{\{\s*' . $loopVar . '\.[a-zA-Z0-9_]+/', $line)) {
                        $report->addViolation(new Violation(
                            'Twig N+1 Risk',
                            "Potential Lazy Loading inside Twig loop. Ensure '" . $loopVar . ".*' is properly joined in your Controller/Repository.",
                            "templates/" . $filename,
                            $lineNumber + 1,
                            'warning'
                        ));
                        // Prevent flagging multiple times for the same loop
                        $inLoop = false;
                    }
                }
            }
        }
    }
}