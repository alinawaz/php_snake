<?php

namespace Snake\Database;

class MySqlTable
{

    private $conn;
    private $table_name;
    private $columns = '*';
    private $where = [];
    private $where_bind_types = '';
    private $where_bind_values = [];
    private $limit = null;
    private $groupBy = null;
    private $orderBy = null;
    private $link_relations = [];
    private $populate_relations = [];

    public function __construct($table_name)
    {
        global $db;
        $this->conn = $db->getConnection();
        $this->table_name = $table_name;
    }

    public function reset()
    {
        $this->columns = '*';
        $this->where = [];
        $this->limit = null;
        $this->groupBy = null;
        $this->orderBy = null;
        $this->link_relations = [];
        $this->populate_relations = [];
        $this->where_bind_types = '';
        $this->where_bind_values = [];
        return $this;
    }

    public function link($relation)
    {
        $this->link_relations[] = $relation;
        return $this;
    }

    public function populate($relation)
    {
        $this->populate_relations[] = $relation;
        return $this;
    }

    public function select($columns = '*')
    {
        if (is_array($columns)) {
            $this->columns = implode(', ', $columns);
        } else {
            $this->columns = $columns;
        }
        return $this;
    }

    public function where($conditions_or_key, $value = NULL)
    {
        if (is_string($conditions_or_key) && $value != NULL) {
            $this->where[] = "$conditions_or_key = ?";
            $type = '';
            if (is_int($value)) $type = 'i';
            elseif (is_float($value)) $type = 'd';
            else $type = 's';
            $this->where_bind_types = $type;
            $this->where_bind_values = [$value];
        } else {
            $values = [];
            foreach ($conditions_or_key as $col => $val) {
                $this->where[] = "$col = ?";
                $values[] = $val;
            }

            if (!empty($values)) {
                $types = '';
                foreach ($values as $v) {
                    if (is_int($v)) $types .= 'i';
                    elseif (is_float($v)) $types .= 'd';
                    else $types .= 's';
                }
                $this->where_bind_types = $types;
                $this->where_bind_values = $values;
            }
        }

        return $this;
    }

    public function limit($limit)
    {
        $this->limit = intval($limit);
        return $this;
    }

    public function group_by($column)
    {
        $this->groupBy = $column;
        return $this;
    }

    public function sort_by($column, $direction = 'ASC')
    {
        $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBy = "$column $dir";
        return $this;
    }

    public function get()
    {
        global $inflector;

        $sql = "SELECT {$this->columns} FROM {$this->table_name}";
        if ($this->where) {

            $sql .= " WHERE " . implode(' AND ', $this->where);
        }
        if ($this->groupBy) {
            $sql .= " GROUP BY {$this->groupBy}";
        }
        if ($this->orderBy) {
            $sql .= " ORDER BY {$this->orderBy}";
        }
        if ($this->limit) {
            $sql .= " LIMIT {$this->limit}";
        }

        $stmt = $this->conn->prepare($sql);
        if ($this->where_bind_types != '' && !empty($this->where_bind_values)) {
            $stmt->bind_param($this->where_bind_types, ...$this->where_bind_values);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        $objects = [];
        while ($row = $result->fetch_object()) {
            $objects[] = $row;
        }

        // --- Handle link relations
        if (!empty($this->link_relations) && !empty($objects)) {

            foreach ($this->link_relations as $relation) {

                $relation_names = explode('.', $relation);

                // Attach related to parent objects
                foreach ($objects as $obj) {
                    $this->linkRelationalData($obj, $relation_names);
                }
            }
        }

        // --- Handle populate relations
        if (!empty($this->populate_relations) && !empty($objects)) {

            foreach ($this->populate_relations as $relation) {

                /** populating each item of base data */
                foreach ($objects as $item) {
                    $col_name = $relation;
                    $rel_name = $inflector->singularize($col_name);
                    $current_table_singular = $inflector->singularize($this->table_name);
                    $fk = $current_table_singular . '_id';
                    $model_name = ucfirst($rel_name) . 'Model';
                    $model_namespace = 'App\\Models\\' . $model_name;
                    $item->$col_name = $model_namespace::where($fk, $item->id)->get();
                }
            }            
        }

        $this->reset();

        return $objects;
    }

    private function linkRelationalData($item, $relation_names, $index = 0)
    {
        if ($index > count($relation_names) - 1) return;

        $rel_name = $relation_names[$index];
        $lk = $rel_name . '_id';
        $model_name = ucfirst($rel_name) . 'Model';
        $model_namespace = 'App\\Models\\' . $model_name;
        $item->$rel_name = $model_namespace::find($item->$lk);

        $this->linkRelationalData($item->$rel_name, $relation_names, ++$index);
    }

    public function first()
    {
        $this->limit(1);
        $results = $this->get();
        return $results ? $results[0] : null;
    }

    /**
     * Execute a raw SQL query
     * @param string $sql
     * @return array Result set as associative array
     */
    public function rawQuery($sql)
    {
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function insert($data)
    {
        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = [];
        $types = '';
        foreach ($values as $key => $value) {
            if (is_int($value)) $types .= 'i';
            elseif (is_float($value)) $types .= 'd';
            else $types .= 's';
            $placeholders[] = '?';
        }

        $sql_query = "INSERT INTO {$this->table_name} (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";

        $stmt = $this->conn->prepare($sql_query);
        if (!empty($values)) {
            $stmt->bind_param($types, ...$values);
        }
        $stmt->execute();
        $insert_id = $stmt->insert_id;
        $stmt->close();

        return $insert_id ? $this->where(['id' => $insert_id])->limit(1)->get()[0] : null;
    }

    public function update($data)
    {
        $set = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $set[] = "$key = '" . $value . "'";
            } else {
                $set[] = "$key = " . $value;
            }
        }
        $setStr = implode(', ', $set);

        $sql = "UPDATE {$this->table_name} SET $setStr";
        if ($this->where) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
        }

        $stmt = $this->conn->prepare($sql);
        if ($this->where_bind_types != '' && !empty($this->where_bind_values)) {
            $stmt->bind_param($this->where_bind_types, ...$this->where_bind_values);
        }
        $result = $stmt->execute();
        $stmt->close();

        $this->reset();
        return $result;
    }

    public function delete()
    {
        $sql = "DELETE FROM {$this->table_name}";
        if ($this->where) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
        }

        $stmt = $this->conn->prepare($sql);
        if ($this->where_bind_types != '' && !empty($this->where_bind_values)) {
            $stmt->bind_param($this->where_bind_types, ...$this->where_bind_values);
        }
        $result = $stmt->execute();
        $stmt->close();

        $this->reset();
        return $result;
    }

    public function count()
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table_name}";
        if ($this->where) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
        }

        $stmt = $this->conn->prepare($sql);
        if ($this->where_bind_types != '' && !empty($this->where_bind_values)) {
            $stmt->bind_param($this->where_bind_types, ...$this->where_bind_values);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $this->reset();
        return $result['count'] ?? 0;
    }

    public function all()
    {
        // Reset any previous conditions to fetch all rows
        $this->reset();

        $sql = "SELECT * FROM {$this->table_name}";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        $objects = [];
        while ($row = $result->fetch_object()) {
            $objects[] = $row;
        }

        return $objects;
    }
}
