<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Uri;
use Pest\Browser\Api\PendingAwaitablePage;
use Pest\Browser\Playwright\Client;
use Pest\Browser\Playwright\Page;
use ReflectionProperty;
use RuntimeException;

/**
 * Chrome's virtual authenticator: it holds passkeys and answers each ceremony at once, as a user who
 * confirms with a fingerprint. pest-plugin-browser exposes no DevTools session, so this class opens one
 * through the plugin's Playwright client, with the private guids of its context and page: an upgrade
 * of the plugin breaks this file alone.
 */
final class VirtualAuthenticator
{
    /**
     * Visits the URL on localhost, with an authenticator in the page. Browser tests are served on 127.0.0.1,
     * which WebAuthn refuses as relying party: the application moves to localhost, on the same port, for the
     * rest of the test. The cookies and the authenticator belong to this page: the journey stays in it.
     */
    public static function visit(string $url): PendingAwaitablePage
    {
        $origin = Uri::of(Config::string('app.url'))->withHost('localhost')->value();

        Config::set('app.url', $origin);
        URL::useOrigin($origin);
        // Without it, the page asks 127.0.0.1 for its scripts, and stays blank.
        URL::useAssetOrigin($origin);

        $url = Uri::of($url)->withHost('localhost')->value();
        $page = visit($url);

        $session = self::newSession($page->page());

        self::send($session, 'WebAuthn.enable');
        self::send($session, 'WebAuthn.addVirtualAuthenticator', ['options' => [
            'protocol' => 'ctap2',
            'transport' => 'internal',
            'hasResidentKey' => true,
            'hasUserVerification' => true,
            'isUserVerified' => true,
            'automaticPresenceSimulation' => true,
        ]]);

        // The page loaded before the authenticator joined it, and may have started a ceremony without it.
        $page->navigate($url);

        return $page;
    }

    private static function newSession(Page $page): string
    {
        $guid = fn (object $object): string => (string) new ReflectionProperty($object, 'guid')->getValue($object);

        foreach (Client::instance()->execute($guid($page->context()), 'newCDPSession', ['page' => ['guid' => $guid($page)]]) as $message) {
            $session = data_get($message, 'result.session.guid');

            if (is_string($session)) {
                return $session;
            }
        }

        throw new RuntimeException('Playwright opened no DevTools session.');
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private static function send(string $session, string $method, array $params = []): void
    {
        $call = $params === [] ? ['method' => $method] : ['method' => $method, 'params' => $params];

        iterator_to_array(Client::instance()->execute($session, 'send', $call));
    }
}
