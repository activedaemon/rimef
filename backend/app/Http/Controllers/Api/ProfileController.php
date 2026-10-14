<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfilePhotoRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\EditableProfileResource;
use App\Models\User;
use App\Services\ProfileEditor;
use Illuminate\Http\Request;

/**
 * Page « Modifier mon profil » : le compte connecté modifie sa propre fiche.
 */
class ProfileController extends Controller
{
    public function __construct(private readonly ProfileEditor $editor) {}

    /**
     * Valeurs actuelles et listes de choix du formulaire.
     */
    public function show(Request $request): EditableProfileResource
    {
        return $this->resource($request->user())
            ->additional(['meta' => ['options' => $this->editor->options()]]);
    }

    public function update(UpdateProfileRequest $request): EditableProfileResource
    {
        $this->editor->update($request->user(), $request->validated());

        return $this->resource($request->user());
    }

    public function updatePhoto(UpdateProfilePhotoRequest $request): EditableProfileResource
    {
        $this->editor->replacePhoto($request->user(), $request->file('photo'));

        return $this->resource($request->user());
    }

    public function destroyPhoto(Request $request): EditableProfileResource
    {
        $this->editor->removePhoto($request->user());

        return $this->resource($request->user());
    }

    private function resource(User $user): EditableProfileResource
    {
        $user->refresh()->load(['memberProfile.expertises', 'memberProfile.zones']);
        $user->setAttribute('has_sheet', $this->editor->hasSheet($user));

        return new EditableProfileResource($user);
    }
}
