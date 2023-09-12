<?php

namespace htmxthing;

use Faker\Factory;
use Faker\Generator;
use PDO;

class Storage
{
    private Generator $faker;
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->faker = Factory::create();
        $this->faker->seed("This my seed, many like it, but this mine.");
        $this->db = $db;
    }

    public function getPeopleCount(): int
    {
        return $this->db->query("SELECT COUNT(*) AS people_count FROM people")->fetchColumn();
    }

    public function getPeople(): array
    {
        return $this->db->query("SELECT * FROM people")->fetchAll();
    }

    public function getPerson(int $id): array
    {
        $statement = $this->db->prepare("SELECT * FROM people WHERE id = :id");
        $statement->execute(['id' => $id]);
        return $statement->fetch();
    }

    public function savePerson(array $p): void
    {
        $this->db->prepare("UPDATE people set name = :name, email = :email WHERE id = :id")
            ->execute($p);
    }

    public function seedDatabase(int $numberOfPeople): void
    {
        $this->db->exec("DROP TABLE IF EXISTS people");
        $this->db->exec("CREATE TABLE people (
            id serial primary key,
            name varchar(200) not null,
            email varchar(200) not null
            );");
        $statement = $this->db->prepare(
            "INSERT INTO people (name, email) VALUES (:name, :email)"
        );
        for ($i = 0; $i < $numberOfPeople; $i++) {
            $record = [
                "name" => $this->faker->name(),
                "email" => $this->faker->email(),
            ];
            $statement->execute($record);
        }
    }

    public function deletePerson(int $id): int
    {
        return $this->db->prepare("DELETE FROM people WHERE id = :id")
            ->execute(["id" => $id]);
    }

    public function addPerson(array $array): int
    {
        $statement = $this->db->prepare("INSERT INTO people (name, email) VALUES (:name, :email)");
        $statement->execute($array);
        return $this->db->lastInsertId();
    }
}
