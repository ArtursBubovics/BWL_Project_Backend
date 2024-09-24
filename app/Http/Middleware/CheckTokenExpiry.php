<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckTokenExpiry
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('CheckTokenExpiry middleware triggered');

        // Ваш код проверки токена
        $token = $request->user()->currentAccessToken();

        if (!$token) {
            Log::error('Token not found');
            return response()->json(['message' => 'Token not found'], 401);
        }

        $tokenData = DB::table('personal_access_tokens')->where('id', $token->id)->first();

        if (!$tokenData) {
            Log::error('Token data not found');
            return response()->json(['message' => 'Token data not found'], 401);
        }

        $expiresAt = $tokenData->expires_at;

        if ($expiresAt && Carbon::parse($expiresAt)->isPast()) {
            Log::error('Token has expired');
            return response()->json(['message' => 'Token has expired'], 401);
        }

        Log::info('Token is valid');
        return $next($request);
    }
}
