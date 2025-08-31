<?php

loadFile('snake.database.mysql_model');

class AppModel extends MySqlModel
{
    protected $table_name = 'apps';

}
