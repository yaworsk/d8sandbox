<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\LocalActionDefault.
 */

namespace Drupal\Core\Menu;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a default implementation for local action plugins.
 */
class LocalActionDefault extends PluginBase implements LocalActionInterface, ContainerFactoryPluginInterface {

  /**
   * The route provider to load routes by name.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
   protected $routeProvider;

  /**
   * Constructs a LocalActionDefault object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider to load routes by name.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteProviderInterface $route_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('router.route_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return $this->pluginDefinition['route_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {
    // Subclasses may pull in the request or specific attributes as parameters.
    $options = array();
    if (!empty($this->pluginDefinition['title_context'])) {
      $options['context'] = $this->pluginDefinition['title_context'];
    }
    $args = array();
    if (isset($this->pluginDefinition['title_arguments']) && $title_arguments = $this->pluginDefinition['title_arguments']) {
      $args = (array) $title_arguments;
    }
    return $this->t($this->pluginDefinition['title'], $args, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->pluginDefinition['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(Request $request) {
    $parameters = isset($this->pluginDefinition['route_parameters']) ? $this->pluginDefinition['route_parameters'] : array();
    $route = $this->routeProvider->getRouteByName($this->getRouteName());
    $variables = $route->compile()->getVariables();

    // Normally the \Drupal\Core\ParamConverter\ParamConverterManager has
    // processed the Request attributes, and in that case the _raw_variables
    // attribute holds the original path strings keyed to the corresponding
    // slugs in the path patterns. For example, if the route's path pattern is
    // /filter/tips/{filter_format} and the path is /filter/tips/plain_text then
    // $raw_variables->get('filter_format') == 'plain_text'.
    $raw_variables = $request->attributes->get('_raw_variables');

    foreach ($variables as $name) {
      if (isset($parameters[$name])) {
        continue;
      }

      if ($raw_variables && $raw_variables->has($name)) {
        $parameters[$name] = $raw_variables->get($name);
      }
      elseif ($request->attributes->has($name)) {
        $parameters[$name] = $request->attributes->get($name);
      }
    }
    // The UrlGenerator will throw an exception if expected parameters are
    // missing. This method should be overridden if that is possible.
    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(Request $request) {
    return (array) $this->pluginDefinition['options'];
  }
}
