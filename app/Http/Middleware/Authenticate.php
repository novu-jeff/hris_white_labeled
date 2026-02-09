<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson() || $request->hasHeader('X-Livewire')) {
            return null;
        }

        if ($request->is('admin/*')) {
            return route('admin.login');
        } elseif ($request->is('employee/*')) {
            return route('employee.login');
        } else {
            return route('home.login');
        }
    }
}
