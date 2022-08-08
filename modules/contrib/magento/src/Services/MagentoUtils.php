<?php

namespace Drupal\magento\Services;

use Drupal\commerce_price\CurrencyImporterInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\ProductAttributeFieldManagerInterface;
use Drupal\commerce_stock\StockServiceManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\magento\Model\SearchCriteria;
use Drupal\entity\BundleFieldDefinition;

/**
 * Class MagentoUtils.
 *
 * @package \Drupal\magento\Services
 */
class MagentoUtils {

  /**
   * Add-on storage.
   *
   * @var \Drupal\commerce_addon\Entity\AddonInterface
   */
  protected $addOnStorage;

  /**
   * Add-on Type storage.
   *
   * @var \Drupal\commerce_addon\Entity\AddonTypeInterface
   */
  protected $addOnTypeStorage;

  /**
   * Alias storage.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface
   */
  protected $aliasStorage;

  /**
   * Config Factory Interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Currency Importer.
   *
   * @var \Drupal\commerce_price\CurrencyImporterInterface
   */
  protected $currencyImporter;

  /**
   * Database connection instance.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * FieldConfig storage.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $fieldConfigStorage;

  /**
   * FieldStorageConfig storage.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorageConfigStorage;

  /**
   * Product Attribute Field Manager.
   *
   * @var \Drupal\File\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * Logger instance.
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
   * Product Attribute Field Manager.
   *
   * @var \Drupal\commerce_product\ProductAttributeFieldManagerInterface
   */
  protected $productAttributeFieldManager;

  /**
   * Product Attribute Storage.
   *
   * @var \Drupal\commerce_product\Entity\ProductAttributeInterface
   */
  protected $productAttributeStorage;

  /**
   * Product Attribute Value Storage.
   *
   * @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface
   */
  protected $productAttributeValueStorage;

  /**
   * Product Storage.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $productStorage;

  /**
   * Product Type Storage.
   *
   * @var \Drupal\commerce_product\Entity\ProductTypeInterface
   */
  protected $productTypeStorage;

  /**
   * Product Variation Type Storage.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface
   */
  protected $productVariationTypeStorage;

  /**
   * Product Variation Storage.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $productVariationStorage;

  /**
   * Term Storage.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $termStorage;

  /**
   * Stock service manager.
   *
   * @var \Drupal\commerce_stock\StockServiceManager
   */
  protected $stockServiceManager;

  /**
   * Default Store.
   *
   * @var \Drupal\commerce_store\Entity\StoreInterface
   */
  protected $store;

  /**
   * Vocabulary Storage.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  protected $vocabularyStorage;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Config Factory Interface.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   * @param \Drupal\commerce_price\CurrencyImporterInterface $currencyImporter
   *   Currency Importer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager.
   * @param \Drupal\commerce_product\ProductAttributeFieldManagerInterface $productAttributeFieldManager
   *   Product Attribute Field Manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   A logger instance.
   * @param \Drupal\magento\Services\MagentoClient $magentoClient
   *   Magento Client.
   * @param \Drupal\commerce_stock\StockServiceManager $stockServiceManager
   *   Stock service manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    ConfigFactoryInterface $config,
    Connection $database,
    CurrencyImporterInterface $currencyImporter,
    EntityTypeManagerInterface $entityTypeManager,
    ProductAttributeFieldManagerInterface $productAttributeFieldManager,
    LoggerChannelFactoryInterface $logger,
    MagentoClient $magentoClient,
    StockServiceManager $stockServiceManager
  ) {
    $this->addOnStorage = $entityTypeManager->getStorage('commerce_addon');
    $this->addOnTypeStorage = $entityTypeManager->getStorage('commerce_addon_type');
    $this->aliasStorage = $entityTypeManager->getStorage('path_alias')->create();
    $this->config = $config->get('magento.settings');
    $this->currencyImporter = $currencyImporter;
    $this->database = $database;
    $this->fieldConfigStorage = $entityTypeManager->getStorage('field_config');
    $this->fieldStorageConfigStorage = $entityTypeManager->getStorage('field_storage_config');
    $this->fileStorage = $entityTypeManager->getStorage('file');
    $this->logger = $logger->get('magento');
    $this->magentoClient = $magentoClient;
    $this->productAttributeFieldManager = $productAttributeFieldManager;
    $this->productAttributeStorage = $entityTypeManager->getStorage('commerce_product_attribute');
    $this->productAttributeValueStorage = $entityTypeManager->getStorage('commerce_product_attribute_value');
    $this->productStorage = $entityTypeManager->getStorage('commerce_product');
    $this->productTypeStorage = $entityTypeManager->getStorage('commerce_product_type');
    $this->productVariationTypeStorage = $entityTypeManager->getStorage('commerce_product_variation_type');
    $this->productVariationStorage = $entityTypeManager->getStorage('commerce_product_variation');
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
    $this->stockServiceManager = $stockServiceManager;
    $this->store = $entityTypeManager->getStorage('commerce_store');
    $this->store = $this->getStore();
    $this->vocabularyStorage = $entityTypeManager->getStorage('taxonomy_vocabulary');
  }

  /**
   * Creates/loads default Drupal store.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface
   *   Store.
   */
  protected function getStore() {
    $country = 'US';
    $currency = 'USD';
    $this->currencyImporter->import($currency);
    $data = [
      'type' => 'online',
      'uid' => 1,
      'name' => 'John Doe',
      'mail' => 'johndoe@example.com',
      'default_currency' => $currency,
      'billing_countries' => [
        $country,
      ],
    ];
    $store = $this->store->loadByProperties($data);
    $store = $store ? reset($store) : FALSE;
    if (!$store) {
      $address = [
        'country_code' => $country,
        'address_line1' => 'Malibu ',
        'locality' => 'Malibu',
        'administrative_area' => 'CA',
        'postal_code' => '93291',
      ];
      $data['address'] = $address;
      $store = $this->store->create($data);
      $this->store->markAsDefault($store);
      try {
        $store->save();
      }
      catch (EntityStorageException $e) {
        $this->logger->error($e->getMessage());
      }
    }
    return $store;
  }

