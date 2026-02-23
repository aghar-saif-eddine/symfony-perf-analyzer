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

class NPlusOneRule implements RuleInterface
{
    public function analyze(string $projectPath, Report $report): void
    {
        $srcDir = $projectPath . '/src';

        if (!is_dir($srcDir)) {
            return;
        }

        $finder = new Finder();
        $finder->files()->in($srcDir)->name('*.php');

        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        foreach ($finder as $file) {
            $code = $file->getContents();

            if (str_contains($code, 'namespace Aghar\SymfonyPerfAnalyzer')) {
                continue;
            }
            try {
                $stmts = $parser->parse($code);
                $traverser = new NodeTraverser();

                $visitor = new class($file->getRelativePathname(), $report) extends NodeVisitorAbstract {
                    private string $filename;
                    private Report $report;
                    private bool $inLoop = false;

                    public function __construct($filename, Report $report)
                    {
                        $this->filename = $filename;
                        $this->report = $report; // 👈 ولينا نستعملو الـ Report
                    }

                    public function enterNode(Node $node)
                    {
                        if ($node instanceof Node\Stmt\Foreach_ || $node instanceof Node\Stmt\For_ || $node instanceof Node\Stmt\While_) {
                            $this->inLoop = true;
                        }

                        if ($this->inLoop && $node instanceof Node\Expr\MethodCall) {
                            if ($node->name instanceof Node\Identifier && str_starts_with($node->name->name, 'get')) {
                                // 👈 نزيدو الغلطة ديركت في الـ Report
                                $this->report->addViolation(new Violation(
                                    'N+1 Query Risk',
                                    sprintf('Potential N+1 query: Calling "%s()" inside a loop.', $node->name->name),
                                    $this->filename,
                                    $node->getStartLine(),
                                    'warning'
                                ));
                            }
                        }
                    }

                    public function leaveNode(Node $node)
                    {
                        if ($node instanceof Node\Stmt\Foreach_ || $node instanceof Node\Stmt\For_ || $node instanceof Node\Stmt\While_) {
                            $this->inLoop = false;
                        }
                    }
                };

                $traverser->addVisitor($visitor);
                $traverser->traverse($stmts);
            } catch (Error $e) {
                continue;
            }
        }
    }
}
