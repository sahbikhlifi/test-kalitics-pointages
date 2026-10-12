<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use DateTime;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Faker\Factory;

use function random_int;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240207125847 extends AbstractMigration
{
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE clocking DROP FOREIGN KEY FK_D3E9DCCD4431A71B');
        $this->addSql('ALTER TABLE clocking DROP FOREIGN KEY FK_D3E9DCCDA1F846FC');
        $this->addSql('DROP TABLE clocking');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE clocking (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, duration INT NOT NULL, clocking_project_id INT NOT NULL, clocking_user_id INT NOT NULL, INDEX IDX_D3E9DCCD4431A71B (clocking_project_id), INDEX IDX_D3E9DCCDA1F846FC (clocking_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, address VARCHAR(255) NOT NULL, date_end DATE DEFAULT NULL, date_start DATE NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, matricule VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D64912B2DC9C (matricule), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE clocking ADD CONSTRAINT FK_D3E9DCCD4431A71B FOREIGN KEY (clocking_project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE clocking ADD CONSTRAINT FK_D3E9DCCDA1F846FC FOREIGN KEY (clocking_user_id) REFERENCES `user` (id)');

        $faker = Factory::create();

        $sqlFormat = 'Y-m-d H:i:s';

        $nbUsers = random_int(10, 50);
        $nbProjects = random_int(10, 50);
        $nbClockings = random_int(100, 1000);

        for ($index = 1; $index <= $nbUsers; $index++) {
            $firstName = $faker->firstName;
            $lastName = $faker->lastName;
            $matricule = "{$firstName}_{$lastName}_{$index}";

            $this->addSql("INSERT INTO user (id, first_name, last_name, matricule) VALUES ($index, \"$firstName\", \"$lastName\", \"$matricule\")");
        }

        for ($index = 1; $index <= $nbProjects; $index++) {
            $dateStart = $faker->dateTimeBetween('-5 years', 'today');
            $dateEnd = null;
            if (random_int(0, 100) > 50) {
                $nbJours = random_int(10, 100);

                $dateEnd = DateTime::createFromInterface($dateStart);
                $dateEnd->modify("+ {$nbJours} days");
            }

            $address = $faker->streetName;
            $dateStart = $dateStart->format($sqlFormat);
            $dateEnd = $dateEnd === null
                ? 'NULL'
                : "'" . $dateEnd->format($sqlFormat) . "'";

            $this->addSql("INSERT INTO project (id, address, date_end, date_start, name) VALUES ($index, \"$address\", $dateEnd, '$dateStart', \"Chantier $address\")");
        }

        for ($index = 1; $index <= $nbClockings; $index++) {
            $date = $faker->dateTimeBetween('-5 years', 'today');
            $date->setTime(8, 0);

            $date = $date->format($sqlFormat);
            $duration = random_int(1, 10);
            $user = random_int(1, $nbUsers);
            $project = random_int(1, $nbProjects);

            $this->addSql("INSERT INTO clocking (id, date, duration, clocking_project_id, clocking_user_id) VALUES ($index, '$date', $duration, $project, $user)");
        }
    }
}
