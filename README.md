# Test Kalitics fullstack — Pointages multi-chantiers / multi-collaborateurs

## Démarrage rapide (Docker, recommandé)

Prérequis : Docker + `make`.

```shell
make start   # PHP 8.2 + MariaDB 10.11, composer install, migrations (avec jeu de données aléatoire)
make test    # suite PHPUnit
```

Application : http://localhost:8001 — MariaDB exposée sur `localhost:3307` (`app` / `app`).

Autres commandes : `make help` (`stop`, `reset-db`, `sh`).

## Démarrage sans Docker (procédure d'origine)

* Installer le CLI de symfony
* Préparer une base de donnée SQL (le projet a été créé avec MariaDB)

```shell
# Adapter DATABASE_URL dans .env pour votre database SQL
symfony composer install
symfony console doctrine:migrations:migrate

# Pour démarrer le serveur local
symfony serve

# Tests (SQLite, n'utilisent pas votre base)
symfony php bin/phpunit
```

## Ce qui a été réalisé

### Problème

L'ancien modèle `Clocking` = 1 collaborateur × 1 chantier × 1 jour × 1 durée obligeait à :
1. resaisir collaborateur + date pour chaque chantier de la journée,
2. resaisir le formulaire pour chaque collaborateur pointé sur le même chantier.

### Modèle de données

```
User 1──n Clocking (user, date)            unique (clocking_user_id, date)
              1──n ClockingEntry (project, duration)   unique (clocking_id, project_id)
Project 1──n ClockingEntry
```

* **Un pointage = un collaborateur, un jour**, avec 1..n lignes *(chantier, durée)*.
* La durée totale du pointage est calculée (`Clocking::getTotalDuration()`).
* Supprimer un pointage supprime ses lignes (`ON DELETE CASCADE` + `orphanRemoval`).

### Un seul formulaire pour les deux besoins

`/clockings/create` : **collaborateurs (1..n) + date (saisie une fois) + lignes chantier/durée (1..n, ajout/retrait dynamique)**.

| Saisie | Résultat en base |
|---|---|
| 1 collaborateur, 3 chantiers | 1 `clocking` + 3 `clocking_entry` (besoin 1) |
| 4 collaborateurs, 1 chantier | 4 `clocking` + 1 `clocking_entry` chacun (besoin 2) |
| 4 collaborateurs, 2 chantiers | 4 `clocking` + 2 `clocking_entry` chacun |

Si un collaborateur a **déjà un pointage ce jour-là**, les nouvelles lignes y sont **ajoutées** : on conserve une seule ligne pointage par collaborateur et par jour (`App\Service\ClockingCreator`).

### Règles de validation

