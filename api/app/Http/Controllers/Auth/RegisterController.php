<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'                  => $request->string('name')->toString(),
            'email'                 => $request->string('email')->toString(),
            'password'              => $request->string('password')->toString(),
            'notifications_enabled' => $request->boolean('notifications_enabled', true),
        ]);

        Auth::login($user);

        return UserResource::make($user)
            ->response()
            ->setStatusCode(201);
    }
}
