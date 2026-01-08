# FlowBPMN - Guia do Desenvolvedor

[![GLPI Version](https://img.shields.io/badge/GLPI-11.0+-orange.svg)](https://glpi-project.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.1+-purple.svg)](https://php.net/)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-yellow.svg)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)

Guia completo para desenvolvedores que desejam contribuir ou estender o plugin FlowBPMN.

---

## 📚 Índice

1. [Arquitetura Geral](#arquitetura-geral)
2. [Setup de Desenvolvimento](#setup-de-desenvolvimento)
3. [Estrutura do Código](#estrutura-do-código)
4. [Backend (PHP)](#backend-php)
5. [Frontend (JavaScript)](#frontend-javascript)
6. [Banco de Dados](#banco-de-dados)
7. [Internacionalização](#internacionalização-i18n)
8. [Testes](#testes)
9. [Contribuindo](#contribuindo)

---

## Arquitetura Geral

### Stack Tecnológico

| Camada | Tecnologia | Versão |
|--------|------------|--------|
| **Frontend** | BPMN.io | 18.6.1 |
| **UI Framework** | Bootstrap | 5.x (GLPI 11) |
| **Icons** | Tabler Icons | (GLPI 11) |
| **Backend** | PHP | 8.1+ |
| **Framework** | GLPI Core | 11.0+ |
| **Database** | MySQL/MariaDB | 5.7+ / 10.3+ |
| **JavaScript** | ES6+ | - |

### Diagrama de Componentes

```
┌─────────────────────────────────────────────────────────┐
│                    Browser (Client)                      │
│  ┌────────────────────────────────────────────────────┐ │
│  │  BPMN.io Editor (JavaScript)                       │ │
│  │  - BpmnFlowEditor class (2094 lines)               │ │
│  │  - Modals (Templates, Versions, Import)            │ │
│  │  - Event handlers                                  │ │
│  └────────────────┬───────────────────────────────────┘ │
└───────────────────┼─────────────────────────────────────┘
                    │ AJAX (JSON)
┌───────────────────▼─────────────────────────────────────┐
│                 GLPI Server (PHP)                        │
│  ┌────────────────────────────────────────────────────┐ │
│  │  AJAX Endpoints (16 files)                         │ │
│  │  - flow.php (main handler)                         │ │
│  │  - template.php                                    │ │
│  │  - bpmn_versions.php                               │ │
│  │  - etc.                                            │ │
│  └────────────────┬───────────────────────────────────┘ │
│  ┌────────────────▼───────────────────────────────────┐ │
│  │  PHP Classes (8 files in /inc)                     │ │
│  │  - PluginFlowbpmnFlow (main)                       │ │
│  │  - PluginFlowbpmnVersion                           │ │
│  │  - PluginFlowbpmnTemplate                          │ │
│  │  - PluginFlowbpmnProfile                           │ │
│  │  - etc.                                            │ │
│  └────────────────┬───────────────────────────────────┘ │
└───────────────────┼─────────────────────────────────────┘
                    │ SQL
┌───────────────────▼─────────────────────────────────────┐
│              MySQL/MariaDB Database                      │
│  - glpi_plugin_flowbpmn_flows                           │
│  - glpi_plugin_flowbpmn_versions                        │
│  - glpi_plugin_flowbpmn_templates                       │
│  - glpi_plugin_flowbpmn_profiles                        │
│  - glpi_plugin_flowbpmn_configs                         │
└─────────────────────────────────────────────────────────┘
```

### Fluxo de Dados

#### Salvamento de Diagrama

```
1. User clicks "Save" button
   ↓
2. BpmnFlowEditor.saveDiagram()
   - Exports BPMN XML from modeler
   - Exports SVG for preview
   - Generates PNG (canvas)
   ↓
3. AJAX POST to /plugins/flowbpmn/ajax/flow.php
   - action: "save"
   - bpmn_xml, svg_content, png_data
   - CSRF token
   ↓
4. flow.php validates:
   - Session authentication
   - CSRF token
   - User permissions
   ↓
5. PluginFlowbpmnFlow::saveFlow()
   - Creates or updates flow record
   - Calls post_addItem() or post_updateItem()
   ↓
6. post_updateItem() creates version:
   - PluginFlowbpmnVersion::add()
   - Cleanup old versions (if > max)
   ↓
7. savePNGAsDocument()
   - Saves PNG to /files/_plugins/flowbpmn/
   - Creates Document record
   - Links to item
   ↓
8. Returns JSON response:
   {
     "success": true,
     "flow_id": 123,
     "message": "Diagrama salvo com sucesso"
   }
   ↓
9. BpmnFlowEditor shows success message
```

---

## Setup de Desenvolvimento

### Pré-requisitos

- **GLPI 11.0+** instalado e funcionando
- **PHP 8.1+** com extensões: mysqli, json, mbstring, session
- **MySQL 5.7+** ou **MariaDB 10.3+**
- **Git** para controle de versão
- **IDE** recomendado: VS Code, PHPStorm

### Clonando o Repositório

```bash
# Clone o repositório
git clone https://github.com/diegojucah/FlowBPMN.git
cd FlowBPMN

# Checkout da branch de desenvolvimento
git checkout glpi-11

# Criar symlink para GLPI (desenvolvimento)
ln -s $(pwd) /var/www/html/glpi/plugins/flowbpmn
```

### Instalação no GLPI

```bash
# Via CLI
cd /var/www/html/glpi
php bin/console glpi:plugin:install flowbpmn
php bin/console glpi:plugin:activate flowbpmn

# Ou via interface web
# Configurar → Plugins → Instalar → Ativar
```

### Configuração do Ambiente

#### Habilitar Debug no GLPI

```php
// config/config_db.php
$CFG_GLPI['debug_mode'] = true;
$CFG_GLPI['debug_sql'] = true;
$CFG_GLPI['debug_vars'] = true;
```

#### Habilitar Logs de Erro PHP

```bash
# php.ini
error_reporting = E_ALL
display_errors = On
log_errors = On
error_log = /var/log/php/error.log
```

### Ferramentas Recomendadas

#### VS Code Extensions

- **PHP Intelephense** - Autocomplete e análise
- **PHP Debug** - Debugging com Xdebug
- **ESLint** - Linting JavaScript
- **Prettier** - Formatação de código
- **GitLens** - Git avançado
- **MySQL** - Gerenciamento de banco

#### Outras Ferramentas

- **Composer** - Gerenciamento de dependências PHP (futuro)
- **npm** - Gerenciamento de dependências JS (futuro)
- **Xdebug** - Debugging PHP
- **MySQL Workbench** - Design e queries SQL

---

## Estrutura do Código

### Organização de Diretórios

```
flowbpmn/
├── ajax/                    # AJAX endpoints (16 arquivos)
│   ├── flow.php            # Handler principal (save, delete, restore)
│   ├── template.php        # Operações de templates
│   ├── bpmn_versions.php   # Listagem de versões
│   ├── bpmn_restore.php    # Restauração de versões
│   ├── load_diagram_from_item.php  # Importação
│   ├── list_items_with_diagrams.php
│   ├── import_items.php
│   └── ...                 # Outros endpoints
├── css/
│   └── flowbpmn.css        # Estilos customizados (193 linhas)
├── docs/                    # Documentação
│   ├── README.md
│   ├── INSTALLATION.md
│   ├── USER_GUIDE.md
│   ├── ADMIN_GUIDE.md
│   └── assets/
├── front/                   # Páginas front-end
│   └── profile.form.php    # Formulário de perfil
├── inc/                     # Classes PHP (8 arquivos)
│   ├── flow.class.php      # Classe principal (1071 linhas)
│   ├── version.class.php   # Gerenciamento de versões
│   ├── template.class.php  # Gerenciamento de templates
│   ├── profile.class.php   # Permissões
│   ├── config.class.php    # Configurações
│   ├── helper.class.php    # Utilitários
│   ├── bpmntask.class.php  # Tarefas automatizadas
│   └── plugin.class.php    # Classe legada
├── install/                 # Scripts de instalação
│   └── mysql/              # Schemas SQL (não usado)
├── js/
│   └── flowbpmn.js         # JavaScript principal (2094 linhas)
├── lib/
│   └── bpmn-js/            # Biblioteca BPMN.io (local)
│       ├── bpmn-modeler.development.js
│       ├── bpmn-js.css
│       ├── diagram-js.css
│       └── bpmn-embedded.css
├── locales/                 # Traduções
│   ├── pt_BR.php
│   ├── en_GB.php
│   └── es_ES.php
├── templates/               # Templates BPMN de exemplo
│   ├── approval.bpmn
│   ├── parallel.bpmn
│   └── simple.bpmn
├── CHANGELOG.md
├── LICENSE
├── README.md
├── SECURITY.md
├── hook.php                 # Hooks do GLPI
├── plugin.xml               # Metadados do plugin
└── setup.php                # Setup e instalação
```

### Convenções de Nomenclatura

#### PHP

- **Classes**: `PluginFlowbpmnNome` (PascalCase com prefixo)
- **Métodos**: `getNome()`, `setNome()` (camelCase)
- **Constantes**: `PLUGIN_FLOWBPMN_VERSION` (UPPER_SNAKE_CASE)
- **Variáveis**: `$nome_variavel` (snake_case)
- **Tabelas**: `glpi_plugin_flowbpmn_nome` (snake_case)

#### JavaScript

- **Classes**: `BpmnFlowEditor` (PascalCase)
- **Métodos**: `saveDiagram()`, `loadDiagram()` (camelCase)
- **Constantes**: `BPMN_JS_URL` (UPPER_SNAKE_CASE)
- **Variáveis**: `diagramXml`, `flowId` (camelCase)

#### Arquivos

- **PHP Classes**: `nome.class.php`
- **AJAX Endpoints**: `nome.php`
- **CSS**: `nome.css`
- **JavaScript**: `nome.js`

---

## Backend (PHP)

### Classes Principais

#### 1. PluginFlowbpmnFlow (inc/flow.class.php)

**Responsabilidade**: Gerenciamento principal de fluxos BPMN

**Herança**: `CommonDBTM` (GLPI Core)

**Métodos Principais**:

```php
class PluginFlowbpmnFlow extends CommonDBTM {
    
    // GLPI Standard Methods
    public static function getTypeName($nb = 0);
    public function prepareInputForAdd($input);
    public function prepareInputForUpdate($input);
    public function post_addItem();
    public function post_updateItem($history = true);
    public function post_deleteItem();
    public function getSearchOptions();
    
    // Tab Integration
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0);
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0);
    
    // Custom Methods
    public static function showForItem(CommonDBTM $item);
    public static function loadBpmnEditor($existingFlow = null);
    public static function getForItem($itemtype, $items_id);
    public static function saveFlow($itemtype, $items_id, $bpmn_xml, $svg_content, $name = '', $png_data = '');
    public static function savePNGAsDocument($itemtype, $items_id, $png_data, $name);
    public static function deleteFlow($id);
    public static function getHistory($itemtype, $items_id);
}
```

**Exemplo de Uso**:

```php
// Obter diagrama de um ticket
$flow = PluginFlowbpmnFlow::getForItem('Ticket', 123);

// Salvar novo diagrama
PluginFlowbpmnFlow::saveFlow(
    'Ticket',
    123,
    $bpmn_xml,
    $svg_content,
    'Processo de Atendimento',
    $png_data
);
```

#### 2. PluginFlowbpmnVersion (inc/version.class.php)

**Responsabilidade**: Controle de versão de diagramas

**Métodos Principais**:

```php
class PluginFlowbpmnVersion extends CommonDBTM {
    public static function getVersionsForFlow($flow_id);
    public static function restoreVersion($flow_id, $version_id);
    public static function deleteVersion($version_id);
    public static function cleanupOldVersions($flow_id, $max_versions = 10);
}
```

#### 3. PluginFlowbpmnTemplate (inc/template.class.php)

**Responsabilidade**: Gerenciamento de templates

**Métodos Principais**:

```php
class PluginFlowbpmnTemplate extends CommonDBTM {
    public static function getTemplates($search = '');
    public static function saveTemplate($name, $bpmn_xml, $svg_content);
    public static function deleteTemplate($id);
    public static function canDelete($id, $user_id);
}
```

#### 4. PluginFlowbpmnProfile (inc/profile.class.php)

**Responsabilidade**: Gerenciamento de permissões

**Métodos Principais**:

```php
class PluginFlowbpmnProfile extends CommonDBTM {
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0);
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0);
    public static function updateProfileRight(Profile $item);
    public static function canView($itemtype, $profile_id = null);
    public static function canEdit($itemtype, $profile_id = null);
    public static function canDelete($itemtype, $profile_id = null);
    public static function canRestore($itemtype, $profile_id = null);
}
```

### AJAX Endpoints

#### Estrutura Padrão

Todos os endpoints seguem este padrão:

```php
<?php
// 1. Bootstrap GLPI
define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
include(GLPI_ROOT . "/inc/includes.php");

// 2. Security: Check authentication
Session::checkLoginUser();

// 3. Security: Validate CSRF token
Session::checkCSRF($_POST);

// 4. Get parameters
$action = $_POST['action'] ?? '';
$itemtype = $_POST['itemtype'] ?? '';
$items_id = (int)($_POST['items_id'] ?? 0);

// 5. Validate permissions
if (!PluginFlowbpmnProfile::canEdit($itemtype)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sem permissão']);
    exit;
}

// 6. Process action
switch ($action) {
    case 'save':
        // Process save
        break;
    case 'delete':
        // Process delete
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ação inválida']);
        exit;
}

// 7. Return JSON response
header('Content-Type: application/json');
echo json_encode(['success' => true, 'data' => $result]);
```

#### Principais Endpoints

| Endpoint | Ações | Descrição |
|----------|-------|-----------|
| `flow.php` | save, delete_version, restore | Handler principal |
| `template.php` | save, load, delete, list | Operações de templates |
| `bpmn_versions.php` | list | Listagem de versões |
| `bpmn_restore.php` | restore | Restauração de versões |
| `load_diagram_from_item.php` | load | Carrega diagrama de item |
| `list_items_with_diagrams.php` | list | Lista itens com diagramas |
| `import_items.php` | import | Importa diagrama |

### Hooks do GLPI

#### setup.php

```php
function plugin_init_flowbpmn() {
    global $PLUGIN_HOOKS;
    
    // CSRF compliant
    $PLUGIN_HOOKS['csrf_compliant']['flowbpmn'] = true;
    
    // Register classes
    Plugin::registerClass('PluginFlowbpmnFlow', [
        'addtabon' => ['Ticket', 'Problem', 'Change']
    ]);
    
    Plugin::registerClass('PluginFlowbpmnProfile', [
        'addtabon' => ['Profile']
    ]);
    
    // Add CSS/JS
    $PLUGIN_HOOKS['add_css']['flowbpmn'] = ['css/flowbpmn.css'];
    $PLUGIN_HOOKS['add_javascript']['flowbpmn'] = ['js/flowbpmn.js'];
    
    // Hook for profile updates
    $PLUGIN_HOOKS['item_update']['flowbpmn'] = 'PluginFlowbpmnProfile::updateProfileRight';
}
```

### Padrões de Código

#### PSR-12 Compliance

```php
<?php
declare(strict_types=1);

namespace PluginFlowbpmn;

class ExampleClass
{
    private string $property;
    
    public function __construct(string $property)
    {
        $this->property = $property;
    }
    
    public function getProperty(): string
    {
        return $this->property;
    }
}
```

#### PHPDoc

```php
/**
 * Save BPMN flow
 *
 * @param string $itemtype Item type (Ticket, Problem, Change)
 * @param int $items_id Item ID
 * @param string $bpmn_xml BPMN XML content
 * @param string $svg_content SVG preview
 * @param string $name Diagram name
 * @param string $png_data Base64 PNG data
 * @return int|false Flow ID or false on failure
 */
public static function saveFlow($itemtype, $items_id, $bpmn_xml, $svg_content, $name = '', $png_data = '')
{
    // Implementation
}
```

---

## Frontend (JavaScript)

### Classe Principal: BpmnFlowEditor

**Arquivo**: `js/flowbpmn.js` (2094 linhas)

**Responsabilidade**: Gerenciamento completo do editor BPMN

#### Estrutura da Classe

```javascript
class BpmnFlowEditor {
    constructor(options) {
        this.itemtype = options.itemtype;
        this.items_id = options.items_id;
        this.existingXml = options.existingXml || null;
        this.flowId = options.flowId || null;
        this.modeler = null;
        this.searchDebounceTimers = {};
        
        // Expose globally for modal events
        window.BpmnFlowEditor_instance = this;
        
        this.init();
    }
    
    // Initialization
    init() { }
    loadCSS() { }
    loadBpmnJS() { }
    initModeler() { }
    loadDiagram(xml) { }
    createNewDiagram() { }
    bindEvents() { }
    
    // Save/Export
    saveDiagram() { }
    exportBPMN() { }
    exportSVG() { }
    exportPNG() { }
    exportPDF() { }
    
    // Versions
    showVersionsModal() { }
    restoreVersion(flowId, versionId) { }
    deleteVersion(versionId) { }
    
    // Templates
    showTemplatesModal() { }
    saveAsTemplate() { }
    loadTemplate(templateId) { }
    deleteTemplate(templateId) { }
    
    // Import
    showImportModal() { }
    loadImportGallery(type, page, search) { }
    importFromItem(sourceItemtype, sourceItemsId) { }
    
    // UI Helpers
    showLoading() { }
    hideLoading() { }
    showSuccess(message) { }
    showError(message) { }
    
    // Security
    escapeHtml(text) { }
    getCSRFToken() { }
    
    // I18n
    _t(key) { }
}
```

#### Inicialização

```javascript
// Chamado por PluginFlowbpmnFlow::showForItem()
window.addEventListener('load', function() {
    if (typeof FLOWBPMN_CONFIG !== 'undefined') {
        new BpmnFlowEditor({
            itemtype: FLOWBPMN_CONFIG.itemtype,
            items_id: FLOWBPMN_CONFIG.items_id,
            existingXml: FLOWBPMN_CONFIG.existingXml,
            flowId: FLOWBPMN_CONFIG.flowId
        });
    }
});
```

#### Integração com BPMN.io

```javascript
initModeler() {
    this.modeler = new BpmnJS({
        container: '#flowbpmn-canvas',
        keyboard: {
            bindTo: document
        },
        height: 800
    });
    
    // Load existing or create new
    if (this.existingXml) {
        this.loadDiagram(this.existingXml);
    } else {
        this.createNewDiagram();
    }
}
```

#### AJAX Requests

```javascript
saveDiagram() {
    this.showLoading();
    
    // Export BPMN XML
    this.modeler.saveXML({ format: true }, (err, xml) => {
        if (err) {
            this.showError('Erro ao exportar diagrama');
            return;
        }
        
        // Export SVG
        this.modeler.saveSVG((err, svg) => {
            if (err) {
                this.showError('Erro ao exportar SVG');
                return;
            }
            
            // Generate PNG
            this.generatePNG(svg).then(pngData => {
                // AJAX POST
                fetch('/plugins/flowbpmn/ajax/flow.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': this.getCSRFToken()
                    },
                    body: JSON.stringify({
                        action: 'save',
                        itemtype: this.itemtype,
                        items_id: this.items_id,
                        bpmn_xml: xml,
                        svg_content: svg,
                        png_data: pngData
                    })
                })
                .then(response => response.json())
                .then(data => {
                    this.hideLoading();
                    if (data.success) {
                        this.showSuccess(data.message);
                        this.flowId = data.flow_id;
                    } else {
                        this.showError(data.message);
                    }
                })
                .catch(error => {
                    this.hideLoading();
                    this.showError('Erro de conexão');
                });
            });
        });
    });
}
```

### Modais

#### Sistema de Modais

Todos os modais seguem o padrão Bootstrap 5:

```javascript
createTemplatesModalHTML(templates) {
    return `
        <div class="modal fade" id="flowbpmn-templates-modal" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Galeria de Modelos</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Search bar -->
                        <input type="text" class="form-control mb-3" placeholder="Buscar...">
                        
                        <!-- Grid -->
                        <div class="flowbpmn-versions-grid">
                            ${templates.map(t => this.createTemplateCard(t)).join('')}
                        </div>
                        
                        <!-- Pagination -->
                        ${this.createPaginationHTML('templates', totalPages, templates)}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
}
```

### Event Handling

```javascript
bindEvents() {
    // Save button
    document.getElementById('flowbpmn-save-btn')?.addEventListener('click', () => {
        this.saveDiagram();
    });
    
    // Export button
    document.getElementById('flowbpmn-export-btn')?.addEventListener('click', () => {
        this.showExportModal();
    });
    
    // Templates button
    document.getElementById('flowbpmn-templates-btn')?.addEventListener('click', () => {
        this.showTemplatesModal();
    });
    
    // Versions button
    document.getElementById('flowbpmn-versions-btn')?.addEventListener('click', () => {
        this.showVersionsModal();
    });
    
    // Import button
    document.getElementById('flowbpmn-import-btn')?.addEventListener('click', () => {
        this.showImportModal();
    });
}
```

---

## Banco de Dados

### Schema Completo

Veja [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) para detalhes completos.

### Relacionamentos

```sql
-- Flows → Versions (1:N, CASCADE DELETE)
ALTER TABLE glpi_plugin_flowbpmn_versions
ADD CONSTRAINT glpi_plugin_flowbpmn_versions_ibfk_1
FOREIGN KEY (plugin_flowbpmn_flows_id)
REFERENCES glpi_plugin_flowbpmn_flows (id)
ON DELETE CASCADE;

-- Profiles → GLPI Profiles (1:1, CASCADE DELETE)
ALTER TABLE glpi_plugin_flowbpmn_profiles
ADD CONSTRAINT glpi_plugin_flowbpmn_profiles_ibfk_1
FOREIGN KEY (profiles_id)
REFERENCES glpi_profiles (id)
ON DELETE CASCADE;
```

### Queries Úteis

```sql
-- Obter diagrama de um ticket
SELECT * FROM glpi_plugin_flowbpmn_flows
WHERE itemtype = 'Ticket' AND items_id = 123 AND is_deleted = 0;

-- Obter versões de um diagrama
SELECT * FROM glpi_plugin_flowbpmn_versions
WHERE plugin_flowbpmn_flows_id = 456
ORDER BY version_number DESC;

-- Obter templates públicos
SELECT * FROM glpi_plugin_flowbpmn_templates
WHERE is_public = 1 AND is_active = 1
ORDER BY name;

-- Verificar permissões de um perfil
SELECT * FROM glpi_plugin_flowbpmn_profiles
WHERE profiles_id = 3;
```

---

## Internacionalização (i18n)

### Estrutura de Traduções

Arquivos em `locales/`:

```php
// locales/pt_BR.php
<?php
$LANG['plugin_flowbpmn'] = [
    'menu' => 'FlowBPMN',
    'save' => 'Salvar',
    'export' => 'Exportar',
    'templates' => 'Templates',
    'versions' => 'Versões',
    'import' => 'Importar',
    // ... mais traduções
];
```

### Uso no PHP

```php
// Via helper
echo PluginFlowbpmnFlow::_t('save');

// Via GLPI
echo __('save', 'flowbpmn');
```

### Uso no JavaScript

```javascript
// Injetado via PHP
window.FLOWBPMN_I18N = <?php echo json_encode($LANG['plugin_flowbpmn']); ?>;

// Uso
this._t('save');  // Retorna tradução
```

### Adicionando Novo Idioma

1. Criar arquivo `locales/fr_FR.php`
2. Copiar estrutura de `pt_BR.php`
3. Traduzir todas as chaves
4. Testar mudando idioma no GLPI

---

## Testes

### Estratégia de Testes

| Tipo | Ferramenta | Status |
|------|------------|--------|
| **Unitários** | PHPUnit | 🚧 Planejado |
| **Integração** | PHPUnit | 🚧 Planejado |
| **E2E** | Selenium | 🚧 Planejado |
| **Manuais** | Checklist | ✅ Atual |

### Testes Manuais

#### Checklist de Funcionalidades

- [ ] Criar diagrama em Ticket
- [ ] Criar diagrama em Problem
- [ ] Criar diagrama em Change
- [ ] Salvar diagrama
- [ ] Editar diagrama existente
- [ ] Criar versão
- [ ] Restaurar versão
- [ ] Deletar versão
- [ ] Salvar como template
- [ ] Carregar template
- [ ] Deletar template
- [ ] Importar diagrama
- [ ] Exportar BPMN
- [ ] Exportar SVG
- [ ] Exportar PNG
- [ ] Exportar PDF
- [ ] Verificar permissões (View, Edit, Delete, Restore)
- [ ] Testar em diferentes idiomas (PT, EN, ES)
- [ ] Testar em dark mode
- [ ] Testar em mobile

### Testes de Regressão

Após cada mudança, executar:

```bash
# 1. Verificar instalação
php bin/console glpi:plugin:list | grep flowbpmn

# 2. Verificar tabelas
mysql -e "SHOW TABLES LIKE 'glpi_plugin_flowbpmn%';"

# 3. Verificar permissões de arquivos
ls -la /var/www/html/glpi/plugins/flowbpmn/

# 4. Verificar logs
tail -f /var/www/html/glpi/files/_log/php-errors.log
```

---

## Contribuindo

### Processo de Contribuição

1. **Fork** o repositório
2. **Clone** seu fork
3. **Crie** uma branch para sua feature
4. **Desenvolva** e **teste**
5. **Commit** com mensagens descritivas
6. **Push** para seu fork
7. **Abra** um Pull Request

### Commit Messages

Seguir [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Tipos**:
- `feat`: Nova funcionalidade
- `fix`: Correção de bug
- `docs`: Documentação
- `style`: Formatação
- `refactor`: Refatoração
- `test`: Testes
- `chore`: Manutenção

**Exemplos**:

```bash
feat(templates): Add template categories

- Add category field to templates table
- Update UI to show categories
- Add filter by category

Closes #123
```

```bash
fix(save): Fix PNG attachment on large diagrams

- Increase max_allowed_packet handling
- Add chunked upload for large PNGs
- Add error handling for upload failures

Fixes #456
```

### Code Review

Todos os PRs passam por code review:

- ✅ Código segue padrões PSR-12 (PHP) e ES6+ (JS)
- ✅ Código está documentado (PHPDoc, JSDoc)
- ✅ Testes passam (quando implementados)
- ✅ Sem warnings ou erros
- ✅ Performance aceitável
- ✅ Segurança verificada

### Recursos Adicionais

- [GLPI Developer Documentation](https://glpi-developer-documentation.readthedocs.io/)
- [BPMN.io Documentation](https://bpmn.io/toolkit/bpmn-js/)
- [Bootstrap 5 Documentation](https://getbootstrap.com/docs/5.0/)
- [PHP PSR-12](https://www.php-fig.org/psr/psr-12/)

---

**Boas contribuições!** 💻🚀

Para dúvidas, abra uma [Issue](https://github.com/diegojucah/FlowBPMN/issues) ou [Discussion](https://github.com/diegojucah/FlowBPMN/discussions).
