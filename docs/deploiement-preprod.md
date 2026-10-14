# Preprod RIMeF — rimef.active-daemon.com

Instance de **recette temporaire** sur le serveur Active Daemon, en attendant un
serveur dédié. Données de démonstration, emails capturés par Mailpit (aucun envoi
réel), accès protégé par mot de passe et non référencé.

```
Internet ─► ingress NGINX du K3s (Seckup) — TLS Let's Encrypt (cert-manager, HTTP-01)
              │  rimef.active-daemon.com       → 172.17.0.1:9380
              │  mail.rimef.active-daemon.com  → 172.17.0.1:9381
              ▼
        Stack Docker « rimef-preprod » (.docker/docker-compose.preprod.yml)
          nginx (SPA + /api, mot de passe, noindex) · php-fpm · queue · MySQL · Mailpit
              │ code monté depuis /srv/rimef/preprod/current
```

| Élément | Fichier |
|---|---|
| Stack Docker | `.docker/docker-compose.preprod.yml` (+ `php.prod.ini`, `default.prod.conf`) |
| Relais K3s | `infra/k3s/rimef-preprod.yaml` |
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

1. **DNS** (zone active-daemon.com) : `rimef` et `mail.rimef` en A vers `151.80.20.114`.
2. **Docker** sur le serveur (Engine + plugin Compose), utilisateur `ubuntu` dans le
   groupe `docker`. Il cohabite avec K3s (containerd) sans conflit.
3. **Clé de déploiement** en lecture seule sur `activedaemon/rimef` (GitHub → Deploy keys).
4. **Clone de l'outillage** :
   ```bash
   sudo mkdir -p /srv/rimef && sudo chown ubuntu:ubuntu /srv/rimef
   git clone git@github.com:activedaemon/rimef.git /srv/rimef/app
   ```
   Tant que l'outillage n'est pas fusionné dans `main`, faire un `git checkout` de sa
   branche dans `/srv/rimef/app`.
5. **Bootstrap**, depuis le poste : `make preprod-bootstrap`
   (si `kubectl` demande les droits root : `ssh active-daemon` puis
   `cd /srv/rimef/app && make -f Makefile.prod preprod-bootstrap KUBECTL="sudo k3s kubectl"`).

   Il génère les secrets (`shared/compose.env`, `shared/.env`, mot de passe du site),
   démarre la stack, applique le relais K3s et fait le premier déploiement.
   Pour fixer les mots de passe des comptes dès la création, renseigner
   `RIMEF_SUPERADMIN_PASSWORD` / `RIMEF_OLIVIA_PASSWORD` dans
   `/srv/rimef/preprod/shared/.env` puis relancer `make preprod-deploy` : le seeder
   ne modifie jamais le mot de passe d'un compte existant, il faut donc le faire
   avant le premier déploiement, sinon passer par « Mot de passe oublié » (Mailpit).

## Bon à savoir

- **Photos de personnes réelles** : non versionnées, elles sont absentes de la preprod
  (les profils concernés s'affichent sans photo).
- **`/api/dev/emails`** est disponible (hors `production`), derrière le mot de passe du site.
- **Vérifier le relais** si le site ne répond pas : `curl -I http://172.17.0.1:9380/nginx-health`
  sur le serveur, puis `kubectl -n rimef-preprod get ingress,certificate`. Les pods de
  l'ingress doivent joindre `172.17.0.1` (interface docker0).
- **Retrait complet** : `kubectl delete -f infra/k3s/rimef-preprod.yaml`, puis
  `docker compose -p rimef-preprod … down -v` (supprime la base) et `/srv/rimef/preprod`.
- Sur un serveur dédié, le relais K3s disparaît : un Traefik devant la même stack suffit.
