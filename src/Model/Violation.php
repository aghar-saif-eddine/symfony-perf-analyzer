<?php

namespace Aghar\SymfonyPerfAnalyzer\Model;

class Violation
{
    public function __construct(
        public string $ruleName,
        public string $message,
        public string $file,
        public int $line = 0,
        public string $severity = 'warning' // 'error', 'warning', 'info'
    ) {}
}