  /**
   * Creates a taxonomy terms recursively.
   *
   * @param array $data
   *   Term data.
   * @param string $vid
   *   Vocabulary ID.
   * @param array $parent
   *   Parent ID(s).
   */
  public function createTerms(array $data, $vid, array $parent = []) {
    $category = $this->magentoClient->getCategory($data['id'] ?? 0);
    if ($category) {
      $name = $data['name'] ?? '';
      $term = reset($this->termStorage->loadByProperties([
        'name' => $name,
        'vid' => $vid,
        'parent' => empty($parent) ? [0] : $parent,
      ]));
      if (!$term) {
        $term = $this->termStorage->create([
          'parent' => $parent,
          'name' => $name,
          'vid' => $vid,
        ]);
        $customAttributes = $category['custom_attributes'] ?? [];
        foreach ($customAttributes as $customAttribute) {
          if ($term->hasField($customAttribute['attribute_code']) && $customAttribute['attribute_code'] !== 'path') {
            $term->set($customAttribute['attribute_code'], $customAttribute['value']);
          }
          else {
            $attribute = $this->magentoClient->getCategoryAttribute(
              $customAttribute['attribute_code'] ?? 0
            );
            if ($attribute) {
              $fieldType = _get_field_type($attribute['backend_type'], $attribute['frontend_input']);
              if (!$term->hasField($customAttribute['attribute_code'])) {
                $this->createConfigurableField($vid, 'taxonomy_term', $customAttribute['attribute_code'], $fieldType);
              }
            }
          }
        }
      }
      try {
        if ($term->save()) {
          $children = $data['children_data'] ?? [];
          foreach ($children as $child) {
            $this->createTerms($child, $vid, [$term->id()]);
          }
        }
      }
      catch (EntityStorageException $e) {
        $this->logger->error($e->getMessage());
      }
    }
  }

  /**
   * Creates a configurable field.
   *
   * @param string $bundle
   *   Bundle.
   * @param string $entityType
   *   Entity Type.
   * @param string $name
   *   Field Name.
   * @param string $type
   *   Field Type.
   *
   * @return bool
   *   Whether the operation was successful or not.
   */
  protected function createConfigurableField($bundle, $entityType, $name, $type) {
    $created = FALSE;
    if (empty($this->fieldStorageConfigStorage->loadByProperties(['field_name' => $name]))) {
      $fieldStorage = $this->fieldStorageConfigStorage->create([
        'field_name' => $name,
        'entity_type' => $entityType,
        'type' => $type,
      ]);
      try {
        if ($fieldStorage->save() && empty($this->fieldConfigStorage->loadByProperties(['field_name' => $name]))) {
          $fieldConfig = $this->fieldConfigStorage->create([
            'field_name' => $name,
            'entity_type' => $entityType,
            'label' => $name,
            'bundle' => $bundle,
          ]);
          $created = $fieldConfig->save();
        }
      }
      catch (EntityStorageException $e) {
        $this->logger->error($e->getMessage());
      }
    }
    return $created;
  }

