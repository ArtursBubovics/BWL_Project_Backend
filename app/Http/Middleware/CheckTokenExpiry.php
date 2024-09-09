<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CheckTokenExpiry
{
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();
        if ($token) {
            $hashedToken = hash('sha256', $token);
            $tokenData = DB::table('personal_access_tokens')
                ->where('token', $hashedToken)
                ->where('expires_at', '>', Carbon::now())
                ->first();

            if (!$tokenData) {
                return response()->json(['message' => 'Token is invalid or expired'], 401);
            }
        } else {
            return response()->json(['message' => 'Token not provided'], 401);
        }

        return $next($request);
    }
}