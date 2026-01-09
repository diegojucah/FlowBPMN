# FlowBPMN v1.0.0 - BPMN Editor Plugin for GLPI

[![License: GPLv3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![GLPI Version](https://img.shields.io/badge/GLPI-11.0+-orange.svg)](https://glpi-project.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4+-purple.svg)](https://php.net/)
[![Languages](https://img.shields.io/badge/Languages-EN%20|%20PT%20|%20ES-green.svg)](https://github.com/diegojucah/FlowBPMN)

Professional BPMN (Business Process Model and Notation) editor plugin for GLPI 10, enabling creation, editing, and management of BPMN diagrams directly within Tickets, Problems, and Changes.

**Developed by [KactuX](https://github.com/diegojucah)** - Contributing to the GLPI open-source community.

---

## ✨ Features

### Core Features (v1.0.0)

- **📊 Full BPMN 2.0 Editor** - Complete visual editor powered by [bpmn.io](https://bpmn.io/) v18.6.1 (local, no CDN)
- **🎯 GLPI Integration** - Seamless tabs in Tickets, Problems, and Changes
- **📝 Version Control** - Complete history with restore and delete capabilities
- **🗂️ Template System** - Save, load, and share BPMN diagrams as reusable templates
- **🔍 Search & Filter** - Quick template search by name
- **🔒 Granular Permissions** - Per-profile rights management (view, edit, delete, restore)
- **💾 Auto-save** - Automatic PNG attachment to items
- **📤 Export Options** - Export to BPMN XML, SVG, PNG, and PDF (via browser print)
- **🎨 Modern UI** - Fully integrated with GLPI 10 design system (Bootstrap 4)
- **🌙 Dark Mode** - Full support for GLPI dark theme
- **📱 Responsive** - Works on desktop and mobile devices
- **🌍 Multilingual** - Full support for English, Portuguese (BR), and Spanish

### Security Features

- **CSRF Protection**: All forms and AJAX requests include CSRF token validation
- **SQL Injection Prevention**: All database queries use prepared statements
- **XSS Protection**: All user input is properly escaped before display
- **Session Management**: Leverages GLPI's robust session handling
- **Access Control**: Fine-grained permissions per profile and item type
- **Audit Trail**: Complete version history with user tracking

---

## 📋 Requirements

- **GLPI**: 11.0.0 or higher
- **PHP**: 7.4 or higher (8.1+ recommended)
- **Web Browser**: Modern browser with JavaScript enabled (Chrome, Firefox, Edge, Safari)
- **Database**: MySQL 5.7+ or MariaDB 10.3+

---

## 🚀 Installation

### Method 1: Manual Installation

1. **Download the plugin**
   ```bash
   cd /var/www/html/glpi/plugins
   git clone https://github.com/diegojucah/FlowBPMN.git flowbpmn
   ```

2. **Set permissions**
   ```bash
   chown -R www-data:www-data flowbpmn
   chmod -R 755 flowbpmn
   ```

3. **Install via GLPI Interface**
   - Go to: **Setup → Plugins**
   - Find **"FlowBPMN"**
   - Click **Install**
   - Click **Enable**

### Method 2: GLPI Marketplace (Coming Soon)

Once published to GLPI Marketplace, install directly from **Setup → Plugins → Marketplace**.

---

## ⚙️ Configuration

### Profile Permissions

Navigate to: **Setup → Profiles → [Profile Name] → FlowBPMN**

Set permissions for each item type (Tickets, Problems, Changes):

| Permission | Description |
|------------|-------------|
| **View** | Can see BPMN diagrams |
| **Edit** | Can create and modify diagrams |
| **Delete** | Can delete diagrams and versions |
| **Restore** | Can restore previous versions |

### Default Settings

The plugin comes with sensible defaults:
- ✅ Auto-attach PNG diagrams to items (enabled)
- 📊 Maximum 10 versions per flow (automatic cleanup)
- 📤 All export formats enabled (BPMN, SVG, PNG, PDF)
- 🎨 Canvas height: 800px
- 📏 Grid enabled (10px)

> **Note**: Configuration UI is planned for v1.1.0. Current settings are defined in the database and can be modified directly if needed.

---

## 📖 Usage

### Creating a BPMN Diagram

1. Open a **Ticket**, **Problem**, or **Change**
2. Click the **"FlowBPMN"** tab
3. Use the visual editor to create your diagram:
   - Drag elements from the palette (left sidebar)
   - Connect elements using arrows
   - Edit properties by clicking elements
   - Use the mini-map (bottom right) for navigation
4. Click **"Save"** to store the diagram
   - Diagram is saved as BPMN XML
   - PNG preview is automatically attached to the item
   - Version is created automatically

### Managing Versions

- Click **"Versions"** button to view history
- **Preview**: Click "View" to see full-size diagram
- **Restore**: Click "Restore" to revert to a previous version
  - Current version is saved before restore
  - Confirmation required
- **Delete**: Remove old versions to save space
- All changes tracked with user and timestamp

### Using Templates

#### Save as Template
1. Create or edit a diagram
2. Click dropdown on **Save** button
3. Select **"Save as Template"**
4. Enter template name
5. Template is saved with thumbnail

#### Load Template
1. Click **"Templates"** button
2. Browse available templates
3. Use search bar to filter by name
4. Click **"Apply"** to load template
5. Confirmation required (overwrites current diagram)

#### Delete Template
- Click **"Delete"** on template card
- Only available for templates you created or if you have global delete permission

### Exporting Diagrams

Click **"Export"** button and choose format:

| Format | Description | Use Case |
|--------|-------------|----------|
| **BPMN XML** | Standard BPMN 2.0 format | Interoperability with other BPMN tools |
| **SVG** | Vector graphics | Scalable images for documentation |
| **PNG** | Raster image | Presentations and reports |
| **PDF** | Print-ready document | Browser print dialog (Ctrl+P) |

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
   Should return 5 tables:
   - `glpi_plugin_flowbpmn_configs`
   - `glpi_plugin_flowbpmn_flows`
   - `glpi_plugin_flowbpmn_profiles`
   - `glpi_plugin_flowbpmn_templates`
   - `glpi_plugin_flowbpmn_versions`

### Testing Basic Functionality

1. **Create a Test Ticket**
   - Navigate to: **Assistance → Tickets → Create Ticket**
   - Fill basic information and save

2. **Test BPMN Tab**
   - Open the created ticket
   - Click **"FlowBPMN"** tab
   - Verify editor loads correctly
   - Check that toolbar buttons appear

3. **Create a Simple Diagram**
   - Drag a **Start Event** from palette
   - Add a **Task** and **End Event**
   - Connect them with arrows
   - Click **Save**
   - Verify success message appears
   - Check that PNG is attached to ticket

4. **Test Version Control**
   - Modify the diagram
   - Save again
   - Click **"Versions"** button
   - Verify both versions are listed with thumbnails
   - Test restore functionality

5. **Test Templates**
   - Save diagram as template
   - Create new ticket
   - Load template from gallery
   - Verify diagram loads correctly

### Testing Permissions

1. **Create a Test Profile**
   - Setup → Profiles → Add
   - Name it "BPMN Viewer"

2. **Configure Limited Rights**
   - FlowBPMN tab → Set "View" only for Tickets
   - Assign a test user to this profile

3. **Login as Test User**
   - Open a ticket
   - Verify BPMN tab shows read-only mode
   - Verify no "Save" or "Templates" buttons appear

### Testing Internationalization

1. **Change GLPI Language**
   - User Preferences → Language → Select Portuguese/Spanish
   - Refresh page (Ctrl+F5)

2. **Verify Translation**
   - Open FlowBPMN tab
   - Click "Versions" or "Templates" buttons
   - Verify modals are in selected language
   - Check button labels and messages

---

## 🛠️ Troubleshooting

### Editor doesn't load

**Problem**: BPMN editor shows loading spinner indefinitely

**Solutions**:
1. Check browser console for errors (F12)
2. Verify `/plugins/flowbpmn/lib/bpmn-js/` directory exists
3. Check file permissions (755 for directories, 644 for files)
4. Clear browser cache (Ctrl+F5)
5. Check PHP error logs for backend issues

### Cannot save diagrams

**Problem**: Save button doesn't work or shows error

**Solutions**:
1. Check user has "Edit" permission for the item type
2. Verify PHP error logs: `/var/log/apache2/error.log`
3. Check database connectivity
4. Verify `glpi_plugin_flowbpmn_flows` table exists
5. Ensure sufficient disk space for attachments
6. Check `max_allowed_packet` in MySQL (for large diagrams)

### Permissions not working

**Problem**: Users see tabs they shouldn't

**Solutions**:
1. Rebuild GLPI rights cache:
   ```bash
   php bin/console glpi:database:check
   ```
2. Re-save profile permissions in GLPI interface
3. Have user logout and login again
4. Clear browser cache

### Missing tab in Tickets/Problems/Changes

**Problem**: FlowBPMN tab doesn't appear

**Solutions**:
1. Verify plugin is enabled
2. Check profile has at least "View" permission
3. Clear GLPI cache:
   ```bash
   php bin/console cache:clear
   ```
4. Check browser console for JavaScript errors
5. Verify item type is supported (Ticket, Problem, or Change)

### Templates not loading

**Problem**: Template gallery is empty or shows errors

**Solutions**:
1. Verify `glpi_plugin_flowbpmn_templates` table exists
2. Check user has permission to view templates
3. Verify SVG content is being saved correctly
4. Check PHP `max_allowed_packet` setting for large diagrams
5. Clear GLPI cache

### Language not changing

**Problem**: Interface remains in English despite language change

**Solutions**:
1. Clear GLPI cache: `php bin/console cache:clear`
2. Hard refresh browser (Ctrl+F5)
3. Verify locale files exist in `locales/` directory
4. Check `$_SESSION['glpilanguage']` is set correctly
5. Verify translations exist in locale files

### Version limit not enforced

**Problem**: More than 10 versions are being kept

**Solutions**:
1. Version cleanup runs on new save operations
2. Check `glpi_plugin_flowbpmn_configs` table for `max_versions_per_item` value
3. Manually trigger cleanup by saving a diagram
4. Check PHP error logs for cleanup errors

---

## 🗂️ File Structure

```
flowbpmn/
├── ajax/
│   ├── bpmn_restore.php       # Version restore endpoint
│   ├── bpmn_save.php          # Diagram save endpoint (legacy)
│   ├── bpmn_version.php       # Single version endpoint
│   ├── bpmn_versions.php      # Version list endpoint
│   ├── db_update.php          # Database update utility
│   ├── flow.php               # Main AJAX handler (save, restore, delete)
│   └── template.php           # Template AJAX handler
├── css/
│   └── flowbpmn.css           # Custom styles (minimal, uses inline CSS)
├── front/
│   ├── config.form.php        # Configuration page (UI not implemented)
│   └── config.form.old.php    # Backup file
├── inc/
│   ├── bpmntask.class.php     # BPMN task class (version cleanup)
│   ├── flow.class.php         # Main flow class (CRUD, tab display)
│   ├── helper.class.php       # Helper utilities
│   ├── plugin.class.php       # Legacy plugin class
│   ├── profile.class.php      # Permissions management
│   ├── template.class.php     # Template management
│   └── version.class.php      # Version control
├── install/
│   └── mysql/                 # Database schema (not used, schema in setup.php)
├── js/
│   └── flowbpmn.js            # Main JavaScript (1272 lines, full editor logic)
├── lib/
│   └── bpmn-js/               # BPMN.io library v18.6.1 (local, 4 files)
│       ├── bpmn-embedded.css
│       ├── bpmn-js.css
│       ├── bpmn-modeler.development.js
│       └── diagram-js.css
├── locales/
│   ├── en_GB.php              # English translations
│   ├── es_ES.php              # Spanish translations
│   └── pt_BR.php              # Portuguese translations
├── templates/
│   ├── approval.bpmn          # Approval process template
│   ├── parallel.bpmn          # Parallel tasks template
│   └── simple.bpmn            # Simple workflow template
├── CHANGELOG.md               # Version history
├── LICENSE                    # GPLv3 License
├── README.md                  # This file
├── SECURITY.md                # Security policy
├── check_db.php               # Database check utility
├── hook.php                   # Plugin hooks
├── plugin.xml                 # Plugin metadata
├── setup.php                  # Plugin setup and installation
└── .gitignore                 # Git ignore rules
```

---

## 🔧 Development

### Local Development Setup

1. **Clone repository**
   ```bash
   git clone https://github.com/diegojucah/FlowBPMN.git flowbpmn
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
- **CSS**: Inline styles + minimal external CSS
- **Comments**: PHPDoc and JSDoc
- **Database**: Follow GLPI naming conventions (`glpi_plugin_flowbpmn_*`)

### Adding Translations

1. Edit locale files in `locales/` directory
2. Add new keys to `$LANG['plugin_flowbpmn']` array
3. Use `__('Key', 'flowbpmn')` in PHP
4. Inject translations via `flow.class.php` → `window.FLOWBPMN_I18N`
5. Use `this._t('Key')` in JavaScript
6. Test with all supported languages

### Architecture Notes

- **No CDN Dependencies**: bpmn.io library is bundled locally
- **Single Page Application**: Editor loads dynamically via JavaScript
- **AJAX-Heavy**: All operations use AJAX endpoints
- **No Configuration UI**: Settings exist in DB but no admin interface (planned for v1.1.0)
- **Auto-versioning**: Every save creates a new version
- **Automatic Cleanup**: Old versions deleted based on `max_versions_per_item`

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

- **[bpmn.io](https://bpmn.io/)** - For the excellent BPMN editor library
- **[GLPI Community](https://glpi-project.org/)** - For the amazing ITIL asset management system


---

## 📞 Support

- **Issues**: [GitHub Issues](https://github.com/diegojucah/FlowBPMN/issues)
- **Documentation**: [GitHub Wiki](https://github.com/diegojucah/FlowBPMN/wiki)
- **GLPI Forum**: [GLPI Plugins Forum](https://forum.glpi-project.org/)

---

## 🗺️ Roadmap

### Version 1.1.0 (Planned - Q1 2025)
- [ ] **Configuration UI** - Admin interface for plugin settings
  - Manage max versions per flow
  - Toggle export formats
  - Configure canvas settings
  - Enable/disable features
- [ ] **Enhanced Templates** - Template categories and tags
- [ ] **Improved Search** - Advanced filtering in template gallery
- [ ] **Bulk Operations** - Delete multiple versions at once

### Version 1.2.0 (Planned - Q2 2025)
- [ ] **BPMN Validation** - Real-time diagram validation
- [ ] **Collaboration Features** - Comments and annotations
- [ ] **Diagram Comparison** - Visual diff between versions
- [ ] **Export Improvements** - Batch export, custom formats

### Version 2.0.0 (Vision - 2025)
- [ ] **Workflow Automation** - Execute BPMN workflows
- [ ] **REST API** - Programmatic access to diagrams
- [ ] **Real-time Collaboration** - Multiple users editing simultaneously
- [ ] **Advanced Analytics** - Diagram metrics and insights
- [ ] **Mobile App** - Native mobile companion app

---

## 📊 Changelog

### v1.0.0 (2024-12-29) - First Stable Release
- ✨ Full BPMN 2.0 editor integration with bpmn.io v18.6.1
- ✨ Complete template system with gallery and search
- ✨ Full internationalization support (English, Portuguese, Spanish)
- ✨ Version control with restore capabilities
- ✨ Granular permission system per profile
- ✨ Multiple export formats (BPMN, SVG, PNG, PDF)
- ✨ Auto-save and automatic PNG attachment
- ✨ Modern UI fully integrated with GLPI 10
- ✨ Dark mode support
- ✨ Responsive design for mobile devices
- 🔒 Complete authentication and audit trail
- 📝 Comprehensive documentation in English

---

**Made with ❤️ by [KactuX](https://github.com/diegojucah) for the GLPI Community**
