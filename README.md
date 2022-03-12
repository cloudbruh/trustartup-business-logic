<h1 align="center"> Trustartup Business Logic </h1>

<h3 align="center">
  Микросервис для проекта Trustartup.
</h3>

## Содержание

-   [Описание](#описание)
-   [Технологии](#технологии)
-   [Использование](#использование)
-   [API](#api)

## Описание

Отвечает на все запросы, которые происходят редко, такие как формы, запросы на модерацию, создание стартапа.

## Технологии

-   Lumen (PHP framework)
-   Docker

## Использование

Микросервис может быть запущен локально или в докер контейнере.

### Локально

-   [PHP 7.4+](https://www.php.net/downloads.php)
-   [Composer](https://getcomposer.org/download/)

Сначала установите зависимости:

```bash
composer install
```

Затем скопируйте `.env.example` в `.env` и измените переменные среды в зависимости от вашего окружения

Запустите микросервис:

```bash
php -S localhost:8000 -t public
```

### Docker

-   [Docker](https://www.docker.com/get-docker)

Сначала постройте образ:

```bash
docker-compose build
```

Запустите микросервис:

```bash
docker-compose up -d
```

По умолчанию сервис запустится на `8090` порте

## API

[Полная api-документация в формате OpenAPI3.0](https://www.getpostman.com/collections/fbb18529532803b00ac2)
