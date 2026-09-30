// One level deep, as Laravel reads its JSON translation files: keys start with their module id.
export type Translations = { [key: string]: string };

export type Replacements = { [placeholder: string]: string | number };

// Laravel's syntax: `{0} none|[1,*] :count`, where `*` is unbounded.
const INTERVAL = /^[{[]([-?\d|*,.]*)[}\]]([\s\S]*)/;

export function translate(
    translations: Translations,
    key: string,
    replacements: Replacements = {},
): string {
    const line = lineFor(translations, key);

    return line === undefined ? key : replace(line, replacements);
}

export function translateChoice(
    translations: Translations,
    key: string,
    count: number,
    replacements: Replacements = {},
): string {
    const line = lineFor(translations, key);

    return line === undefined ? key : replace(choose(line, count), { count, ...replacements });
}

function lineFor(translations: Translations, key: string): string | undefined {
    return Object.hasOwn(translations, key) ? translations[key] : undefined;
}

function choose(line: string, count: number): string {
    const segments = line.split('|');

    for (const segment of segments) {
        const [, condition, text] = INTERVAL.exec(segment) ?? [];

        if (condition !== undefined && text !== undefined && isWithin(condition, count)) {
            return text.trim();
        }
    }

    const texts = segments.map((segment) => segment.replace(INTERVAL, '$2'));

    return texts[pluralIndex(count)] ?? texts[0] ?? line;
}

function isWithin(condition: string, count: number): boolean {
    if (!condition.includes(',')) {
        return condition !== '' && Number(condition) === count;
    }

    const [from = '', to = ''] = condition.split(',', 2);

    return (from === '*' || count >= Number(from)) && (to === '*' || count <= Number(to));
}

// English plural rules, as in Laravel.
function pluralIndex(count: number): number {
    return count === 1 ? 0 : 1;
}

function replace(line: string, replacements: Replacements): string {
    const substitutes = new Map<string, string>();

    for (const [placeholder, value] of Object.entries(replacements)) {
        const text = String(value);

        substitutes.set(`:${capitalize(placeholder)}`, capitalize(text));
        substitutes.set(`:${placeholder.toUpperCase()}`, text.toUpperCase());
        substitutes.set(`:${placeholder}`, text);
    }

    if (substitutes.size === 0) {
        return line;
    }

    // Longest placeholders first, so that `:names` is never read as `:name` followed by `s`.
    const pattern = new RegExp(
        [...substitutes.keys()]
            .sort((first, second) => second.length - first.length)
            .map((placeholder) => placeholder.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'))
            .join('|'),
        'g',
    );

    return line.replace(pattern, (placeholder) => substitutes.get(placeholder) ?? placeholder);
}

function capitalize(text: string): string {
    return text.charAt(0).toUpperCase() + text.slice(1);
}
