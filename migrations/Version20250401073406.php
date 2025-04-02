<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250401073406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add category_id column to bucket_list_item table, create Others category, and set up foreign key constraint.';
    }

    public function up(Schema $schema): void
    {
        // Step 1: Add the category_id column if it doesn't already exist
        $this->addSql("ALTER TABLE bucket_list_item ADD category_id INT DEFAULT NULL");

        // Step 2: Ensure the "Others" category exists
        $this->addSql("INSERT INTO category (id, name) VALUES (1, 'Others') ON DUPLICATE KEY UPDATE id = id");

        // Step 3: Assign the "Others" category to all existing bucket list items
        $this->addSql("UPDATE bucket_list_item SET category_id = 1 WHERE category_id IS NULL");

        // Step 4: Modify the category_id column to NOT NULL
        $this->addSql("ALTER TABLE bucket_list_item MODIFY COLUMN category_id INT NOT NULL");

        // Step 5: Add the foreign key constraint
        $this->addSql("ALTER TABLE bucket_list_item ADD CONSTRAINT FK_74CE416C12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)");
    }

    public function down(Schema $schema): void
    {
        // Drop the foreign key constraint if it exists
        $this->addSql("ALTER TABLE bucket_list_item DROP FOREIGN KEY FK_74CE416C12469DE2");

        // Drop the category_id column if it exists
        $this->addSql("ALTER TABLE bucket_list_item DROP COLUMN category_id");

        // Drop the category table if it exists
        $this->addSql("DROP TABLE IF EXISTS category");
    }
}
