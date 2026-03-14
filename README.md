# Gym Booking API checkpoint 1-5

Booking API для бронирования ресурсов (залы, площадки) с JWT авторизацией, ролями, проверкой пересечения времени, расписанием и отзывами.

---

## 1. Цель проекта

Сделать API системы бронирования, где:

- есть роли `admin` и `user`;
- пользователь может бронировать ресурс на дату и время;
- система не допускает пересечения интервалов бронирования;
- есть отмена бронирований с ролевыми ограничениями;
- есть расписание, поиск свободных ресурсов, отзывы и рейтинг;
- проект запускается из коробки через Docker.

---

## 2. Предметная область и стек

Предметная область: бронирование спортивных ресурсов.

Стек:

- Backend: Laravel 12 (PHP 8.2)
- Auth: JWT (`tymon/jwt-auth`)
- DB: MySQL 8.4
- Tests: PHPUnit
- API docs: OpenAPI 3.0 + Swagger UI
- Infra: Docker + Docker Compose

---

## 3. ER модель и данные (чекпоинт 1)

### 3.1 Основные сущности

- `users`
- `resources`
- `bookings`
- `reviews`

### 3.2 Связи

- `users (1) -> (M) bookings`
- `resources (1) -> (M) bookings`
- `users (1) -> (M) reviews`
- `resources (1) -> (M) reviews`
- `bookings (1) -> (0..1) reviews`

### 3.3 Ключевые поля

| Таблица     | Поля                                                                                                |
| ----------- | --------------------------------------------------------------------------------------------------- |
| `users`     | `id`, `name`, `email`, `password`, `role`, timestamps                                               |
| `resources` | `id`, `name`, `description`, `type`, `capacity`, `floor`, `price_per_hour`, `is_active`, timestamps |
| `bookings`  | `id`, `user_id`, `resource_id`, `date`, `start_time`, `end_time`, `status`, timestamps              |
| `reviews`   | `id`, `user_id`, `booking_id`, `resource_id`, `rating`, `comment`, timestamps                       |

Ограничения по БД:

- `users.role`: `admin | user`
- `bookings.status`: `active | cancelled`

### 3.4 Миграции

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
- создание и просмотр своих бронирований;
- отмена своих бронирований;
- создание отзыва только после завершенного бронирования.

---

## 5. API контракт (чекпоинты 1-4)

Публичные:

- `POST /api/auth/register`
- `POST /api/auth/login`
- `GET /api/resources`
- `GET /api/resources/{id}`
- `GET /api/resources/{id}/schedule`
- `GET /api/resources/{id}/reviews`

Требуют токен:

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

Файл роутов:

- `src/routes/api.php`

---

## 6. Что сделано по чекпоинтам

### Чекпоинт 1. Проектирование и старт

Сделано:

- определены сущности, роли, ограничения;
- созданы миграции;
- сформирован список эндпоинтов;
- подготовлена структура репозитория.

Артефакты:

- `projects/booking-api.md`
- `projects/checkpoint-workflow.md`
- `PostmanCollections/gym-booking-сheckpoint1.json`

### Чекпоинт 2. Авторизация и CRUD ресурсов

Сделано:

- регистрация и логин через JWT;
- middleware для админ-доступа;
- CRUD ресурсов только для `admin`;
- валидация входных данных;
- seeders для демо.

Артефакты:

- `src/app/Http/Controllers/AuthController.php`
- `src/app/Http/Controllers/ResourceController.php`
- `src/app/Http/Middleware/AdminMiddleware.php`
- `src/database/seeders/*`

### Чекпоинт 3. Бронирование

Сделано:

- создание бронирования;
- проверка конфликтов по времени;
- отмена бронирования пользователем и админом;
- список бронирований текущего пользователя.

Артефакты:

- `src/app/Http/Controllers/BookingController.php`
- `PostmanCollections/gym-booking-checkpoint3.json`

### Чекпоинт 4. Расписание, поиск, отзывы

Сделано:

- расписание ресурса на день и неделю;
- поиск свободных ресурсов по дате и времени;
- фильтрация, сортировка, пагинация ресурсов;
- отзывы после завершенного бронирования;
- средний рейтинг ресурса.

Артефакты:

- `src/app/Http/Controllers/ScheduleController.php`
- `src/app/Http/Controllers/ReviewController.php`
- `src/app/Http/Controllers/ResourceController.php`
- `PostmanCollections/gym-booking-checkpoint4.json`

### Чекпоинт 5. Тесты, Swagger, Docker

Сделано:

- автотесты критичных сценариев, включая schedule/review flow;
- OpenAPI спецификация;
- Dockerfile + docker-compose (app + db + swagger);
- запуск проекта одной командой.

