# RIMeF — Backend

API Laravel de **RIMeF**, la plateforme du Réseau International des Femmes Médiatrices Francophones.

Le backend est une **API pure** : l'interface est servie par le frontend Quasar (`../frontend/`).
Traefik lui transmet uniquement les requêtes `/api/*`.

## Démarrage

L'installation et l'environnement Docker sont décrits dans le [README racine](../README.md).
PHP et Composer tournent dans le conteneur `rimef-php` : rien à installer sur le poste.

## Commandes utiles

Depuis la racine `App/` :

```bash
make artisan ARGS="route:list"    # Commande artisan
make composer ARGS="require x/y"  # Commande composer
make migrate                      # Migrations
make test                         # Tests (Pest)
make pint                         # Formatage (ARGS="--test" pour vérifier)
make shell-php                    # Shell dans le conteneur PHP
```

## Points d'entrée

| Route | Rôle |
|---|---|
| `GET /api/health` | État de l'API et de la base : sur `supervisor.rimef.localhost`, contexte central (`rimef_central`) ; sur `rimef.localhost`, contexte tenant (`rimef_tenant_rimef`) |
