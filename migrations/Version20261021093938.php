<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261021093938 extends AbstractMigration
{
  public function getDescription(): string
  {
    return '';
  }

  public function up(Schema $schema): void
  {
    // this up() migration is auto-generated, please modify it to your needs
    $this->addSql(
      'CREATE TABLE ascension (id INT AUTO_INCREMENT NOT NULL, date_reussite DATETIME NOT NULL, note INT NOT NULL, commentaire LONGTEXT DEFAULT NULL, grimpeur_id INT NOT NULL, bloc_id INT NOT NULL, INDEX IDX_3D48D26D5582E9C03C7693F5 (bloc_id, date_reussite), INDEX IDX_3D48D26D86AFB02F5582E9C0 (grimpeur_id, bloc_id), INDEX IDX_3D48D26D86AFB02F (grimpeur_id), INDEX IDX_3D48D26D5582E9C0 (bloc_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4',
    );
    $this->addSql(
      'CREATE TABLE bloc (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, couleur VARCHAR(255) NOT NULL, cotation INT NOT NULL, date_ouverture DATETIME NOT NULL, date_demontage DATETIME DEFAULT NULL, nombre_reussites INT DEFAULT 0 NOT NULL, note_moyenne DOUBLE PRECISION DEFAULT NULL, secteur_id INT NOT NULL, ouvreur_id INT NOT NULL, INDEX IDX_C778955ACF16FE80996DA944 (date_demontage, cotation), INDEX IDX_C778955A9F7E4405 (secteur_id), INDEX IDX_C778955A59A76D67 (ouvreur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4',
    );
    $this->addSql(
      'CREATE TABLE secteur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8045251F6C6E55B5 (nom), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4',
    );
    $this->addSql(
      'ALTER TABLE ascension ADD CONSTRAINT FK_3D48D26D86AFB02F FOREIGN KEY (grimpeur_id) REFERENCES utilisateur (id)',
    );
    $this->addSql(
      'ALTER TABLE ascension ADD CONSTRAINT FK_3D48D26D5582E9C0 FOREIGN KEY (bloc_id) REFERENCES bloc (id)',
    );
    $this->addSql(
      'ALTER TABLE bloc ADD CONSTRAINT FK_C778955A9F7E4405 FOREIGN KEY (secteur_id) REFERENCES secteur (id)',
    );
    $this->addSql(
      'ALTER TABLE bloc ADD CONSTRAINT FK_C778955A59A76D67 FOREIGN KEY (ouvreur_id) REFERENCES utilisateur (id)',
    );
  }

  public function down(Schema $schema): void
  {
    // this down() migration is auto-generated, please modify it to your needs
    $this->addSql('ALTER TABLE ascension DROP FOREIGN KEY FK_3D48D26D86AFB02F');
    $this->addSql('ALTER TABLE ascension DROP FOREIGN KEY FK_3D48D26D5582E9C0');
    $this->addSql('ALTER TABLE bloc DROP FOREIGN KEY FK_C778955A9F7E4405');
    $this->addSql('ALTER TABLE bloc DROP FOREIGN KEY FK_C778955A59A76D67');
    $this->addSql('DROP TABLE ascension');
    $this->addSql('DROP TABLE bloc');
    $this->addSql('DROP TABLE secteur');
  }
}
