<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureDomainAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());
        $path = $request->path();

        $essDomain = config('app.ess_domain');
        $hrisDomain = config('app.hris_domain');
        $careersDomain = config('app.careers_domain');
        $publicDomain = config('app.public_domain');

        if ($this->hostMatches($host, $essDomain)) {
            if ($path === '' || $path === '/') {
                return redirect('/employee/login');
            }

            if ($request->is('employee*') || $this->isAssetPath($request)) {
                return $next($request);
            }

            if ($this->isPublicPath($request) || $request->is('admin*')) {
                return redirect('/employee/login');
            }

            return $this->forbidden($request, '/employee/login');
        }

        if ($this->hostMatches($host, $hrisDomain)) {
            if ($path === '' || $path === '/') {
                return redirect('/admin/login');
            }

            if ($request->is('admin*') || $this->isAssetPath($request)) {
                return $next($request);
            }

            if ($this->isPublicPath($request) || $request->is('employee*')) {
                return redirect('/admin/login');
            }

            return $this->forbidden($request, '/admin/login');
        }

        if ($this->hostMatches($host, $careersDomain) || $this->hostMatches($host, $publicDomain)) {
            if ($path === '' || $path === '/') {
                return redirect('/jobs');
            }

            if ($this->isPublicPath($request) || $this->isAssetPath($request)) {
                return $next($request);
            }

            if ($request->is('admin*') || $request->is('employee*')) {
                return redirect('/jobs');
            }

            return $this->forbidden($request, '/jobs');
        }

        return $next($request);
    }

    private function hostMatches(string $host, ?string $domain): bool
    {
        return $domain && strcasecmp($host, $domain) === 0;
    }

    private function isPublicPath(Request $request): bool
    {
        return $request->is('jobs*')
            || $request->is('login*')
            || $request->is('register*')
            || $request->is('my*')
            || $request->is('search*')
            || $request->is('assessment*')
            || $request->is('job*')
            || $request->is('logout');
    }

    private function isAssetPath(Request $request): bool
    {
        return $request->is('css*')
            || $request->is('js*')
            || $request->is('build*')
            || $request->is('assets*')
            || $request->is('storage*')
            || $request->is('images*')
            || $request->is('vendor*')
            || $request->is('livewire*')
            || $request->is('favicon.ico')
            || $request->is('robots.txt');
    }

    private function forbidden(Request $request, string $suggestedUrl): Response
    {
        return response()->view('errors.404', [
            'message' => 'You are not allowed to access this page.',
            'suggestedUrl' => $suggestedUrl,
        ], 404);
    }
}
