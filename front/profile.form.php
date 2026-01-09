<?php
/**
 * FlowBPMN Plugin for GLPI 11 - Profile Form Handler
 * FINAL WORKING VERSION
 */

try {
    // GLPI root - don't define, let autoloader do it
    $glpi_root = '/var/www/html/glpi';
    
    // Change to GLPI directory FIRST (required for autoloader)
    chdir($glpi_root);

    // Load autoloader - this will define GLPI_ROOT automatically
    require_once $glpi_root . '/vendor/autoload.php';

    // Initialize Kernel
    $kernel = new Glpi\Kernel\Kernel();

    // Create request
    $request = Symfony\Component\HttpFoundation\Request::createFromGlobals();

    // Handle the request - this initializes Session and everything else
    $response = $kernel->handle($request);

    // Get profile ID for redirect
    $profiles_id = intval($_REQUEST['profiles_id'] ?? 0);

    // Now we can process our form
    if (isset($_REQUEST['update']) && $profiles_id > 0) {
        Session::checkRight('profile', UPDATE);

        // Collect all permission fields
        $types = ['ticket', 'problem', 'change'];
        $actions = ['view', 'edit', 'delete', 'restore'];

        $input = ['profiles_id' => $profiles_id];

        foreach ($types as $type) {
            foreach ($actions as $action) {
                $col = 'can_' . $action . '_' . $type;
                // FIX: Check the VALUE, not just if it's set
                // GLPI sends can_*=0 for unchecked, can_*=1 for checked
                $input[$col] = (isset($_REQUEST[$col]) && $_REQUEST[$col] == 1) ? 1 : 0;
            }
        }

        // Get existing rights
        $existing = PluginFlowbpmnProfile::getProfileRights($profiles_id);

        $profile = new PluginFlowbpmnProfile();

        if (isset($existing['id']) && $existing['id'] > 0) {
            $input['id'] = $existing['id'];
            $result = $profile->update($input);
        } else {
            $result = $profile->add($input);
        }

        if ($result) {
            Session::addMessageAfterRedirect(__s('Item successfully updated!'), true, INFO);
        } else {
            Session::addMessageAfterRedirect(__s('Error updating item.'), false, ERROR);
        }
    }

    // Simple redirect back to profile page with FlowBPMN tab
    $redirect_url = '/front/profile.form.php?id=' . $profiles_id . '&forcetab=PluginFlowbpmnProfile$1';
    header('Location: ' . $redirect_url);
    exit();

} catch (Exception $e) {
    echo "<h1>Exception</h1>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
} catch (Error $e) {
    echo "<h1>PHP Error</h1>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
