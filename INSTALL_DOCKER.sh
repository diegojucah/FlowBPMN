#!/bin/bash

##############################################################################
# Script de Instalação do Plugin flowBPMN no Docker GLPI
# Versão: 1.0.0
# Autor: Diego Jucá
##############################################################################

set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configurações
CONTAINER_ID="${1:-4a5500931c39}"
PLUGIN_NAME="flowBPMN"
PLUGIN_DIR="/home/diego/glpi11/${PLUGIN_NAME}"
GLPI_PLUGINS_DIR="/var/www/glpi/plugins"

echo -e "${BLUE}╔═══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║     Instalação Plugin flowBPMN no Docker GLPI 11            ║${NC}"
echo -e "${BLUE}╚═══════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Verificar se o Docker está rodando
if ! docker ps > /dev/null 2>&1; then
    echo -e "${RED}❌ Erro: Docker não está rodando ou você não tem permissões.${NC}"
    echo -e "${YELLOW}   Execute: sudo chmod 666 /var/run/docker.sock${NC}"
    exit 1
fi

# Verificar se o container existe
if ! docker ps -a --format '{{.ID}}' | grep -q "^${CONTAINER_ID}"; then
    echo -e "${RED}❌ Erro: Container ${CONTAINER_ID} não encontrado.${NC}"
    echo -e "${YELLOW}   Containers disponíveis:${NC}"
    docker ps -a --format "   {{.ID}} - {{.Names}} ({{.Status}})"
    exit 1
fi

# Verificar se o container está rodando
if ! docker ps --format '{{.ID}}' | grep -q "^${CONTAINER_ID}"; then
    echo -e "${YELLOW}⚠️  Container ${CONTAINER_ID} não está rodando. Iniciando...${NC}"
    docker start "${CONTAINER_ID}"
    sleep 3
fi

echo -e "${GREEN}✅ Container ${CONTAINER_ID} está rodando${NC}"
echo ""

# Criar backup se plugin já existe no container
echo -e "${BLUE}📦 Verificando plugin existente...${NC}"
if docker exec "${CONTAINER_ID}" test -d "${GLPI_PLUGINS_DIR}/${PLUGIN_NAME}" 2>/dev/null; then
    echo -e "${YELLOW}⚠️  Plugin existente encontrado. Criando backup...${NC}"
    BACKUP_NAME="${PLUGIN_NAME}_backup_$(date +%Y%m%d_%H%M%S)"
    docker exec "${CONTAINER_ID}" bash -c "
        cd ${GLPI_PLUGINS_DIR} && \
        cp -r ${PLUGIN_NAME} ${BACKUP_NAME}
    "
    echo -e "${GREEN}✅ Backup criado: ${BACKUP_NAME}${NC}"
fi

# Criar ZIP do plugin
echo -e "${BLUE}📦 Criando arquivo ZIP do plugin...${NC}"
cd "$(dirname "${PLUGIN_DIR}")"
ZIP_FILE="/tmp/${PLUGIN_NAME}.zip"

if [ -f "${ZIP_FILE}" ]; then
    rm "${ZIP_FILE}"
fi

zip -r "${ZIP_FILE}" "${PLUGIN_NAME}/" \
    -x "${PLUGIN_NAME}/.git/*" \
    -x "${PLUGIN_NAME}/node_modules/*" \
    -x "${PLUGIN_NAME}/.gitignore" \
    -x "${PLUGIN_NAME}/*.md~" \
    > /dev/null 2>&1

echo -e "${GREEN}✅ ZIP criado: ${ZIP_FILE}${NC}"
echo ""

# Copiar ZIP para o container
echo -e "${BLUE}📤 Copiando plugin para o container...${NC}"
docker cp "${ZIP_FILE}" "${CONTAINER_ID}:/tmp/"
echo -e "${GREEN}✅ Arquivo copiado para o container${NC}"

# Desinstalar versão antiga (se existir)
echo -e "${BLUE}🔄 Removendo plugin antigo...${NC}"
docker exec "${CONTAINER_ID}" bash -c "
    if [ -d '${GLPI_PLUGINS_DIR}/${PLUGIN_NAME}' ]; then
        rm -rf '${GLPI_PLUGINS_DIR}/${PLUGIN_NAME}'
        echo 'Plugin antigo removido'
    else
        echo 'Nenhum plugin antigo encontrado'
    fi
"

# Extrair e instalar no container
echo -e "${BLUE}📦 Extraindo e instalando plugin...${NC}"
docker exec "${CONTAINER_ID}" bash -c "
    cd /tmp && \
    unzip -q -o ${PLUGIN_NAME}.zip && \
    mv ${PLUGIN_NAME} ${GLPI_PLUGINS_DIR}/ && \
    chown -R www-data:www-data ${GLPI_PLUGINS_DIR}/${PLUGIN_NAME} && \
    chmod -R 755 ${GLPI_PLUGINS_DIR}/${PLUGIN_NAME} && \
    rm /tmp/${PLUGIN_NAME}.zip
"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Plugin instalado com sucesso!${NC}"
else
    echo -e "${RED}❌ Erro ao instalar plugin${NC}"
    exit 1
fi

echo ""
echo -e "${BLUE}╔═══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║                    INSTALAÇÃO CONCLUÍDA                      ║${NC}"
echo -e "${BLUE}╚═══════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${GREEN}✅ Plugin flowBPMN instalado no container ${CONTAINER_ID}${NC}"
echo ""
echo -e "${YELLOW}📋 PRÓXIMOS PASSOS:${NC}"
echo ""
echo -e "  1️⃣  Acesse o GLPI no navegador"
echo -e "  2️⃣  Vá em: ${BLUE}Configurar → Plugins${NC}"
echo -e "  3️⃣  Localize ${BLUE}BPMN Flow v1.0.0${NC}"
echo -e "  4️⃣  Clique em ${GREEN}Instalar${NC}"
echo -e "  5️⃣  Clique em ${GREEN}Ativar${NC}"
echo ""
echo -e "${YELLOW}🧪 TESTE DO PLUGIN:${NC}"
echo ""
echo -e "  • Abra um ${BLUE}Chamado/Problema/Mudança${NC}"
echo -e "  • Verifique a aba ${BLUE}BPMN Flow${NC}"
echo -e "  • Crie um fluxo BPMN"
echo -e "  • Salve e verifique versionamento"
echo ""
echo -e "${YELLOW}⚙️  CONFIGURAÇÃO:${NC}"
echo ""
echo -e "  • ${BLUE}Configurar → Gerais → BPMN Flow${NC}"
echo -e "  • ${BLUE}Configurar → Perfis → [Perfil] → Aba BPMN Flow${NC}"
echo ""
echo -e "${YELLOW}📊 VERIFICAR LOGS:${NC}"
echo ""
echo -e "  ${BLUE}docker exec -it ${CONTAINER_ID} tail -f /var/www/glpi/files/_log/php-errors.log${NC}"
echo ""
echo -e "${YELLOW}🗑️  DESINSTALAR (para testar):${NC}"
echo ""
echo -e "  ${BLUE}Configurar → Plugins → flowBPMN → Desinstalar${NC}"
echo ""

# Limpar arquivo ZIP local
rm "${ZIP_FILE}"

exit 0
