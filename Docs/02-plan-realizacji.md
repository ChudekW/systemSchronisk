# ŁapaRazem — planowane prace

## Założenie

Projekt powinien być rozwijany etapami. Pierwsza wersja ma rozwiązać podstawowe problemy schroniska: ewidencję zwierząt, aktualizację danych i obsługę adopcji. Kolejne etapy będą rozszerzać system o pracę terenową, wolontariuszy, finansowanie i integracje.

## Etap 0 — analiza i przygotowanie

- rozmowy z kilkoma schroniskami i fundacjami;
- identyfikacja różnych sposobów pracy;
- zebranie wzorów dokumentów;
- określenie wymaganych raportów;
- analiza ról i uprawnień;
- przygotowanie modelu danych;
- przygotowanie architektury multi-tenant;
- wybór technologii webowej i mobilnej;
- przygotowanie makiet kluczowych ekranów;
- wybór organizacji pilotażowych.

## Etap 1 — fundament systemu

- rejestracja i logowanie;
- organizacje i schroniska;
- role i uprawnienia;
- profile użytkowników;
- konfiguracja schroniska;
- podstawowe słowniki;
- dziennik aktywności;
- podstawowe powiadomienia;
- kopie zapasowe i mechanizmy bezpieczeństwa.

## Etap 2 — ewidencja zwierząt

- karta zwierzęcia;
- gatunki, rasy, płeć i wiek;
- zdjęcia i multimedia;
- numer mikroczipa;
- miejsce znalezienia;
- przyjęcia i wydania;
- statusy;
- boksy i lokalizacje;
- historia zmian;
- import z Excela;
- eksport danych.

## Etap 3 — zdrowie i opieka

- wizyty weterynaryjne;
- leczenie i diagnozy;
- szczepienia;
- zabiegi;
- leki i dawki;
- przypomnienia;
- kwarantanna i izolacja;
- dokumentacja medyczna;
- koszty leczenia.

## Etap 4 — adopcje i portal publiczny

- publiczne profile zwierząt;
- wyszukiwarka i filtry;
- formularz adopcyjny;
- obsługa zgłoszeń;
- statusy procesu adopcyjnego;
- rezerwacje;
- umowy adopcyjne;
- generowanie PDF;
- informacje poadopcyjne;
- zapisane wyszukiwania i alerty.

## Etap 5 — aplikacja mobilna Android/iOS

- logowanie mobilne;
- role pracownika i wolontariusza;
- lista zwierząt;
- szybkie wyszukiwanie;
- skanowanie QR;
- dodawanie zdjęć;
- notatki;
- zadania i checklisty;
- rejestrowanie spacerów;
- potwierdzanie karmienia i leków;
- zgłaszanie problemów;
- powiadomienia push;
- podstawowy tryb offline;
- synchronizacja danych.

## Etap 6 — wolontariusze i domy tymczasowe

- grafiki;
- zapisy na dyżury;
- spacery;
- zadania cykliczne;
- komunikaty;
- szkolenia;
- domy tymczasowe;
- raporty opiekunów;
- koszty utrzymania;
- przekazywanie karmy i leków.

## Etap 7 — magazyn i finansowanie

- magazyn;
- stany minimalne;
- daty ważności;
- zapotrzebowanie;
- darowizny rzeczowe;
- płatności online;
- zbiórki celowe;
- wirtualne adopcje;
- płatności cykliczne;
- raporty darowizn.

## Etap 8 — raporty i administracja publiczna

- raporty miesięczne i roczne;
- raporty dla gmin;
- raporty dotyczące przepływu zwierząt;
- raporty leczenia i kosztów;
- raporty obłożenia;
- eksport PDF, Excel i CSV;
- historia zmian;
- walidacja brakujących danych;
- integracje z bazami mikrochipów.

## Etap 9 — integracje i automatyzacja

- API dla organizacji;
- integracje z systemami weterynaryjnymi;
- synchronizacja z bazami chipów;
- publikacja w mediach społecznościowych;
- SMS i e-mail;
- import danych z innych systemów;
- integracje księgowe;
- integracje z systemami płatności.

## Etap 10 — funkcje zaawansowane

- dopasowanie adopcyjne;
- rozpoznawanie podobnych profili zwierząt;
- automatyczne tworzenie opisów;
- automatyczne wykrywanie nieaktualnych ogłoszeń;
- prognozowanie obłożenia;
- analiza skuteczności adopcji;
- aplikacja dla lekarzy weterynarii;
- publiczne statystyki platformy.

## Priorytet MVP

Pierwsza wersja powinna zawierać:

1. organizacje, użytkowników i role;
2. ewidencję zwierząt;
3. zdjęcia i mikrochipy;
4. statusy i lokalizacje;
5. podstawową historię leczenia;
6. publiczne profile zwierząt;
7. formularz adopcyjny;
8. obsługę zgłoszeń;
9. podstawowe raporty;
10. aplikację mobilną z QR, zdjęciami, zadaniami i notatkami.

## Pilotaż

Przed szerszym wdrożeniem system powinien zostać przetestowany w kilku różnych organizacjach:

- dużym schronisku miejskim;
- małej fundacji;
- organizacji prowadzącej domy tymczasowe;
- organizacji z dużą liczbą wolontariuszy.

Pozwoli to wykryć różnice w sposobie pracy i uniknąć budowy systemu dopasowanego wyłącznie do jednego schroniska.

## Kryteria sukcesu pierwszej wersji

- pracownik może znaleźć kartę zwierzęcia w mniej niż 10 sekund;
- wolontariusz może potwierdzić spacer w kilku kliknięciach;
- schronisko może samodzielnie opublikować zwierzę do adopcji;
- formularz adopcyjny nie wymaga ręcznego przepisywania danych;
- raport można wygenerować bez pracy w Excelu;
- aplikacja mobilna działa przy chwilowym braku internetu;
- organizacja może wyeksportować swoje dane.

