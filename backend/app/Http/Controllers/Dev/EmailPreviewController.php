<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dev;

use App\Enums\ContactSubject;
use App\Http\Controllers\Controller;
use App\Mail\MailBrand;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

/**
 * Prévisualisation et envoi de test des emails : DÉVELOPPEMENT UNIQUEMENT.
 *
 * Les routes associées (routes/dev.php) ne sont enregistrées qu'en dehors de
 * l'environnement de production. Les emails envoyés sont capturés par Mailpit
 * (http://localhost:9826).
 *
 * Pour ajouter un email : une entrée dans TEMPLATES et une branche dans
 * notificationFor().
 */
class EmailPreviewController extends Controller
{
    private const MAILPIT_URL = 'http://localhost:9826';

    /**
     * Catalogue des emails prévisualisables : libellé et classe source.
     *
     * @var array<string, array{name: string, source: class-string<Notification>}>
     */
    private const TEMPLATES = [
        'password-reset' => [
            'name' => 'Réinitialisation du mot de passe',
            'source' => ResetPasswordNotification::class,
        ],
        'new-message' => [
            'name' => 'Nouveau message (messagerie du réseau)',
            'source' => NewMessageNotification::class,
        ],
    ];

    /**
     * Liste les emails avec, pour chacun, l'aperçu et le formulaire d'envoi de test.
     */
    public function index(): Response
    {
        $rows = '';

        foreach (self::TEMPLATES as $key => $template) {
            $name = e($template['name']);
            $source = e($template['source']);

            $rows .= <<<HTML
                <li>
                    <a class="tpl" href="/api/dev/emails/preview/{$key}" target="_blank">
                        <span class="tpl-name">{$name}</span>
                        <span class="tpl-file">{$source}</span>
                    </a>
                    <form class="send" data-template="{$key}">
                        <label for="email-{$key}">Envoyer un test à :</label>
                        <input id="email-{$key}" type="email" name="email" placeholder="vous@example.org" required>
                        <button type="submit">Envoyer</button>
                        <div class="msg" role="status"></div>
                    </form>
                </li>
            HTML;
        }

        $mailpit = self::MAILPIT_URL;

        $html = <<<HTML
            <!DOCTYPE html>
            <html lang="fr"><head><meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>RIMeF — Test des emails</title>
            <style>
                body { font-family: Inter, -apple-system, 'Segoe UI', Arial, sans-serif; max-width: 720px; margin: 0 auto; padding: 32px 16px; color: #1f2a2e; background: #F5F1E8; }
                h1 { font-size: 24px; color: #234C55; margin: 0 0 8px; }
                p.intro { color: #5b6669; font-size: 14px; }
                .hint { font-size: 14px; color: #5b6669; background: #fff; border: 1px solid #e4ddd0; border-radius: 8px; padding: 12px 16px; }
                .hint a { color: #234C55; }
                ul { list-style: none; padding: 0; }
                li { margin: 20px 0; background: #fff; border: 1px solid #e4ddd0; border-radius: 8px; overflow: hidden; }
                a.tpl { display: block; padding: 16px 20px; text-decoration: none; color: #1f2a2e; border-bottom: 1px solid #f0ebe0; }
                a.tpl:hover, a.tpl:focus { background: #faf8f3; }
                .tpl-name { display: block; font-weight: 600; font-size: 16px; color: #234C55; }
                .tpl-file { display: block; color: #5b6669; font-size: 13px; margin-top: 4px; font-family: ui-monospace, Menlo, monospace; word-break: break-all; }
                form.send { padding: 14px 20px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
                form.send label { font-size: 14px; color: #5b6669; flex-basis: 100%; }
                form.send input { flex: 1; min-width: 0; min-height: 44px; box-sizing: border-box; padding: 8px 12px; border: 1px solid #cfc6b5; border-radius: 6px; font-size: 16px; }
                form.send button { min-height: 44px; padding: 8px 20px; background: #234C55; color: #fff; border: none; border-radius: 6px; font-size: 15px; font-weight: 600; cursor: pointer; }
                form.send button:disabled { background: #8a9a9d; cursor: not-allowed; }
                .msg { flex-basis: 100%; font-size: 14px; display: none; padding: 8px 12px; border-radius: 6px; }
                .msg.ok { display: block; background: #e8efe7; color: #3f5a3e; }
                .msg.ko { display: block; background: #f8e6e0; color: #8e3c26; }
            </style></head><body>
            <h1>Test des emails RIMeF</h1>
            <p class="intro">Prévisualisez un email ou envoyez-en un exemplaire de test. Les envois sont capturés par Mailpit.</p>
            <p class="hint">Boîte Mailpit : <a href="{$mailpit}" target="_blank">{$mailpit}</a></p>
            <ul>{$rows}</ul>
            <script>
                document.querySelectorAll("form.send").forEach((form) => {
                    form.addEventListener("submit", async (event) => {
                        event.preventDefault();
                        const input = form.querySelector("input");
                        const button = form.querySelector("button");
                        const msg = form.querySelector(".msg");
                        button.disabled = true; button.textContent = "Envoi…"; msg.className = "msg";
                        try {
                            const res = await fetch("/api/dev/emails/send/" + form.dataset.template, {
                                method: "POST",
                                headers: { "Content-Type": "application/json", "Accept": "application/json" },
                                body: JSON.stringify({ email: input.value }),
                            });
                            const data = await res.json();
                            const success = res.ok && data.success;
                            msg.textContent = (success ? "✓ " : "✗ ") + data.message;
                            msg.className = "msg " + (success ? "ok" : "ko");
                            if (success) input.value = "";
                        } catch (error) {
                            msg.textContent = "✗ Erreur réseau lors de l'envoi.";
                            msg.className = "msg ko";
                        } finally {
                            button.disabled = false; button.textContent = "Envoyer";
                        }
                    });
                });
            </script>
            </body></html>
        HTML;

        return response($html);
    }

    /**
     * Rendu navigateur d'un email avec des données fictives.
     */
    public function show(string $template): Response
    {
        abort_unless(array_key_exists($template, self::TEMPLATES), 404, "Email « {$template} » introuvable.");

        $recipient = $this->fakeRecipient();
        $mail = $this->notificationFor($template)->toMail($recipient);

        abort_unless($mail instanceof MailMessage, 500, 'Aperçu disponible pour les MailMessage uniquement.');

        return response(MailBrand::inlineLogoForBrowser((string) $mail->render()));
    }

    /**
     * Envoie un email de test à l'adresse fournie (capturé par Mailpit).
     */
    public function send(Request $request, string $template): JsonResponse
    {
        if (! array_key_exists($template, self::TEMPLATES)) {
            return response()->json(['success' => false, 'message' => "Email « {$template} » introuvable."], 404);
        }

        $validated = $request->validate(['email' => ['required', 'email', 'max:255']]);

        try {
            $this->fakeRecipient($validated['email'])->notify($this->notificationFor($template));

            return response()->json([
                'success' => true,
                'message' => "Email de test envoyé à {$validated['email']} (voir Mailpit).",
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => "Échec de l'envoi : ".$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Construit la notification correspondant à l'email, avec des données de test.
     */
    private function notificationFor(string $template): Notification
    {
        return match ($template) {
            'password-reset' => new ResetPasswordNotification('demo-token-123'),
            // Email seul : le destinataire fictif n'existe pas en base (pas d'entrée de cloche)
            'new-message' => new NewMessageNotification(
                conversationId: 1,
                senderName: 'Fatou Ndiaye',
                senderPhotoUrl: null,
                subject: ContactSubject::CoMediation,
                excerpt: 'Bonjour Aminata, je prépare un dialogue communautaire à Ziguinchor et j’aimerais bénéficier de votre regard sur l’implication des femmes leaders…',
                unreadCount: 1,
                url: url(NewMessageNotification::path(1)),
                channels: ['mail'],
            ),
        };
    }

    /**
     * Destinataire fictif : jamais enregistré, aucune écriture en base.
     */
    private function fakeRecipient(string $email = 'aminata.diallo@example.org'): User
    {
        return new User([
            'first_name' => 'Aminata',
            'last_name' => 'Diallo',
            'email' => $email,
        ]);
    }
}
