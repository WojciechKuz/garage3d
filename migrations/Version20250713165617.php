<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250713165617 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE likes_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE likes (id INT NOT NULL, who_likes_id INT NOT NULL, liked_item_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_49CA4E7D616A005 ON likes (who_likes_id)');
        $this->addSql('CREATE INDEX IDX_49CA4E7D68771114 ON likes (liked_item_id)');
        $this->addSql('ALTER TABLE likes ADD CONSTRAINT FK_49CA4E7D616A005 FOREIGN KEY (who_likes_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE likes ADD CONSTRAINT FK_49CA4E7D68771114 FOREIGN KEY (liked_item_id) REFERENCES item (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE likes_id_seq CASCADE');
        $this->addSql('ALTER TABLE likes DROP CONSTRAINT FK_49CA4E7D616A005');
        $this->addSql('ALTER TABLE likes DROP CONSTRAINT FK_49CA4E7D68771114');
        $this->addSql('DROP TABLE likes');
        $this->addSql('ALTER TABLE item ALTER author_id SET NOT NULL');
    }
}