  /**
   * Retrieves a Magento Product Using ID.
   *
   * @param int $id
   *   Magento Product ID.
   *
   * @return array|null
   *   Magento product array.
   */
  public function getMagentoProduct($id) {
    $searchCriteria = new SearchCriteria([
      'condition' => 'eq',
      'field' => 'entity_id',
      'value' => $id,
    ]);
    return $this->magentoClient->getProducts([$searchCriteria]);
  }

  /**
   * Retrieves/creates a product.
   *
   * @param int $productId
   *   Product ID.
   * @param string $productTypeId
   *   Product Type ID.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface
   *   Product entity.
   */
  protected function getProduct($productId, $productTypeId) {
    $product = $this->productStorage->load($productId);
    if (!$product) {
      $product = $this->productStorage->create([
        'type' => $productTypeId,
      ]);
    }
    return $product;
  }

  /**
   * Retrieves a product ID using product SKU.
   *
   * @param int $sku
   *   Product SKU.
   *
   * @return int
   *   Product ID.
   */
  protected function getProductId($sku) {
    $query = $this->database->select('commerce_product_variation_field_data', 'cpvfd')
      ->condition('cpvfd.sku', $sku, '=')
      ->fields('cpvfd', ['product_id']);
    $rows = $query->execute()->fetchAll();
    $id = 0;
    foreach ($rows as $row) {
      $id = $row->product_id;
    }
    return $id;
  }

  /**
   * Retrieves/creates a product variation.
   *
   * @param int $variationId
   *   Product Variation ID.
   * @param string $variationType
   *   Product Variation Type.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface
   *   Product Variation entity.
   */
  protected function getProductVariation($variationId, $variationType) {
    $variation = $this->productVariationStorage->load($variationId);
    if (!$variation) {
      $variation = $this->productVariationStorage->create([
        'type' => $variationType,
      ]);
    }
    return $variation;
  }

  /**
   * Retrieves/creates a product variation type.
   *
   * @param int $variationTypeId
   *   Product Variation Type ID.
   * @param string $label
   *   Product Variation Type Label.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationTypeInterface
   *   Product Variation entity.
   */
  protected function getProductVariationType($variationTypeId, $label) {
    $variationType = $this->productVariationTypeStorage->load($variationTypeId);
    if (!$variationType) {
      $variationType = $this->productVariationTypeStorage->create([
        'status' => 1,
        'id' => $variationTypeId,
        'label' => $label,
        'orderItemType' => 'default',
        'generateTitle' => TRUE,
      ]);
    }
    return $variationType;
  }

  /**
   * Retrieves/creates a product attribute.
   *
   * @param string $attributeId
   *   Product Attribute ID.
   * @param string $label
   *   Product Attribute Label.
   *
   * @return \Drupal\commerce_product\Entity\ProductAttributeInterface
   *   Product Variation entity.
   */
  protected function getProductAttribute($attributeId, $label) {
    $attribute = $this->productAttributeStorage->load($attributeId);
    if (!$attribute) {
      $attribute = $this->productAttributeStorage->create([
        'id' => $attributeId,
        'label' => $label,
      ]);
    }
    return $attribute;
  }

  /**
   * Retrieves/creates a product attribute value.
   *
   * @param string $attributeValueId
   *   Product Attribute Value ID.
   * @param string $label
   *   Product Attribute Value Label.
   *
   * @return \Drupal\commerce_product\Entity\ProductAttributeValueInterface
   *   Product Variation entity.
   */
  protected function getProductAttributeValue($attributeValueId, $label) {
    $attributeValue = $this->productAttributeValueStorage->load($attributeValueId);
    if (!$attributeValue) {
      $attributeValue = $this->productAttributeValueStorage->create([
        'attribute' => $attributeValueId,
        'name' => $label,
      ]);
    }
    return $attributeValue;
  }

