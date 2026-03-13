# Gym Booking API checkpoint 1-5

REST API для бронирования ресурсов (залы/площадки) с JWT-авторизацией, ролями, проверкой пересечения времени, расписанием и отзывами.

---

## 1. Цель проекта

Сделать API системы бронирования, где:

- есть роли `admin` и `user`;
- пользователь бронирует ресурс на дату/время;
- система блокирует пересечения интервалов;
- есть отмена бронирований с ролевыми ограничениями;
- есть расписание ресурса, поиск свободных ресурсов и отзывы;
- проект воспроизводимо запускается в Docker одной командой.

---

## 2. Предметная область и стек

Предметная область: бронирование спортивных ресурсов (зал, бассейн, йога-зал и т.п.).

Стек:

- Backend: Laravel 12 (PHP 8.2)
- Auth: JWT (`tymon/jwt-auth`)
- DB: MySQL 8.4
- Тесты: PHPUnit (Feature tests)
- Документация API: OpenAPI 3.0 + Swagger UI
- Контейнеризация: Docker + Docker Compose

---

## 3. ER-модель и сущности (чекпоинт 1)

Основные сущности:

- `users`
- `resources`
- `bookings`
- `reviews`

Связи:

- пользователь имеет много бронирований;
- ресурс имеет много бронирований;
- отзыв связан с пользователем, ресурсом и конкретным бронированием.

```mermaid
erDiagram
    USERS ||--o{ BOOKINGS : creates
    RESOURCES ||--o{ BOOKINGS : reserved_for
    USERS ||--o{ REVIEWS : writes
    RESOURCES ||--o{ REVIEWS : has
    BOOKINGS ||--o| REVIEWS : reviewed_by_booking

    USERS {
      bigint id PK
      string name
      string email UNIQUE
      string password
      enum role "admin|user"
    }

    RESOURCES {
      bigint id PK
      string name
      text description
      string type
      int capacity
      int floor
      decimal price_per_hour
      bool is_active
    }

    BOOKINGS {
      bigint id PK
      bigint user_id FK
      bigint resource_id FK
      date date
      time start_time
      time end_time
      enum status "active|cancelled"
    }

    REVIEWS {
      bigint id PK
      bigint user_id FK
      bigint booking_id FK
      bigint resource_id FK
      int rating
      text comment
    }
```

Миграции:

- `src/database/migrations/0001_01_01_000000_create_users_table.php`
- `src/database/migrations/2026_03_01_080205_add_role_to_users_table.php`
- `src/database/migrations/2026_03_01_080239_create_resources_table.php`
- `src/database/migrations/2026_03_01_080248_create_bookings_table.php`
- `src/database/migrations/2026_03_01_080300_create_reviews_table.php`

---

## 4. Роли и права

`admin`:

- CRUD ресурсов;
- просмотр всех бронирований;
- отмена любого бронирования.

`user`:

- просмотр ресурсов;
- создание/просмотр/отмена своих бронирований;
- добавление отзыва только после завершенного бронирования.

---

## 5. API-контракт (сводно по этапам 1-4)

Публичные:

- `POST /api/auth/register`
- `POST /api/auth/login`
- `GET /api/resources`
- `GET /api/resources/{id}`
- `GET /api/resources/{id}/schedule`
- `GET /api/resources/{id}/reviews`

Только с токеном:

- `POST /api/auth/logout`
- `GET /api/auth/me`
- `GET /api/bookings`
- `POST /api/bookings`
- `DELETE /api/bookings/{id}`
- `POST /api/resources/{id}/reviews`

Только `admin`:

- `POST /api/resources`
- `PUT /api/resources/{id}`
- `DELETE /api/resources/{id}`

Полная спецификация:

- `src/openapi.yaml`
- Swagger UI: `http://localhost:8081`

---

## 6. Что реализовано по чекпоинтам

### Чекпоинт 1. Проектирование и старт

Сделано:

- выбрана предметная область и стек;
- сформирована модель данных и миграции;
- определены роли и список эндпоинтов;
- подготовлена структура репозитория.

Артефакты:

- миграции: `src/database/migrations/*`
- черновые проектные документы: `projects/booking-api.md`, `projects/checkpoint-workflow.md`
- Postman-артефакт этапа: `PostmanCollections/gym-booking-сheckpoint1.json`

### Чекпоинт 2. Авторизация и CRUD ресурсов

Сделано:

- регистрация/логин на JWT;
- middleware для `admin`;
- CRUD ресурсов только для администратора;
- валидация входных данных;
- seeders с тестовыми пользователями и ресурсами.

Артефакты:

