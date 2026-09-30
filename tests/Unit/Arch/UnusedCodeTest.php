<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('imports every component and hook somewhere', function (): void {
    $scripts = dirname(__DIR__, 3).'/resources/js';

    $sources = '';

    foreach (Finder::create()->files()->in($scripts)->name(['*.ts', '*.tsx'])->notName('*.test.*') as $file) {
        $sources .= $file->getContents();
    }

    $folders = array_filter([$scripts.'/components', $scripts.'/hooks'], is_dir(...));

    $unused = [];

    foreach (Finder::create()->files()->in($folders)->name(['*.ts', '*.tsx'])->notName('*.test.*') as $file) {
        $module = '@/'.str($file->getPathname())->after($scripts.'/')->beforeLast('.');

        if (! str_contains($sources, "'{$module}'")) {
            $unused[] = $module;
        }
    }

    expect($unused)->toBe([]);
});
