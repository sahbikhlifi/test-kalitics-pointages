<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Clocking;
use App\Entity\ClockingEntry;
use PHPUnit\Framework\TestCase;

class ClockingTest extends TestCase
{
    public function testAddEntryLinksBothSidesAndSumsDurations(): void
    {
        $clocking = new Clocking();
        $first = (new ClockingEntry())->setDuration(3);
        $second = (new ClockingEntry())->setDuration(5);

        $clocking->addEntry($first);
        $clocking->addEntry($second);
        $clocking->addEntry($first);

        self::assertCount(2, $clocking->getEntries());
        self::assertSame($clocking, $first->getClocking());
        self::assertSame(8, $clocking->getTotalDuration());
    }

    public function testRemoveEntryUnlinksOwningSide(): void
    {
        $clocking = new Clocking();
        $entry = (new ClockingEntry())->setDuration(3);
        $clocking->addEntry($entry);

        $clocking->removeEntry($entry);

        self::assertCount(0, $clocking->getEntries());
        self::assertNull($entry->getClocking());
        self::assertSame(0, $clocking->getTotalDuration());
    }
}
