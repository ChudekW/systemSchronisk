# ŁapaRazem — ogólny opis projektu

## 1. Koncepcja

ŁapaRazem to wieloorganizacyjna platforma dla schronisk, fundacji, domów tymczasowych, wolontariuszy, lekarzy weterynarii, gmin oraz osób zainteresowanych adopcją.

System łączy panel operacyjny dla organizacji, aplikację mobilną dla pracowników i wolontariuszy oraz publiczną wyszukiwarkę zwierząt.

## 2. Cele projektu

- uporządkowanie danych o zwierzętach;
- usprawnienie codziennej pracy schronisk;
- skrócenie i uporządkowanie procesu adopcyjnego;
- ułatwienie koordynacji wolontariuszy i domów tymczasowych;
- zapewnienie pełnej historii opieki, leczenia i zmian statusu;
- automatyzacja raportów i dokumentów;
- zwiększenie widoczności zwierząt potrzebujących domu;
- stworzenie wspólnej platformy dla wielu organizacji.

## 3. Użytkownicy systemu

- administrator platformy;
- administrator schroniska;
- kierownik i pracownik schroniska;
- koordynator adopcji;
- koordynator wolontariuszy;
- lekarz weterynarii;
- wolontariusz;
- opiekun domu tymczasowego;
- przedstawiciel gminy;
- osoba zainteresowana adopcją;
- darczyńca.

## 4. Główne moduły

### 4.1. Ewidencja zwierząt

Każde zwierzę posiada kartę z informacjami o gatunku, rasie, wieku, płci, numerze mikroczipa, miejscu znalezienia, dacie przyjęcia, zdjęciach, charakterze, lokalizacji oraz aktualnym statusie.

System przechowuje pełną historię zmian, między innymi:

`znalezione → przyjęte → kwarantanna → leczenie → gotowe do adopcji → rezerwacja → adopcja`

### 4.2. Przyjęcia i wydania

- przyjęcie zwierzęcia;
- zgłoszenie znalezienia;
- interwencja;
- przekazanie przez właściciela;
- zwrot właścicielowi;
- wydanie do adopcji;
- przekazanie do domu tymczasowego;
- transfer do innej organizacji;
- rejestracja zgonu;
- generowanie protokołów i dokumentów.

### 4.3. Leczenie i zdrowie

- wizyty weterynaryjne;
- diagnozy i zabiegi;
- szczepienia, odrobaczenia i kastracje;
- leki i dawkowanie;
- badania i dokumenty;
- koszty leczenia;
- kwarantanna i izolacja;
- przypomnienia o terminach;
- historia medyczna dostępna dla uprawnionych osób.

### 4.4. Adopcje

Proces obejmuje formularz adopcyjny, weryfikację kandydata, kontakt, spotkanie, wizytę przedadopcyjną, rezerwację, umowę, wydanie zwierzęcia oraz kontakt poadopcyjny.

Funkcje:

- elektroniczny formularz adopcyjny;
- status zgłoszenia;
- komentarze pracowników;
- generowanie umów PDF;
- rejestr adopcji i zwrotów;
- ankiety poadopcyjne;
- dopasowanie zwierzęcia do stylu życia opiekuna.

### 4.5. Publiczna platforma adopcyjna

- wyszukiwarka zwierząt z wielu organizacji;
- filtry według lokalizacji, gatunku, wieku, wielkości i charakteru;
- profile zwierząt z fotografiami i opisami;
- zgłoszenie zainteresowania adopcją;
- zapisane wyszukiwania i alerty;
- udostępnianie profili;
- zgłaszanie nieaktualnych danych.

### 4.6. Wolontariusze

- rejestr wolontariuszy;
- grafiki dyżurów;
- zapisy na spacery;
- zadania i checklisty;
- notatki po spacerach;
- zgłaszanie problemów;
- szkolenia i uprawnienia;
- komunikacja z koordynatorem.

### 4.7. Domy tymczasowe

