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

if [ ! -d "src/public" ]; then
    echo "Clonando repositório..."
    mkdir -p src
    (
        cd src || exit
        git clone "$REPO"
        mv Fase1 public
        rm -rf Fase1
    )
    echo "Copiando arquivos da pasta files/ para src/public/"
    cp -a files/. src/public/
fi

# Restores executable permissions
echo "Restaurando permissões executáveis para arquivos críticos..."

chmod +x \
    src/public/system/core/LaravelLikeUtils/var-dumper/Resources/bin/var-dump-server \
    src/public/system/libraries/Vendor/paragonie/random_compat/build-phar.sh

find src/public -type f -name "*.sh" -exec chmod +x {} +

echo
printf '\xF0\x9F\xA7\xB9 Limpando arquivos temporários...\n'
find . -type f -name "*Zone.Identifier*" -exec rm -f {} +

echo
printf '\xF0\x9F\x9A\x80 Iniciando containers...\n'
docker-compose up -d
