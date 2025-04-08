# Quick Checker API (backend)

Backend for [Quick Checker App](https://github.com/amiano4/quick-checker-app.git) using Laravel 12 powered by Laravel Sanctum and OpenAI

## Prerequisites

Make sure you have the following technologies:

-   PHP 8.2+
-   MySQL
-   Composer
-   An [OpenAI Account](https://platform.openai.com/) (must have enough credits)

## Obtain an API Key from OpenAI Developer Platform

Consult to the developer for more info and guidance

## Project Setup

```bash
# clone the repository
git clone https://github.com/amiano4/quick-checker-api.git

# open the project folder
cd quick-checker-api
```

### Install packages

```bash
composer install
```

### Set environment variables

```bash
# create .env file from template
copy .env.example .env

# generate app id
php artisan key:generate

# update to your local configurations
# ...
```

### Important: OpenAI API Setup

The following variables must be set for OpenAI authentication

```bash
OPENAI_API_KEY=
OPENAI_ORGANIZATION=
```

### Run migrations

```bash
php artisan migrate
```

### Serve the backend API

```bash
php artisan serve
```
