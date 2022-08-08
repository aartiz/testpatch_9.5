<?php

namespace Drupal\magento\Plugin\QueueWorker;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\magento\Services\MagentoClient;
use Drupal\magento\Services\MagentoUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates products in Drupal.
 *
 * @QueueWorker(
 *   id = "magento_create_products",
 *   title = @Translation("Creates Products"),
 *   cron = {"time" = 60}
 * )
 */
class CreateProduct extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Magento Client.
   *
   * @var \Drupal\magento\Services\MagentoClient
   */
  protected $magentoClient;

  /**
   * Magento Utils instance.
   *
   * @var \Drupal\magento\Services\MagentoUtils
   */
  protected $magentoUtils;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    LoggerChannelFactoryInterface $logger,
    MagentoClient $magentoClient,
    MagentoUtils $magentoUtils
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger->get('magento');
    $this->magentoClient = $magentoClient;
    $this->magentoUtils = $magentoUtils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('magento.client'),
      $container->get('magento.utils')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    $currencyCode = $this->magentoClient->getCurrencyCode();
    $product = $this->magentoClient->getProduct($item['sku']);
    if ($currencyCode && $product) {
      $id = $this->magentoUtils->createProduct($product, $currencyCode);
      if ($id) {
        $this->logger->notice('Product with ID %id created', ['%id' => $id]);
      }
    }
  }

}
