<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250709193429 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE item ADD author_id INT'); // proponowało DEFAULT NULL po INT
        $this->addSql('UPDATE item SET author_id=2'); // nie było author_id w item, więc uzupełnione. Przypisany użytkownik z id 2
        $this->addSql('ALTER TABLE item ALTER COLUMN author_id SET NOT NULL'); // po uzupełnieniu oznaczamy jako null
        $this->addSql('ALTER TABLE item ADD CONSTRAINT FK_1F1B251EF675F31B FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_1F1B251EF675F31B ON item (author_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE item DROP CONSTRAINT FK_1F1B251EF675F31B');
        $this->addSql('DROP INDEX IDX_1F1B251EF675F31B');
        $this->addSql('ALTER TABLE item DROP author_id');
    }
}
