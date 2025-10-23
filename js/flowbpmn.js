/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI - JavaScript
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 by KactuX
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/diegojucah/pluginBPMN
 * -------------------------------------------------------------------------
 */

// BPMN.js CDN version
const BPMN_JS_VERSION = '18.6.1';
const BPMN_JS_CDN = `https://cdn.jsdelivr.net/npm/bpmn-js@${BPMN_JS_VERSION}/dist/bpmn-modeler.development.js`;
const BPMN_JS_CSS = `https://cdn.jsdelivr.net/npm/bpmn-js@${BPMN_JS_VERSION}/dist/assets/diagram-js.css`;
const BPMN_FONT_CSS = `https://cdn.jsdelivr.net/npm/bpmn-js@${BPMN_JS_VERSION}/dist/assets/bpmn-js.css`;
const BPMN_FONT = `https://cdn.jsdelivr.net/npm/bpmn-js@${BPMN_JS_VERSION}/dist/assets/bpmn-font/css/bpmn-embedded.css`;

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
        this.modeler = null;
        
        this.init();
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
    }
    
    async loadBpmnJS() {
        if (window.BpmnJS) {
            return Promise.resolve();
        }
        
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = BPMN_JS_CDN;
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
        
        // Export button  
        const exportBtn = document.getElementById('bpmn-export-btn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.showExportModal());
        }
        
        // Versions button
        const versionsBtn = document.getElementById('bpmn-versions-btn');
        if (versionsBtn) {
            versionsBtn.addEventListener('click', () => this.showVersionsModal());
        }
    }
    
    async saveDiagram() {
        try {
            const { xml } = await this.modeler.saveXML({ format: true });
            const { svg } = await this.modeler.saveSVG();
            
            const url = `${this.pluginUrl}/ajax/flow.php`;
            console.log('Saving to:', url);
            
            const response = await fetch(url, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    action: 'save',
                    itemtype: this.itemtype,
                    items_id: this.items_id,
                    bpmn_xml: xml,
                    svg_content: svg,
                    name: document.getElementById('flow-name')?.value || ''
                })
            });
            
            console.log('Response status:', response.status);
            const text = await response.text();
            console.log('Response text:', text);
            
            let result;
            try {
                result = JSON.parse(text);
            } catch (e) {
                throw new Error('Resposta inválida do servidor: ' + text.substring(0, 100));
            }
            
            if (result.success) {
                this.showSuccess('Diagrama BPMN salvo com sucesso!');
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
            
            switch(format) {
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
    
    async exportPNG() {
        const { svg } = await this.modeler.saveSVG();
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const img = new Image();
        
        const svgBlob = new Blob([svg], { type: 'image/svg+xml' });
        const url = URL.createObjectURL(svgBlob);
        
        img.onload = () => {
            canvas.width = img.width;
            canvas.height = img.height;
            ctx.drawImage(img, 0, 0);
            
            canvas.toBlob(blob => {
                this.downloadFile(blob, 'diagram.png', 'image/png');
                URL.revokeObjectURL(url);
            });
        };
        
        img.src = url;
    }
    
    downloadFile(data, filename, mimeType) {
        const blob = data instanceof Blob ? data : new Blob([data], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    
    showVersionsModal() {
        alert('Versions feature - View and restore previous versions');
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
        if (typeof displayAjaxMessageAfterRedirect === 'function') {
            displayAjaxMessageAfterRedirect();
        }
        alert(message); // Fallback
    }
    
    showError(message) {
        alert('Error: ' + message);
    }
}

// Export to global scope
window.BpmnFlowEditor = BpmnFlowEditor;
