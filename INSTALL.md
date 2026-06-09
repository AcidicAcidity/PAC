# Инструкция по установке окружения для разработки (Arch Linux)

Полное руководство по настройке всего необходимого для разработки и запуска TaskFlow на Arch Linux.

---

## 1. Обновление системы

```bash
sudo pacman -Syu
```

## 2. Установка Docker и Docker Compose

```
# Установить Docker
sudo pacman -S docker

# Установить Docker Compose (плагин)
sudo pacman -S docker-compose

# Запустить и включить демон Docker
sudo systemctl start docker
sudo systemctl enable docker

# Добавить текущего пользователя в группу docker (чтобы не писать sudo)
sudo usermod -aG docker $USER

# Выйти и зайти заново (или перезагрузить сессию)
newgrp docker

# Проверить установку
docker --version
# Должно быть: Docker version 24.x.x или выше

docker compose version
# Должно быть: Docker Compose version v2.23.x или выше
```

## 3. Установка Git

```
sudo pacman -S git

# Настройка (если ещё не настроен)
git config --global user.name "Ваше Имя"
git config --global user.email "your@email.com"
```

## 4. Установка Node.js и npm (для фронтенда)

```
# Установить Node.js LTS через nvm (рекомендуется) или напрямую

# Вариант A: Через nvm (рекомендуется — гибкое управление версиями)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash
# Перезапустить терминал или выполнить:
source ~/.bashrc

nvm install --lts
nvm use --lts
nvm alias default 'lts/*'

# Вариант B: Напрямую из репозитория
sudo pacman -S nodejs npm

# Проверить
node --version   # Должно быть v20.x или выше
npm --version    # Должно быть 10.x или выше
```

## 5. Установка PHP и Composer (для локальной разработки бэкенда)

Хотя бэкенд работает в Docker, для локальной разработки и отладки удобно иметь PHP локально.

```
# Установить PHP 8.1+ и необходимые расширения
sudo pacman -S php php-pgsql php-redis composer

# Проверить
php --version     # Должно быть PHP 8.1.x или выше
composer --version

# Включить расширения в php.ini (если не включены)
sudo nano /etc/php/php.ini
# Раскомментировать или добавить:
# extension=pdo_pgsql
# extension=redis
# extension=opcache
```

**Важно:** Локальный PHP нужен только для запуска тестов или линтинга. Сам проект работает внутри Docker-контейнера `php:8.1-apache`, где все расширения уже установлены.

## 6. Установка вспомогательных инструментов (опционально)

```
# HTTP-клиент для тестирования API
sudo pacman -S curl

# jq — для форматирования JSON в терминале
sudo pacman -S jq

# PostgreSQL клиент (для прямого подключения к БД)
sudo pacman -S postgresql-libs

# Редактор кода (выберите свой)
sudo pacman -S code          # VS Code
# или
sudo pacman -S neovim        # Neovim
```

## 7. Клонирование и настройка проекта

```
# Клонировать репозиторий
git clone <repo-url> taskflow
cd taskflow

# Скопировать конфигурацию окружения
cp .env.example .env

# Отредактировать .env (если нужно изменить пароли или ключи)
nano .env
# Минимально необходимые переменные уже заполнены значениями по умолчанию
```

## 8. Установка фронтенд-зависимостей

```
cd frontend

# Установить зависимости
npm install

# Проверить, что Vite работает
npx vite --version
```

## 9. Запуск проекта

### 9.1. Production-режим (через Docker — рекомендуется)

```
# Из корня проекта
docker compose up -d

# Проверить статус контейнеров
docker compose ps

# Посмотреть логи
docker compose logs -f

# Остановить
docker compose down
```

### 9.2. Режим разработки (фронтенд локально, бэкенд в Docker)

```
# Терминал 1: Запустить только бэкенд-сервисы
docker compose up -d db redis backend websocket

# Терминал 2: Запустить Vite dev-сервер для фронтенда
cd frontend
npm run dev

# Открыть http://localhost:5173 (Vite с HMR)
# API-запросы проксируются на http://localhost:8080
```

## 10. Проверка работоспособности

### Проверить, что backend отвечает

```
curl -X POST http://localhost:8080/api.php \
  -H "Content-Type: application/json" \
  -d '{"method": "tasks.list", "params": {"is_public": true}}' | jq
```

### Проверить WebSocket

```
# Установить websocat (опционально)
sudo pacman -S websocat

# Подключиться к WebSocket-серверу
# (замените JWT_TOKEN на реальный токен после регистрации)
websocat "ws://localhost:8081?token=JWT_TOKEN"
```

### Проверить PostgreSQL напрямую

```
# Подключиться к БД внутри контейнера
docker compose exec db psql -U taskflow -d taskflow

# Вывести список таблиц
\dt

# Выйти
\q
```

### Проверить Redis

```
# Подключиться к Redis внутри контейнера
docker compose exec redis redis-cli

# Проверить соединение
PING
# Должен ответить: PONG

# Выйти
exit
```

## 11. Полезные команды Docker

```
# Пересобрать контейнеры после изменения Dockerfile
docker compose up -d --build

# Посмотреть логи конкретного сервиса
docker compose logs backend
docker compose logs websocket

# Зайти внутрь контейнера
docker compose exec backend bash
docker compose exec db bash

# Полностью удалить всё (включая тома с данными)
docker compose down -v

# Перезапустить конкретный сервис
docker compose restart websocket
```

## 12. Устранение неполадок

### Ошибка «address already in use» (порты заняты)

```
# Проверить, что занимает порт
sudo lsof -i :8080
sudo lsof -i :5432
sudo lsof -i :6379

# Остановить процесс или изменить порты в .env
```

### Docker не запускается

```
# Проверить статус демона
sudo systemctl status docker

# Перезапустить
sudo systemctl restart docker
```

### Ошибка прав доступа к Docker

```
# Убедиться, что пользователь в группе docker
groups $USER | grep docker

# Если нет — добавить и перезайти
sudo usermod -aG docker $USER
newgrp docker
```

### PostgreSQL не применяет схему

```
# Удалить том и пересоздать
docker compose down -v
docker compose up -d
```

## 13. Разработка: типичный рабочий процесс

```
# 1. Запустить окружение
docker compose up -d db redis

# 2. Запустить фронтенд в dev-режиме (HMR)
cd frontend && npm run dev

# 3. Бэкенд — либо через Docker, либо встроенный сервер PHP:
php -S localhost:8080 -t backend/

# 4. Вносить изменения:
#    - PHP: править файлы в backend/, обновлять страницу
#    - Vue: править в frontend/src/, HMR применяет мгновенно

# 5. Перед коммитом проверить production-сборку:
cd frontend && npm run build
docker compose up -d --build
```

## Требования к ПО (сводка)

| Инструмент | Минимальная версия | Команда установки (Arch) |
|---|---|---|
| Docker | 24.x | `sudo pacman -S docker` |
| Docker Compose | 2.23.x | `sudo pacman -S docker-compose` |
| Git | 2.x | `sudo pacman -S git` |
| Node.js | 20.x | `nvm install --lts` |
| npm | 10.x | (идёт с Node.js) |
| PHP (опционально) | 8.1+ | `sudo pacman -S php` |
| Composer (опционально) | 2.x | `sudo pacman -S composer` |

Готово! После выполнения всех шагов проект должен запускаться одной командой:

```
docker compose up -d
```

И быть доступным по адресу: **[http://localhost:8080](http://localhost:8080)**

