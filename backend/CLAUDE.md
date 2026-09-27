# RIMeF — Backend Laravel

Les règles générales du projet sont dans `../CLAUDE.md`.

## Exécution : tout passe par Docker

PHP et Composer ne sont **pas** installés sur le poste : ils tournent dans le conteneur `rimef-php`.
Ne jamais proposer d'installer PHP localement. Depuis la racine `App/` :

| Besoin | Commande |
|---|---|
| Artisan | `make artisan ARGS="route:list"` |
| Composer | `make composer ARGS="require vendor/package"` |
| Tests (Pest) | `make test` |
| Formatage (Pint) | `make pint` (`ARGS="--test"` pour vérifier sans modifier) |
| Shell PHP | `make shell-php` |

Toute commande `php …` ou `composer …` citée dans les consignes ci-dessous s'exécute de cette manière.

## Consignes Laravel Boost

Générées par Laravel Boost (`boost:install` / `boost:update`) : ne pas modifier `AGENTS.md` à la main.

@AGENTS.md
