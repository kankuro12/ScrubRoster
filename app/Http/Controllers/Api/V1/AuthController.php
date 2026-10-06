<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'tenant' => ['nullable', 'string', 'max:100'],
        ]);

        $tenantSlug = $request->input('tenant') ?? $request->header('X-Tenant');
        $query = User::withoutGlobalScopes()->where('email', $credentials['email']);

        if ($tenantSlug) {
            $tenant = \App\Models\Tenant::where('slug', strtolower($tenantSlug))->first();
            if (! $tenant) {
                throw ValidationException::withMessages([
                    'tenant' => ['Workspace not found.'],
                ]);
            }
            $query->where('tenant_id', $tenant->id);
        }

        $users = $query->get();

        if ($users->count() > 1) {
            throw ValidationException::withMessages([
                'tenant' => ['Multiple workspaces found for this email. Please specify the tenant prefix.'],
            ]);
        }

        $user = $users->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken($credentials['device_name'] ?? 'api');

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'token' => $token->plainTextToken,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['data' => null]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => new UserResource($request->user())]);
    }
}
