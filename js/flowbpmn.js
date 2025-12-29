/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI - JavaScript
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 by KactuX
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/diegojucah/pluginBPMN
 * -------------------------------------------------------------------------
 */

// BPMN.js LOCAL version (v3.0 - No CDN dependency)
const BPMN_JS_VERSION = '18.6.1';
const BPMN_JS_LOCAL = '/plugins/flowbpmn/lib/bpmn-js/bpmn-modeler.development.js';
const BPMN_JS_CSS = '/plugins/flowbpmn/lib/bpmn-js/diagram-js.css';
const BPMN_FONT_CSS = '/plugins/flowbpmn/lib/bpmn-js/bpmn-js.css';
const BPMN_FONT = '/plugins/flowbpmn/lib/bpmn-js/bpmn-embedded.css';

/**
 * BPMN Flow Editor Class
 */
class BpmnFlowEditor {
    constructor(options) {
        this.container = document.querySelector(options.container);
        if (!this.container) {
            console.error('Container not found:', options.container);
            return;
        }

        this.itemtype = this.container.dataset.itemtype;
        this.items_id = this.container.dataset.itemsId;
        this.canEdit = this.container.dataset.canEdit === '1';
        this.existingXml = options.existingXml;
        this.pluginUrl = options.pluginUrl || '';
        this.lastDateMod = options.dateMod || ''; // Optimistic Locking
        this.modeler = null;

        this.init();
    }

