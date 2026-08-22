<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwnerToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $cookieName = 'owner_token';

        // Jika user belum punya token di cookie, buatkan UUID baru
        if (!$request->hasCookie($cookieName)) {
            $ownerToken = (string) Str::uuid();
            // Simpan cookie selama 1 tahun (525600 menit)
            Cookie::queue($cookieName, $ownerToken, 525600);
            $request->attributes->set('owner_token', $ownerToken);
        } else {
            $request->attributes->set('owner_token', $request->cookie($cookieName));
        }

        return $next($request);
    }
}
