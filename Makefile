# Makefile RIMeF
# Services : Traefik + MySQL 8.4 LTS + Mailpit
# Les cibles backend et frontend sont ajoutées brique par brique.

.PHONY: help start stop down restart ps info \
        shell-mysql \
        logs logs-mysql logs-traefik logs-mailpit

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
	@echo "$(MAGENTA)Base de données :$(NC)"
	@grep -E '^[a-zA-Z_-]+:.*?## @db ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## @db "}; {printf "  $(GREEN)%-18s$(NC) %s\n", $$1, $$2}'
	@echo ""
	@echo "$(MAGENTA)Logs :$(NC)"
	@grep -E '^[a-zA-Z_-]+:.*?## @logs ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## @logs "}; {printf "  $(GREEN)%-18s$(NC) %s\n", $$1, $$2}'
	@echo ""

# ============================================================================
# Cycle de vie
# ============================================================================

start: ## @main Démarrer les services
	@if [ ! -f .docker/.env ]; then \
		echo "$(YELLOW).docker/.env absent, génération depuis .docker/.env.example...$(NC)"; \
		cp .docker/.env.example .docker/.env; \
		echo "$(GREEN).docker/.env créé$(NC)"; \
	fi
	@echo "$(BLUE)Démarrage des services...$(NC)"
	@$(COMPOSE) up -d --wait
	@echo "$(GREEN)Services démarrés$(NC)"
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
	@echo "  Point d'entrée        $(GREEN)http://rimef.localhost:9280$(NC) (frontend et API à venir)"
	@echo ""
	@echo "$(YELLOW)Base de données$(NC)"
	@echo "  MySQL 8.4 LTS         $(GREEN)127.0.0.1:9307$(NC) (user: rimef / pass: rimef / db: rimef_central)"
	@echo ""
	@echo "$(YELLOW)Outils$(NC)"
	@echo "  Dashboard Traefik     $(GREEN)http://localhost:9281$(NC)"
	@echo "  Mailpit (emails)      $(GREEN)http://localhost:9826$(NC) — SMTP : $(GREEN)127.0.0.1:9526$(NC)"
	@echo ""
	@echo "$(BLUE)Note : sur Linux et navigateurs modernes, *.localhost résout$(NC)"
	@echo "$(BLUE)       automatiquement à 127.0.0.1. Sinon, ajouter dans /etc/hosts :$(NC)"
	@echo "$(BLUE)       127.0.0.1 rimef.localhost$(NC)"
	@echo ""

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

logs-mysql: ## @logs Voir uniquement les logs de MySQL
	@$(COMPOSE) logs -f --tail=100 rimef-mysql

logs-traefik: ## @logs Voir uniquement les logs de Traefik
	@$(COMPOSE) logs -f --tail=100 rimef-traefik

logs-mailpit: ## @logs Voir uniquement les logs de Mailpit
	@$(COMPOSE) logs -f --tail=100 rimef-mailpit

.DEFAULT_GOAL := help
