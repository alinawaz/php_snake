<?php

namespace App\Models;

use Snake\Database\MySqlModel;

class UserModel extends MySqlModel
{
    protected $table_name = 'users';

    public function findByUsername($username)
    {
        $results = $this->where(['username' => $username]);
        return $results[0] ?? null;
    }

}
