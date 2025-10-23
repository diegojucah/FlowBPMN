<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Base class for Flow BPMN plugin
 */
class PluginFlowbpmn extends CommonDBTM {
    
    // Table name for the plugin
    public static $table_name = 'glpi_plugin_flowbpmn_flows';
    public static $rightname = 'plugin_flowbpmn';
    
    /**
     * Get the standard name of the item type
     *
     * @param int $nb Number of items
     * @return string Name of the item type
     */
    public static function getTypeName($nb = 0) {
        return _n('BPMN Flow', 'BPMN Flows', $nb, 'flowbpmn');
    }
    
    /**
     * Get the tab name for an item
     *
     * @param CommonGLPI $item Item to get the tab for
     * @param int $withtemplate Whether it's a template
     * @return array|string Tab name
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() == 'Ticket') {
            $nb = countElementsInTable(
                self::$table_name,
                [
                    'itemtype' => 'Ticket',
                    'items_id' => $item->getID()
                ]
            );
            return self::createTabEntry(
                __('BPMN', 'flowbpmn'),
                $nb
            );
        }
        return '';
    }
    
    /**
     * Display the content of the tab
     *
     * @param CommonGLPI $item Item being displayed
     * @param int $tabnum Tab number
     * @param int $withtemplate Whether it's a template
     * @return bool True if the tab is displayed
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == 'Ticket') {
            self::showForTicket($item);
            return true;
        }
        return false;
    }
    
    /**
     * Display the BPMN editor for a ticket
     *
     * @param Ticket $ticket The ticket to display the BPMN editor for
     */
    public static function showForTicket(Ticket $ticket) {
        global $CFG_GLPI;
        
        // Get existing BPMN data if it exists
        $bpmnData = self::getBPMNForItem($ticket);
        $bpmnXML = $bpmnData['bpmn'] ?? '';
        
        // Generate a unique ID for this instance
        $editorId = 'bpmn-editor-' . $ticket->getID();
        
        // Display the editor container
        echo "<div id='$editorId' class='bpmn-editor-container' style='width: 100%; height: 600px; border: 1px solid #ddd;'></div>";
        
        // Add the BPMN.js library and initialization code
        echo "
        <script src='https://cdn.jsdelivr.net/npm/bpmn-js@14.0.1/dist/bpmn-navigated-viewer.development.js'></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const container = document.getElementById('$editorId');
                const viewer = new BpmnJS({ container });
                
                // Default BPMN XML if none exists
                const defaultBPMN = `
                    <?xml version=\"1.0\" encoding=\"UTF-8\"?>
                    <bpmn:definitions xmlns:bpmn=\"http://www.omg.org/spec/BPMN/20100524/MODEL\"
                                     xmlns:bpmndi=\"http://www.omg.org/spec/BPMN/20100524/DI\"
                                     id=\"Definitions_1\"
                                     targetNamespace=\"http://bpmn.io/schema/bpmn\">
                      <bpmn:process id=\"Process_1\" isExecutable=\"false\">
                        <bpmn:startEvent id=\"StartEvent_1\" />
                      </bpmn:process>
                      <bpmndi:BPMNDiagram id=\"BPMNDiagram_1\">
                        <bpmndi:BPMNPlane id=\"BPMNPlane_1\" bpmnElement=\"Process_1\">
                          <bpmndi:BPMNShape id=\"_BPMNShape_StartEvent_2\" bpmnElement=\"StartEvent_1\">
                            <dc:Bounds x=\"179\" y=\"99\" width=\"36\" height=\"36\" />
                          </bpmndi:BPMNShape>
                        </bpmndi:BPMNPlane>
                      </bpmndi:BPMNDiagram>
                    </bpmn:definitions>
                `;
                
                // Load the BPMN diagram
                const bpmnXML = `" . addslashes($bpmnXML) . "` || defaultBPMN;
                
                viewer.importXML(bpmnXML)
                    .then(() => {
                        viewer.get('canvas').zoom('fit-viewport');
                    })
                    .catch(err => {
                        console.error('Error rendering BPMN diagram:', err);
                        container.innerHTML = '<div class=\'alert alert-important alert-warning\'>' +
                            __('Error loading BPMN diagram', 'flowbpmn') + '</div>';
                    });
                
                // Add save button
                const saveButton = document.createElement('button');
                saveButton.className = 'btn btn-primary mt-2';
                saveButton.textContent = '" . __('Save BPMN', 'flowbpmn') . "';
                saveButton.onclick = function() {
                    viewer.saveXML({ format: true }, function(err, xml) {
                        if (err) {
                            console.error('Error saving BPMN:', err);
                            alert('" . __('Error saving BPMN diagram', 'flowbpmn') . "');
                            return;
                        }
                        
                        // Save the BPMN XML via AJAX
                        $.ajax({
                            url: '" . $CFG_GLPI['root_doc'] . "/plugins/flowbpmn/front/bpmn.save.php" . "',
                            method: 'POST',
                            data: {
                                itemtype: 'Ticket',
                                items_id: '" . $ticket->getID() . "',
                                bpmn: xml
                            },
                            success: function(response) {
                                if (response.success) {
                                    displayAjaxMessageAfterRedirect();
                                } else {
                                    alert('" . __('Error saving BPMN diagram', 'flowbpmn') . "');
                                }
                            },
                            error: function() {
                                alert('" . __('Error saving BPMN diagram', 'flowbpmn') . "');
                            }
                        });
                    });
                };
                
                container.parentNode.insertBefore(saveButton, container.nextSibling);
            });
        </script>
        ";
    }
    
    /**
     * Get BPMN data for an item
     *
     * @param CommonDBTM $item Item to get BPMN data for
     * @return array BPMN data
     */
    public static function getBPMNForItem(CommonDBTM $item) {
        global $DB;
        
        $iterator = $DB->request([
            'FROM'   => self::$table_name,
            'WHERE'  => [
                'itemtype' => $item->getType(),
                'items_id' => $item->getID()
            ],
            'ORDER'  => 'date_mod DESC',
            'LIMIT'  => 1
        ]);
        
        if (count($iterator)) {
            return $iterator->current();
        }
        
        return [];
    }
}
