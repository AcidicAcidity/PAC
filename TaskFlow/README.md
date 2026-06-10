# TaskFlow — Корпоративный портал

Упрощённый аналог Битрикс24 для управления задачами и коммуникации внутри компании.

Полная документация: см. [README.md](../README.md) в корне репозитория и [INSTALL.md](../INSTALL.md).

## Быстрый старт

```bash
cp .env.example .env
docker compose up -d --build
```

Откройте [http://localhost:8080](http://localhost:8080)

## Разработка фронтенда

```bash
cd frontend
npm install
npm run dev
```

Vite dev-сервер: [http://localhost:5173](http://localhost:5173) (API проксируется на :8080)
