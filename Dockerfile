FROM php:8.2-cli

# Install the MySQL drivers the app needs
RUN docker-php-ext-install pdo pdo_mysql mysqli

# App code
WORKDIR /app
COPY . /app/

# Railway provides $PORT; default to 8080 locally
EXPOSE 8080
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t /app"]
