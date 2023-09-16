package storage

import (
	"database/sql"
	"time"

	// For side effects, in this case it means that the std. sql module can be
	// used for accessing the database and it'll use this mysql driver.
	_ "github.com/go-sql-driver/mysql"
)

type Person struct {
	ID    int
	Name  string
	Email string
}

func getConnection() (*sql.DB, error) {
	db, err := sql.Open("mysql", "juchtmpe:@unix(/tmp/mysql.sock)/test?charset=utf8")
	if err != nil {
		return nil, err
	}

	// Apparently this is necessary so that everything is nicely cleaned up
	// and connection don't stay open, nor get closed prematurely
	db.SetConnMaxLifetime(time.Minute * 3)
	db.SetMaxOpenConns(10)
	db.SetMaxIdleConns(10)
	return db, err
}

func GetPeople(amount int) ([]Person, error) {
	db, err := getConnection()
	if err != nil {
		return nil, err
	}
	rows, err := db.Query("SELECT id, name, email FROM people LIMIT ?", amount)

	if err != nil {
		return nil, err
	}

	// Releases any resources, no matter how this functions returns.
	// Note that looping all the way through the rows also closes it implicitly,
	// but it's better to use `defer` to make sure the rows are closed no matter
	// what.
	// This should come _after_ the above error check, otherwise rows.Close()
	// will segfault first.
	defer rows.Close()
	defer db.Close()

	var people []Person
	for rows.Next() {
		var p Person
		if err := rows.Scan(&p.ID, &p.Name, &p.Email); err != nil {
			return people, err
		}
		people = append(people, p)
	}
	if err = rows.Err(); err != nil {
		return people, err
	}
	return people, nil
}
