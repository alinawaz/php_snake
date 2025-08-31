<?php

namespace Tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Snake\Database\MySql;
use Snake\Database\MySqlTable;

#[TestDox("Snake\Database\MySqlTable::class (Tests\MySqlTable)")]
class MySqlTableTest extends TestCase
{
    protected static \mysqli $conn;

    public static function setUpBeforeClass(): void
    {
        $host = getenv('DB_HOST');
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASS');
        $db_name = getenv('DB_NAME');

        $test_db_config = [
            'host' => $host,
            'dbname' => $db_name,
            'username' => $user,
            'password' => $pass,
            'charset' => 'utf8mb4'
        ];

        $conn = new \mysqli($host, $user, $pass);
        if ($conn->connect_error) {
            die("MySQL Connection failed: " . $conn->connect_error);
        }

        $conn->query("DROP DATABASE IF EXISTS `$db_name`");
        $conn->query("CREATE DATABASE `$db_name`");
        $conn->select_db($db_name);

        global $db;
        $db = new MySql($test_db_config);

        self::$conn = $conn;

        self::createSchema();
    }

    protected static function createSchema(): void
    {
        $queries = [
            "CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) UNIQUE NOT NULL
            )",
            "CREATE TABLE posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(200) NOT NULL,
                body TEXT,
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            "CREATE TABLE comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                user_id INT NOT NULL,
                comment TEXT NOT NULL,
                FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE,
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
            )"
        ];
        foreach ($queries as $sql) {
            self::$conn->query($sql);
        }
    }

    public function testInsertAndFirst(): void
    {
        $users = new MySqlTable('users');
        $alice = $users->insert(['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->assertNotNull($alice);
        $this->assertSame('Alice', $alice->name);

        $found = $users->where(['id' => $alice->id])->first();
        $this->assertSame('alice@example.com', $found->email);
    }

    public function testSelectAndGetWithConditions(): void
    {
        $users = new MySqlTable('users');
        $users->insert(['name' => 'Bob', 'email' => 'bob@example.com']);
        $users->insert(['name' => 'Charlie', 'email' => 'charlie@example.com']);

        $result = $users->select(['id', 'name'])
            ->where(['name' => 'Charlie'])
            ->get();

        $this->assertCount(1, $result);
        $this->assertSame('Charlie', $result[0]->name);
        $this->assertFalse(property_exists($result[0], 'email'));
    }

    public function testLimitAndOrderBy(): void
    {
        $users = new MySqlTable('users');
        $users->insert(['name' => 'Dave', 'email' => 'dave@example.com']);
        $users->insert(['name' => 'Eve', 'email' => 'eve@example.com']);

        $result = $users->sort_by('name', 'DESC')->limit(1)->get();
        $this->assertSame('Eve', $result[0]->name);
    }

    public function testGroupByAndCount(): void
    {
        $posts = new MySqlTable('posts');
        $users = new MySqlTable('users');
        $uid = $users->insert(['name' => 'Frank', 'email' => 'frank@example.com'])->id;

        $posts->insert(['user_id' => $uid, 'title' => 'Post 1', 'body' => 'Body']);
        $posts->insert(['user_id' => $uid, 'title' => 'Post 2', 'body' => 'Body']);

        $count = $posts->where(['user_id' => $uid])->count();
        $this->assertSame(2, $count);
    }

    public function testUpdate(): void
    {
        $users = new MySqlTable('users');
        $user = $users->insert(['name' => 'Grace', 'email' => 'grace@example.com']);

        $updated = $users->where(['id' => $user->id])->update(['name' => 'Grace Updated']);
        $this->assertTrue($updated);

        $check = $users->where(['id' => $user->id])->first();
        $this->assertSame('Grace Updated', $check->name);
    }

    public function testDelete(): void
    {
        $users = new MySqlTable('users');
        $user = $users->insert(['name' => 'Hank', 'email' => 'hank@example.com']);

        $deleted = $users->where(['id' => $user->id])->delete();
        $this->assertTrue($deleted);

        $check = $users->where(['id' => $user->id])->first();
        $this->assertNull($check);
    }

    public function testAll(): void
    {
        $users = new MySqlTable('users');
        $all = $users->all();
        $this->assertGreaterThan(0, count($all));
    }

    public function testRawQuery(): void
    {
        $users = new MySqlTable('users');
        $rows = $users->rawQuery("SELECT * FROM users WHERE name = 'Alice'");
        $this->assertNotEmpty($rows);
        $this->assertSame('Alice', $rows[0]['name']);
    }

    public function testResetClearsState(): void
    {
        $users = new MySqlTable('users');
        $users->where(['id' => 1])->limit(1)->sort_by('id', 'DESC');
        $users->reset();

        $reflection = new \ReflectionClass($users);
        $prop = $reflection->getProperty('where');
        $prop->setAccessible(true);

        $this->assertEmpty($prop->getValue($users));
    }
}
