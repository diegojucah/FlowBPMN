<?php
/**
 * -------------------------------------------------------------------------
 * flowBPMN Plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of flowBPMN.
 *
 * flowBPMN is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * flowBPMN is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with flowBPMN. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 by KactuX
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/diegojucah/pluginBPMN
 * -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Helper class - Provides utility functions for flowBPMN plugin
 *
 * Features:
 * - GLPI version detection and compatibility
 * - XML/SVG sanitization
 * - Document upload helpers
 * - Security functions
 */
class PluginFlowbpmnHelper {

    /**
     * Check if running GLPI 11.x
     *
     * @return bool True if GLPI 11.x or higher
     */
    /**
     * Check if running GLPI 11.x
     * Always returns true as plugin requires GLPI 11+
     *
     * @return bool True
     */
    public static function isGlpi11() {
        return true;
    }

    /**
     * Check if running GLPI 10.x
     * Always returns false as plugin requires GLPI 11+
     *
     * @return bool False
     */
    public static function isGlpi10() {
        return false;
    }

    /**
     * Get appropriate icon based on GLPI version
     *
     * @param string $glpi11Icon Tabler icon for GLPI 11.x (e.g., 'ti ti-diagram')
     * @param string $glpi10Icon FontAwesome icon for GLPI 10.x (e.g., 'fas fa-project-diagram')
     *
     * @return string Icon class
     */
    public static function getIcon($glpi11Icon, $glpi10Icon) {
        return self::isGlpi11() ? $glpi11Icon : $glpi10Icon;
    }

    /**
     * Create tab entry with version-specific format
     *
     * @param string      $title     Tab title
     * @param int         $count     Badge count (optional)
     * @param string|null $itemtype  Item type (GLPI 11.x)
     * @param string|null $icon      Icon class (GLPI 11.x)
     *
     * @return string|array Tab entry
     */
    public static function createTab($title, $count = 0, $itemtype = null, $icon = null) {
        if (self::isGlpi11() && $icon && $itemtype) {
            return CommonGLPI::createTabEntry($title, $count, $itemtype, $icon);
        } else {
            return CommonGLPI::createTabEntry($title, $count);
        }
    }

    /**
     * Sanitize BPMN XML to remove dangerous content
     *
     * @param string $xml BPMN XML content
     *
     * @return string Sanitized XML
     * @throws Exception If XML is invalid
     */
    public static function sanitizeBpmnXml($xml) {
        if (empty($xml)) {
            throw new Exception('Empty XML content');
        }

        // Dangerous tags to remove
        $dangerous_tags = [
            'script', 'iframe', 'embed', 'object',
            'link', 'style', 'meta', 'form', 'input'
        ];

        // Remove dangerous tags
        foreach ($dangerous_tags as $tag) {
            $xml = preg_replace('/<' . $tag . '[^>]*>.*?<\/' . $tag . '>/is', '', $xml);
            $xml = preg_replace('/<' . $tag . '[^>]*\/>/is', '', $xml);
        }

        // Validate XML structure
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        if (!$loaded) {
            throw new Exception('Invalid XML structure');
        }

        // Verify it's BPMN XML
        if ($dom->documentElement->tagName !== 'bpmn:definitions' &&
            $dom->documentElement->tagName !== 'definitions') {
            throw new Exception('Not a valid BPMN XML document');
        }

        return $dom->saveXML();
    }

