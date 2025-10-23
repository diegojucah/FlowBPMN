#!/bin/bash

##############################################################################
# Script de Debug do Plugin flowBPMN no Docker
##############################################################################

set -e

CONTAINER_ID="${1:-4a5500931c39}"
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}═══════════════════════════════════════════════════════════${NC}"
echo -e "${YELLOW}  DEBUG flowBPMN no Docker${NC}"
echo -e "${YELLOW}═══════════════════════════════════════════════════════════${NC}"
echo ""

echo -e "${GREEN}1. Verificando logs de erro PHP:${NC}"
docker exec ${CONTAINER_ID} tail -50 /var/www/glpi/files/_log/php-errors.log 2>/dev/null || echo "Nenhum log encontrado"
echo ""

echo -e "${GREEN}2. Verificando arquivos do plugin:${NC}"
docker exec ${CONTAINER_ID} ls -la /var/www/glpi/plugins/flowBPMN/ 2>/dev/null || echo "Plugin não encontrado"
echo ""

echo -e "${GREEN}3. Verificando tabelas do banco:${NC}"
docker exec ${CONTAINER_ID} mysql -u root -proot glpi -e "SHOW TABLES LIKE 'glpi_plugin_flowbpmn%';" 2>/dev/null || echo "Erro ao acessar banco"
echo ""

echo -e "${GREEN}4. Testando sintaxe PHP do setup.php:${NC}"
docker exec ${CONTAINER_ID} php -l /var/www/glpi/plugins/flowBPMN/setup.php 2>&1
echo ""

echo -e "${GREEN}5. Verificando se JS existe:${NC}"
docker exec ${CONTAINER_ID} test -f /var/www/glpi/plugins/flowBPMN/js/flowbpmn.js && echo "✅ flowbpmn.js existe" || echo "❌ flowbpmn.js NÃO existe"
echo ""

echo -e "${GREEN}6. Verificando permissões:${NC}"
docker exec ${CONTAINER_ID} ls -la /var/www/glpi/plugins/flowBPMN/js/ 2>/dev/null || echo "Diretório js não encontrado"
echo ""

echo -e "${YELLOW}═══════════════════════════════════════════════════════════${NC}"
