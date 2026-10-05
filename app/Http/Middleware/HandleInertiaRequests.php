<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Data\UserData;
use App\Enums\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Inertia\Inertia;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = App::getLocale();

        return [
            ...parent::share($request),
            'name' => Config::string('app.name'),
            'theme' => ($user = $request->user()) === null ? Theme::System : $user->theme,
            'isSidebarOpen' => $request->cookie('sidebar_state') !== 'false',
            'user' => fn (): ?UserData => ($user = $request->user()) === null ? null : new UserData($user->name, $user->email),
            // The interface text: lang/<locale>.json, which Laravel loads under the * group.
            'translations' => Inertia::once(fn (): array => Lang::getLoader()->load($locale, '*', '*'))->as('translations.'.$locale),
        ];
    }
}
