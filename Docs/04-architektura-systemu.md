# ŁapaRazem — dokumentacja architektury systemu

## 1. Cel dokumentu

Dokument opisuje docelową architekturę platformy ŁapaRazem — systemu dla schronisk, fundacji, domów tymczasowych, wolontariuszy, gmin, darczyńców i osób zainteresowanych adopcją.

Architektura zakłada:

- Laravel jako centralny backend;
- Docker jako standard uruchamiania środowisk;
- PostgreSQL jako główną bazę danych;
- panel webowy dla organizacji;
- aplikację mobilną Android/iOS dla pracowników i wolontariuszy;
- publiczny portal adopcyjny;
- obsługę darowizn, zbiórek i grantów;
- możliwość dalszego skalowania bez przedwczesnego przechodzenia na mikroserwisy.

## 2. Założenia architektoniczne

1. System jest platformą wieloorganizacyjną typu multi-tenant.
2. Każde schronisko lub fundacja posiada własną przestrzeń danych.
3. Dane publiczne mogą być prezentowane we wspólnej wyszukiwarce adopcyjnej.
4. Laravel jest źródłem prawdy dla logiki biznesowej.
5. Aplikacja mobilna korzysta z tego samego API co inne klienty.
6. Operacje długotrwałe są wykonywane asynchronicznie przez kolejki.
7. Zdjęcia i dokumenty są przechowywane w storage obiektowym, nie w kontenerze aplikacji.
8. Wszystkie ważne operacje są rejestrowane w historii zmian.
9. Uprawnienia są sprawdzane na poziomie organizacji, roli i konkretnej operacji.
10. Pierwsza wersja działa jako modularny monolit Laravel.

## 3. Widok wysokiego poziomu

```text
                        UŻYTKOWNICY
       ┌─────────────────┼─────────────────┐
       │                 │                 │
  Panel webowy     Aplikacja mobilna   Portal publiczny
  pracowników      Android / iOS       adopcji i zbiórek
       │                 │                 │
       └─────────────────┼─────────────────┘
                         │
                  Laravel API / Web
                         │
       ┌─────────────────┼─────────────────┐
       │                 │                 │
 PostgreSQL          Redis             Storage
 baza danych       cache/kolejki       zdjęcia/pliki
                         │
                   Worker / Scheduler
                         │
              Integracje i usługi zewnętrzne
```

## 4. Główne komponenty

### 4.1. Backend Laravel

Backend odpowiada za:

- autoryzację i użytkowników;
- organizacje i multi-tenancy;
- role i uprawnienia;
- ewidencję zwierząt;
- przyjęcia i wydania;
- leczenie;
- adopcje;
- wolontariuszy;
- domy tymczasowe;
- zadania i grafiki;
- zbiórki i darowizny;
- granty;
- raporty;
- powiadomienia;
- integracje;
- API dla aplikacji mobilnej.

### 4.2. Panel webowy

Panel webowy jest przeznaczony dla administracji organizacji, pracowników, koordynatorów, lekarzy i uprawnionych przedstawicieli gmin.

Rekomendowany stack:

- Vue 3 + TypeScript;
- Inertia.js 3;
- Laravel Vue Starter Kit;
- Tailwind CSS;
- własny system komponentów UI.

Panel powinien obsługiwać złożone formularze, tabele, raporty, filtrowanie, zarządzanie dokumentami i konfigurację organizacji.

### 4.3. Aplikacja mobilna

Aplikacja Android/iOS jest przeznaczona przede wszystkim dla pracowników, wolontariuszy i domów tymczasowych.

Rekomendowany stack:

- Flutter;
- Riverpod;
- Dio;
- go_router;
- lokalna baza danych Drift lub Isar;
- Firebase Cloud Messaging;
- skaner QR i kodów kreskowych;
- obsługa aparatu i Bluetooth.

Główne zadania aplikacji:

