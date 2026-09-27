<!--suppress ALL -->
# 📐 Standards de code

## Principes généraux

### Clean Code
- **DRY** : Don't Repeat Yourself
- **KISS** : Keep It Simple, Stupid
- **YAGNI** : You Aren't Gonna Need It
- **Separation of Concerns** : Une fonction = une responsabilité

### Lisibilité
```javascript
// ✅ BON - Code lisible et explicite
function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

// ❌ MAUVAIS - Code obscur
function v(e) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e);
}
```

## PHP / Laravel (backend)

### Conventions de nommage
```php
// Classes : PascalCase
class UserRepository
{
    // Méthodes : camelCase
    public function findActiveUsers(): Collection
    {
        // ...
    }

    // Constantes de classe : UPPER_SNAKE_CASE
    public const MAX_EMAIL_LENGTH = 255;
}

class StoreUserRequest extends FormRequest
{
    // ...
}

// Variables : camelCase
$userEmail = 'user@example.com';

// Tables et colonnes : snake_case pluriel pour les tables
// users, email_subscriptions, user_id, created_at
```

### Structure type d'un contrôleur
```php
// ✅ BON - Contrôleur fin, logique dans des services
class UserController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function store(StoreUserRequest $request): JsonResource
    {
        $user = $this->userService->create($request->validated());
        return new UserResource($user);
    }
}

// ❌ MAUVAIS - Logique métier dans le contrôleur (200 lignes)
```

### Eloquent
```php
// ✅ BON - Scopes, relations bien typées, eager loading
class User extends Model
{
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }
}

// Usage
User::active()->with('orders')->get();

// ❌ MAUVAIS - N+1 queries
$users = User::all();
foreach ($users as $user) {
    $user->orders; // N+1
}
```

## JavaScript / TypeScript / Vue 3 (frontend)

### Conventions de nommage
```typescript
// Variables et fonctions : camelCase
const userEmail = 'user@example.com';
function validateEmail(email: string): boolean { }

// Composants Vue : PascalCase
// UserCard.vue, OrderListView.vue
// Composables : useXxx en camelCase (useAuth.ts, useOrders.ts)

// Constants : UPPER_SNAKE_CASE
const MAX_EMAIL_LENGTH = 255;
const API_BASE_URL = '/api';

// Fichiers utilitaires : kebab-case
// email-validator.ts, date-helpers.ts
```

### Composition API (recommandée)
```text
<!-- ✅ BON - <script setup> avec TypeScript -->
<script setup lang="ts">
import { ref, computed } from 'vue';

interface Props {
  userId: number;
}

const props = defineProps<Props>();
const emit = defineEmits<{ updated: [id: number] }>();

const loading = ref(false);
const isReady = computed(() => !loading.value);
</script>
```

### Fonctions
```javascript
// ✅ BON - Fonction pure, courte, explicite
function calculateTotal(items) {
  return items.reduce((sum, item) => sum + item.price, 0);
}

// ✅ BON - Gestion d'erreur claire
async function saveEmail(email) {
  try {
    if (!isValidEmail(email)) {
      throw new Error('Email invalide');
    }
    return await api.emails.create({ email });
  } catch (error) {
    logger.error('Failed to save email', { error, email: maskEmail(email) });
    throw error;
  }
}

// ❌ MAUVAIS - Fonction trop longue, fait trop de choses
function processUserDataAndSaveToDbAndSendEmailAndLogEverything() {
  // 200 lignes de code...
}
```

### Async/Await
```javascript
// ✅ BON - async/await
async function getEmails() {
  try {
    const emails = await api.emails.findAll();
    return emails;
  } catch (error) {
    throw new Error('Failed to fetch emails');
  }
}

// ❌ MAUVAIS - Callback hell / .then() imbriqués
```

### Imports
```javascript
// ✅ BON - Imports organisés
// 1. Modules natifs / framework
import { ref, computed } from 'vue';
import { useQuasar } from 'quasar';

// 2. Dépendances externes
import axios from 'axios';
import { useI18n } from 'vue-i18n';

// 3. Modules internes (alias @ pour src/)
import { useAuth } from '@/composables/useAuth';
import OrderCard from '@/components/OrderCard.vue';
```

## CSS / Quasar / Sass

### Organisation des fichiers
```
frontend/app/src/css/
├── app.scss          # Point d'entrée global
├── quasar.variables.scss  # Personnalisation Quasar (palette, typo)
├── base/             # Reset, typography
├── components/       # Styles spécifiques composants
└── utilities/        # Classes utilitaires
```

