#!/bin/bash

# Script para sincronizar alterações do plugin flowBPMN para o Docker
# Container ID: 4a5500931c39

CONTAINER_ID="4a5500931c39"
SOURCE_DIR="/home/diego/glpi11/flowbpmn"
DEST_DIR="/var/www/glpi/plugins/flowbpmn"

echo "=== Sincronizando flowBPMN para Docker ==="
echo "Container: $CONTAINER_ID"
echo "Origem: $SOURCE_DIR"
echo "Destino: $DEST_DIR"
echo ""

# Verificar se o container está rodando
echo "Verificando container..."
if ! sudo docker ps | grep -q "$CONTAINER_ID"; then
    echo "ERRO: Container $CONTAINER_ID não está em execução!"
    exit 1
fi

echo "Container encontrado e em execução."
echo ""

# Arquivos a sincronizar
FILES=(
    "ajax/bpmn_restore.php"
    "ajax/bpmn_versions.php"
    "ajax/flow.php"
    "inc/config.class.php"
    "inc/flow.class.php"
    "inc/profile.class.php"
    "inc/version.class.php"
    "locales/pt_BR.php"
    "setup.php"
    "hook.php"
    "front/config.form.php"
    "front/profile.form.php"
    "js/flowbpmn.js"
)

# Copiar cada arquivo
for file in "${FILES[@]}"; do
    if [ -f "$SOURCE_DIR/$file" ]; then
        echo "Copiando: $file"
        sudo docker cp "$SOURCE_DIR/$file" "$CONTAINER_ID:$DEST_DIR/$file"
        if [ $? -eq 0 ]; then
            echo "  ✓ OK"
        else
            echo "  ✗ ERRO ao copiar $file"
        fi
    else
        echo "  ⚠ Arquivo não encontrado: $file"
    fi
done

echo ""
echo "Ajustando permissões..."
sudo docker exec "$CONTAINER_ID" bash -c "chown -R www-data:www-data $DEST_DIR"

if [ $? -eq 0 ]; then
    echo "✓ Permissões ajustadas com sucesso"
else
    echo "✗ ERRO ao ajustar permissões"
    exit 1
fi

echo ""
echo "=== Sincronização concluída ==="
echo "Os arquivos foram copiados para o Docker."
echo "Acesse http://localhost:8080 para testar as alterações."
