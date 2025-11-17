<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251005123529 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game_map (map_id INT AUTO_INCREMENT NOT NULL, game_id INT NOT NULL, map_name VARCHAR(250) NOT NULL, map_description LONGTEXT DEFAULT NULL, map_image_url VARCHAR(500) DEFAULT NULL, map_grid_size INT NOT NULL, map_grid_type VARCHAR(20) NOT NULL, map_width INT NOT NULL, map_height INT NOT NULL, map_is_active TINYINT(1) NOT NULL, map_settings JSON DEFAULT NULL, map_created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', map_updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_88F7B97EE48FD905 (game_id), PRIMARY KEY(map_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE game_message (message_id INT AUTO_INCREMENT NOT NULL, game_id INT NOT NULL, user_id INT NOT NULL, message_recipient_id INT DEFAULT NULL, message_type VARCHAR(20) NOT NULL, message_content LONGTEXT NOT NULL, message_dice_result JSON DEFAULT NULL, message_is_ic TINYINT(1) NOT NULL, message_created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_237F4F50E48FD905 (game_id), INDEX IDX_237F4F50A76ED395 (user_id), INDEX IDX_237F4F502D2EBA9E (message_recipient_id), PRIMARY KEY(message_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE game_token (token_id INT AUTO_INCREMENT NOT NULL, map_id INT NOT NULL, token_name VARCHAR(250) NOT NULL, token_type VARCHAR(20) NOT NULL, token_image_url VARCHAR(500) DEFAULT NULL, token_x INT NOT NULL, token_y INT NOT NULL, token_size NUMERIC(3, 1) NOT NULL, token_rotation INT NOT NULL, token_is_visible TINYINT(1) NOT NULL, token_is_locked TINYINT(1) NOT NULL, token_layer VARCHAR(20) NOT NULL, token_settings JSON DEFAULT NULL, token_created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', token_updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_6D04D8B453C55F64 (map_id), PRIMARY KEY(token_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE game_map ADD CONSTRAINT FK_88F7B97EE48FD905 FOREIGN KEY (game_id) REFERENCES game (game_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game_message ADD CONSTRAINT FK_237F4F50E48FD905 FOREIGN KEY (game_id) REFERENCES game (game_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game_message ADD CONSTRAINT FK_237F4F50A76ED395 FOREIGN KEY (user_id) REFERENCES user (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game_message ADD CONSTRAINT FK_237F4F502D2EBA9E FOREIGN KEY (message_recipient_id) REFERENCES user (user_id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE game_token ADD CONSTRAINT FK_6D04D8B453C55F64 FOREIGN KEY (map_id) REFERENCES game_map (map_id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_map DROP FOREIGN KEY FK_88F7B97EE48FD905');
        $this->addSql('ALTER TABLE game_message DROP FOREIGN KEY FK_237F4F50E48FD905');
        $this->addSql('ALTER TABLE game_message DROP FOREIGN KEY FK_237F4F50A76ED395');
        $this->addSql('ALTER TABLE game_message DROP FOREIGN KEY FK_237F4F502D2EBA9E');
        $this->addSql('ALTER TABLE game_token DROP FOREIGN KEY FK_6D04D8B453C55F64');
        $this->addSql('DROP TABLE game_map');
        $this->addSql('DROP TABLE game_message');
        $this->addSql('DROP TABLE game_token');
    }
}
