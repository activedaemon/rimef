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
- **Framework** : Laravel (API)
- **Base de données** : MySQL 8.4
- **Email (dev)** : Mailpit

### Frontend
- **Framework** : Vue 3 (Composition API, TypeScript)
- **UI Framework** : Quasar (SPA, plugin Vite)
- **État** : Pinia
- **HTTP Client** : Axios
- **Build Tool** : Vite

### Infrastructure
- **Conteneurisation** : Docker & Docker Compose
- **Reverse proxy** : Traefik (dev : `rimef.localhost`)
- **Serveur Web** : Nginx


## 🎨 Design system

La maquette de référence est dans `../design-system/` (hors dépôt) :
pages HTML statiques, tokens CSS (`src/css/rimef.css`) et assets (`public/rimef/`).

- **Couleurs** : pétrole `#234C55` (principale), terracotta `#C86F55`, safran `#D3A64A`, sauge `#7C8B7B`, fond ivoire `#F5F1E8`
- **Typographies** : *Instrument Serif* (titres), *Inter* (texte)


## 🚀 Infrastructure & Déploiement

### Environnement de dev (`.docker/`)

Lancement depuis `App/` : `make start` (voir `make help`).

| Service | Conteneur | Accès |
|---|---|---|
| Traefik v3.7 | `rimef-traefik` | `http://rimef.localhost:9280`, dashboard `http://localhost:9281` |
| MySQL 8.4 | `rimef-mysql` | `127.0.0.1:9307` (rimef / rimef / base `rimef`) |
| Mailpit | `rimef-mailpit` | UI `http://localhost:9826`, SMTP `9526` |

- Ports en **92xx** pour cohabiter avec Fruxa (91xx) sur le même poste.
- MySQL stocke les dates en **UTC** ; la conversion dans le fuseau de l'utilisatrice se fait côté application.
- `make down` supprime la base locale : ne jamais le lancer sans accord explicite.

> Déploiement : à définir.
