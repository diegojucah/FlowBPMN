# Changelog

All notable changes to the flowBPMN plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-10-23

### 🎉 Initial Release

#### Added
- Full BPMN 2.0 editor integration using bpmn.io (v18.6.1)
- BPMN Flow tabs for Tickets, Problems, and Changes
- Complete version control system with restore capabilities
- Granular permissions system per profile and item type
- Auto-save functionality with SVG attachment to items
- Export to BPMN XML, SVG, and PNG formats
- Modern UI fully integrated with GLPI 11 design system
- Dark mode support
- Responsive design for mobile and desktop
- Comprehensive configuration page
- Profile-based rights management
- Database tables for flows, versions, configs, and profiles
- Complete documentation and installation guide

#### Technical Details
- PHP 8.1+ compatibility
- GLPI 11.0+ compatibility
- CDN-based bpmn-js loading (no local dependencies)
- RESTful AJAX API
- PSR-12 code standards
- GPLv3+ license

### 🙏 Credits
- Developed by KactuX
- Powered by bpmn.io
- Built for GLPI Community

---

## Legend

- **Added** - New features
- **Changed** - Changes in existing functionality
- **Deprecated** - Soon-to-be removed features
- **Removed** - Removed features
- **Fixed** - Bug fixes
- **Security** - Security improvements
