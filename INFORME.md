# INFORME TÉCNICO: DESARROLLO Y CONTENEDORIZACIÓN DE APLICACIÓN WEB EN PHP CON PERSISTENCIA SQL Y DOCKER

**Proyecto:** FinanSmart - Conversor de Divisas y Calculadora de Presupuesto  
**Tecnologías:** PHP 8.2, Apache 2.4, SQL (SQLite vía PDO), Docker, Docker Compose, Bootstrap 5  
**Autor:** Pineda-25  
**Fecha:** Septiembre de 2026  

---

## 1. INTRODUCCIÓN Y JUSTIFICACIÓN

En el desarrollo de software moderno, uno de los desafíos más habituales es el problema clásico: *"en mi máquina sí funciona, pero en la tuya no"*. Esto ocurre comúnmente debido a discrepancias en las versiones instaladas de PHP, extensiones ausentes, configuración del servidor web (Apache/Nginx) o falta de configuración en los motores de bases de datos.

Para resolver esta problemática de raíz, este proyecto implementa una solución basada en **contenedores Docker**. Mediante la creación de una imagen personalizada y una definición orquestada con **Docker Compose**, se empaqueta la aplicación completa: el runtime de PHP 8.2, el servidor web Apache y el motor de base de datos SQL. 

Como resultado, cualquier usuario o evaluador puede descargar el proyecto y ponerlo en funcionamiento de forma idéntica en cualquier sistema operativo (Linux, Windows, macOS) ejecutando un único comando, sin requerir instalaciones previas en su equipo.

---

## 2. OBJETIVOS

### 2.1 Objetivo General
Diseñar, programar y desplegar una aplicación web funcional en PHP con persistencia de datos relacionales SQL, empaquetándola en un contenedor Docker portable y autónomo para su distribución y ejecución universal.

### 2.2 Objetivos Específicos
1. Desarrollar un sistema con dos módulos funcionales: **Conversor de Divisas con cálculo de comisiones** y **Calculadora de Presupuesto Financiero con Balance**.
2. Diseñar un esquema de base de datos SQL con tablas relacionales (`conversiones` y `movimientos_presupuesto`) gestionadas mediante la extensión `PDO` de PHP.
3. Construir un `Dockerfile` optimizado a partir de la imagen oficial `php:8.2-apache`, habilitando permisos de lectura/escritura y módulos requeridos.
4. Configurar un archivo `docker-compose.yml` para gestionar el mapeo de puertos de red y la persistencia de datos mediante volúmenes.
5. Gestionar el control de versiones con Git y publicar el código en un repositorio remoto de GitHub.

---

## 3. ARQUITECTURA Y ESTRUCTURA DEL PROYECTO

El proyecto se organiza bajo una estructura modular y limpia:

```text
conversor-presupuesto-php/
├── Dockerfile                  # Receta de construcción de la imagen Docker
├── docker-compose.yml          # Definición y orquestación del servicio contenedor
├── .dockerignore               # Archivos excluidos del contexto de compilación
├── .gitignore                  # Archivos excluidos del control de versiones Git
├── README.md                   # Manual de usuario rápido
├── INFORME.md                  # Informe técnico detallado (este documento)
└── src/                        # Código fuente montado en el contenedor
    ├── db.php                  # Conexión a la base de datos SQL e inicialización DDL
    ├── index.php               # Vistas (HTML5/CSS3) y lógica del servidor PHP
    └── data/                   # Directorio de persistencia de la base de datos
        └── database.sqlite     # Archivo físico de la base de datos SQL
```

---

## 4. DESARROLLO DE LA APLICACIÓN PHP Y BASE DE DATOS SQL

### 4.1 Elección del Motor SQL (SQLite vía PDO)
Para garantizar la portabilidad absoluta sin dependencias frágiles de red, se utilizó **SQLite** mediante la interfaz **PHP Data Objects (PDO)**. SQLite es un motor SQL relacional estándar y completo que almacena toda la base de datos en un archivo binario dentro del contenedor. Esto aporta dos ventajas decisivas:
1. **Cero conflictos de puertos:** No compite por puertos como el 3306 de MySQL.
2. **Consultas SQL Estándar:** Emplea DDL (`CREATE TABLE`), DML (`INSERT`, `SELECT`, `DELETE`) y sentencias preparadas contra inyecciones SQL.

### 4.2 Script de Base de Datos (`src/db.php`)
El archivo `db.php` se encarga de abrir la conexión y asegurar que las tablas requeridas existan:

```php
$pdo = new PDO("sqlite:" . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Tabla 1: Historial de Conversiones
$pdo->exec("
    CREATE TABLE IF NOT EXISTS conversiones (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        monto_origen REAL NOT NULL,
        moneda_origen TEXT NOT NULL,
        monto_destino REAL NOT NULL,
        moneda_destino TEXT NOT NULL,
        tasa REAL NOT NULL,
        comision_porcentaje REAL DEFAULT 0,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

// Tabla 2: Movimientos del Presupuesto
$pdo->exec("
    CREATE TABLE IF NOT EXISTS movimientos_presupuesto (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        descripcion TEXT NOT NULL,
        tipo TEXT NOT NULL CHECK(tipo IN ('ingreso', 'gasto')),
        categoria TEXT NOT NULL,
        monto REAL NOT NULL,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");
```

### 4.3 Módulos de la Aplicación (`src/index.php`)

#### Módulo A: Conversor de Divisas
- Soporta conversión entre 9 divisas (USD, EUR, COP, MXN, PEN, ARS, GBP, BRL, CLP) utilizando el Dólar como divisa pivote.
- Permite calcular comisiones bancarias o de casas de cambio (0%, 1%, 2.5%, 4%).
- Ejecuta una sentencia preparada SQL para auditar cada transacción:
  ```php
  $stmt = $pdo->prepare("INSERT INTO conversiones (...) VALUES (...)");
  ```

