DC  = docker compose
PHP = $(DC) exec php

.DEFAULT_GOAL := help

help: ## Liste les commandes
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2}'

start: ## Construit et démarre PHP 8.2 + MariaDB, installe les dépendances, joue les migrations
	$(DC) up -d --build
	$(PHP) composer install --no-interaction
	$(PHP) bin/console doctrine:migrations:migrate --no-interaction

stop: ## Arrête les conteneurs
	$(DC) down

reset-db: ## Recrée la base (nouveau jeu de données aléatoire) et rejoue toutes les migrations
	$(PHP) bin/console doctrine:database:drop --force --if-exists
	$(PHP) bin/console doctrine:database:create
	$(PHP) bin/console doctrine:migrations:migrate --no-interaction

test: ## Lance la suite PHPUnit (SQLite, n'impacte pas la base de dev)
	$(PHP) bin/phpunit

cs: ## Corrige le style du code (PSR-12, PHP-CS-Fixer)
	$(PHP) vendor/bin/php-cs-fixer fix

cs-check: ## Vérifie le style du code sans modifier les fichiers
	$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff

sh: ## Shell dans le conteneur PHP
	$(PHP) bash

.PHONY: help start stop reset-db test cs cs-check sh
