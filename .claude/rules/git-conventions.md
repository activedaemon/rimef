# 🌿 Conventions Git

## Convention de commit

### Format Conventional Commits
```
<type>: <description courte>

[corps optionnel]

[footer optionnel]
```

### Types de commit

| Type | Usage | Exemple |
|------|-------|---------|
| `feat` | Nouvelle fonctionnalité | `feat: ajout du formulaire de commande` |
| `fix` | Correction de bug | `fix: validation email ne fonctionnait pas` |
| `refactor` | Refactoring sans changement fonctionnel | `refactor: extraction de la logique de validation` |
| `style` | Changements de style/CSS | `style: amélioration du responsive mobile` |
| `docs` | Documentation | `docs: mise à jour du README` |
| `test` | Ajout ou modification de tests | `test: ajout tests unitaires Order` |
| `chore` | Maintenance, config | `chore: mise à jour des dépendances` |
| `perf` | Amélioration de performance | `perf: optimisation du chargement des images` |

### Exemples concrets

```bash
# ✅ BON - Message clair et descriptif
git commit -m "feat: ajout du système de panier"
git commit -m "fix: correction du calcul de TVA sur les commandes"
git commit -m "refactor: séparation de la logique métier et du contrôleur"
git commit -m "style: amélioration de la charte graphique selon maquette"

# ❌ MAUVAIS - Messages vagues
git commit -m "update"
git commit -m "fix bug"
git commit -m "wip"
git commit -m "corrections"
```

### Corps du commit (optionnel)
```bash
git commit -m "feat: ajout du rate limiting sur l'API

Implémentation de Laravel throttle pour protéger contre
les abus de l'endpoint d'inscription. Limite : 5 requêtes
par IP toutes les 15 minutes.

Closes #12"
```

## Branches

### Convention de nommage
```
<numéro-issue>-<description-courte-kebab-case>
```

### Exemples
```bash
# ✅ BON
1-mise-en-place-socle
4-design-charte-graphique
12-feature-panier-commande
23-fix-validation-email

# ❌ MAUVAIS
nouvelle-feature
fix
dev-david
test123
```

### Types de branches

**Feature branches** : Nouvelles fonctionnalités
```bash
git checkout -b 15-feature-export-commandes-csv
```

**Fix branches** : Corrections de bugs
```bash
git checkout -b 8-fix-database-connection-timeout
```

**Refactor branches** : Refactoring
```bash
git checkout -b 20-refactor-order-service
```

## Workflow Git

### 1. Créer une nouvelle branche
```bash
# Partir de main à jour
git checkout main
git pull origin main

# Créer la nouvelle branche
git checkout -b 25-feature-export-commandes
```

### 2. Développer
```bash
# Faire des commits atomiques réguliers
git add path/to/file.php
git commit -m "feat: ajout du modèle Order"

git add path/to/other-file.php
git commit -m "feat: ajout de la route /api/orders"

# Vérifier l'état
git status
git log --oneline
```

### 3. Pousser la branche
```bash
git push origin 25-feature-export-commandes
```

### 4. Créer une Merge Request / Pull Request
- Sur GitHub / GitLab
- Source : `25-feature-export-commandes`
- Target : `main`
- Description claire des changements
- Assigner un reviewer si applicable

### 5. Après validation
```bash
# Mettre à jour main
git checkout main
git pull origin main

# Supprimer la branche locale (optionnel)
git branch -d 25-feature-export-commandes
```

## Bonnes pratiques

### Commits atomiques
```bash
# ✅ BON - Un commit = une modification logique
git commit -m "feat: ajout du champ 'address' dans le modèle User"
git commit -m "feat: validation du champ 'address'"
git commit -m "test: tests du champ 'address'"

# ❌ MAUVAIS - Commit fourre-tout
git commit -m "ajout address + validation + tests + fix bug + refactor"
```

### Fréquence des commits
```bash
# ✅ BON - Commits réguliers
# Commit après chaque étape logique terminée

# ❌ MAUVAIS
# 1 seul commit de 2000 lignes après 3 jours de dev
```

### Messages explicites
```bash
# ✅ BON - On comprend sans regarder le code
git commit -m "fix: correction du bug d'encodage UTF-8 dans les emails"

# ❌ MAUVAIS - On ne comprend rien
git commit -m "fix stuff"
```

## .gitignore

### Fichiers à ignorer
```gitignore
# Dépendances
node_modules/
vendor/

# Build
dist/
build/
*.log

# Laravel
/storage/*.key
/storage/oauth-*.key
/storage/framework/cache/data/*
/storage/framework/sessions/*
/storage/framework/views/*
/storage/logs/*
/public/storage
/public/hot

# Environnement
.env
.env.*.local
.docker/.env
*.local

# IDE
.idea/
.vscode/
*.swp

# OS
.DS_Store
Thumbs.db

# Secrets
*.pem
*.key
credentials.json
```

## Commandes utiles

### Annuler des changements
```bash
# Annuler les modifications non commitées
git checkout -- file.php

# Annuler le dernier commit (garde les changements)
git reset --soft HEAD~1

# Annuler le dernier commit (supprime les changements) ⚠️ DANGEREUX
git reset --hard HEAD~1
```

### Stash (mettre de côté)
```bash
# Sauvegarder temporairement des changements
git stash

# Récupérer les changements
git stash pop

# Lister les stash
git stash list
```

### Rebase (mise à jour propre)
```bash
# Mettre à jour votre branche avec les derniers changements de main
git checkout ma-branche
git rebase main

# En cas de conflit
# 1. Résoudre les conflits
# 2. git add les fichiers résolus
# 3. git rebase --continue
```

### Logs
```bash
# Voir l'historique simplifié
git log --oneline --graph --all

# Voir les changements d'un fichier
git log -p -- file.php

# Voir qui a modifié quoi
git blame file.php
```

## Merge Request / Pull Request - Checklist

Avant de créer une MR/PR :
- [ ] La branche compile sans erreur (`npm run build`, `composer install`)
- [ ] Les tests passent (`./vendor/bin/pest`, `npm run test`)
- [ ] Le code respecte les standards (Pint, ESLint)
- [ ] Pas de `console.log()` / `dd()` / `dump()` oubliés
- [ ] Pas de secrets/credentials
- [ ] Les commits sont propres et explicites
- [ ] La description de la MR/PR est claire

## En cas de conflit

```bash
# 1. Mettre à jour main
git checkout main
git pull origin main

# 2. Revenir sur votre branche
git checkout ma-branche

# 3. Merger main dans votre branche
git merge main

# 4. Résoudre les conflits dans les fichiers
# 5. Marquer comme résolu
git add fichier-avec-conflit.php

# 6. Finaliser le merge
git commit -m "merge: résolution des conflits avec main"

# 7. Pousser
git push origin ma-branche
```
