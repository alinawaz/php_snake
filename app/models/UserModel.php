<?php

loadFile('snake.database.mysql_model');

class UserModel extends MySqlModel
{
    protected $table_name = 'users';

    public function findByUsername($username)
    {
        $results = $this->where(['username' => $username]);
        return $results[0] ?? null;
    }

}
