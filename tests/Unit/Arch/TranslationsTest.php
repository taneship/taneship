<?php

declare(strict_types=1);

use Illuminate\Support\Arr;
use Symfony\Component\Finder\Finder;

/**
 * @return list<string>
 */
function translationLocales(): array
{
    $lang = dirname(__DIR__, 3).'/lang';

    $locales = [
        ...array_map(basename(...), glob($lang.'/*', GLOB_ONLYDIR) ?: []),
        ...array_map(fn (string $file): string => basename($file, '.json'), glob($lang.'/*.json') ?: []),
    ];

    return array_values(array_unique($locales));
}

/**
 * The keys of the interface text, which the front end receives.
 *
 * @return array<string, string>
 */
function interfaceText(string $locale): array
{
    $file = dirname(__DIR__, 3).'/lang/'.$locale.'.json';

    /** @var array<string, string> $lines */
    $lines = is_file($file) ? json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR) : [];

    return $lines;
}

/**
 * The keys of the server text, as the translator reads them: group.key.
 *
 * @return list<string>
 */
function serverTextKeys(string $locale): array
{
    $directory = dirname(__DIR__, 3).'/lang/'.$locale;

    if (! is_dir($directory)) {
        return [];
    }

    $keys = [];

    foreach (Finder::create()->files()->in($directory)->name('*.php') as $file) {
        /** @var array<string, mixed> $lines */
        $lines = require $file->getPathname();

        $keys = [...$keys, ...array_keys(Arr::dot([$file->getBasename('.php') => $lines]))];
    }

    return $keys;
}

it('has every key in every locale', function (): void {
    $keysByLocale = [];

    foreach (translationLocales() as $locale) {
        $keysByLocale[$locale] = [...array_keys(interfaceText($locale)), ...serverTextKeys($locale)];
    }

    $keys = array_unique(array_merge(...array_values($keysByLocale)));

    foreach ($keysByLocale as $locale => $localeKeys) {
        expect(array_values(array_diff($keys, $localeKeys)))->toBe([], "Keys missing in [{$locale}].");
    }
});

it('keeps each key in one file', function (): void {
    foreach (translationLocales() as $locale) {
        $shared = array_intersect(array_keys(interfaceText($locale)), serverTextKeys($locale));

        expect(array_values($shared))->toBe([], "Keys of [{$locale}] in both the JSON file and a PHP file.");
    }
});

it('defines every key the front end translates', function (): void {
    $keys = [];

    foreach (Finder::create()->files()->in(dirname(__DIR__, 3).'/resources/js')->name(['*.ts', '*.tsx']) as $file) {
        preg_match_all("/\btranslate(?:Choice)?\(\s*'([^']+)'/", $file->getContents(), $matches);

        $keys = [...$keys, ...$matches[1]];
    }

    foreach (translationLocales() as $locale) {
        $missing = array_diff(array_unique($keys), array_keys(interfaceText($locale)));

        expect(array_values($missing))->toBe([], "Keys missing in lang/{$locale}.json.");
    }
});
