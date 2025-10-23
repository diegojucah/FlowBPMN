// BPMN Editor using bpmn-js
class BPMNEditor {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Container with id "${containerId}" not found`);
            return;
        }

        this.options = {
            height: '500px',
            ...options
        };

        this.initEditor();
    }

    async initEditor() {
        // Create editor container
        this.container.innerHTML = `
            <div class="bpmn-editor-container" style="height: ${this.options.height};">
                <div class="bpmn-toolbar">
                    <button class="btn btn-primary" id="save-bpmn">
                        <i class="fas fa-save"></i> Save
                    </button>
                    <button class="btn btn-secondary" id="export-bpmn">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <div class="version-control">
                        <button class="btn btn-outline-secondary" id="version-history">
                            <i class="fas fa-history"></i> Versions
                        </button>
                    </div>
                </div>
                <div class="bpmn-canvas" style="height: calc(100% - 40px);"></div>
            </div>
        `;

        // Load bpmn-js
        await this.loadScripts();
        
        // Initialize BPMN viewer/editor
        this.initBPMNViewer();
        this.bindEvents();
    }

    loadScripts() {
        return new Promise((resolve) => {
            // Check if bpmn-js is already loaded
            if (window.BpmnJS) {
                resolve();
                return;
            }

            // Load bpmn-js CSS
            const cssLink = document.createElement('link');
            cssLink.rel = 'stylesheet';
            cssLink.href = 'vendor/npm-asset/bpmn-js/dist/assets/diagram-js.css';
            document.head.appendChild(cssLink);

            // Load bpmn-js script
            const script = document.createElement('script');
            script.src = 'vendor/npm-asset/bpmn-js/dist/bpmn-navigated-viewer.development.js';
            script.onload = () => {
                // Load additional required scripts
                const bpmnModelerScript = document.createElement('script');
                bpmnModelerScript.src = 'vendor/npm-asset/bpmn-js/dist/bpmn-modeler.development.js';
                bpmnModelerScript.onload = resolve;
                document.head.appendChild(bpmnModelerScript);
            };
            document.head.appendChild(script);
        });
    }

    initBPMNViewer() {
        // Initialize BPMN Modeler
        this.bpmnModeler = new BpmnJS({
            container: this.container.querySelector('.bpmn-canvas'),
            keyboard: {
                bindTo: document
            },
            additionalModules: [
                // Add any additional modules here
            ]
        });

        // Load default diagram
        this.loadDefaultDiagram();
    }

    async loadDefaultDiagram() {
        try {
            const response = await fetch('vendor/flowBPMN/bpmn/default.bpmn');
            const xml = await response.text();
            await this.bpmnModeler.importXML(xml);
            console.log('BPMN diagram loaded');
        } catch (err) {
            console.error('Error loading BPMN diagram:', err);
            // Create a new empty diagram if loading fails
            await this.createNewDiagram();
        }
    }

    async createNewDiagram() {
        const newDiagram = `<?xml version="1.0" encoding="UTF-8"?>
        <bpmn:definitions 
            xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
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

