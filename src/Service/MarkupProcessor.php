<?php

namespace Drupal\ambientimpact_core\Service;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ambientimpact_core\Event\DOMCrawlerEvent;
use Drupal\ambientimpact_core\Service\MarkupProcessorInterface;

/**
 * Markup processor service class.
 */
class MarkupProcessor implements MarkupProcessorInterface {
  /**
   * The Drupal/Symfony event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs this service object.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The Drupal/Symfony event dispatcher service.
   */
  public function __construct(EventDispatcherInterface $eventDispatcher) {
    // Save dependencies.
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function process($markup) {
    if (!is_string($markup) && !($markup instanceof TranslatableMarkup)) {
      throw new \InvalidArgumentException('The $markup parameter must either be a string or an instance of \Drupal\Core\StringTranslation\TranslatableMarkup!');
    }

    // If there aren't any listeners attached to the event, just return the
    // markup as-is without parsing it so that we don't do unnecessary work.
    if (!$this->eventDispatcher->hasListeners('ambientimpact.markup_process')) {
      return $markup;
    }

    if ($markup instanceof TranslatableMarkup) {
      /** @var string */
      $crawlerMarkup = $markup->getUntranslatedString();
    } else {
      /** @var string */
      $crawlerMarkup = $markup;
    }

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $crawler = new Crawler(
      // The <div> is to prevent the PHP DOM automatically wrapping any
      // top-level text content in a <p> element.
      '<div id="ambientimpact-dom-root">' . $crawlerMarkup . '</div>'
    );

    // Set the crawler to our root element so that only its children are
    // rendered without the root element wrapping them.
    $crawler = $crawler->filter('#ambientimpact-dom-root');

    // Create the event object.
    $event = new DOMCrawlerEvent($crawler);

    // Dispatch the event with the event object.
    $this->eventDispatcher->dispatch($event, 'ambientimpact.markup_process');

    // Get the crawler with any modifications listeners/subscribers may have
    // made to it.
    $crawler = $event->getCrawler();

    // Render the crawler and return it as a TranslatableMarkup object or a
    // string, depending on what was provided.
    if ($markup instanceof TranslatableMarkup) {
      return new TranslatableMarkup(
        $crawler->html(),
        $markup->getArguments(),
        $markup->getOptions()
      );
    } else {
      return $crawler->html();
    }
  }
}