  /**
   * Adds an attribute value to an attribute.
   *
   * @param string $attributeId
   *   Product Attribute ID.
   * @param string $attributeValueId
   *   Product Attribute Value ID.
   * @param string $attributeValueName
   *   Product Attribute Value Label.
   *
   * @return int
   *   Number of rows affected.
   */
  protected function addAttributeValue($attributeId, $attributeValueId, $attributeValueName) {
    return $this->database->merge('commerce_product_attribute_value_field_data')
      ->key([
        'attribute_value_id' => $attributeValueId,
        'langcode' => 'en',
      ])
      ->fields([
        'attribute' => $attributeId,
        'attribute_value_id' => $attributeValueId,
        'name' => $attributeValueName,
        'langcode' => 'en',
        'weight' => 1,
        'created' => strtotime('now'),
        'changed' => strtotime('now'),
        'default_langcode' => 1,
      ])->execute();
  }

  /**
   * Retrieves a product variation using SKU.
   *
   * @param string $sku
   *   Product variation SKU.
   *
   * @return int
   *   ID of the product variation.
   */
  protected function getProductVariationId($sku) {
    $query = $this->database->select('commerce_product_variation_field_data', 'cpvfd')
      ->condition('cpvfd.sku', $sku, '=')
      ->fields('cpvfd', ['variation_id']);
    $rows = $query->execute()->fetchAll();
    $id = 0;
    foreach ($rows as $row) {
      $id = $row->variation_id;
    }
    return $id;
  }

  /**
   * Retrieves product variation types using Magento Product Type ID.
   *
   * @param string $typeId
   *   Magento Product Type ID.
   * @param array $configurableOptions
   *   Product configurable options.
   * @param array $productLinks
   *   Product links.
   *
   * @return array
   *   Array of product variation types.
   */
  protected function getProductVariationTypes($typeId, array $configurableOptions = [], array $productLinks = []) {
    $variationTypes = [];
    switch ($typeId) {
      case 'bundle':
        break;

      case 'grouped':
        foreach ($productLinks as $productLink) {
          if (isset($productLink['link_type']) && $productLink['link_type'] === 'associated') {
            $variationTypes[] = [
              'variation_type' => preg_replace('@[^a-z0-9_]+@', '_', strtolower($productLink['sku'])),
            ];
          }
        }
        break;

      case 'simple':
        $variationTypes[] = [
          'variation_type' => 'default',
        ];
        break;

      case 'configurable':
        foreach ($configurableOptions as $configurableOption) {
          $variationTypes[] = [
            'variation_type' => preg_replace('@[^a-z0-9_]+@', '_', strtolower($configurableOption['label'])),
            'attribute_id' => $configurableOption['attribute_id'],
          ];
        }
        $variationTypes = empty($variationTypes) ? ['variation_type' => 'default'] : $variationTypes;
        break;

      case 'virtual':
        // Downloadable products
        // variations of configurable product are also virtual products.
        break;
    }
    return $variationTypes;
  }

  /**
   * Retrieves/creates an add-on type field.
   *
   * @param string $fieldName
   *   Field name.
   * @param string $fieldType
   *   Field type.
   * @param array $values
   *   Field allowed values, if any.
   *
   * @return bool
   *   Whether or not the field exists/created.
   */
  protected function getProductAddOnTypeField($fieldName, $fieldType, array $values = []) {
    $entityTypeId = 'commerce_addon';
    $field = FieldConfig::loadByName($entityTypeId, 'field', $fieldName);
    try {
      if (!$field) {
        $field_storage = FieldStorageConfig::loadByName($entityTypeId, $fieldName);
        if (!$field_storage) {
          $field_storage = FieldStorageConfig::create([
            'entity_type' => $entityTypeId,
            'field_name' => $fieldName,
            'type' => $fieldType,
          ]);
          $allowedValues = [];
          foreach ($values as $value) {
            $allowedValues[$value] = $value;
          }
          $field_storage->setSettings(['allowed_values' => $allowedValues]);
          $field_storage->save();
        }
        $field = FieldConfig::create([
          'field_storage' => $field_storage,
          'bundle' => 'field',
          'label' => $fieldName,
        ]);
        return $field->save();
      }
      else {
        return TRUE;
      }
    }
    catch (EntityStorageException $e) {
      $this->logger->error($e->getMessage());
    }
    return FALSE;
  }

