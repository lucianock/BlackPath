FROM ubuntu:22.04

# Evitar prompts interactivos durante la instalación
ENV DEBIAN_FRONTEND=noninteractive

# Actualizar e instalar dependencias básicas
RUN apt-get update && apt-get install -y \
    nmap \
    git \
    iputils-ping \
    dnsutils \
    net-tools \
    wget \
    curl \
    ca-certificates \
    ruby \
    ruby-dev \
    ruby-bundler \
    build-essential \
    libyaml-dev \
    sudo \
    whatweb \
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

# Instalar Go desde el sitio oficial con manejo de errores
RUN wget --no-verbose --tries=3 --timeout=60 https://go.dev/dl/go1.20.14.linux-amd64.tar.gz && \
    tar -C /usr/local -xzf go1.20.14.linux-amd64.tar.gz && \
    rm go1.20.14.linux-amd64.tar.gz && \
    ln -sf /usr/local/go/bin/go /usr/local/bin/go

# Cambiar al usuario no root
USER scanner
WORKDIR /app

# Instalar Gobuster desde binario precompilado con manejo de errores
RUN wget --no-verbose --tries=3 --timeout=60 https://github.com/OJ/gobuster/releases/download/v3.7.0/gobuster_Linux_x86_64.tar.gz && \
    tar -xzf gobuster_Linux_x86_64.tar.gz && \
    sudo mv gobuster /usr/local/bin/ && \
    sudo chmod 755 /usr/local/bin/gobuster && \
    rm gobuster_Linux_x86_64.tar.gz

# Descargar wordlists con manejo de errores
RUN wget --no-verbose --tries=3 --timeout=60 https://raw.githubusercontent.com/danielmiessler/SecLists/master/Discovery/Web-Content/common.txt -O /app/wordlists/common.txt && \
    wget --no-verbose --tries=3 --timeout=60 https://raw.githubusercontent.com/danielmiessler/SecLists/master/Discovery/Web-Content/raft-medium-directories.txt -O /app/wordlists/medium.txt && \
    wget --no-verbose --tries=3 --timeout=60 https://raw.githubusercontent.com/danielmiessler/SecLists/master/Discovery/Web-Content/raft-large-directories.txt -O /app/wordlists/full.txt

# Verificar que las herramientas están instaladas y funcionan
RUN which nmap && \
    which gobuster && \
    which whatweb && \
    nmap --version && \
    gobuster version && \
    whatweb --version

# Mantener el contenedor corriendo
CMD ["tail", "-f", "/dev/null"] 