- szybki dostęp do karty zwierzęcia;
- skanowanie QR lub mikroczipa;
- zdjęcia i notatki;
- karmienie i podawanie leków;
- spacery;
- zadania i checklisty;
- zgłaszanie problemów;
- powiadomienia;
- praca offline.

### 4.4. Portal publiczny

Portal publiczny prezentuje:

- schroniska i fundacje;
- zwierzęta do adopcji;
- wyszukiwarkę i filtry;
- formularze adopcyjne;
- zbiórki;
- listy potrzeb;
- informacje o darowiznach;
- aktualności i wydarzenia.

Na początku portal działa w tej samej aplikacji Laravel + Vue + Inertia. SSR można włączyć dla potrzeb SEO. Oddzielny frontend, np. Next.js, można wprowadzić później, jeśli wzrośnie ruch lub wymagania SEO.

## 5. Proponowany stack technologiczny

| Obszar | Technologia |
|---|---|
| Backend | Laravel 13 |
| PHP | PHP 8.5 |
| Panel webowy | Vue 3 + Inertia.js 3 + TypeScript |
| Style | Tailwind CSS |
| API | Laravel REST API + Sanctum |
| Baza danych | PostgreSQL |
| Geolokalizacja | PostGIS |
| Cache | Redis |
| Kolejki | Redis + Laravel Horizon |
| Harmonogram | Laravel Scheduler |
| WebSockety | Laravel Reverb |
| Aplikacja mobilna | Flutter |
| Lokalna baza mobilna | Drift lub Isar |
| Pliki | S3-compatible storage |
| Lokalny storage | MinIO |
| Reverse proxy | Nginx lub Traefik |
| Kontenery | Docker + Docker Compose |
| Płatności | Stripe lub Przelewy24 |
| Push notifications | Firebase Cloud Messaging + APNs |
| Monitoring | Sentry + Laravel Pulse |
| Testy | Pest/PHPUnit, PHPStan, Laravel Pint |

## 6. Modularny monolit

Na początku aplikacja powinna być jednym wdrażanym systemem, ale podzielonym na moduły domenowe.

Proponowana struktura:

```text
app/
├── Domain/
│   ├── Organizations/
│   ├── Users/
│   ├── Animals/
│   ├── Admissions/
│   ├── Medical/
│   ├── Adoptions/
│   ├── Volunteers/
│   ├── TemporaryHomes/
│   ├── Tasks/
│   ├── Inventory/
│   ├── Donations/
│   ├── Fundraising/
│   ├── Grants/
│   ├── Reports/
│   └── Notifications/
├── Application/
├── Infrastructure/
└── Support/
```

Każdy moduł powinien zawierać własne:

- modele i encje;
- akcje aplikacyjne;
- polityki;
- walidatory;
- eventy i listenery;
- joby;
- repozytoria, jeśli są potrzebne;
- testy.

## 7. Multi-tenancy

### Model początkowy

Rekomendowany model dla MVP:

```text
jedna aplikacja
jedna baza PostgreSQL
wspólne tabele
organization_id w tabelach biznesowych
```

Przykładowe powiązanie:

```text
organizations
    ├── users
    ├── animals
    ├── adoptions
    ├── medical_records
    ├── tasks
    ├── donations
    └── reports
```

### Zasady izolacji danych

- każdy rekord organizacji posiada `organization_id`;
- aktywna organizacja jest ustalana na podstawie kontekstu użytkownika;
- wszystkie zapytania biznesowe są ograniczane do aktywnej organizacji;
- polityki Laravel sprawdzają organizację i rolę;
- testy muszą potwierdzać brak dostępu między organizacjami;
- administrator platformy posiada osobny, kontrolowany zakres dostępu.

W przyszłości, przy dużej skali, możliwe jest przejście na osobne schematy lub bazy danych.

## 8. Model domenowy

Najważniejsze encje:

```text
Organization
User
Role
Animal
AnimalStatus
AnimalLocation
Admission
Transfer
MedicalRecord
Medication
Task
VolunteerShift
TemporaryHome
AdoptionApplication
Adoption
DonationCampaign
Donation
Grant
InventoryItem
Document
Notification
AuditLog
```

