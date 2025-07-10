# Garage 3D
Garaged - Strona z plikami do druku 3D (.stl)
(nazwa jest już w użyciu, trzeba by zmienić)

Strona do dzielenia się modelami do druku 3D, podobna jak [printables.com](https://www.printables.com).
Użytkownicy mogą umieszczać swoje przedmioty do wydruku (publicznie lub prywatnie).
Można przeglądać, polubić i pobierać przedmioty innych użytkowników. Przedmioty mogą
składać się z kilku plików stl. Do strony przedmiotu dołączony jest opis, zdjęcia przedmiotu,
może będzie dostępny podgląd 3D.


**Uwaga! Niektóre pliki .stl nie są mojego autorstwa.**
Nie widać ich w repozytorium na githubie, nie udostępniam ich publicznie.
Wykorzystane na zasadzie dozwolonego użytku (cele edukacyjne, projekt na zaliczenie)
jedynie jako placeholder.

W przypadku użycia produkcyjnego / komercyjnego tego projektu te dane należy usunąć.
<!--Jeśli będę pamiętał, w opisie każdego takiego pliku wstawię słowo "placeholder", aby
można je było łatwo usunąć.-->

Uruchamianie:

1. będąc w głównym katalogu projektu...
2. Uruchomić kontener Dockera `docker compose up`. Gdy już istnieje można uruchamiać kontener "garage3d" przez Docker Desktop.
3. Uruchomić serwer symfony `symfony console server:start`
4. Teraz można otworzyć stronę [localhost](http://localhost:8000/) w przeglądarce.