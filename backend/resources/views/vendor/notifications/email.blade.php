{{--
    Gabarit commun à toutes les notifications par email.
    Formule d'appel et signature par défaut : une notification ne définit que son
    contenu (subject, line, action) et ne surcharge greeting() ou salutation()
    qu'au besoin (ex. « Bonjour Aminata, »).
    Textes en français écrits ici et non dans lang/fr.json, géré par laravel-lang.
--}}
<x-mail::message>
{{-- Greeting --}}
# {{ $greeting ?: 'Bonjour,' }}

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Salutation --}}
{{ $salutation ?: 'L’équipe '.App\Mail\MailBrand::name() }}

{{-- Subcopy --}}
@isset($actionText)
<x-slot:subcopy>
Si le bouton « {{ $actionText }} » ne fonctionne pas, copiez ce lien dans votre navigateur : <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
