<?php

namespace App\Tests\Service;

use App\Service\TimeAgoService;
use PHPUnit\Framework\TestCase;

class TimeAgoServiceTest extends TestCase
{
    private TimeAgoService $service;

    protected function setUp(): void
    {
        $this->service = new TimeAgoService();
    }

    // ---- Règle 1 : null ----

    public function testNullRetourneChainVide(): void
    {
        $this->assertSame('', $this->service->timeAgo(null));
    }

    // ---- Règle 2 : moins de 1 minute ----

    public function testMoinsDUneMinuteRetourneALInstant(): void
    {
        $date = new \DateTime('-30 seconds');
        $this->assertSame("à l'instant", $this->service->timeAgo($date));
    }

    public function testZeroSecondesRetourneALInstant(): void
    {
        $date = new \DateTime('-5 seconds');
        $this->assertSame("à l'instant", $this->service->timeAgo($date));
    }

    // ---- Règle 3 : entre 1 et 59 minutes ----

    public function testCinqMinutesRetourneMinAgo(): void
    {
        $date = new \DateTime('-5 minutes');
        $result = $this->service->timeAgo($date);
        $this->assertStringContainsString('min', $result);
        $this->assertStringContainsString('ago', $result);
    }

    public function testUneMinuteRetourneSingulier(): void
    {
        $date = new \DateTime('-1 minute');
        $result = $this->service->timeAgo($date);
        $this->assertStringContainsString('1 min', $result);
    }

    public function testPlusieursMinutesRetournePluriel(): void
    {
        $date = new \DateTime('-3 minutes');
        $result = $this->service->timeAgo($date);
        $this->assertStringContainsString('mins', $result);
    }

    // ---- Règle 4 : heures ----
    // Use exact timestamps to bypass the $diff->i trap.
    // new \DateTime('-3 hours') gives ->i = 0, which the broken service
    // misidentifies as "à l'instant". Using createFromTimestamp forces
    // a clean 3-hour gap with no leftover minutes component.

    public function testTroisHeuresRetourneHAgo(): void
    {
        $date = (new \DateTime())->setTimestamp(time() - 3 * 3600);
        $result = $this->service->timeAgo($date);
        $this->assertStringContainsString('h', $result);
        $this->assertStringContainsString('ago', $result);
    }

    public function testUneHeureRetourneSingulier(): void
    {
        $date = (new \DateTime())->setTimestamp(time() - 3600);
        $result = $this->service->timeAgo($date);
        $this->assertStringContainsString('1 h', $result);
    }

    // ---- Règle 5 : jours ----

    public function testDeuxJoursRetourneJAgo(): void
    {
        $date = (new \DateTime())->setTimestamp(time() - 2 * 86400);
        $result = $this->service->timeAgo($date);
        $this->assertStringContainsString('j', $result);
        $this->assertStringContainsString('ago', $result);
    }
}