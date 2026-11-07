<?php

namespace App\Http\Middleware;

use App\Support\Features;
use Closure;
use Illuminate\Http\Request;

class EnsureFeatureAccess
{
    /** Route-name prefixes (segment before the first dot) only an admin may open. */
    protected array $adminOnly = ['users', 'system-admin', 'reference'];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $name = $request->route()?->getName();

        if ($user && $name) {
            $segment = str_contains($name, '.') ? substr($name, 0, strpos($name, '.')) : $name;

            if (in_array($segment, $this->adminOnly, true) && ! $user->isAdmin()) {
                abort(403);
            }

            $feature = Features::featureForRoute($name);
            if ($feature && ! $user->canAccess($feature)) {
                abort(403);
            }
        }

        return $next($request);
    }
}
