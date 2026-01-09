# Translation Guide

This guide helps translate FlowBPMN documentation to other languages.

## Current Status

- **Portuguese (PT-BR)**: ✅ Complete (7,805 lines)
- **English (EN)**: 🚧 Structure created, needs translation
- **Spanish (ES)**: 🚧 Structure created, needs translation

## Translation Priority

### High Priority (translate first)
1. README.md
2. INSTALLATION.md
3. USER_GUIDE.md

### Medium Priority
4. ADMIN_GUIDE.md
5. TROUBLESHOOTING.md

### Low Priority (technical, code is universal)
6. DEVELOPER_GUIDE.md
7. API_REFERENCE.md
8. DATABASE_SCHEMA.md
9. ARCHITECTURE.md
10. BUSINESS_PROCESSES.md

## Recommended Tools

### DeepL (Best Quality)
- https://www.deepl.com/translator
- Maintains Markdown formatting
- Better technical context

### Google Translate API
- Faster
- Supports batch processing
- Free tier available

### ChatGPT/Claude
- Good quality
- Maintains technical context
- Can review translations

## What NOT to Translate

- Technical terms: BPMN, XML, SVG, PNG, SQL, etc.
- Class names: `PluginFlowbpmnFlow`
- Commands: SQL queries, Bash scripts
- URLs and links
- Code blocks
- File paths
- Only translate labels in Mermaid diagrams

## How to Contribute

1. Fork the repository
2. Translate documents in `docs/en/` or `docs/es/`
3. Maintain Markdown structure
4. Test all links
5. Submit Pull Request

## Questions?

Open an issue on GitHub: https://github.com/diegojucah/FlowBPMN/issues
