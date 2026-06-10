# TaskFlow — Корпоративный портал

Упрощённый аналог Битрикс24 для управления задачами и коммуникации внутри компании.

## Возможности

- **Канбан-доска** с drag-and-drop для управления задачами
- **Иерархическая система ролей** (Менеджер → Супервайзер → Техспециалист)
- **Коллабы (группы)** с внутренним чатом и обходом иерархии
- **Чат в реальном времени** (WebSocket) — личные и групповые сообщения
- **Система отзывов** с модерацией
- **Админ-панель** для полного управления порталом
- **5 цветовых тем** (светлая, тёмная, стекло iOS, киберпанк, природа)
- **Мультиязычность** (русский / английский)
- **Полная контейнеризация** через Docker

## Стек технологий

| Уровень     | Технология                                      |
| ----------- | ----------------------------------------------- |
| Бэкенд      | PHP 8.1 (без фреймворка, API в стиле Битрикс24) |
| База данных | PostgreSQL 15                                   |
| Кеширование | Redis 7                                         |
| WebSocket   | Ratchet (PHP)                                   |
| Фронтенд    | Vue 3 (Composition API, Pinia, Vue Router)      |
| Контейнеры  | Docker + docker-compose                         |

## Требования

- Docker 24+
- docker-compose 2.23+
- 2 ГБ свободной RAM
- 5 ГБ свободного места на диске

## Быстрый старт

```bash
# 1. Клонировать репозиторий
git clone <repo-url> taskflow
cd taskflow

# 2. Скопировать и настроить переменные окружения
cp .env.example .env
# При необходимости отредактируйте .env (пароли, ключи)

# 3. Запустить все сервисы
docker-compose up -d

# 4. Проверить, что всё работает
docker-compose ps
# Должны быть запущены: backend, websocket, db, redis

# 5. Открыть в браузере
# http://localhost:8080
```

## Первый запуск

1. Откройте [http://localhost:8080](http://localhost:8080)
1. Нажмите «Начать»
1. Выберите «Я администратор»
1. Укажите название компании, email и пароль
1. Введите код подтверждения из письма (локально — в [Mailpit](http://localhost:8025); запасной вариант — `docker compose logs backend`)
1. Готово! Вы в панели управления порталом

## Структура проекта

```
TaskFlow/
├── backend/                # PHP-бэкенд
│   ├── api.php            # Единая точка входа API
│   ├── config.php         # Конфигурация (БД, Redis, JWT)
│   ├── auth.php           # Регистрация, вход, JWT
│   ├── middleware.php     # Проверка JWT, rate limiting
│   ├── handlers/          # Обработчики методов API
│   │   ├── tasks.php
│   │   ├── collabs.php
│   │   ├── messages.php
│   │   ├── reviews.php
│   │   └── admin.php
│   ├── websocket/         # WebSocket-сервер
│   │   └── server.php
│   └── composer.json
├── frontend/              # Vue 3 SPA
│   ├── src/
│   │   ├── api/          # Обёртка над API
│   │   ├── components/   # Переиспользуемые компоненты
│   │   ├── views/        # Страницы
│   │   ├── stores/       # Pinia-сторы
│   │   ├── router/       # Vue Router
│   │   ├── i18n/         # Переводы (ru.json, en.json)
│   │   ├── App.vue
│   │   └── main.js
│   ├── public/themes/    # CSS-файлы тем
│   ├── index.html
│   ├── vite.config.js
│   └── package.json
├── database/              # SQL-схема
│   └── init.sql
├── docker-compose.yml
├── Dockerfile.backend
├── Dockerfile.websocket
├── .env.example
├── README.md              # Этот файл
└── INSTALL.md             # Инструкция по установке окружения
```

## Сервисы Docker

| Сервис    | Порт (внутри) | Порт (снаружи) | Назначение                 |
| --------- | ------------- | -------------- | -------------------------- |
| backend   | 80            | 8080           | Apache + PHP API + статика |
| websocket | 8080          | 8081           | Ratchet WebSocket-сервер   |
| db        | 5432          | 5432           | PostgreSQL 15              |
| redis     | 6379          | 6379           | Redis 7                    |
| mailpit   | 8025 / 1025   | 8025 / 1025    | Перехват email (dev)       |

## Лицензия

MIT
