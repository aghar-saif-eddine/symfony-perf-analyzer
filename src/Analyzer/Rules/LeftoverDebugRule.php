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

class LeftoverDebugRule implements RuleInterface
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
                        // Catch dd(), dump(), var_dump(), print_r()
                        if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name) {
                            $funcName = $node->name->toString();
                            if (in_array($funcName, ['dd', 'dump', 'var_dump', 'print_r', 'die'])) {
                                $this->report->addViolation(new Violation(
                                    'Leftover Debug Code',
                                    "Critical: Found '$funcName()' left in the code. This can break production!",
                                    $this->filename,
                                    $node->getStartLine(),
                                    'critical'
                                ));
                            }
                        }
                        // Catch exit; or die;
                        if ($node instanceof Node\Expr\Exit_) {
                            $this->report->addViolation(new Violation(
                                'Leftover Debug Code',
                                "Critical: Found 'exit' or 'die' statement.",
                                $this->filename,
                                $node->getStartLine(),
                                'critical'
                            ));
                        }
                    }
                };

                $traverser->addVisitor($visitor);
                $traverser->traverse($stmts);
            } catch (Error $e) { continue; }
        }
    }
}