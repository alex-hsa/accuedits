<?php

namespace Drupal\openai_devel\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\devel_generate\DevelGenerateBaseInterface;
use Drupal\devel_generate\DevelGeneratePluginManager;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class OpenAIDevelCommands extends DrushCommands {

  /**
   * The DevelGenerate plugin manager.
   *
   * @var \Drupal\devel_generate\DevelGeneratePluginManager
   */
  protected $manager;

  /**
   * The plugin instance.
   *
   * @var \Drupal\devel_generate\DevelGenerateBaseInterface
   */
  protected $pluginInstance;

  /**
   * The Generate plugin parameters.
   *
   * @var array
   */
  protected $parameters;

  /**
   * DevelGenerateCommands constructor.
   *
   * @param \Drupal\devel_generate\DevelGeneratePluginManager $manager
   *   The DevelGenerate plugin manager.
   */
  public function __construct(DevelGeneratePluginManager $manager) {
    parent::__construct();
    $this->setManager($manager);
  }

  /**
   * Get the DevelGenerate plugin manager.
   *
   * @return \Drupal\devel_generate\DevelGeneratePluginManager
   *   The DevelGenerate plugin manager.
   */
  public function getManager() {
    return $this->manager;
  }

  /**
   * Set the DevelGenerate plugin manager.
   *
   * @param \Drupal\devel_generate\DevelGeneratePluginManager $manager
   *   The DevelGenerate plugin manager.
   */
  public function setManager(DevelGeneratePluginManager $manager) {
    $this->manager = $manager;
  }

  /**
   * Get the DevelGenerate plugin instance.
   *
   * @return mixed
   *   The DevelGenerate plugin instance.
   */
  public function getPluginInstance() {
    return $this->pluginInstance;
  }

  /**
   * Set the DevelGenerate plugin instance.
   *
   * @param mixed $pluginInstance
   *   The DevelGenerate plugin instance.
   */
  public function setPluginInstance($pluginInstance) {
    $this->pluginInstance = $pluginInstance;
  }

  /**
   * Get the DevelGenerate plugin parameters.
   *
   * @return array
   *   The plugin parameters.
   */
  public function getParameters() {
    return $this->parameters;
  }

  /**
   * Set the DevelGenerate plugin parameters.
   *
   * @param array $parameters
   *   The plugin parameters.
   */
  public function setParameters(array $parameters) {
    $this->parameters = $parameters;
  }

  /**
   * Generate content using OpenAI's GPT services.
   *
   * @command devel-generate:content-gpt
   * @aliases gencgpt
   * @pluginId content_gpt
   * @validate-module-enabled node
   *
   * @param int $num
   *   Number of nodes to generate.
   * @param array $options
   *   Array of options as described below.
   *
   * @option kill Delete all content before generating new content.
   * @option bundles A comma-delimited list of content types to create.
   * @option authors A comma delimited list of authors ids. Defaults to all users.
   * @option feedback An integer representing interval for insertion rate logging.
   * @option skip-fields A comma delimited list of fields to omit when generating random values
   * @option base-fields A comma delimited list of base field names to populate
   * @option languages A comma-separated list of language codes
   * @option translations A comma-separated list of language codes for translations.
   * @option add-type-label Add the content type label to the front of the node title
   * @option model The OpenAI GPT model to use.
   * @option system The "system profile" helps set the behavior of the ChatGPT response. You can change/influence how it responds by adjusting the above instruction. Try adding instructions related to the types of content you wish to generate for different results.
   * @option temperature What sampling temperature to use, between 0 and 2. Higher values like 0.8 will make the output more random, while lower values like 0.2 will make it more focused and deterministic.
   * @option max_tokens The maximum number of tokens to generate in the response. The token count of your prompt plus max_tokens cannot exceed the model\'s context length.
   * @option html If TRUE, OpenAI will be instructed to format the replies in basic HTML format for text formatted fields. Warning, this will consume many more tokens in the response.
   */
  public function content($num = 15, array $options = [
    'kill' => FALSE,
    'bundles' => 'page,article',
    'authors' => self::REQ,
    'feedback' => 1,
    'skip-fields' => self::REQ,
    'base-fields' => self::REQ,
    'languages' => self::REQ,
    'translations' => self::REQ,
    'add-type-label' => FALSE,
    'model' => 'gpt-3.5-turbo',
    'system' => self::REQ,
    'temperature' => 0.4,
    'max_tokens' => 512,
    'html' => FALSE,
  ]) {
    $this->generate();
  }

  /**
   * The standard drush validate hook.
   *
   * @hook validate
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The data sent from the drush command.
   */
  public function validate(CommandData $commandData) {
    $manager = $this->getManager();
    $args = $commandData->input()->getArguments();
    // The command name is the first argument but we do not need this.
    array_shift($args);
    /** @var DevelGenerateBaseInterface $instance */
    $instance = $manager->createInstance($commandData->annotationData()->get('pluginId'), []);
    $this->setPluginInstance($instance);
    $parameters = $instance->validateDrushParams($args, $commandData->input()->getOptions());
    $this->setParameters($parameters);
  }

  /**
   * Wrapper for calling the plugin instance generate function.
   */
  public function generate() {
    $instance = $this->getPluginInstance();
    $instance->generate($this->getParameters());
  }

}
