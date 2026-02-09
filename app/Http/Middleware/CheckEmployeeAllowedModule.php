<?php

namespace App\Http\Middleware;

use App\Helpers\EmployeeModules;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckEmployeeAllowedModule
{
    public function handle(Request $request, Closure $next): Response
    {
        $currentRoute = $request->route()?->getName();
        if (!$currentRoute) {
            return $next($request);
        }
        $product = config('app.product');

        $notAllowed = [
            'opap' => [
                'routes' => ['/employee/leave*'],
            ],
        ];

        if ($currentRoute && $currentRoute !== 'employee.dashboard' && Auth::guard('employee')->check()) {
            $user = Auth::guard('employee')->user();
            $info = $user->information ?? null;

            if ($info && $info->employment_type_id !== 1 && isset($notAllowed[$product])) {
                foreach ($notAllowed[$product]['routes'] as $routePattern) {
                    $routePattern = ltrim($routePattern, '/');
                    if ($request->is($routePattern)) {
                        return redirect()->route('employee.dashboard');
                    }
                }
            }

            $form = $request->route('form');
            if ($currentRoute !== 'employee.dashboard' && !EmployeeModules::isRouteAllowed($currentRoute, $form)) {
                return redirect()->route('employee.dashboard');
            }
        }

        return $next($request);
    }
}
