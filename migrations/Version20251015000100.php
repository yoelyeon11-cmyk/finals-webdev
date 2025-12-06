<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251015000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add stock to products; reshape inventory to track product, quantity, createdAt';
    }

    public function up(Schema $schema): void
    {
        // Products: ensure stock column exists
        $this->addSql('ALTER TABLE products ADD stock INT NOT NULL DEFAULT 0');

        // Inventory: replace legacy name column with product relation, quantity and createdAt
        $this->addSql('ALTER TABLE inventory DROP name');
        $this->addSql("ALTER TABLE inventory ADD product_id INT NOT NULL, ADD quantity INT NOT NULL, ADD created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql('CREATE INDEX IDX_inventory_product ON inventory (product_id)');
        $this->addSql('ALTER TABLE inventory ADD CONSTRAINT FK_inventory_product FOREIGN KEY (product_id) REFERENCES products (id)');
    }

    public function down(Schema $schema): void
    {
        // Inventory: revert to legacy state
        $this->addSql('ALTER TABLE inventory DROP FOREIGN KEY FK_inventory_product');
        $this->addSql('DROP INDEX IDX_inventory_product ON inventory');
        $this->addSql('ALTER TABLE inventory DROP product_id, DROP quantity, DROP created_at');
        $this->addSql('ALTER TABLE inventory ADD name VARCHAR(255) NOT NULL');

        // Products: remove stock column
        $this->addSql('ALTER TABLE products DROP stock');
    }
}


