# Base PHP con Apache
FROM php:8.2-apache

# Instalar Node.js y dependencias necesarias (incluye GTK para PrusaSlicer)
RUN apt-get update && apt-get install -y \
    wget \
    fuse \
    libglu1-mesa \
    libtbb-dev \
    libdbus-1-3 \
    nodejs \
    npm \
    unzip \
    libgtk-3-0 \
    libx11-xcb1 \
    libxtst6 \
    libxrandr2 \
    libasound2 \
    libpangocairo-1.0-0 \
    libpango-1.0-0 \
    libcairo2 \
    libatk1.0-0 \
    && rm -rf /var/lib/apt/lists/*

# Copiar la carpeta www al contenedor
COPY ./www /var/www/html

# Crear carpeta para perfiles de impresora
RUN mkdir -p /app/profiles

# Descargar y descomprimir PrusaSlicer
RUN wget https://github.com/prusa3d/PrusaSlicer/releases/download/version_2.7.4/PrusaSlicer-2.7.4+linux-x64-GTK3-202404050928.AppImage -O /tmp/prusaslicer.AppImage \
    && chmod +x /tmp/prusaslicer.AppImage \
    && mkdir -p /app/squashfs-root \
    && /tmp/prusaslicer.AppImage --appimage-extract \
    && mv squashfs-root /app/squashfs-root \
    && rm /tmp/prusaslicer.AppImage

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Exponer el puerto 80
EXPOSE 80