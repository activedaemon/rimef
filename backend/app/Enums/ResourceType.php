<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Type d'une ressource de la bibliothèque (filtre « Type ») et verbe de son appel à l'action.
 */
enum ResourceType: string
{
    case Report = 'report';
    case Analysis = 'analysis';
    case Article = 'article';
    case Podcast = 'podcast';
    case Video = 'video';
    case Tool = 'tool';

    public function label(): string
    {
        return match ($this) {
            self::Report => 'Rapport',
            self::Analysis => 'Analyse',
            self::Article => 'Article',
            self::Podcast => 'Podcast',
            self::Video => 'Vidéo',
            self::Tool => 'Outil',
        };
    }

    /**
     * Appel à l'action des cartes : « Lire », « Écouter »…
     */
    public function action(): string
    {
        return match ($this) {
            self::Report, self::Analysis, self::Article => 'Lire',
            self::Podcast => 'Écouter',
            self::Video => 'Regarder',
            self::Tool => 'Consulter',
        };
    }
}
