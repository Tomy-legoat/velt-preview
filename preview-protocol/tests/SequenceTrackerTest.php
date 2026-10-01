<?php

namespace PreviewProtocol\Tests;

use PHPUnit\Framework\TestCase;
use PreviewProtocol\Protocol\SequenceTracker;

class SequenceTrackerTest extends TestCase
{
    public function testConstructorWithDefaultSequence(): void
    {
        $tracker = new SequenceTracker();
        $this->assertEquals(0, $tracker->getCurrentSequence());
    }

    public function testConstructorWithCustomSequence(): void
    {
        $tracker = new SequenceTracker(5);
        $this->assertEquals(5, $tracker->getCurrentSequence());
    }

    public function testConstructorThrowsOnNegativeSequence(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SequenceTracker(-1);
    }

    public function testNextSequence(): void
    {
        $tracker = new SequenceTracker();
        $this->assertEquals(1, $tracker->nextSequence());
        $this->assertEquals(2, $tracker->nextSequence());
        $this->assertEquals(2, $tracker->getCurrentSequence());
    }

    public function testGetCurrentSequence(): void
    {
        $tracker = new SequenceTracker(10);
        $this->assertEquals(10, $tracker->getCurrentSequence());
    }

    public function testAcknowledgeValidSequence(): void
    {
        $tracker = new SequenceTracker();
        $tracker->nextSequence();
        $this->assertTrue($tracker->acknowledge(1));
        $this->assertTrue($tracker->isAcknowledged(1));
    }

    public function testAcknowledgeInvalidSequenceTooHigh(): void
    {
        $tracker = new SequenceTracker();
        $this->assertFalse($tracker->acknowledge(100));
    }

    public function testAcknowledgeNegativeSequence(): void
    {
        $tracker = new SequenceTracker();
        $this->assertFalse($tracker->acknowledge(-1));
    }

    public function testIsAcknowledged(): void
    {
        $tracker = new SequenceTracker();
        $tracker->nextSequence();
        $tracker->acknowledge(1);
        $this->assertTrue($tracker->isAcknowledged(1));
        $this->assertFalse($tracker->isAcknowledged(0));
    }

    public function testGetUnacknowledgedSequences(): void
    {
        $tracker = new SequenceTracker();
        $tracker->nextSequence();
        $tracker->nextSequence();
        $tracker->nextSequence();
        $tracker->acknowledge(1);
        $tracker->acknowledge(3);
        
        $unacknowledged = $tracker->getUnacknowledgedSequences();
        $this->assertEquals([2], $unacknowledged);
    }

    public function testGetUnacknowledgedSequencesNone(): void
    {
        $tracker = new SequenceTracker();
        $tracker->nextSequence();
        $tracker->acknowledge(1);
        
        $unacknowledged = $tracker->getUnacknowledgedSequences();
        $this->assertEquals([], $unacknowledged);
    }

    public function testReset(): void
    {
        $tracker = new SequenceTracker();
        $tracker->nextSequence();
        $tracker->nextSequence();
        $tracker->acknowledge(1);
        
        $tracker->reset();
        
        $this->assertEquals(0, $tracker->getCurrentSequence());
        $this->assertEquals([], $tracker->getUnacknowledgedSequences());
    }

    public function testResetWithCustomSequence(): void
    {
        $tracker = new SequenceTracker();
        $tracker->nextSequence();
        $tracker->nextSequence();
        
        $tracker->reset(5);
        
        $this->assertEquals(5, $tracker->getCurrentSequence());
    }

    public function testResetThrowsOnNegativeSequence(): void
    {
        $tracker = new SequenceTracker();
        $this->expectException(\InvalidArgumentException::class);
        $tracker->reset(-1);
    }

    public function testResumeFromValidSequence(): void
    {
        $tracker = new SequenceTracker();
        $tracker->nextSequence();
        $tracker->nextSequence();
        $tracker->nextSequence();
        
        $this->assertTrue($tracker->resumeFrom(2));
        $this->assertTrue($tracker->isAcknowledged(0));
        $this->assertTrue($tracker->isAcknowledged(1));
        $this->assertTrue($tracker->isAcknowledged(2));
        $this->assertFalse($tracker->isAcknowledged(3));
    }

    public function testResumeFromInvalidSequenceTooHigh(): void
    {
        $tracker = new SequenceTracker();
        $tracker->nextSequence();
        
        $this->assertFalse($tracker->resumeFrom(100));
    }

    public function testResumeFromNegativeSequence(): void
    {
        $tracker = new SequenceTracker();
        $this->assertFalse($tracker->resumeFrom(-1));
    }

    public function testResumeFromCurrentSequence(): void
    {
        $tracker = new SequenceTracker();
        $tracker->nextSequence();
        
        $this->assertTrue($tracker->resumeFrom(1));
        $this->assertTrue($tracker->isAcknowledged(0));
        $this->assertTrue($tracker->isAcknowledged(1));
    }
}
