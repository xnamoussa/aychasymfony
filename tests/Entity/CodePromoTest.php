<?php

namespace App\Tests\Entity;

use App\Entity\CodePromo;
use PHPUnit\Framework\TestCase;

/**
 * Tests — CodePromo (module Restauration / Participation)
 * Règles métier testées :
 *  1. Un code expiré (date dans le passé) ne peut pas être utilisé
 *  2. Un code dont la limite d'utilisation est atteinte ne peut pas être utilisé
 *  3. Un code inactif ne peut pas être utilisé
 *  4. Un code valide sans restriction peut être utilisé
 *  5. use() incrémente le compteur si le code est valide
 *  6. use() retourne false si canBeUsed() est false
 *  7. isExpired() retourne false si aucune date d'expiration
 *  8. isLimitReached() retourne false si usageLimit = 0 (illimité)
 */
class CodePromoTest extends TestCase
{
    private function buildCode(
        bool $active = true,
        ?\DateTime $expiry = null,
        int $limit = 0,
        int $usage = 0
    ): CodePromo {
        $c = new CodePromo();
        $c->setCode('LAMMA2025');
        $c->setActive($active);
        $c->setExpirationDate($expiry);
        $c->setUsageLimit($limit);
        $c->setCurrentUsage($usage);
        $c->setDiscountPercentage(20);
        return $c;
    }

    // ---- Règle 1 : code expiré ----

    public function testCodeExpireNePeutPasEtreUtilise(): void
    {
        $code = $this->buildCode(expiry: new \DateTime('-1 day'));
        $this->assertFalse($code->canBeUsed());
    }

    public function testIsExpiredVraiSiDatePassee(): void
    {
        $code = $this->buildCode(expiry: new \DateTime('-1 day'));
        $this->assertTrue($code->isExpired());
    }

    public function testIsExpiredFauxSiDateFuture(): void
    {
        $code = $this->buildCode(expiry: new \DateTime('+1 day'));
        $this->assertFalse($code->isExpired());
    }

    // ---- Règle 2 : limite atteinte ----

    public function testLimiteAtteintNePeutPasEtreUtilise(): void
    {
        $code = $this->buildCode(limit: 10, usage: 10);
        $this->assertFalse($code->canBeUsed());
    }

    public function testIsLimitReachedVraiSiUsageEgalLimit(): void
    {
        $code = $this->buildCode(limit: 5, usage: 5);
        $this->assertTrue($code->isLimitReached());
    }

    // ---- Règle 3 : code inactif ----

    public function testCodeInactifNePeutPasEtreUtilise(): void
    {
        $code = $this->buildCode(active: false);
        $this->assertFalse($code->canBeUsed());
    }

    // ---- Règle 4 : code valide sans restriction ----

    public function testCodeValideIllimitePeutEtreUtilise(): void
    {
        $code = $this->buildCode(); // actif, pas de date, pas de limite
        $this->assertTrue($code->canBeUsed());
    }

    public function testCodeValideAvecLimitePasAtteintePeutEtreUtilise(): void
    {
        $code = $this->buildCode(limit: 10, usage: 3);
        $this->assertTrue($code->canBeUsed());
    }

    // ---- Règle 5 : use() incrémente le compteur ----

    public function testUseIncrementeCompteur(): void
    {
        $code = $this->buildCode();
        $code->use();
        $this->assertSame(1, $code->getCurrentUsage());
    }

    public function testUseMultipleFoisIncrementeCorrectement(): void
    {
        $code = $this->buildCode(limit: 50, usage: 0);
        $code->use();
        $code->use();
        $code->use();
        $this->assertSame(3, $code->getCurrentUsage());
    }

    // ---- Règle 6 : use() retourne false si invalide ----

    public function testUseSurCodeInactifRetourneFalse(): void
    {
        $code = $this->buildCode(active: false);
        $this->assertFalse($code->use());
        $this->assertSame(0, $code->getCurrentUsage());
    }

    public function testUseSurCodeExpiréRetourneFalse(): void
    {
        $code = $this->buildCode(expiry: new \DateTime('-1 day'));
        $this->assertFalse($code->use());
    }

    // ---- Règle 7 : isExpired() sans date ----

    public function testIsExpiredFauxSiPasDeDate(): void
    {
        $code = $this->buildCode(); // pas de date d'expiration
        $this->assertFalse($code->isExpired());
    }

    // ---- Règle 8 : isLimitReached() avec limit = 0 (illimité) ----

    public function testIsLimitReachedFauxSiLimiteZero(): void
    {
        $code = $this->buildCode(limit: 0, usage: 100);
        $this->assertFalse($code->isLimitReached());
    }
}