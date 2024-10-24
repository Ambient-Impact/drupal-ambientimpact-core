<?php

namespace Drupal\ambientimpact_core;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use function method_exists;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for implementing Ambient.Impact Component plug-ins.
 */
class ComponentBase extends PluginBase implements
ContainerFactoryPluginInterface, ConfigurableInterface, ComponentInterface {
  use StringTranslationTrait;

  /**
   * The Drupal module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Drupal language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The Drupal renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The Drupal YAML serialization class.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $yamlSerialization;

  /**
   * The Drupal string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * The Component HTML cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $htmlCacheService;

  /**
   * The directory in which component directories are located.
   *
   * This is relative to the implementing module's directory.
   *
   * @var string
   */
  protected $componentsDirectory = 'components';

  /**
   * The path to this component's directory.
   *
   * This is relative to the implementing module's directory.
   *
   * If empty, will be built in $this->__construct() with
   * $this->componentsDirectory and the plug-in ID.
   *
   * @var string
   *
   * @see $this->componentsDirectory
   *   The directory in which this component's directory is found, relative to
   *   the implementing module's directory.
   */
  protected $path = '';

  /**
   * Whether this Component has any HTML cached.
   *
   * @var null|bool
   */
  protected $hasCachedHTML = null;

  /**
   * This Component's HTML cache ID.
   *
   * @var null|string
   */
  protected $htmlCacheID = null;

  /**
   * Constructs an Ambient.Impact Component object.
   *
   * This calls the parent PluginBase::__construct(), and then calls
   * $this->setConfiguration() to ensure settings are merged over defaults.
   *
   * @param array $configuration
   *   A configuration array containing information about the plug-in instance.
   *
   * @param string $pluginID
   *   The plugin_id for the plug-in instance.
   *
   * @param array $pluginDefinition
   *   The plug-in implementation definition. PluginBase defines this as mixed,
   *   but we should always have an array so the type is set. This can be
   *   changed in the future if need be.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The Drupal module handler service.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The Drupal language manager service.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Drupal renderer service.
   *
   * @param \Drupal\Component\Serialization\SerializationInterface $yamlSerialization
   *   The Drupal YAML serialization class.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The Drupal string translation service.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $htmlCacheService
   *   The Component HTML cache service.
   */
  public function __construct(
    array $configuration, string $pluginID, array $pluginDefinition,
    ModuleHandlerInterface $moduleHandler,
    LanguageManagerInterface $languageManager,
    RendererInterface $renderer,
    SerializationInterface $yamlSerialization,
    TranslationInterface $stringTranslation,
    CacheBackendInterface $htmlCacheService
  ) {
    parent::__construct($configuration, $pluginID, $pluginDefinition);

    // Save dependencies.
    $this->moduleHandler      = $moduleHandler;
    $this->languageManager    = $languageManager;
    $this->renderer           = $renderer;
    $this->yamlSerialization  = $yamlSerialization;
    $this->stringTranslation  = $stringTranslation;
    $this->htmlCacheService   = $htmlCacheService;

    $this->setConfiguration($configuration);

    // Build the path if it hasn't been built/specified.
    if (empty($this->path)) {
      $this->path = $this->componentsDirectory . '/' . $pluginID;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration, $pluginID, $pluginDefinition
  ) {
    return new static(
      $configuration, $pluginID, $pluginDefinition,
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('renderer'),
      $container->get('serialization.yaml'),
      $container->get('string_translation'),
      $container->get('cache.ambientimpact_component_html')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPath(bool $absolute = false): string {
    if ($absolute === true) {
      return $this->moduleHandler->getModule(
        $this->pluginDefinition['provider']
      )->getPath() . '/' . $this->path;
    }

    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Block\BlockBase::setConfiguration()
   *   Copied from this.
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep(
      $this->baseConfigurationDefaults(),
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * Returns generic default configuration for Component plug-ins.
   *
   * @return array
   *   An associative array with the default configuration.
   *
   * @todo Is there a point to including the plug-in ID in this?
   */
  protected function baseConfigurationDefaults() {
    return [
      'id' => $this->getPluginId(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(): array {
    // This component's libraries, if any are found.
    $libraries = [];

    // An array of file array references for ease of manipulation: one index for
    // each 'css' group found, and the 'js' array, if present. At that level
    // they're structured the same, so this avoids repeating code.
    $files = [];

    // This is the full file system path to the file, including the file name
    // and extension.
    $filePath =
      DRUPAL_ROOT . '/' . $this->getPath(true) . '/' .
      $this->pluginDefinition['id'] . '.libraries.yml';

    // Don't proceed if the file doesn't exist.
    if (!file_exists($filePath)) {
      return $libraries;
    }

    // Parse the YAML file.
    $libraries = $this->yamlSerialization::decode(file_get_contents($filePath));

    foreach ($libraries as &$library) {
      // Save references to each 'css' group array found.
      if (isset($library['css'])) {
        foreach ($library['css'] as &$category) {
          $files[] = &$category;
        }
      }

      if (isset($library['js'])) {
        // Save a reference to the 'js' array, if found.
        $files[] = &$library['js'];

        // Add the JavaScript framework as a dependency if:
        if (
          // This library isn't to be attached in the header. The reason for
          // this is that this would cause Drupal to place the framework in the
          // header as well, which would contribute to a page that's slower to
          // render. If a library attaches itself to the header, it must not
          // expect the framework to be available.
          (
            !isset($library['header']) ||
            $library['header'] === false
          ) && (
            // The library either doesn't have any dependencies defined, or it
            // does but does not have the framework listed as a dependency.
            !isset($library['dependencies']) ||
            is_array($library['dependencies']) &&
            !in_array(
              'ambientimpact_core/framework',
              $library['dependencies']
            )
          )
        ) {
          $library['dependencies'][] = 'ambientimpact_core/framework';
        }

        // If no 'defer' attribute is set, default to true to delay component
        // JavaScript until most other stuff is done executing. This helps the
        // page feel a bit faster to load.
        foreach ($library['js'] as $file => &$fileSettings) {
          if (!isset($fileSettings['attributes']['defer'])) {
            $fileSettings['attributes']['defer'] = true;
          }
        }
      }
    }

    // Prepend the component path to make it relative to the module's directory
    // as opposed to the component's.
    foreach ($files as &$category) {
      foreach (array_keys($category) as $key) {
        // New key.
        $category[$this->getPath() . '/' . $key] = $category[$key];
        // Delete the old key.
        unset($category[$key]);
      }
    }

    return $libraries;
  }

  /**
   * {@inheritdoc}
   */
  public function getJSSettings(): array {
    return [];
  }

  /**
   * Get the Component HTML cache settings.
   *
   * This can be overridden on a per-Component basis to set custom cache
   * invalidation.
   *
   * This supports 'max-age' and 'tags', but 'contexts' is not yet supported.
   *
   * @return array
   *   The Component HTML cache settings with 'max-age' set to permanent, i.e.
   *   only rebuilt on a cache rebuild.
   *
   * @see https://api.drupal.org/api/drupal/core!core.api.php/group/cache
   *   Drupal Cache API documentation.
   *
   * @todo Add support for cache contexts?
   *
   * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Renderer.php/function/Renderer%3A%3AdoRender
   *   Cache contexts used in the rendering process.
   */
  protected static function getHTMLCacheSettings(): array {
    return [
      'max-age' => Cache::PERMANENT,
    ];
  }

  /**
   * Get this Component's HTML cache ID.
   *
   * This is only built once and stored for subsequent use.
   *
   * @return string
   *   The value of $this->htmlCacheID.
   *
   * @see $this->htmlCacheID
   *   The HTML cache ID is stored here.
   */
  protected function getHTMLCacheID(): string {
    if ($this->htmlCacheID === null) {
      $this->htmlCacheID =
        $this->pluginDefinition['provider'] . ':' .
        $this->pluginDefinition['id'] . ':' .
        $this->languageManager->getCurrentLanguage()->getId();
    }

    return $this->htmlCacheID;
  }

  /**
   * Determine if this Component has any cached HTML available.
   *
   * @return boolean
   *   The value of $this->hasCachedHTML.
   *
   * @see $this->hasCachedHTML
   *   Stores whether this Component has cached HTML.
   */
  protected function hasCachedHTML(): bool {
    if ($this->hasCachedHTML === null) {
      $this->hasCachedHTML = !empty(
        $this->htmlCacheService->get($this->getHTMLCacheID())->data
      );
    }

    return $this->hasCachedHTML;
  }

  /**
   * Get the file system path to this Component's HTML file.
   *
   * @return string
   *   The Component's <component name>.html.twig file path.
   */
  protected function getHTMLPath() {
    // This is the full file system path to the file, including the file name
    // and extension.
    return DRUPAL_ROOT . '/' . $this->getPath(true) . '/' .
      $this->pluginDefinition['id'] . '.html.twig';
  }

  /**
   * {@inheritdoc}
   */
  public function hasHTML(): bool {
    return file_exists($this->getHTMLPath());
  }

  /**
   * {@inheritdoc}
   */
  public function getHTML() {
    // Don't proceed if a Twig template doesn't exist.
    if (!$this->hasHTML()) {
      return false;
    }

    // If cached HTML is available, grab that without doing any rendering.
    if ($this->hasCachedHTML()) {
      $html = $this->htmlCacheService->get($this->getHTMLCacheID())->data;

    // If no cached HTML is found, render and cache the HTML.
    } else {
      // Render array containing the file contents as an inline template.
      $renderArray = [
        '#type'     => 'inline_template',
        '#template' => file_get_contents($this->getHTMLPath()),
      ];

      // Render the inline template.
      if (method_exists($this->renderer, 'renderInIsolation')) {

        // Drupal >= 10.3
        //
        // @see https://www.drupal.org/node/3407994
        //   Change record deprecating RendererInterface::renderPlain() in
        //   favour of RendererInterface::renderInIsolation().
        $html = $this->renderer->renderInIsolation($renderArray);

      } else {

        // Drupal < 10.3
        //
        // @todo Remove when minimum core is increased to 10.3 or higher.
        /** @phpstan-ignore-next-line */
        $html = $this->renderer->renderPlain($renderArray);

      }

      $cacheSettings = static::getHTMLCacheSettings();

      // Set the 'max-age' and 'tags' keys if they're not set.
      if (!isset($cacheSettings['max-age'])) {
        $cacheSettings['max-age'] = Cache::PERMANENT;
      }
      if (!isset($cacheSettings['tags'])) {
        $cacheSettings['tags'] = [];
      }

      // Save the rendered template HTML to the cache.
      $this->htmlCacheService->set(
        $this->getHTMLCacheID(),
        $html,
        $cacheSettings['max-age'],
        $cacheSettings['tags']
      );
    }

    return $html;
  }

  /**
   * {@inheritdoc}
   */
  public function hasDemo(): bool {
    $reflection = new \ReflectionMethod($this, 'getDemo');

    return self::class !== $reflection->getDeclaringClass()->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getDemo(): array {
    return [];
  }
}
