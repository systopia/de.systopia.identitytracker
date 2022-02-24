<?php
namespace Civi\Identitytracker;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use CRM_Identitytracker_ExtensionUtil as E;

/**
 * Compiler Class for action provider
 */
class CompilerPass implements CompilerPassInterface {

  public function process(ContainerBuilder $container) {
    if ($container->hasDefinition('action_provider')) {
      $actionProviderDefinition = $container->getDefinition('action_provider');
      $actionProviderDefinition->addMethodCall('addAction',
        ['CreateIdentifier', 'Civi\Identitytracker\Actions\CreateIdentifier', E::ts('Create Contact Identity'), []]);
    }
  }
}