Артефакты:

- `src/tests/Feature/CriticalApiTest.php`
- `src/tests/Feature/ScheduleReviewFlowTest.php`
- `src/openapi.yaml`
- `Dockerfile`
- `docker-compose.yml`
- `docker/app/entrypoint.sh`

#### Отдельно по замечанию: тесты schedule/review flow

Добавлено в автотесты (`src/tests/Feature/ScheduleReviewFlowTest.php`):

- расписание ресурса на день (`GET /api/resources/{id}/schedule?date=...&period=day`);
- успешный отзыв после завершённого бронирования;
- запрет отзыва до завершения бронирования;
- запрет дублирующего отзыва на одно бронирование.

#### Отдельно по замечанию: вынос секретов из docker-compose.yml

Сделано:

- секреты удалены из `docker-compose.yml`, используются переменные окружения (`.env`/runtime env);
- добавлен корневой шаблон `.env.example` для docker запуска;
- добавлен корневой `.gitignore` с исключением `.env`;
- `APP_KEY` и `JWT_SECRET` могут не храниться в репозитории и генерируются в runtime (в `entrypoint`) при пустых значениях;
- `DB_PASSWORD` и `MYSQL_ROOT_PASSWORD` задаются в `.env` перед запуском.

---

## 7. Быстрый запуск (из коробки)

Требуется:

- Docker Desktop (или Docker Engine + Compose)
- Git

Команды:

```bash
git clone <URL_РЕПОЗИТОРИЯ>
cd practice-backend-2026
cp .env.example .env
docker compose up --build
```

PowerShell вариант:

```powershell
Copy-Item .env.example .env
```

После копирования открой `.env` и задай минимум:

- `DB_PASSWORD`
- `MYSQL_ROOT_PASSWORD`

`APP_KEY` и `JWT_SECRET` можно оставить пустыми: они сгенерируются автоматически при старте контейнера `app`.

Если старая версия compose:

```bash
docker-compose up --build
```

Что произойдет автоматически:

- поднимется MySQL;
- поднимется приложение;
- выполнятся миграции и сиды;
- поднимется Swagger UI.
- docker секреты и пароли берутся из корневого `.env`, а не из `docker-compose.yml`.

Проверка:

- API: `http://localhost:8000/api/resources`
- Swagger UI: `http://localhost:8081`

---

## 8. Тестовые пользователи

После сидирования:

- `admin@gym.com` / `password`
- `user@gym.com` / `password`

---

## 9. Как проверить ключевые сценарии

### 9.1 Авторизация

1. `POST /api/auth/login`
2. Получить JWT
3. `GET /api/auth/me` с `Bearer token`

Ожидаемо:

- без токена на защищенных маршрутах: `401`
- с токеном: `200`

### 9.2 Роли и CRUD ресурсов

1. `POST /api/resources` без токена
2. `POST /api/resources` от `user`
3. `POST /api/resources` от `admin`

Ожидаемо:

- `401`, `403`, `201` соответственно.

### 9.3 Бронирование и пересечение времени

1. Создать бронирование на свободный слот
2. Создать второе с пересечением по времени

Ожидаемо:

- первое: `201`
- второе: `422` и поле `conflict` в ответе

### 9.4 Расписание и поиск

1. `GET /api/resources/{id}/schedule?date=YYYY-MM-DD&period=day`
2. `GET /api/resources?date=...&start_time=...&end_time=...`

### 9.5 Отзывы и рейтинг

1. Попробовать оставить отзыв до завершения бронирования
2. Оставить отзыв после завершения
3. Проверить `GET /api/resources/{id}/reviews`

---

## 10. Автотесты

Запуск в контейнере:

```bash
docker compose exec app php artisan test
```

Покрыто:

- авторизация с/без токена;
- создание бронирования;
- пересечение интервалов;
- доступ по ролям.
- flow расписания ресурса (schedule day);
- flow отзывов: успешный отзыв, запрет отзыва до завершения, запрет дубликата.

---

## 11. Swagger / OpenAPI

- Спецификация: `src/openapi.yaml`
- Swagger UI: `http://localhost:8081`

Документация содержит:

- описание эндпоинтов;
- входные и выходные данные;
- ошибки и условия их возникновения (`401/403/404/422`).

---

## 12. Демо

1. Клонирование репозитория
2. `docker compose up --build`
3. Проверка `http://localhost:8000/api/resources`
4. Проверка `http://localhost:8081`
5. Прогон `docker compose exec app php artisan test`

---

## 13. Полезные команды

Остановить:

```bash
docker compose down
```

Остановить и удалить тома:

```bash
docker compose down -v
```

Посмотреть логи:

```bash
docker compose logs --tail=200 app db swagger
```

---
