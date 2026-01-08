# FlowBPMN - Guia do Administrador

[![GLPI Version](https://img.shields.io/badge/GLPI-11.0+-orange.svg)](https://glpi-project.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.1+-purple.svg)](https://php.net/)

Guia completo para administradores do plugin FlowBPMN no GLPI.

---

## 📚 Índice

1. [Visão Geral](#visão-geral)
2. [Gerenciamento de Permissões](#gerenciamento-de-permissões)
3. [Configurações do Plugin](#configurações-do-plugin)
4. [Monitoramento](#monitoramento)
5. [Manutenção](#manutenção)
6. [Segurança](#segurança)
7. [Integração com GLPI](#integração-com-glpi)
8. [Backup e Restore](#backup-e-restore)
9. [Troubleshooting Avançado](#troubleshooting-avançado)

---

## Visão Geral

### Responsabilidades do Administrador

Como administrador do FlowBPMN, você é responsável por:

- ✅ Configurar permissões de perfis
- ✅ Monitorar uso e performance
- ✅ Realizar manutenção periódica
- ✅ Garantir segurança dos dados
- ✅ Gerenciar backups
- ✅ Resolver problemas técnicos
- ✅ Treinar usuários

### Arquitetura do Plugin

```
┌─────────────────────────────────────┐
│         Frontend (Browser)          │
│  BPMN.io Editor + Bootstrap UI      │
└──────────────┬──────────────────────┘
               │ AJAX
┌──────────────▼──────────────────────┐
│         Backend (PHP)                │
│  Classes + AJAX Endpoints            │
└──────────────┬──────────────────────┘
               │ SQL
┌──────────────▼──────────────────────┐
│         Database (MySQL)             │
│  5 Tables + GLPI Core                │
└─────────────────────────────────────┘
```

### Tabelas do Banco de Dados

| Tabela | Registros | Função |
|--------|-----------|--------|
| `glpi_plugin_flowbpmn_flows` | Diagramas | Armazena diagramas BPMN |
| `glpi_plugin_flowbpmn_versions` | Versões | Histórico de versões |
| `glpi_plugin_flowbpmn_templates` | Templates | Modelos reutilizáveis |
| `glpi_plugin_flowbpmn_profiles` | Perfis | Permissões por perfil |
| `glpi_plugin_flowbpmn_configs` | 1 | Configurações globais |

---

## Gerenciamento de Permissões

### Estrutura de Permissões

O FlowBPMN usa um sistema de permissões granular com 4 níveis:

| Permissão | Descrição | Permite |
|-----------|-----------|---------|
| **View** | Visualizar | Ver diagramas, versões, templates |
| **Edit** | Editar | Criar e modificar diagramas |
| **Delete** | Deletar | Remover versões e templates |
| **Restore** | Restaurar | Restaurar versões anteriores |

### Configurando Permissões

#### Via Interface GLPI

1. Acesse **Configurar → Perfis**
2. Selecione um perfil (ex: "Technician")
3. Clique na aba **"FlowBPMN"**
4. Configure permissões por tipo de item:

```
┌──────────────┬──────┬──────┬────────┬─────────┐
│ Item Type    │ View │ Edit │ Delete │ Restore │
├──────────────┼──────┼──────┼────────┼─────────┤
│ Tickets      │  ✓   │  ✓   │   ✗    │   ✗     │
│ Problems     │  ✓   │  ✓   │   ✗    │   ✗     │
│ Changes      │  ✓   │  ✗   │   ✗    │   ✗     │
└──────────────┴──────┴──────┴────────┴─────────┘
```

5. Clique em **"Salvar"**

#### Via SQL (Avançado)

```sql
-- Atualizar permissões de um perfil específico
UPDATE glpi_plugin_flowbpmn_profiles
SET 
    can_view_ticket = 1,
    can_edit_ticket = 1,
    can_delete_ticket = 0,
    can_restore_ticket = 0
WHERE profiles_id = 3;  -- ID do perfil Technician
```

### Matriz de Permissões Recomendadas

#### Super-Admin / Admin

```
Tickets:   View ✓ | Edit ✓ | Delete ✓ | Restore ✓
Problems:  View ✓ | Edit ✓ | Delete ✓ | Restore ✓
Changes:   View ✓ | Edit ✓ | Delete ✓ | Restore ✓
```

**Justificativa**: Controle total para administração.

#### Technician

```
Tickets:   View ✓ | Edit ✓ | Delete ✗ | Restore ✗
Problems:  View ✓ | Edit ✓ | Delete ✗ | Restore ✗
Changes:   View ✓ | Edit ✗ | Delete ✗ | Restore ✗
```

**Justificativa**: Pode documentar atendimentos, mas não deletar histórico.

#### Observer

```
Tickets:   View ✓ | Edit ✗ | Delete ✗ | Restore ✗
Problems:  View ✓ | Edit ✗ | Delete ✗ | Restore ✗
Changes:   View ✓ | Edit ✗ | Delete ✗ | Restore ✗
```

**Justificativa**: Apenas visualização para auditoria.

#### Self-Service

```
Tickets:   View ✗ | Edit ✗ | Delete ✗ | Restore ✗
Problems:  View ✗ | Edit ✗ | Delete ✗ | Restore ✗
Changes:   View ✗ | Edit ✗ | Delete ✗ | Restore ✗
```

**Justificativa**: Usuários finais não precisam ver processos internos.

### Casos de Uso de Permissões

#### Caso 1: Equipe de Documentação

**Objetivo**: Criar biblioteca de processos

**Permissões**:
- View: ✓ (todos os tipos)
- Edit: ✓ (todos os tipos)
- Delete: ✓ (para limpar templates)
- Restore: ✗ (não necessário)

#### Caso 2: Auditoria

**Objetivo**: Revisar processos sem modificar

**Permissões**:
- View: ✓ (todos os tipos)
- Edit: ✗
- Delete: ✗
- Restore: ✗

#### Caso 3: Gerente de Mudanças

**Objetivo**: Planejar mudanças complexas

**Permissões**:
- View: ✓ (todos os tipos)
- Edit: ✓ (apenas Changes)
- Delete: ✗
- Restore: ✓ (apenas Changes)

### Verificando Permissões

```sql
-- Listar permissões de todos os perfis
SELECT 
    p.name AS profile_name,
    fp.can_view_ticket,
    fp.can_edit_ticket,
    fp.can_delete_ticket,
    fp.can_restore_ticket
FROM glpi_plugin_flowbpmn_profiles fp
JOIN glpi_profiles p ON p.id = fp.profiles_id
ORDER BY p.name;
```

---

## Configurações do Plugin

### Configurações Disponíveis

As configurações são armazenadas na tabela `glpi_plugin_flowbpmn_configs`:

| Configuração | Tipo | Padrão | Descrição |
|--------------|------|--------|-----------|
| `enable_auto_attach_image` | boolean | 1 | Auto-anexar PNG ao item |
| `enable_auto_attach_xml` | boolean | 0 | Auto-anexar BPMN XML ao item |
| `max_versions_per_item` | int | 10 | Máximo de versões mantidas |
| `enable_version_cleanup` | boolean | 1 | Limpeza automática de versões |
| `enable_export_bpmn` | boolean | 1 | Habilitar exportação BPMN |
| `enable_export_svg` | boolean | 1 | Habilitar exportação SVG |
| `enable_export_png` | boolean | 1 | Habilitar exportação PNG |
| `default_canvas_height` | int | 800 | Altura do canvas (px) |
| `enable_grid` | boolean | 1 | Habilitar grade |
| `grid_size` | int | 10 | Tamanho da grade (px) |
| `enable_notifications` | boolean | 0 | Notificações (futuro) |

### Visualizando Configurações

```sql
SELECT * FROM glpi_plugin_flowbpmn_configs;
```

### Alterando Configurações

> **⚠️ ATENÇÃO**: Não há interface de configuração ainda (planejado para v2.3.0). Alterações devem ser feitas via SQL.

#### Exemplo 1: Aumentar Limite de Versões

```sql
UPDATE glpi_plugin_flowbpmn_configs
SET max_versions_per_item = 20
WHERE id = 1;
```

#### Exemplo 2: Desabilitar Auto-attach de PNG

```sql
UPDATE glpi_plugin_flowbpmn_configs
SET enable_auto_attach_image = 0
WHERE id = 1;
```

#### Exemplo 3: Habilitar Auto-attach de XML

```sql
UPDATE glpi_plugin_flowbpmn_configs
SET enable_auto_attach_xml = 1
WHERE id = 1;
```

### Configurações Recomendadas

#### Ambiente de Produção

```sql
UPDATE glpi_plugin_flowbpmn_configs SET
    enable_auto_attach_image = 1,      -- PNG para visualização rápida
    enable_auto_attach_xml = 0,        -- XML ocupa espaço
    max_versions_per_item = 10,        -- Histórico moderado
    enable_version_cleanup = 1,        -- Limpeza automática
    enable_grid = 1,                   -- Facilita alinhamento
    grid_size = 10                     -- Padrão BPMN
WHERE id = 1;
```

#### Ambiente de Desenvolvimento

```sql
UPDATE glpi_plugin_flowbpmn_configs SET
    enable_auto_attach_image = 1,
    enable_auto_attach_xml = 1,        -- Útil para debug
    max_versions_per_item = 50,        -- Mais histórico
    enable_version_cleanup = 0,        -- Não deletar versões
    enable_grid = 1,
    grid_size = 5                      -- Grade mais fina
WHERE id = 1;
```

---

## Monitoramento

### Estatísticas de Uso

#### Total de Diagramas

```sql
SELECT 
    COUNT(*) AS total_diagrams,
    COUNT(DISTINCT users_id) AS unique_users
FROM glpi_plugin_flowbpmn_flows
WHERE is_deleted = 0;
```

#### Diagramas por Tipo de Item

```sql
SELECT 
    itemtype,
    COUNT(*) AS count
FROM glpi_plugin_flowbpmn_flows
WHERE is_deleted = 0
GROUP BY itemtype
ORDER BY count DESC;
```

#### Usuários Mais Ativos

```sql
SELECT 
    u.name AS user_name,
    COUNT(f.id) AS diagrams_created
FROM glpi_plugin_flowbpmn_flows f
JOIN glpi_users u ON u.id = f.users_id
WHERE f.is_deleted = 0
GROUP BY f.users_id
ORDER BY diagrams_created DESC
LIMIT 10;
```

#### Templates Mais Usados

```sql
SELECT 
    name,
    (SELECT COUNT(*) FROM glpi_plugin_flowbpmn_flows 
     WHERE bpmn_xml LIKE CONCAT('%', t.name, '%')) AS usage_count
FROM glpi_plugin_flowbpmn_templates t
WHERE is_active = 1
ORDER BY usage_count DESC
LIMIT 10;
```

### Monitoramento de Performance

#### Tamanho dos Diagramas

```sql
SELECT 
    itemtype,
    AVG(LENGTH(bpmn_xml)) AS avg_size_bytes,
    MAX(LENGTH(bpmn_xml)) AS max_size_bytes
FROM glpi_plugin_flowbpmn_flows
WHERE is_deleted = 0
GROUP BY itemtype;
```

#### Versões por Diagrama

```sql
SELECT 
    f.id,
    f.name,
    COUNT(v.id) AS version_count
FROM glpi_plugin_flowbpmn_flows f
LEFT JOIN glpi_plugin_flowbpmn_versions v ON v.plugin_flowbpmn_flows_id = f.id
WHERE f.is_deleted = 0
GROUP BY f.id
HAVING version_count > 5
ORDER BY version_count DESC;
```

### Logs e Auditoria

#### Atividade Recente

```sql
SELECT 
    f.id,
    f.name,
    f.itemtype,
    f.items_id,
    u.name AS user_name,
    f.date_mod AS last_modified
FROM glpi_plugin_flowbpmn_flows f
JOIN glpi_users u ON u.id = f.users_id
WHERE f.is_deleted = 0
ORDER BY f.date_mod DESC
LIMIT 20;
```

#### Histórico de Restaurações

```sql
SELECT 
    v.id,
    v.plugin_flowbpmn_flows_id,
    v.version_number,
    u.name AS restored_by,
    v.date_creation AS restored_at
FROM glpi_plugin_flowbpmn_versions v
JOIN glpi_users u ON u.id = v.users_id
WHERE v.comment LIKE '%Restored%'
ORDER BY v.date_creation DESC;
```

---

## Manutenção

### Limpeza de Dados

#### Remover Versões Antigas Manualmente

```sql
-- Deletar versões antigas (mantendo últimas 10)
DELETE v FROM glpi_plugin_flowbpmn_versions v
WHERE v.id NOT IN (
    SELECT id FROM (
        SELECT id 
        FROM glpi_plugin_flowbpmn_versions
        WHERE plugin_flowbpmn_flows_id = v.plugin_flowbpmn_flows_id
        ORDER BY version_number DESC
        LIMIT 10
    ) AS keep
);
```

#### Remover Diagramas de Itens Deletados

```sql
-- Marcar como deletado diagramas de tickets deletados
UPDATE glpi_plugin_flowbpmn_flows f
SET f.is_deleted = 1
WHERE f.itemtype = 'Ticket'
AND f.items_id NOT IN (SELECT id FROM glpi_tickets WHERE is_deleted = 0);
```

#### Limpar Templates Inativos

```sql
-- Deletar templates não usados há mais de 1 ano
DELETE FROM glpi_plugin_flowbpmn_templates
WHERE is_active = 0
AND date_mod < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

### Otimização de Banco de Dados

#### Analisar Tabelas

```sql
ANALYZE TABLE glpi_plugin_flowbpmn_flows;
ANALYZE TABLE glpi_plugin_flowbpmn_versions;
ANALYZE TABLE glpi_plugin_flowbpmn_templates;
```

#### Otimizar Tabelas

```sql
OPTIMIZE TABLE glpi_plugin_flowbpmn_flows;
OPTIMIZE TABLE glpi_plugin_flowbpmn_versions;
OPTIMIZE TABLE glpi_plugin_flowbpmn_templates;
```

#### Verificar Índices

```sql
SHOW INDEX FROM glpi_plugin_flowbpmn_flows;
```

### Tarefas Periódicas Recomendadas

| Tarefa | Frequência | Comando |
|--------|------------|---------|
| Backup completo | Diário | Ver seção [Backup](#backup-e-restore) |
| Limpeza de versões | Semanal | Automático (se habilitado) |
| Otimização de tabelas | Mensal | `OPTIMIZE TABLE ...` |
| Análise de uso | Mensal | Queries de estatísticas |
| Auditoria de permissões | Trimestral | Revisar perfis |

---

## Segurança

### Autenticação e Autorização

#### Verificação de Sessão

Todos os endpoints AJAX verificam:
```php
Session::checkLoginUser();  // Usuário autenticado?
```

#### Verificação de Permissões

Antes de cada operação:
```php
PluginFlowbpmnFlow::canView($item);    // Pode visualizar?
PluginFlowbpmnFlow::canEdit($item);    // Pode editar?
PluginFlowbpmnFlow::canDelete($item);  // Pode deletar?
```

### Proteção contra Ataques

#### CSRF Protection

Todos os formulários incluem token CSRF:
```php
Html::closeForm();  // Adiciona token automaticamente
```

#### SQL Injection Prevention

Todas as queries usam prepared statements:
```php
$DB->request([
    'FROM' => 'glpi_plugin_flowbpmn_flows',
    'WHERE' => ['id' => $id]  // Escapado automaticamente
]);
```

#### XSS Protection

Todo output é escapado:
```php
echo Html::cleanPostForTextArea($data);
```

### Auditoria de Segurança

#### Verificar Permissões Suspeitas

```sql
-- Perfis com permissões de delete sem view
SELECT p.name
FROM glpi_plugin_flowbpmn_profiles fp
JOIN glpi_profiles p ON p.id = fp.profiles_id
WHERE (fp.can_delete_ticket = 1 AND fp.can_view_ticket = 0)
   OR (fp.can_delete_problem = 1 AND fp.can_view_problem = 0)
   OR (fp.can_delete_change = 1 AND fp.can_view_change = 0);
```

#### Verificar Acessos Recentes

```sql
-- Últimas modificações por usuário
SELECT 
    u.name,
    COUNT(*) AS modifications,
    MAX(f.date_mod) AS last_activity
FROM glpi_plugin_flowbpmn_flows f
JOIN glpi_users u ON u.id = f.users_id
WHERE f.date_mod > DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY u.id
ORDER BY modifications DESC;
```

### Recomendações de Segurança

1. ✅ **Mantenha GLPI atualizado** (11.0.x mais recente)
2. ✅ **Use HTTPS** em produção
3. ✅ **Configure permissões mínimas** necessárias
4. ✅ **Faça backups regulares**
5. ✅ **Monitore logs** de acesso
6. ✅ **Revise permissões** trimestralmente
7. ✅ **Treine usuários** em boas práticas

---

## Integração com GLPI

### Entidades e Recursividade

O FlowBPMN respeita o sistema de entidades do GLPI:

```sql
-- Diagramas por entidade
SELECT 
    e.name AS entity_name,
    COUNT(f.id) AS diagram_count
FROM glpi_plugin_flowbpmn_flows f
JOIN glpi_entities e ON e.id = f.entities_id
WHERE f.is_deleted = 0
GROUP BY f.entities_id;
```

### Anexos e Documentos

PNGs são salvos automaticamente em:
```
/var/www/html/glpi/files/_plugins/flowbpmn/
```

Estrutura:
```
_plugins/
└── flowbpmn/
    ├── Ticket/
    │   ├── 123_diagram_20260107.png
    │   └── 456_diagram_20260107.png
    ├── Problem/
    └── Change/
```

### Perfis e Permissões

O FlowBPMN se integra ao sistema de perfis do GLPI:

```sql
-- Sincronizar com novos perfis
INSERT INTO glpi_plugin_flowbpmn_profiles (profiles_id)
SELECT id FROM glpi_profiles
WHERE id NOT IN (SELECT profiles_id FROM glpi_plugin_flowbpmn_profiles);
```

---

## Backup e Restore

### Backup Completo

#### Via mysqldump

```bash
#!/bin/bash
# backup_flowbpmn.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backup/flowbpmn"

# Criar diretório
mkdir -p $BACKUP_DIR

# Backup do banco de dados
mysqldump -u root -p glpi \
    glpi_plugin_flowbpmn_flows \
    glpi_plugin_flowbpmn_versions \
    glpi_plugin_flowbpmn_templates \
    glpi_plugin_flowbpmn_profiles \
    glpi_plugin_flowbpmn_configs \
    > $BACKUP_DIR/flowbpmn_db_$DATE.sql

# Backup dos arquivos
tar -czf $BACKUP_DIR/flowbpmn_files_$DATE.tar.gz \
    /var/www/html/glpi/plugins/flowbpmn/ \
    /var/www/html/glpi/files/_plugins/flowbpmn/

# Manter apenas últimos 30 dias
find $BACKUP_DIR -name "flowbpmn_*" -mtime +30 -delete

echo "Backup concluído: $BACKUP_DIR"
```

#### Agendar com Cron

```bash
# Editar crontab
crontab -e

# Adicionar linha (backup diário às 2h)
0 2 * * * /usr/local/bin/backup_flowbpmn.sh
```

### Restore de Backup

```bash
#!/bin/bash
# restore_flowbpmn.sh

BACKUP_FILE=$1

if [ -z "$BACKUP_FILE" ]; then
    echo "Uso: $0 <arquivo_backup.sql>"
    exit 1
fi

# Restaurar banco de dados
mysql -u root -p glpi < $BACKUP_FILE

echo "Restore concluído"
```

### Backup Seletivo

#### Apenas Templates

```bash
mysqldump -u root -p glpi \
    glpi_plugin_flowbpmn_templates \
    > templates_backup.sql
```

#### Apenas Diagramas de um Período

```bash
mysqldump -u root -p glpi \
    glpi_plugin_flowbpmn_flows \
    --where="date_creation >= '2026-01-01'" \
    > flows_2026.sql
```

---

## Troubleshooting Avançado

### Diagnóstico de Performance

#### Queries Lentas

```sql
-- Habilitar slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;

-- Verificar queries lentas
SELECT * FROM mysql.slow_log
WHERE sql_text LIKE '%flowbpmn%';
```

#### Tamanho das Tabelas

```sql
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
FROM information_schema.TABLES
WHERE table_schema = 'glpi'
AND table_name LIKE 'glpi_plugin_flowbpmn%'
ORDER BY size_mb DESC;
```

### Problemas Comuns

#### Problema: Versões não são limpas automaticamente

**Diagnóstico**:
```sql
SELECT * FROM glpi_plugin_flowbpmn_configs
WHERE enable_version_cleanup = 1;
```

**Solução**:
```sql
-- Habilitar limpeza
UPDATE glpi_plugin_flowbpmn_configs
SET enable_version_cleanup = 1;

-- Executar limpeza manual
DELETE v FROM glpi_plugin_flowbpmn_versions v
WHERE v.id NOT IN (
    SELECT id FROM (
        SELECT id FROM glpi_plugin_flowbpmn_versions
        WHERE plugin_flowbpmn_flows_id = v.plugin_flowbpmn_flows_id
        ORDER BY version_number DESC
        LIMIT 10
    ) AS keep
);
```

#### Problema: Permissões não funcionam após atualização

**Diagnóstico**:
```sql
-- Verificar se todos os perfis têm registro
SELECT p.id, p.name, fp.id AS flowbpmn_profile_id
FROM glpi_profiles p
LEFT JOIN glpi_plugin_flowbpmn_profiles fp ON fp.profiles_id = p.id;
```

**Solução**:
```sql
-- Criar registros faltantes
INSERT INTO glpi_plugin_flowbpmn_profiles (profiles_id)
SELECT id FROM glpi_profiles
WHERE id NOT IN (SELECT profiles_id FROM glpi_plugin_flowbpmn_profiles);
```

### Ferramentas de Diagnóstico

#### Script de Verificação

```bash
#!/bin/bash
# check_flowbpmn.sh

echo "=== FlowBPMN Health Check ==="

# Verificar plugin ativo
php /var/www/html/glpi/bin/console glpi:plugin:list | grep flowbpmn

# Verificar tabelas
mysql -u root -p glpi -e "SHOW TABLES LIKE 'glpi_plugin_flowbpmn%';"

# Verificar configurações
mysql -u root -p glpi -e "SELECT * FROM glpi_plugin_flowbpmn_configs;"

# Verificar permissões de arquivos
ls -la /var/www/html/glpi/plugins/flowbpmn/

# Verificar logs
tail -n 50 /var/www/html/glpi/files/_log/php-errors.log | grep flowbpmn

echo "=== Check Completo ==="
```

---

## Recursos Adicionais

### Documentação

- [Guia de Instalação](INSTALLATION.md)
- [Guia do Usuário](USER_GUIDE.md)
- [Guia do Desenvolvedor](DEVELOPER_GUIDE.md)
- [Referência da API](API_REFERENCE.md)
- [Schema do Banco](DATABASE_SCHEMA.md)

### Suporte

- [GitHub Issues](https://github.com/diegojucah/FlowBPMN/issues)
- [Fórum GLPI](https://forum.glpi-project.org/)
- [Documentação GLPI](https://glpi-project.org/documentation/)

---

**Administre o FlowBPMN com confiança!** 🔧🚀

Para dúvidas ou sugestões, visite nosso [GitHub](https://github.com/diegojucah/FlowBPMN).
