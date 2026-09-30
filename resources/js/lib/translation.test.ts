import { describe, expect, it } from 'vite-plus/test';

import { translate, translateChoice } from './translation';
import type { Translations } from './translation';

const translations: Translations = {
    'orders.title': 'Orders',
    'orders.greeting': 'Hello :name',
    'orders.headline': ':Name has :COUNT orders',
    'orders.names': ':names and :name',
    'orders.apples': 'one apple|:count apples',
    'orders.items': '{0} No items|[1,*] :count items',
    'orders.baskets': '[*,1] A basket|[2,5] A few baskets|[6,*] Many baskets',
    'orders.exact': '{3} Three|Other',
    'orders.single': ':count order',
};

describe('translate', () => {
    it('returns the line of a key', () => {
        expect(translate(translations, 'orders.title')).toBe('Orders');
    });

    it('returns the key when the line is missing', () => {
        expect(translate(translations, 'orders.missing')).toBe('orders.missing');
        expect(translate(translations, 'constructor')).toBe('constructor');
    });

    it('replaces placeholders in their casing', () => {
        expect(translate(translations, 'orders.greeting', { name: 'taylor' })).toBe('Hello taylor');
        expect(translate(translations, 'orders.headline', { name: 'taylor', count: 3 })).toBe(
            'Taylor has 3 orders',
        );
    });

    it('replaces the longest placeholder first', () => {
        expect(translate(translations, 'orders.names', { name: 'Ada', names: 'Grace' })).toBe(
            'Grace and Ada',
        );
    });

    it('leaves the line untouched without replacements', () => {
        expect(translate(translations, 'orders.greeting')).toBe('Hello :name');
    });
});

describe('translateChoice', () => {
    it('follows English plural rules', () => {
        expect(translateChoice(translations, 'orders.apples', 1)).toBe('one apple');
        expect(translateChoice(translations, 'orders.apples', 0)).toBe('0 apples');
        expect(translateChoice(translations, 'orders.apples', 2)).toBe('2 apples');
    });

    it('chooses an exact interval', () => {
        expect(translateChoice(translations, 'orders.items', 0)).toBe('No items');
        expect(translateChoice(translations, 'orders.exact', 3)).toBe('Three');
    });

    it('chooses a range', () => {
        expect(translateChoice(translations, 'orders.items', 7)).toBe('7 items');
        expect(translateChoice(translations, 'orders.baskets', 1)).toBe('A basket');
        expect(translateChoice(translations, 'orders.baskets', 4)).toBe('A few baskets');
        expect(translateChoice(translations, 'orders.baskets', 9)).toBe('Many baskets');
    });

    it('falls back to plural rules when no interval matches', () => {
        expect(translateChoice(translations, 'orders.exact', 5)).toBe('Other');
    });

    it('uses the only segment of a line without plural', () => {
        expect(translateChoice(translations, 'orders.single', 4)).toBe('4 order');
    });

    it('lets an explicit count replacement win', () => {
        expect(translateChoice(translations, 'orders.apples', 2, { count: 'two' })).toBe(
            'two apples',
        );
    });

    it('returns the key when the line is missing', () => {
        expect(translateChoice(translations, 'orders.missing', 2)).toBe('orders.missing');
    });
});
