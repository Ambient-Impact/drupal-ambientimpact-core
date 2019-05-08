<?php

namespace Drupal\ambientimpact_core;

/**
 * An interface for returning HTML from Ambient.Impact Component plugins.
 */
interface ComponentHTMLInterface {
  /**
   * Determine if this Component has HTML available.
   *
   * @return boolean
   *   True if the <component name>.html.twig file exists, false otherwise.
   */
  public function hasHTML(): bool;

  /**
   * Get any HTML this Component may have available for the front-end.
   *
   * @return string|bool
   *   If the component has a <component name>.html.twig file in its directory,
   *   it will be rendered and returned, otherwise false is returned.
   */
  public function getHTML();
}
