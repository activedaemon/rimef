# RIMeF

> **Plateforme web du Réseau International des Femmes Médiatrices Francophones**

RIMeF est une application web destinée à faciliter l'accès à l'information, aux ressources et aux opportunités de mise en réseau pour les femmes médiatrices et les professionnelles impliquées dans les processus de paix et de médiation.

La plateforme vise notamment à renforcer la visibilité des initiatives francophones, à centraliser les ressources liées à la médiation et à favoriser les échanges entre les membres du réseau.

## Objectifs du projet

L'application RIMeF a pour objectifs de :

- centraliser les informations utiles aux médiatrices ;
- faciliter l'accès aux ressources francophones sur la médiation et les processus de paix ;
- donner de la visibilité aux événements internationaux et régionaux ;
- permettre aux membres de savoir qui participe à quels événements ;
- faciliter la mise en relation entre les membres du réseau ;
- partager des rapports, analyses, publications et travaux académiques ;
- diffuser des opportunités de formation ;
- valoriser les initiatives et prises de parole autour de la médiation et de la paix ;
- construire progressivement une communauté internationale de médiatrices francophones.

## Principes directeurs

- **Rapidité** : l'application charge vite, y compris sur réseau mobile lent.
- **Mobile first** : pleinement utilisable sur téléphone et tablette.
- **Accessibilité** : contrastes, libellés explicites, navigation clavier.

## Stack technique

### Backend
- **Framework** : Laravel (API)
- **Base de données** : MySQL 8.4
- **Email (dev)** : Mailpit

### Frontend
- **Framework** : Vue 3 (Composition API, TypeScript)
- **UI Framework** : Quasar (SPA)
- **État** : Pinia
- **HTTP Client** : Axios
- **Build Tool** : Vite

### Infrastructure
- **Conteneurisation** : Docker & Docker Compose
- **Reverse proxy** : Traefik
- **Serveur Web** : Nginx

## Prérequis

- Docker & Docker Compose
- Make
- Git

## Installation

```bash
git clone git@github.com:activedaemon/rimef.git
cd rimef

# Démarrer l'environnement (crée .docker/.env depuis .docker/.env.example s'il est absent)
make start
```

Sur Linux, vérifier que `USER_ID` et `GROUP_ID` de `.docker/.env` correspondent à `id -u` et `id -g`.

## Accès à l'application

| Service | Accès |
|---|---|
| Application | http://rimef.localhost:9280 (frontend et API à venir) |
| MySQL 8.4 | `127.0.0.1:9307` — user `rimef` / pass `rimef` / base `rimef` |
| Dashboard Traefik | http://localhost:9281 |
| Mailpit (emails de dev) | http://localhost:9826 — SMTP `127.0.0.1:9526` |

Les ports (92xx) sont choisis pour cohabiter avec d'autres projets locaux.

## Commandes utiles (Make)

```bash
make help         # Liste de toutes les commandes
make start        # Démarrer les services
make stop         # Arrêter les services (conserve les données)
make restart      # Redémarrer les services
make ps           # État des services
make info         # URLs d'accès
make logs         # Logs de tous les services
make shell-mysql  # Client MySQL sur la base rimef
make down         # Tout supprimer, base comprise (DESTRUCTIF, demande confirmation)
```

## Structure du projet

```
.
├── backend/             # API Laravel                       (à venir)
├── frontend/            # SPA Vue 3 / Quasar                (à venir)
├── .docker/             # Configuration Docker (dev)
│   ├── mysql/           # Configuration MySQL (utf8mb4, UTC, mode strict)
│   ├── traefik/         # Configuration Traefik
│   └── docker-compose.yml
│
├── .claude/             # Configuration et règles Claude Code
│   ├── commands/        # Slash commands personnalisés
│   ├── rules/           # Règles de développement
│   └── skills/          # Skills personnalisés
│
├── CLAUDE.md            # Configuration Claude Code
└── Makefile             # Commandes de développement
```

## Contribution

1. Créer une branche depuis `main` selon la convention `<numéro>-<description-kebab-case>`
2. Suivre les standards du projet (`.claude/rules/`)
3. Commiter au format Conventional Commits (`feat:`, `fix:`, `chore:`…)
4. Pousser la branche et ouvrir une Pull Request vers `main`

## Sécurité

- Ne **jamais** commiter les fichiers `.env`
- Utiliser des mots de passe forts pour la base de données
- Régénérer `APP_KEY` pour chaque environnement