### Historia zwierzęcia

Historia zwierzęcia powinna być budowana z osobnych zdarzeń i rekordów, a nie tylko z nadpisywania pól.

```text
Animal
 ├── Admissions
 ├── Transfers
 ├── MedicalRecords
 ├── StatusHistory
 ├── LocationHistory
 ├── AdoptionApplications
 ├── Documents
 └── Notes
```

## 9. API

API jest przeznaczone dla:

- aplikacji mobilnej;
- przyszłych integracji;
- widgetów na stronach schronisk;
- systemów zewnętrznych;
- potencjalnie portalu Next.js.

Przykładowe zasoby:

```text
GET    /api/v1/animals
GET    /api/v1/animals/{animal}
POST   /api/v1/animals/{animal}/notes
POST   /api/v1/animals/{animal}/photos
POST   /api/v1/tasks/{task}/complete
POST   /api/v1/walks
GET    /api/v1/volunteer/shifts
POST   /api/v1/sync
```

API powinno posiadać:

- wersjonowanie `/api/v1`;
- Sanctum;
- rate limiting;
- walidację requestów;
- spójny format błędów;
- paginację;
- idempotency keys dla operacji finansowych i synchronizacji;
- dokumentację OpenAPI.

## 10. Synchronizacja offline

### Przepływ

```text
Użytkownik wykonuje akcję offline
        ↓
Zapis lokalny w aplikacji
        ↓
Kolejka zmian lokalnych
        ↓
Odzyskanie połączenia
        ↓
Wysyłka do API
        ↓
Walidacja i zapis w Laravelu
        ↓
Potwierdzenie lub konflikt
```

Każda zmiana offline powinna posiadać:

- lokalny identyfikator;
- typ operacji;
- czas wykonania;
- użytkownika;
- urządzenie;
- status synchronizacji;
- wersję rekordu.

Konflikty nie powinny być rozwiązywane przez bezwarunkowe nadpisanie danych. System powinien oznaczyć konflikt i umożliwić jego obsługę.

## 11. Docker i środowiska

### Środowisko lokalne

```text
app           Laravel / PHP-FPM
nginx         serwer HTTP
postgres      baza danych
redis         cache i kolejki
queue         worker kolejki
scheduler     harmonogram zadań
horizon       monitoring kolejek
mailpit       lokalne e-maile
minio         lokalne pliki
```

### Środowisko produkcyjne

```text
Internet
   ↓
Reverse proxy / TLS
   ↓
Nginx
   ↓
Laravel PHP-FPM
   ├── PostgreSQL
   ├── Redis
   ├── Queue workers
   ├── Scheduler
   └── S3-compatible storage
```

Kontenery aplikacyjne powinny być bezstanowe. Dane trwałe muszą znajdować się w PostgreSQL, Redisie zgodnie z jego przeznaczeniem oraz storage obiektowym.

## 12. Kolejki i zadania asynchroniczne

Kolejki powinny obsługiwać:

- e-maile;
- SMS-y;
- push notifications;
- generowanie PDF;
- generowanie miniaturek zdjęć;
- raporty;
- synchronizację z zewnętrznymi bazami;
- webhooki płatnicze;
- eksporty danych;
- powiadomienia o terminach;
- przetwarzanie danych offline.

Operacje finansowe muszą być idempotentne. Ten sam webhook lub request nie może zaksięgować darowizny albo transferu drugi raz.

## 13. Pliki i multimedia

### Storage

- lokalnie: MinIO;
- produkcyjnie: S3, Cloudflare R2, Wasabi lub inny kompatybilny storage;
- w bazie: ścieżka, typ, rozmiar, właściciel i metadane.

### Zasady

- oryginały zdjęć są przechowywane prywatnie;
- publiczne zdjęcia są udostępniane przez kontrolowane URL-e;
- generowane są miniatury;
- dokumenty adopcyjne są prywatne;
- pliki są skanowane i walidowane;
- usunięcie rekordu nie powinno automatycznie usuwać pliku bez polityki retencji.

