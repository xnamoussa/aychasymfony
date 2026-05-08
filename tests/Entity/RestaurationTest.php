<?php

namespace App\Tests\Entity;

use App\Entity\ParticipationRestaurant;
use App\Entity\Restauration;
use PHPUnit\Framework\TestCase;

/**
 * Module 4 — Restauration
 * Règles métier testées :
 *  A. ParticipationRestaurant
 *     1. restrictionActive initialisé à true
 *     2. annule initialisé à false
 *     3. estActif() retourne true quand non annulé
 *     4. annuler() met annule à true et estActif() retourne false
 *     5. aRestrictionSevere() est vrai si restriction active + niveau SEVERE
 *     6. peutModifierChoix() retourne false si dateLimite non définie
 *     7. peutModifierChoix() retourne true si date limite dans le futur
 *
 *  B. Restauration (factory methods)
 *     8. factory menu() crée un objet de type MENU
 *     9. factory option() crée un objet de type OPTION
 *     10. Restauration active par défaut
 */
class RestaurationTest extends TestCase
{
    // ================= ParticipationRestaurant =================

    private function buildParticipationResto(): ParticipationRestaurant
    {
        $pr = new ParticipationRestaurant();
        $pr->setParticipantId(1);
        $pr->setEvenementId(10);
        return $pr;
    }

    // ---- Règle 1 : restrictionActive initialisé à true ----

    public function testRestrictionActiveInitialiseATrue(): void
    {
        $pr = new ParticipationRestaurant();
        $this->assertTrue($pr->isRestrictionActive());
    }

    // ---- Règle 2 : annule initialisé à false ----

    public function testNonAnnuleParDefaut(): void
    {
        $pr = new ParticipationRestaurant();
        $this->assertFalse($pr->isAnnule());
    }

    // ---- Règle 3 : estActif() quand non annulé ----

    public function testEstActifRetourneTrueQuandNonAnnule(): void
    {
        $pr = $this->buildParticipationResto();
        $this->assertTrue($pr->estActif());
    }

    // ---- Règle 4 : annuler() ----

    public function testAnnulerRendEstActifFalse(): void
    {
        $pr = $this->buildParticipationResto();
        $pr->annuler();
        $this->assertFalse($pr->estActif());
        $this->assertTrue($pr->isAnnule());
    }

    // ---- Règle 5 : aRestrictionSevere() ----

    public function testARestrictionSevereVraiSiSevereEtActive(): void
    {
        $pr = $this->buildParticipationResto();
        $pr->setRestrictionActive(true);
        $pr->setNiveauGravite(ParticipationRestaurant::GRAVITE_SEVERE);
        $this->assertTrue($pr->aRestrictionSevere());
    }

    public function testARestrictionSevereFauxSiNonSevere(): void
    {
        $pr = $this->buildParticipationResto();
        $pr->setRestrictionActive(true);
        $pr->setNiveauGravite(ParticipationRestaurant::GRAVITE_LEGERE);
        $this->assertFalse($pr->aRestrictionSevere());
    }

    public function testARestrictionSevereFauxSiInactive(): void
    {
        $pr = $this->buildParticipationResto();
        $pr->setRestrictionActive(false);
        $pr->setNiveauGravite(ParticipationRestaurant::GRAVITE_SEVERE);
        $this->assertFalse($pr->aRestrictionSevere());
    }

    // ---- Règle 6 : peutModifierChoix() sans dateLimite ----

    public function testPeutModifierChoixFauxSiPasDeDate(): void
    {
        $pr = $this->buildParticipationResto();
        $this->assertFalse($pr->peutModifierChoix());
    }

    // ---- Règle 7 : peutModifierChoix() avec date future ----

    public function testPeutModifierChoixVraiSiDateFuture(): void
    {
        $pr = $this->buildParticipationResto();
        $pr->setDateLimiteModification(new \DateTime('+1 day'));
        $this->assertTrue($pr->peutModifierChoix());
    }

    public function testPeutModifierChoixFauxSiDatePassee(): void
    {
        $pr = $this->buildParticipationResto();
        $pr->setDateLimiteModification(new \DateTime('-1 day'));
        $this->assertFalse($pr->peutModifierChoix());
    }

    // ================= Restauration factory methods =================

    // ---- Règle 8 : factory menu() ----

    public function testFactoryMenuCreeeTypeMenu(): void
    {
        $r = Restauration::menu('Menu Végétarien', 5, true);
        $this->assertSame(Restauration::TYPE_MENU, $r->getType());
        $this->assertSame('Menu Végétarien', $r->getNom());
        $this->assertTrue($r->isActif());
    }

    // ---- Règle 9 : factory option() ----

    public function testFactoryOptionCreeeTypeOption(): void
    {
        $r = Restauration::option('Option Sans Gluten', 'CULTUREL', true);
        $this->assertSame(Restauration::TYPE_OPTION, $r->getType());
        $this->assertSame('Option Sans Gluten', $r->getLibelle());
    }

    // ---- Règle 10 : actif par défaut ----

    public function testRestauratioActifParDefaut(): void
    {
        $r = new Restauration();
        $this->assertTrue($r->isActif());
    }
}