- baza opiekunów;
- dostępne miejsca;
- preferencje domu;
- umowy;
- raporty opieki;
- zdjęcia i obserwacje;
- koszty utrzymania;
- zapotrzebowanie na karmę lub leczenie.

### 4.8. Zadania i organizacja pracy

- karmienie;
- podawanie leków;
- spacery;
- sprzątanie i dezynfekcja;
- kontrole boksów;
- transporty;
- wizyty weterynaryjne;
- przypisywanie osób i terminów;
- priorytety i status wykonania.

### 4.9. Boksy i lokalizacje

- mapa boksów;
- pojemność i zajętość;
- lokalizacje kwarantanny i izolacji;
- historia przeprowadzek;
- ostrzeżenia dotyczące zwierząt wymagających szczególnej uwagi.

### 4.10. Magazyn i potrzeby

- karma;
- leki;
- środki czystości;
- akcesoria;
- stany minimalne;
- daty ważności;
- darowizny rzeczowe;
- publiczna lista bieżących potrzeb.

### 4.11. Darowizny

- szybkie płatności;
- zbiórki celowe;
- wsparcie konkretnego zwierzęcia;
- wirtualna adopcja;
- płatności cykliczne;
- kampanie ratunkowe;
- raportowanie wykorzystania środków.

### 4.12. Raporty

- przyjęcia i wydania;
- adopcje;
- zwroty właścicielom;
- transfery;
- zgony;
- średni czas pobytu;
- stan zwierząt;
- obłożenie miejsc;
- leczenie i koszty;
- eksport PDF, Excel i CSV;
- dziennik zmian.

## 5. Aplikacja mobilna Android/iOS

Aplikacja mobilna jest przeznaczona dla pracowników, wolontariuszy i opiekunów domów tymczasowych.

### Funkcje pracownika

- szybkie wyszukiwanie zwierzęcia;
- skanowanie kodu QR lub mikroczipa;
- podgląd karty zwierzęcia;
- dodawanie zdjęć i filmów;
- notatki tekstowe i głosowe;
- potwierdzanie karmienia i podania leków;
- kontrola boksu;
- rejestrowanie spacerów;
- zgłaszanie problemów;
- zmiana lokalizacji zwierzęcia;
- powiadomienia o pilnych zadaniach.

### Funkcje wolontariusza

- grafik dyżurów;
- zapisy na spacery;
- lista zadań;
- potwierdzanie wykonania;
- notatka po spacerze;
- zdjęcia i propozycje opisów;
- zgłaszanie problemów;
- komunikaty od koordynatora.

### Tryb offline

Aplikacja powinna umożliwiać pracę przy słabym zasięgu. Dane, zdjęcia i notatki są zapisywane lokalnie i synchronizowane po odzyskaniu połączenia.

## 6. Zasady działania

1. Każde schronisko działa w odizolowanej przestrzeni organizacyjnej.
2. Administrator schroniska zarządza użytkownikami i uprawnieniami.
3. Dane publiczne są publikowane tylko po zatwierdzeniu przez organizację.
4. Dane prywatne, medyczne i adopcyjne są dostępne wyłącznie uprawnionym osobom.
5. Każda istotna zmiana jest zapisywana w historii.
6. Status zwierzęcia determinuje dostępne działania.
7. Wycofanie zwierzęcia z adopcji automatycznie usuwa je z publicznych wyników.
8. System powinien umożliwiać eksport danych organizacji.
9. Synchronizacja z aplikacją mobilną nie może powodować utraty danych.
10. Każda organizacja zachowuje kontrolę nad swoimi danymi.

## 7. Model platformy

Platforma działa jako system multi-tenant. Oznacza to, że wiele organizacji korzysta z jednej aplikacji, ale każda ma własne dane, użytkowników, ustawienia i dokumenty.

Wspólna pozostaje publiczna część adopcyjna, w której — za zgodą organizacji — prezentowane są zwierzęta gotowe do adopcji.

