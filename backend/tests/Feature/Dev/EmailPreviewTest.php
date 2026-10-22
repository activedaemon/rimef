<?php

use App\Mail\MailBrand;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

// Routes de dev sans contrainte de domaine : visées sur le domaine du tenant, comme dans le navigateur.

// Les tests tournent en `testing` : sans l'option, les outils de dev répondraient 404.
beforeEach(fn () => config(['rimef.dev_tools' => true]));

it('hides the email catalogue in staging without the option', function () {
    app()->detectEnvironment(fn () => 'staging');
    config(['rimef.dev_tools' => false]);

    $this->get('http://rimef.localhost/api/dev/emails')->assertNotFound();
    $this->postJson('http://rimef.localhost/api/dev/emails/send/password-reset', ['email' => 'test@example.org'])->assertNotFound();
});

it('opens the email catalogue in local without the option', function () {
    app()->detectEnvironment(fn () => 'local');
    config(['rimef.dev_tools' => false]);

    $this->get('http://rimef.localhost/api/dev/emails')->assertOk();
});

it('opens the email catalogue in staging with the option', function () {
    app()->detectEnvironment(fn () => 'staging');

    $this->get('http://rimef.localhost/api/dev/emails')->assertOk();
});

it('limits test sends to ten per minute', function () {
    Notification::fake();

    foreach (range(1, 10) as $attempt) {
        $this->postJson('http://rimef.localhost/api/dev/emails/send/password-reset', ['email' => 'test@example.org'])->assertOk();
    }

    $this->postJson('http://rimef.localhost/api/dev/emails/send/password-reset', ['email' => 'test@example.org'])->assertTooManyRequests();
});

it('hides the failure detail from the response and logs it', function () {
    Log::spy();
    Notification::shouldReceive('send')->andThrow(new RuntimeException('smtp://rimef:secret@mailpit:1025 injoignable'));

    $this->postJson('http://rimef.localhost/api/dev/emails/send/password-reset', ['email' => 'test@example.org'])
        ->assertServerError()
        ->assertJson(['success' => false, 'message' => "Échec de l'envoi. Le détail est dans les logs."])
        ->assertDontSee('secret');

    Log::shouldHaveReceived('error')->once();
});

it('lists the previewable emails', function () {
    $this->get('http://rimef.localhost/api/dev/emails')
        ->assertOk()
        ->assertSee('Réinitialisation du mot de passe')
        ->assertSee('/api/dev/emails/preview/password-reset', false);
});

it('renders the password reset email with fake data', function () {
    $this->get('http://rimef.localhost/api/dev/emails/preview/password-reset')
        ->assertOk()
        ->assertSee('Choisir un nouveau mot de passe')
        ->assertSee('token=demo-token-123', false)
        ->assertSee('Bonjour,')
        ->assertSee('L’équipe RIMeF')
        ->assertSee('Si le bouton « Choisir un nouveau mot de passe » ne fonctionne pas');
});

it('returns 404 for an unknown email', function () {
    $this->get('http://rimef.localhost/api/dev/emails/preview/inconnu')->assertNotFound();
    $this->postJson('http://rimef.localhost/api/dev/emails/send/inconnu', ['email' => 'test@example.org'])->assertNotFound();
});

it('sends a test email to the given address', function () {
    Notification::fake();

    $this->postJson('http://rimef.localhost/api/dev/emails/send/password-reset', ['email' => 'test@example.org'])
        ->assertOk()
        ->assertJson(['success' => true]);

    Notification::assertSentTimes(ResetPasswordNotification::class, 1);
});

it('rejects an invalid address', function () {
    Notification::fake();

    $this->postJson('http://rimef.localhost/api/dev/emails/send/password-reset', ['email' => 'pas-un-email'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    Notification::assertNothingSent();
});

it('renders the shared header with the logo inlined for the browser', function () {
    $this->get('http://rimef.localhost/api/dev/emails/preview/password-reset')
        ->assertOk()
        ->assertSee('RIMEF')
        ->assertSee('Réseau international')
        ->assertSee('src="data:image/png;base64,', false)
        ->assertDontSee('cid:'.MailBrand::LOGO_CID, false);
});

it('renders the new message email without any address', function () {
    $this->get('http://rimef.localhost/api/dev/emails/preview/new-message')
        ->assertOk()
        ->assertSee('Fatou Ndiaye vous a écrit sur la messagerie du réseau.')
        ->assertSee('dialogue communautaire à Ziguinchor')
        ->assertSee('Lire et répondre')
        ->assertSee('/messages/1', false)
        ->assertDontSee('@example.org');
});
