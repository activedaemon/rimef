<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Mail\MailBrand;
use Illuminate\Mail\Events\MessageSending;

/**
 * Joint le logo inline aux emails dont le HTML y fait référence (en-tête commun),
 * qu'ils viennent d'une notification ou d'un Mailable.
 */
class EmbedMailLogo
{
    public function handle(MessageSending $event): void
    {
        $html = $event->message->getHtmlBody();

        if (is_string($html) && str_contains($html, 'cid:'.MailBrand::LOGO_CID)) {
            $event->message->embedFromPath(MailBrand::logoPath(), MailBrand::LOGO_CID, 'image/png');
        }
    }
}
