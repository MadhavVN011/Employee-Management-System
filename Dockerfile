FROM php:8.2-apache

RUN docker-php-ext-install mysqli

COPY docker/php.ini /usr/local/etc/php/conf.d/uploads.ini
COPY docker/apache.conf /etc/apache2/conf-available/ems.conf
RUN a2enconf ems

WORKDIR /var/www/html
COPY . .
RUN chown -R www-data:www-data images
