# FlowBPMN v1.0.0 - BPMN Editor Plugin for GLPI

[![License: GPLv3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![GLPI Version](https://img.shields.io/badge/GLPI-11.0+-orange.svg)](https://glpi-project.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4+-purple.svg)](https://php.net/)
[![Languages](https://img.shields.io/badge/Languages-EN%20|%20PT%20|%20ES-green.svg)](https://github.com/diegojucah/FlowBPMN)

Professional BPMN (Business Process Model and Notation) editor plugin for GLPI 11, enabling creation, editing, and management of BPMN diagrams directly within Tickets, Problems, and Changes.

**Developed by [KactuX](https://github.com/diegojucah)** - Contributing to the GLPI open-source community.

---

## ✨ Features

- **📊 Full BPMN 2.0 Editor** - Complete visual editor powered by [bpmn.io](https://bpmn.io/)
- **🎯 Integrated with GLPI** - Seamless tabs in Tickets, Problems, and Changes
- **📝 Version Control** - Complete history with restore capabilities
- **🔒 Granular Permissions** - Per-profile rights management (view, edit, delete, restore)
- **💾 Auto-save** - Automatic diagram attachment to items
- **📤 Export Options** - Export to BPMN XML, SVG, PNG, and PDF formats
- **🎨 Modern UI** - Fully integrated with GLPI 11 design system
- **🌙 Dark Mode** - Full support for GLPI dark theme
- **📱 Responsive** - Works on desktop and mobile devices
- **🗂️ Template System** - Save and reuse BPMN diagrams as templates
- **🌍 Multilingual** - Full support for English, Portuguese, and Spanish
- **🔍 Search & Filter** - Quick template search and version filtering

---

## 📋 Requirements

- **GLPI**: 11.0.0 or higher
- **PHP**: 7.4 or higher (8.1+ recommended)
- **Web Browser**: Modern browser with JavaScript enabled
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

### 1. Global Settings

Navigate to: **Setup → General → FlowBPMN**

Configure:
- ✅ Auto-attach diagrams to items
- 🔢 Maximum versions per flow (default: 10)
- 📤 Enable/disable export formats (BPMN, SVG, PNG)
- 🎨 Canvas settings (height, grid)

### 2. Profile Permissions

Navigate to: **Setup → Profiles → [Profile Name] → FlowBPMN**

Set permissions for each item type (Tickets, Problems, Changes):

| Permission | Description |
|------------|-------------|
| **View** | Can see BPMN diagrams |
| **Edit** | Can create and modify diagrams |
| **Delete** | Can delete diagrams and versions |
| **Restore** | Can restore previous versions |

---

## 🔒 Security

### Authentication and Auditing

FlowBPMN implements GLPI's native authentication and complete audit tracking:

- ✅ **Mandatory Authentication**: All AJAX endpoints verify authentication via `Session::checkLoginUser()`
- ✅ **User Tracking**: Every action (create, edit, restore, delete) records the correct user via `Session::getLoginUserID()`
- ✅ **Permission Validation**: All operations verify user profile permissions
- ✅ **Complete Audit Trail**: Version history maintains records of who made each modification

### Security Features

FlowBPMN is built with security as a priority:

- **CSRF Protection**: All forms and AJAX requests include CSRF token validation
- **SQL Injection Prevention**: All database queries use prepared statements
- **XSS Protection**: All user input is properly escaped before display
- **Session Management**: Leverages GLPI's robust session handling
- **Access Control**: Fine-grained permissions per profile and item type

### Best Practices

1. **Configure Appropriate Permissions**: Don't grant "Edit" permission to all profiles
2. **Review Logs Regularly**: Check who is creating/modifying diagrams
3. **Keep Updated**: Always use the latest plugin version
4. **Backup Regularly**: Ensure database backups include plugin tables

---

## 📖 Usage

### Creating a BPMN Diagram

1. Open a **Ticket**, **Problem**, or **Change**
2. Click the **"FlowBPMN"** tab
3. Use the visual editor to create your diagram:
   - Drag elements from the palette (left sidebar)
   - Connect elements using arrows
   - Edit properties by clicking elements
4. Click **"Save"** to store the diagram

### Managing Versions

- Click **"Versions"** button to view history
- Preview any version by clicking **"View"**
- Click **"Restore"** to revert to a previous version
- Delete old versions to save space
- All changes are tracked with user and timestamp

### Using Templates

- **Save as Template**: Click the dropdown on the Save button → "Save as Template"
- **Load Template**: Click the "Templates" button to browse available templates
- **Search Templates**: Use the search bar to quickly find templates by name
- **Delete Templates**: Remove templates you no longer need (requires permission)

### Exporting Diagrams

- Click **"Export"** button
- Choose format:
  - **BPMN XML** - Standard BPMN 2.0 format (interoperable)
  - **SVG** - Vector graphics (scalable)
  - **PNG** - Raster image (for presentations)
  - **PDF** - Print-ready document

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
   - `glpi_plugin_flowbpmn_templates`

### Testing Basic Functionality

1. **Create a Test Ticket**
   - Navigate to: **Assistance → Tickets → Create Ticket**
   - Fill basic information and save

2. **Test BPMN Tab**
   - Open the created ticket
   - Click **"FlowBPMN"** tab
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
   - Test restore functionality

5. **Test Templates**
   - Save diagram as template
   - Create new ticket
   - Load template from gallery
   - Verify diagram loads correctly

### Testing Permissions

1. **Create a Test Profile**
   - Setup → Profiles → Add
   - Name it "BPMN Tester"

2. **Configure Limited Rights**
   - FlowBPMN tab → Set "View" only for Tickets
   - Assign a test user to this profile

3. **Login as Test User**
   - Open a ticket
   - Verify BPMN tab shows read-only mode
   - Verify no "Save" button appears

### Testing Internationalization

1. **Change GLPI Language**
   - User Preferences → Language → Select Portuguese/Spanish
   - Refresh page (Ctrl+F5)

2. **Verify Translation**
   - Open FlowBPMN tab
   - Click "Versions" or "Templates" buttons
   - Verify modals are in selected language

---

## 🛠️ Troubleshooting

### Editor doesn't load

**Problem**: BPMN editor shows loading spinner indefinitely

**Solutions**:
1. Check browser console for errors (F12)
2. Check if JavaScript is enabled
3. Try clearing browser cache (Ctrl+F5)
4. Verify plugin files are correctly uploaded
5. Check PHP error logs for backend issues

### Cannot save diagrams

**Problem**: Save button doesn't work or shows error

**Solutions**:
1. Check user has "Edit" permission for the item type
2. Verify PHP error logs: `/var/log/apache2/error.log` or `/var/log/php-fpm/error.log`
3. Check database connectivity
4. Verify `glpi_plugin_flowbpmn_flows` table exists
5. Ensure sufficient disk space for attachments

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

### Templates not loading

**Problem**: Template gallery is empty or shows errors

**Solutions**:
1. Verify `glpi_plugin_flowbpmn_templates` table exists
2. Check user has permission to view templates
3. Verify SVG content is being saved correctly
4. Check PHP `max_allowed_packet` setting for large diagrams

### Language not changing

**Problem**: Interface remains in English despite language change

**Solutions**:
1. Clear GLPI cache: `php bin/console cache:clear`
2. Hard refresh browser (Ctrl+F5)
3. Verify locale files exist in `locales/` directory
4. Check `$_SESSION['glpilanguage']` is set correctly

---

## 🗂️ File Structure

```
flowbpmn/
├── ajax/
│   ├── bpmn_restore.php       # Version restore endpoint
│   ├── bpmn_save.php          # Diagram save endpoint
│   ├── bpmn_version.php       # Single version endpoint
│   ├── bpmn_versions.php      # Version list endpoint
│   ├── db_update.php          # Database update utility
│   ├── flow.php               # Main AJAX handler
│   └── template.php           # Template AJAX handler
├── css/
│   └── flowbpmn.css           # Main stylesheet
├── front/
│   └── config.form.php        # Configuration page
├── inc/
│   ├── bpmntask.class.php     # BPMN task class
│   ├── flow.class.php         # Main flow class
│   ├── helper.class.php       # Helper utilities
│   ├── plugin.class.php       # Plugin configuration
│   ├── profile.class.php      # Permissions management
│   ├── template.class.php     # Template management
│   └── version.class.php      # Version control
├── install/
│   └── mysql/                 # Database schema
├── js/
│   └── flowbpmn.js            # Main JavaScript with bpmn-js integration
├── lib/
│   └── bpmn-js/               # BPMN.io library (local)
├── locales/
│   ├── en_GB.php              # English translations
│   ├── es_ES.php              # Spanish translations
│   └── pt_BR.php              # Portuguese translations
├── templates/
│   ├── approval.bpmn          # Approval process template
│   ├── parallel.bpmn          # Parallel tasks template
│   └── simple.bpmn            # Simple workflow template
├── hook.php                   # Plugin hooks
├── setup.php                  # Plugin setup and installation
├── plugin.xml                 # Plugin metadata
├── README.md                  # This file
├── LICENSE                    # GPLv3 License
├── CHANGELOG.md               # Version history
├── SECURITY.md                # Security policy
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
- **CSS**: BEM methodology
- **Comments**: PHPDoc and JSDoc
- **Database**: Follow GLPI naming conventions

### Adding Translations

1. Edit locale files in `locales/` directory
2. Add new keys to `$LANG['plugin_flowbpmn']` array
3. Use `__('Key', 'flowbpmn')` in PHP
4. Use `this._t('Key')` in JavaScript
5. Test with all supported languages

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
- **Contributors** - For all the valuable feedback and contributions

---

## 📞 Support

- **Issues**: [GitHub Issues](https://github.com/diegojucah/FlowBPMN/issues)
- **Documentation**: [GitHub Wiki](https://github.com/diegojucah/FlowBPMN/wiki)
- **GLPI Forum**: [GLPI Plugins Forum](https://forum.glpi-project.org/)
- **Email**: support@kactux.com

---

## 🗺️ Roadmap

### Version 3.1 (Planned)
- [ ] BPMN validation and error checking
- [ ] Collaboration features (comments, annotations)
- [ ] Enhanced template library with categories
- [ ] Diagram comparison (diff view)

### Version 3.2 (Future)
- [ ] Simulation/execution engine
- [ ] REST API endpoints
- [ ] Diagram analytics and metrics
- [ ] Mobile app companion

### Version 4.0 (Vision)
- [ ] Real-time collaborative editing
- [ ] AI-powered diagram suggestions
- [ ] Integration with external BPMN tools
- [ ] Advanced workflow automation

---

## 📊 Changelog

### v1.0.0 (2024-12-29) - First Stable Release
- ✨ Full BPMN 2.0 editor integration with bpmn.io
- ✨ Complete template system with gallery and search
- ✨ Full internationalization support (English, Portuguese, Spanish)
- ✨ Version control with restore capabilities
- ✨ Granular permission system per profile
- ✨ Multiple export formats (BPMN, SVG, PNG, PDF)
- ✨ Auto-save and automatic diagram attachment
- ✨ Modern UI fully integrated with GLPI 11
- ✨ Dark mode support
- ✨ Responsive design for mobile devices
- 🔒 Complete authentication and audit trail
- 📝 Comprehensive documentation in English

---

**Made with ❤️ by [KactuX](https://github.com/diegojucah) for the GLPI Community**
