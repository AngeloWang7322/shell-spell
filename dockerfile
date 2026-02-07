FROM php:8.5.2-cli

WORKDIR /shell-spell

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/* \
    && php composer.phar install --no-dev  --optimize-autoloader

COPY composer.json composer.lock ./

CMD ["php", "shell-spell.php"]
