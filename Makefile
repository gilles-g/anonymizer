.DEFAULT_GOAL := help

PHP          := php
COMPOSER     := composer
PHPUNIT      := vendor/bin/phpunit
PHPSTAN      := vendor/bin/phpstan
PHP_CS_FIXER := vendor/bin/php-cs-fixer
RECTOR       := vendor/bin/rector

.PHONY: help
help:
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'

.PHONY: install
install: ## Installe les dependances (COMPOSER_HOME isole : le token global de la machine est invalide)
	COMPOSER_HOME=$$(mktemp -d) $(COMPOSER) install --no-interaction --no-progress

.PHONY: update
update: ## Met a jour les dependances
	COMPOSER_HOME=$$(mktemp -d) $(COMPOSER) update --no-interaction --no-progress

.PHONY: quality
quality: phpcsfix phpstan test ## Chaine complete : cs-fix, phpstan, tests

.PHONY: ci
ci: phpcscheck phpstan rector-check test ## Chaine de verification sans ecriture (comme la CI)

.PHONY: phpcsfix
phpcsfix: ## Corrige le style et pose les en-tetes de licence
	$(PHP_CS_FIXER) fix

.PHONY: phpcscheck
phpcscheck: ## Verifie le style sans rien ecrire
	$(PHP_CS_FIXER) fix --dry-run --diff

.PHONY: phpstan
phpstan: ## Analyse statique (level max)
	$(PHPSTAN) analyse --no-progress

.PHONY: phpstan-baseline
phpstan-baseline: ## Regenere la baseline PHPStan
	$(PHPSTAN) analyse --no-progress --generate-baseline

.PHONY: rector
rector: ## Applique les regles Rector
	$(RECTOR) process

.PHONY: rector-check
rector-check: ## Liste ce que Rector changerait, sans ecrire
	$(RECTOR) process --dry-run

.PHONY: test
test: ## Lance toute la suite de tests
	$(PHPUNIT)

.PHONY: test-unit
test-unit: ## Lance les tests unitaires
	$(PHPUNIT) tests/Unit

.PHONY: test-integration
test-integration: ## Lance les tests d'integration
	$(PHPUNIT) tests/Integration

.PHONY: test-filter
test-filter: ## Lance les tests correspondant a FILTER=... (ex: make test-filter FILTER=it_should_abort)
	$(PHPUNIT) --filter=$(FILTER)

.PHONY: test-file
test-file: ## Lance un fichier de test : make test-file FILE=tests/Unit/...Test.php
	$(PHPUNIT) $(FILE)

.PHONY: coverage
coverage: ## Couverture en texte (exige 100%)
	XDEBUG_MODE=coverage $(PHPUNIT) --coverage-text

.PHONY: coverage-clover
coverage-clover: ## Couverture au format clover (utilise par la CI)
	XDEBUG_MODE=coverage $(PHPUNIT) --coverage-text --coverage-clover=clover.xml

.PHONY: coverage-html
coverage-html: ## Couverture HTML dans var/coverage
	XDEBUG_MODE=coverage $(PHPUNIT) --coverage-html var/coverage

.PHONY: pool
pool: ## Genere un pool de demonstration dans var/pool.sql
	scripts/generate-pool.sh -c firstName:first,lastName:last,city:city -s 200 -o var/pool.sql

.PHONY: clean
clean: ## Vide tous les caches d'outillage
	rm -rf .phpunit.cache .php-cs-fixer.cache var/coverage
