<?php

declare(strict_types=1);

namespace Drupal\openai_dalle\Form;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to interact with the OpenAI API's DALL·E (image generation)
 * endpoint.
 */
class DalleForm extends FormBase {

  /**
   * The OpenAI API wrapper.
   *
   * @var \Drupal\openai\OpenAIApi
   */
  protected $api;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The datetime service.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * The file generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openai_dalle_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->api = $container->get('openai.api');
    $instance->fileSystem = $container->get('file_system');
    $instance->time = $container->get('datetime.time');
    $instance->fileUrlGenerator = $container->get('file_url_generator');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Prompt'),
      '#description' => $this->t('A text description of the desired image(s). The maximum length is 1000 characters for dall-e-2 and 4000 characters for dall-e-3. Please note that OpenAI may reject prompts it deems in violation of their content standards.'),
      '#required' => TRUE,
    ];

    $models = $this->api->filterModels(['dall']);

    $form['model'] = [
      '#type' => 'select',
      '#title' => $this->t('Model'),
      '#options' => $models,
      '#default_value' => 'dall-e-3',
      '#description' => $this->t('The model to use to generate an image. See the <a href=":link">link</a> for more information.', [':link' => 'https://platform.openai.com/docs/models/dall-e']),
    ];

    $form['quality'] = [
      '#type' => 'select',
      '#title' => $this->t('Quality'),
      '#options' => [
        'hd' => 'HD',
        'standard' => 'Standard',
      ],
      '#default_value' => 'hd',
      '#states' => [
        'visible' => [
          [
            ':input[name="model"]' => ['value' => 'dall-e-3']
          ]
        ]
      ],
      '#description' => $this->t('The quality of the image that will be generated. hd creates images with finer details and greater consistency across the image. This parameter only supported for dall-e-3.'),
    ];

    $form['size'] = [
      '#type' => 'select',
      '#title' => $this->t('Size'),
      '#options' => [
        '256x256' => '256x256',
        '512x512' => '512x512',
        '1024x1024' => '1024x1024',
        '1792x1024' => '1792x1024',
        '1024x1792' => '1024x1792',
      ],
      '#default_value' => '',
      '#description' => $this->t('The size of the generated images.'),
    ];

    $form['style'] = [
      '#type' => 'select',
      '#title' => $this->t('Style'),
      '#options' => [
        'vivid' => 'Vivid - Generate hyper-real and dramatic images',
        'natural' => 'Natural - Generate more natural, less hyper-real looking images',
      ],
      '#default_value' => 'vivid',
      '#states' => [
        'visible' => [
          [
            ':input[name="model"]' => ['value' => 'dall-e-3']
          ]
        ]
      ],
      '#description' => $this->t('The style of the generated images. Must be one of vivid or natural. Vivid causes the model to lean towards generating hyper-real and dramatic images. Natural causes the model to produce more natural, less hyper-real looking images. This parameter only supported for dall-e-3.'),
    ];

    $form['response_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Response Format'),
      '#options' => [
        'url' => 'URL',
        'b64_json' => 'b64_json',
      ],
      '#default_value' => 'url',
      '#description' => $this->t('The image format of the result. See the <a href=":link">link</a> for more information.', [':link' => 'https://platform.openai.com/docs/api-reference/images/create#images-create-response_format']),
    ];

    $form['filename'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filename'),
      '#default_value' => 'dalle_image',
      '#states' => [
        'visible' => [
          [
            ':input[name="response_format"]' => ['value' => 'b64_json']
          ]
        ]
      ],
      '#required' => TRUE,
      '#description' => $this->t('The filename to save the result as.'),
    ];

    $form['response'] = [
      '#markup' => 'The response will create a link to the image below.',
    ];

    $form['file'] = [
      '#prefix' => '<div id="openai-dalle-response">',
      '#suffix' => '</div>',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::getResponse',
        'wrapper' => 'openai-dalle-response',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $prompt = $form_state->getValue('prompt');
    $model = $form_state->getValue('model');
    $size = $form_state->getValue('size');

    $dalle_2_sizes = [
      '256x256' => '256x256',
      '512x512' => '512x512',
      '1024x1024' => '1024x1024',
    ];

    $dalle_3_sizes = [
      '1024x1024' => '1024x1024',
      '1792x1024' => '1792x1024',
      '1024x1792' => '1024x1792',
    ];

    if ($model === 'dall-e-2' && mb_strlen($prompt) > 1000) {
      $form_state->setErrorByName('prompt', 'The input cannot exceed 1000 characters for the dall-e-2 model.');
    }

    if ($model === 'dall-e-2' && !in_array($size, $dalle_2_sizes)) {
      $form_state->setErrorByName('size', 'This size is not supported by the dall-e-2 model.');
    }

    if ($model === 'dall-e-3' && !in_array($size, $dalle_3_sizes)) {
      $form_state->setError($form['size'], 'This size is not supported by the dall-e-3 model.');
    }

    if ($model === 'dall-e-3' && mb_strlen($prompt) > 4000) {
      $form_state->setErrorByName('prompt', 'The input cannot exceed 4000 characters for the dall-e-3 model.');
    }
  }

  /**
   * Renders the response.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The modified form element.
   */
  public function getResponse(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();

    if (empty($form_state->getErrors()) && !empty($storage['filepath'])) {
      $storage = $form_state->getStorage();

      if ($storage['format'] === 'b64_json') {
        $file_url = $this->fileUrlGenerator->generate($storage['filepath']);
        $link = Link::fromTextAndUrl($storage['filename'], $file_url)->toString();
      }
      else {
        $url = Url::fromUri($storage['filepath']);
        $link = Link::fromTextAndUrl('DALL·E result', $url)->toString();
      }

      $form['file']['#markup'] = 'Download/view the image: ' . $link;
    }

    return $form['file'];
  }

  /**
   * Submits the input to OpenAI.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $prompt = $form_state->getValue('prompt');
    $model = $form_state->getValue('model');
    $quality = $form_state->getValue('quality');
    $size = $form_state->getValue('size');
    $style = $form_state->getValue('style');
    $format = $form_state->getValue('response_format');
    $filename = $form_state->getValue('filename');
    $form_state->setStorage([]);

    // Example of getting the result and handling it.
    try {
      $result = $this->api->images($model, $prompt, $size, $format, $quality, $style);

      if ($format === 'b64_json') {
        $filename = $filename . '.png';
        $data = base64_decode($result);
        $file_uri = $this->fileSystem->saveData($data, 'public://' . $filename, FileSystemInterface::EXISTS_REPLACE);
        $file = File::create(['uri' => $file_uri]);
        $file->setOwnerId($this->currentUser()->id());
        $file->setPermanent();
        $file->save();
      }

      $form_state->setStorage(
        [
          'filename' => ($format === 'b64_json') ? $file->getFilename() : 'DALL·E result',
          'filepath' => ($format === 'b64_json') ? $file->getFileUri() : $result,
          'format' => $format,
        ],
      );
    } catch (\Exception $e) {

    }

    $form_state->setRebuild();
  }
}