    /**
     * Sanitize SVG to remove dangerous content
     *
     * @param string $svg SVG content
     *
     * @return string Sanitized SVG
     * @throws Exception If SVG is invalid
     */
    public static function sanitizeSvg($svg) {
        if (empty($svg)) {
            throw new Exception('Empty SVG content');
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($svg, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        if (!$loaded) {
            throw new Exception('Invalid SVG structure');
        }

        // Remove script tags
        $scripts = $dom->getElementsByTagName('script');
        while ($scripts->length > 0) {
            $scripts->item(0)->parentNode->removeChild($scripts->item(0));
        }

        // Remove event handlers (on* attributes)
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//@*[starts-with(name(), "on")]');
        foreach ($nodes as $node) {
            $node->ownerElement->removeAttribute($node->name);
        }

        // Remove javascript: URLs
        $links = $xpath->query('//@*[starts-with(., "javascript:")]');
        foreach ($links as $link) {
            $link->ownerElement->removeAttribute($link->name);
        }

        // Remove data: URLs (can be used for XSS)
        $dataUrls = $xpath->query('//@*[starts-with(., "data:")]');
        foreach ($dataUrls as $dataUrl) {
            $dataUrl->ownerElement->removeAttribute($dataUrl->name);
        }

        return $dom->saveXML();
    }

    /**
     * Upload document with version-specific logic
     *
     * @param mixed  $file_data Binary data or base64 string
     * @param string $filename  Filename
     * @param string $itemtype  Item type (Ticket, Problem, Change)
     * @param int    $items_id  Item ID
     * @param string $name      Document name
     *
     * @return int|false Document ID on success, false on failure
     */
    public static function uploadDocument($file_data, $filename, $itemtype, $items_id, $name = '') {
        if (self::isGlpi11()) {
            return self::uploadDocumentGlpi11($file_data, $filename, $itemtype, $items_id, $name);
        } else {
            return self::uploadDocumentGlpi10($file_data, $filename, $itemtype, $items_id, $name);
        }
    }

    /**
     * Upload document for GLPI 11.x
     *
     * @param mixed  $file_data Binary data
     * @param string $filename  Filename
     * @param string $itemtype  Item type
     * @param int    $items_id  Item ID
     * @param string $name      Document name
     *
     * @return int|false Document ID on success
     */
    private static function uploadDocumentGlpi11($file_data, $filename, $itemtype, $items_id, $name) {
        // Decode base64 if needed
        if (is_string($file_data) && preg_match('/^data:/', $file_data)) {
            $parts = explode(',', $file_data);
            if (count($parts) === 2) {
                $file_data = base64_decode($parts[1]);
            }
        }

        $filepath = GLPI_TMP_DIR . '/' . uniqid() . '_' . $filename;

        if (file_put_contents($filepath, $file_data) === false) {
            error_log('flowBPMN: Failed to write temp file: ' . $filepath);
            return false;
        }

        try {
            // Simulate file upload array structure for GLPI 11
            $_FILES['filename'] = [
                'name' => $filename,
                'tmp_name' => $filepath,
                'size' => filesize($filepath),
                'type' => mime_content_type($filepath),
                'error' => 0
            ];

            $document = new Document();
            $input = [
                '_filename' => [$filename],
                '_only_if_upload_succeed' => true,
                'name' => $name ?: __('flowBPMN Diagram', 'flowbpmn'),
                'users_id' => Session::getLoginUserID(),
                'entities_id' => $_SESSION['glpiactive_entity'] ?? 0,
            ];

            $doc_id = $document->add($input);

            // Clean the simulated upload
            unset($_FILES['filename']);

            if ($doc_id) {
                // Link document to item
                $docItem = new Document_Item();
                $docItem->add([
                    'documents_id' => $doc_id,
                    'itemtype' => $itemtype,
                    'items_id' => $items_id,
                    'users_id' => Session::getLoginUserID(),
                    'entities_id' => $_SESSION['glpiactive_entity'] ?? 0
                ]);

                error_log("flowBPMN: Document uploaded successfully (GLPI 11.x) - ID: $doc_id");
            }

            // Clean up temp file
            @unlink($filepath);

            return $doc_id;

        } catch (Exception $e) {
            error_log('flowBPMN: Upload failed (GLPI 11.x): ' . $e->getMessage());
            @unlink($filepath);
            return false;
        }
    }

    /**
     * Upload document for GLPI 10.x
     *
     * @param mixed  $file_data Binary data
     * @param string $filename  Filename
     * @param string $itemtype  Item type
     * @param int    $items_id  Item ID
     * @param string $name      Document name
     *
     * @return int|false Document ID on success
     */
    private static function uploadDocumentGlpi10($file_data, $filename, $itemtype, $items_id, $name) {
        // Decode base64 if needed
        if (is_string($file_data) && preg_match('/^data:/', $file_data)) {
            $parts = explode(',', $file_data);
            if (count($parts) === 2) {
                $file_data = base64_decode($parts[1]);
            }
        }

        $filepath = GLPI_TMP_DIR . '/' . uniqid() . '_' . $filename;

        if (file_put_contents($filepath, $file_data) === false) {
            error_log('flowBPMN: Failed to write temp file: ' . $filepath);
            return false;
        }

        try {
            $document = new Document();
            $input = [
                'itemtype' => $itemtype,
                'items_id' => $items_id,
                'name' => $name ?: __('flowBPMN Diagram', 'flowbpmn'),
                'filename' => $filename,
                'filepath' => $filepath,
                'mime' => mime_content_type($filepath),
                'users_id' => Session::getLoginUserID(),
            ];

            // Special handling for Tickets in GLPI 10.x
            if ($itemtype == 'Ticket') {
                $input['tickets_id'] = $items_id;
            }

            $doc_id = $document->add($input);

            if ($doc_id) {
                // Link document to item
                $docItem = new Document_Item();
                $docItem->add([
                    'documents_id' => $doc_id,
                    'itemtype' => $itemtype,
                    'items_id' => $items_id,
                    'users_id' => Session::getLoginUserID()
                ]);

                error_log("flowBPMN: Document uploaded successfully (GLPI 10.x) - ID: $doc_id");
            }

            // Clean up temp file
            @unlink($filepath);

            return $doc_id;

        } catch (Exception $e) {
            error_log('flowBPMN: Upload failed (GLPI 10.x): ' . $e->getMessage());
            @unlink($filepath);
            return false;
        }
    }

    /**
     * Validate CSRF token
     *
     * @param array $request Request data
     *
     * @return bool True if valid
     * @throws Exception If CSRF validation fails
     */
    public static function validateCSRF($request) {
        try {
            Session::checkCSRF($request);
            return true;
        } catch (Exception $e) {
            error_log('flowBPMN: CSRF validation failed - ' . $e->getMessage());
            throw new Exception('CSRF validation failed');
        }
    }

    /**
     * Check if user can edit flow for itemtype
     *
     * @param string $itemtype Item type (Ticket, Problem, Change)
     *
     * @return bool True if user can edit
     */
    public static function canEditFlow($itemtype) {
        return PluginFlowbpmnProfile::canEditFlow($itemtype);
    }

    /**
     * Check if user can view flow for itemtype
     *
     * @param string $itemtype Item type (Ticket, Problem, Change)
     *
     * @return bool True if user can view
     */
    public static function canViewFlow($itemtype) {
        return PluginFlowbpmnProfile::canViewFlow($itemtype);
    }

    /**
     * Check if user can delete flow for itemtype
     *
     * @param string $itemtype Item type (Ticket, Problem, Change)
     *
     * @return bool True if user can delete
     */
    public static function canDeleteFlow($itemtype) {
        return PluginFlowbpmnProfile::canDeleteFlow($itemtype);
    }

    /**
     * Check if user can restore flow versions for itemtype
     *
     * @param string $itemtype Item type (Ticket, Problem, Change)
     *
     * @return bool True if user can restore
     */
    public static function canRestoreFlow($itemtype) {
        return PluginFlowbpmnProfile::canRestoreFlow($itemtype);
    }

    /**
     * Log message with context
     *
     * @param string $level   Log level (debug, info, warning, error)
     * @param string $message Log message
     * @param array  $context Additional context
     *
     * @return void
     */
    public static function log($level, $message, $context = []) {
        // Only log in debug mode
        if (!isset($_SESSION['glpi_use_mode']) || $_SESSION['glpi_use_mode'] != Session::DEBUG_MODE) {
            return;
        }

        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => strtoupper($level),
            'user' => $_SESSION['glpiname'] ?? 'anonymous',
            'message' => $message,
            'context' => $context
        ];

        $log_line = 'flowBPMN [' . $log_entry['level'] . '] ' .
                    $log_entry['timestamp'] . ' - ' .
                    $log_entry['user'] . ' - ' .
                    $log_entry['message'];

        if (!empty($context)) {
            $log_line .= ' | Context: ' . json_encode($context);
        }

        error_log($log_line);
    }

    /**
     * Get current entity ID
     *
     * @return int Entity ID
     */
    public static function getCurrentEntity() {
        return $_SESSION['glpiactive_entity'] ?? 0;
    }

    /**
     * Check if item belongs to current entity
     *
     * @param CommonDBTM $item Item to check
     *
     * @return bool True if item belongs to current entity
     */
    public static function checkEntity($item) {
        if (!isset($item->fields['entities_id'])) {
            return true; // No entity field
        }

        $current_entity = self::getCurrentEntity();
        $item_entity = $item->fields['entities_id'];

        // Check if in same entity or recursive
        if ($item_entity == $current_entity) {
            return true;
        }

        // Check recursive entities
        if (isset($item->fields['is_recursive']) && $item->fields['is_recursive']) {
            $entities = getSonsOf('glpi_entities', $item_entity);
            return in_array($current_entity, $entities);
        }

        return false;
    }
}
