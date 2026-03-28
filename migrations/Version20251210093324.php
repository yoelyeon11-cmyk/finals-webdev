<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251210093324 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE custom_cosplay_request (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, customer_name VARCHAR(255) NOT NULL, customer_email VARCHAR(255) NOT NULL, customer_phone VARCHAR(255) DEFAULT NULL, cosplay_character VARCHAR(255) NOT NULL, design_notes LONGTEXT DEFAULT NULL, reference_images JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', bust NUMERIC(5, 2) DEFAULT NULL, waist NUMERIC(5, 2) DEFAULT NULL, hip NUMERIC(5, 2) DEFAULT NULL, shoulder_width NUMERIC(5, 2) DEFAULT NULL, inseam NUMERIC(5, 2) DEFAULT NULL, height NUMERIC(5, 2) DEFAULT NULL, custom_measurements LONGTEXT DEFAULT NULL, estimated_cost NUMERIC(10, 2) DEFAULT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_997F757B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, custom_request_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, transaction_id VARCHAR(100) NOT NULL, customer_name VARCHAR(255) NOT NULL, customer_email VARCHAR(255) NOT NULL, customer_phone VARCHAR(255) DEFAULT NULL, items_description LONGTEXT NOT NULL, total_amount NUMERIC(10, 2) NOT NULL, payment_method VARCHAR(50) NOT NULL, shipping_address LONGTEXT DEFAULT NULL, status VARCHAR(50) NOT NULL, shipping_carrier VARCHAR(100) DEFAULT NULL, tracking_number VARCHAR(255) DEFAULT NULL, order_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_F52993982FC0CB0F (transaction_id), INDEX IDX_F52993987D81840F (custom_request_id), INDEX IDX_F5299398B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE custom_cosplay_request ADD CONSTRAINT FK_997F757B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993987D81840F FOREIGN KEY (custom_request_id) REFERENCES custom_cosplay_request (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE custom_cosplay_request DROP FOREIGN KEY FK_997F757B03A8386');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993987D81840F');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398B03A8386');
        $this->addSql('DROP TABLE custom_cosplay_request');
        $this->addSql('DROP TABLE `order`');
    }
}
