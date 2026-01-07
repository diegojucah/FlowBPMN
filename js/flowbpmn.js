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
        // Expose instance for global access (needed for modal onclick events)
        window.BpmnFlowEditor_instance = this;

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
            this.showError(this._t('Error loading BPMN diagram'));
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

        // Import dropdown options
        const fileInput = document.getElementById('bpmn-file-input');
        const importFromTicketOption = document.getElementById('import-from-ticket-option');
        const importFromProblemOption = document.getElementById('import-from-problem-option');
        // Unified Import Button
        const unifiedImportBtn = document.getElementById('bpmn-import-unified-btn');
        if (unifiedImportBtn) {
            unifiedImportBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.showImportModal();
            });
        }

        // File input (optional if kept for other uses, but modal handles upload now)
        if (fileInput && this.canEdit) {
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
        // Templates Buttons (Priority 4.1) - Event Delegation for robustness inside Dropdowns
        document.addEventListener('click', (e) => {
            const saveBtn = e.target.closest('#bpmn-save-template-btn');
            if (saveBtn) {
                e.preventDefault();
                e.stopPropagation();
                this.saveAsTemplate();
            }
        });

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
                alert(this._t('Please allow popups to export PDF.'));
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
            alert(this._t('Error generating PDF: %s').replace('%s', err.message));
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
            this.showError(this._t('Error saving diagram: %s').replace('%s', err.message));
        }
    }

    async showExportModal() {
        try {
            const format = prompt(this._t('Choose export format:\\n1 - BPMN XML\\n2 - SVG\\n3 - PNG'), '1');

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
                    alert(this._t('Invalid format'));
            }
        } catch (err) {
            this.showError(this._t('Error exporting: %s').replace('%s', err.message));
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

                this.showSuccess(this._t('File saved successfully!'));
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
            this.showError(this._t('Error loading versions: %s').replace('%s', err.message));
        }
    }

    createVersionsModalHTML(data) {
        const versions = data.versions;
        const itemsPerPage = 6;
        const totalPages = Math.ceil(versions.length / itemsPerPage);

        let html = `
        <div class="modal fade" id="flowbpmn-versions-modal" tabindex="-1" aria-labelledby="flowbpmnVersionsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl" style="max-width: 65vw; margin-top: 1.75rem;">
                <div class="modal-content" style="max-height: 60vh; background-color: white !important;">
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
        let thumbnail = '<div class="text-muted"><i class="ti ti-photo-off"></i> ' + this._t('No preview available') + '</div>';
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
        const grid = document.getElementById('flowbpmn-versions-grid');
        if (!grid) return;

        // Use delegation on the grid container
        grid.onclick = (e) => {
            // Restore Action
            const restoreBtn = e.target.closest('.flowbpmn-restore-version');
            if (restoreBtn) {
                e.preventDefault();
                e.stopPropagation();
                const versionId = restoreBtn.dataset.versionId;

                // Direct restore
                this.restoreVersion(currentFlowId, versionId).catch(err => {
                    console.error('Error restoring version:', err);
                    this.showError(this._t('Error restoring version: %s').replace('%s', err.message));
                });
                return;
            }

            // Delete Action
            const deleteBtn = e.target.closest('.flowbpmn-delete-version');
            if (deleteBtn) {
                e.preventDefault();
                e.stopPropagation();
                const versionId = deleteBtn.dataset.versionId;
                this.showConfirmModal(this._t('Are you sure you want to permanently delete this version?'), () => {
                    this.deleteVersion(versionId).catch(err => {
                        console.error('Error deleting version:', err);
                        this.showError(this._t('Error deleting version: %s').replace('%s', err.message));
                    });
                });
                return;
            }

            // View Action
            const viewBtn = e.target.closest('.flowbpmn-view-image');
            if (viewBtn) {
                e.preventDefault();
                e.stopPropagation();
                const svgContent = decodeURIComponent(viewBtn.dataset.svg);

                if (svgContent) {
                    const container = document.getElementById('flowbpmn-image-container');
                    // Ensure container exists (re-fetch if needed)
                    if (!container) {
                        console.error('Image container missing'); return;
                    }

                    container.innerHTML = svgContent;

                    // Fix SVG size
                    const svgEl = container.querySelector('svg');
                    if (svgEl) {
                        svgEl.removeAttribute('width');
                        svgEl.removeAttribute('height');
                        svgEl.style.width = '100%';
                        svgEl.style.height = 'auto';
                        svgEl.style.maxWidth = '100%';
                    }

                    const imgModalEl = document.getElementById('flowbpmn-image-modal');
                    if (imgModalEl) {
                        const imgModal = new bootstrap.Modal(imgModalEl);
                        imgModal.show();
                    }
                } else {
                    alert(this._t('Image unavailable for this version.'));
                }
                return;
            }


        };
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
                this.showSuccess(this._t('Version restored! You can edit and save again.'));

                // Ajustar zoom para caber na tela
                const canvas = this.modeler.get('canvas');
                canvas.zoom('fit-viewport');
            } else {
                throw new Error(result.message || this._t('Failed to restore version'));
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
                this.showError(this._t('Invalid format. Use .bpmn or .xml files'));
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
                    this.showSuccess(this._t('Diagram imported successfully!'));

                    // Limpar input para permitir reimportação do mesmo arquivo
                    event.target.value = '';

                } catch (err) {
                    console.error('Erro ao importar diagrama:', err);
                    this.showError(this._t('Error importing diagram: %s').replace('%s', err.message));
                }
            };

            reader.onerror = () => {
                this.showError(this._t('Error reading file'));
            };

            reader.readAsText(file);

        } catch (err) {
            console.error('Erro no processo de importação:', err);
            this.showError(this._t('Error processing file: %s').replace('%s', err.message));
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
        if (typeof glpi_toast !== 'undefined') {
            glpi_toast('error', message);
        } else {
            console.error(message);
            alert(this._t('Error: %s').replace('%s', message));
        }
    }

    /**
     * Show Confirmation Modal (Replaces native confirm)
     */
    showConfirmModal(message, onConfirm) {
        // Remove existing if any
        const existing = document.getElementById('flowbpmn-confirm-modal');
        if (existing) existing.remove();

        const html = `
        <div class="modal fade" id="flowbpmn-confirm-modal" tabindex="-1" style="z-index: 1070;">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ti ti-alert-circle text-warning"></i> ${this._t('Confirm')}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">${this._t('Cancel')}</button>
                        <button type="button" class="btn btn-danger btn-sm" id="flowbpmn-confirm-btn-yes">${this._t('Confirm')}</button>
                    </div>
                </div>
            </div>
        </div>`;

        document.body.insertAdjacentHTML('beforeend', html);
        const modalEl = document.getElementById('flowbpmn-confirm-modal');
        const modal = new bootstrap.Modal(modalEl);

        document.getElementById('flowbpmn-confirm-btn-yes').onclick = () => {
            modal.hide();
            if (typeof onConfirm === 'function') onConfirm();
        };

        modal.show();
    }

    /**
     * Show Save Template Modal
     */
    async saveAsTemplate() {
        // Create modal if it doesn't exist
        if (!document.getElementById('flowbpmn-save-template-modal')) {
            const modalHTML = this.createSaveTemplateModalHTML();
            document.body.insertAdjacentHTML('beforeend', modalHTML);

            // Bind Save Event
            document.getElementById('flowbpmn-confirm-save-template').addEventListener('click', () => {
                const nameInput = document.getElementById('flowbpmn-template-name-input');
                const name = nameInput.value.trim();
                if (name) {
                    this.performSaveTemplate(name);
                    const modalEl = document.getElementById('flowbpmn-save-template-modal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    modal.hide();
                } else {
                    alert(this._t('Please enter a template name'));
                }
            });
        }

        // Reset input
        document.getElementById('flowbpmn-template-name-input').value = 'Novo Template';

        // Show Modal
        const modal = new bootstrap.Modal(document.getElementById('flowbpmn-save-template-modal'));
        modal.show();
    }

    /**
     * Perform the actual save logic
     */
    async performSaveTemplate(name) {
        try {
            const { xml } = await this.modeler.saveXML({ format: true });
            const { svg } = await this.modeler.saveSVG();

            const root = typeof CFG_GLPI !== 'undefined' ? CFG_GLPI.root_doc : '';
            const url = `${root}/plugins/flowbpmn/ajax/template.php?action=save`;

            console.log('FlowBPMN: Saving template to', url);

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Glpi-Csrf-Token': this.getCSRFToken()
                },
                body: JSON.stringify({
                    action: 'save',
                    name: name,
                    bpmn_xml: xml,
                    svg_content: svg,
                    is_public: 0 // Default private
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccess(this._t('Template saved successfully!'));
            } else {
                throw new Error(result.message);
            }

        } catch (err) {
            console.error('Error saving template:', err);
            this.showError(this._t('Error saving template: %s').replace('%s', err.message));
        }
    }

    createSaveTemplateModalHTML() {
        return `
        <div class="modal fade" id="flowbpmn-save-template-modal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${this._t('Save as Template')}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="flowbpmn-template-name-input" class="form-label">${this._t('Template Name')}</label>
                            <input type="text" class="form-control" id="flowbpmn-template-name-input" placeholder="Ex: Onboarding Process">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${this._t('Cancel')}</button>
                        <button type="button" class="btn btn-primary" id="flowbpmn-confirm-save-template">${this._t('Save')}</button>
                    </div>
                </div>
            </div>
        </div>`;
    }

    /**
     * Show Modal to Load Template
     */
    async showLoadTemplateModal() {
        try {
            const pluginUrl = this.pluginUrl || '/plugins/flowbpmn';
            const url = `${pluginUrl}/ajax/template.php?action=list`;

            const response = await fetch(url);
            const result = await response.json();

            if (!result.success) throw new Error(result.message);

            const templates = result.templates;
            const itemsPerPage = 6;
            const totalPages = Math.ceil(templates.length / itemsPerPage);

            let html = `
            <div class="modal fade" id="flowbpmn-templates-modal" tabindex="-1" aria-labelledby="flowbpmnTemplatesModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" style="max-width: 65vw; margin-top: 1.75rem;">
                    <div class="modal-content" style="max-height: 60vh; background-color: white !important;">
                        <div class="modal-header">
                            <h5 class="modal-title" id="flowbpmnTemplatesModalLabel">
                                <i class="ti ti-template"></i> ${this._t('Template Gallery')}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" style="background-color: #f5f7fa;">
                            
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
            
            <!-- Shared Image Preview Modal (Specific for Templates to avoid ID conflict) -->
            <div class="modal fade" id="flowbpmn-templates-image-modal" tabindex="-1" aria-hidden="true" style="z-index: 1065;">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body position-relative">
                             <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 10px; right: 10px; z-index: 10;"></button>
                             <div id="flowbpmn-templates-image-container" class="d-flex justify-content-center"></div>
                        </div>
                    </div>
                </div>
            </div>`;

            // Clean old modal
            const oldModalEl = document.getElementById('flowbpmn-templates-modal');
            if (oldModalEl) {
                const oldInstance = bootstrap.Modal.getInstance(oldModalEl);
                if (oldInstance) {
                    oldInstance.dispose(); // Unbind events, don't animate hide
                }
                oldModalEl.remove(); // Remove element immediately

                // Manual Backdrop Cleanup (Essential since we skipped hide())
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.paddingRight = '';
                document.body.style.overflow = '';
            }

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
            this.showError(this._t('Error listing templates: %s').replace('%s', err.message));
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
                    <button type="button" class="btn-flowbpmn-action flowbpmn-view-image-template" data-svg="${svgData}">
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
                    <button type="button" class="btn btn-warning w-100 flowbpmn-apply-template"
                            data-template-id="${template.id}"
                            style="background-color: #FFC107; border: none; color: #212529; font-weight: 500;">
                        <i class="ti ti-check"></i> ${this._t('Apply')}
                    </button>
                </div>
            </div>
        </div>`;
    }

    bindTemplateActions() {
        const grid = document.getElementById('flowbpmn-templates-grid');
        if (!grid) {
            return;
        }

        grid.onclick = (e) => {

            // Apply Template
            const applyBtn = e.target.closest('.flowbpmn-apply-template');
            if (applyBtn) {
                console.log('Apply button clicked:', applyBtn.dataset.templateId);
                e.preventDefault();
                e.stopPropagation();
                const id = applyBtn.dataset.templateId;

                // Direct apply
                this.loadTemplate(id).catch(err => {
                    console.error('Error applying template:', err);
                    this.showError(this._t('Error applying template: %s').replace('%s', err.message));
                });
                return;
            }

            // View Image
            const viewBtn = e.target.closest('.flowbpmn-view-image-template');
            if (viewBtn) {
                e.preventDefault();
                e.stopPropagation();
                const svgContent = decodeURIComponent(viewBtn.dataset.svg);

                if (svgContent) {
                    const container = document.getElementById('flowbpmn-templates-image-container');
                    if (!container) return;

                    container.innerHTML = svgContent;

                    const svgEl = container.querySelector('svg');
                    if (svgEl) {
                        svgEl.removeAttribute('width');
                        svgEl.removeAttribute('height');
                        svgEl.style.width = '100%';
                        svgEl.style.height = 'auto';
                        svgEl.style.maxWidth = '100%';
                    }

                    const imgModal = new bootstrap.Modal(document.getElementById('flowbpmn-templates-image-modal'));
                    imgModal.show();
                }
                return;
            }

            // Delete Template
            const deleteBtn = e.target.closest('.flowbpmn-delete-template');
            if (deleteBtn) {
                e.preventDefault();
                e.stopPropagation();
                const id = deleteBtn.dataset.templateId;
                this.showConfirmModal(this._t('Delete this template permanently?'), () => {
                    this.deleteTemplate(id).catch(console.error);
                });
                return;
            }
        };
    }

    /**
     * Load Template by ID
     */
    async loadTemplate(id) {
        // Try to find in grid data first
        const grid = document.getElementById('flowbpmn-templates-grid');
        let xml = null;

        if (grid && grid.dataset.allTemplates) {
            try {
                const templates = JSON.parse(grid.dataset.allTemplates.replace(/&apos;/g, "'"));
                const template = templates.find(t => t.id == id);
                if (template) {
                    // Check if XML is base64 or raw (server usually sends raw in JSON, but let's verify)
                    // If it was Base64 encoded for the attribute, we need to know.
                    // Usually in JSON it's raw string.
                    if (template.bpmn_xml && template.bpmn_xml.startsWith('COMPRESSED::')) {
                        // We might need to decompress on server if JS can't.
                        // But wait, if it's compressed, we can't load it directly?
                        // Actually, duplicate logic from loadDiagramFromItem might be needed or just call server.
                        // Let's assume for now we should fetch it fresh to be safe and handle compression.
                        // OR: we can trust the previous implementation? 
                        // Previous implementation did: decodeURIComponent(escape(atob(base64Xml)))
                        // This implies the attribute HELD a base64 string.
                        // If the JSON holds raw XML, we can just use it.
                        xml = template.bpmn_xml;
                    } else {
                        xml = template.bpmn_xml;
                    }
                }
            } catch (e) {
                console.error('Error parsing templates data', e);
            }
        }

        // If validation or compression is complex, better to fetch from server "load_diagram_from_item" 
        // passing the template ID? No, templates are different table?
        // Let's implement a safe server fetch for templates to ensure we get clean XML.
        // Re-using load_diagram_from_item.php might work if we pass correct type?
        // Templates might be 'PluginFlowbpmnTemplate'?
        // Let's try to just load the XML we have.

        if (xml) {
            // If looks base64'd (no <definitions)
            if (!xml.trim().startsWith('<')) {
                // Try decode
                try {
                    xml = decodeURIComponent(escape(atob(xml)));
                } catch (e) {
                    // Maybe it was already xml?
                }
            }
            await this.loadDiagram(xml);
            this.showSuccess(this._t('Template loaded successfully!'));

            // Close modal
            const modalEl = document.getElementById('flowbpmn-templates-modal');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
            return;
        }

        throw new Error('Template data not found');
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
        } else if (type === 'templates') {
            grid.innerHTML = pageData.map(t => this.createTemplateCardHTML(t)).join('');
        } else if (type.startsWith('import-')) {
            const itemType = type.replace('import-', ''); // e.g. Ticket
            grid.innerHTML = pageData.map(item => this.createImportCardHTML(itemType, item)).join('');
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
    /**
     * Show Image Modal (Standard)
     */
    showImageModal(content) {
        // Remove existing
        let existing = document.getElementById('flowbpmn-image-modal');
        if (existing) existing.remove();

        // Match structure of Versions image modal
        const html = `
        <div class="modal fade" id="flowbpmn-image-modal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body position-relative">
                         <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 10px; right: 10px; z-index: 10;"></button>
                         <div id="flowbpmn-image-container" class="d-flex justify-content-center align-items-center" style="min-height: 400px; padding: 20px; overflow: auto;">
                            ${content}
                         </div>
                    </div>
                </div>
            </div>
        </div>`;

        document.body.insertAdjacentHTML('beforeend', html);
        const el = document.getElementById('flowbpmn-image-modal');

        // Apply SVG resizing logic matches Versions modal
        const container = document.getElementById('flowbpmn-image-container');
        if (container) {
            const svgEl = container.querySelector('svg');
            if (svgEl) {
                svgEl.removeAttribute('width');
                svgEl.removeAttribute('height');
                svgEl.style.width = '100%';
                svgEl.style.height = 'auto'; // Maintain aspect ratio
                svgEl.style.maxWidth = '100%';
                // Ensure it expands
                svgEl.style.minHeight = '300px';
            }
        }

        const modal = new bootstrap.Modal(el);
        modal.show();

        el.addEventListener('hidden.bs.modal', () => {
            el.remove();
        });
    }

    /**
     * Load Diagram from a specific item (Ticket/Problem/Change)
     */
    async loadDiagramFromItem(itemtype, items_id, modalElement) {
        try {
            const root = typeof CFG_GLPI !== 'undefined' ? CFG_GLPI.root_doc : '';
            const url = `${root}/plugins/flowbpmn/ajax/flow.php`;

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Glpi-Csrf-Token': this.getCSRFToken()
                },
                body: JSON.stringify({
                    action: 'get_xml',
                    itemtype: itemtype,
                    items_id: items_id
                })
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || this._t('Failed to load diagram'));
            }

            if (!result.xml) {
                this.showError(this._t('No diagram content found for this item.'));
                return;
            }

            // Load logic
            await this.loadDiagram(result.xml);

            // Close modal
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            if (modalInstance) modalInstance.hide();

            this.showSuccess(this._t('Diagram imported successfully.'));

        } catch (err) {
            console.error('Import Error:', err);
            this.showError(`${this._t('Error importing diagram')}: ${err.message}`);
        }
    }
    /**
     * Helper to create Item Type Card
     */
    createImportTypeCard(type, icon, color) {
        const t = (k) => this._t(k);
        const description = {
            'Ticket': t('Import diagram from existing Tickets'),
            'Problem': t('Import diagram from existing Problems'),
            'Change': t('Import diagram from existing Changes')
        };

        return `
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0 flowbpmn-type-card text-center p-3" role="button" data-type="${type}" style="cursor: pointer; transition: transform 0.2s;">
                <div class="card-body">
                    <i class="ti ${icon} mb-3" style="font-size: 3rem; color: ${color};"></i>
                    <h4 class="card-title fw-bold">${t(type)}</h4>
                    <p class="card-text text-muted small">${description[type] || ''}</p>
                </div>
            </div>
        </div>`;
    }

    /**
     * Show Modal to Import Diagrams (Unified)
     */
    showImportModal() {
        const modalId = 'flowbpmn-import-modal';
        let modal = document.getElementById(modalId);
        if (modal) modal.remove();

        const html = this.createImportModalHTML();
        document.body.insertAdjacentHTML('beforeend', html);

        modal = document.getElementById(modalId);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        this.bindImportEvents(modal);
        this.loadImportGallery('Ticket'); // Load default
    }

    async loadImportGallery(type, search = '') {
        const container = document.getElementById(`flowbpmn-import-grid-${type}`);
        if (!container) return;

        // Show loading state
        if (!search) {
            const searchInput = document.getElementById(`flowbpmn-import-search-${type}`);
            if (searchInput) searchInput.value = '';
        }
        container.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">' + this._t('Loading items...') + '</p></div>';

        const root = typeof CFG_GLPI !== 'undefined' ? CFG_GLPI.root_doc : '';
        const url = `${root}/plugins/flowbpmn/ajax/import_items.php`;

        try {
            const formData = new FormData();
            formData.append('itemtype', type);
            formData.append('search', search);

            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 'X-Glpi-Csrf-Token': this.getCSRFToken() }
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            // Clean old pagination
            const parent = container.parentNode;
            Array.from(parent.children).forEach(child => {
                if (child !== container && (child.classList.contains('flowbpmn-import-pagination-wrapper') || child.querySelector('.pagination'))) {
                    child.remove();
                }
            });

            if (result.items.length === 0) {
                container.innerHTML = `<div class="text-center p-5 text-muted">
                    <i class="ti ti-ghost mb-3" style="font-size: 2rem;"></i>
                    <p>${this._t('No diagram found')}</p>
                </div>`;
                return;
            }

            // Store all data for pagination
            const itemsPerPage = 6;
            const totalPages = Math.ceil(result.items.length / itemsPerPage);

            // Render first page immediately
            this.renderImportPage(type, 1, result.items);

            // Add pagination controls if needed
            if (totalPages > 1) {
                const paginationHtml = this.createPaginationHTML(`import-${type}`, totalPages, result.items);
                const paginationContainer = document.createElement('div');
                paginationContainer.className = 'flowbpmn-import-pagination-wrapper';
                paginationContainer.innerHTML = paginationHtml;
                container.parentNode.appendChild(paginationContainer); // Append AFTER grid

                this.bindPaginationEvents(`import-${type}`, result.items);
            }

        } catch (err) {
            console.error('Error loading gallery:', err);
            container.innerHTML = `<div class="text-center p-5 text-danger">
                <i class="ti ti-alert-triangle mb-2"></i><br>
                ${err.message || this._t('Error loading items')}
            </div>`;
        }
    }

    renderImportPage(type, page, allItems) {
        const container = document.getElementById(`flowbpmn-import-grid-${type}`);
        if (!container) return;

        const itemsPerPage = 6;
        const start = (page - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        const pageItems = allItems.slice(start, end);

        container.className = 'flowbpmn-versions-grid';
        container.style.padding = '0';

        // Ensure container has data attributes for pagination to work
        container.dataset.currentPage = page;
        container.dataset.totalPages = Math.ceil(allItems.length / itemsPerPage);

        container.innerHTML = pageItems.map(item => this.createImportCardHTML(type, item)).join('');

        // Update pagination active state
        const pagination = document.getElementById(`flowbpmn-import-${type}-pagination`);
        if (pagination) {
            pagination.querySelectorAll('.page-item[data-page]').forEach(li => {
                li.classList.toggle('active', parseInt(li.dataset.page) === page);
            });
            // Update prev/next buttons
            const prevBtn = document.getElementById(`flowbpmn-import-${type}-prev`);
            const nextBtn = document.getElementById(`flowbpmn-import-${type}-next`);

            if (prevBtn) prevBtn.classList.toggle('disabled', page === 1);
            if (nextBtn) nextBtn.classList.toggle('disabled', page === Math.ceil(allItems.length / itemsPerPage));
        }
    }

    createImportCardHTML(type, item) {
        // Translate item type (Ticket -> Chamado, Problem -> Problema, Change -> Mudança)
        const translatedType = this._t(type);
        // Simple gray badge with white text
        const badge = `<span class="badge" style="background-color: #6c757d; color: #fff; font-size: 0.85em; padding: 5px 10px; border-radius: 4px;">${translatedType} #${item.id}</span>`;

        // Thumbnail Logic
        let thumbnail = '<div class="text-muted"><i class="ti ti-photo-off"></i> ' + this._t('No preview available') + '</div>';
        let svgData = '';

        if (item.svg_content && item.svg_content !== '0' && item.svg_content.length > 50) {
            thumbnail = item.svg_content; // Directly embed SVG
            svgData = encodeURIComponent(item.svg_content);
        } else {
            thumbnail = `
                 <div class="text-center">
                    <i class="ti ti-file-import mb-2" style="font-size: 2.5rem; opacity: 0.5;"></i>
                    <div class="small">${this._t('No Preview')}</div>
                 </div>`;
        }

        return `
        <div class="flowbpmn-version-card">
            <div class="flowbpmn-version-preview text-muted d-flex align-items-center justify-content-center" style="background: #f8f9fa;">
                 ${thumbnail}
                 <div class="flowbpmn-version-overlay">
                     <button type="button" class="btn-flowbpmn-action flowbpmn-view-import-image" data-svg="${svgData}" ${!svgData ? 'disabled' : ''}>
                        <i class="ti ti-eye"></i> ${this._t('View')}
                    </button>
                 </div>
            </div>
            
            <div class="flowbpmn-version-info">
                <div class="flowbpmn-version-header d-flex justify-content-between align-items-center">
                    ${badge}
                </div>
                <div class="mb-2"><strong>${this.escapeHtml(item.name || this._t('No Title'))}</strong></div>

                <div class="flowbpmn-meta" title="${this._t('Modification Date')}">
                    <i class="ti ti-calendar"></i> ${item.date_mod_formatted}
                </div>
                <div class="flowbpmn-meta" title="${this._t('Author')}">
                    <i class="ti ti-user"></i> ${item.user_name || this._t('Unknown')}
                </div>

                <div class="flowbpmn-actions mt-3">
                    <button type="button" class="btn btn-warning w-100" 
                            style="background-color: #FFC107; border: none; color: #212529; font-weight: 500;"
                            onclick="window.BpmnFlowEditor_instance.loadDiagramFromItem('${type}', ${item.id}, document.getElementById('flowbpmn-import-modal'))">
                        <i class="ti ti-download me-1"></i> ${this._t('Import')}
                    </button>
                </div>
            </div>
        </div>`;
    }

    /**
     * Debounce Search Input
     * @param {string} type - 'Ticket', 'Problem', 'Change'
     * @param {string} value - Search term
     */
    debounceSearch(type, value) {
        if (this.searchTimeout) clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            this.loadImportGallery(type, value);
        }, 500); // 500ms delay
    }

    createImportModalHTML() {
        return `
            <div class="modal fade" id="flowbpmn-import-modal" tabindex="-1" style="z-index: 1060;">
                <div class="modal-dialog modal-xl" style="max-width: 65vw; margin-top: 0.5rem;">
                    <div class="modal-content" style="background-color: white !important;">
                        
                        <!-- Header -->
                        <div class="modal-header d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <h5 class="modal-title m-0">
                                    <i class="ti ti-download me-2"></i>${this._t('Import Diagram')}
                                </h5>
                                
                                <!-- Tabs -->
                                <ul class="nav nav-pills nav-sm" role="tablist">
                                    <li class="nav-item">
                                        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#flowbpmn-panel-Ticket" type="button" onclick="window.BpmnFlowEditor_instance.loadImportGallery('Ticket')">
                                            ${this._t('Tickets')}
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#flowbpmn-panel-Problem" type="button" onclick="window.BpmnFlowEditor_instance.loadImportGallery('Problem')">
                                            ${this._t('Problems')}
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#flowbpmn-panel-Change" type="button" onclick="window.BpmnFlowEditor_instance.loadImportGallery('Change')">
                                            ${this._t('Changes')}
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            
                            <!-- Import Button -->
                            <div class="ms-auto me-3">
                                 <button class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('flowbpmn-import-file-input').click()">
                                    <i class="ti ti-upload"></i> ${this._t('Import from File')}
                                </button>
                            </div>
                            
                            <!-- Close Button -->
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <!-- Body -->
                        <div class="modal-body pb-3" style="background-color: white;">
                             <input type="file" id="flowbpmn-import-file-input" accept=".bpmn,.xml" style="display: none;">
                             
                             <div class="tab-content">
                                ${['Ticket', 'Problem', 'Change'].map((type, index) => `
                                    <div class="tab-pane fade ${index === 0 ? 'show active' : ''}" id="flowbpmn-panel-${type}" role="tabpanel">
                                        <div class="d-flex flex-column">
                                            <!-- Search Bar (Compact Standardized) -->
                                            <div class="mb-2 sticky-top bg-white py-2" style="top: -16px; z-index: 5; margin-left: -1rem; margin-right: -1rem; padding-left: 1rem; padding-right: 1rem; border-bottom: 1px solid #dee2e6;">
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                                                    <input type="text" id="flowbpmn-import-search-${type}" class="form-control" 
                                                           placeholder="${this._t('Search in')} ${this._t(type)}..."
                                                           autocomplete="off"
                                                           oninput="window.BpmnFlowEditor_instance.debounceSearch('${type}', this.value)">
                                                    <span class="input-group-text bg-transparent border-start-0" onclick="document.getElementById('flowbpmn-import-search-${type}').value = ''; window.BpmnFlowEditor_instance.loadImportGallery('${type}','')" style="cursor:pointer; display:none;" id="flowbpmn-clear-search-${type}">
                                                        <i class="ti ti-x text-muted"></i>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <!-- Grid -->
                                            <div>
                                                <div id="flowbpmn-import-grid-${type}" class="container-fluid"></div>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                             </div>
                        </div>
                        
                        <!-- Footer -->
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${this._t('Close')}</button>
                        </div>

                    </div>
                </div>
            </div>`;
    }
    bindImportEvents(modalEl) {
        // File Import Logic
        const fileInput = modalEl.querySelector('#flowbpmn-import-file-input');
        if (fileInput) {
            fileInput.addEventListener('change', () => {
                if (fileInput.files.length === 0) return;
                const file = fileInput.files[0];
                const reader = new FileReader();

                reader.onload = (e) => {
                    this.loadDiagram(e.target.result);
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) modalInstance.hide();
                    this.showSuccess(this._t('Diagram imported from file.'));
                };
                reader.readAsText(file);
            });
        }

        // View Image Delegation
        modalEl.addEventListener('click', (e) => {
            const btn = e.target.closest('.flowbpmn-view-import-image');
            if (btn && !btn.disabled) {
                e.preventDefault();
                e.stopPropagation();
                if (btn.dataset.svg) {
                    this.showImageModal(decodeURIComponent(btn.dataset.svg));
                }
            }
        });
    }

} // End Class

// Export to global scope
window.BpmnFlowEditor = BpmnFlowEditor;
