<?php

loadFile('snake.database.mysql_model');

class TransactionModel extends MySqlModel
{
    protected $table_name = 'transactions';
}