### Variables Quasar
```scss
// ✅ BON - Variables Quasar pour la cohérence
// quasar.variables.scss
$primary   : #234C55;  // Pétrole RIMeF (couleur principale)
$secondary : #C86F55;  // Terracotta
$accent    : #D3A64A;  // Safran

$dark      : #1D1D1D;
$positive  : #21BA45;
$negative  : #C10015;

.btn-primary {
  background-color: $primary;
}
```

### Scoped styles dans les composants Vue
```scss
// Dans un fichier .vue, à l'intérieur de <style lang="scss" scoped>
.user-card {
  padding: 16px;

  &__title {
    font-weight: 600;
  }

  &--highlighted {
    border-color: $primary;
  }
}
```

## HTML

### Sémantique
```html
<!-- ✅ BON - HTML sémantique -->
<header>
  <nav>
    <ul>
      <li><a href="/">Accueil</a></li>
    </ul>
  </nav>
</header>

<main>
  <section>
    <h1>Titre principal</h1>
    <article>
      <h2>Sous-titre</h2>
      <p>Contenu...</p>
    </article>
  </section>
</main>

<footer>
  <p>&copy; 2026 RIMeF</p>
</footer>
```

### Accessibilité
```html
<!-- ✅ BON - Accessible -->
<q-btn
  type="submit"
  aria-label="Valider la commande"
  color="primary"
>
  Valider
</q-btn>

<q-img
  src="/logo.png"
  alt="Logo RIMeF"
  width="200px"
/>

<!-- ❌ MAUVAIS - Non accessible -->
<div onclick="submit()">Valider</div>
```

## SQL / PostgreSQL / MySQL

### Conventions
```sql
-- ✅ BON - Requêtes lisibles
SELECT
  id,
  email,
  confirmed,
  subscribed_at
FROM users
WHERE deleted_at IS NULL
  AND confirmed = true
ORDER BY subscribed_at DESC
LIMIT 10;

-- Noms de tables : snake_case pluriel
CREATE TABLE email_subscriptions (
  id BIGSERIAL PRIMARY KEY,
  email VARCHAR(255) NOT NULL
);

-- Index : idx_[table]_[column]
CREATE INDEX idx_users_confirmed ON users(confirmed);
```

### Migrations Laravel
```php
// ✅ BON - Migrations descriptives
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->decimal('total', 10, 2);
    $table->timestamps();

    $table->index('user_id');
});
```

## Commentaires

### Quand commenter
```php
class EmailService
{
    // ✅ BON - Commentaire expliquant le "pourquoi"
    // On limite à 5 inscriptions/15min pour éviter le spam
    public const EMAIL_RATE_LIMIT = 5;

    /**
     * Enregistre un nouvel email dans la base de données
     *
     * @throws ValidationException Si l'email est invalide
     */
    public function saveEmail(string $email): User
    {
        // ...

        // ❌ MAUVAIS - Commentaire inutile (le code est explicite)
        // Incrémente le compteur
        $this->counter++;
    }
}
```

## Tests

### Structure
```php
// ✅ BON - Test Pest clair et descriptif
test('it accepts valid email addresses', function () {
    expect(isValidEmail('user@example.com'))->toBeTrue();
});

test('it rejects emails without domain', function () {
    expect(isValidEmail('user@'))->toBeFalse();
});
```

```javascript
// ✅ BON - Test Vitest clair
describe('Email Validation', () => {
  it('should accept valid email addresses', () => {
    expect(isValidEmail('user@example.com')).toBe(true);
  });
});
```

## Formatage

### PHP - Laravel Pint / PHP-CS-Fixer
```bash
./vendor/bin/pint
```

### JS/TS - Prettier
```json
{
  "semi": true,
  "trailingComma": "es5",
  "singleQuote": true,
  "printWidth": 100,
  "tabWidth": 2
}
```

### ESLint (Vue 3 + TypeScript)
```json
{
  "extends": [
    "plugin:vue/vue3-recommended",
    "@vue/typescript/recommended"
  ],
  "rules": {
    "no-console": "warn",
    "no-unused-vars": "error",
    "prefer-const": "error"
  }
}
```

## Checklist avant commit

- [ ] Code formaté (Pint pour PHP, Prettier pour JS/TS/Vue)
- [ ] Pas de `console.log()` / `dd()` / `dump()` oubliés
- [ ] Nommage cohérent et explicite
- [ ] Fonctions/méthodes courtes et focused
- [ ] Gestion d'erreur appropriée
- [ ] Commentaires sur le code complexe seulement
- [ ] HTML sémantique et accessible
- [ ] Pas de N+1 queries (eager loading)
- [ ] CSS organisé et réutilisable
