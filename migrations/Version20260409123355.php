<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260409123355 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE login_attempts CHANGE last_attempt_time last_attempt_time DATETIME DEFAULT NULL, CHANGE cooldown_until cooldown_until DATETIME DEFAULT NULL, CHANGE banned_until banned_until DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE motorized motorized VARCHAR(3) DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE phone phone VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE login_attempts CHANGE last_attempt_time last_attempt_time DATETIME DEFAULT \'NULL\', CHANGE cooldown_until cooldown_until DATETIME DEFAULT \'NULL\', CHANGE banned_until banned_until DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE users CHANGE motorized motorized VARCHAR(3) DEFAULT \'NULL\', CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE phone phone VARCHAR(20) DEFAULT \'NULL\'');
    }
}
