#!/bin/bash
curl -X POST http://localhost/plugins/flowbpmn/ajax/flow.php \
-H "Content-Type: application/json" \
-d '{
  "action": "save",
  "itemtype": "Ticket",
  "items_id": 1,
  "bpmn_xml": "<?xml version=\"1.0\" encoding=\"UTF-8\"?><bpmn:definitions>TEST_XML_V14</bpmn:definitions>",
  "svg_content": "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"100\" height=\"100\"><circle cx=\"50\" cy=\"50\" r=\"40\"/></svg>",
  "name": "Teste Backend V14"
}'
echo ""