#### Módulo B: Control de Presupuesto
- Permite registrar ingresos y gastos con descripción, categoría y monto.
- Calcula dinámicamente mediante agregaciones SQL los totales:
  ```php
  SELECT 
      SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) AS total_ingresos,
      SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END) AS total_gastos
  FROM movimientos_presupuesto;
  ```
- Permite eliminar registros individualmente mediante `DELETE FROM movimientos_presupuesto WHERE id = :id`.

---

## 5. CONTENEDORIZACIÓN CON DOCKER (PASO A PASO)

### 5.1 Explicación del `Dockerfile`

El `Dockerfile` contiene las instrucciones exactas para crear la imagen del contenedor:

```dockerfile
# 1. Imagen base oficial con PHP 8.2 y servidor Apache preconfigurado
FROM php:8.2-apache

# 2. Habilita el módulo mod_rewrite de Apache para URLs limpias
RUN a2enmod rewrite

# 3. Establece la ruta de trabajo dentro del contenedor
WORKDIR /var/www/html

# 4. Copia el código fuente desde el host local hacia el contenedor
COPY src/ /var/www/html/

# 5. Crea el directorio de datos y asigna permisos de lectura/escritura para www-data (Apache)
RUN mkdir -p /var/www/html/data && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 777 /var/www/html/data

# 6. Informa que el contenedor escucha tráfico en el puerto web 80
EXPOSE 80

# 7. Comando de arranque que mantiene el servidor Apache activo en primer plano
CMD ["apache2-foreground"]
```

### 5.2 Explicación de `docker-compose.yml`

Para simplificar la ejecución sin tener que memorizar comandos largos de `docker run`, se utiliza Docker Compose:

```yaml
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: app_conversor_presupuesto
    ports:
      - "8080:80"        # Mapeo: Puerto 8080 en el host -> Puerto 80 dentro del contenedor
    volumes:
      - ./src:/var/www/html  # Montaje de volumen: sincroniza cambios de código y persiste la base de datos
    restart: unless-stopped
```

* **`ports: "8080:80"`**: Permite al usuario acceder a la aplicación en su máquina abriendo `http://localhost:8080`.
* **`volumes: ./src:/var/www/html`**: Vincula el código local con el contenedor. Gracias a esto, la base de datos SQL (`src/data/database.sqlite`) persiste físicamente en el disco del usuario aunque el contenedor se apague o reinicie.

---

## 6. GUÍA DE INSTALACIÓN, EJECUCIÓN Y PRUEBAS

### Paso 1: Configurar permisos de Docker (si aplica en Linux)
Para ejecutar comandos de Docker sin `sudo`, el usuario debe pertenecer al grupo `docker`:
```bash
sudo usermod -aG docker $USER
newgrp docker
```

### Paso 2: Clonar el proyecto desde GitHub
```bash
git clone git@github.com:Pineda-25/conversor-presupuesto-php.git
cd conversor-presupuesto-php
```

### Paso 3: Construir y levantar el contenedor
Ejecutar en la terminal:
```bash
docker compose up -d --build
```
* Parámetros:
  * `-d` (*detached*): Ejecuta el contenedor en segundo plano, liberando la terminal.
  * `--build`: Fuerza la compilación de la imagen asegurando que se incluyan todos los cambios del `Dockerfile`.

### Paso 4: Comprobar el estado del contenedor
```bash
docker ps
```
Debe figurar el contenedor `app_conversor_presupuesto` en estado **Up** con el puerto `0.0.0.0:8080->80/tcp`.

### Paso 5: Probar la aplicación en el navegador
1. Abrir el navegador en: `http://localhost:8080`.
2. Probar el **Conversor de Divisas**: ingresar 100 USD a EUR, seleccionar 1% de comisión y hacer clic en **Calcular y Guardar en SQL**. Verificar que aparezca en la tabla de historial.
3. Probar el **Presupuesto**: agregar un ingreso (ej. "Sueldo", $1500) y un gasto (ej. "Supermercado", $200). Verificar que las tarjetas de métricas actualicen el Balance Neto automáticamente.

### Paso 6: Ver los logs en tiempo real (opcional para depuración)
```bash
docker logs -f app_conversor_presupuesto
```

### Paso 7: Detener la aplicación
```bash
docker compose down
```

---

## 7. VENTAJAS DE LA SOLUCIÓN IMPLEMENTADA

1. **Aislamiento Total:** El entorno de ejecución no ensucia el sistema anfitrión con paquetes, librerías o servidores web locales.
2. **Reproducibilidad Garantizada:** El entorno es idéntico para cualquier persona que clone el repositorio, eliminando incompatibilidades entre Windows, Linux y macOS.
3. **Persistencia de Datos Segura:** El archivo SQL se almacena en el volumen local, evitando pérdida de información tras apagados o reinicios.
4. **Facilidad de Despliegue (CI/CD):** Esta misma imagen Docker puede ser desplegada directamente en servidores en la nube (AWS EC2, Google Cloud Run, DigitalOcean, Azure) sin modificar una sola línea de código.

---

## 8. CONCLUSIONES

- La integración de Docker en proyectos PHP permite simplificar drásticamente el proceso de entrega y evaluación de software, eliminando configuraciones manuales complejas.
- La utilización de PDO con SQLite proporcionó una base de datos relacional SQL completa, eficiente y autónoma, idónea para sistemas modulares y portables.
- El uso de Docker Compose abstrae la complejidad de la red y el montaje de volúmenes en un archivo declarativo fácil de versionar y compartir.
