# Makefile RIMeF
# Les cibles Docker, backend et frontend sont ajoutées brique par brique.

.PHONY: help info

# Couleurs
BLUE    := \033[0;34m
GREEN   := \033[0;32m
CYAN    := \033[0;36m
MAGENTA := \033[0;35m
NC      := \033[0m

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

info: ## @main Afficher les informations du projet
	@echo "$(BLUE)RIMeF$(NC)"
	@echo "  Branche : $$(git rev-parse --abbrev-ref HEAD)"
	@echo "  Commit  : $$(git rev-parse --short HEAD 2>/dev/null)"

.DEFAULT_GOAL := help
