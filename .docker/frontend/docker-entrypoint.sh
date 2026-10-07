#!/bin/sh
set -e

echo "RIMeF frontend ($(basename "$(pwd)")) - entrypoint"

# node_modules doit appartenir à l'utilisateur courant (UID/GID de l'hôte)
if [ -d "node_modules" ]; then
  if [ "$(stat -c '%u' node_modules 2>/dev/null)" != "$(id -u)" ] || [ "$(stat -c '%g' node_modules 2>/dev/null)" != "$(id -g)" ]; then
    if ! chown -R "$(id -u):$(id -g)" node_modules 2>/dev/null; then
      echo "Droits de node_modules incorrects, suppression avant réinstallation..."
      rm -rf node_modules 2>/dev/null || echo "Suppression impossible, réinstallation lancée quand même"
    fi
  fi
fi

dependencies_installed() {
  [ -f "node_modules/.package-lock.json" ] && [ -d "node_modules/vite" ]
}

if ! dependencies_installed; then
  echo "Installation des dépendances..."
  npm install
elif [ "package.json" -nt "node_modules/.package-lock.json" ]; then
  echo "package.json modifié, mise à jour des dépendances..."
  npm install
else
  echo "Dépendances déjà installées"
fi

echo "Démarrage : $*"
exec "$@"
