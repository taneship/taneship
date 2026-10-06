<?php

declare(strict_types=1);

use Illuminate\Support\Facades\App;
use Inertia\Testing\AssertableInertia;

it('shares the direction of the locale', function (string $locale, string $direction): void {
    App::setLocale($locale);

    $this->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('direction', $direction));
})->with([
    'English' => ['en', 'ltr'],
    'American English' => ['en_US', 'ltr'],
    'Arabic' => ['ar', 'rtl'],
    'Egyptian Arabic' => ['ar_EG', 'rtl'],
    'Hebrew' => ['he', 'rtl'],
    'Persian' => ['fa', 'rtl'],
    'Urdu' => ['ur', 'rtl'],
]);

it('sets the direction of the first response', function (string $locale, string $html): void {
    App::setLocale($locale);

    $this->get(route('home'))->assertSee($html, false);
})->with([
    'left to right' => ['en', '<html lang="en" dir="ltr">'],
    'right to left' => ['ar_EG', '<html lang="ar-EG" dir="rtl">'],
]);
