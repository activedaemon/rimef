# Preprod RIMeF — rimef.active-daemon.com

Instance de **recette temporaire** sur le serveur Active Daemon, en attendant un
serveur dédié. Données de démonstration, emails capturés par Mailpit (aucun envoi
réel), accès protégé par mot de passe et non référencé.

```
Internet ─► Traefik de Seckup (seckup-traefik, ports 80/443, Let's Encrypt TLS-ALPN)
              │ réseau Docker seckup-proxy, labels des conteneurs RIMeF
              │  rimef.active-daemon.com       → rimef-preprod-nginx
              │  mail.rimef.active-daemon.com  → rimef-preprod-mailpit
              ▼
        Stack Docker « rimef-preprod » (.docker/docker-compose.preprod.yml)
          nginx (SPA + /api, mot de passe, noindex) · php-fpm · queue · MySQL · Mailpit
              │ code monté depuis /srv/rimef/preprod/current
```

RIMeF se branche sur le Traefik de Seckup comme le site Active Daemon : la
configuration de Traefik (`/home/ubuntu/seckup/.docker/prod/traefik/`) n'est pas
modifiée. Entrypoint `websecure`, certresolver `letsencrypt-tls`, middleware
`rate-limit@file` devant Mailpit.

| Élément | Fichier |
|---|---|
| Stack Docker | `.docker/docker-compose.preprod.yml` (+ `php.prod.ini`, `default.prod.conf`) |
| Déploiement | `infra/scripts/deploy.sh`, `Makefile.prod` (serveur), cibles `preprod-*` du `Makefile` (poste) |
| Modèle `.env` | `infra/templates/backend.env.preprod.example` |

Le code est déployé façon Capistrano (modèle Fruxa), jamais embarqué dans une image :

```
/srv/rimef/
  app/                      ← clone du dépôt : outillage (Makefile.prod, compose, scripts)
  preprod/
    releases/<date>/        ← clone + vendor + frontend/app/dist
    shared/  .env  storage/  compose.env  basic-auth.*   ← persistants
    current → releases/<date>
```

## Au quotidien (depuis le poste)

```bash
make preprod-deploy                     # déploie main
make preprod-deploy PREPROD_REF=ma-branche
make preprod-rollback                   # release précédente (migrations non annulées)
make preprod-credentials                # accès au site et à Mailpit
make preprod-ps
```

Chaque déploiement : clone → `composer --no-dev` → build Vite (conteneur node
jetable) → `migrate`, `TenantSeeder`, `tenants:migrate`, `tenants:seed`
(relançables) → `optimize` → bascule de `current` → redémarrage de php et du worker.

L'alias SSH (`PREPROD_SSH`, défaut `active-daemon`) et le dossier du clone
(`PREPROD_DIR`, défaut `/srv/rimef/app`) se surchargent à l'appel.

## Mise en place (une fois)

1. **DNS** : rien à faire, le wildcard `*.active-daemon.com` pointe déjà sur le serveur.
2. **Docker** : déjà en place (stack Seckup), `ubuntu` est dans le groupe `docker`.
3. **Clé de déploiement** en lecture seule sur `activedaemon/rimef` (GitHub → Deploy keys),
   déclarée dans `~/.ssh/config` sous l'alias `github-rimef`.
4. **Clone de l'outillage** :
   ```bash
   sudo mkdir -p /srv/rimef && sudo chown ubuntu:ubuntu /srv/rimef
   git clone github-rimef:activedaemon/rimef.git /srv/rimef/app
   ```
   Les déploiements clonent depuis la même URL (celle du `remote` de ce clone).
5. **Mots de passe des comptes** (facultatif) : pour les fixer dès la création, préparer
   le `.env` avant le bootstrap, qui le complète sans l'écraser :
   ```bash
   mkdir -p /srv/rimef/preprod/shared
   cp /srv/rimef/app/infra/templates/backend.env.preprod.example /srv/rimef/preprod/shared/.env
   # renseigner RIMEF_SUPERADMIN_PASSWORD et RIMEF_OLIVIA_PASSWORD
   ```
   Sinon, mot de passe aléatoire puis « Mot de passe oublié » (email dans Mailpit). Le
   seeder ne modifie jamais le mot de passe d'un compte existant.
6. **Bootstrap**, depuis le poste : `make preprod-bootstrap`.
   Il génère les secrets (`shared/compose.env`, `APP_KEY`, mot de passe du site),
   démarre la stack (Traefik émet alors les certificats) et fait le premier déploiement.

## Bon à savoir

- **Photos de personnes réelles** : non versionnées, elles sont absentes de la preprod
  (les profils concernés s'affichent sans photo).
- **`/api/dev/emails`** est disponible (hors `production`), derrière le mot de passe du site.
- **Dépendance** : la preprod passe par le Traefik de Seckup ; s'il redémarre, elle est
  coupée le temps du redémarrage.
- **Diagnostic** : `make preprod-ps`, `make -f Makefile.prod preprod-logs` sur le serveur,
  `docker logs seckup-traefik 2>&1 | grep -i rimef` (routage, certificats).
- **Retrait complet** : `docker compose -p rimef-preprod --env-file /srv/rimef/preprod/shared/compose.env -f .docker/docker-compose.preprod.yml down -v`
  (supprime la base), puis `/srv/rimef/preprod`.
- Sur un serveur dédié : un Traefik propre devant la même stack, en surchargeant
  `TRAEFIK_NETWORK`, `TRAEFIK_ENTRYPOINT` et `TRAEFIK_CERTRESOLVER` dans `compose.env`.
