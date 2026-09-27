# Makefile RIMeF
# Services : Traefik + Backend (PHP-FPM 8.5 + Nginx) + MySQL 8.4 LTS + Mailpit
# Les cibles frontend sont ajoutées avec leur brique.

.PHONY: help start stop down restart ps info \
        artisan composer migrate fresh clear-cache tinker test pint shell-php shell-nginx \
        tenants-seed tenants-migrate \
        shell-mysql \
        logs logs-backend logs-mysql logs-traefik logs-mailpit

# Couleurs
BLUE    := \033[0;34m
GREEN   := \033[0;32m
YELLOW  := \033[0;33m
CYAN    := \033[0;36m
MAGENTA := \033[0;35m
RED     := \033[0;31m
NC      := \033[0m

# Compose : lance depuis .docker/ pour que les chemins relatifs fonctionnent
COMPOSE := cd .docker && docker compose -f docker-compose.yml

# ============================================================================
# Aide
# ============================================================================

help: ## @main Afficher ce message d'aide
	@echo "$(CYAN)═══════════════════════════════════════════════════════════════$(NC)"
	@echo "$(BLUE)RIMeF$(NC) - Réseau International des Femmes Médiatrices Francophones"
	@echo "$(CYAN)═══════════════════════════════════════════════════════════════$(NC)"
	@echo ""
	@echo "$(MAGENTA)Cycle de vie :$(NC)"
	@grep -E '^[a-zA-Z_-]+:.*?## @main ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## @main "}; {printf "  $(GREEN)%-18s$(NC) %s\n", $$1, $$2}'
	@echo ""
	@echo "$(MAGENTA)Backend :$(NC)"
	@grep -E '^[a-zA-Z_-]+:.*?## @back ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## @back "}; {printf "  $(GREEN)%-18s$(NC) %s\n", $$1, $$2}'
	@echo ""
	@echo "$(MAGENTA)Base de données :$(NC)"
	@grep -E '^[a-zA-Z_-]+:.*?## @db ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## @db "}; {printf "  $(GREEN)%-18s$(NC) %s\n", $$1, $$2}'
	@echo ""
	@echo "$(MAGENTA)Logs :$(NC)"
	@grep -E '^[a-zA-Z_-]+:.*?## @logs ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## @logs "}; {printf "  $(GREEN)%-18s$(NC) %s\n", $$1, $$2}'
	@echo ""

# ============================================================================
# Cycle de vie
# ============================================================================

start: ## @main Démarrer les services + migrer la base centrale + créer et migrer les tenants
	@if [ ! -f .docker/.env ]; then \
		echo "$(YELLOW).docker/.env absent, génération depuis .docker/.env.example...$(NC)"; \
		cp .docker/.env.example .docker/.env; \
		echo "$(GREEN).docker/.env créé$(NC)"; \
	fi
	@if [ ! -f backend/.env ]; then \
		echo "$(YELLOW)backend/.env absent, génération depuis backend/.env.example...$(NC)"; \
		cp backend/.env.example backend/.env; \
		echo "$(GREEN)backend/.env créé$(NC)"; \
	fi
	@echo "$(BLUE)Démarrage des services...$(NC)"
	@$(COMPOSE) up -d --wait
	@echo "$(GREEN)Services démarrés$(NC)"
	@$(MAKE) -s clear-cache
	@$(MAKE) -s migrate
	@$(MAKE) -s tenants-seed
	@$(MAKE) -s tenants-migrate
	@$(MAKE) -s info

stop: ## @main Arrêter les services (conserve les volumes)
	@echo "$(BLUE)Arrêt des services...$(NC)"
	@$(COMPOSE) down
	@echo "$(GREEN)Services arrêtés$(NC)"

down: ## @main Arrêter et supprimer volumes + orphelins (DESTRUCTIF, demande confirmation)
	@printf "$(RED)Cette commande supprime la base de données locale. Continuer ? [o/N] $(NC)"; \
	read answer; \
	if [ "$$answer" != "o" ] && [ "$$answer" != "O" ]; then \
		echo "$(YELLOW)Annulé$(NC)"; exit 0; \
	fi; \
	echo "$(YELLOW)Arrêt et suppression des volumes...$(NC)"; \
	$(COMPOSE) down -v --remove-orphans; \
	echo "$(GREEN)Tout supprimé$(NC)"

restart: stop start ## @main Redémarrer les services

ps: ## @main Afficher l'état des services
	@$(COMPOSE) ps

info: ## @main Afficher les URLs d'accès
	@echo ""
	@echo "$(CYAN)═══════════════════════════════════════════════════════════════$(NC)"
	@echo "$(MAGENTA)URLs accessibles$(NC)"
	@echo "$(CYAN)═══════════════════════════════════════════════════════════════$(NC)"
	@echo ""
	@echo "$(YELLOW)Application$(NC)"
	@echo "  Tenant rimef          $(GREEN)http://rimef.localhost:9280$(NC) (frontend à venir) — API : $(GREEN)/api/health$(NC)"
	@echo "  Supervision (central) $(GREEN)http://supervisor.rimef.localhost:9280$(NC) — API : $(GREEN)/api/health$(NC)"
	@echo ""
	@echo "$(YELLOW)Base de données$(NC)"
	@echo "  MySQL 8.4 LTS         $(GREEN)127.0.0.1:9307$(NC) (user: rimef / pass: rimef)"
	@echo "  Base centrale         $(GREEN)rimef_central$(NC) — tenant rimef : $(GREEN)rimef_tenant_rimef$(NC)"
	@echo ""
	@echo "$(YELLOW)Outils$(NC)"
	@echo "  Dashboard Traefik     $(GREEN)http://localhost:9281$(NC)"
	@echo "  Mailpit (emails)      $(GREEN)http://localhost:9826$(NC) — SMTP : $(GREEN)127.0.0.1:9526$(NC)"
	@echo ""
	@echo "$(BLUE)Note : sur Linux et navigateurs modernes, *.localhost résout$(NC)"
	@echo "$(BLUE)       automatiquement à 127.0.0.1. Sinon, ajouter dans /etc/hosts :$(NC)"
	@echo "$(BLUE)       127.0.0.1 rimef.localhost supervisor.rimef.localhost$(NC)"
	@echo ""

# ============================================================================
# Backend
# ============================================================================

# Usage : make artisan ARGS="route:list"
artisan: ## @back Lancer une commande artisan (ex: make artisan ARGS="route:list")
	@$(COMPOSE) exec rimef-php php artisan $(ARGS)

# Usage : make composer ARGS="require vendor/package"
composer: ## @back Lancer une commande composer (ex: make composer ARGS="require vendor/pkg")
	@$(COMPOSE) exec rimef-php composer $(ARGS)

migrate: ## @back Exécuter les migrations de la base centrale
	@echo "$(BLUE)Exécution des migrations...$(NC)"
	@$(COMPOSE) exec rimef-php php artisan migrate --force
	@echo "$(GREEN)Migrations exécutées$(NC)"

fresh: ## @back Drop + recréer la base centrale + migrer (DESTRUCTIF, demande confirmation)
	@printf "$(RED)Cette commande vide la base centrale. Continuer ? [o/N] $(NC)"; \
	read answer; \
	if [ "$$answer" != "o" ] && [ "$$answer" != "O" ]; then \
		echo "$(YELLOW)Annulé$(NC)"; exit 0; \
	fi; \
	echo "$(YELLOW)Drop + recréation de la base + migrations...$(NC)"; \
	$(COMPOSE) exec rimef-php php artisan migrate:fresh --force; \
	echo "$(GREEN)Base réinitialisée$(NC)"

tenants-seed: ## @back Créer les tenants et leurs domaines (TenantSeeder, idempotent)
	@echo "$(BLUE)Création des tenants...$(NC)"
	@$(COMPOSE) exec rimef-php php artisan db:seed --class=TenantSeeder --force
	@echo "$(GREEN)Tenants à jour$(NC)"

tenants-migrate: ## @back Exécuter les migrations de toutes les bases tenant
	@echo "$(BLUE)Migrations des tenants...$(NC)"
	@$(COMPOSE) exec rimef-php php artisan tenants:migrate
	@echo "$(GREEN)Tenants migrés$(NC)"

clear-cache: ## @back Vider les caches Laravel (config, route, view, event, compiled — sans toucher à la table cache)
	@echo "$(BLUE)Vidage des caches Laravel...$(NC)"
	@$(COMPOSE) exec rimef-php php artisan config:clear -q
	@$(COMPOSE) exec rimef-php php artisan route:clear -q
	@$(COMPOSE) exec rimef-php php artisan view:clear -q
	@$(COMPOSE) exec rimef-php php artisan event:clear -q
	@$(COMPOSE) exec rimef-php php artisan clear-compiled -q
	@echo "$(GREEN)Caches vidés$(NC)"

tinker: ## @back Ouvrir un Tinker (REPL Laravel)
	@$(COMPOSE) exec rimef-php php artisan tinker

test: ## @back Lancer les tests backend (Pest)
	@$(COMPOSE) exec rimef-php php artisan test

pint: ## @back Formater le code PHP (Pint) — ARGS="--test" pour vérifier sans modifier
	@$(COMPOSE) exec rimef-php ./vendor/bin/pint $(ARGS)

shell-php: ## @back Ouvrir un shell dans le container PHP-FPM
	@$(COMPOSE) exec rimef-php bash

shell-nginx: ## @back Ouvrir un shell dans le container Nginx
	@$(COMPOSE) exec rimef-nginx bash

# ============================================================================
# Base de données
# ============================================================================

shell-mysql: ## @db Ouvrir un client mysql sur la base centrale (DB=rimef_tenant_rimef pour un tenant)
	@$(COMPOSE) exec rimef-mysql mysql -urimef -primef $(or $(DB),rimef_central)

# ============================================================================
# Logs
# ============================================================================

logs: ## @logs Voir les logs de tous les services (suivi)
	@$(COMPOSE) logs -f --tail=100

logs-backend: ## @logs Voir les logs du backend (PHP-FPM + Nginx)
	@$(COMPOSE) logs -f --tail=100 rimef-php rimef-nginx

logs-mysql: ## @logs Voir uniquement les logs de MySQL
	@$(COMPOSE) logs -f --tail=100 rimef-mysql

logs-traefik: ## @logs Voir uniquement les logs de Traefik
	@$(COMPOSE) logs -f --tail=100 rimef-traefik

logs-mailpit: ## @logs Voir uniquement les logs de Mailpit
	@$(COMPOSE) logs -f --tail=100 rimef-mailpit

.DEFAULT_GOAL := help
