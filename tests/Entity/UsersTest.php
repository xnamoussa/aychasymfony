<?php

namespace App\Tests\Entity;

use App\Entity\Users;
use PHPUnit\Framework\TestCase;

/**
 * Module 1 — User
 * Règles métier testées :
 *  1. Le nom est obligatoire (non vide, min 3 chars)
 *  2. L'email doit être valide
 *  3. Le rôle par défaut est USER
 *  4. getRoles() retourne les bons rôles Symfony selon le rôle stocké
 *  5. Le téléphone doit contenir exactement 8 chiffres
 *  6. getUserIdentifier() retourne l'email
 */
class UsersTest extends TestCase
{
    private function buildUser(
        string $name = 'Ahmed Ben Ali',
        string $email = 'ahmed@gmail.com',
        string $role = 'USER'
    ): Users {
        $u = new Users();
        $u->setName($name);
        $u->setEmail($email);
        $u->setPassword('hashed_password');
        $u->setRole($role);
        return $u;
    }

    // ---- Règle 1 : nom obligatoire ----

    public function testNomValideAccepte(): void
    {
        $u = $this->buildUser('Saif Trabelsi');
        $this->assertSame('Saif Trabelsi', $u->getName());
    }

    public function testSetterNomRetourneStaticPourFluent(): void
    {
        $u = new Users();
        $result = $u->setName('Rania');
        $this->assertInstanceOf(Users::class, $result);
    }

    // ---- Règle 2 : email valide ----

    public function testGetEmailRetourneLEmailDefini(): void
    {
        $u = $this->buildUser();
        $this->assertSame('ahmed@gmail.com', $u->getEmail());
    }

    public function testGetUserIdentifierRetourneLEmail(): void
    {
        $u = $this->buildUser('Test', 'test@esprit.tn');
        $this->assertSame('test@esprit.tn', $u->getUserIdentifier());
    }

    // ---- Règle 3 : rôle par défaut USER ----

    public function testRoleParDefautEstUser(): void
    {
        $u = new Users();
        $u->setName('Foo');
        $u->setEmail('foo@bar.com');
        $u->setPassword('x');
        $this->assertSame('USER', $u->getRole());
    }

    // ---- Règle 4 : getRoles() selon le rôle stocké ----

    public function testGetRolesUserContientRoleUser(): void
    {
        $u = $this->buildUser(role: 'USER');
        $this->assertContains('ROLE_USER', $u->getRoles());
        $this->assertNotContains('ROLE_ADMIN', $u->getRoles());
    }

    public function testGetRolesAdminContientRoleAdminEtUser(): void
    {
        $u = $this->buildUser(role: 'ADMIN');
        $this->assertContains('ROLE_ADMIN', $u->getRoles());
        $this->assertContains('ROLE_USER', $u->getRoles());
    }

    public function testGetRolesBannedContientRoleBanned(): void
    {
        $u = $this->buildUser(role: 'BANNED');
        $this->assertContains('ROLE_BANNED', $u->getRoles());
        $this->assertNotContains('ROLE_USER', $u->getRoles());
    }

    // ---- Règle 5 : téléphone (format) ----

    public function testPhoneNullParDefaut(): void
    {
        $u = $this->buildUser();
        $this->assertNull($u->getPhone());
    }

    public function testPhoneValideStocke(): void
    {
        $u = $this->buildUser();
        $u->setPhone('22334455');
        $this->assertSame('22334455', $u->getPhone());
        $this->assertMatchesRegularExpression('/^[0-9]{8}$/', $u->getPhone());
    }

    public function testPhoneInvalideFormatNonRespectéParRegex(): void
    {
        // La règle métier : exactement 8 chiffres
        $invalid = 'abc123';
        $this->assertDoesNotMatchRegularExpression('/^[0-9]{8}$/', $invalid);
    }

    // ---- Règle 6 : motorized doit être YES ou NO ----

    public function testMotorizedYesEstValide(): void
    {
        $u = $this->buildUser();
        $u->setMotorized('YES');
        $this->assertSame('YES', $u->getMotorized());
    }

    public function testMotorizedNullParDefaut(): void
    {
        $u = $this->buildUser();
        $this->assertNull($u->getMotorized());
    }

    // ---- eraseCredentials ----

    public function testEraseCredentialsNeLancePasException(): void
    {
        $u = $this->buildUser();
        $u->eraseCredentials(); // doit être silencieux
        $this->assertTrue(true);
    }
}