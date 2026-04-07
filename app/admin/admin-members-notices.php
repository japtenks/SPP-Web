<?php

if (!function_exists('spp_admin_members_emit_detail_notices')) {
    function spp_admin_members_emit_detail_notices(): void
    {
        $xfer = (string)($_GET['xfer'] ?? '');
        if ($xfer !== '') {
            $xferMessages = array(
                'success' => array('notice', '<b>Character transferred to the target account.</b>'),
                'missing_target' => array('alert', '<b>Target account was not found.</b>'),
                'source_online' => array('alert', '<b>Source account is still online. Log it out before transferring.</b>'),
                'target_online' => array('alert', '<b>Target account is still online. Log it out before transferring.</b>'),
                'char_online' => array('alert', '<b>The selected character is still online. Log it out before transferring.</b>'),
                'same_target' => array('alert', '<b>Target account must be different from the current account.</b>'),
                'missing_character' => array('alert', '<b>That character was not found on this account.</b>'),
                'failed' => array('alert', '<b>Character transfer failed. No changes were saved.</b>'),
            );
            if (isset($xferMessages[$xfer])) {
                output_message($xferMessages[$xfer][0], $xferMessages[$xfer][1]);
            }
        }

        $charDelete = (string)($_GET['chardelete'] ?? '');
        if ($charDelete !== '') {
            $charDeleteMessages = array(
                'success' => array('notice', '<b>Character deleted from the active realm.</b>'),
                'missing' => array('alert', '<b>That character was not found on this account.</b>'),
                'failed' => array('alert', '<b>Character deletion failed. No changes were saved.</b>'),
            );
            if (isset($charDeleteMessages[$charDelete])) {
                output_message($charDeleteMessages[$charDelete][0], $charDeleteMessages[$charDelete][1]);
            }
        }

        $pwreset = (string)($_GET['pwreset'] ?? '');
        if ($pwreset !== '') {
            $pwresetMessages = array(
                '1' => array('notice', '<b>Password changed successfully.</b>'),
                'mismatch' => array('alert', '<b>New password confirmation does not match.</b>'),
                'missing' => array('alert', '<b>Account not found.</b>'),
                'failed' => array('alert', '<b>Password reset failed: SRP values were not saved.</b>'),
            );
            if (isset($pwresetMessages[$pwreset])) {
                output_message($pwresetMessages[$pwreset][0], $pwresetMessages[$pwreset][1]);
            }
        }
    }
}

if (!function_exists('spp_admin_members_emit_list_notices')) {
    function spp_admin_members_emit_list_notices(): void
    {
        if ((string)($_GET['botexp'] ?? '') !== 'normalized') {
            return;
        }

        $normalizedCount = (int)($_GET['count'] ?? 0);
        $normalizedTarget = spp_admin_members_expansion_label((int)($_GET['to'] ?? 0));
        output_message('notice', '<b>Normalized ' . $normalizedCount . ' bot account(s) to ' . htmlspecialchars($normalizedTarget) . '.</b>');
    }
}

