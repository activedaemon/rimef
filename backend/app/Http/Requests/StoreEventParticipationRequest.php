<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * « J'y participe » (PUT /api/events/{event}/participation) : jours de présence (événement de
 * plusieurs jours, dans le fuseau de l'utilisatrice ; absents = tous), note facultative et
 * proposition de se retrouver sur place. Chaque champ absent garde sa valeur.
 */
class StoreEventParticipationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'days' => ['nullable', 'array', 'min:1'],
            'days.*' => ['date_format:Y-m-d', 'distinct'],
            'note' => ['nullable', 'string', 'max:150'],
            'wants_to_meet' => ['sometimes', 'boolean'],
            'timezone' => ['nullable', 'timezone:all'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'days.min' => 'Choisissez au moins un jour de présence.',
            'note.max' => 'La note ne peut pas dépasser 150 caractères.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $event = $this->route('event');
                $days = $this->input('days');

                if (! $event instanceof Event || ! is_array($days) || $validator->errors()->isNotEmpty()) {
                    return;
                }

                if (array_diff($days, $event->days($this->timezone())) !== []) {
                    $validator->errors()->add('days', 'Ces jours ne font pas partie de l’événement.');
                }
            },
        ];
    }

    /**
     * Fuseau où les jours sont lus (contrôlé par les règles avant tout usage).
     */
    public function timezone(): string
    {
        return (string) ($this->input('timezone') ?: 'UTC');
    }

    /**
     * Valeurs à enregistrer sur la participation : seulement les champs envoyés ; tous les jours
     * cochés (ou aucun jour envoyé) = null.
     *
     * @return array<string, mixed>
     */
    public function attendance(Event $event): array
    {
        $values = [];

        if ($this->exists('days')) {
            $days = $this->validated('days');
            $all = $event->days($this->timezone());
            $values['attendance_days'] = $days === null || array_diff($all, $days) === []
                ? null
                : array_values(array_intersect($all, $days));
        }
        if ($this->exists('note')) {
            $note = trim((string) $this->validated('note'));
            $values['attendance_note'] = $note === '' ? null : $note;
        }
        if ($this->exists('wants_to_meet')) {
            $values['wants_to_meet'] = $this->boolean('wants_to_meet');
        }

        return $values;
    }
}
