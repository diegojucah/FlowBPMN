# Changelog

All notable changes to the flowBPMN plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.2.0] - 2025-12-26

### ⚠️ Breaking Changes
- **Removido suporte ao GLPI 10.x**: O plugin agora requer exclusivamente GLPI 11.0.0 ou superior.
- Código de compatibilidade legado removido para otimização e limpeza.

### Changed
- Requisitos mínimos atualizados para PHP 8.1+ e GLPI 11.0+.
- Simplificação da classe `PluginFlowbpmnHelper`.
- Atualização de ícones para usar nativamente Tabler Icons do GLPI 11.

---

## [2.1.0] - 2025-12-26

### 🔒 Security (CRITICAL)

#### Fixed
- **CRITICAL**: Removido hardcoded `$user_id = 2` em `ajax/flow.php` que causava todos os registros serem atribuídos ao usuário ID 2
- Implementado bootstrap correto do GLPI em todos os arquivos AJAX
- Adicionado autenticação nativa usando `Session::checkLoginUser()` e `Session::getLoginUserID()`
- Implementado validações de permissão em todas as operações AJAX (save, delete, restore, view)
- Adicionado registro correto do usuário em operações de restore
- Corrigido rastreamento de auditoria para refletir o usuário real de cada ação

#### Changed
- `ajax/flow.php`: Implementado bootstrap do GLPI e validações de permissão para save e delete_version
- `ajax/bpmn_versions.php`: Adicionado bootstrap do GLPI e verificação de permissões de view e restore
- `ajax/bpmn_restore.php`: Implementado bootstrap do GLPI, validação de permissões e registro do usuário que executou o restore
- Todas as operações agora seguem a arquitetura nativa do GLPI para identificação de usuários

#### Technical Details
- Removido código manual de detecção de sessão que não funcionava corretamente
- Implementado padrão correto de bootstrap: `define('GLPI_ROOT')` + `include(GLPI_ROOT . "/inc/includes.php")`
- Adicionado verificações de autenticação antes de processar qualquer operação
- Implementado mensagens de erro em português (PT-BR)
- Garantido compatibilidade com GLPI 10.x e 11.x

### 📝 Impact

Esta atualização corrige um problema crítico de segurança e auditoria. **Todos os usuários devem atualizar imediatamente.**

**Antes**: Todos os diagramas eram registrados com `users_id = 2`, impossibilitando auditoria correta.

**Depois**: Cada ação (criar, editar, restaurar, deletar) é corretamente registrada com o usuário autenticado que a executou.

---

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
