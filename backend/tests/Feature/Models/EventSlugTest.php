<?php

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

it('builds the slug from the title and the year, without accents', function () {
    $event = Event::factory()->create(['title' => 'Sommet de la Francophonie', 'starts_at' => Carbon::parse('2026-11-18 08:00')]);

    expect($event->slug)->toBe('sommet-de-la-francophonie-2026');
});

it('keeps the editions of an event apart, and suffixes the same title the same year', function () {
    $forum = Event::factory()->create(['title' => 'Paris Peace Forum', 'starts_at' => Carbon::parse('2026-11-12 08:00')]);
    $nextYear = Event::factory()->create(['title' => 'Paris Peace Forum', 'starts_at' => Carbon::parse('2027-11-11 08:00')]);
    $twin = Event::factory()->create(['title' => 'Paris Peace Forum', 'starts_at' => Carbon::parse('2026-12-01 08:00')]);

    expect([$forum->slug, $nextYear->slug, $twin->slug])
        ->toBe(['paris-peace-forum-2026', 'paris-peace-forum-2027', 'paris-peace-forum-2026-2']);
});

it('updates the slug when the title or the year changes, and keeps it otherwise', function () {
    $event = Event::factory()->create(['title' => 'Atelier régional', 'starts_at' => Carbon::parse('2026-12-05 08:00')]);

    $event->update(['city' => 'Rabat']);
    expect($event->slug)->toBe('atelier-regional-2026');

    $event->update(['starts_at' => Carbon::parse('2027-01-10 08:00')]);
    expect($event->slug)->toBe('atelier-regional-2027');

    $event->update(['title' => 'Atelier du Maghreb']);
    expect($event->slug)->toBe('atelier-du-maghreb-2027');
});
