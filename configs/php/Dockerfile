FROM php:8.2-fpm-bookworm

ARG UID
ARG GID
ARG USER=www

ENV USER=${USER}
ENV UID=${UID}
ENV GID=${GID}

# Установка системных зависимостей
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libmemcached-dev \
    libz-dev \
    libpq-dev \
    libssl-dev \
    libzip-dev \
 && rm -rf /var/lib/apt/lists/*


# Установка PHP расширений
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    zip

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Создание пользователя
RUN groupadd --gid ${GID} ${USER} \
 && useradd --uid ${UID} --gid ${GID} --shell /bin/bash --create-home ${USER}

# Копирование конфигурации PHP
COPY /configs/php/php.ini /usr/local/etc/php/conf.d/40-custom.ini

# Установка рабочей директории
WORKDIR /var/www

# Смена владельца директории
RUN chown -R ${UID}:${GID} /var/www

# Переключение на пользователя
USER ${UID}

EXPOSE 9000

CMD ["php-fpm"]