    /**
     * Security: Escape HTML to prevent XSS attacks
     * @param {string} text - Text to escape
     * @returns {string} - Escaped HTML
     */
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Security: Get CSRF token from GLPI meta tag
     * @returns {string} - CSRF token
     */
    getCSRFToken() {
        const meta = document.querySelector('meta[name="glpi-csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    /**
     * Helper: Get translated string
     * @param {string} key - Translation key
     * @return {string} - Translated text or key if missing
     */
    _t(key) {
        if (window.FLOWBPMN_I18N && window.FLOWBPMN_I18N[key]) {
            return window.FLOWBPMN_I18N[key];
        }
        return key;
    }

    async init() {
        // Load CSS
        this.loadCSS();

        // Show loading
        this.showLoading();

        // Load bpmn-js library
        await this.loadBpmnJS();

        // Initialize modeler
        this.initModeler();

        // Bind events
        this.bindEvents();

        // Hide loading
        this.hideLoading();
    }

    loadCSS() {
        if (!document.querySelector(`link[href="${BPMN_JS_CSS}"]`)) {
            const link1 = document.createElement('link');
            link1.rel = 'stylesheet';
            link1.href = BPMN_JS_CSS;
            document.head.appendChild(link1);

            const link2 = document.createElement('link');
            link2.rel = 'stylesheet';
            link2.href = BPMN_FONT_CSS;
            document.head.appendChild(link2);

            const link3 = document.createElement('link');
            link3.rel = 'stylesheet';
            link3.href = BPMN_FONT;
            document.head.appendChild(link3);
        }

        // Custom CSS for FlowBPMN Interface
        if (!document.getElementById('flowbpmn-custom-css')) {
            const css = `
                /* Versions Modal Grid */
                .flowbpmn-versions-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
                    gap: 25px;
                    padding: 10px;
                }
                
                /* Version Card Styling */
                .flowbpmn-version-card {
                    border: 1px solid #e9ecef;
                    border-radius: 12px;
                    overflow: hidden;
                    background: #fff;
                    transition: all 0.2s ease;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
                    display: flex;
                    flex-direction: column;
                }
                .flowbpmn-version-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
                }

                /* Preview Area */
                .flowbpmn-version-preview {
                    height: 240px;
                    background-color: #f8f9fa;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    position: relative;
                    border-bottom: 1px solid #e9ecef;
                    overflow: hidden;
                }
                .flowbpmn-version-preview svg {
                    width: 100%;
                    height: 100%;
                    object-fit: contain;
                }

                /* Overlay */
                .flowbpmn-version-overlay {
                    position: absolute;
                    top: 0; left: 0; right: 0; bottom: 0;
                    background: rgba(33, 37, 41, 0.85); /* Dark overlay */
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    opacity: 0;
                    transition: opacity 0.2s;
                    gap: 10px;
                }
                .flowbpmn-version-card:hover .flowbpmn-version-overlay {
                    opacity: 1;
                }

                /* Info Section */
                .flowbpmn-version-info {
                    padding: 15px;
                    flex-grow: 1;
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                }
                .flowbpmn-version-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 5px;
                }
                .flowbpmn-badge {
                    background: #212529;
                    color: #fff;
                    padding: 4px 8px;
                    border-radius: 6px;
                    font-size: 0.85rem;
                    font-weight: 600;
                }
                .flowbpmn-meta {
                    font-size: 0.9rem;
                    color: #6c757d;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                }

                /* Actions Footer */
                .flowbpmn-actions {
                    margin-top: 15px;
                }
                
                /* Action Buttons in Overlay */
                .btn-flowbpmn-action {
                    background: rgba(255,255,255,0.15);
                    border: 1px solid rgba(255,255,255,0.5);
                    color: white;
                    border-radius: 6px;
                    padding: 8px 16px;
                    backdrop-filter: blur(4px);
                    transition: all 0.2s;
                }
                .btn-flowbpmn-action:hover {
                    background: white;
                    color: #212529;
                    border-color: white;
                }
            `;
            const style = document.createElement('style');
            style.id = 'flowbpmn-custom-css';
            style.textContent = css;
            document.head.appendChild(style);
        }
    }

    async loadBpmnJS() {
        if (window.BpmnJS) {
            return Promise.resolve();
        }

        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = BPMN_JS_LOCAL;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    initModeler() {
        this.modeler = new BpmnJS({
            container: this.container
        });

        // Load diagram
        if (this.existingXml) {
            this.loadDiagram(this.existingXml);
        } else {
            this.createNewDiagram();
        }
    }

    async loadDiagram(xml) {
        try {
            await this.modeler.importXML(xml);
            const canvas = this.modeler.get('canvas');
            canvas.zoom('fit-viewport');
        } catch (err) {
            console.error('Error loading diagram:', err);
            this.showError('Error loading BPMN diagram');
        }
    }

    async createNewDiagram() {
        const newDiagram = `<?xml version="1.0" encoding="UTF-8"?>
        <bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL" 
                          xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" 
                          xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" 
                          id="Definitions_1" 
                          targetNamespace="http://bpmn.io/schema/bpmn">
            <bpmn:process id="Process_1" isExecutable="false">
                <bpmn:startEvent id="StartEvent_1" />
            </bpmn:process>
            <bpmndi:BPMNDiagram id="BPMNDiagram_1">
                <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="Process_1">
                    <bpmndi:BPMNShape id="_BPMNShape_StartEvent_2" bpmnElement="StartEvent_1">
                        <dc:Bounds x="173" y="102" width="36" height="36" />
                    </bpmndi:BPMNShape>
                </bpmndi:BPMNPlane>
            </bpmndi:BPMNDiagram>
        </bpmn:definitions>`;

        await this.loadDiagram(newDiagram);
    }

    bindEvents() {
        // Save button
        const saveBtn = document.getElementById('bpmn-save-btn');
        if (saveBtn && this.canEdit) {
            saveBtn.addEventListener('click', () => this.saveDiagram());
        }

        // Import button
        const importBtn = document.getElementById('bpmn-import-btn');
        const fileInput = document.getElementById('bpmn-file-input');
        if (importBtn && fileInput && this.canEdit) {
            importBtn.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', (e) => this.importDiagram(e));
        }

        // Export dropdown options
        const exportPngOption = document.getElementById('export-png-option');
        if (exportPngOption) {
            exportPngOption.addEventListener('click', (e) => {
                e.preventDefault();
                this.exportPNG();
            });
        }

        const exportSvgOption = document.getElementById('export-svg-option');
        if (exportSvgOption) {
            exportSvgOption.addEventListener('click', (e) => {
                e.preventDefault();
                this.exportSVG();
            });
        }

        const exportBpmnOption = document.getElementById('export-bpmn-option');
        if (exportBpmnOption) {
            exportBpmnOption.addEventListener('click', (e) => {
                e.preventDefault();
                this.exportBPMN();
            });
        }

        // Export PDF option (Priority 4)
        const exportPdfOption = document.getElementById('export-pdf-option');
        if (exportPdfOption) {
            exportPdfOption.addEventListener('click', (e) => {
                e.preventDefault();
                this.exportPDF();
            });
        }

        // Versions button
        const versionsBtn = document.getElementById('bpmn-versions-btn');
        if (versionsBtn) {
            versionsBtn.addEventListener('click', () => this.showVersionsModal());
        }

        // Templates Buttons (Priority 4.1)
        const saveTemplateBtn = document.getElementById('bpmn-save-template-btn');
        if (saveTemplateBtn) {
            saveTemplateBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.saveAsTemplate();
            });
        }

        const loadTemplateBtn = document.getElementById('bpmn-load-template-btn');
        if (loadTemplateBtn) {
            loadTemplateBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.showLoadTemplateModal();
            });
        }
    }

    /**
     * Export diagram as PDF (using browser print for high quality vector output)
     */
    async exportPDF() {
        try {
            const { svg } = await this.modeler.saveSVG({ format: true });

            const printWindow = window.open('', '_blank');
            if (!printWindow) {
                alert('Por favor, permita popups para exportar o PDF.');
                return;
            }

            printWindow.document.write(`
                <html>
                <head>
                    <title>FlowBPMN Diagram</title>
                    <style>
                        @page { size: landscape; margin: 0; }
                        body { margin: 0; display: flex; justify-content: center; align-items: center; height: 100vh; }
                        svg { width: 100%; height: 100%; maxHeight: 100vh; }
                    </style>
                </head>
                <body>
                    ${svg}
                    <script>
                        window.onload = function() {
                            setTimeout(function() {
                                window.print();
                                // window.close(); // User closes manually to verify
                            }, 500);
                        }
                    </script>
                </body>
                </html>
            `);
            printWindow.document.close();

        } catch (err) {
            console.error('Error exporting PDF:', err);
            alert('Erro ao gerar PDF: ' + err.message);
        }
    }

    async saveDiagram() {
        try {
            const { xml } = await this.modeler.saveXML({ format: true });

            // Robust SVG generation handling for different bpmn-js versions
            let svg = '';
            try {
                const result = await this.modeler.saveSVG();
                svg = result.svg || result; // Handle {svg: string} or raw string
            } catch (svgErr) {
                console.error('Error generating SVG:', svgErr);
            }


            // Generate PNG for document attachment
            const pngData = await this.generatePNG(svg);

            const url = `${this.pluginUrl}/ajax/flow.php`;
            console.log('Saving to:', url);

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Glpi-Csrf-Token': this.getCSRFToken()
                },
                body: JSON.stringify({
                    action: 'save',
                    itemtype: this.itemtype,
                    items_id: this.items_id,
                    bpmn_xml: xml,
                    svg_content: svg,
                    png_data: pngData,
                    name: document.getElementById('flow-name')?.value || ''
                })
            });

            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            console.log('Response ok:', response.ok);

            const text = await response.text();
            console.log('Response text length:', text.length);
            console.log('Response text:', text);
            console.log('Response text (first 200 chars):', text.substring(0, 200));

            let result;
            try {
                result = JSON.parse(text);
                console.log('Parsed result:', result);
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Failed to parse text:', text);
                throw new Error('Resposta inválida do servidor: ' + (text.substring(0, 100) || '[resposta vazia]'));
            }

            if (result.success) {
                // Redirecionar para aba principal (comportamento idêntico para Ticket, Problem e Change)
                const origin = window.location.origin;
                const formFile = this.itemtype.toLowerCase() + '.form.php';
                let targetUrl = `${origin}/front/${formFile}?id=${this.items_id}`;

                // Ticket usa $1, Problem e Change usam $main
                if (this.itemtype === 'Ticket') {
                    targetUrl += '&forcetab=Ticket$1';
                } else {
                    targetUrl += `&forcetab=${this.itemtype}$main`;
                }

                console.log('Redirecionando para:', targetUrl);
                targetUrl += `&_ts=${new Date().getTime()}`;
                window.location.href = targetUrl;
            } else {
                throw new Error(result.message || 'Falha ao salvar');
            }
        } catch (err) {
            console.error('Erro ao salvar:', err);
            this.showError('Erro ao salvar diagrama: ' + err.message);
        }
    }

    async showExportModal() {
        try {
            const format = prompt('Escolha o formato de exportação:\n1 - BPMN XML\n2 - SVG\n3 - PNG', '1');

            if (!format) return;

            switch (format) {
                case '1':
                    await this.exportBPMN();
                    break;
                case '2':
                    await this.exportSVG();
                    break;
                case '3':
                    await this.exportPNG();
                    break;
                default:
                    alert('Formato inválido');
            }
        } catch (err) {
            this.showError('Erro ao exportar: ' + err.message);
        }
    }

    async exportBPMN() {
        const { xml } = await this.modeler.saveXML({ format: true });
        this.downloadFile(xml, 'diagram.bpmn', 'application/bpmn+xml');
    }

    async exportSVG() {
        const { svg } = await this.modeler.saveSVG();
        this.downloadFile(svg, 'diagram.svg', 'image/svg+xml');
    }

    async generatePNG(svg) {
        return new Promise((resolve) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();

            const svgBlob = new Blob([svg], { type: 'image/svg+xml' });
            const url = URL.createObjectURL(svgBlob);

            img.onload = () => {
                canvas.width = img.width;
                canvas.height = img.height;
                ctx.drawImage(img, 0, 0);

                const pngData = canvas.toDataURL('image/png');
                URL.revokeObjectURL(url);
                resolve(pngData);
            };

            img.src = url;
        });
    }

    async exportPNG() {
        const { svg } = await this.modeler.saveSVG();
        const pngData = await this.generatePNG(svg);

        // Convert base64 to blob
        const base64Data = pngData.split(',')[1];
        const byteCharacters = atob(base64Data);
        const byteNumbers = new Array(byteCharacters.length);
        for (let i = 0; i < byteCharacters.length; i++) {
            byteNumbers[i] = byteCharacters.charCodeAt(i);
        }
        const byteArray = new Uint8Array(byteNumbers);
        const blob = new Blob([byteArray], { type: 'image/png' });

        this.downloadFile(blob, 'diagram.png', 'image/png');
    }

    async downloadFile(data, filename, mimeType) {
        const blob = data instanceof Blob ? data : new Blob([data], { type: mimeType });

        try {
            // Tentar usar File System Access API (navegadores modernos)
            if ('showSaveFilePicker' in window) {
                const options = {
                    suggestedName: filename,
                    types: [{
                        description: this.getFileDescription(mimeType),
                        accept: { [mimeType]: [this.getFileExtension(filename)] }
                    }]
                };

                const handle = await window.showSaveFilePicker(options);
                const writable = await handle.createWritable();
                await writable.write(blob);
                await writable.close();

                this.showSuccess('Arquivo salvo com sucesso!');
                return;
            }
        } catch (err) {
            if (err.name === 'AbortError') {
                // Usuário cancelou o dialog
                return;
            }
            console.warn('File System Access API falhou, usando fallback:', err);
        }

        // Fallback para navegadores antigos (download automático)
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    getFileDescription(mimeType) {
        const descriptions = {
            'image/png': 'Imagem PNG',
            'image/svg+xml': 'Imagem SVG',
            'application/bpmn+xml': 'Diagrama BPMN'
        };
        return descriptions[mimeType] || 'Arquivo';
    }

    getFileExtension(filename) {
        return '.' + filename.split('.').pop();
    }

    async showVersionsModal() {
        try {
            const pluginUrl = this.pluginUrl || '/plugins/flowbpmn';
            const url = `${pluginUrl}/ajax/bpmn_versions.php?itemtype=${this.itemtype}&items_id=${this.items_id}`;

            const response = await fetch(url);
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Failed to load versions');
            }

            // Create modal HTML
            const modalHtml = this.createVersionsModalHTML(result);

            // Check if modal already exists
            let modal = document.getElementById('flowbpmn-versions-modal');
            if (modal) {
                modal.remove();
            }

            // Add modal to body
            document.body.insertAdjacentHTML('beforeend', modalHtml);

            // Show modal (GLPI 11.x uses Bootstrap 5 only)
            modal = document.getElementById('flowbpmn-versions-modal');
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();

            // Bind version actions
            this.bindVersionActions(result.current.flow_db_id, result.canRestore);

            // Bind pagination if needed
            if (result.versions.length > 6) {
                this.bindPaginationEvents('versions', result.versions, result.canRestore);
            }

        } catch (err) {
            console.error('Error loading versions:', err);
            this.showError('Erro ao carregar versões: ' + err.message);
        }
    }

    createVersionsModalHTML(data) {
        const versions = data.versions;
        const itemsPerPage = 6;
        const totalPages = Math.ceil(versions.length / itemsPerPage);

        let html = `
        <div class="modal fade" id="flowbpmn-versions-modal" tabindex="-1" aria-labelledby="flowbpmnVersionsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl" style="max-width: 65vw; margin-top: 1.75rem;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="flowbpmnVersionsModalLabel">
                            <i class="ti ti-history"></i> ${this._t('Version History')}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        
                        <!-- Current Version Info -->
                        <div class="alert alert-info mb-3 d-flex align-items-center justify-content-between" style="min-height: 38px; padding: 0.5rem 1rem;">
                            <div>
                                <i class="ti ti-info-circle me-2"></i>
                                <strong>${this._t('Current Version')}:</strong> v${data.current.id || 'N/A'}
                            </div>
                            <small class="text-muted">
                                <i class="ti ti-calendar me-1"></i>${data.current.date_mod || ''}
                            </small>
                        </div>
                        
                        ${versions.length === 0 ?
                '<div class="alert alert-warning">' + this._t('No previous versions available') + '</div>' :
                `<div class="flowbpmn-versions-grid" id="flowbpmn-versions-grid" data-total-pages="${totalPages}" data-current-page="1">
                                ${versions.slice(0, itemsPerPage).map(v => this.createVersionCard(v, data.canRestore)).join('')}
                            </div>
                            ${totalPages > 1 ? this.createPaginationHTML('versions', totalPages, versions, data.canRestore) : ''}`
            }

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${this._t('Close')}</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Image Preview Modal -->
        <div class="modal fade" id="flowbpmn-image-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body position-relative">
                         <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 10px; right: 10px; z-index: 10;"></button>
                         <div id="flowbpmn-image-container" class="d-flex justify-content-center"></div>
                    </div>
                </div>
            </div>
        </div>`;

        return html;
    }

    createVersionCard(version, canRestore) {
        // Thumbnail logic
        let thumbnail = '<div class="text-muted"><i class="ti ti-photo-off"></i> Sem pré-visualização</div>';
        let svgData = '';

        // Check if content exists and is not just "0" (DB default/error) and looks like SVG
        if (version.svg_content && version.svg_content !== '0' && version.svg_content.length > 50) {
            thumbnail = version.svg_content; // Directly embed SVG
            svgData = encodeURIComponent(version.svg_content);
        } else {
            thumbnail = `<div class="text-muted"><i class="ti ti-photo-off"></i> ${this._t('No preview available')}</div>`;
        }

        return `
        <div class="flowbpmn-version-card" id="version-card-${version.id}">
            <div class="flowbpmn-version-preview">
                ${thumbnail}
                <div class="flowbpmn-version-overlay">
                    <button type="button" class="btn-flowbpmn-action flowbpmn-view-image" data-svg="${svgData}">
                        <i class="ti ti-eye"></i> ${this._t('View')}
                    </button>
                    <button type="button" class="btn-flowbpmn-action flowbpmn-delete-version" data-version-id="${version.id}">
                        <i class="ti ti-trash"></i> ${this._t('Delete')}
                    </button>
                </div>
            </div>
            <div class="flowbpmn-version-info">
                <div class="flowbpmn-version-header">
                    <span class="flowbpmn-badge">v${version.version_number}</span>
                </div>
                
                <div class="flowbpmn-meta" title="${this._t('Modification Date')}">
                    <i class="ti ti-calendar"></i> ${version.date_creation_formatted}
                </div>
                <div class="flowbpmn-meta" title="${this._t('Responsible User')}">
                    <i class="ti ti-user"></i> ${version.user_name}
                </div>

                <div class="flowbpmn-actions">
                    ${canRestore ?
                `<button type="button" class="btn btn-warning w-100 flowbpmn-restore-version btn-flowbpmn-restore"
                                data-version-id="${version.id}"
                                style="background-color: #FFC107; border: none; color: #212529; font-weight: 500;">
                            <i class="ti ti-refresh"></i> ${this._t('Restore')}
                        </button>` : ''
            }
                </div>
            </div>
        </div>`;
    }

    bindVersionActions(currentFlowId, canRestore) {
        // Restore Action
        const restoreButtons = document.querySelectorAll('.flowbpmn-restore-version');
        restoreButtons.forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const versionId = e.currentTarget.dataset.versionId;
                if (!confirm(this._t('This will overwrite the current diagram. Continue?'))) {
                    return;
                }
                try {
                    await this.restoreVersion(currentFlowId, versionId);
                } catch (err) {
                    console.error('Error restoring version:', err);
                    this.showError('Erro ao restaurar versão: ' + err.message);
                }
            });
        });

        // View Action
        const viewBtns = document.querySelectorAll('.flowbpmn-view-image');
        viewBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const svgContent = decodeURIComponent(e.currentTarget.dataset.svg);
                if (svgContent) {
                    const container = document.getElementById('flowbpmn-image-container');
                    container.innerHTML = svgContent;

                    // Fix SVG size for modal
                    const svgEl = container.querySelector('svg');
                    if (svgEl) {
                        svgEl.removeAttribute('width');
                        svgEl.removeAttribute('height');
                        svgEl.style.width = '100%';
                        svgEl.style.height = 'auto';
                        svgEl.style.maxWidth = '100%';
                        // svgEl.style.maxHeight = '75vh'; // Removed to allow full height expansion
                    }

                    const imgModal = new bootstrap.Modal(document.getElementById('flowbpmn-image-modal'));
                    imgModal.show();
                } else {
                    alert('Imagem indisponível para esta versão.');
                }
            });
        });

        // Delete Action
        const deleteBtns = document.querySelectorAll('.flowbpmn-delete-version');
        deleteBtns.forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const versionId = e.currentTarget.dataset.versionId;
                if (confirm(this._t('Are you sure you want to permanently delete this version?'))) {
                    await this.deleteVersion(versionId);
                }
            });
        });
    }

    async deleteVersion(versionId) {
        try {
            const response = await fetch(`${this.pluginUrl}/ajax/flow.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'delete_version',
                    version_id: versionId
                })
            });

            const result = await response.json();
            if (result.success) {
                // Remove card from DOM with animation
                const card = document.getElementById(`version-card-${versionId}`);
                if (card) {
                    card.style.transition = 'opacity 0.5s';
                    card.style.opacity = '0';
                    setTimeout(() => card.remove(), 500);
                }
                this.showSuccess(this._t('Version deleted successfully!'));
            } else {
                this.showError(this._t('Error deleting version') + ': ' + result.message);
            }
        } catch (err) {
            console.error(err);
            this.showError(this._t('Connection error when trying to delete.'));
        }
    }

    async restoreVersion(flowId, versionId) {
        console.log('Restoring version:', { flowId, versionId });
        const pluginUrl = this.pluginUrl || '/plugins/flowbpmn';
        const url = `${pluginUrl}/ajax/bpmn_restore.php`;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Glpi-Csrf-Token': this.getCSRFToken()
                },
                body: JSON.stringify({
                    flow_id: flowId,
                    version_id: versionId
                })
            });

            const text = await response.text();
            console.log('Restore response:', text);

            let result;
            try {
                result = JSON.parse(text);
            } catch (e) {
                throw new Error('Invalid JSON from server: ' + text.substring(0, 100));
            }

            if (result.success && result.bpmn_xml) {
                // EXPOSE FOR DEBUGGING
                window.lastRestoreResult = result;
                console.log('DEBUG RESTORE RESULT:', result);

                // Importar XML diretamente no editor
                await this.modeler.importXML(result.bpmn_xml);

                // Fechar modal de versões
                const modalElement = document.getElementById('flowbpmn-versions-modal');
                if (modalElement) {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    }
                }

                // Mostrar mensagem de sucesso
                this.showSuccess('Versão restaurada! Você pode editar e salvar novamente.');

                // Ajustar zoom para caber na tela
                const canvas = this.modeler.get('canvas');
                canvas.zoom('fit-viewport');
            } else {
                throw new Error(result.message || 'Falha ao restaurar versão');
            }
        } catch (err) {
            console.error('Error in restoreVersion:', err);
            // Re-throw so bindVersionActions can catch it
            throw err;
        }
    }

    async importDiagram(event) {
        try {
            const file = event.target.files[0];
            if (!file) return;

            // Validar extensão
            const validExtensions = ['.bpmn', '.xml'];
            const fileExtension = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();
            if (!validExtensions.includes(fileExtension)) {
                this.showError('Formato inválido. Use arquivos .bpmn ou .xml');
                return;
            }

            // Ler arquivo
            const reader = new FileReader();
            reader.onload = async (e) => {
                try {
                    const xml = e.target.result;

                    // Importar no modeler
                    await this.modeler.importXML(xml);

                    // Ajustar zoom
                    const canvas = this.modeler.get('canvas');
                    canvas.zoom('fit-viewport');

                    // Feedback visual
                    this.showSuccess('Diagrama importado com sucesso!');

                    // Limpar input para permitir reimportação do mesmo arquivo
                    event.target.value = '';

                } catch (err) {
                    console.error('Erro ao importar diagrama:', err);
                    this.showError('Erro ao importar diagrama: ' + err.message);
                }
            };

            reader.onerror = () => {
                this.showError('Erro ao ler arquivo');
            };

            reader.readAsText(file);

        } catch (err) {
            console.error('Erro no processo de importação:', err);
            this.showError('Erro ao processar arquivo: ' + err.message);
        }
    }

    showLoading() {
        const loading = document.createElement('div');
        loading.className = 'flowbpmn-loading';
        loading.innerHTML = '<i class="fas fa-spinner"></i><p>Loading BPMN Editor...</p>';
        this.container.appendChild(loading);
    }

    hideLoading() {
        const loading = this.container.querySelector('.flowbpmn-loading');
        if (loading) {
            loading.remove();
        }
    }

    showSuccess(message) {
        // Usar toast do GLPI se disponível
        if (typeof glpi_toast !== 'undefined') {
            glpi_toast('success', message);
        } else if (typeof displayAjaxMessageAfterRedirect === 'function') {
            displayAjaxMessageAfterRedirect();
        } else {
            alert(message);
        }
    }

    showError(message) {
        alert('Error: ' + message);
    }

    /**
     * Save current diagram as Template
     */
    async saveAsTemplate() {
        const name = prompt("Nome do Template:", "Novo Template");
        if (!name) return;

        try {
            const { xml } = await this.modeler.saveXML({ format: true });
            const { svg } = await this.modeler.saveSVG();

            const pluginUrl = this.pluginUrl || '/plugins/flowbpmn';
            const url = `${pluginUrl}/ajax/flow.php?action=save_template`;

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Glpi-Csrf-Token': this.getCSRFToken()
                },
                body: JSON.stringify({
                    action: 'save_template',
                    name: name,
                    bpmn_xml: xml,
                    svg_content: svg,
                    is_public: 0 // Default private
                })
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess('Template salvo com sucesso!');
            } else {
                throw new Error(result.message);
            }

        } catch (err) {
            console.error('Erro ao salvar template:', err);
            this.showError('Erro ao salvar template: ' + err.message);
        }
    }

    /**
     * Show Modal to Load Template
     */
    async showLoadTemplateModal() {
        try {
            const pluginUrl = this.pluginUrl || '/plugins/flowbpmn';
            const url = `${pluginUrl}/ajax/flow.php?action=list_templates`;

            const response = await fetch(url);
            const result = await response.json();

            if (!result.success) throw new Error(result.message);

            const templates = result.templates;
            const itemsPerPage = 6;
            const totalPages = Math.ceil(templates.length / itemsPerPage);

            let html = `
            <div class="modal fade" id="flowbpmn-templates-modal" tabindex="-1" aria-labelledby="flowbpmnTemplatesModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" style="max-width: 65vw; margin-top: 1.75rem;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="flowbpmnTemplatesModalLabel">
                                <i class="ti ti-template"></i> ${this._t('Template Gallery')}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            
                            <!-- Search Field -->
                            <div class="mb-3 sticky-top bg-white pt-2 pb-2" style="top: -16px; z-index: 5;">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                                    <input type="text" id="flowbpmn-template-search" class="form-control" placeholder="${this._t('Search templates by name...')}">
                                </div>
                            </div>

                            ${templates.length === 0 ?
                    `<div class="alert alert-info">${this._t('No templates found')}</div>` :
                    `<div class="flowbpmn-versions-grid" id="flowbpmn-templates-grid" data-total-pages="${totalPages}" data-current-page="1" data-all-templates='${JSON.stringify(templates).replace(/'/g, "&apos;")}'>
                                    ${templates.slice(0, itemsPerPage).map(t => this.createTemplateCardHTML(t)).join('')}
                                </div>
                                ${totalPages > 1 ? this.createPaginationHTML('templates', totalPages, templates) : ''}`
                }               </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${this._t('Close')}</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Shared Image Preview Modal (Identical to Versions) -->
            <div class="modal fade" id="flowbpmn-image-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body position-relative">
                             <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 10px; right: 10px; z-index: 10;"></button>
                             <div id="flowbpmn-image-container" class="d-flex justify-content-center"></div>
                        </div>
                    </div>
                </div>
            </div>`;

            // Clean old modal
            const oldModal = document.getElementById('flowbpmn-templates-modal');
            if (oldModal) oldModal.remove();

            document.body.insertAdjacentHTML('beforeend', html);

            const modalEl = document.getElementById('flowbpmn-templates-modal');
            const bsModal = new bootstrap.Modal(modalEl);
            bsModal.show();

            // Bind Search Logic
            const searchInput = document.getElementById('flowbpmn-template-search');
            if (searchInput) {
                searchInput.addEventListener('keyup', (e) => {
                    const term = e.target.value.toLowerCase();
                    const cards = document.querySelectorAll('#flowbpmn-templates-grid .flowbpmn-version-card');

                    cards.forEach(card => {
                        const nameEl = card.querySelector('.flowbpmn-version-header strong');
                        const name = nameEl ? nameEl.textContent.toLowerCase() : '';

                        if (name.includes(term)) {
                            card.style.display = 'flex';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });

                // Focus on search
                setTimeout(() => searchInput.focus(), 500);
            }

            // Bind Template Actions
            this.bindTemplateActions();

            // Bind pagination if needed
            if (templates.length > 6) {
                this.bindPaginationEvents('templates', templates);
            }

        } catch (err) {
            console.error('Erro ao listar templates:', err);
            this.showError('Erro ao listar templates: ' + err.message);
        }
    }
    createTemplateCardHTML(template) {
        // Thumbnail logic
        let thumbnail = '<div class="text-muted"><i class="ti ti-photo-off"></i> Sem pré-visualização</div>';
        let svgData = '';

        if (template.svg_content && template.svg_content !== '0' && template.svg_content.length > 50) {
            thumbnail = template.svg_content; // Directly embed SVG
            svgData = encodeURIComponent(template.svg_content);
        } else {
            thumbnail = `<div class="text-muted"><i class="ti ti-photo-off"></i> ${this._t('No preview available')}</div>`;
        }

        const deleteBtn = template.can_delete ?
            `<button type="button" class="btn-flowbpmn-action flowbpmn-delete-template" data-template-id="${template.id}">
                <i class="ti ti-trash"></i> ${this._t('Delete')}
            </button>` : '';

        return `
        <div class="flowbpmn-version-card" id="template-card-${template.id}">
            <div class="flowbpmn-version-preview">
                ${thumbnail}
                <div class="flowbpmn-version-overlay">
                    <button type="button" class="btn-flowbpmn-action flowbpmn-view-image" data-svg="${svgData}">
                        <i class="ti ti-eye"></i> ${this._t('View')}
                    </button>
                    ${deleteBtn}
                </div>
            </div>
            <div class="flowbpmn-version-info">
                <div class="flowbpmn-version-header">
                    <strong>${this.escapeHtml(template.name)}</strong>
                </div>
                
                <div class="flowbpmn-meta" title="${this._t('Modification Date')}">
                    <i class="ti ti-calendar"></i> ${template.date_mod}
                </div>
                <div class="flowbpmn-meta" title="${this._t('Author')}">
                    <i class="ti ti-user"></i> ${template.author_name}
                </div>

                <div class="flowbpmn-actions">
                    <button type="button" class="btn btn-warning w-100 flowbpmn-load-template"
                            data-xml="${template.bpmn_xml}"
                            style="background-color: #FFC107; border: none; color: #212529; font-weight: 500;">
                        <i class="ti ti-check"></i> ${this._t('Apply')}
                    </button>
                </div>
            </div>
        </div>`;
    }

    bindTemplateActions() {
        // Scope to the Modal
        const modalEl = document.getElementById('flowbpmn-templates-modal');
        if (!modalEl) return;

        // Load (Apply) Action
        modalEl.querySelectorAll('.flowbpmn-load-template').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                if (confirm(this._t('This will overwrite the current diagram. Continue?'))) {
                    const base64Xml = e.currentTarget.getAttribute('data-xml');

                    try {        // Decode Base64 (handle UTF-8 correctly)
                        const xml = decodeURIComponent(escape(atob(base64Xml)));

                        // Close Modal
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();

                        await this.loadDiagram(xml);
                        this.showSuccess(this._t('Template loaded successfully!'));
                    } catch (err) {
                        console.error('Error decoding/loading XML:', err);
                        this.showError(this._t('Error loading template') + ': ' + err.message);
                    }
                }
            });
        });

        // View Action
        modalEl.querySelectorAll('.flowbpmn-view-image').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const svgContent = decodeURIComponent(e.currentTarget.dataset.svg);
                if (svgContent) {
                    const container = document.getElementById('flowbpmn-image-container');
                    container.innerHTML = svgContent;

                    // Fix SVG size for modal
                    const svgEl = container.querySelector('svg');
                    if (svgEl) {
                        svgEl.removeAttribute('width');
                        svgEl.removeAttribute('height');
                        svgEl.style.width = '100%';
                        svgEl.style.height = 'auto';
                        svgEl.style.maxWidth = '100%';
                    }

                    const imgModal = new bootstrap.Modal(document.getElementById('flowbpmn-image-modal'));
                    imgModal.show();
                }
            });
        });

        // Delete Template Action
        modalEl.querySelectorAll('.flowbpmn-delete-template').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const id = e.currentTarget.dataset.templateId;
                if (confirm(this._t('Delete this template permanently?'))) {
                    await this.deleteTemplate(id);
                }
            });
        });
    }

    async deleteTemplate(id) {
        try {
            const pluginUrl = this.pluginUrl || '/plugins/flowbpmn';
            const url = `${pluginUrl}/ajax/flow.php?action=delete_template`;

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Glpi-Csrf-Token': this.getCSRFToken()
                },
                body: JSON.stringify({
                    action: 'delete_template',
                    id: id
                })
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess(this._t('Template deleted'));
                // Refresh
                this.showLoadTemplateModal();
            } else {
                throw new Error(result.message);
            }
        } catch (err) {
            this.showError(this._t('Error deleting template') + ': ' + err.message);
        }
    }

    /**
     * Create pagination HTML (GLPI style)
     */
    createPaginationHTML(type, totalPages, allData, canRestore = null) {
        const currentPage = 1;

        let html = `
        <nav aria-label="Page navigation" class="mt-3">
            <ul class="pagination justify-content-center" id="flowbpmn-${type}-pagination">
                <li class="page-item disabled" id="flowbpmn-${type}-prev">
                    <a class="page-link" href="#" tabindex="-1">
                        <i class="ti ti-chevron-left"></i>
                    </a>
                </li>`;

        for (let i = 1; i <= totalPages; i++) {
            html += `
                <li class="page-item ${i === 1 ? 'active' : ''}" data-page="${i}">
                    <a class="page-link" href="#">${i}</a>
                </li>`;
        }

        html += `
                <li class="page-item ${totalPages === 1 ? 'disabled' : ''}" id="flowbpmn-${type}-next">
                    <a class="page-link" href="#">
                        <i class="ti ti-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>`;

        return html;
    }

    /**
     * Bind pagination events
     */
    bindPaginationEvents(type, allData, canRestore = null) {
        const pagination = document.getElementById(`flowbpmn-${type}-pagination`);
        if (!pagination) return;

        const grid = document.getElementById(`flowbpmn-${type}-grid`);
        const itemsPerPage = 6;

        // Page number clicks
        pagination.querySelectorAll('.page-item[data-page]').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(item.dataset.page);
                this.goToPage(type, page, allData, canRestore);
            });
        });

        // Previous button
        const prevBtn = document.getElementById(`flowbpmn-${type}-prev`);
        if (prevBtn) {
            prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const currentPage = parseInt(grid.dataset.currentPage);
                if (currentPage > 1) {
                    this.goToPage(type, currentPage - 1, allData, canRestore);
                }
            });
        }

        // Next button
        const nextBtn = document.getElementById(`flowbpmn-${type}-next`);
        if (nextBtn) {
            nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const currentPage = parseInt(grid.dataset.currentPage);
                const totalPages = parseInt(grid.dataset.totalPages);
                if (currentPage < totalPages) {
                    this.goToPage(type, currentPage + 1, allData, canRestore);
                }
            });
        }
    }

    /**
     * Go to specific page
     */
    goToPage(type, page, allData, canRestore = null) {
        const grid = document.getElementById(`flowbpmn-${type}-grid`);
        const pagination = document.getElementById(`flowbpmn-${type}-pagination`);
        const itemsPerPage = 6;
        const totalPages = parseInt(grid.dataset.totalPages);

        // Update grid
        const start = (page - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        const pageData = allData.slice(start, end);

        if (type === 'versions') {
            grid.innerHTML = pageData.map(v => this.createVersionCard(v, canRestore)).join('');
            this.bindVersionActions(null, canRestore); // Re-bind actions
        } else if (type === 'templates') {
            grid.innerHTML = pageData.map(t => this.createTemplateCardHTML(t)).join('');
            this.bindTemplateActions(); // Re-bind actions
        }

        // Update pagination state
        grid.dataset.currentPage = page;

        // Update active page
        pagination.querySelectorAll('.page-item[data-page]').forEach(item => {
            if (parseInt(item.dataset.page) === page) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        // Update prev/next buttons
        const prevBtn = document.getElementById(`flowbpmn-${type}-prev`);
        const nextBtn = document.getElementById(`flowbpmn-${type}-next`);

        if (prevBtn) {
            if (page === 1) {
                prevBtn.classList.add('disabled');
            } else {
                prevBtn.classList.remove('disabled');
            }
        }

        if (nextBtn) {
            if (page === totalPages) {
                nextBtn.classList.add('disabled');
            } else {
                nextBtn.classList.remove('disabled');
            }
        }

        // Scroll to top of modal
        const modalBody = grid.closest('.modal-body');
        if (modalBody) {
            modalBody.scrollTop = 0;
        }
    }
}

// Export to global scope
window.BpmnFlowEditor = BpmnFlowEditor;
