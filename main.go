package main

import (
	"fmt"
	"html"
	"htmxthing/storage"
	"net/http"
)

func peopleList(w http.ResponseWriter, request *http.Request) {
	people, err := storage.GetPeople(10)
	if err != nil {
		panic(err)
	}
	w.WriteHeader(http.StatusOK)
	for i := range people {
		p := people[i]
		w.Write([]byte(html.EscapeString(fmt.Sprint(p.ID) + ", " + p.Name + ", " + p.Email)))
	}
}

func main() {
	http.HandleFunc("/people", peopleList)
	http.ListenAndServe(":8000", nil)
}
