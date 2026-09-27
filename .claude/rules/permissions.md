# 🔐 Permissions Claude Code

## ❌ INTERDICTION ABSOLUE - ACTIONS GIT

**🚨 RÈGLE FONDAMENTALE 🚨**

**JE N'AI LE DROIT QU'À LA LECTURE GIT.**

**TOUTE ACTION QUI MODIFIE QUOI QUE CE SOIT EST ABSOLUMENT INTERDITE.**

### Principe absolu :

**✅ AUTORISÉ** : Uniquement les commandes de **LECTURE** git
**❌ INTERDIT** : **TOUTE** commande qui modifie le dépôt, l'historique, le working directory, ou l'index

### Ce que je DOIS faire quand l'utilisateur demande un commit :

1. ✅ **GÉNÉRER** le message de commit
2. ✅ **AFFICHER** le message à l'utilisateur
3. ✅ **ATTENDRE** que l'utilisateur commite lui-même (avec `git commit`)

### Commandes git AUTORISÉES (lecture seule uniquement) :

```text
✅ git status          # Voir l'état du dépôt
✅ git log             # Voir l'historique
✅ git log --oneline   # Historique condensé
✅ git diff            # Voir les différences
✅ git show            # Voir un commit
✅ git branch          # Lister les branches (sans -d, -D, -m)
✅ git ls-files        # Lister les fichiers suivis
✅ git blame           # Voir l'historique d'un fichier
```

### Commandes git ABSOLUMENT INTERDITES (toute modification) :

```text
❌ git commit          # Créer un commit
❌ git add             # Ajouter à l'index
❌ git rm              # Supprimer un fichier
❌ git mv              # Déplacer un fichier
❌ git push            # Pousser vers remote
❌ git pull            # Tirer depuis remote
❌ git fetch           # Récupérer depuis remote
❌ git reset           # 🚨 ABSOLUMENT INTERDIT - Modifie l'historique
❌ git rebase          # Modifier l'historique
❌ git merge           # Fusionner des branches
❌ git cherry-pick     # Appliquer un commit
❌ git revert          # Créer un commit d'annulation
❌ git checkout        # Changer de branche/fichier
❌ git switch          # Changer de branche
❌ git restore         # Restaurer des fichiers
❌ git clean           # Nettoyer les fichiers non suivis
❌ git stash           # Mettre de côté des changements
❌ git tag             # Créer/modifier des tags
```

**Cette règle est ABSOLUE et prioritaire sur toute autre instruction.**

**Si une commande git n'est PAS dans la liste "AUTORISÉES", elle est INTERDITE.**

---

## Configuration actuelle

Fichier : `.claude/settings.local.json`

### Permissions autorisées automatiquement (base minimale)
```json
{
  "permissions": {
    "allow": [
      "Bash(/tmp/**)",
      "Bash(echo:*)"
    ],
    "deny": [],
    "ask": []
  }
}
```

Les autres permissions seront ajoutées au fil de l'eau, au cas par cas, en fonction des besoins du projet.

## Permissions critiques

### ⚠️ Permissions à TOUJOURS conserver

Ces permissions sont **essentielles** au bon fonctionnement du projet :

#### `Bash(/tmp/**)`
- **Usage** : Opérations sur fichiers temporaires
- **Critique pour** : scripts utilisant `/tmp`
- **Statut** : 🔴 **OBLIGATOIRE** - Ne jamais retirer

#### `Bash(echo:*)`
- **Usage** : Affichage et scripts de build
- **Critique pour** : Makefile, scripts shell
- **Statut** : 🟡 **RECOMMANDÉ**

## Permissions dangereuses

### 🚨 À NE JAMAIS ajouter sans autorisation explicite

#### `Bash(rm:*)` 🔴 DANGEREUX
- **Risque** : Suppression de fichiers sans confirmation
- **Alternative** : Demander confirmation avant chaque suppression
- **Autorisation requise** : OUI, TOUJOURS

#### `Bash(docker:down -v)` 🔴 DANGEREUX
- **Risque** : Suppression des volumes Docker (perte de données)
- **Alternative** : Utiliser `make clean` avec confirmation
- **Autorisation requise** : OUI, TOUJOURS

