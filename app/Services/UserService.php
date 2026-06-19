<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    public function list()
    {
        return User::latest()->paginate(20);
    }

    public function create(array $data)
    {
        return User::create($data);
    }

    public function update(User $user, array $data)
    {
        $user->update($data);
        return $user;
    }

    public function delete(User $user)
    {
        return $user->delete();
    }
}
