# Génération de message de commit

Analyse les changements Git actuels et génère un message de commit au format Keep a Changelog.

## Instructions

1. Exécute `git status --short` et `git diff --stat` pour voir les changements
2. Analyse le diff complet avec `git diff` (ou `git diff --cached` si des fichiers sont déjà staged)
3. Génère un message de commit suivant CE FORMAT EXACT :

```
type: description courte

  ### Ajouté
  - Liste des ajouts (supprime cette section si vide)


  ### Modifié
  - Liste des modifications (supprime cette section si vide)


  ### Corrigé
  - Liste des corrections (supprime cette section si vide)


  ### Supprimé
  - Liste des suppressions (supprime cette section si vide)

```

## Règles importantes

**Types de commit** :
- `feat`: Nouvelle fonctionnalité
- `fix`: Correction de bug
- `refactor`: Refactoring
- `chore`: Maintenance (release, config)
- `docs`: Documentation
- `style`: Formatage
- `perf`: Performance
- `test`: Tests

**Format** :
- Sois concis et précis
- Focus sur le "pourquoi" plutôt que le "what"
- Supprime les sections vides (### Ajouté, etc.)
- 2 espaces d'indentation pour les sections
- 2 lignes vides entre les sections
- 1 ligne vide à la fin

**Présentation** :
- Affiche le message généré dans un bloc de code pour faciliter la copie
- Demande confirmation avant de créer le commit avec `git add -A && git commit -m "..."`
