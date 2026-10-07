# RIMeF - Configuration Claude Code

Bienvenue dans le projet **RIMeF** — plateforme web du *Réseau International des Femmes Médiatrices Francophones*.

## 🕊 Domaine métier

RIMeF facilite l'accès à l'information, aux ressources et aux opportunités de mise en réseau pour les femmes médiatrices et les professionnelles impliquées dans les processus de paix et de médiation. La présentation complète du projet est dans le `README.md`.

### Espaces de la plateforme (d'après la maquette)

| Écran | Rôle |
|---|---|
| Accueil | Actualités du réseau, événements à venir, ressources récentes |
| Réseau | Annuaire des membres, recherche et filtres |
| Profil médiatrice | Parcours, expertises, événements auxquels elle participe |
| Agenda / Fiche événement | Événements internationaux et régionaux, participantes |
| Ressources / Fiche ressource | Rapports, analyses, publications, travaux académiques, formations |
| Modifier mon profil | Espace personnel du membre |

### Architecture multi-tenant

- La plateforme est **multi-tenant** (stancl/tenancy), avec **un seul tenant au démarrage** : `rimef`.
- Le tenant est **résolu par le domaine complet** : `rimef.localhost` (prod : `rimef.org`, à confirmer). D'autres tenants pourront recevoir un sous-domaine ou leur propre domaine.
- L'espace central de **supervision** (gestion des tenants) est sur `supervisor.rimef.localhost`.
- Deux SPA distinctes (modèle Seckup) : `frontend/app/` (membres, domaines des tenants) et `frontend/supervisor/` (supervision). Chacune appelle `/api` sur son propre domaine.
- **Tout nouveau domaine de tenant** hors `*.rimef.localhost` doit être ajouté aux règles Traefik du frontend et du backend (`.docker/docker-compose.yml`), en plus de la table `domains`.
- Bases : **`rimef_central`** (tenants, domaines, superviseurs) et **`rimef_tenant_<id>`** par tenant (données métier), soit `rimef_tenant_rimef`.
- Côté backend : API centrale dans `routes/api.php` (domaines centraux uniquement), API tenant dans `routes/tenant.php` ; migrations centrales dans `database/migrations/`, migrations tenant dans `database/migrations/tenant/`. **Toute nouvelle table métier va dans les migrations tenant.**

### Principes directeurs

- **Rapidité** : l'application doit charger vite, y compris sur réseau mobile lent.
- **Mobile first** : pleinement utilisable sur téléphone et tablette.
- **Francophonie** : interface en français, contenus internationaux.


## 📋 Règles et standards

### Validation des actions
@.claude/rules/confirmation.md

### Développement
@.claude/rules/security.md
@.claude/rules/coding-standards.md
@.claude/rules/quasar-first.md
@.claude/rules/mobile-performance.md
@.claude/rules/git-conventions.md

### Configuration
@.claude/rules/permissions.md


## 🛠 Stack technique

### Backend
- **Framework** : Laravel 13 (API), PHP 8.5 — consignes détaillées dans `backend/CLAUDE.md` (Laravel Boost)
- **Base de données** : MySQL 8.4
- **Tests** : Pest — **Formatage** : Pint
- **Email (dev)** : Mailpit

### Frontend
- **Framework** : Vue 3 (Composition API, TypeScript)
- **UI Framework** : Quasar (SPA, plugin Vite)
- **État** : Pinia — **Routage** : Vue Router (routes chargées à la demande)
- **HTTP Client** : Axios
- **Build Tool** : Vite — **Tests** : Vitest — **Qualité** : vue-tsc, ESLint, Prettier
- **Icônes** : Tabler (`@tabler/icons-webfont`, `<q-icon name="search" />`)
- **Polices** : hébergées par l'application (`@fontsource`), pas de Google Fonts
- Organisation de chaque SPA : `src/core/` (config, layouts, services) et `src/modules/<domaine>/` (views, services, router)

### Infrastructure
- **Conteneurisation** : Docker & Docker Compose
- **Reverse proxy** : Traefik (dev : `rimef.localhost`)
- **Serveur Web** : Nginx


## 🎨 Design system

La maquette de référence est dans `../rimef-handoff/` (hors dépôt) :
`design-system/` (jetons `tokens.json`, charte `README.md`, fiches de composants,
logo et icônes) et `sources/` (pages HTML statiques, `design-system.css`).

- Les jetons sont repris dans `frontend/*/src/css/tokens.scss` (variables CSS) et
  `quasar.variables.scss` (palette Quasar) : les garder alignés sur `tokens.json`.
- La supervision applique la charte en version sobre (fond ivoire, en-tête pétrole sombre).

- **Couleurs** : pétrole `#234C55` (principale), terracotta `#C86F55`, safran `#D3A64A`, sauge `#7C8B7B`, fond ivoire `#F5F1E8`
- **Typographies** : *Instrument Serif* (titres), *Inter* (texte)


## 🚀 Infrastructure & Déploiement

### Environnement de dev (`.docker/`)

Lancement depuis `App/` : `make start` (voir `make help`).

| Service | Conteneur | Accès |
|---|---|---|
| Traefik v3.7 | `rimef-traefik` | `http://rimef.localhost:9280`, dashboard `http://localhost:9281` |
| Node 24 (Vite) | `rimef-frontend` | SPA des membres (`frontend/app/`) sur `rimef.localhost` et `*.rimef.localhost` — `make npm`, `make shell-front` |
| Node 24 (Vite) | `rimef-supervisor` | SPA de supervision (`frontend/supervisor/`) sur `supervisor.rimef.localhost` — `make npm-sup`, `make shell-sup` |
| PHP-FPM 8.5 | `rimef-php` | Laravel (`backend/`) — commandes via `make artisan`, `make composer`, `make test` |
| Nginx | `rimef-nginx` | reçoit de Traefik les requêtes `/api/*` de `rimef.localhost` et `*.rimef.localhost` (dont `supervisor.`) |
| MySQL 8.4 | `rimef-mysql` | `127.0.0.1:9307` (rimef / rimef / base `rimef_central`) |
| Mailpit | `rimef-mailpit` | UI `http://localhost:9826`, SMTP `9526` |

- Ports en **92xx** pour cohabiter avec Fruxa (91xx) sur le même poste.
- MySQL stocke les dates en **UTC** ; la conversion dans le fuseau de l'utilisatrice se fait côté application.
- L'utilisateur `rimef` peut créer et supprimer les bases `rimef_tenant_*` (`.docker/mysql/init/01-tenant-grants.sql`, appliqué à la création du volume uniquement).
- **PHP n'est pas installé sur le poste** : toute commande PHP/Composer passe par le conteneur `rimef-php`.
- Les commandes npm passent par les conteneurs frontend (`make npm`, `make npm-sup`) ; vérifications : `make front-check`, `make front-test`.
- Routage Traefik : API `/api/*` priorité 200, HMR Vite 110/120, supervision 60, frontend des tenants 50.
- Serveur MCP **Laravel Boost** : `.mcp.json` à la racine, via `docker exec` (conteneurs démarrés requis).
- `make down` et `make fresh` suppriment des données : ne jamais les lancer sans accord explicite.

> Déploiement : à définir.
