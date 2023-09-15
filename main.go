package main

import (
	"fmt"
	"htmxthing/storage"
)

func main() {
    people, err := storage.GetPeople(10)
    if err != nil {
        panic(err)
    }
    for i := range people {
        p:= people[i]
        fmt.Println(p.ID, p.Name, p.Email)
    }
}
