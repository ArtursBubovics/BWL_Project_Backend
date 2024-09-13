<?php

namespace App\Http;

use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CheckTokenExpiry
{
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();
        dd('Extracted token: ' . $token);

        if ($token) {
            $hashedToken = hash('sha256', $token);
            dd('Hashed token: ' . $hashedToken);

            $tokenData = DB::table('personal_access_tokens')
                ->where('token', $hashedToken)
                ->where('expires_at', '>', Carbon::now())
                ->first();

            if (!$tokenData) {
                dd('Token is invalid or expired: ' . $hashedToken);
                return response()->json(['message' => 'Token is invalid or expired'], 401);
            }
        } else {
            dd('Token not provided');
            return response()->json(['message' => 'Token not provided'], 401);
        }

        return $next($request);
    }
}