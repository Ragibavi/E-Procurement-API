<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    public function createUser(array $data): User
    {
        return User::create([
            'id' => Str::uuid(),
            'name' => $data['name'],
            'role' => $data['role'] ?? 'user',
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    public function getUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
}
