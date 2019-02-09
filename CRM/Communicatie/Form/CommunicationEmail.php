<?php

use CRM_Communicatie_ExtensionUtil as E;

class CRM_Communicatie_Form_CommunicationEmail extends CRM_Core_Form {
  private $queue;
  private $queueName = 'kavacommunicatieemail';

  public function __construct() {
    // create the queue
    $this->queue = CRM_Queue_Service::singleton()->create([
      'type' => 'Sql',
      'name' => $this->queueName,
      'reset' => FALSE, //do not flush queue upon creation
    ]);

    parent::__construct();
  }

  public function buildQuickForm() {
    $actions = [
      'add' => '<strong>toevoegen</strong> aan contacten met communicatievoorkeuren',
      'remove' => '<strong>verwijderen</strong> uit contacten zonder communicatievoorkeuren',
    ];
    $this->addRadio('action', 'E-mailadres Mailinglijst:', $actions, NULL, '<br>');

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Uitvoeren'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  public function postProcess() {
    $EmailLocationType = 16; // emailMailinglist
    $sql = "";

    // get submitted values
    $values = $this->exportValues();

    if ($values['action'] == 'add') {
      // selecteer contacten MET communicatievinkjes, ZONDER e-mail MailingLijst
      $sql = "
        select
          *
        from
          civicrm_value_kava_communic_76 comm
        where
          ifnull(comm.kava_communicatiediensten_253, '') = ''
        and 
          not exists (
            select * from civicrm_email em where em.contact_id = comm.entity_id and em.location_type_id = $EmailLocationType  
          )
      ";
    }
    elseif ($values['action'] == 'remove') {
      // selecteer contacten ZONDER communicatievinkjes, MET e-mail MailingLijst
      $sql = "
        select
          *
        from
          civicrm_value_kava_communic_76 comm
        where
          ifnull(comm.kava_communicatiediensten_253, '') = ''
        and 
          not exists (
            select * from civicrm_email em where em.contact_id = comm.entity_id and em.location_type_id = $EmailLocationType  
          )
      ";
    }

    if ($sql) {
      // clear the queue
      $this->queue->deleteQueue();

      // add the queue items
      $dao = CRM_Core_DAO::executeQuery($sql);
      while ($dao->fetch()) {
        $method = $values['action'] . '_email_task';
        $task = new CRM_Queue_Task(['CRM_Communicatie_KavaEmailMailingLijst', $method], [$dao->id, $dao->email]);
        $this->queue->createItem($task);
      }

      // run the queue
      $runner = new CRM_Queue_Runner([
        'title' => 'KAVA Email Mailinglijst',
        'queue' => $this->queue,
        'errorMode'=> CRM_Queue_Runner::ERROR_CONTINUE,
        'onEnd' => ['CRM_Communicatie_KavaEmailMailingLijst', 'onEnd'],
        'onEndUrl' => CRM_Utils_System::url('civicrm/kava-communicatie-email', 'reset=1'),
      ]);
      $runner->runAllViaWeb();
    }

    parent::postProcess();
  }

  public function getRenderableElementNames() {
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
