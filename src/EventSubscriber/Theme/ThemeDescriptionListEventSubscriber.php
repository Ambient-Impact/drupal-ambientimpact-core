<?php

namespace Drupal\ambientimpact_core\EventSubscriber\Theme;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\core_event_dispatcher\Event\Theme\ThemeEvent;
use Drupal\core_event_dispatcher\ThemeHookEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * hook_theme() event subscriber class to define 'description_list' element.
 *
 * @deprecated in 2.x; will be removed in 3.0.
 *
 * @see https://www.drupal.org/project/description_list
 */
class ThemeDescriptionListEventSubscriber implements EventSubscriberInterface {
  /**
   * The Drupal module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The Drupal module handler service.
   */
  public function __construct(
    ModuleHandlerInterface $moduleHandler
  ) {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ThemeHookEvents::THEME => 'theme',
    ];
  }

  /**
   * Defines the 'description_list' theme element.
   *
   * @param \Drupal\core_event_dispatcher\Event\Theme\ThemeEvent $event
   *   The event object.
   */
  public function theme(ThemeEvent $event) {

    // Don't register our implementation if one already exists.
    //
    // @see https://www.drupal.org/project/description_list
    if (isset($event->getExisting()['description_list'])) {
      return;
    }

    $event->addNewTheme('description_list', [
      'variables' => [
        'groups'    => [],
        'attributes'  => [],
      ],
      'template'  => 'description-list',
      // Path is required.
      // @see https://www.drupal.org/project/hook_event_dispatcher/issues/3038311
      'path'      => $this->moduleHandler->getModule('ambientimpact_core')
                     ->getPath() . '/templates',
    ]);
  }
}
