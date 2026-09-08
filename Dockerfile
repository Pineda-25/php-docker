FROM php:8.2-apache

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Copiar el código fuente al contenedor
COPY src/ /var/www/html/

# Asegurar permisos correctos para que Apache (www-data) pueda escribir la base de datos SQL
RUN mkdir -p /var/www/html/data && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 777 /var/www/html/data

# Exponer el puerto 80 del contenedor
EXPOSE 80

# Iniciar Apache en primer plano
CMD ["apache2-foreground"]