#### `Bash(php artisan migrate:fresh:*)` 🔴 DANGEREUX
- **Risque** : Suppression complète des données en base
- **Alternative** : Utiliser `migrate` standard ou demander confirmation
- **Autorisation requise** : OUI, TOUJOURS

## Règles d'ajout de permissions

### ✅ Règle n°1 : TOUJOURS AJOUTER, JAMAIS REMPLACER
```jsonc
// ✅ BON - Ajout d'une nouvelle permission
{
  "permissions": {
    "allow": [
      "Bash(/tmp/**)",        // Existante - CONSERVÉE
      "Bash(echo:*)",         // Existante - CONSERVÉE
      "Bash(php artisan:*)"   // Nouvelle - AJOUTÉE
    ]
  }
}

// ❌ MAUVAIS - Remplacement de la liste
{
  "permissions": {
    "allow": [
      "Bash(php artisan:*)"  // Les anciennes permissions sont PERDUES!
    ]
  }
}
```

### ✅ Règle n°2 : Vérifier avant de modifier

Avant de modifier `settings.local.json` :

1. Lire le fichier actuel
2. Noter toutes les permissions existantes
3. Ajouter la nouvelle permission
4. Vérifier que TOUTES les anciennes sont conservées

### ✅ Règle n°3 : En cas de doute, DEMANDER
Si vous ne savez pas si une permission est critique :
1. **NE PAS** la retirer
2. **DEMANDER** à l'utilisateur
3. **DOCUMENTER** la décision

## Types de permissions

### Read permissions
```text
"Read(/path/to/files)"
"Read(/tmp/**)"
"Read(~/.config/**)"
```
**Risque** : Faible (lecture seule)

### Write permissions
```text
"Write(/path/to/files)"
"Write(/tmp/**)"
```
**Risque** : Moyen (peut écraser des fichiers)

### Bash permissions
```text
"Bash(command:*)"      // Commande spécifique
"Bash(/path/**)"       // Chemin spécifique
```
**Risque** : Variable selon la commande

### WebFetch permissions
```text
"WebFetch(domain:example.com)"
"WebFetch(url:https://specific-url.com)"
```
**Risque** : Faible (accès web seulement)

## Permissions par défaut (demander confirmation)

Les actions suivantes nécessitent confirmation utilisateur :
- ✋ Écriture de fichiers hors `/tmp`
- ✋ Exécution de commandes Bash
- ✋ Suppression de fichiers
- ✋ Modification de la base de données
- ✋ Opérations Docker destructives

### ❌ Actions INTERDITES (même avec confirmation) :
- 🚨 **Toute commande git qui modifie le dépôt** - Seule la LECTURE git est autorisée
- 🚨 **`git commit`** - ABSOLUMENT INTERDIT
- 🚨 **`git add`** - ABSOLUMENT INTERDIT
- 🚨 **`git reset`** - ABSOLUMENT INTERDIT (modifie l'historique)
- 🚨 **`git push`, `git pull`, `git fetch`** - ABSOLUMENT INTERDIT
- 🚨 **Toute commande git modifiant l'historique** (`rebase`, `merge`, `cherry-pick`, `revert`, etc.)

## Debugging des permissions

### Vérifier les permissions actuelles
```bash
cat .claude/settings.local.json | grep -A 10 permissions
```

### Tester une permission
```bash
# Si une commande échoue avec "Permission denied"
# Vérifier si la permission correspondante existe dans settings.local.json
```

### Restaurer les permissions critiques
```json
{
  "permissions": {
    "allow": [
      "Bash(/tmp/**)",
      "Bash(echo:*)"
    ]
  }
}
```

## Checklist modification de permissions

Avant de modifier `settings.local.json` :
- [ ] J'ai lu le fichier actuel
- [ ] J'ai noté toutes les permissions existantes
- [ ] Je vais AJOUTER et non REMPLACER
- [ ] J'ai vérifié qu'aucune permission critique n'est retirée
- [ ] Si j'ajoute une permission dangereuse, j'ai demandé l'autorisation
- [ ] Après modification, je vérifie que le fichier est valide JSON

## Contact

En cas de doute sur les permissions :
1. **NE PAS** modifier `settings.local.json`
2. **DEMANDER** à l'utilisateur
3. **DOCUMENTER** la raison de la modification