## 14. Płatności, zbiórki i granty

### Zbiórki

System obsługuje:

- zbiórki celowe;
- darowizny jednorazowe;
- darowizny cykliczne;
- wirtualne adopcje;
- listy potrzeb;
- cele organizacji;
- aktualizacje zbiórek;
- raportowanie wykorzystania środków.

### Przepływ płatności

```text
Darczyńca
   ↓
Operator płatności
   ↓
Webhook Laravel
   ↓
Weryfikacja podpisu
   ↓
Idempotentny zapis darowizny
   ↓
Aktualizacja zbiórki
   ↓
Powiadomienie i potwierdzenie
```

Nie należy uznawać płatności za zakończoną wyłącznie na podstawie przekierowania użytkownika. Źródłem prawdy jest zweryfikowany webhook operatora.

### Granty

Moduł grantów powinien przechowywać:

- konkursy;
- terminy;
- wymagania;
- budżety;
- załączniki;
- statusy wniosków;
- osoby odpowiedzialne;
- harmonogramy;
- raporty rozliczeniowe.

## 15. Bezpieczeństwo

### Uwierzytelnianie

- Fortify dla panelu webowego;
- Sanctum dla aplikacji mobilnej;
- 2FA dla administratorów;
- reset hasła;
- weryfikacja e-mail;
- możliwość unieważnienia sesji i tokenów;
- ograniczanie prób logowania.

### Autoryzacja

- role;
- permissions;
- policies;
- ograniczenie do organizacji;
- ograniczenie do lokalizacji;
- ograniczenie danych medycznych;
- osobne uprawnienia finansowe.

### Audyt

Audit log powinien rejestrować między innymi:

- logowania;
- zmiany statusów zwierząt;
- zmiany danych medycznych;
- dostęp do danych prywatnych;
- operacje finansowe;
- eksporty;
- usunięcia i archiwizacje;
- zmiany uprawnień.

## 16. Monitoring i utrzymanie

Minimum produkcyjne:

- monitoring błędów przez Sentry;
- monitoring aplikacji przez Laravel Pulse;
- monitoring Redis i kolejek;
- alerty o nieudanych jobach;
- alerty o braku miejsca;
- health check aplikacji;
- kopie bazy danych;
- test odtwarzania backupu;
- centralne logi;
- monitoring certyfikatów TLS.

## 17. Testy

### Testy backendu

- testy jednostkowe domeny;
- testy feature API;
- testy polityk;
- testy multi-tenancy;
- testy webhooków;
- testy raportów;
- testy generowania dokumentów.

### Testy aplikacji mobilnej

- testy jednostkowe;
- testy synchronizacji;
- testy trybu offline;
- testy konfliktów;
- testy skanowania QR;
- testy podstawowych ścieżek użytkownika.

### Najważniejsze scenariusze bezpieczeństwa

1. Organizacja A nie może odczytać danych organizacji B.
2. Wolontariusz nie może zmienić danych finansowych.
3. Użytkownik nie może zobaczyć prywatnych danych adoptującego bez uprawnienia.
4. Webhook płatności nie może zostać zaksięgowany podwójnie.
5. Nieaktualne zwierzę nie może być prezentowane jako dostępne do adopcji.
6. Konflikt synchronizacji nie może bezwarunkowo nadpisać nowszych danych.

## 18. CI/CD

Pipeline powinien obejmować:

1. instalację zależności;
2. analizę statyczną PHP;
3. formatowanie i linting;
4. testy jednostkowe;
5. testy feature;
6. testy API;
7. budowę obrazu Docker;
8. skan bezpieczeństwa obrazu;
9. wdrożenie na środowisko testowe;
10. migracje po akceptacji;
11. wdrożenie produkcyjne;
12. health check i możliwość rollbacku.

## 19. Strategia skalowania

