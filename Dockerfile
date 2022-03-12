FROM dwchiang/nginx-php-fpm:7.4.25-fpm-alpine3.14-nginx-1.21.1

RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer

COPY public /usr/share/nginx/html
COPY . /var/www/html

RUN composer install --no-dev --working-dir=/var/www/html
