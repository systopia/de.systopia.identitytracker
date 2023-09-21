<?php
namespace Civi\Identitytracker\Actions;

use \Civi\ActionProvider\Action\AbstractAction;
use Civi\ActionProvider\Exception\ExecutionException;
use \Civi\ActionProvider\Exception\InvalidParameterException;
use Civi\ActionProvider\Parameter\OptionGroupSpecification;
use \Civi\ActionProvider\Parameter\ParameterBagInterface;
use \Civi\ActionProvider\Parameter\SpecificationBag;
use \Civi\ActionProvider\Parameter\Specification;

use Civi\Core\Lock\NullLock;
use CRM_Identitytracker_ExtensionUtil as E;

/**
 * Class to create a new contact identifier (for usage in action-provider/form-processor)
 *
 * @package Civi\Identitytracker\Actions
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @license AGPL-3.0
 */
class CreateIdentifier extends AbstractAction {

  /**
   * @return SpecificationBag
   */
  public function getParameterSpecification() {
    $specs = new SpecificationBag();
    $specs->addSpecification(new Specification('contact_id', 'Integer', E::ts('Contact ID'), TRUE, NULL));
    $specs->addSpecification(new Specification('identifier', 'String', E::ts('Contact Identifier'), TRUE, NULL));
    return $specs;
  }

  /**
   * @return SpecificationBag
   */
  public function getConfigurationSpecification() {
    $specs = new SpecificationBag();
    $specs->addSpecification(new OptionGroupSpecification('identifier_type', 'contact_id_history_type', E::ts('Contact Identity Type'), TRUE));
    return $specs;
  }

  /**
   * Do the actual action - add identifier to contact
   *
   * @param ParameterBagInterface $parameters
   * @param ParameterBagInterface $output
   * @throws ExecutionException
   */
  public function doAction(ParameterBagInterface $parameters, ParameterBagInterface $output) {
    $type = $this->configuration->getParameter('identifier_type');
    try {
      civicrm_api3('Contact', 'addidentity', [
        'contact_id' => $parameters->getParameter('contact_id'),
        'identifier' => $parameters->getParameter('identifier'),
        'identifier_type' => $type,
      ]);
    }
    catch (\CiviCRM_API3_Exception $ex) {
      throw new ExecutionException(E::ts('Error in API Contact addidentity with message: ') . $ex->getMessage());
    }
  }
  /**
   * Returns the specification of the output parameters of this action.
   *
   * This function could be overriden by child classes.
   *
   * @return SpecificationBag
   */
  public function getOutputSpecification() {
    return new SpecificationBag();
  }

}
