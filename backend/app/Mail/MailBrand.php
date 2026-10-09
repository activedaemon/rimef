<?php

declare(strict_types=1);

namespace App\Mail;

/**
 * Identité commune à tous les emails : nom affiché et logo de l'en-tête.
 *
 * Le nom vient du tenant courant, ce qui permet à un futur tenant de reprendre le
 * gabarit sans le copier. Le logo est intégré au message (pièce jointe inline,
 * cf. App\Listeners\EmbedMailLogo) : rien à héberger, et il s'affiche même quand
 * la messagerie bloque les images distantes.
 */
class MailBrand
{
    /**
     * Identifiant du logo dans le HTML de l'email (`<img src="cid:rimef-mark">`).
     */
    public const LOGO_CID = 'rimef-mark';

    /**
     * Nom du réseau émetteur : celui du tenant, sinon celui de l'application.
     */
    public static function name(): string
    {
        return (string) (tenant('name') ?? config('app.name'));
    }

    public static function logoPath(): string
    {
        return resource_path('mail/rimef-mark.png');
    }

    /**
     * Remplace la référence inline du logo par l'image elle-même, pour afficher
     * un email dans le navigateur (prévisualisation), où `cid:` ne se résout pas.
     */
    public static function inlineLogoForBrowser(string $html): string
    {
        $dataUri = 'data:image/png;base64,'.base64_encode((string) file_get_contents(self::logoPath()));

        return str_replace('cid:'.self::LOGO_CID, $dataUri, $html);
    }
}
