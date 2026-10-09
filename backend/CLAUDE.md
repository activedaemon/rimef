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

## Multi-tenant (stancl/tenancy)

| | Central (supervision) | Tenant (ex. `rimef`) |
|---|---|---|
| Domaine | `supervisor.rimef.localhost` (`CENTRAL_DOMAINS`) | `rimef.localhost` (`RIMEF_TENANT_DOMAIN`) |
| Base | `rimef_central` | `rimef_tenant_<id>` |
| Routes API | `routes/api.php` | `routes/tenant.php` |
| Migrations | `database/migrations/` | `database/migrations/tenant/` |
| Seeders | `DatabaseSeeder` → `TenantSeeder` | `TenantDatabaseSeeder` |

- Les données métier (membres, événements, ressources…) vont dans les **migrations tenant**.
- `make tenants-migrate` migre toutes les bases tenant ; `make tenants-seed` crée les tenants.
- Tests : SQLite en mémoire pour la base centrale ; un tenant de test crée une base SQLite temporaire, supprimée en fin de test (voir `tests/Feature/Api/TenancyHealthTest.php`). Les requêtes de test doivent viser un domaine explicite (`http://supervisor.rimef.localhost/...` ou le domaine du tenant).
- Tests dans un tenant : `uses(RefreshDatabase::class, WithTenant::class)` (`tests/Concerns/WithTenant.php`) fournit le tenant `test` sur `test.rimef.localhost`, ses rôles et `tenantUrl()`. Le tenant est fermé par `TestCase::tearDown()` avant l'annulation de la transaction.

## Emails

- Gabarit commun à la charte : thème `rimef` (`resources/views/vendor/mail/html/themes/rimef.css`), en-tête avec logo et pied de page (`resources/views/vendor/mail/`), formule d'appel et signature des notifications (`resources/views/vendor/notifications/email.blade.php`). Nom et logo : `App\Mail\MailBrand` ; le logo est joint inline par `App\Listeners\EmbedMailLogo`.
- Nouvel email : une Notification dont `toMail()` renvoie un `MailMessage` limité à `subject()`, `line()` et `action()` (ou un Mailable Markdown avec `<x-mail::message>`), puis l'ajouter au catalogue de `App\Http\Controllers\Dev\EmailPreviewController` pour le tester sur `/api/dev/emails`.

## Consignes Laravel Boost

Générées par Laravel Boost (`boost:install` / `boost:update`) : ne pas modifier `AGENTS.md` à la main.

@AGENTS.md
