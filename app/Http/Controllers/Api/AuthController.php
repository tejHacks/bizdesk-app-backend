<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'business_name' => ['required', 'string', 'max:120'],
            'currency' => ['nullable', 'string', 'max:8'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Password::min(8)],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $user = User::create($data);

        return response()->json([
            'user' => $this->profile($user),
            'token' => $user->createToken($data['device_name'])->plainTextToken,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['The provided credentials are incorrect.']]);
        }

        return response()->json([
            'user' => $this->profile($user),
            'token' => $user->createToken($data['device_name'])->plainTextToken,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->profile($request->user())]);
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->noContent();
    }

    private function profile(User $user): array
    {
        return $user->only(['id', 'name', 'business_name', 'currency', 'email']);
    }
}
