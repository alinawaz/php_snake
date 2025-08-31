<?php

namespace Snake\Database;

use Snake\Database\MySqlTable;

class MySqlModel {

    protected static $conn;
    protected $table_name;
    protected $table;

    public function __construct() {
        $this->table = new MySqlTable($this->getTableName());
    }

    protected function getTableName() {
        return $this->table_name ?? null;
    }

    public static function query() {
        $instance = new static();
        return $instance->table;
    }

    // Chainable static methods

    public static function select($columns = '*') {
        return static::query()->select($columns);
    }

    public static function find($id) {
        return static::query()->where(['id' => $id])->first();
    }

    public static function all() {
        return static::query()->get();
    }

    public static function where($conditions) {
        return static::query()->where($conditions);
    }

    public static function create($data) {
        return static::query()->insert($data);
    }

    public static function link($relation) {
        return static::query()->link($relation);
    }

    public static function update($id, $data) {
        return static::query()->where(['id' => $id])->update($data);
    }

    public static function delete($id) {
        return static::query()->where(['id' => $id])->delete();
    }

    public static function limit($limit) {
        return static::query()->limit($limit);
    }

    public static function group_by($column) {
        return static::query()->group_by($column);
    }

    public static function sort_by($column, $direction = 'ASC') {
        return static::query()->sort_by($column, $direction);
    }

    public static function get() {
        return static::query()->get();
    }   

    public static function first() {
        $results = static::query()->limit(1)->get();
        return $results ? $results[0] : null;
    }

    public static function count() {
        $instance = new static();
        $table = $instance->table;
        $result = $table->select('COUNT(*) as count')->get();
        return $result ? (int)$result[0]['count'] : 0;
    }


}