  /**
   * Retrieves/creates an add-on type of Drupal.
   *
   * @param string $magentoOptionType
   *   Magento Product Add-on Type.
   * @param string $magentoOptionId
   *   Magento Option.
   * @param array $values
   *   Magento Option Values.
   *
   * @return string
   *   Add-on Type.
   */
  protected function getProductAddOnType($magentoOptionType, $magentoOptionId, array $values = []) {
    $drupalAddOnType = 'default';
    $fieldName = 'field_product_option_field';
    $fieldType = 'string_long';
    switch ($magentoOptionType) {
      case 'area':
      case 'field':
        $fieldName = 'field_product_option_field';
        $fieldType = 'string_long';
        $drupalAddOnType = 'field';
        break;

      case 'checkbox':
      case 'drop_down':
      case 'multiple':
      case 'radio':
        $fieldType = 'list_string';
        $drupalAddOnType = $fieldName = 'list_string' . $magentoOptionId;
        break;

      case 'file':
        $fieldType = 'file';
        $drupalAddOnType = $fieldName = 'file' . $magentoOptionId;
        break;
    }
    $productAddOnType = $this->addOnTypeStorage->load($drupalAddOnType);
    if (!$productAddOnType) {
      $addOnType = $this->addOnTypeStorage->create(
        [
          'id' => $drupalAddOnType,
          'label' => $drupalAddOnType,
        ]
      );
      try {
        if ($addOnType->save()) {
          return $addOnType->id();
        }
      }
      catch (EntityStorageException $e) {
        $this->logger->error($e->getMessage());
      }
    }
    $this->getProductAddOnTypeField($fieldName, $fieldType, $values);
    return $drupalAddOnType;
  }

  /**
   * Retrieves/creates a vocabulary.
   *
   * @param string $vid
   *   Magento Product Type ID.
   * @param array $data
   *   Vocabulary data.
   *
   * @return \Drupal\taxonomy\Entity\Vocabulary
   *   Vocabulary object.
   */
  public function getVocabulary($vid, array $data = []) {
    $vocabulary = $this->vocabularyStorage->load($vid);
    if (!$vocabulary) {
      $vocabulary = $this->vocabularyStorage->create($data);
    }
    try {
      $vocabulary->save();
    }
    catch (EntityStorageException $e) {
      $this->logger->error($e->getMessage());
    }
    return $vocabulary;
  }

  /**
   * Retrieves Drupal taxonomy term ID.
   *
   * @param string $magentoCategoryId
   *   Magento Category ID.
   *
   * @return int
   *   Drupal term ID.
   */
  public function getCategoryTerm($magentoCategoryId) {
    $category = $this->magentoClient->getCategory($magentoCategoryId);
    if ($category) {
      $name = $category['name'] ?? '';
      $term = reset($this->termStorage->loadByProperties([
        'name' => $name,
      ]));

      return $term ? $term->id() : 0;
    }
  }

