<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251001213023 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game (game_id INT AUTO_INCREMENT NOT NULL, game_master_id INT NOT NULL, game_name VARCHAR(250) NOT NULL, game_description LONGTEXT DEFAULT NULL, game_status VARCHAR(255) NOT NULL, game_max_players INT NOT NULL, game_is_public TINYINT(1) NOT NULL, game_password VARCHAR(255) DEFAULT NULL, game_invite_code VARCHAR(10) NOT NULL, game_settings JSON DEFAULT NULL, game_created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', game_updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', game_started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', game_completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_232B318CB174B310 (game_invite_code), INDEX IDX_232B318CC1151A13 (game_master_id), PRIMARY KEY(game_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE game_player (game_player_id INT AUTO_INCREMENT NOT NULL, game_id INT NOT NULL, user_id INT NOT NULL, game_player_role VARCHAR(255) NOT NULL, game_player_status VARCHAR(255) NOT NULL, game_player_joined_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', game_player_left_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_E52CD7ADE48FD905 (game_id), INDEX IDX_E52CD7ADA76ED395 (user_id), UNIQUE INDEX unique_game_user (game_id, user_id), PRIMARY KEY(game_player_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CC1151A13 FOREIGN KEY (game_master_id) REFERENCES user (user_id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE game_player ADD CONSTRAINT FK_E52CD7ADE48FD905 FOREIGN KEY (game_id) REFERENCES game (game_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game_player ADD CONSTRAINT FK_E52CD7ADA76ED395 FOREIGN KEY (user_id) REFERENCES user (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user CHANGE user_pseudo user_pseudo VARCHAR(50) NOT NULL, CHANGE user_timezone user_timezone VARCHAR(50) NOT NULL, CHANGE user_language user_language VARCHAR(10) NOT NULL, CHANGE user_created_at user_created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE user_updated_at user_updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318CC1151A13');
        $this->addSql('ALTER TABLE game_player DROP FOREIGN KEY FK_E52CD7ADE48FD905');
        $this->addSql('ALTER TABLE game_player DROP FOREIGN KEY FK_E52CD7ADA76ED395');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE game_player');
        $this->addSql('ALTER TABLE user CHANGE user_pseudo user_pseudo VARCHAR(100) NOT NULL, CHANGE user_timezone user_timezone VARCHAR(50) DEFAULT NULL, CHANGE user_language user_language VARCHAR(5) NOT NULL, CHANGE user_created_at user_created_at DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE user_updated_at user_updated_at DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\'');
    }
}
