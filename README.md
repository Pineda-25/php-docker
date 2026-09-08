# Cambio de Monedas

Programa simple en PHP para realizar conversiones de divisas y almacenar el historial en una base de datos SQL, ejecutado completamente dentro de Docker.

---

## 1. Cómo Clonar el Repositorio

Abre una terminal y ejecuta:

```bash
git clone git@github.com:Pineda-25/php-docker.git
cd php-docker
```

*(O vía HTTPS si no usas clave SSH)*:
```bash
git clone https://github.com/Pineda-25/php-docker.git
cd php-docker
```

---

## 2. Cómo Hacerlo Correr

Solo necesitas tener Docker instalado. Ejecuta en la terminal:

```bash
docker compose up -d --build
```

Luego abre tu navegador en:
```text
http://localhost:8080
```

Para detener el programa:
```bash
docker compose down
```

---

## 3. Pasos de Cómo se Hizo Todo Mediante Docker

Para que el programa funcione en cualquier computadora sin necesidad de instalar PHP, Apache ni bases de datos en la máquina física, se siguieron estos pasos:

### Paso A: Creación del entorno con Dockerfile
Se creó un archivo `Dockerfile` utilizando la imagen oficial ligera de PHP con servidor web Apache:
- **Imagen base:** `php:8.2-apache` (trae el intérprete de PHP y el servidor web listos).
- **Módulo Rewrite:** Se habilitó `mod_rewrite` de Apache.
- **Directorio de trabajo:** Se definió `/var/www/html` dentro del contenedor.
- **Copia del código:** Se copió la carpeta `src/` al contenedor.
- **Permisos de base de datos:** Se otorgaron permisos a la carpeta `data/` para que el servidor web pueda escribir en la base de datos SQL (SQLite).
- **Puerto expuesto:** Se configuró el puerto `80`.

### Paso B: Integración de la Base de Datos SQL
- Se utilizó **SQLite vía PDO** directamente dentro del contenedor PHP.
- Esto permite usar consultas SQL estándar (`CREATE TABLE`, `INSERT`, `SELECT`, `DELETE`) sin necesidad de levantar un servicio de MySQL externo que pueda fallar por puertos ocupados o configuraciones de contraseña.
- Al iniciar la aplicación, la base de datos y la tabla `conversiones` se crean automáticamente.

### Paso C: Orquestación con docker-compose.yml
Para no tener que escribir comandos largos en la consola, se creó `docker-compose.yml`:
- **Mapeo de puertos:** Conecta el puerto `8080` de tu máquina con el puerto `80` del contenedor (`"8080:80"`).
- **Volumen de persistencia:** Conecta la carpeta `./src` local con `/var/www/html` del contenedor. Gracias a esto, cualquier dato guardado en la base de datos SQL no se borra al apagar el contenedor.

---

## Estructura de Archivos del Proyecto

```text
php-docker/
├── Dockerfile          # Configuración de la imagen con PHP y Apache
├── docker-compose.yml  # Orquestación de puertos y volúmenes
├── README.md           # Instrucciones y explicación del proyecto
└── src/                # Código de la aplicación
    ├── db.php          # Conexión y creación de la tabla SQL
    ├── index.php       # Interfaz visual y cálculo de conversión
    └── data/           # Directorio donde se guarda la base de datos SQL
```
