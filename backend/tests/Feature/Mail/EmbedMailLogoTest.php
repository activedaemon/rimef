<?php

use App\Listeners\EmbedMailLogo;
use App\Mail\MailBrand;
use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Email;

it('embeds the logo when the html references it', function () {
    $email = (new Email)->html('<img src="cid:'.MailBrand::LOGO_CID.'">');

    (new EmbedMailLogo)->handle(new MessageSending($email));

    expect($email->getAttachments())->toHaveCount(1)
        ->and($email->getAttachments()[0]->getFilename())->toBe(MailBrand::LOGO_CID);
});

it('leaves emails without the shared header untouched', function () {
    $email = (new Email)->html('<p>Sans en-tête</p>');

    (new EmbedMailLogo)->handle(new MessageSending($email));

    expect($email->getAttachments())->toBeEmpty();
});
