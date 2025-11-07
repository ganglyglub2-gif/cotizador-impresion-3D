# Cotizador de Impresión 3D 

Este es un sistema de cotización de impresiones 3D que utiliza PHP, Node.js y PrusaSlicer para calcular el costo de una impresión basándose en un archivo STL.

El proyecto está diseñado para correr dentro de **Docker** y **Docker Compose** para un despliegue fácil y autocontenido.

---

## Prerrequisitos

Asegúrate de tener instalado el siguiente software en tu computadora:

* [Git](https://git-scm.com/)
* [Docker](https://www.docker.com/products/docker-desktop/)
* [Docker Compose](https://docs.docker.com/compose/install/)

---

## Pasos de Instalación

Sigue estos comandos en tu terminal:

**1. Clonar el repositorio**
Descarga el código fuente desde GitHub.
git clone [https://github.com/ganglyglub2-gif/cotizador-impresion-3D.git](https://github.com/ganglyglub2-gif/cotizador-impresion-3D.git)

2.Entrar al directorio

cd cotizador-impresion-3D

3. Crear la carpeta de Subidas

mkdir www/uploads

4. Dar permisos a la carpeta de Subidas (¡Crítico!)

chmod -R 777 www/uploads

5.Construir y Levantar el Contenedor:Este comando construirá la imagen de Docker (tener abierto la aplicacion de docker desktop para este paso)

docker-compose up -d --build
(Nota: La primera vez, este paso tardará varios minutos).

6.Acceso de la aplicacion

Para acceder a la aplicación, abre tu navegador web y ve a: http://localhost:8080

7.Detener la aplicación

Para detener la aplicación, corre este comando en la misma carpeta:
docker-compose down