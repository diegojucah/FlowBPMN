<?php
/**
 * FlowBPMN Plugin - Profile Form Handler (GLPI 10 Compatible)
 */

// Standard GLPI 10 includes
include ('../../../inc/includes.php');

Session::checkRight('profile', UPDATE);

$profile = new PluginFlowbpmnProfile();

if (isset($_POST['update'])) {
    $profiles_id = intval($_POST['profiles_id'] ?? 0);
    
    if ($profiles_id > 0) {
        // Collect all permission fields
        $types = ['ticket', 'problem', 'change'];
        $actions = ['view', 'edit', 'delete', 'restore'];

        $input = ['profiles_id' => $profiles_id];

        foreach ($types as $type) {
            foreach ($actions as $action) {
                $col = 'can_' . $action . '_' . $type;
                // GLPI sends can_*=0 for unchecked, can_*=1 for checked
                $input[$col] = (isset($_POST[$col]) && $_POST[$col] == 1) ? 1 : 0;
            }
        }

        // Get existing rights
        $existing = PluginFlowbpmnProfile::getProfileRights($profiles_id);

        if (isset($existing['id']) && $existing['id'] > 0) {
            $input['id'] = $existing['id'];
            $result = $profile->update($input);
        } else {
            $result = $profile->add($input);
        }

        if ($result) {
            Session::addMessageAfterRedirect(__('Item successfully updated!'), true, INFO);
        } else {
            Session::addMessageAfterRedirect(__('Error updating item.'), false, ERROR);
        }
    }
}

// Redirect back to profile page with FlowBPMN tab
Html::back();
