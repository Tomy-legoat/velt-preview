<?php

namespace PreviewProtocol\Protocol;

class SequenceTracker
{
    private int $currentSequence;
    private array $acknowledgedSequences;

    public function __construct(int $initialSequence = 0)
    {
        if ($initialSequence < 0) {
            throw new \InvalidArgumentException('Initial sequence must be non-negative');
        }
        $this->currentSequence = $initialSequence;
        $this->acknowledgedSequences = [];
    }

    public function nextSequence(): int
    {
        return ++$this->currentSequence;
    }

    public function getCurrentSequence(): int
    {
        return $this->currentSequence;
    }

    public function acknowledge(int $sequence): bool
    {
        if ($sequence < 0 || $sequence > $this->currentSequence) {
            return false;
        }

        $this->acknowledgedSequences[$sequence] = true;
        return true;
    }

    public function isAcknowledged(int $sequence): bool
    {
        return isset($this->acknowledgedSequences[$sequence]);
    }

    public function getUnacknowledgedSequences(): array
    {
        $unacknowledged = [];
        for ($i = 1; $i <= $this->currentSequence; $i++) {
            if (!isset($this->acknowledgedSequences[$i])) {
                $unacknowledged[] = $i;
            }
        }
        return $unacknowledged;
    }

    public function reset(int $newSequence = 0): void
    {
        if ($newSequence < 0) {
            throw new \InvalidArgumentException('New sequence must be non-negative');
        }
        $this->currentSequence = $newSequence;
        $this->acknowledgedSequences = [];
    }

    public function resumeFrom(int $sequence): bool
    {
        if ($sequence < 0 || $sequence > $this->currentSequence) {
            return false;
        }

        // Mark all sequences up to the resume point as acknowledged
        for ($i = 0; $i <= $sequence; $i++) {
            $this->acknowledgedSequences[$i] = true;
        }

        return true;
    }
}
