# FlowBPMN - Guia de Instalação

[![GLPI Version](https://img.shields.io/badge/GLPI-11.0+-orange.svg)](https://glpi-project.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.1+-purple.svg)](https://php.net/)

Guia completo de instalação do plugin FlowBPMN para GLPI 11.

---

## 📋 Pré-requisitos

Antes de iniciar a instalação, certifique-se de que seu ambiente atende aos seguintes requisitos:

### Requisitos Obrigatórios

| Componente | Versão Mínima | Versão Recomendada |
|------------|---------------|-------------------|
| **GLPI** | 11.0.0 | 11.0.x (mais recente) |
| **PHP** | 8.1 | 8.2+ |
| **MySQL** | 5.7 | 8.0+ |
| **MariaDB** | 10.3 | 10.6+ |

### Extensões PHP Necessárias

- `mysqli` ou `pdo_mysql`
- `json`
- `mbstring`
- `session`
- `fileinfo`

### Permissões de Sistema

O usuário do servidor web (geralmente `www-data` ou `apache`) precisa de:
- **Leitura** em todos os arquivos do plugin
- **Escrita** no diretório de anexos do GLPI (para salvar PNGs)
- **Execução** em diretórios

### Navegadores Suportados

- Chrome/Chromium 90+
- Firefox 88+
- Edge 90+
- Safari 14+

> **Nota**: JavaScript deve estar habilitado no navegador.

---

## 🚀 Métodos de Instalação

### Método 1: Via Git Clone (Recomendado para Desenvolvimento)

Ideal para desenvolvedores ou quem deseja contribuir com o projeto.

```bash
# 1. Navegue até o diretório de plugins do GLPI
cd /var/www/html/glpi/plugins

# 2. Clone o repositório
git clone https://github.com/diegojucah/FlowBPMN.git flowbpmn

# 3. Configure permissões
sudo chown -R www-data:www-data flowbpmn
sudo chmod -R 755 flowbpmn

# 4. Verifique a instalação
ls -la flowbpmn/
```

**Vantagens**:
- Fácil atualização com `git pull`
- Acesso a branches de desenvolvimento
- Contribuição facilitada

---

### Método 2: Via Download ZIP (Recomendado para Produção)

Ideal para ambientes de produção ou instalações simples.

```bash
# 1. Navegue até o diretório de plugins
cd /var/www/html/glpi/plugins

# 2. Baixe a versão mais recente
wget https://github.com/diegojucah/FlowBPMN/archive/refs/heads/glpi-11.zip

# 3. Extraia o arquivo
unzip glpi-11.zip

# 4. Renomeie o diretório
mv FlowBPMN-glpi-11 flowbpmn

# 5. Configure permissões
sudo chown -R www-data:www-data flowbpmn
sudo chmod -R 755 flowbpmn

# 6. Remova o arquivo ZIP
rm glpi-11.zip
```

**Vantagens**:
- Instalação limpa e controlada
- Sem dependências do Git
- Ideal para servidores de produção

---

### Método 3: Via GLPI Marketplace (Em Breve)

> **Status**: Planejado para versão 3.0.0

Quando disponível no marketplace oficial do GLPI:

1. Acesse **Configurar → Plugins → Marketplace**
2. Busque por "FlowBPMN"
3. Clique em **Instalar**
4. Clique em **Ativar**

---

## ⚙️ Instalação via Interface GLPI

Após copiar os arquivos para o diretório de plugins:

### Passo 1: Acessar Gerenciamento de Plugins

1. Faça login no GLPI como **administrador**
2. Navegue para: **Configurar → Plugins**
3. Localize **"FlowBPMN"** na lista de plugins

![Localizar Plugin](assets/screenshots/plugin-list.png)

### Passo 2: Instalar o Plugin

1. Clique no botão **"Instalar"** ao lado do FlowBPMN
2. Aguarde a conclusão da instalação
3. Verifique se aparece a mensagem de sucesso

**O que acontece durante a instalação**:
- ✅ Criação de 5 tabelas no banco de dados
- ✅ Inserção de configurações padrão
- ✅ Criação de permissões para perfis existentes
- ✅ Registro de hooks do GLPI

### Passo 3: Ativar o Plugin

1. Após a instalação, clique em **"Ativar"**
2. O status deve mudar para **"Ativo"**
3. O plugin está pronto para uso!

---

## 🔍 Verificação da Instalação

### Verificação via Interface

1. **Verificar Tab em Tickets**:
   - Abra qualquer ticket existente
   - Verifique se a aba **"FlowBPMN"** aparece
   - Clique na aba e verifique se o editor carrega

2. **Verificar Permissões**:
   - Acesse **Configurar → Perfis → [Seu Perfil]**
   - Verifique se a aba **"FlowBPMN"** está disponível
   - Confirme as permissões padrão

### Verificação via Linha de Comando

```bash
# 1. Verificar se o plugin está listado
cd /var/www/html/glpi
php bin/console glpi:plugin:list

# Saída esperada:
# flowbpmn | 2.2.0 | ENABLED

# 2. Verificar tabelas do banco de dados
mysql -u root -p glpi -e "SHOW TABLES LIKE 'glpi_plugin_flowbpmn%';"

# Saída esperada (5 tabelas):
# glpi_plugin_flowbpmn_configs
# glpi_plugin_flowbpmn_flows
# glpi_plugin_flowbpmn_profiles
# glpi_plugin_flowbpmn_templates
# glpi_plugin_flowbpmn_versions

# 3. Verificar arquivos críticos
ls -la /var/www/html/glpi/plugins/flowbpmn/lib/bpmn-js/

# Saída esperada (4 arquivos):
# bpmn-embedded.css
# bpmn-js.css
# bpmn-modeler.development.js
# diagram-js.css
```

### Verificação de Permissões

```bash
# Verificar propriedade dos arquivos
ls -la /var/www/html/glpi/plugins/flowbpmn/

# Esperado: www-data:www-data (ou apache:apache)

# Verificar permissões de diretórios
find /var/www/html/glpi/plugins/flowbpmn/ -type d -exec ls -ld {} \; | head -5

# Esperado: drwxr-xr-x (755)

# Verificar permissões de arquivos
find /var/www/html/glpi/plugins/flowbpmn/ -type f -exec ls -l {} \; | head -5

# Esperado: -rw-r--r-- (644)
```

---

## 🎯 Configuração Inicial

### 1. Configurar Permissões de Perfis

Após a instalação, configure as permissões para cada perfil:

1. Acesse **Configurar → Perfis**
2. Selecione um perfil (ex: "Super-Admin")
3. Clique na aba **"FlowBPMN"**
4. Configure as permissões:

| Tipo de Item | View | Edit | Delete | Restore |
|--------------|------|------|--------|---------|
| **Tickets** | ✅ | ✅ | ✅ | ✅ |
| **Problems** | ✅ | ✅ | ✅ | ✅ |
| **Changes** | ✅ | ✅ | ✅ | ✅ |

**Permissões Recomendadas por Perfil**:

- **Super-Admin / Admin**: Todas as permissões
- **Technician**: View + Edit
- **Observer**: Apenas View
- **Self-Service**: Nenhuma (ocultar aba)

5. Clique em **"Salvar"**
6. Repita para outros perfis conforme necessário

### 2. Testar Funcionalidades Básicas

#### Teste 1: Criar Diagrama

1. Abra um ticket existente
2. Clique na aba **"FlowBPMN"**
3. Arraste um **Start Event** da paleta
4. Adicione uma **Task** e um **End Event**
5. Conecte os elementos com setas
6. Clique em **"Salvar"**
7. Verifique a mensagem de sucesso
8. Confirme que o PNG foi anexado ao ticket

#### Teste 2: Versões

1. Modifique o diagrama criado
2. Salve novamente
3. Clique em **"Versões"**
4. Verifique se 2 versões aparecem
5. Teste restaurar a versão anterior

#### Teste 3: Templates

1. Crie um diagrama
2. Clique na seta ao lado de **"Salvar"**
3. Selecione **"Salvar como Template"**
4. Digite um nome e salve
5. Abra outro ticket
6. Clique em **"Templates"**
7. Verifique se seu template aparece
8. Teste aplicar o template

---

## 🔄 Migração de Versões Anteriores

### De v1.x para v2.x

> **⚠️ ATENÇÃO**: Faça backup completo antes de atualizar!

#### Passo 1: Backup

```bash
# Backup do banco de dados
mysqldump -u root -p glpi \
  glpi_plugin_flowbpmn_flows \
  glpi_plugin_flowbpmn_versions \
  glpi_plugin_flowbpmn_templates \
  glpi_plugin_flowbpmn_profiles \
  glpi_plugin_flowbpmn_configs \
  > flowbpmn_backup_$(date +%Y%m%d).sql

# Backup dos arquivos
tar -czf flowbpmn_files_backup_$(date +%Y%m%d).tar.gz \
  /var/www/html/glpi/plugins/flowbpmn/
```

#### Passo 2: Desativar Versão Antiga

1. Acesse **Configurar → Plugins**
2. Clique em **"Desativar"** no FlowBPMN
3. **NÃO** desinstale (para preservar dados)

#### Passo 3: Atualizar Arquivos

```bash
# Remover arquivos antigos (mantendo dados no banco)
cd /var/www/html/glpi/plugins
sudo rm -rf flowbpmn/

# Instalar nova versão (escolha um método acima)
git clone https://github.com/diegojucah/FlowBPMN.git flowbpmn
sudo chown -R www-data:www-data flowbpmn
sudo chmod -R 755 flowbpmn
```

#### Passo 4: Reinstalar via Interface

1. Acesse **Configurar → Plugins**
2. Clique em **"Instalar"** no FlowBPMN
3. Clique em **"Ativar"**

> **Nota**: O instalador detecta tabelas existentes e preserva os dados.

#### Passo 5: Verificar Migração

```bash
# Verificar versão instalada
php bin/console glpi:plugin:list | grep flowbpmn

# Verificar dados preservados
mysql -u root -p glpi -e "SELECT COUNT(*) FROM glpi_plugin_flowbpmn_flows;"
```

---

## 🐳 Instalação via Docker (Opcional)

Se você usa GLPI em Docker:

```yaml
# docker-compose.yml
version: '3.8'

services:
  glpi:
    image: diouxx/glpi:latest
    volumes:
      - ./flowbpmn:/var/www/html/glpi/plugins/flowbpmn:ro
    environment:
      - GLPI_VERSION=11.0
```

```bash
# Clonar plugin no host
git clone https://github.com/diegojucah/FlowBPMN.git flowbpmn

# Reiniciar container
docker-compose restart glpi

# Acessar container e instalar
docker-compose exec glpi bash
cd /var/www/html/glpi
php bin/console glpi:plugin:install flowbpmn
php bin/console glpi:plugin:activate flowbpmn
```

---

## 🛠️ Solução de Problemas de Instalação

### Problema: Plugin não aparece na lista

**Causa**: Arquivos não estão no diretório correto

**Solução**:
```bash
# Verificar estrutura
ls -la /var/www/html/glpi/plugins/flowbpmn/setup.php

# Deve existir. Se não:
cd /var/www/html/glpi/plugins
mv FlowBPMN flowbpmn  # Nome deve ser minúsculo
```

### Problema: Erro ao instalar - Tabelas já existem

**Causa**: Instalação anterior não foi completamente removida

**Solução**:
```sql
-- Remover tabelas antigas
DROP TABLE IF EXISTS glpi_plugin_flowbpmn_versions;
DROP TABLE IF EXISTS glpi_plugin_flowbpmn_flows;
DROP TABLE IF EXISTS glpi_plugin_flowbpmn_templates;
DROP TABLE IF EXISTS glpi_plugin_flowbpmn_profiles;
DROP TABLE IF EXISTS glpi_plugin_flowbpmn_configs;

-- Reinstalar via interface GLPI
```

### Problema: Erro de permissão ao salvar

**Causa**: Permissões incorretas no diretório de anexos

**Solução**:
```bash
# Verificar diretório de anexos do GLPI
ls -la /var/www/html/glpi/files/_plugins/flowbpmn/

# Corrigir permissões
sudo chown -R www-data:www-data /var/www/html/glpi/files/
sudo chmod -R 755 /var/www/html/glpi/files/
```

### Problema: Editor não carrega (spinner infinito)

**Causa**: Biblioteca BPMN.io não encontrada

**Solução**:
```bash
# Verificar arquivos da biblioteca
ls -la /var/www/html/glpi/plugins/flowbpmn/lib/bpmn-js/

# Se vazio, re-clone o repositório
cd /var/www/html/glpi/plugins
sudo rm -rf flowbpmn
git clone https://github.com/diegojucah/FlowBPMN.git flowbpmn
```

### Problema: Erro de versão PHP

**Causa**: PHP < 8.1

**Solução**:
```bash
# Verificar versão do PHP
php -v

# Atualizar PHP (Ubuntu/Debian)
sudo apt update
sudo apt install php8.2 php8.2-mysql php8.2-mbstring

# Reiniciar servidor web
sudo systemctl restart apache2
```

---

## 📞 Suporte

Se encontrar problemas durante a instalação:

1. **Verifique os logs**:
   - GLPI: `/var/www/html/glpi/files/_log/`
   - Apache: `/var/log/apache2/error.log`
   - PHP: `/var/log/php/error.log`

2. **Consulte a documentação**:
   - [Troubleshooting Guide](TROUBLESHOOTING.md)
   - [FAQ](README.md#faq)

3. **Reporte problemas**:
   - [GitHub Issues](https://github.com/diegojucah/FlowBPMN/issues)
   - [GLPI Forum](https://forum.glpi-project.org/)

---

## ✅ Checklist de Instalação

Use este checklist para garantir uma instalação completa:

- [ ] Pré-requisitos verificados (GLPI 11+, PHP 8.1+)
- [ ] Arquivos copiados para `/var/www/html/glpi/plugins/flowbpmn/`
- [ ] Permissões configuradas (755 dirs, 644 files, www-data owner)
- [ ] Plugin instalado via interface GLPI
- [ ] Plugin ativado
- [ ] 5 tabelas criadas no banco de dados
- [ ] Aba FlowBPMN aparece em Tickets
- [ ] Editor BPMN carrega corretamente
- [ ] Permissões de perfis configuradas
- [ ] Teste de criação de diagrama realizado
- [ ] Teste de salvamento bem-sucedido
- [ ] PNG anexado automaticamente ao ticket
- [ ] Sistema de versões funcionando
- [ ] Templates funcionando
- [ ] Exportação funcionando

---

## 🎉 Próximos Passos

Após a instalação bem-sucedida:

1. **Configure permissões** para todos os perfis
2. **Leia o [Guia do Usuário](USER_GUIDE.md)** para aprender a usar o plugin
3. **Crie templates** de processos comuns
4. **Treine sua equipe** no uso do editor BPMN
5. **Explore recursos avançados** no [Guia do Administrador](ADMIN_GUIDE.md)

---

**Instalação concluída com sucesso!** 🚀

Para dúvidas ou sugestões, visite nosso [GitHub](https://github.com/diegojucah/FlowBPMN).
