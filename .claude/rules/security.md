<!--suppress ALL -->
# 🔒 Règles de sécurité

## Principes fondamentaux

### OWASP — Principaux risques
Toujours vérifier et prévenir les vulnérabilités courantes :

1. **Injection SQL** ❌
   - Utiliser des requêtes préparées (Eloquent / PDO)
   - Valider et sanitizer tous les inputs
   - Ne jamais concaténer des inputs dans les requêtes

2. **XSS (Cross-Site Scripting)** ❌
   - Échapper toutes les sorties HTML (Vue le fait par défaut avec `{{ }}`)
   - Utiliser Content Security Policy
   - Sanitizer les inputs utilisateur, méfiance avec `v-html`

3. **Authentification brisée** ❌
   - Utiliser des tokens sécurisés (Sanctum, Passport)
   - Implémenter rate limiting
   - Hashage fort des mots de passe (bcrypt, argon2)

4. **Exposition de données sensibles** ❌
   - HTTPS en production
   - Chiffrement des données sensibles
   - Pas de secrets dans le code

5. **Configuration non sécurisée** ❌
   - Pas de credentials en dur
   - Variables d'environnement pour les secrets
   - `APP_DEBUG=false` en production

## Validation des inputs

### Côté Laravel (Form Requests)
```php
// ✅ BON - Form Request avec validation
public function rules(): array
{
    return [
        'email' => ['required', 'email:rfc,dns', 'max:255'],
        'name'  => ['required', 'string', 'max:100'],
    ];
}

// ❌ MAUVAIS
// Accepter $request->all() directement dans le modèle sans validation
```

### Côté Vue/Quasar
```vue
<template>
  <!-- ✅ BON - Validation Quasar -->
  <q-input
    v-model="email"
    :rules="[val => !!val || 'Requis', val => /.+@.+\..+/.test(val) || 'Email invalide']"
  ></q-input>
</template>
```

### Sanitization
```javascript
// ✅ BON
const sanitizedInput = DOMPurify.sanitize(userInput);

// ❌ MAUVAIS
const query = `SELECT * FROM users WHERE email = '${email}'`; // SQL Injection!
```

## Secrets et credentials

### Variables d'environnement
```bash
# ✅ BON - Dans .env (non versionné)
DB_PASSWORD=secure_password_here
APP_KEY=base64:...
JWT_SECRET=random_secret_key
```

```javascript
// ❌ MAUVAIS - Dans le code
const password = "my_password"; // Ne JAMAIS faire ça!
```

### Fichiers à ne JAMAIS commiter
```
.env
.env.*.local
.docker/.env
credentials.json
secrets.yaml
*.pem
*.key
id_rsa
storage/oauth-*.key
```

## Rate Limiting

### Protection contre les abus (Laravel)
```php
// ✅ Throttle sur les routes sensibles
Route::middleware('throttle:5,15')->group(function () {
    Route::post('/api/register', [AuthController::class, 'register']);
});
```

## Headers de sécurité

### Headers HTTP recommandés
```nginx
# Nginx configuration
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Content-Security-Policy "default-src 'self'" always;
```

## CORS

### Configuration sécurisée (Laravel `config/cors.php`)
```php
// ✅ BON - CORS restrictif
'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:9000')),
'supports_credentials' => true,

// ❌ MAUVAIS - CORS ouvert à tous
'allowed_origins' => ['*'], // Dangereux avec credentials!
```

## Protection CSRF

Laravel gère le CSRF automatiquement via le middleware `VerifyCsrfToken`. Pour les SPA Vue/Quasar utilisant Sanctum, le cookie XSRF-TOKEN est géré par axios.

## Logging

### Logs sécurisés
```php
// ✅ BON - Logger sans informations sensibles
Log::info('User submitted email', [
    'email' => Str::mask($email, '*', 3, 5),
    'ip' => $request->ip(),
]);

// ❌ MAUVAIS - Logger des données sensibles
Log::info('User login', [
    'password' => $password,  // Ne JAMAIS logger de passwords!
]);
```

## Base de données

### Sécurité PostgreSQL/MySQL avec Eloquent
```php
// ✅ BON - Eloquent/Query Builder (requête préparée automatiquement)
$user = User::where('email', $email)->first();
DB::select('SELECT * FROM emails WHERE email = ?', [$email]);

// ❌ MAUVAIS - Injection SQL possible
DB::select("SELECT * FROM emails WHERE email = '$email'");
```

### Permissions minimales
```sql
-- Utilisateur de base de données avec permissions limitées
GRANT SELECT, INSERT, UPDATE ON rimef.* TO rimef_app;
-- Ne pas donner DROP, DELETE inutilement
```

## Docker

### Bonnes pratiques
```dockerfile
# ✅ BON
USER www-data  # Ne pas exécuter en root
COPY --chown=www-data:www-data . /var/www/html

# ❌ MAUVAIS
USER root  # Vulnérabilité si container compromis
```

## Checklist avant commit

- [ ] Pas de secrets/credentials dans le code
- [ ] Validation de tous les inputs (Form Request côté Laravel, rules côté Quasar)
- [ ] Sanitization des sorties (méfiance avec `v-html`)
- [ ] Pas de logs de données sensibles
- [ ] Protection contre injection SQL (Eloquent / requêtes préparées)
- [ ] Protection contre XSS
- [ ] Rate limiting sur les endpoints publics
- [ ] HTTPS en production

## En cas de doute

**TOUJOURS demander une revue de sécurité** avant de merger du code qui :
- Manipule des données utilisateur
- Accède à la base de données
- Gère l'authentification
- Expose de nouveaux endpoints API
