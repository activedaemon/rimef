# 🍃 Quasar First — Privilégier les composants Quasar

## Règle générale
Avant d'écrire du HTML natif ou un composant custom, **vérifier qu'un
composant Quasar existe**. Si oui, l'utiliser sauf justification précise.

## Composants à privilégier systématiquement

| Besoin                 | Composant Quasar              | À éviter                |
|------------------------|-------------------------------|-------------------------|
| Bouton cliquable       | `q-btn`                       | `<button>`              |
| Champ texte            | `q-input`                     | `<input>`               |
| Liste sélectionnable   | `q-list` + `q-item`           | `<button>` itérés       |
| Tableau de données     | `q-table`                     | `<table>`               |
| Modale                 | `q-dialog`                    | `<dialog>` custom       |
| Pagination             | `q-pagination`                | prev/next manuels      |
| Badge / étiquette      | `q-badge` / `q-chip`          | `<span>` stylisé        |
| Carte                  | `q-card`                      | `<div>` stylisé         |
| Panneau collapsible    | `q-expansion-item`            | `<div>` + state JS      |
| Onglets                | `q-tabs` + `q-tab`            | `<button>` itérés       |
| Tooltip                | `q-tooltip`                   | tooltip custom          |
| Spinner                | `q-spinner`                   | spinner CSS             |

## Exceptions acceptables
- Conteneur de layout pur (`<div>`, `<section>`) sans interactivité
- Texte sémantique (`<p>`, `<h1>` à `<h6>`)
- Composant métier **wrappant** un composant Quasar (ex: une pagination unifiée)

## Charte RIMeF
Quand Quasar utilise une couleur standard (ex: `color="warning"`),
**préserver la charte RIMeF** (pétrole, terracotta, safran, sauge — voir
`../rimef-handoff/design-system/tokens.json`, repris dans `frontend/*/src/css/tokens.scss`) via :
- Override CSS scoped `:deep(.q-xxx)` avec les variables de la charte
- OU configurer `quasar.variables.scss` pour aligner globalement

## Avant de créer un composant custom
1. Consulter https://quasar.dev/vue-components
2. Si rien ne convient, créer un composant qui **wrap** Quasar plutôt
   que de partir de zéro
3. Documenter la raison si on s'écarte délibérément de Quasar
