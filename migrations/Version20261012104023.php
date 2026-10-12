<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Pointage multi-chantiers : clocking (user, date) 1..n clocking_entry (project, duration).
 *
 * Reprise des données existantes :
 * 1. chaque ancien pointage devient une ligne clocking_entry,
 * 2. les pointages d'un même collaborateur le même jour sont regroupés sur le plus ancien,
 * 3. les lignes en double sur un même chantier sont fusionnées (durées additionnées),
 * 4. les pointages devenus vides sont supprimés.
 */
final class Version20261012104023 extends AbstractMigration
{
    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_CLOCKING_USER_DATE ON clocking');
        $this->addSql('ALTER TABLE clocking ADD duration INT DEFAULT NULL, ADD clocking_project_id INT DEFAULT NULL');

        // La première ligne reste sur le pointage d'origine, les suivantes redeviennent des pointages à part entière.
        $this->addSql('UPDATE clocking c
            INNER JOIN (SELECT clocking_id, MIN(id) AS first_id FROM clocking_entry GROUP BY clocking_id) f ON f.clocking_id = c.id
            INNER JOIN clocking_entry e ON e.id = f.first_id
            SET c.clocking_project_id = e.project_id, c.duration = e.duration');
        $this->addSql('INSERT INTO clocking (date, clocking_user_id, clocking_project_id, duration)
            SELECT c.date, c.clocking_user_id, e.project_id, e.duration
            FROM clocking_entry e
            INNER JOIN clocking c ON c.id = e.clocking_id
            WHERE e.id NOT IN (SELECT first_id FROM (SELECT MIN(id) AS first_id FROM clocking_entry GROUP BY clocking_id) f)');
        $this->addSql('DELETE FROM clocking WHERE clocking_project_id IS NULL');

        $this->addSql('ALTER TABLE clocking_entry DROP FOREIGN KEY FK_64D26B05B6D103F');
        $this->addSql('ALTER TABLE clocking_entry DROP FOREIGN KEY FK_64D26B05166D1F9C');
        $this->addSql('DROP TABLE clocking_entry');

        $this->addSql('ALTER TABLE clocking MODIFY duration INT NOT NULL, MODIFY clocking_project_id INT NOT NULL');
        $this->addSql('ALTER TABLE clocking ADD CONSTRAINT FK_D3E9DCCD4431A71B FOREIGN KEY (clocking_project_id) REFERENCES project (id)');
        $this->addSql('CREATE INDEX IDX_D3E9DCCD4431A71B ON clocking (clocking_project_id)');
    }

    public function getDescription(): string
    {
        return 'Pointage multi-chantiers : extraction des lignes (chantier, durée) dans clocking_entry, un pointage par collaborateur et par jour';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE clocking_entry (duration INT NOT NULL, id INT AUTO_INCREMENT NOT NULL, clocking_id INT NOT NULL, project_id INT NOT NULL, INDEX IDX_64D26B05B6D103F (clocking_id), INDEX IDX_64D26B05166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');

        // 1. Un ancien pointage = une ligne.
        $this->addSql('INSERT INTO clocking_entry (clocking_id, project_id, duration)
            SELECT id, clocking_project_id, duration FROM clocking ORDER BY id');

        // 2. Regroupement par (collaborateur, jour) sur le pointage le plus ancien.
        $this->addSql('UPDATE clocking_entry e
            INNER JOIN clocking c ON c.id = e.clocking_id
            INNER JOIN (SELECT clocking_user_id, date, MIN(id) AS keep_id FROM clocking GROUP BY clocking_user_id, date) k
                ON k.clocking_user_id = c.clocking_user_id AND k.date = c.date
            SET e.clocking_id = k.keep_id');

        // 3. Fusion des lignes d'un même pointage sur un même chantier.
        $this->addSql('UPDATE clocking_entry e
            INNER JOIN (
                SELECT MIN(id) AS keep_id, SUM(duration) AS total
                FROM clocking_entry
                GROUP BY clocking_id, project_id
                HAVING COUNT(*) > 1
            ) d ON d.keep_id = e.id
            SET e.duration = d.total');
        $this->addSql('DELETE e FROM clocking_entry e
            INNER JOIN (SELECT clocking_id, project_id, MIN(id) AS keep_id FROM clocking_entry GROUP BY clocking_id, project_id) k
                ON k.clocking_id = e.clocking_id AND k.project_id = e.project_id
            WHERE e.id <> k.keep_id');

        // 4. Pointages vidés par le regroupement.
        $this->addSql('DELETE c FROM clocking c LEFT JOIN clocking_entry e ON e.clocking_id = c.id WHERE e.id IS NULL');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_CLOCKING_ENTRY_PROJECT ON clocking_entry (clocking_id, project_id)');
        $this->addSql('ALTER TABLE clocking_entry ADD CONSTRAINT FK_64D26B05B6D103F FOREIGN KEY (clocking_id) REFERENCES clocking (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE clocking_entry ADD CONSTRAINT FK_64D26B05166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');

        $this->addSql('ALTER TABLE clocking DROP FOREIGN KEY FK_D3E9DCCD4431A71B');
        $this->addSql('DROP INDEX IDX_D3E9DCCD4431A71B ON clocking');
        $this->addSql('ALTER TABLE clocking DROP duration, DROP clocking_project_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CLOCKING_USER_DATE ON clocking (clocking_user_id, date)');
    }
}
