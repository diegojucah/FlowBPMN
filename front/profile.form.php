<?php
include ('../../../inc/includes.php');

// Check rights
Session::checkRight("profile", UPDATE);

$profile = new PluginFlowbpmnProfile();

if (isset($_POST["update"])) {
    // Determine plugin profile record ID.
    // If 'id' is sent in POST (plugin profile id), use it.
    // Otherwise try to find by profiles_id.
    
    if (empty($_POST['id'])) {
        // Fallback: Find ID by profiles_id
        $rights = PluginFlowbpmnProfile::getProfileRights($_POST['profiles_id']);
        if (isset($rights['id'])) {
            $_POST['id'] = $rights['id'];
            $profile->update($_POST);
        } else {
             // Create new rights record for this profile
             unset($_POST['id']);
             $profile->add($_POST);
        }
    } else {
        $profile->update($_POST);
    }
    
    Html::back();
} else {
    Html::back();
}
