<?php

namespace Drupal\mailchimp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Mailchimp\MailchimpAPIException;

/**
 * Configure Mailchimp settings for this site.
 */
class MailchimpAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailchimp_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mailchimp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mailchimp.settings');

    $mc_api_url = Url::fromUri('http://admin.mailchimp.com/account/api', ['attributes' => ['target' => '_blank']]);
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mailchimp API Key'),
      '#required' => TRUE,
      '#default_value' => $config->get('api_key'),
      '#description' => $this->t('The API key for your Mailchimp account. Get or generate a valid API key at your @apilink.', ['@apilink' => Link::fromTextAndUrl($this->t('Mailchimp API Dashboard'), $mc_api_url)->toString()]),
    ];

    $form['connected_sites'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Connected sites'),
    ];

    $mc_connected_sites_url = Url::fromUri('https://kb.mailchimp.com/integrations/connected-sites/about-connected-sites')->toString();
    $form['connected_sites']['enable_connected'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable connected site'),
      '#description' => $this->t('Connects this website to Mailchimp by automatically embedding Mailchimp\'s <a href=":link" target="_blank">Connected Sites</a> JavaScript code.', [
        ':link' => $mc_connected_sites_url,
      ]),
      '#default_value' => $config->get('enable_connected'),
    ];

    /* @var \Mailchimp\MailchimpConnectedSites $mc_connected */
    try {
      $mc_connected = mailchimp_get_api_object('MailchimpConnectedSites');
      if ($mc_connected) {
        $connected_sites = $mc_connected->getConnectedSites();
      }
    }
    catch (MailchimpAPIException $e) {
      // Ignore errors due to invalid keys.
    }

    $connected_sites_options = [];
    if (!empty($connected_sites) && !empty($connected_sites->sites)) {
      foreach ($connected_sites->sites as $site) {
        $connected_sites_options[$site->foreign_id] = $site->domain;
      }
    }

    $form['connected_sites']['config'] = [
      '#type' => 'container',
      '#states' => [
        'invisible' => [
          ':input[name="enable_connected"]' => ['checked' => FALSE],
        ],
      ],
    ];

    if (!empty($connected_sites_options)) {
      // If the Mailchimp account contains connected sites, allow the user to
      // choose one here.
      $form['connected_sites']['config']['connected_id'] = [
        '#type' => 'radios',
        '#options' => $connected_sites_options,
        '#default_value' => $config->get('connected_id'),
        '#prefix' => $this->t('<p><b>Choose a connected site from your Mailchimp account.</b></p>'),
      ];

      // Allow the user to configure which paths to embed JavaScript on.
      $form['connected_sites']['config']['connected_paths'] = [
        '#type' => 'textarea',
        '#default_value' => $config->get('connected_paths'),
        '#prefix' => $this->t("<p><b>Configure paths to embed Mailchimp's JavaScript code on.</b></p>"),
        '#description' => $this->t('Specify pages using their paths. Enter one path per line. <front> is the front page. If you have created a pop-up subscription form in Mailchimp, it will appear on paths defined here.'),
      ];
    }
    else {
      // If the Mailchimp account does not contain any connected sites, gently
      // encourage the user to create one.
      $form['connected_sites']['sites']['info'] = [
        '#type' => 'markup',
        '#markup' => $this->t('You will need to connect this site to Mailchimp first! <a href=":link" target="_blank">Check out the documentation here</a>.', [
          ':link' => $mc_connected_sites_url,
        ]),
      ];
    }

    $form['batch'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Batch processing'),
    ];

    $form['batch']['cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use batch processing.'),
      '#description' => $this->t('Puts all Mailchimp subscription operations into the cron queue. (Includes subscribe, update, and unsubscribe operations.) <i>Note: May cause confusion if caches are cleared, as requested changes will appear to have failed until cron is run.</i>'),
      '#default_value' => $config->get('cron'),
    ];
    $form['batch']['batch_limit'] = [
      '#type' => 'select',
      '#options' => [
        '1' => '1',
        '10' => '10',
        '25' => '25',
        '50' => '50',
        '75' => '75',
        '100' => '100',
        '250' => '250',
        '500' => '500',
        '750' => '750',
        '1000' => '1000',
        '2500' => '2500',
        '5000' => '5000',
        '7500' => '7500',
        '10000' => '10000',
      ],
      '#title' => $this->t('Batch limit'),
      '#description' => $this->t('Maximum number of entities to process in a single cron run. Mailchimp suggest keeping this at 5000 or below. <i>This value is also used for batch Merge Variable updates on the Fields tab (part of mailchimp_lists).</i>'),
      '#default_value' => $config->get('batch_limit'),
    ];

    global $base_url;
    $current_webhook_hash = $config->get('webhook_hash');

    $form['webhook_hash'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Webhook Hash'),
      '#default_value' => $current_webhook_hash ? $current_webhook_hash : md5(uniqid(mt_rand(), TRUE)),
      '#description' => $this->t('Hash to validate incoming webhooks. Whatever you put here should be appended to the URL you provide Mailchimp.'),
      '#suffix' => '<strong>Your webhook URL:</strong> ' . $base_url . '/mailchimp/webhook/[hash]',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('mailchimp.settings');
    $config
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('enable_connected', $form_state->getValue('enable_connected'))
      ->set('connected_id', $form_state->getValue('connected_id'))
      ->set('connected_paths', $form_state->getValue('connected_paths'))
      ->set('cron', $form_state->getValue('cron'))
      ->set('batch_limit', $form_state->getValue('batch_limit'))
      ->set('webhook_hash', $form_state->getValue('webhook_hash'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
