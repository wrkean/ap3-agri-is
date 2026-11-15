FROM php:8.2-apache

# curl and mbstring are already included in the base image
# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy the app
COPY . /var/www/html/
WORKDIR /var/www/html

EXPOSE 10000

# Start script that binds Apache to Render's $PORT
COPY start.sh /start.sh
RUN chmod +x /start.sh
CMD ["/start.sh"]
