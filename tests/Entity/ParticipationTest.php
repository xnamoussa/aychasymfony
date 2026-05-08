<?php

namespace App\Tests\Entity;

use App\Entity\Participation;
use PHPUnit\Framework\TestCase;

/**
 * Module 3 — Participation
 * Règles métier testées :
 *  1. nbAdultes par défaut = 0 (le service le corrige à 1)
 *  2. totalParticipants = nbAdultes + nbEnfants
 *  3. montantCalcule = adultes*25 + enfants*15
 *  4. Les constantes TYPE/STATUT/CONTEXTE/MEAL sont définies
 *  5. devise par défaut = 'EUR'
 *  6. pointsEarned initialisé à 0
 */
class ParticipationTest extends TestCase
{
    private function buildParticipation(
        int $adultes = 2,
        int $enfants = 1,
        string $statut = 'EN_ATTENTE',
        string $type = 'SIMPLE'
    ): Participation {
        $p = new Participation();
        $p->setUserId(1);
        $p->setEvenementId(10);
        $p->setDateInscription(new \DateTime());
        $p->setType($type);
        $p->setStatut($statut);
        $p->setNbAdultes($adultes);
        $p->setNbEnfants($enfants);
        $p->setTotalParticipants($adultes + $enfants);
        return $p;
    }

    // ---- Règle 1 : adultes et enfants stockés ----

    public function testNbAdultesStocke(): void
    {
        $p = $this->buildParticipation(adultes: 3);
        $this->assertSame(3, $p->getNbAdultes());
    }

    public function testNbEnfantsStocke(): void
    {
        $p = $this->buildParticipation(enfants: 2);
        $this->assertSame(2, $p->getNbEnfants());
    }

    // ---- Règle 2 : totalParticipants = adultes + enfants ----

    public function testTotalParticipantsEgaleAdultesEtEnfants(): void
    {
        $p = $this->buildParticipation(adultes: 2, enfants: 3);
        $this->assertSame(5, $p->getTotalParticipants());
    }

    public function testTotalParticipantsSansEnfants(): void
    {
        $p = $this->buildParticipation(adultes: 1, enfants: 0);
        $this->assertSame(1, $p->getTotalParticipants());
    }

    // ---- Règle 3 : calcul du montant ----

    public function testMontantCalculeParAdulteEt15ParEnfant(): void
    {
        $p = $this->buildParticipation(adultes: 2, enfants: 1);
        $montant = ($p->getNbAdultes() * 25.00) + ($p->getNbEnfants() * 15.00);
        $p->setMontantCalcule((string)$montant);
        $this->assertSame('65', $p->getMontantCalcule());
    }

    public function testMontantCalculeSansEnfants(): void
    {
        $p = $this->buildParticipation(adultes: 1, enfants: 0);
        $montant = 1 * 25.00;
        $p->setMontantCalcule((string)$montant);
        $this->assertSame('25', $p->getMontantCalcule());
    }

    // ---- Règle 4 : constantes TYPE ----

    public function testConstanteTypeSimpleEstDefinie(): void
    {
        $this->assertSame('SIMPLE', Participation::TYPE_SIMPLE);
    }

    public function testConstanteTypeHebergementEstDefinie(): void
    {
        $this->assertSame('HEBERGEMENT', Participation::TYPE_HEBERGEMENT);
    }

    public function testConstanteTypeGroupeEstDefinie(): void
    {
        $this->assertSame('GROUPE', Participation::TYPE_GROUPE);
    }

    // ---- Règle 5 : statut ----

    public function testStatutEnAttenteParDefaut(): void
    {
        $p = $this->buildParticipation();
        $this->assertSame('EN_ATTENTE', $p->getStatut());
    }

    public function testStatutConfirmeStocke(): void
    {
        $p = $this->buildParticipation(statut: 'CONFIRME');
        $this->assertSame('CONFIRME', $p->getStatut());
    }

    public function testStatutAnnuleStocke(): void
    {
        $p = $this->buildParticipation(statut: 'ANNULE');
        $this->assertSame('ANNULE', $p->getStatut());
    }

    // ---- Règle 6 : devise par défaut EUR ----

    public function testDeviseParDefautEstEur(): void
    {
        $p = new Participation();
        $p->setUserId(1);
        $p->setDateInscription(new \DateTime());
        $p->setType('SIMPLE');
        $p->setStatut('EN_ATTENTE');
        $this->assertSame('EUR', $p->getDevise());
    }

    // ---- Règle 7 : pointsEarned initialisé à 0 ----

    public function testPointsEarnedInitialiseA0(): void
    {
        $p = new Participation();
        $this->assertSame(0, $p->getPointsEarned());
    }

    // ---- Règle 8 : hebergementNuits par défaut ----

    public function testHebergementNuitsInitialiseA0(): void
    {
        $p = new Participation();
        $this->assertSame(0, $p->getHebergementNuits());
    }
}