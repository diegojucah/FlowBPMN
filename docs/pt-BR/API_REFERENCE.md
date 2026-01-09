# FlowBPMN - API Reference

[![Version](https://img.shields.io/badge/Version-2.2.0-blue.svg)](https://github.com/diegojucah/FlowBPMN)

Referência completa da API do plugin FlowBPMN para GLPI.

---

## 📚 Índice

1. [AJAX Endpoints](#ajax-endpoints)
2. [PHP Classes](#php-classes)
3. [JavaScript API](#javascript-api)
4. [Códigos de Erro](#códigos-de-erro)

---

## AJAX Endpoints

Todos os endpoints seguem o padrão:
- **Base URL**: `/plugins/flowbpmn/ajax/`
- **Método**: POST (exceto onde indicado)
- **Autenticação**: Session-based (GLPI)
- **CSRF**: Token obrigatório em todos os requests
- **Content-Type**: `application/json` ou `application/x-www-form-urlencoded`

### 1. flow.php

**Descrição**: Handler principal para operações de fluxo (save, delete, restore).

#### Action: save

Salva ou atualiza um diagrama BPMN.

**Request**:
```json
POST /plugins/flowbpmn/ajax/flow.php
Content-Type: application/json
X-CSRF-Token: <token>

{
  "action": "save",
  "itemtype": "Ticket",
  "items_id": 123,
  "bpmn_xml": "<?xml version=\"1.0\"...",
  "svg_content": "<svg xmlns=\"http://www.w3.org/2000/svg\"...",
  "png_data": "data:image/png;base64,iVBORw0KGgo...",
  "name": "Processo de Atendimento"
}
```

**Response Success**:
```json
{
  "success": true,
  "flow_id": 456,
  "message": "Diagrama salvo com sucesso",
  "version_number": 3
}
```

**Response Error**:
```json
{
  "success": false,
  "message": "Sem permissão para editar",
  "error_code": "PERMISSION_DENIED"
}
```

**Permissões**: Requer `can_edit_{itemtype}`

---

#### Action: delete_version

Deleta uma versão específica de um diagrama.

**Request**:
```json
{
  "action": "delete_version",
  "version_id": 789
}
```

**Response Success**:
```json
{
  "success": true,
  "message": "Versão deletada com sucesso"
}
```

**Permissões**: Requer `can_delete_{itemtype}`

---

#### Action: restore

Restaura uma versão anterior de um diagrama.

**Request**:
```json
{
  "action": "restore",
  "flow_id": 456,
  "version_id": 789
}
```

**Response Success**:
```json
{
  "success": true,
  "message": "Versão restaurada com sucesso",
  "bpmn_xml": "<?xml version=\"1.0\"...",
  "svg_content": "<svg xmlns=\"http://www.w3.org/2000/svg\"..."
}
```

**Permissões**: Requer `can_restore_{itemtype}`

---

### 2. template.php

**Descrição**: Operações com templates BPMN.

#### Action: save

Salva um novo template.

**Request**:
```json
{
  "action": "save",
  "name": "Processo de Aprovação",
  "bpmn_xml": "<?xml version=\"1.0\"...",
  "svg_content": "<svg xmlns=\"http://www.w3.org/2000/svg\"...",
  "comment": "Template para processos de aprovação"
}
```

**Response Success**:
```json
{
  "success": true,
  "template_id": 12,
  "message": "Template salvo com sucesso"
}
```

---

#### Action: list

Lista todos os templates disponíveis.

**Request**:
```json
{
  "action": "list",
  "search": "aprovação",
  "page": 1,
  "per_page": 6
}
```

**Response Success**:
```json
{
  "success": true,
  "templates": [
    {
      "id": 12,
      "name": "Processo de Aprovação",
      "svg_content": "<svg...",
      "date_creation": "2026-01-07 10:30:00",
      "user_name": "Diego Jucá"
    }
  ],
  "total": 15,
  "total_pages": 3,
  "current_page": 1
}
```

---

#### Action: load

Carrega um template específico.

**Request**:
```json
{
  "action": "load",
  "template_id": 12
}
```

**Response Success**:
```json
{
  "success": true,
  "template": {
    "id": 12,
    "name": "Processo de Aprovação",
    "bpmn_xml": "<?xml version=\"1.0\"...",
    "svg_content": "<svg..."
  }
}
```

---

#### Action: delete

Deleta um template.

**Request**:
```json
{
  "action": "delete",
  "template_id": 12
}
```

**Response Success**:
```json
{
  "success": true,
  "message": "Template deletado com sucesso"
}
```

**Permissões**: Apenas criador ou admin

---

### 3. bpmn_versions.php

**Descrição**: Lista versões de um diagrama.

**Request**:
```json
GET /plugins/flowbpmn/ajax/bpmn_versions.php?flow_id=456
```

**Response Success**:
```json
{
  "success": true,
  "versions": [
    {
      "id": 789,
      "version_number": 3,
      "svg_content": "<svg...",
      "date_creation": "2026-01-07 15:45:00",
      "user_name": "Diego Jucá"
    },
    {
      "id": 788,
      "version_number": 2,
      "svg_content": "<svg...",
      "date_creation": "2026-01-07 14:30:00",
      "user_name": "Diego Jucá"
    }
  ],
  "current_flow_id": 456,
  "can_restore": true
}
```

---

### 4. load_diagram_from_item.php

**Descrição**: Carrega diagrama de um item específico.

**Request**:
```json
{
  "itemtype": "Ticket",
  "items_id": 123
}
```

**Response Success**:
```json
{
  "success": true,
  "flow": {
    "id": 456,
    "bpmn_xml": "<?xml version=\"1.0\"...",
    "svg_content": "<svg...",
    "name": "Processo de Atendimento"
  }
}
```

---

### 5. list_items_with_diagrams.php

**Descrição**: Lista itens que possuem diagramas (para importação).

**Request**:
```json
{
  "itemtype": "Ticket",
  "search": "123",
  "page": 1,
  "per_page": 6
}
```

**Response Success**:
```json
{
  "success": true,
  "items": [
    {
      "items_id": 123,
      "item_name": "Chamado #123",
      "flow_name": "Processo de Atendimento",
      "svg_content": "<svg...",
      "date_mod": "2026-01-07 16:00:00",
      "user_name": "Diego Jucá"
    }
  ],
  "total": 25,
  "total_pages": 5
}
```

---

### 6. import_items.php

**Descrição**: Importa diagrama de outro item.

**Request**:
```json
{
  "source_itemtype": "Ticket",
  "source_items_id": 123,
  "target_itemtype": "Problem",
  "target_items_id": 456
}
```

**Response Success**:
```json
{
  "success": true,
  "message": "Diagrama importado com sucesso",
  "bpmn_xml": "<?xml version=\"1.0\"...",
  "svg_content": "<svg..."
}
```

---

## PHP Classes

### PluginFlowbpmnFlow

**Namespace**: Global  
**Extends**: `CommonDBTM`  
**File**: `inc/flow.class.php`

#### Métodos Públicos

##### getTypeName()

```php
public static function getTypeName($nb = 0): string
```

Retorna o nome do tipo para exibição.

**Parâmetros**:
- `$nb` (int): Número de itens (para pluralização)

**Retorno**: Nome traduzido do tipo

---

##### getTabNameForItem()

```php
public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string|array
```

Retorna o nome da aba para um item.

**Parâmetros**:
- `$item` (CommonGLPI): Item GLPI
- `$withtemplate` (int): Template flag

**Retorno**: Nome da aba ou array de abas

---

##### displayTabContentForItem()

```php
public static function displayTabContentForItem(
    CommonGLPI $item, 
    $tabnum = 1, 
    $withtemplate = 0
): bool
```

Exibe o conteúdo da aba.

**Parâmetros**:
- `$item` (CommonGLPI): Item GLPI
- `$tabnum` (int): Número da aba
- `$withtemplate` (int): Template flag

**Retorno**: true se exibido com sucesso

---

##### showForItem()

```php
public static function showForItem(CommonDBTM $item): void
```

Exibe o editor BPMN para um item.

**Parâmetros**:
- `$item` (CommonDBTM): Item GLPI (Ticket/Problem/Change)

**Retorno**: void (exibe HTML)

---

##### getForItem()

```php
public static function getForItem(string $itemtype, int $items_id): array|false
```

Obtém o diagrama de um item.

**Parâmetros**:
- `$itemtype` (string): Tipo do item
- `$items_id` (int): ID do item

**Retorno**: Array com dados do flow ou false

**Exemplo**:
```php
$flow = PluginFlowbpmnFlow::getForItem('Ticket', 123);
if ($flow) {
    echo $flow['bpmn_xml'];
}
```

---

##### saveFlow()

```php
public static function saveFlow(
    string $itemtype,
    int $items_id,
    string $bpmn_xml,
    string $svg_content,
    string $name = '',
    string $png_data = ''
): int|false
```

Salva ou atualiza um diagrama.

**Parâmetros**:
- `$itemtype` (string): Tipo do item
- `$items_id` (int): ID do item
- `$bpmn_xml` (string): Conteúdo BPMN XML
- `$svg_content` (string): Conteúdo SVG
- `$name` (string): Nome do diagrama
- `$png_data` (string): Dados PNG em base64

**Retorno**: ID do flow ou false

**Exemplo**:
```php
$flow_id = PluginFlowbpmnFlow::saveFlow(
    'Ticket',
    123,
    $bpmn_xml,
    $svg_content,
    'Processo de Atendimento',
    $png_data
);
```

---

##### savePNGAsDocument()

```php
public static function savePNGAsDocument(
    string $itemtype,
    int $items_id,
    string $png_data,
    string $name
): int|false
```

Salva PNG como documento anexo.

**Parâmetros**:
- `$itemtype` (string): Tipo do item
- `$items_id` (int): ID do item
- `$png_data` (string): Dados PNG em base64
- `$name` (string): Nome do arquivo

**Retorno**: ID do documento ou false

---

### PluginFlowbpmnVersion

**File**: `inc/version.class.php`

#### Métodos Principais

##### getVersionsForFlow()

```php
public static function getVersionsForFlow(int $flow_id): array
```

Obtém todas as versões de um diagrama.

**Parâmetros**:
- `$flow_id` (int): ID do flow

**Retorno**: Array de versões

---

##### restoreVersion()

```php
public static function restoreVersion(int $flow_id, int $version_id): bool
```

Restaura uma versão anterior.

**Parâmetros**:
- `$flow_id` (int): ID do flow
- `$version_id` (int): ID da versão

**Retorno**: true se restaurado com sucesso

---

##### cleanupOldVersions()

```php
public static function cleanupOldVersions(int $flow_id, int $max_versions = 10): int
```

Limpa versões antigas mantendo apenas as últimas N.

**Parâmetros**:
- `$flow_id` (int): ID do flow
- `$max_versions` (int): Máximo de versões a manter

**Retorno**: Número de versões deletadas

---

### PluginFlowbpmnTemplate

**File**: `inc/template.class.php`

#### Métodos Principais

##### getTemplates()

```php
public static function getTemplates(string $search = ''): array
```

Obtém lista de templates.

**Parâmetros**:
- `$search` (string): Termo de busca

**Retorno**: Array de templates

---

##### saveTemplate()

```php
public static function saveTemplate(
    string $name,
    string $bpmn_xml,
    string $svg_content,
    string $comment = ''
): int|false
```

Salva um novo template.

**Retorno**: ID do template ou false

---

### PluginFlowbpmnProfile

**File**: `inc/profile.class.php`

#### Métodos Principais

##### canView()

```php
public static function canView(string $itemtype, int $profile_id = null): bool
```

Verifica se pode visualizar.

**Parâmetros**:
- `$itemtype` (string): Tipo do item (Ticket/Problem/Change)
- `$profile_id` (int): ID do perfil (null = perfil atual)

**Retorno**: true se pode visualizar

---

##### canEdit()

```php
public static function canEdit(string $itemtype, int $profile_id = null): bool
```

Verifica se pode editar.

---

##### canDelete()

```php
public static function canDelete(string $itemtype, int $profile_id = null): bool
```

Verifica se pode deletar.

---

##### canRestore()

```php
public static function canRestore(string $itemtype, int $profile_id = null): bool
```

Verifica se pode restaurar.

---

## JavaScript API

### Classe: BpmnFlowEditor

**File**: `js/flowbpmn.js`

#### Constructor

```javascript
new BpmnFlowEditor(options)
```

**Parâmetros**:
```javascript
{
  itemtype: 'Ticket',      // Tipo do item
  items_id: 123,           // ID do item
  existingXml: '<?xml...',  // XML existente (opcional)
  flowId: 456              // ID do flow (opcional)
}
```

**Exemplo**:
```javascript
const editor = new BpmnFlowEditor({
  itemtype: 'Ticket',
  items_id: 123
});
```

---

#### Métodos Públicos

##### saveDiagram()

```javascript
saveDiagram(): Promise<void>
```

Salva o diagrama atual.

**Exemplo**:
```javascript
editor.saveDiagram()
  .then(() => console.log('Salvo!'))
  .catch(err => console.error(err));
```

---

##### exportBPMN()

```javascript
exportBPMN(): void
```

Exporta diagrama para BPMN XML.

---

##### exportSVG()

```javascript
exportSVG(): void
```

Exporta diagrama para SVG.

---

##### exportPNG()

```javascript
exportPNG(): void
```

Exporta diagrama para PNG.

---

##### exportPDF()

```javascript
exportPDF(): void
```

Abre diálogo de impressão para PDF.

---

##### showVersionsModal()

```javascript
showVersionsModal(): void
```

Exibe modal de versões.

---

##### showTemplatesModal()

```javascript
showTemplatesModal(): void
```

Exibe modal de templates.

---

##### showImportModal()

```javascript
showImportModal(): void
```

Exibe modal de importação.

---

##### restoreVersion()

```javascript
restoreVersion(flowId: number, versionId: number): Promise<void>
```

Restaura uma versão específica.

**Parâmetros**:
- `flowId` (number): ID do flow
- `versionId` (number): ID da versão

---

##### loadTemplate()

```javascript
loadTemplate(templateId: number): Promise<void>
```

Carrega um template.

**Parâmetros**:
- `templateId` (number): ID do template

---

## Códigos de Erro

### HTTP Status Codes

| Código | Significado | Quando Ocorre |
|--------|-------------|---------------|
| 200 | OK | Operação bem-sucedida |
| 400 | Bad Request | Parâmetros inválidos |
| 401 | Unauthorized | Não autenticado |
| 403 | Forbidden | Sem permissão |
| 404 | Not Found | Recurso não encontrado |
| 500 | Internal Server Error | Erro no servidor |

### Error Codes (Custom)

| Código | Descrição |
|--------|-----------|
| `PERMISSION_DENIED` | Usuário sem permissão |
| `INVALID_PARAMETERS` | Parâmetros inválidos |
| `FLOW_NOT_FOUND` | Diagrama não encontrado |
| `VERSION_NOT_FOUND` | Versão não encontrada |
| `TEMPLATE_NOT_FOUND` | Template não encontrado |
| `SAVE_FAILED` | Falha ao salvar |
| `DELETE_FAILED` | Falha ao deletar |
| `RESTORE_FAILED` | Falha ao restaurar |

---

## Exemplos de Uso

### Salvar Diagrama (JavaScript)

```javascript
const editor = new BpmnFlowEditor({
  itemtype: 'Ticket',
  items_id: 123
});

// Salvar
await editor.saveDiagram();
```

### Obter Diagrama (PHP)

```php
$flow = PluginFlowbpmnFlow::getForItem('Ticket', 123);
if ($flow) {
    $bpmn_xml = $flow['bpmn_xml'];
    $svg_content = $flow['svg_content'];
}
```

### Verificar Permissões (PHP)

```php
if (PluginFlowbpmnProfile::canEdit('Ticket')) {
    // Usuário pode editar diagramas em tickets
    PluginFlowbpmnFlow::showForItem($ticket);
}
```

### Restaurar Versão (AJAX)

```javascript
fetch('/plugins/flowbpmn/ajax/flow.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-Token': getCSRFToken()
  },
  body: JSON.stringify({
    action: 'restore',
    flow_id: 456,
    version_id: 789
  })
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    console.log('Versão restaurada!');
    // Recarregar diagrama
    editor.loadDiagram(data.bpmn_xml);
  }
});
```

---

## Recursos Adicionais

- [Guia do Desenvolvedor](DEVELOPER_GUIDE.md)
- [Database Schema](DATABASE_SCHEMA.md)
- [Architecture](ARCHITECTURE.md)

---

**API Reference Completa!** 📚

Para dúvidas, consulte o [GitHub](https://github.com/diegojucah/FlowBPMN).
