#!/bin/bash

HOSTS_FILE="/mnt/c/Windows/System32/drivers/etc/hosts"
ENTRIES=("127.0.0.1 minio.local" "127.0.0.1 mysql")
REPO="https://github.com/ConectaLa/Fase1.git"

for ENTRY in "${ENTRIES[@]}"; do
    if ! grep -Fxq "$ENTRY" "$HOSTS_FILE"; then
        echo "Adicionando '$ENTRY' aos hosts..."
        echo "$ENTRY" | sudo tee -a "$HOSTS_FILE" > /dev/null
    else
        echo "Entry '$ENTRY' já existe."
    fi
done

echo 

if [ ! -d "public" ]; then
	echo "Clonando repositório..."
	cd src
	git clone "$REPO"
	mv Fase1 public

	cd ../#!/bin/bash

HOSTS_FILE="/mnt/c/Windows/System32/drivers/etc/hosts"
ENTRIES=("127.0.0.1 minio.local" "127.0.0.1 mysql")
REPO="https://github.com/ConectaLa/Fase1.git"

# Adiciona entradas ao arquivo de hosts do Windows
for ENTRY in "${ENTRIES[@]}"; do
    if ! grep -Fxq "$ENTRY" "$HOSTS_FILE"; then
        echo "Adicionando '$ENTRY' aos hosts..."
        echo "$ENTRY" | sudo tee -a "$HOSTS_FILE" > /dev/null
    else
        echo "Entry '$ENTRY' já existe."
    fi
done

echo

# Clonagem e organização dos arquivos
if [ ! -d "public" ]; then
    echo "Clonando repositório..."
    cd src
    git clone "$REPO"
    mv Fase1 public
    rm -rf Fase1  # ← remove a pasta original após mover
    cd ../

    echo "Copiando arquivos da pasta files/ para src/public/"
    cp -a files/. src/public/
else
    cd ../
fi

# 🔧 Restaura permissões de execução para arquivos que precisam
echo "Restaurando permissões executáveis para arquivos críticos..."

chmod +x \
src/public/system/core/LaravelLikeUtils/var-dumper/Resources/bin/var-dump-server \
src/public/system/libraries/Vendor/paragonie/random_compat/build-phar.sh

# Aplica permissão +x a todos arquivos .sh (opcional e seguro)
find src/public -type f -name "*.sh" -exec chmod +x {} +

echo
echo "🧹 Limpando arquivos temporários..."
find . -type f -name "*Zone.Identifier*" -exec rm -f {} +

echo
echo "🚀 Iniciando containers..."
docker-compose up -d


	cp -a files/. src/public/
else
	cd ../
fi

echo
echo "Limpando arquivos temporários."
find . -type f -name "*Zone.Identifier*" -exec rm -f {} +

echo
echo "Iniciando containers..."
docker-compose up -d