  /**
   * Makes an HTTP request.
   *
   * @param array $data
   *   Product data.
   * @param string $currencyCode
   *   Currency Code.
   *
   * @return int
   *   ID of the created product entity.
   */
  public function createProduct(array $data, $currencyCode) {
    $id = 0;
    $productType = $this->productTypeStorage->load($data['attribute_set_id']);
    $productTypes = $this->magentoClient->getProductAttributeSets(new SearchCriteria([
      'field' => 'attribute_set_id',
      'condition' => 'gt',
      'value' => 0,
    ]));
    if ($productType) {
      $attribute_mapping = [];
      $drupalProductTypeFields = $data['custom_attributes'] ?? [];
      $variations = [];
      $sku = $data['sku'];
      $configurableProductOptions = $data['extension_attributes']['configurable_product_options'] ?? [];
      $configurableProductLinks = $data['extension_attributes']['configurable_product_links'] ?? [];
      $product_links = $data['product_links'] ?? [];
      $variationTypes = $this->getProductVariationTypes($data['type_id'], $configurableProductOptions, $product_links);
      $quantity = (float) ($data['extension_attributes']['stock_item']['qty'] ?? 0);
      $mediaGalleryEntries = $data['media_gallery_entries'] ?? [];
      try {
        foreach ($productTypes as $product) {
          $variationTypes[] = [
            'variation_type' => str_replace(" ", "_", strtolower($product['attribute_set_name'])),
            'attribute_id' => $product['attribute_set_id'],
          ];
          $customVariations[] = [
            'variation_type' => str_replace(" ", "_", strtolower($product['attribute_set_name'])),
            'attribute_id' => $product['attribute_set_id'],
          ];
        }
        foreach ($variationTypes as $variationType) {
          $drupalVariationType = $this->getProductVariationType($variationType['variation_type'], $variationType['variation_type']);
          if ($drupalVariationType->save()) {
            $drupalAttribute = $this->getProductAttribute($variationType['variation_type'], $variationType['variation_type']);
            // Adding image fields @TODO to be replaced by dynamic code.
            $this->addProductVariationImageField($drupalAttribute, 'image', 'image');
            $this->addProductVariationImageField($drupalAttribute, 'small_image', 'small_image');
            $this->addProductVariationImageField($drupalAttribute, 'thumbnail', 'thumbnail');

            if ($drupalAttribute->save()) {
              if (!in_array($drupalAttribute->id(), $this->productAttributeFieldManager->getFieldMap($drupalVariationType->id()))) {
                $this->productAttributeFieldManager->createField($drupalAttribute, $drupalVariationType->id());
              }
            }
            if (isset($variationType['attribute_id'])) {
              if (!in_array($variationType, $customVariations)) {
                $magentoAttribute = $this->magentoClient->getProductAttribute($variationType['attribute_id']);
              }
              $options = $magentoAttribute['options'] ?? [];
              $attribute_mapping[$variationType['attribute_id']] = $options;
              foreach ($options as $option) {
                if (isset($option['label']) && $option['value']) {
                  $attributeValue = $this->getProductAttributeValue($option['value'], $option['label']);
                  if ($attributeValue->save()) {
                    $this->addAttributeValue($drupalAttribute->id(), $attributeValue->id(), $attributeValue->getName());
                  }
                }
              }
            }
          }
        }
        if (empty($configurableProductLinks) && !empty($data['product_links'])) {
          $price = isset($data['price']) && $data['price']
            ? $data['price'] : 0;
          $setId = $data['attribute_set_id'];
          foreach ($variationTypes as $variation) {
            if ($variation['variation_type'] !== 'default' && $variation['attribute_id'] == $setId) {
              $variationType = $variation['variation_type'];
            }
          }
          $variation = reset($this->productVariationStorage->loadByProperties([
            'sku' => $data['sku'],
          ]));
          if ($variation) {
            $variation->setPrice(new Price($price, $currencyCode));
          }
          else {
            $variation = $this->productVariationStorage->create([
              'type' => $variationType,
              'sku' => $sku,
              'price' => new Price((string) $price, $currencyCode),
              'title' => 'Default',
            ]);
          }
          $customAttributes = $data['custom_attributes'] ?? [];
          foreach ($customAttributes as $customAttribute) {
            $fieldName = 'attribute_' . $customAttribute['attribute_code'];
            if ($variation->hasField($fieldName)) {
              $variation->set($fieldName, $customAttribute['value']);
            }
          }
          // $variation->set('field_stock', $quantity);
          if ($variation->save()) {
            $variations[] = $variation;
            if ($quantity > 0) {
              $this->stockServiceManager->receiveStock($variation, 1, 'default', $quantity, $price, $currencyCode);
            }
          }
        }
        // If the product have different variations.
        foreach ($configurableProductLinks as $configurableProductLink) {
          $magentoConfigurableProduct = $this->getMagentoProduct($configurableProductLink);
          if ($magentoConfigurableProduct) {
            $customAttributes = $magentoConfigurableProduct[0]['custom_attributes'] ?? [];
            $var = reset($this->productVariationStorage->loadByProperties(
              [
                'sku' =>
                $magentoConfigurableProduct[0]['sku'],
              ]
            ));
            if ($var) {
              $this->updateVarationData($var, $magentoConfigurableProduct, $currencyCode, $customAttributes);
            }
            else {
              $setId = $magentoConfigurableProduct[0]['attribute_set_id'];
              foreach ($variationTypes as $variation) {
                if ($variation['attribute_id'] == $setId) {
                  $drupalVariationType = $this->getProductVariationType($variation['variation_type'], $variation['variation_type']);
                  $attributesToMap = ['color', 'size'];
                  foreach ($attributesToMap as $attributeType) {
                    $this->mapFields($attributeType, $drupalVariationType);
                  }
                }
              }
              $price = isset($magentoConfigurableProduct[0]['price']) && $magentoConfigurableProduct[0]['price']
                ? $magentoConfigurableProduct[0]['price'] : 0;
              $variation = $this->productVariationStorage->create(['type' => $drupalVariationType->id()]);
              $variation->setTitle($magentoConfigurableProduct[0]['name']);
              $variation->setSku($magentoConfigurableProduct[0]['sku']);
              $variation->setPrice(new Price($price, $currencyCode));
              $imgData = [];
              foreach ($customAttributes as $customAttribute) {
                if ($customAttribute['attribute_code'] == 'image' || $customAttribute['attribute_code'] == 'small_image' || $customAttribute['attribute_code'] == 'thumbnail') {
                  $imgData[] = [
                    'attribute_code' => $customAttribute['attribute_code'],
                    'value' => $customAttribute['value'],
                  ];
                }
                // Mapping Images to the variation type.
                $fieldName = 'attribute_' . $customAttribute['attribute_code'];
                foreach ($attribute_mapping as $options) {
                  foreach ($options as $option) {
                    if ($option['value'] == $customAttribute['value']) {
                      $attribute = $this->getProductAttributeValue($option['value'], $option['label']);
                      $customAttribute['value'] = $attribute->id();
                    }
                  }
                }
                if ($variation->hasField($fieldName)) {
                  $variation->set($fieldName, $customAttribute['value']);
                }
              }
              $this->mapImagesToVariations($variation, $imgData);
              if ($variation->save()) {
                $variations[] = $variation;
                if ($quantity > 0) {
                  $this->stockServiceManager->receiveStock($variation, 1, 'default', $quantity, $price, $currencyCode);
                }
              }
            }
          }
        }
        if (count($variations) > 0) {
          $alias = '';
          $pro = reset($this->productStorage->loadByProperties([
            'sku' => $data['sku'],
          ]));
          if ($pro) {
            $pid = $pro->id();
            $product = $this->productStorage->load($pid);
          }
          else {
            $product = $this->productStorage->create([
              'type' => $productType->id(),
              'title' => $data['name'],
              'variations' => $variations,
              'stores' => [$this->store],
              'sku' => $sku,
            ]);
          }
          $options = $data['options'] ?? [];
          foreach ($drupalProductTypeFields as $drupalProductTypeField) {
            if ($product->hasField($drupalProductTypeField['attribute_code'])) {
              $magentoAttribute = $this->magentoClient->getProductAttribute($drupalProductTypeField['attribute_code']);

              if ($magentoAttribute['frontend_input'] === 'media_image') {
                $urls = explode(',', $drupalProductTypeField['value']);
                $values = [];
                foreach ($urls as $url) {
                  $url = str_replace("_1", "", $url);
                  $baseUrl = $this->config->get('magento_url');
                  $imgUri = $baseUrl . '/media/catalog/product/' . $url;
                  $uri = file_build_uri('products' . $url);
                  $path = explode('//', $uri);
                  $pathOccurance = strripos($path[1], '/');
                  $fullPath = str_split($path[1], $pathOccurance);
                  $dir = 'sites/default/files/' . $fullPath[0];
                  if (!file_exists($dir)) {
                    mkdir($dir, 0777, TRUE);
                  }
                  $file_path = $dir . substr($path[1], $pathOccurance);
                  copy($imgUri, $file_path);
                  $file = File::Create(['uri' => $uri]);
                  $file->setPermanent();
                  if ($file->save()) {
                    $values[] = [
                      'target_id' => $file->id(),
                      'alt' => $drupalProductTypeField['attribute_code'],
                      'title' => $drupalProductTypeField['attribute_code'],
                    ];
                  }
                }
                $product->set($drupalProductTypeField['attribute_code'], $values);
              }
              elseif ($drupalProductTypeField['attribute_code'] === 'category_ids') {
                $category_ids = $drupalProductTypeField['value'];
                $fieldName = $drupalProductTypeField['attribute_code'];
                foreach ($category_ids as $category_id) {
                  $product->$fieldName->appendItem($this->getCategoryTerm($category_id));
                }
              }
              elseif ($drupalProductTypeField['attribute_code'] === 'description') {
                $product->set('description', [
                  'value' => $drupalProductTypeField['value'],
                  'format' => 'full_html',
                ]);
              }
              elseif ($drupalProductTypeField['attribute_code'] === 'url_path' && !empty($drupalProductTypeField['value'])) {
                $alias = substr($drupalProductTypeField['value'], 0, 1) !== '/'
                  ? '/' . $drupalProductTypeField['value'] : $drupalProductTypeField['value'];
              }
              else {
                $product->set($drupalProductTypeField['attribute_code'], $drupalProductTypeField['value']);
              }
            }
          }

          if ($product->hasField('field_media_gallery_entries') && $product->hasField('field_video_embed')) {
            foreach ($mediaGalleryEntries as $mediaGalleryEntry) {
              if ($mediaGalleryEntry['media_type'] === 'image') {
                $uri = file_build_uri('products' . $mediaGalleryEntry['file']);
                $file = File::Create(['uri' => $uri]);
                if ($file->save()) {
                  $product->field_media_gallery_entries->appendItem([
                    'target_id' => $file->id(),
                    'alt' => $mediaGalleryEntry['label'],
                    'title' => $mediaGalleryEntry['label'],
                  ]);
                }
              }
              elseif ($mediaGalleryEntry['media_type'] === 'external-video') {
                $url = $mediaGalleryEntry['extension_attributes']['video_content']['video_url'] ?? '';
                if (!empty($url)) {
                  $product->field_video_embed->appendItem(stripcslashes($url));
                }
              }
            }
          }
          $id = $product->save() ? $product->id() : $id;
          if ($id && !empty($alias)) {
            $variations = $product->getVariations();
            if ($quantity > 0) {
              foreach ($variations as $variation) {
                $variation->set('field_stock', $quantity);
                $variation->save();
              }
            }
            $product->save();
            $this->aliasStorage->save('/product/' . $id, $alias, 'en');
          }
        }
      }
      catch (EntityStorageException $e) {
        $this->logger->error($e->getMessage());
      }
    }
    return $id;
  }

