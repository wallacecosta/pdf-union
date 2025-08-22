FROM php:8.1-apache

# Instala dependências do sistema e extensões PHP necessárias (gd, zip, mbstring)
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev zip unzip libpng-dev libjpeg-dev libfreetype6-dev libonig-dev pkg-config default-libmysqlclient-dev curl git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd zip pdo pdo_mysql mbstring \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Instala o Composer (usa a imagem oficial como source)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copia o código da aplicação
COPY . /var/www/html

# Instala dependências PHP via Composer (se houver composer.json)
RUN composer install --no-dev --optimize-autoloader || true

# Cria diretórios utilizados pela aplicação e ajusta permissões
RUN mkdir -p /var/www/html/arquivos /var/www/html/resultado \
    && chown -R www-data:www-data /var/www/html/arquivos /var/www/html/resultado /var/www/html/vendor || true \
    && chmod -R 0775 /var/www/html/arquivos /var/www/html/resultado

EXPOSE 80

CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]
