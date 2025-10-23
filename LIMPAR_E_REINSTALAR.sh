#!/bin/bash

##############################################################################
# Script para Limpar Completamente e Reinstalar o Plugin flowBPMN
##############################################################################

set -e

CONTAINER_ID="${1:-4a5500931c39}"
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${RED}╔═══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${RED}║     LIMPEZA COMPLETA E REINSTALAÇÃO - flowBPMN              ║${NC}"
echo -e "${RED}╚═══════════════════════════════════════════════════════════════╝${NC}"
echo ""

echo -e "${YELLOW}⚠️  Este script irá:${NC}"
echo "   1. Remover completamente o plugin flowBPMN do GLPI"
echo "   2. Limpar todas as tabelas do banco de dados"
echo "   3. Reinstalar o plugin do zero"
echo ""
read -p "Deseja continuar? (s/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo "Operação cancelada."
    exit 1
fi

echo ""
echo -e "${BLUE}PASSO 1: Limpando banco de dados...${NC}"
docker exec ${CONTAINER_ID} mysql -u root -proot glpi << 'SQL' 2>/dev/null || true
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS glpi_plugin_flowbpmn_versions;
DROP TABLE IF EXISTS glpi_plugin_flowbpmn_flows;
DROP TABLE IF EXISTS glpi_plugin_flowbpmn_profiles;
DROP TABLE IF EXISTS glpi_plugin_flowbpmn_configs;
DROP TABLE IF EXISTS glpi_plugin_flowBPMN_versions;
DROP TABLE IF EXISTS glpi_plugin_flowBPMN_flows;
DROP TABLE IF EXISTS glpi_plugin_flowBPMN_config;
SET FOREIGN_KEY_CHECKS = 1;
SQL

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Banco de dados limpo${NC}"
else
    echo -e "${RED}❌ Erro ao limpar banco de dados${NC}"
fi

echo ""
echo -e "${BLUE}PASSO 2: Removendo arquivos antigos do Docker...${NC}"
docker exec ${CONTAINER_ID} bash -c "
    rm -rf /var/www/glpi/plugins/flowBPMN
    rm -rf /var/www/glpi/plugins/flowbpmn
    rm -f /tmp/flowBPMN.zip
"
echo -e "${GREEN}✅ Arquivos antigos removidos${NC}"

echo ""
echo -e "${BLUE}PASSO 3: Limpando cache do GLPI...${NC}"
docker exec ${CONTAINER_ID} bash -c "
    rm -rf /var/www/glpi/files/_cache/*
    rm -rf /var/www/glpi/files/_sessions/*
"
echo -e "${GREEN}✅ Cache limpo${NC}"

echo ""
echo -e "${BLUE}PASSO 4: Criando novo ZIP do plugin...${NC}"
cd /home/diego/glpi11
rm -f /tmp/flowBPMN.zip
zip -r /tmp/flowBPMN.zip flowBPMN/ \
    -x "flowBPMN/.git/*" \
    -x "flowBPMN/node_modules/*" \
    -x "flowBPMN/.gitignore" \
    > /dev/null 2>&1
echo -e "${GREEN}✅ ZIP criado${NC}"

echo ""
echo -e "${BLUE}PASSO 5: Copiando para o Docker...${NC}"
docker cp /tmp/flowBPMN.zip ${CONTAINER_ID}:/tmp/
echo -e "${GREEN}✅ Copiado para o Docker${NC}"

echo ""
echo -e "${BLUE}PASSO 6: Instalando plugin no Docker...${NC}"
docker exec ${CONTAINER_ID} bash -c "
    cd /tmp
    unzip -q -o flowBPMN.zip
    mv flowBPMN /var/www/glpi/plugins/
    chown -R www-data:www-data /var/www/glpi/plugins/flowBPMN
    chmod -R 755 /var/www/glpi/plugins/flowBPMN
    rm /tmp/flowBPMN.zip
"
echo -e "${GREEN}✅ Plugin instalado${NC}"

echo ""
echo -e "${BLUE}PASSO 7: Verificando instalação...${NC}"
docker exec ${CONTAINER_ID} ls -la /var/www/glpi/plugins/flowBPMN/setup.php
docker exec ${CONTAINER_ID} php -l /var/www/glpi/plugins/flowBPMN/setup.php

echo ""
echo -e "${BLUE}PASSO 8: Verificando logs de erro...${NC}"
docker exec ${CONTAINER_ID} tail -20 /var/www/glpi/files/_log/php-errors.log 2>/dev/null || echo "Sem erros recentes"

echo ""
echo -e "${GREEN}╔═══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║                  INSTALAÇÃO CONCLUÍDA                        ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}📋 PRÓXIMOS PASSOS:${NC}"
echo ""
echo "  1. Acesse o GLPI no navegador"
echo "  2. Vá em: Configurar → Plugins"
echo "  3. Localize 'BPMN Flow v1.0.0'"
echo "  4. Clique em [Instalar]"
echo "  5. Clique em [Ativar]"
echo ""
echo -e "${YELLOW}🐛 Se houver erro:${NC}"
echo "  Execute: ./DEBUG_DOCKER.sh"
echo ""

# Limpar ZIP local
rm -f /tmp/flowBPMN.zip

exit 0