  /**
   * Function to add image field in product variation.
   */
  public function addProductVariationImageField($variation, $name, $label) {
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('commerce_product_variation', $variation->id());    if (!isset($fields[$name])) {
      $configurable_field_manager = \Drupal::service('commerce.configurable_field_manager');
      $field_definition = BundleFieldDefinition::create('image')
        ->setTargetEntityTypeId('commerce_product_variation')
        ->setTargetBundle($variation->id())
        ->setName($name)
        ->setLabel($label)
        // @todo See if it is possible to get this from API.
        ->setCardinality(1)
        ->setRequired('required')
        ->setTranslatable(TRUE)
        ->setDefaultValue(NULL)
        ->setDisplayOptions('form', ['weight' => 4])
        ->setDisplayOptions('view', ['weight' => 3]);
      $configurable_field_manager->createField($field_definition, FALSE);
    }
  }

  /**
   * Function to update variation data.
   */
  public function updateVarationData($variation, $variationData, $currencyCode, $customAttributes) {
    $price = isset($variationData[0]['price']) && $variationData[0]['price']
      ? $variationData[0]['price'] : 0;
    $variation->setTitle($variationData[0]['name']);
    $variation->setPrice(new Price($price, $currencyCode));

    // @todo check if image name already exists & if exists skip the request.
    // $fid=$variation->get('small_image')->getValue()->first();exit;
    // $this->entityTypeManager->getStorage('file')->load($fid);
    $imgData = [];
    foreach ($customAttributes as $customAttribute) {
      if ($customAttribute['attribute_code'] == 'image' || $customAttribute['attribute_code'] == 'small_image' || $customAttribute['attribute_code'] == 'thumbnail') {
        $imgData[] = [
          'attribute_code' => $customAttribute['attribute_code'],
          'value' => $customAttribute['value'],
        ];
      }
    }
    $this->mapImagesToVariations($variation, $imgData);
    return $variation;
  }

