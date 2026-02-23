<?php

namespace Aghar\SymfonyPerfAnalyzer\Model;

class Report
{
    private array $violations = [];
    private int $score = 100;

    public function addViolation(Violation $violation): void
    {
        $this->violations[] = $violation;
        $this->score -= ($violation->severity === 'error') ? 10 : 5;
    }

    public function getViolations(): array
    {
        return $this->violations;
    }

    public function getScore(): int
    {
        return max(0, $this->score);
    }
}
