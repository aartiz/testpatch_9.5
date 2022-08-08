<?php

namespace Drupal\magento\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\magento\Services\MagentoClient;
use Drupal\magento\Services\MagentoUtils;

/**
 * Class ImportProduct.
 *
 * @category Commands
 * @package Drupal\magento\Commands
 * @author Vighnesh Sadagopal <vighnesh.sadagopal@qed42.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link https://www.drupal.org/project/magento
 */
class ImportProduct extends DrushCommands {
  /**
   * Magento Client.
   *
   * @var \Drupal\magento\Client\MagentoClient
   */
  protected $magentoClient;

  /**
   * Magento Utils.
   *
   * @var \Drupal\magento\Services\MagentoUtils
   */
  protected $magentoUtils;

  /**
   * Constructor.
   *
   * @param \Drupal\magento\Services\MagentoClient $magentoClient
   *   Magento Client.
   * @param \Drupal\magento\Services\MagentoUtils $magentoUtils
   *   Magento Utils.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @return \Drupal\magento\Commands\ImportProduct
   */
  public function __construct(
        MagentoClient $magentoClient,
        MagentoUtils $magentoUtils
    ) {
    $this->magentoClient = $magentoClient;
    $this->magentoUtils = $magentoUtils;
  }

  /**
   * Import Magento Product Singly with entering SKU from magento.
   *
   * @param string $sku
   *   SKU informed from magento.
   * @param string $options
   *   Options arr will import product in the basis of array with spaces.
   *
   * @command magento:import
   * @aliases magento-import
   * @options arr Will import product in the basis of array with spaces.
   *
   * @usage drush magento:import <sku>
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function importProduct($sku, $options = ['arr' => FALSE]) {
    if ($options['arr']) {
      try {
        $this->output->writeln('The Product is getting imported ....');
        $skus = (explode(" ", $sku));
        foreach ($skus as $key => $value) {
          $currencyCode = $this->magentoClient->getCurrencyCode();
          $product = $this->magentoClient->getProduct($skus[$key]);
          $this->magentoUtils->createProduct($product, $currencyCode);
        }
        $this->logger()->success('Successfully imported');
      }
      catch (EntityStorageException $e) {
        $this->logger()->error($e->getMessage());
      }
    }
    else {
      try {
        $this->output->writeln('The Product is getting imported ....');
        $currencyCode = $this->magentoClient->getCurrencyCode();
        $product = $this->magentoClient->getProduct($sku);
        $this->magentoUtils->createProduct($product, $currencyCode);
        $this->logger()->success('Successfully Imported');
      }
      catch (EntityStorageException $e) {
        $this->logger()->error($e->getMessage());
      }
    }
  }

}
