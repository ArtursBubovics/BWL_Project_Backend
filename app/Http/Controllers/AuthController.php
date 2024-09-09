<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\RefreshToken;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Info(
 *     title="Auth API",
 *     version="1.0.0",
 *     description="Authentication endpoints for user registration and login"
 * )
 *
 * @OA\Server(
 *     url="http://localhost/api",
 *     description="Local API server"
 * )
 */

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="Register a new user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "device_name"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="johndoe@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123"),
     *             @OA\Property(property="device_name", type="string", example="My Device")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJleHBpcmF0aW9uX3Rva2VuIjoiV2FsdC1bNVZ2VzJ9.S5V8mZ_dARea8T-b6gV5G7Hs5J8iQHcOZznzthktds0"),
     *             @OA\Property(property="refresh_token", type="string", example="r1m32z8cE4Tcn3j8NtbSjU2w9v36JS4e7Lrmz2hBGu9n1X6zbG5RxRz8DNi9P3Fl")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'device_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Генерация access токена
        $accessToken = $user->createToken($request->device_name)->plainTextToken;

        DB::table('personal_access_tokens')
        ->where('token', hash('sha256', $accessToken))
        ->update(['expires_at' => Carbon::now()->addMinutes(60)]);

        // Генерация и сохранение refresh токена
        $refreshToken = Str::random(64);
        RefreshToken::create([
            'user_id' => $user->id,
            'refresh_token' => hash('sha256', $refreshToken),
            'expires_at' => Carbon::now()->addDays(30), // Срок действия 30 дней
        ]);

        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ], 201);
    }


    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Login a user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password", "device_name"},
     *             @OA\Property(property="email", type="string", format="email", example="johndoe@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="device_name", type="string", example="My Device")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User logged in successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJleHBpcmF0aW9uX3Rva2VuIjoiV2FsdC1bNVZ2VzJ9.S5V8mZ_dARea8T-b6gV5G7Hs5J8iQHcOZznzthktds0"),
     *             @OA\Property(property="refresh_token", type="string", example="r1m32z8cE4Tcn3j8NtbSjU2w9v36JS4e7Lrmz2hBGu9n1X6zbG5RxRz8DNi9P3Fl")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
            'device_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = Auth::user();

        if ($user instanceof \App\Models\User) {
            // Генерация access токена
            $accessToken = $user->createToken($request->device_name)->plainTextToken;

            DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $accessToken))
            ->update(['expires_at' => Carbon::now()->addMinutes(60)]);

            // Генерация и сохранение refresh токена
            $refreshToken = Str::random(64);
            RefreshToken::create([
                'user_id' => $user->id,
                'refresh_token' => hash('sha256', $refreshToken),
                'expires_at' => Carbon::now()->addDays(30),
            ]);

            return response()->json([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
            ], 200);
        } else {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/new-access-token",
     *     summary="Generate a new access token using a refresh token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"refresh_token", "device_name"},
     *             @OA\Property(property="refresh_token", type="string", example="r1m32z8cE4Tcn3j8NtbSjU2w9v36JS4e7Lrmz2hBGu9n1X6zbG5RxRz8DNi9P3Fl"),
     *             @OA\Property(property="device_name", type="string", example="My Device")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="New access token generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJleHBpcmF0aW9uX3Rva2VuIjoiV2FsdC1bNVZ2VzJ9.S5V8mZ_dARea8T-b6gV5G7Hs5J8iQHcOZznzthktds0")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid or expired refresh token",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid or expired refresh token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */


    public function new_access_token(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
            'device_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Найдем refresh токен в базе данных
        $hashedToken = hash('sha256', $request->refresh_token);
        $refreshToken = RefreshToken::where('refresh_token', $hashedToken)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$refreshToken) {
            return response()->json(['message' => 'Invalid or expired refresh token'], 401);
        }

        // Генерация нового access токена
        $user = $refreshToken->user;
        $accessToken = $user->createToken($request->device_name)->plainTextToken;

        return response()->json(['access_token' => $accessToken], 200);
    }
}
