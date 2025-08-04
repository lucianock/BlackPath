FROM ubuntu:22.04

# Evitar prompts interactivos durante la instalación
ENV DEBIAN_FRONTEND=noninteractive

# Actualizar e instalar dependencias
RUN apt-get update && apt-get install -y \
    nmap \
    golang \
    git \
    iputils-ping \
    dnsutils \
    net-tools \
    wget \
    ruby \
    ruby-dev \
    ruby-bundler \
    build-essential \
    libyaml-dev \
    sudo \
    && rm -rf /var/lib/apt/lists/*

# Crear usuario no root y agregar al grupo sudo
RUN useradd -m -s /bin/bash scanner && \
    usermod -aG sudo scanner && \
    echo "scanner ALL=(ALL) NOPASSWD:ALL" >> /etc/sudoers

# Crear directorios y establecer permisos
RUN mkdir -p /app/wordlists /app/results && \
    chown -R scanner:scanner /app && \
    chmod -R 755 /usr/bin/nmap

# Configurar GOPATH y PATH
ENV GOPATH=/home/scanner/go
ENV PATH=$PATH:/home/scanner/go/bin:/usr/local/go/bin

# Instalar Go desde el sitio oficial (versión 1.20 para compatibilidad con Gobuster)
RUN wget https://go.dev/dl/go1.20.14.linux-amd64.tar.gz && \
    tar -C /usr/local -xzf go1.20.14.linux-amd64.tar.gz && \
    rm go1.20.14.linux-amd64.tar.gz

# Cambiar al usuario no root
USER scanner
WORKDIR /app

# Instalar WhatWeb
RUN git clone https://github.com/urbanadventurer/WhatWeb.git /home/scanner/whatweb && \
    cd /home/scanner/whatweb && \
    bundle install && \
    sudo ln -s /home/scanner/whatweb/whatweb /usr/local/bin/whatweb

# Instalar Gobuster desde binario precompilado
RUN wget https://github.com/OJ/gobuster/releases/download/v3.7.0/gobuster_Linux_x86_64.tar.gz && \
    tar -xzf gobuster_Linux_x86_64.tar.gz && \
    sudo mv gobuster /usr/local/bin/ && \
    sudo chmod 755 /usr/local/bin/gobuster && \
    rm gobuster_Linux_x86_64.tar.gz

# Descargar wordlists
RUN wget https://raw.githubusercontent.com/danielmiessler/SecLists/master/Discovery/Web-Content/common.txt -O /app/wordlists/common.txt && \
    wget https://raw.githubusercontent.com/danielmiessler/SecLists/master/Discovery/Web-Content/raft-medium-directories.txt -O /app/wordlists/medium.txt && \
    wget https://raw.githubusercontent.com/danielmiessler/SecLists/master/Discovery/Web-Content/raft-large-directories.txt -O /app/wordlists/full.txt

# Verificar que las herramientas están instaladas
RUN which nmap && \
    which gobuster && \
    which whatweb

# Mantener el contenedor corriendo
CMD ["tail", "-f", "/dev/null"] 