        await this.bpmnModeler.importXML(newDiagram);
    }

    bindEvents() {
        // Save button click handler
        this.container.querySelector('#save-bpmn').addEventListener('click', () => this.saveDiagram());
        
        // Export button click handler
        this.container.querySelector('#export-bpmn').addEventListener('click', () => this.exportDiagram());
        
        // Version history button click handler
        this.container.querySelector('#version-history').addEventListener('click', () => this.showVersionHistory());
    }

    async saveDiagram() {
        try {
            const { svg } = await this.bpmnModeler.saveSVG();
            const { xml } = await this.bpmnModeler.saveXML({ format: true });
            
            // Save to server
            const response = await fetch('../ajax/bpmn_save.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    bpmn: xml,
                    svg: svg,
                    itemtype: this.options.itemtype,
                    items_id: this.options.items_id
                })
            });

            const result = await response.json();
            
            if (result.success) {
                this.showNotification('BPMN diagram saved successfully', 'success');
                // Update the ticket/change/problem with the SVG preview
                this.updateItemWithPreview(svg);
            } else {
                throw new Error(result.message || 'Failed to save BPMN diagram');
            }
        } catch (err) {
            console.error('Error saving BPMN diagram:', err);
            this.showNotification('Error saving BPMN diagram: ' + err.message, 'error');
        }
    }

    async exportDiagram(format = 'svg') {
        try {
            let blob, filename;
            
            if (format === 'svg') {
                const { svg } = await this.bpmnModeler.saveSVG();
                blob = new Blob([svg], { type: 'image/svg+xml' });
                filename = 'diagram.svg';
            } else if (format === 'bpmn') {
                const { xml } = await this.bpmnModeler.saveXML({ format: true });
                blob = new Blob([xml], { type: 'application/xml' });
                filename = 'diagram.bpmn';
            } else if (format === 'png') {
                const { svg } = await this.bpmnModeler.saveSVG();
                const img = new Image();
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                
                const imgLoad = new Promise((resolve) => {
                    img.onload = resolve;
                    const blob = new Blob([svg], { type: 'image/svg+xml' });
                    img.src = URL.createObjectURL(blob);
                });
                
                await imgLoad;
                
                canvas.width = img.width;
                canvas.height = img.height;
                ctx.drawImage(img, 0, 0);
                
                blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
                filename = 'diagram.png';
            }
            
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
        } catch (err) {
            console.error('Error exporting diagram:', err);
            this.showNotification('Error exporting diagram: ' + err.message, 'error');
        }
    }

    showVersionHistory() {
        // Implement version history modal
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'bpmn-version-history';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Version History</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="version-list">
                            <!-- Versions will be loaded here -->
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p>Loading versions...</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        $(modal).modal('show');
        
        // Load versions
        this.loadVersions(modal);
        
        // Cleanup on modal close
        $(modal).on('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }
    
    async loadVersions(modal) {
        try {
            const response = await fetch(`../ajax/bpmn_versions.php?itemtype=${this.options.itemtype}&items_id=${this.options.items_id}`);
            const versions = await response.json();
            
            const versionsList = modal.querySelector('.version-list');
            
            if (!versions || versions.length === 0) {
                versionsList.innerHTML = '<div class="alert alert-info">No versions found</div>';
                return;
            }
            
            versionsList.innerHTML = `
                <div class="list-group">
                    ${versions.map((version, index) => `
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Version ${versions.length - index}</h6>
                                    <small class="text-muted">${new Date(version.date_mod).toLocaleString()}</small>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary view-version" data-id="${version.id}">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button class="btn btn-sm btn-outline-success restore-version" data-id="${version.id}">
                                        <i class="fas fa-undo"></i> Restore
                                    </button>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
            
            // Add event listeners for view/restore buttons
            modal.querySelectorAll('.view-version').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const versionId = e.target.closest('button').dataset.id;
                    this.viewVersion(versionId);
                });
            });
            
            modal.querySelectorAll('.restore-version').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const versionId = e.target.closest('button').dataset.id;
                    this.restoreVersion(versionId);
                });
            });
            
        } catch (err) {
            console.error('Error loading versions:', err);
            const versionsList = modal.querySelector('.version-list');
            versionsList.innerHTML = `
                <div class="alert alert-danger">
                    Error loading versions: ${err.message}
                </div>
            `;
        }
    }
    
    async viewVersion(versionId) {
        try {
            const response = await fetch(`../ajax/bpmn_version.php?id=${versionId}`);
            const version = await response.json();
            
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = 'bpmn-version-viewer';
            modal.innerHTML = `
                <div class="modal-dialog modal-xl" style="width: 90%; max-width: 1200px; height: 90vh;">
                    <div class="modal-content h-100">
                        <div class="modal-header">
                            <h5 class="modal-title">Version ${version.id}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body" style="height: calc(100% - 60px);">
                            <div class="h-100" id="version-viewer-container"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary restore-version" data-id="${version.id}">
                                <i class="fas fa-undo"></i> Restore this version
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            $(modal).modal('show');
            
            // Initialize BPMN viewer for this version
            const viewer = new BpmnJS({
                container: '#version-viewer-container'
            });
            
            await viewer.importXML(version.bpmn);
            
            // Add restore button handler
            modal.querySelector('.restore-version').addEventListener('click', () => {
                this.restoreVersion(version.id);
                $(modal).modal('hide');
            });
            
            // Cleanup on modal close
            $(modal).on('hidden.bs.modal', () => {
                viewer.destroy();
                document.body.removeChild(modal);
            });
            
        } catch (err) {
            console.error('Error viewing version:', err);
            this.showNotification('Error viewing version: ' + err.message, 'error');
        }
    }
    
    async restoreVersion(versionId) {
        if (!confirm('Are you sure you want to restore this version? Any unsaved changes will be lost.')) {
            return;
        }
        
        try {
            const response = await fetch(`../ajax/bpmn_restore.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: versionId,
                    itemtype: this.options.itemtype,
                    items_id: this.options.items_id
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Version restored successfully', 'success');
                // Reload the BPMN editor with the restored version
                await this.bpmnModeler.importXML(result.bpmn);
                // Close any open modals
                $('.modal').modal('hide');
            } else {
                throw new Error(result.message || 'Failed to restore version');
            }
        } catch (err) {
            console.error('Error restoring version:', err);
            this.showNotification('Error restoring version: ' + err.message, 'error');
        }
    }
    
    updateItemWithPreview(svg) {
        // This method will be implemented to update the ticket/change/problem with the SVG preview
        console.log('Updating item with BPMN preview');
        // Implementation will depend on how you want to display the preview in the item
    }
    
    showNotification(message, type = 'info') {
        // Use GLPI's notification system if available
        if (window.GlpiHtmlNotification) {
            const notification = new GlpiHtmlNotification();
            notification.show({
                message: message,
                type: type
            });
        } else {
            // Fallback to simple alert
            alert(`${type.toUpperCase()}: ${message}`);
        }
    }
}

// Initialize the editor when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Check if we're on the BPMN tab of an item
    const bpmnContainer = document.getElementById('bpmn-editor');
    if (bpmnContainer) {
        // Extract item type and ID from the URL or data attributes
        const urlParams = new URLSearchParams(window.location.search);
        const itemtype = urlParams.get('itemtype') || bpmnContainer.dataset.itemtype;
        const items_id = urlParams.get('id') || bpmnContainer.dataset.itemsId;
        
        // Initialize the BPMN editor
        window.bpmnEditor = new BPMNEditor('bpmn-editor', {
            itemtype: itemtype,
            items_id: items_id,
            height: '800px'
        });
    }
});
