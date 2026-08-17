<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CoopIpnAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $username = (string) config('services.coop.ipn_username');
        $password = (string) config('services.coop.ipn_password');
        $token = (string) config('services.coop.ipn_token');

        if ($username === '' && $password === '' && $token === '') {
            return response()->json([
                'MessageCode' => '401',
                'Message' => 'IPN authentication is not configured',
            ], 401);
        }

        if ($this->basicMatches($request, $username, $password) || $this->bearerMatches($request, $token)) {
            return $next($request);
        }

        return response()->json([
            'MessageCode' => '401',
            'Message' => 'Unauthorized',
        ], 401);
    }

    private function basicMatches(Request $request, string $username, string $password): bool
    {
        if ($username === '' || $password === '') {
            return false;
        }

        return $request->getUser() === $username && $request->getPassword() === $password;
    }

    private function bearerMatches(Request $request, string $token): bool
    {
        if ($token === '') {
            return false;
        }

        $header = (string) $request->header('Authorization', '');

        if (! str_starts_with($header, 'Bearer ')) {
            return false;
        }

        return hash_equals($token, substr($header, 7));
    }
}
