<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('mirrors every prop type on its enum or data class', function (): void {
    $mismatches = [];

    $types = Finder::create()
        ->files()
        ->in(dirname(__DIR__, 3).'/resources/js/types')
        ->name('*.ts')
        ->notName('shared-props.ts');

    foreach ($types as $file) {
        if (preg_match('/export type (\w+) = ([\s\S]*)/', $file->getContents(), $type) !== 1) {
            $mismatches[] = "{$file->getFilename()} exports no type.";

            continue;
        }

        [, $name, $definition] = $type;

        if (enum_exists($enum = 'App\\Enums\\'.$name)) {
            preg_match_all("/'([^']*)'/", $definition, $values);
            $expected = array_map(fn (BackedEnum $case): int|string => $case->value, $enum::cases());
            $actual = $values[1];
        } elseif (class_exists($data = 'App\\Data\\'.$name.'Data')) {
            preg_match_all('/^\s*(\w+)\??:/m', $definition, $properties);
            $expected = array_map(
                fn (ReflectionProperty $property): string => $property->getName(),
                new ReflectionClass($data)->getProperties(ReflectionProperty::IS_PUBLIC),
            );
            $actual = $properties[1];
        } else {
            $mismatches[] = "{$name} has neither an enum App\\Enums\\{$name} nor a class App\\Data\\{$name}Data.";

            continue;
        }

        sort($expected);
        sort($actual);

        if ($expected !== $actual) {
            $mismatches[] = "{$name} lists [".implode(', ', $actual).'], its PHP counterpart ['.implode(', ', $expected).'].';
        }
    }

    expect($mismatches)->toBe([]);
});
