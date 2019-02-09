<?php

class CRM_Communicatie_KavaEmailMailingLijst {
  public static function onEnd(CRM_Queue_TaskContext $ctx) {
    CRM_Core_Session::setStatus('Klaar.', 'Queue', 'success');
  }

  public static function add_email_task(CRM_Queue_TaskContext $ctx, $contactID, $emailAddress) {
    self::add_email($contactID, $emailAddress);
    return TRUE;
  }

  public static function remove_email_task(CRM_Queue_TaskContext $ctx, $contactID, $emailAddress) {
    self::remove_email($contactID);
    return TRUE;
  }

  public static function add_email($contactID, $emailAddress) {
    $EmailLocationType = 16; // emailMailinglist

    $params = [
      'contact_id' => $contactID,
      'location_type_id' => $EmailLocationType,
      'email' => $emailAddress,
    ];
    civicrm_api3('Email', 'create', $params);
  }

  public static function remove_email($contactID) {
    $EmailLocationType = 16; // emailMailinglist

    try {
      $params = [
        'contact_id' => $contactID,
        'location_type_id' => $EmailLocationType,
        'sequential' => 1,
      ];
      $email = civicrm_api3('Email', 'getsingle', $params);

      civicrm_api3('Email', 'delete', ['id' => $email['id']]);
    }
    catch (Exception $e) {

    }
  }
}