## Oddawanie projektów:

Demonstracja projektu najpóźniej **do 16.07.2025r.** do godz 20.

Oddanie projektu po przekroczeniu terminu skutkuje otrzymaniem maksymalnej oceny dostatecznej.

Projekt ukończony przez studenta powinien być zgłoszony do prowadzącego poprzez kontakt mailowy (grzegorzo96@mat.umk.pl) w celu umówienia terminu zaliczenia projektu na platformie MSTeams.


### Wymagania techniczne:

- PHP 8.2 lub wyższy
- Symfony 6.4
- Docker
- Composer do zarządzania zależnościami
- GIT (projekt powinien być dostępny w repozytorium np. na GitHub)
  Zaliczenie przedmiotu:

### Projekt powinien demonstrować:
- wykorzystanie PHP'a wraz zasadami SOLID
- wykorzystanie Symfony oraz Dockera
- pisanie czystego kodu
- odpowiednią dokumentację (PHPDoc)
- konwencję nazewnictwa i commitów w repozytorium

### Ocena dst.

- projekt odpala się na serwerze lokalnym (z pełną konfiguracją w .env.example)
- projekt posiada bazę danych z przynajmniej 6 tabelami (z polami odpowiedniego typu, relacjami min. 3 relacje między tabelami)
- projekt umożliwia logowanie, dodawanie, edycję, usuwanie, wyświetlenie listy z obiektami (minimum 4 podstawowe operacje CRUD dla każdej głównej encji)
- projekt oparty na silniku szablonów TWIG
- architektura dowolna (polecam MVC na dobry początek, dla bardzo zaawansowanych może być DDD)
- podstawowe README z instrukcją uruchomienia projektu

### Ocena dst.+

jw. +
- dodanie testów jednostkowych przy pomocy PHP Unit (dodawania elementów, usuwania elementów, edycję oraz sprawdzenie szczegółów danego obiektu)
- minimalne pokrycie testami na poziomie 50%

### Ocena db.

jw. +
- integracja z jednym zewnętrznym API (wykorzystując Symfony HTTP Client)

### Ocena db.+

jw. +
- walidacja wszystkich pól przy dodawaniu, edytowaniu (włącznie z własnymi walidatorami)
- logowanie dwuskładnikowe
- przynajmniej wykorzystanie jednej tabeli dynamicznej w formularzu (możliwość dodawania/usuwania wierszy, przy zapisie dane dodają się odpowiednio do encji)
- implementacja własnych klas wyjątków i ich obsługa w logice (np. przy braku możliwości znalezienia obiektu)
- obsługa języka polskiego i angielskiego
- proste API REST dla głównych encji

### Ocena bdb.

jw. +
- wysyłanie maili przy pomocy symfony/mailer
- wykorzystanie API Platform
- wykorzystanie eventów i event listenerów w projekcie (wraz z implementacją własnych typów eventów)
- implementacja kolejki wiadomości (Symfony Messenger)
- implementacja własnych komend konsolowych

END.
