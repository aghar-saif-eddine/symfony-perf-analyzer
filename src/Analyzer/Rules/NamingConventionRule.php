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

class NamingConventionRule implements RuleInterface
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

                    public function __construct($filename, Report $report)
                    {
                        $this->filename = $filename;
                        $this->report = $report;
                    }

                    public function enterNode(Node $node)
                    {
                        // Classes : PascalCase
                        if ($node instanceof Node\Stmt\Class_ && $node->name !== null) {
                            $className = $node->name->name;
                            if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $className)) {
                                $this->report->addViolation(new Violation(
                                    'Naming Standard',
                                    "Class name '$className' should use PascalCase (e.g., MyClass).",
                                    $this->filename,
                                    $node->getStartLine(),
                                    'warning'
                                ));
                            }
                        }
                        // Methods : camelCase
                        if ($node instanceof Node\Stmt\ClassMethod) {
                            $methodName = $node->name->name;
                            if (!str_starts_with($methodName, '__') && !preg_match('/^[a-z][a-zA-Z0-9]*$/', $methodName)) {
                                $this->report->addViolation(new Violation(
                                    'Naming Standard',
                                    "Method name '$methodName' should use camelCase (e.g., myMethod).",
                                    $this->filename,
                                    $node->getStartLine(),
                                    'warning'
                                ));
                            }
                        }
                        // Variables : camelCase
                        if ($node instanceof Node\Expr\Variable && is_string($node->name)) {
                            $varName = $node->name;
                            $superglobals = ['_GET', '_POST', '_COOKIE', '_FILES', '_SERVER', '_ENV', '_REQUEST', '_SESSION', 'GLOBALS', 'this'];

                            if (!in_array($varName, $superglobals) && !preg_match('/^[a-z][a-zA-Z0-9]*$/', $varName)) {
                                $this->report->addViolation(new Violation(
                                    'Naming Standard',
                                    "Variable '\$$varName' should use camelCase (e.g., \$myVariable) instead of snake_case.",
                                    $this->filename,
                                    $node->getStartLine(),
                                    'info'
                                ));
                            }
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
