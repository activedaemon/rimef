<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Langue parlée par une médiatrice (code ISO 639-1, libellé dans config/directory.php).
 */
#[Fillable(['language_code', 'position'])]
class MemberLanguage extends Model
{
    public $timestamps = false;

    /**
     * @return BelongsTo<MemberProfile, $this>
     */
    public function memberProfile(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class);
    }
}
