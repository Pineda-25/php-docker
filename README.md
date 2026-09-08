# 💱 FinanSmart - Conversor de Divisas & Gestor de Presupuesto

> Aplicación web desarrollada en **PHP 8.2** con persistencia en **Base de Datos SQL (SQLite vía PDO)** y completamente **contenedorizada con Docker y Docker Compose**.

---

## 🚀 Características Principales

1. **Conversor de Divisas en Tiempo Real**:
   - Soporte para múltiples divisas: USD, EUR, COP, MXN, PEN, ARS, GBP, BRL, CLP.
   - Cálculo automático de tasas cruzadas y comisiones bancarias/casas de cambio.
   - **Registro automático en SQL** de cada conversión efectuada con fecha y hora.

2. **Calculadora y Control de Presupuesto**:
   - Registro de ingresos y gastos categorizados (Alimentación, Transporte, Servicios, etc.).
   - Panel de indicadores financieros en tiempo real: **Total Ingresos**, **Total Gastos** y **Balance Neto**.
   - Tabla interactiva con eliminación de registros directamente en la base de datos SQL.

3. **Portabilidad Total con Docker**:
   - No requiere instalar PHP, Apache ni bases de datos en la máquina anfitriona.
   - Los datos se almacenan en un volumen persistente (`src/data/`).
   - Se levanta en cualquier sistema operativo (Linux, Windows, macOS) con un solo comando.

---

## 📁 Estructura del Proyecto

```text
conversor-presupuesto-php/
├── Dockerfile              # Imagen personalizada: PHP 8.2 + Apache + PDO SQLite
├── docker-compose.yml      # Configuración de servicios, puertos y volúmenes
├── .dockerignore           # Archivos excluidos del contenedor
├── .gitignore              # Archivos excluidos de Git
├── README.md               # Documentación general
├── INFORME.md              # Informe técnico detallado para entrega académica
└── src/                    # Código fuente de la aplicación
    ├── db.php              # Conexión PDO y creación de tablas SQL
    ├── index.php           # Interfaz gráfica y lógica de negocio
    └── data/               # Directorio donde se guarda la base de datos SQLite
```

---

## 🛠️ Requisitos Previos

Solo necesitas tener instalado:
- [Docker Engine](https://docs.docker.com/engine/install/)
- [Docker Compose](https://docs.docker.com/compose/)

---

## ⚡ Cómo Ejecutar la Aplicación

### 1. Clonar el repositorio
```bash
git clone git@github.com:Pineda-25/conversor-presupuesto-php.git
cd conversor-presupuesto-php
```

### 2. Levantar el contenedor Docker
```bash
docker compose up -d --build
```

### 3. Abrir en el navegador
Ingresa a tu navegador web favorito en:
```text
http://localhost:8080
```

### 4. Detener el contenedor
Cuando desees apagar la aplicación:
```bash
docker compose down
```

---

## 📊 Arquitectura de la Base de Datos SQL

La aplicación crea automáticamente dos tablas al iniciar:

1. **`conversiones`**:
   - `id` (INTEGER PRIMARY KEY AUTOINCREMENT)
   - `monto_origen` (REAL)
   - `moneda_origen` (TEXT)
   - `monto_destino` (REAL)
   - `moneda_destino` (TEXT)
   - `tasa` (REAL)
   - `comision_porcentaje` (REAL)
   - `fecha` (DATETIME)

2. **`movimientos_presupuesto`**:
   - `id` (INTEGER PRIMARY KEY AUTOINCREMENT)
   - `descripcion` (TEXT)
   - `tipo` (TEXT: 'ingreso' | 'gasto')
   - `categoria` (TEXT)
   - `monto` (REAL)
   - `fecha` (DATETIME)

---

## 👤 Autor
Desarrollado por **Pineda-25**.