  /**
   * Function to map color and size to variation types.
   */
  public function mapFields($attributeType, $drupalVariationType) {
    $drupalAttribute = $this->getProductAttribute($attributeType, $attributeType);
    $this->productAttributeFieldManager->createField($drupalAttribute, $drupalVariationType->id());
  }

  /**
   * Map images to variations type.
   */
  public function mapImagesToVariations($variation, $imgData) {
    foreach ($imgData as $img) {
      $url = str_replace("_1", "", $img['value']);
      $baseUrl = $this->config->get('magento_url');
      $imgUri = $baseUrl . '/media/catalog/product/' . $url;
      $uri = file_build_uri('products' . $url);
      $path = explode('//', $uri);
      $pathOccurance = strripos($path[1], '/');
      $fullPath = str_split($path[1], $pathOccurance);
      $dir = 'sites/default/files/' . $fullPath[0];
      if (!file_exists($dir)) {
        mkdir($dir, 0777, TRUE);
      }
      $file_path = $dir . substr($path[1], $pathOccurance);
      copy($imgUri, $file_path);
      $file = File::Create(['uri' => $uri]);
      $file->setPermanent();
      if ($file->save()) {
        $values[] = [
          'target_id' => $file->id(),
          'alt' => $img['attribute_code'],
          'title' => $img['attribute_code'],
        ];
      }
      $variation->set($img['attribute_code'], $values);
      $variation->save();
    }
  }

}