* au moins un collaborateur et au moins une ligne (les lignes laissées vides sont ignorées) ;
* durée entière entre 1 et 10 h par ligne (règle d'origine conservée) ;
* pas de date future ;
* un même chantier ne peut pas apparaître deux fois dans une saisie ;
* le chantier doit être ouvert à la date pointée (entre date d'ouverture et de clôture) ;
* un collaborateur ne peut pas être pointé deux fois sur le même chantier le même jour.

Les règles transverses sont dans `App\Validator\ClockingRequestConstraint(Validator)`, les messages dans `translations/validators.fr.yaml`.

### Migration des données existantes

`migrations/Version20261012104023.php` transforme les données sans perte :
1. chaque ancien pointage devient une ligne `clocking_entry` ;
2. les pointages d'un même collaborateur le même jour sont regroupés sur le plus ancien ;
3. les lignes en double sur un même chantier sont fusionnées (durées additionnées) ;
4. les pointages vidés par le regroupement sont supprimés.

Vérifié sur le jeu de données généré : même nombre de lignes et même total d'heures avant et après. Le `down()` restaure l'ancien format (une ligne par chantier).

### Autres corrections

* **Suppression en `POST` + jeton CSRF** (c'était un lien `GET` : déclenchable par un préchargement ou une page tierce).
* Liste des pointages en **4 requêtes fixes** quelle que soit la volumétrie (total, heures, page, lignes de la page) au lieu de N+1.
* `form_start` en double dans le template de création ; libellé de date erroné (« Date de fin ») ; chantiers sans date de clôture qui affichaient la date du jour (`null|date`).

### Interface

Application métier aux **couleurs de Kalitics** (relevées sur kalitics.com) : barre de navigation marine `#11263C`, orange `#FF441B` pour l'action, bleu-vert `#147D89` pour les heures et les données, police Poppins.
Pour l'accessibilité, les textes sur fond orange utilisent une nuance plus foncée (`#D93511`), qui atteint le contraste AA (4,7:1). L'orange exact reste réservé aux éléments graphiques.

* **Menu horizontal** (Pointages, Chantiers, Collaborateurs, À propos) ; le bouton « Nouveau pointage » est dans l'en-tête de la page Pointages. Sur mobile, le menu défile horizontalement.
* **Liste des pointages** :
  * 3 indicateurs sur les résultats filtrés (pointages, heures, moyenne par pointage) ;
  * filtres côté serveur (collaborateur, chantier, période), dans l'URL donc partageables ;
  * chantiers affichés en pastilles avec leurs heures ; avec un filtre chantier, seules ses heures sont comptées ;
  * pagination par 25 ; après une suppression, retour sur la même page filtrée.
* **Formulaire en 3 étapes** (qui, quel jour, quels chantiers) avec un **récapitulatif en direct** : collaborateurs, chantiers, heures par collaborateur, barre de répartition par chantier, total. Le bouton annonce ce qui va se passer (« Enregistrer 3 pointages »).
* Recherche et compteur dans la sélection des collaborateurs ; chantiers affichés avec leur période d'ouverture ; état Ouvert / À venir / Clôturé dans la liste des chantiers.
* Erreurs sous le champ concerné, focus clavier visible, mise en page adaptée au mobile.
* Sans outil de build front : `public/css/app.css` et deux petits scripts vanilla dans `public/js/`.

## Architecture

| Fichier | Rôle |
|---|---|
| `src/Entity/Clocking.php`, `ClockingEntry.php` | Modèle |
| `src/Form/Model/ClockingRequest.php`, `ClockingEntryInput.php` | DTO de saisie (le formulaire ne manipule pas les entités directement) |
| `src/Form/CreateClockingType.php`, `ClockingEntryType.php` | Formulaire + `CollectionType` |
| `src/Validator/ClockingRequestConstraint*.php` | Règles métier transverses |
| `src/Service/ClockingCreator.php` | Saisie → un pointage par collaborateur, en transaction |
| `src/Form/ClockingFilterType.php`, `Form/Model/ClockingFilter.php` | Filtres de la liste (GET) |
| `ClockingRepository::findPageForListing()` + `ClockingListPage` | Liste filtrée et paginée, avec totaux |
| `templates/app/Clocking/create.html.twig` + `public/js/clocking-form.js` | Formulaire, lignes dynamiques (prototype Symfony), récapitulatif en direct |
| `templates/form/theme.html.twig` | Thème de formulaire (affichage des erreurs) |
| `public/css/app.css`, `public/js/table-filter.js` | Styles, filtre des listes |

## Tests

```shell
make test
```

* `tests/Unit` : entités (rattachement des lignes, total, ouverture d'un chantier).
* `tests/Integration` : `ClockingCreator` (1 collaborateur × n chantiers, n collaborateurs, complétion d'un pointage existant).
* `tests/Functional/ClockingListTest` : chaque filtre, totaux, pagination (y compris page hors limites), période invalide, suppression qui conserve les filtres.
* `tests/Functional/ClockingControllerTest` : formulaire de bout en bout (création multi-collaborateurs/multi-chantiers, chaque règle de validation, suppression POST/CSRF).

Les tests tournent sur **SQLite** (`phpunit.xml.dist`) avec un schéma recréé avant chaque test : rapides, sans dépendance à MariaDB et sans toucher à la base de dev.

## Outils utilisés

* **Docker Compose** (`compose.yaml`, `docker/php/Dockerfile`) : PHP 8.2 exactement comme demandé, et MariaDB comme le projet d'origine. Aucune installation locale de PHP, de Composer ou de base de données n'est nécessaire.
* **Makefile** : raccourcis des commandes courantes (`make help`).
* **PHP-CS-Fixer** (`.php-cs-fixer.dist.php`) : style **PSR-12** appliqué à `src/`, `tests/` et `migrations/`. `make cs` corrige, `make cs-check` vérifie sans modifier (utilisable en CI). Le code fourni suivait une convention maison (`extends` à la ligne, `if(`, `) : void`, propriétés alignées) ; il a été reformaté une fois avec l'outil, sans aucun changement de comportement.
* **`doctrine:migrations:diff`** : génération du squelette de migration, complété à la main par la reprise de données.
* **PHPUnit + WebTestCase** : tests unitaires, d'intégration et fonctionnels.
* **Claude Code (assistant IA)** : aide à l'écriture du code, des tests et de cette documentation. Les choix de conception, la migration de données et les tests ont été relus et vérifiés.

## Pistes non traitées (hors périmètre)

* Authentification / rôle « chef de chantier » : le projet n'a pas d'utilisateurs connectés, tout le monde peut pointer pour tout le monde.
* Modification d'un pointage existant (ajout/retrait de lignes après création).
* Plafond de durée journalière tous chantiers confondus (seule la règle d'origine « ≤ 10 h par ligne » est appliquée).