- контроллеры: `src/app/Http/Controllers/AuthController.php`, `ResourceController.php`
- middleware: `src/app/Http/Middleware/AdminMiddleware.php`
- seeders: `src/database/seeders/*`

### Чекпоинт 3. Бронирование

Сделано:

- создание бронирования;
- проверка пересечения интервалов времени;
- отмена бронирования владельцем или админом;
- список бронирований (для user — только свои, для admin — все).

Артефакты:

- `src/app/Http/Controllers/BookingController.php`
- Postman-артефакт этапа: `PostmanCollections/gym-booking-checkpoint3.json`

### Чекпоинт 4. Расписание, поиск, отзывы

Сделано:

- расписание ресурса на день/неделю;
- фильтрация, сортировка, пагинация ресурсов;
- поиск свободных ресурсов по времени;
- отзывы после завершенного бронирования;
- средний рейтинг ресурса.

Артефакты:

- `src/app/Http/Controllers/ScheduleController.php`
- `src/app/Http/Controllers/ResourceController.php`
- `src/app/Http/Controllers/ReviewController.php`
- Postman-артефакт этапа: `PostmanCollections/gym-booking-checkpoint4.json`

### Чекпоинт 5. Тесты, Swagger, Docker

Сделано:

- автотесты критичных сценариев (6 обязательных/ключевых);
- OpenAPI/Swagger документация;
- Dockerfile и docker-compose;
- запуск одной командой.

Артефакты:

- тесты: `src/tests/Feature/CriticalApiTest.php`
- OpenAPI: `src/openapi.yaml`
- Docker: `Dockerfile`, `docker-compose.yml`, `docker/app/entrypoint.sh`

---

## 7. Быстрый запуск (из коробки)

Требования:

- Docker Desktop (или Docker Engine + Compose)
- Git

Команды:

```bash
git clone <URL_РЕПОЗИТОРИЯ>
cd practice-backend-2026
docker compose up --build
```

Если используется старая команда Compose:

```bash
docker-compose up --build
```

Что происходит автоматически:

- стартует MySQL;
- стартует приложение;
- выполняется `migrate:fresh --seed`;
- стартует Swagger UI.

Проверка:

- API: `http://localhost:8000/api/resources`
- Swagger: `http://localhost:8081`

---

## 8. Тестовые пользователи

После сидирования:

- `admin@gym.com` / `password` (роль `admin`)
- `user@gym.com` / `password` (роль `user`)

---

## 9. Ключевые сценарии проверки (для защиты)

### Сценарий A. Авторизация

1. `POST /api/auth/login` c `admin@gym.com / password`
2. Получить JWT
3. `GET /api/auth/me` с токеном

Ожидаемо:

- без токена защищенные маршруты дают `401`;
- с токеном — `200`.

### Сценарий B. CRUD ресурса и роли

1. `POST /api/resources` без токена
2. `POST /api/resources` токеном обычного пользователя
3. `POST /api/resources` токеном админа

Ожидаемо:

- без токена: `401`;
- user: `403`;
- admin: `201`.

### Сценарий C. Бронирование и конфликт времени

1. Создать бронирование на свободный интервал
2. Повторить бронирование с пересечением по времени

Ожидаемо:

- первое: `201`;
- второе: `422` + `conflict` в ответе.

### Сценарий D. Расписание и поиск

1. `GET /api/resources/{id}/schedule?date=YYYY-MM-DD&period=day`
2. `GET /api/resources?date=...&start_time=...&end_time=...`

Ожидаемо:

- корректный список занятых слотов в расписании;
- поиск возвращает только свободные ресурсы.

### Сценарий E. Отзывы и рейтинг

1. Оставить отзыв только по завершенному бронированию
2. Проверить `GET /api/resources/{id}/reviews`

Ожидаемо:

- до завершения брони отзыв запрещен (`422`);
- после завершения создается (`201`);
- в ответе есть средний рейтинг.

---

## 10. Автотесты

Запуск в контейнере:

```bash
docker compose exec app php artisan test
```

Критичные кейсы из ТЗ покрыты:

- доступ с/без токена;
- создание бронирования;
- пересечение по времени;
- доступ по ролям (`user`/`admin`).

---

## 11. Swagger / OpenAPI

- Спецификация: `src/openapi.yaml`
- UI: `http://localhost:8081`

Документация включает:

- назначение эндпоинтов;
- входные/выходные данные;
- ошибки и условия их возникновения (`401/403/404/422`).

---

## 12. Полезные команды

Остановить:

```bash
docker compose down
```

Остановить и удалить том БД:

```bash
docker compose down -v
```

Логи:

```bash
docker compose logs --tail=200 app db swagger
```

---
