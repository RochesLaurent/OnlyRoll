<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251001110928 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user (user_id INT AUTO_INCREMENT NOT NULL, user_email VARCHAR(180) NOT NULL, user_roles JSON NOT NULL, user_is_verified TINYINT(1) NOT NULL, user_password VARCHAR(255) NOT NULL, user_pseudo VARCHAR(100) NOT NULL, user_avatar VARCHAR(255) DEFAULT NULL, user_timezone VARCHAR(50) DEFAULT NULL, user_language VARCHAR(5) NOT NULL, user_created_at DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', user_updated_at DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', user_last_login DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (user_email), UNIQUE INDEX UNIQ_IDENTIFIER_PSEUDO (user_pseudo), PRIMARY KEY(user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE user');
    }
}
