<?php

namespace Drupal\ambientimpact_core\EventSubscriber;

use Drupal\ambientimpact_core\ContainerAwareEventSubscriber;

use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\hook_event_dispatcher\Event\Theme\LibraryInfoAlterEvent;

/**
 * hook_library_info_alter() Modernizr event subscriber class.
 *
 * This replaces Drupal core's Modernizr with our own. We implement all the
 * options core's does, plus others. Note that we only do this if the core
 * Modernizr path is used, so as not to replace another module's override.
 */
class HookLibraryInfoAlterModernizrEventSubscriber
extends ContainerAwareEventSubscriber {
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::LIBRARY_INFO_ALTER => 'libraryInfoAlter',
    ];
  }

  /**
   * Replace core's Modernizr with our own.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Theme\LibraryInfoAlterEvent $event
   *   The event object.
   */
  public function libraryInfoAlter(LibraryInfoAlterEvent $event) {
    $extension          = $event->getExtension();
    $coreModernizrPath  = 'assets/vendor/modernizr/modernizr.min.js';

    // Ignore anything other than Drupal core.
    if ($extension !== 'core') {
      return;
    }

    $libraries = &$event->getLibraries();

    // Don't do anything if the core Modernizr path doesn't match the default,
    // as that could indicate it's been altered by other code.
    if (!isset($libraries['modernizr']['js'][$coreModernizrPath])) {
      return;
    }

    $moduleHandler    = $this->container->get('module_handler');
    $ourModernizrPath = '../' . $moduleHandler
      ->getModule('ambientimpact_core')->getPath() . '/' . $coreModernizrPath;

    // Save the settings core's uses.
    $libraries['modernizr']['js'][$ourModernizrPath] =
      $libraries['modernizr']['js'][$coreModernizrPath];

    // Remove the core path.
    unset($libraries['modernizr']['js'][$coreModernizrPath]);

    // This is the version that Grunt Modernizr has pulled on 2019-03-02.
    // @todo Can this be read from the file?
    $libraries['modernizr']['version'] = 'v3.5.0';
  }
}