### Początkowo

- jeden serwer aplikacyjny;
- PostgreSQL;
- Redis;
- jeden lub dwa workery;
- storage obiektowy;
- Docker Compose.

### Przy wzroście ruchu

- osobne workery kolejek;
- read replica PostgreSQL;
- osobny serwer dla raportów;
- CDN dla zdjęć;
- cache publicznych profili;
- skalowanie aplikacji horyzontalnie;
- osobny serwis wyszukiwania, np. Meilisearch lub OpenSearch.

### Kiedy rozważyć mikroserwisy

Mikroserwisy mają sens dopiero wtedy, gdy konkretny moduł ma niezależne wymagania dotyczące skalowania lub wdrażania. Potencjalnymi kandydatami są:

- wyszukiwarka publiczna;
- przetwarzanie zdjęć;
- system powiadomień;
- płatności;
- raportowanie;
- synchronizacja z zewnętrznymi bazami.

## 20. Rekomendacja końcowa

Najlepsza architektura dla pierwszej wersji to:

```text
Laravel 13 + PHP 8.5
PostgreSQL + PostGIS
Redis + Horizon
Vue 3 + Inertia.js 3 + Tailwind CSS 4
Laravel Sanctum REST API
Flutter Android/iOS
Docker Compose
S3-compatible storage
Stripe lub Przelewy24
Sentry + Laravel Pulse
```

System powinien rozpocząć jako modularny monolit Laravel. Zapewni to szybki rozwój, prostsze wdrożenie i łatwe utrzymanie, jednocześnie pozostawiając możliwość późniejszego wydzielenia wybranych modułów.


## 21. Wdrożony fundament organizacji

Panel webowy pozostaje na Vue 3, Inertia 3, Tailwind 4 i istniejących komponentach Reka UI. Logika biznesowa i autoryzacja są po stronie Laravel. API mobilne będzie osobnym interfejsem do tej samej logiki, bez wymogu przepisywania panelu na klienta REST.

Organizacje tworzy wyłącznie superadministrator platformy. Uprawnienie `users.is_super_admin` jest nadawane jawnie poleceniem `app:promote-platform-administrator`; adres e-mail nie jest regułą autoryzacji. Pierwszym wskazanym zarządcą jest konto `admin@dev10.pro`.

W aktualnym etapie użytkownik należy do jednej organizacji (`users.organization_id`) albo nie ma przypisania. Administrator organizacji ma dodatkowo `is_organization_admin`. Przynależność i role nie są dostępne w formularzu rejestracji ani aktualizacji profilu. Publiczna rejestracja nie nadaje dostępu do organizacji.

Utworzenie organizacji i jej administratora odbywa się transakcyjnie. Zarządca podaje nazwę, dane schroniska, e-mail administratora i hasło startowe. Hasło jest hashowane i nie jest zwracane w odpowiedzi. Administrator potwierdza adres e-mail standardowym mechanizmem Fortify. Dane dostępowe przekazuje zarządca bezpiecznym kanałem; aplikacja nie wysyła hasła e-mailem.

Organizacja zawiera na razie dane jednego schroniska (`shelter_name`, adres i kontakt). Oddzielne placówki, zarządzanie pracownikami i zwierzęta są kolejnymi modułami. Każdy przyszły rekord biznesowy musi być przypisany do organizacji i objęty kontrolą dostępu.

Panel platformy `/organizations` wymaga uprawnienia `manage-organizations`. Panel tenantowy `/organization` pobiera organizację wyłącznie z zalogowanego użytkownika; nie przyjmuje identyfikatora tenanta z formularza. Administrator organizacji nie może zmieniać aktywności organizacji ani własnych uprawnień. Wyłączenie organizacji blokuje dostęp do jej panelu; konto użytkownika i jego ustawienia pozostają dostępne.

Model członkostwa wielu organizacji, osobne role pracowników, historia audytowa i kolejne moduły są docelowym rozszerzeniem, a nie częścią obecnego wdrożenia.
