# flowBPMN v2.1.0 - BPMN Editor Plugin for GLPI 10.x/11.x

[![License: GPLv3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![GLPI Version](https://img.shields.io/badge/GLPI-11.0+-orange.svg)](https://glpi-project.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.1+-purple.svg)](https://php.net/)

Professional BPMN (Business Process Model and Notation) editor plugin for GLPI 11, allowing creation, editing, and management of BPMN diagrams directly within Tickets, Problems, and Changes.

**Developed by [KactuX](https://github.com/diegojucah)** - Contributing to the GLPI open-source community.

---

## ✨ Features

- **📊 Full BPMN 2.0 Editor** - Complete visual editor powered by [bpmn.io](https://bpmn.io/)
- **🎯 Integrated with GLPI** - Seamless tabs in Tickets, Problems, and Changes
- **📝 Version Control** - Complete history with restore capabilities
- **🔒 Granular Permissions** - Per-profile rights management (view, edit, delete, restore)
- **💾 Auto-save** - Automatic diagram attachment to items
- **📤 Export Options** - Export to BPMN XML, SVG, and PNG formats
- **🎨 Modern UI** - Fully integrated with GLPI 11 design system
- **🌙 Dark Mode** - Full support for GLPI dark theme
- **📱 Responsive** - Works on desktop and mobile devices

---

## 📋 Requirements

- **GLPI**: 11.0.0 or higher
- **PHP**: 8.1 or higher
- **Web Browser**: Modern browser with JavaScript enabled

---

## 🚀 Installation

### Method 1: Manual Installation

1. **Download the plugin**
   ```bash
   cd /var/www/html/glpi/plugins
   git clone https://github.com/diegojucah/pluginBPMN.git flowbpmn
   ```

2. **Set permissions**
   ```bash
   chown -R www-data:www-data flowbpmn
   chmod -R 755 flowbpmn
   ```

3. **Install via GLPI Interface**
   - Go to: **Setup → Plugins**
   - Find **"BPMN Flow"**
   - Click **Install**
   - Click **Enable**

### Method 2: GLPI Marketplace (Coming Soon)

Once published to GLPI Marketplace, install directly from **Setup → Plugins → Marketplace**.

---

## ⚙️ Configuration

### 1. Global Settings

Navigate to: **Setup → General → BPMN Flow**

Configure:
- ✅ Auto-attach diagrams to items
- 🔢 Maximum versions per flow (default: 10)
- 📤 Enable/disable export formats (BPMN, SVG, PNG)

### 2. Profile Permissions

Navigate to: **Setup → Profiles → [Profile Name] → BPMN Flow**

Set permissions for each item type:

| Permission | Description |
|------------|-------------|
| **View** | Can see BPMN diagrams |
| **Edit** | Can create and modify diagrams |
| **Delete** | Can delete diagrams |
| **Restore** | Can restore previous versions |

---

## 🔒 Segurança

### Autenticação e Auditoria

O plugin FlowBPMN implementa autenticação nativa do GLPI e rastreamento completo de auditoria:

- ✅ **Autenticação Obrigatória**: Todos os endpoints AJAX verificam autenticação via `Session::checkLoginUser()`
- ✅ **Rastreamento de Usuário**: Cada ação (criar, editar, restaurar, deletar) registra o usuário correto via `Session::getLoginUserID()`
- ✅ **Validação de Permissões**: Todas as operações verificam permissões do perfil do usuário
- ✅ **Auditoria Completa**: Histórico de versões mantém registro de quem fez cada modificação

### Correções de Segurança (v2.1.0)

**Versão 2.1.0** corrigiu um problema crítico onde todos os registros eram atribuídos ao usuário ID 2:

- ❌ **Antes**: `$user_id = 2` hardcoded
- ✅ **Depois**: `$user_id = Session::getLoginUserID()` nativo do GLPI

### Boas Práticas

1. **Configure Permissões Adequadas**: Não dê permissão de "Edit" para todos os perfis
2. **Revise Logs Regularmente**: Verifique quem está criando/modificando diagramas
3. **Mantenha Atualizado**: Sempre use a versão mais recente do plugin

---

## 📖 Usage

### Creating a BPMN Diagram

1. Open a **Ticket**, **Problem**, or **Change**
2. Click the **"BPMN Flow"** tab
3. Use the visual editor to create your diagram:
   - Drag elements from the palette (left sidebar)
   - Connect elements using arrows
   - Edit properties by clicking elements
4. Click **"Save"** to store the diagram

### Managing Versions

- Click **"Versions"** button to view history
- Select a previous version to preview
- Click **"Restore"** to revert to that version
- All changes are tracked with user and timestamp

### Exporting Diagrams

- Click **"Export"** button
- Choose format:
  - **BPMN XML** - Standard BPMN 2.0 format
  - **SVG** - Vector graphics
  - **PNG** - Raster image

---

## 🧪 Testing

### Testing Installation

1. **Verify plugin is enabled**
   ```bash
   cd /var/www/html/glpi
   php bin/console glpi:plugin:list
   ```
   You should see `flowbpmn` with status `ENABLED`

2. **Check database tables**
   ```sql
   SHOW TABLES LIKE 'glpi_plugin_flowbpmn%';
   ```
   Should return 4 tables:
   - `glpi_plugin_flowbpmn_configs`
   - `glpi_plugin_flowbpmn_flows`
   - `glpi_plugin_flowbpmn_profiles`
   - `glpi_plugin_flowbpmn_versions`

### Testing Basic Functionality

1. **Create a Test Ticket**
   - Navigate to: **Assistance → Tickets → Create Ticket**
   - Fill basic information and save

2. **Test BPMN Tab**
   - Open the created ticket
   - Click **"BPMN Flow"** tab
   - Verify editor loads correctly

3. **Create a Simple Diagram**
   - Drag a **Start Event** from palette
   - Add a **Task** and **End Event**
   - Connect them with arrows
   - Click **Save**
   - Verify success message appears

4. **Test Version Control**
   - Modify the diagram
   - Save again
   - Click **"Versions"** button
   - Verify both versions are listed

### Testing Permissions

1. **Create a Test Profile**
   - Setup → Profiles → Add
   - Name it "BPMN Tester"

2. **Configure Limited Rights**
   - BPMN Flow tab → Set "View" only for Tickets
   - Assign a test user to this profile

3. **Login as Test User**
   - Open a ticket
   - Verify BPMN tab shows read-only mode
   - Verify no "Save" button appears

### Testing Export

1. Open a diagram
2. Click **"Export"** button
3. Try each format:
   - BPMN XML should download `.bpmn` file
   - SVG should download vector image
   - PNG should download raster image
4. Open exported files to verify integrity

---

## 🛠️ Troubleshooting

### Editor doesn't load

**Problem**: BPMN editor shows loading spinner indefinitely

**Solutions**:
1. Check browser console for errors (F12)
2. Verify internet connection (CDN access required)
3. Check if JavaScript is enabled
4. Try clearing browser cache

### Cannot save diagrams

**Problem**: Save button doesn't work or shows error

**Solutions**:
1. Check user has "Edit" permission for the item type
2. Verify PHP error logs: `/var/log/apache2/error.log`
3. Check database connectivity
4. Verify `glpi_plugin_flowbpmn_flows` table exists

### Permissions not working

**Problem**: Users see tabs they shouldn't

**Solutions**:
1. Rebuild GLPI rights cache:
   ```bash
   php bin/console glpi:database:check
   ```
2. Re-save profile permissions
3. Have user logout and login again

### Missing tab in Tickets/Problems/Changes

**Problem**: BPMN Flow tab doesn't appear

**Solutions**:
1. Verify plugin is enabled
2. Check profile has at least "View" permission
3. Clear GLPI cache:
   ```bash
   php bin/console cache:clear
   ```

---

## 🗂️ File Structure

```
flowBPMN/
├── ajax/
│   └── flow.php               # AJAX endpoints
├── css/
│   ├── bpmn.css               # Legacy CSS (unused)
│   └── flowbpmn.css          # Main stylesheet
├── front/
│   ├── bpmn-js/              # bpmn-js assets (legacy)
│   ├── config.form.php       # Configuration page
│   └── config.form.old.php   # Backup
├── inc/
│   ├── config.class.php      # Configuration management
│   ├── flow.class.php        # Main flow class
│   ├── profile.class.php     # Permissions management
│   └── version.class.php     # Version control
├── js/
│   └── flowbpmn.js           # Main JavaScript with bpmn-js integration
├── hook.php                   # Plugin hooks (empty - using setup.php)
├── setup.php                  # Plugin setup and installation
├── plugin.xml                 # Plugin metadata
├── README.md                  # This file
├── LICENSE                    # GPLv3 License
└── .gitignore                # Git ignore rules
```

---

## 🔧 Development

### Local Development Setup

1. **Clone repository**
   ```bash
   git clone https://github.com/diegojucah/pluginBPMN.git flowbpmn
   cd flowbpmn
   ```

2. **Symlink to GLPI plugins directory**
   ```bash
   ln -s $(pwd) /var/www/html/glpi/plugins/flowbpmn
   ```

3. **Install in GLPI**
   - Follow installation steps above

### Code Standards

- **PHP**: PSR-12 coding standards
- **JavaScript**: ES6+ standards
- **CSS**: BEM methodology
- **Comments**: PHPDoc and JSDoc

### Contributing

We welcome contributions! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📜 License

This project is licensed under the **GNU General Public License v3.0** - see the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

- **bpmn.io** - For the excellent BPMN editor library
- **GLPI Community** - For the amazing ITIL asset management system
- **KactuX Team** - For development and open-source contribution

---

## 📞 Support

- **Issues**: [GitHub Issues](https://github.com/diegojucah/pluginBPMN/issues)
- **Documentation**: [GitHub Wiki](https://github.com/diegojucah/pluginBPMN/wiki)
- **GLPI Forum**: [GLPI Plugins Forum](https://forum.glpi-project.org/)

---

## 🗺️ Roadmap

- [ ] Add BPMN validation and error checking
- [ ] Implement collaboration features (comments, annotations)
- [ ] Add diagram templates library
- [ ] Create simulation/execution engine
- [ ] Add PDF export with documentation
- [ ] Implement diagram comparison (diff)
- [ ] Add REST API endpoints
- [ ] Create mobile app companion

---

**Made with ❤️ by KactuX for the GLPI Community**

