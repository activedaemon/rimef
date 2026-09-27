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

> Section à compléter au fur et à mesure de la mise en place du socle.
