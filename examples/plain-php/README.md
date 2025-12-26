# Plain PHP Example

Пример использования TBank ID с обычным PHP (без Laravel).

## Установка

1. Установите пакет через Composer:

```bash
composer require tigusigalpa/tbank-id-php
```

2. Настройте учетные данные в файлах:
   - `index.php`
   - `callback.php`
   - `logout.php`
   - `refresh.php`

Замените:
- `your_client_id` на ваш Client ID
- `your_client_secret` на ваш Client Secret
- `https://your-domain.com/callback.php` на URL вашего callback

## Файлы

- **index.php** - Главная страница с кнопкой авторизации и информацией о пользователе
- **callback.php** - Обработка callback после авторизации
- **logout.php** - Выход из системы
- **refresh.php** - Обновление токена

## Использование

1. Откройте `index.php` в браузере
2. Нажмите "Войти через TBank ID"
3. Авторизуйтесь в TBank
4. Вы будете перенаправлены обратно с информацией о пользователе

## Требования

- PHP 7.4 или выше
- Composer
- Включенные сессии PHP
- HTTPS (рекомендуется для production)

## Безопасность

- Всегда используйте HTTPS в production
- Храните `client_secret` в безопасности
- Проверяйте state параметр (реализовано в примере)
- Используйте безопасные сессии PHP
