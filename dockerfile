FROM php:8.0-fpm
COPY . /usr/src/script
WORKDIR /usr/src/script
CMD [ "php", "./index.php" ]