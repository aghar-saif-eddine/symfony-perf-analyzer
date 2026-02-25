<?php
namespace Aghar\SymfonyPerfAnalyzer\Analyzer\Rules;

use Aghar\SymfonyPerfAnalyzer\Analyzer\RuleInterface;
use Aghar\SymfonyPerfAnalyzer\Model\Report;
use Aghar\SymfonyPerfAnalyzer\Model\Violation;
use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Symfony\Component\Finder\Finder;

class MassiveHydrationRule implements RuleInterface
{
    public function analyze(string $projectPath, Report $report): void
    {
        $srcDir = $projectPath . '/src';
        if (!is_dir($srcDir)) return;

        $finder = new Finder();
        $finder->files()->in($srcDir)->name('*.php');
        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        foreach ($finder as $file) {
            $code = $file->getContents();
            if (str_contains($code, 'namespace Aghar\SymfonyPerfAnalyzer')) continue;

            try {
                $stmts = $parser->parse($code);
                $traverser = new NodeTraverser();

                $visitor = new class($file->getRelativePathname(), $report) extends NodeVisitorAbstract {
                    private string $filename;
                    private Report $report;

                    public function __construct($filename, Report $report) {
                        $this->filename = $filename;
                        $this->report = $report;
                    }

                    public function enterNode(Node $node) {
                        if ($node instanceof Node\Expr\MethodCall && $node->name instanceof Node\Identifier) {
                            if ($node->name->toString() === 'findAll') {
                                $this->report->addViolation(new Violation(
                                    'Massive Hydration Risk',
                                    "Using 'findAll()' can cause severe memory leaks on large tables. Consider using pagination, limit, or toIterable().",
                                    $this->filename,
                                    $node->getStartLine(),
                                    'warning'
                                ));
                            }
                        }
                    }
                };

                $traverser->addVisitor($visitor);
                $traverser->traverse($stmts);
            } catch (Error $e) { continue; }
        }
    }
}