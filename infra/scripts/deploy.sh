#!/usr/bin/env bash
# =============================================================================
# RIMeF — Déploiement façon Capistrano (repris de Fruxa)
# =============================================================================
# Releases atomiques sur le filesystem hôte, montées dans les conteneurs
# (php, queue, nginx) via le symlink `current`. Aucun rebuild d'image pour
# livrer du code.
#
#   $RELEASE_ROOT/
#     releases/<date>/   ← clone git + build (composer, vite)
#     shared/  .env  storage/  compose.env  basic-auth.*   ← persistants
#     current → releases/<date>   ← bascule atomique
#
# Variables (exportées par Makefile.prod) :
#   RELEASE_ROOT   /srv/rimef/preprod
#   GIT_REPO       URL du dépôt
#   GIT_REF        branche ou tag à déployer
#   COMPOSE_FILE_PATH  chemin de docker-compose.preprod.yml
#   KEEP_RELEASES  nombre de releases conservées (défaut 3)
#
# Usage : deploy.sh {deploy|rollback}
# =============================================================================
set -euo pipefail

ACTION="${1:-deploy}"

: "${RELEASE_ROOT:?RELEASE_ROOT requis}"
: "${COMPOSE_FILE_PATH:?COMPOSE_FILE_PATH requis}"
GIT_REPO="${GIT_REPO:-}"
GIT_REF="${GIT_REF:-main}"
KEEP_RELEASES="${KEEP_RELEASES:-3}"

RELEASES_DIR="$RELEASE_ROOT/releases"
SHARED_DIR="$RELEASE_ROOT/shared"
CURRENT="$RELEASE_ROOT/current"

COMPOSE=(docker compose -p rimef-preprod --env-file "$SHARED_DIR/compose.env" -f "$COMPOSE_FILE_PATH")
# Build du frontend dans un conteneur node jetable (node absent de l'image php)
NODE_IMAGE="node:24-alpine"

log()  { printf '\033[0;36m▸ %s\033[0m\n' "$*"; }
ok()   { printf '\033[0;32m✓ %s\033[0m\n' "$*"; }
fail() { printf '\033[0;31m✗ %s\033[0m\n' "$*" >&2; exit 1; }

# Commande artisan dans le conteneur php, sur une release donnée
artisan() { "${COMPOSE[@]}" exec -T php sh -lc "cd /var/www/releases/$1/backend && php artisan $2"; }

deploy() {
  [ -n "$GIT_REPO" ] || fail "GIT_REPO requis"
  [ -f "$SHARED_DIR/.env" ] || fail "$SHARED_DIR/.env manquant (make -f Makefile.prod preprod-bootstrap)"
  mkdir -p "$RELEASES_DIR" "$SHARED_DIR/storage"

  local ts rel
  ts="$(date +%Y-%m-%d_%H-%M-%S)"
  rel="$RELEASES_DIR/$ts"
  log "Nouvelle release : $rel (ref : $GIT_REF)"

  git clone --depth 1 --branch "$GIT_REF" "$GIT_REPO" "$rel" || fail "Clone git échoué"

  # Liens RELATIFS : valables sur l'hôte comme dans les conteneurs (parent monté sous /var/www)
  log "Liens partagés (.env, storage)"
  ln -sfn "../../../shared/.env" "$rel/backend/.env"
  rm -rf "$rel/backend/storage"
  ln -sfn "../../../shared/storage" "$rel/backend/storage"

  log "composer install (sans dépendances de dev)"
  "${COMPOSE[@]}" exec -T php sh -lc "cd /var/www/releases/$ts/backend && \
    composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress" \
    || fail "composer install échoué"

  log "Build de l'espace des membres (npm ci && vite build)"
  docker run --rm \
    -v "$RELEASE_ROOT:/srv" \
    -w "/srv/releases/$ts/frontend/app" \
    --user "$(id -u):$(id -g)" \
    -e npm_config_cache=/tmp/.npm \
    "$NODE_IMAGE" sh -lc "npm ci --no-audit --no-fund && npm run build" \
    || fail "Build du frontend échoué"
  # Les dépendances node ne servent qu'au build
  rm -rf "$rel/frontend/app/node_modules"

  # Seeders relançables : tenants et domaines, rôles, comptes, données de démo
  log "Migrations et données initiales"
  artisan "$ts" "migrate --force"
  artisan "$ts" "db:seed --class=TenantSeeder --force"
  artisan "$ts" "tenants:migrate --force"
  artisan "$ts" "tenants:seed --force"

  log "Caches Laravel"
  artisan "$ts" "optimize"

  log "Bascule current → $ts"
  ln -sfnT "releases/$ts" "$CURRENT"

  # OPcache figé (validate_timestamps=0) et worker gardant le code en mémoire
  log "Redémarrage de php-fpm et du worker"
  "${COMPOSE[@]}" restart php queue

  prune_releases
  ok "Déploiement terminé → $ts"
}

rollback() {
  [ -L "$CURRENT" ] || fail "Aucune release en ligne"
  local current_target previous
  current_target="$(readlink -f "$CURRENT")"
  previous="$(ls -1dt "$RELEASES_DIR"/*/ 2>/dev/null | sed 's:/*$::' | grep -vx "$current_target" | head -n1 || true)"
  [ -n "$previous" ] || fail "Aucune release précédente"

  log "Rollback : $(basename "$current_target") → $(basename "$previous")"
  ln -sfnT "releases/$(basename "$previous")" "$CURRENT"
  "${COMPOSE[@]}" restart php queue
  ok "Rollback effectué (les migrations ne sont pas annulées)"
}

prune_releases() {
  local count
  count="$(ls -1dt "$RELEASES_DIR"/*/ 2>/dev/null | wc -l | tr -d ' ')"
  if [ "$count" -gt "$KEEP_RELEASES" ]; then
    log "Purge des anciennes releases (garde $KEEP_RELEASES / $count)"
    ls -1dt "$RELEASES_DIR"/*/ | tail -n +"$((KEEP_RELEASES + 1))" | while read -r old; do
      old="${old%/}"
      [ "$(readlink -f "$CURRENT")" = "$(readlink -f "$old")" ] && continue
      rm -rf "$old"
    done
  fi
}

case "$ACTION" in
  deploy)   deploy ;;
  rollback) rollback ;;
  *) fail "Action inconnue : $ACTION (deploy|rollback)" ;;
esac
