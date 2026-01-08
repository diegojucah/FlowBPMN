# FlowBPMN - Troubleshooting

[![GLPI Version](https://img.shields.io/badge/GLPI-11.0+-orange.svg)](https://glpi-project.org/)

Guia completo de solução de problemas do plugin FlowBPMN.

---

## 📚 Índice

1. [Problemas Comuns](#problemas-comuns)
2. [Diagnóstico](#diagnóstico)
3. [Logs e Ferramentas](#logs-e-ferramentas)
4. [Problemas de Performance](#problemas-de-performance)
5. [Suporte](#suporte)

---

## Problemas Comuns

### 1. Editor Não Carrega

**Sintoma**: Spinner infinito ou tela branca na aba FlowBPMN

**Causas Prováveis**:
- Biblioteca BPMN.io não encontrada
- Erro de JavaScript
- Permissões de arquivo incorretas

**Diagnóstico**:

```bash
# 1. Verificar se arquivos existem
ls -la /var/www/html/glpi/plugins/flowbpmn/lib/bpmn-js/

# Deve mostrar:
# bpmn-modeler.development.js
# bpmn-js.css
# diagram-js.css
# bpmn-embedded.css

# 2. Verificar permissões
ls -la /var/www/html/glpi/plugins/flowbpmn/

# Esperado: drwxr-xr-x (755) para diretórios
#           -rw-r--r-- (644) para arquivos

# 3. Verificar console do navegador
# F12 → Console → Procurar erros
```

**Solução**:

```bash
# Corrigir permissões
sudo chown -R www-data:www-data /var/www/html/glpi/plugins/flowbpmn
sudo chmod -R 755 /var/www/html/glpi/plugins/flowbpmn
sudo find /var/www/html/glpi/plugins/flowbpmn -type f -exec chmod 644 {} \;

# Limpar cache do navegador
# Ctrl + Shift + Delete → Limpar tudo

# Reiniciar servidor web
sudo systemctl restart apache2
```

---

### 2. Não Consegue Salvar Diagramas

**Sintoma**: Erro ao clicar em "Salvar", mensagem "Erro ao salvar diagrama"

**Causas Prováveis**:
- Sem permissão de edição
- Item fechado/deletado
- Erro de conexão
- Timeout do servidor

**Diagnóstico**:

```bash
# 1. Verificar permissões do usuário
mysql -u root -p glpi -e "
SELECT 
    p.name AS profile,
    fp.can_edit_ticket,
    fp.can_edit_problem,
    fp.can_edit_change
FROM glpi_users u
JOIN glpi_profiles p ON p.id = u.profiles_id
LEFT JOIN glpi_plugin_flowbpmn_profiles fp ON fp.profiles_id = p.id
WHERE u.id = <USER_ID>;
"

# 2. Verificar logs do PHP
tail -f /var/www/html/glpi/files/_log/php-errors.log

# 3. Verificar logs do Apache
tail -f /var/log/apache2/error.log
```

**Solução**:

```bash
# Se permissão incorreta
mysql -u root -p glpi -e "
UPDATE glpi_plugin_flowbpmn_profiles
SET can_edit_ticket = 1
WHERE profiles_id = <PROFILE_ID>;
"

# Se timeout
# Aumentar max_execution_time no php.ini
sudo nano /etc/php/8.2/apache2/php.ini
# max_execution_time = 300

# Reiniciar Apache
sudo systemctl restart apache2
```

---

### 3. Tab FlowBPMN Não Aparece

**Sintoma**: Aba FlowBPMN não é exibida em Tickets/Problems/Changes

**Causas Prováveis**:
- Plugin não ativado
- Sem permissão de visualização
- Cache do GLPI

**Diagnóstico**:

```bash
# 1. Verificar se plugin está ativo
php /var/www/html/glpi/bin/console glpi:plugin:list | grep flowbpmn

# Esperado: flowbpmn | 2.2.0 | ENABLED

# 2. Verificar permissões
mysql -u root -p glpi -e "
SELECT * FROM glpi_plugin_flowbpmn_profiles
WHERE profiles_id = <PROFILE_ID>;
"

# 3. Limpar cache do GLPI
rm -rf /var/www/html/glpi/files/_cache/*
```

**Solução**:

```bash
# Ativar plugin
php /var/www/html/glpi/bin/console glpi:plugin:activate flowbpmn

# Dar permissão de view
mysql -u root -p glpi -e "
UPDATE glpi_plugin_flowbpmn_profiles
SET can_view_ticket = 1,
    can_view_problem = 1,
    can_view_change = 1
WHERE profiles_id = <PROFILE_ID>;
"

# Limpar cache
rm -rf /var/www/html/glpi/files/_cache/*
```

---

### 4. Templates Não Carregam

**Sintoma**: Modal de templates vazio ou erro ao carregar

**Causas Prováveis**:
- Nenhum template criado
- Templates inativos
- Erro no banco de dados

**Diagnóstico**:

```sql
-- Verificar templates
SELECT * FROM glpi_plugin_flowbpmn_templates
WHERE is_active = 1;

-- Verificar se há templates
SELECT COUNT(*) AS total_templates
FROM glpi_plugin_flowbpmn_templates;
```

**Solução**:

```sql
-- Ativar templates
UPDATE glpi_plugin_flowbpmn_templates
SET is_active = 1;

-- Ou criar template de exemplo
INSERT INTO glpi_plugin_flowbpmn_templates (
    name, bpmn_xml, svg_content, users_id, date_creation
) VALUES (
    'Processo Simples',
    '<?xml version="1.0" encoding="UTF-8"?>...',
    '<svg xmlns="http://www.w3.org/2000/svg">...</svg>',
    2,
    NOW()
);
```

---

### 5. Versões Não São Criadas

**Sintoma**: Ao salvar, não cria nova versão no histórico

**Causas Prováveis**:
- Limpeza automática desabilitada
- Erro ao criar versão
- Limite de versões atingido

**Diagnóstico**:

```sql
-- Verificar configuração
SELECT * FROM glpi_plugin_flowbpmn_configs;

-- Verificar versões de um flow
SELECT COUNT(*) AS version_count
FROM glpi_plugin_flowbpmn_versions
WHERE plugin_flowbpmn_flows_id = <FLOW_ID>;
```

**Solução**:

```sql
-- Habilitar criação de versões
UPDATE glpi_plugin_flowbpmn_configs
SET enable_version_cleanup = 1;

-- Aumentar limite de versões
UPDATE glpi_plugin_flowbpmn_configs
SET max_versions_per_item = 20;
```

---

### 6. PNG Não É Anexado

**Sintoma**: Diagrama salvo mas PNG não aparece nos anexos

**Causas Prováveis**:
- Auto-attach desabilitado
- Permissões de diretório
- Tamanho do PNG muito grande

**Diagnóstico**:

```bash
# 1. Verificar configuração
mysql -u root -p glpi -e "
SELECT enable_auto_attach_image
FROM glpi_plugin_flowbpmn_configs;
"

# 2. Verificar diretório de anexos
ls -la /var/www/html/glpi/files/_plugins/flowbpmn/

# 3. Verificar tamanho máximo de upload
php -i | grep upload_max_filesize
```

**Solução**:

```sql
-- Habilitar auto-attach
UPDATE glpi_plugin_flowbpmn_configs
SET enable_auto_attach_image = 1;
```

```bash
# Criar diretório se não existir
mkdir -p /var/www/html/glpi/files/_plugins/flowbpmn
sudo chown -R www-data:www-data /var/www/html/glpi/files/_plugins/flowbpmn
sudo chmod -R 755 /var/www/html/glpi/files/_plugins/flowbpmn

# Aumentar limite de upload (php.ini)
sudo nano /etc/php/8.2/apache2/php.ini
# upload_max_filesize = 10M
# post_max_size = 10M

# Reiniciar Apache
sudo systemctl restart apache2
```

---

### 7. Erro ao Restaurar Versão

**Sintoma**: Erro ao clicar em "Restaurar" em uma versão

**Causas Prováveis**:
- Sem permissão de restore
- Versão corrompida
- Flow deletado

**Diagnóstico**:

```sql
-- Verificar permissão
SELECT can_restore_ticket, can_restore_problem, can_restore_change
FROM glpi_plugin_flowbpmn_profiles
WHERE profiles_id = <PROFILE_ID>;

-- Verificar se versão existe
SELECT * FROM glpi_plugin_flowbpmn_versions
WHERE id = <VERSION_ID>;

-- Verificar se flow existe
SELECT * FROM glpi_plugin_flowbpmn_flows
WHERE id = <FLOW_ID> AND is_deleted = 0;
```

**Solução**:

```sql
-- Dar permissão de restore
UPDATE glpi_plugin_flowbpmn_profiles
SET can_restore_ticket = 1,
    can_restore_problem = 1,
    can_restore_change = 1
WHERE profiles_id = <PROFILE_ID>;
```

---

### 8. Importação Não Funciona

**Sintoma**: Modal de importação vazio ou erro ao importar

**Causas Prováveis**:
- Nenhum item com diagrama
- Filtro de busca muito restritivo
- Erro de permissão

**Diagnóstico**:

```sql
-- Verificar itens com diagramas
SELECT 
    itemtype,
    COUNT(*) AS count
FROM glpi_plugin_flowbpmn_flows
WHERE is_deleted = 0
GROUP BY itemtype;
```

**Solução**:

```bash
# Limpar busca e tentar novamente
# Verificar se há diagramas em outros tipos de item
```

---

### 9. Exportação Falha

**Sintoma**: Erro ao exportar para BPMN/SVG/PNG/PDF

**Causas Prováveis**:
- Diagrama vazio
- Erro de JavaScript
- Bloqueio de popup (PDF)

**Diagnóstico**:

```bash
# Verificar console do navegador
# F12 → Console → Procurar erros

# Verificar se diagrama tem conteúdo
# Deve ter pelo menos um elemento
```

**Solução**:

```bash
# Para PDF: Permitir popups no navegador
# Chrome: Configurações → Privacidade → Popups → Permitir

# Para PNG: Verificar se canvas é suportado
# Usar navegador moderno (Chrome 90+, Firefox 88+)
```

---

### 10. Idioma Não Muda

**Sintoma**: Interface continua em inglês mesmo mudando idioma no GLPI

**Causas Prováveis**:
- Arquivo de tradução não existe
- Cache do navegador
- Sessão não atualizada

**Diagnóstico**:

```bash
# Verificar se arquivo de tradução existe
ls -la /var/www/html/glpi/plugins/flowbpmn/locales/

# Deve ter:
# pt_BR.php
# en_GB.php
# es_ES.php
```

**Solução**:

```bash
# Limpar cache do navegador
# Ctrl + Shift + Delete

# Fazer logout e login novamente no GLPI

# Verificar idioma da sessão
mysql -u root -p glpi -e "
SELECT language FROM glpi_users WHERE id = <USER_ID>;
"
```

---

## Diagnóstico

### Script de Verificação Completa

```bash
#!/bin/bash
# flowbpmn_check.sh - Verificação completa do FlowBPMN

echo "=== FlowBPMN Health Check ==="
echo ""

# 1. Plugin Status
echo "1. Plugin Status:"
php /var/www/html/glpi/bin/console glpi:plugin:list | grep flowbpmn
echo ""

# 2. Database Tables
echo "2. Database Tables:"
mysql -u root -p glpi -e "SHOW TABLES LIKE 'glpi_plugin_flowbpmn%';"
echo ""

# 3. File Permissions
echo "3. File Permissions:"
ls -la /var/www/html/glpi/plugins/flowbpmn/ | head -10
echo ""

# 4. BPMN.io Library
echo "4. BPMN.io Library:"
ls -la /var/www/html/glpi/plugins/flowbpmn/lib/bpmn-js/
echo ""

# 5. Configuration
echo "5. Configuration:"
mysql -u root -p glpi -e "SELECT * FROM glpi_plugin_flowbpmn_configs;"
echo ""

# 6. Statistics
echo "6. Statistics:"
mysql -u root -p glpi -e "
SELECT 
    'Flows' AS type, COUNT(*) AS count FROM glpi_plugin_flowbpmn_flows WHERE is_deleted = 0
UNION ALL
SELECT 
    'Versions', COUNT(*) FROM glpi_plugin_flowbpmn_versions
UNION ALL
SELECT 
    'Templates', COUNT(*) FROM glpi_plugin_flowbpmn_templates WHERE is_active = 1;
"
echo ""

# 7. Recent Errors
echo "7. Recent Errors (last 20 lines):"
tail -20 /var/www/html/glpi/files/_log/php-errors.log | grep -i flowbpmn
echo ""

echo "=== Check Complete ==="
```

**Uso**:

```bash
chmod +x flowbpmn_check.sh
./flowbpmn_check.sh
```

---

## Logs e Ferramentas

### Logs do GLPI

```bash
# PHP Errors
tail -f /var/www/html/glpi/files/_log/php-errors.log

# SQL Errors
tail -f /var/www/html/glpi/files/_log/sql-errors.log

# Cron Errors
tail -f /var/www/html/glpi/files/_log/cron.log
```

### Logs do Apache

```bash
# Error Log
tail -f /var/log/apache2/error.log

# Access Log
tail -f /var/log/apache2/access.log | grep flowbpmn
```

### Logs do MySQL

```bash
# Error Log
tail -f /var/log/mysql/error.log

# Slow Query Log
tail -f /var/log/mysql/slow-query.log
```

### Console do Navegador

```
F12 → Console → Filtrar por "flowbpmn"
F12 → Network → Filtrar por "flowbpmn"
F12 → Application → Storage → Verificar cookies/session
```

---

## Problemas de Performance

### 1. Editor Lento

**Sintoma**: Editor demora para carregar ou responder

**Causas**:
- Diagrama muito grande (>100 elementos)
- Navegador antigo
- Memória insuficiente

**Solução**:

```bash
# Dividir diagrama em sub-processos
# Usar navegador moderno (Chrome 90+)
# Aumentar memória do PHP

# php.ini
memory_limit = 256M
```

---

### 2. Salvamento Lento

**Sintoma**: Demora muito para salvar diagrama

**Causas**:
- PNG muito grande
- Muitas versões antigas
- Banco de dados lento

**Solução**:

```sql
-- Limpar versões antigas
DELETE v FROM glpi_plugin_flowbpmn_versions v
WHERE v.id NOT IN (
    SELECT id FROM (
        SELECT id FROM glpi_plugin_flowbpmn_versions
        WHERE plugin_flowbpmn_flows_id = v.plugin_flowbpmn_flows_id
        ORDER BY version_number DESC
        LIMIT 10
    ) AS keep
);

-- Otimizar tabelas
OPTIMIZE TABLE glpi_plugin_flowbpmn_flows;
OPTIMIZE TABLE glpi_plugin_flowbpmn_versions;
```

---

### 3. Modal de Versões Lento

**Sintoma**: Modal demora para abrir

**Causas**:
- Muitas versões
- SVGs grandes
- Paginação não funcionando

**Solução**:

```sql
-- Reduzir limite de versões
UPDATE glpi_plugin_flowbpmn_configs
SET max_versions_per_item = 5;

-- Limpar versões antigas
-- (ver solução anterior)
```

---

## Suporte

### Antes de Reportar um Problema

1. ✅ Verifique se o problema está neste guia
2. ✅ Execute o script de verificação
3. ✅ Colete logs relevantes
4. ✅ Teste em navegador diferente
5. ✅ Verifique se plugin está atualizado

### Como Reportar

**GitHub Issues**: https://github.com/diegojucah/FlowBPMN/issues

**Template de Issue**:

```markdown
## Descrição do Problema
[Descreva o problema]

## Passos para Reproduzir
1. 
2. 
3. 

## Comportamento Esperado
[O que deveria acontecer]

## Comportamento Atual
[O que está acontecendo]

## Ambiente
- GLPI Version: 
- FlowBPMN Version: 
- PHP Version: 
- MySQL Version: 
- Browser: 
- OS: 

## Logs
```
[Cole logs relevantes]
```

## Screenshots
[Se aplicável]
```

### Informações Úteis

```bash
# Versões
php -v
mysql --version
apache2 -v

# Configuração PHP
php -i | grep -E "memory_limit|upload_max_filesize|post_max_size|max_execution_time"

# Status do Plugin
php /var/www/html/glpi/bin/console glpi:plugin:list | grep flowbpmn

# Estatísticas
mysql -u root -p glpi -e "
SELECT 
    COUNT(*) AS total_flows,
    COUNT(DISTINCT users_id) AS unique_users,
    MAX(date_mod) AS last_activity
FROM glpi_plugin_flowbpmn_flows
WHERE is_deleted = 0;
"
```

---

## Recursos Adicionais

- [Guia do Usuário](USER_GUIDE.md#perguntas-frequentes)
- [Guia do Administrador](ADMIN_GUIDE.md#troubleshooting-avançado)
- [GitHub Issues](https://github.com/diegojucah/FlowBPMN/issues)
- [Fórum GLPI](https://forum.glpi-project.org/)

---

**Problemas resolvidos!** 🔧✅

Para dúvidas, consulte o [GitHub](https://github.com/diegojucah/FlowBPMN).
