FROM php:8.4.6-cli-alpine3.21

WORKDIR /var/www/html

RUN apk update && apk upgrade --no-cache

COPY . /var/www/html

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "/var/www/html"]