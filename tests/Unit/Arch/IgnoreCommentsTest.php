<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('has no ignore or disable comment', function (): void {
    // Split so that this file does not match its own patterns.
    $comments = ['@phpstan'.'-ignore', '@ts'.'-ignore', '@ts'.'-expect-error', 'oxlint'.'-disable', 'eslint'.'-disable'];

    $files = Finder::create()
        ->files()
        ->in(dirname(__DIR__, 3))
        ->exclude(['node_modules', 'vendor'])
        ->ignoreDotFiles(false)
        ->ignoreVCSIgnored(true)
        ->name(['*.php', '*.ts', '*.tsx', '*.js', '*.css'])
        ->contains($comments);

    expect(array_keys(iterator_to_array($files)))->toBeEmpty();
});
