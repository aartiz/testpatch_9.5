<?php

namespace Drupal\magento\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\magento\Model\SearchCriteria;
use Drupal\magento\Model\SortCriteria;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MagentoClient.
 *
 * @package \Drupal\magento\Services
 */
class MagentoClient {

  /**
   * Config Factory Interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Guzzle HTTP Client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Config Factory Interface.
   * @param \GuzzleHttp\Client $client
   *   Guzzle HTTP Client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   A logger instance.
   */
  public function __construct(ConfigFactoryInterface $config, Client $client, LoggerChannelFactoryInterface $logger) {
    $this->client = $client;
    $this->config = $config->get('magento.settings');
    $this->logger = $logger->get('magento');
  }

  /**
   * Retrieves the base URI of Magento.
   *
   * @return string
   *   Base URI.
   */
  protected function getBaseUri() {
    return $this->config->get('magento_url') . str_replace('{vid}', $this->config->get('api_version'), $this->config->get('base_uri'));
  }

  /**
   * Makes an HTTP request.
   *
   * @param string $method
   *   Request method.
   * @param string $endpoint
   *   Request Endpoint.
   * @param array $options
   *   Request Options.
   *
   * @return array|null
   *   Response data.
   */
  protected function request($method, $endpoint, array $options = []) {
    $data = NULL;
    try {
      $uri = $this->getBaseUri() . $endpoint;
      $options['debug'] = $this->config->get('debug_mode');
      $options['headers'] = ['Authorization' => 'Bearer ' . $this->config->get('access_token')];
      $response = $this->client->request($method, $uri, $options);
      if ($response->getStatusCode() === Response::HTTP_OK) {
        $data = json_decode($response->getBody(), TRUE);
      }
    }
    catch (GuzzleException $e) {
      $this->logger->error($e->getMessage());
    }
    return $data;
  }

  /**
   * Retrieves a default search criteria.
   *
   * @return \Drupal\magento\Model\SearchCriteria
   *   Default search criteria.
   */
  protected function getDefaultSearchCriteria() {
    return new SearchCriteria([
      'field' => 'entity_id',
      'value' => 0,
      'condition' => 'gt',
    ]);
  }

  /**
   * Retrieves the request query for search criterion.
   *
   * @param array $searchCriterion
   *   Search Criterion.
   * @param string $condition
   *   Logical Condition.
   * @param int $pageSize
   *   Page Size.
   * @param array $sortCriterion
   *   Sort Criterion.
   *
   * @return array
   *   Request Query.
   */
  protected function getSearchCriteriaQuery(array $searchCriterion, $condition = NULL, $pageSize = 0, array $sortCriterion = []) {
    $requestQuery = [];
    foreach ($searchCriterion as $index => $searchCriteria) {
      if ($condition) {
        $filterGroupIndex = $condition === 'and' ? $index : 0;
        $filterIndex = $condition === 'or' ? $index : 0;
      }
      else {
        $filterGroupIndex = $filterIndex = $index;
      }
      $requestQuery['searchCriteria']['filter_groups'][$filterGroupIndex]['filters'][$filterIndex]['field'] = $searchCriteria->field;
      $requestQuery['searchCriteria']['filter_groups'][$filterGroupIndex]['filters'][$filterIndex]['value'] = $searchCriteria->value;
      $requestQuery['searchCriteria']['filter_groups'][$filterGroupIndex]['filters'][$filterIndex]['condition_type'] = $searchCriteria->condition;
    }
    foreach ($sortCriterion as $index => $sortCriteria) {
      $requestQuery['searchCriteria']['sortOrders'][$index]['field'] = $sortCriterion->field;
      $requestQuery['searchCriteria']['sortOrders'][$index]['direction'] = $sortCriterion->direction;
    }
    $requestQuery['searchCriteria']['pageSize'] = $pageSize;
    return $requestQuery;
  }

  /**
   * Creates a guest cart.
   *
   * @return string
   *   Guest cart salt.
   */
  public function createGuestCart() {
    return (string) $this->request($this->config->get('endpoints.guest-cart.create.method'), $this->config->get('endpoints.guest-cart.create.uri'));
  }

  /**
   * Creates a product.
   *
   * @param array $payLoad
   *   Product payload.
   *
   * @return int|false
   *   ID of the product created.
   */
  public function createProduct(array $payLoad) {
    $options = [
      'json' => $payLoad,
    ];
    $data = $this->request($this->config->get('endpoints.products.create.method'), $this->config->get('endpoints.products.create.uri'), $options);
    return $data['id'] ?? FALSE;
  }

  /**
   * Retrieves a single product attribute of category.
   *
   * @param string $attributeCode
   *   Attribute Code.
   *
   * @return array|null
   *   Response data.
   */
  public function getCategoryAttribute($attributeCode) {
    return $this->request(
      $this->config->get('endpoints.categories.attributes.individual.method'),
      str_replace('{attributeCode}', $attributeCode, $this->config->get('endpoints.categories.attributes.individual.uri'))
    );
  }

  /**
   * Retrieves all the attributes of categories.
   *
   * @param \Drupal\magento\Model\SearchCriteria $searchCriteria
   *   Search Criteria.
   *
   * @return array|null
   *   Response data.
   */
  public function getCategoryAttributes(SearchCriteria $searchCriteria) {
    return $this->request(
      $this->config->get('endpoints.categories.attributes.base.method'),
      $this->config->get('endpoints.categories.attributes.base.uri'), [
        'query' => $this->getSearchCriteriaQuery([$searchCriteria]),
      ]
    );
  }

  /**
   * Retrieves a single product attribute of products.
   *
   * @param string $attributeCode
   *   Attribute Code.
   *
   * @return array|null
   *   Response data.
   */
  public function getProductAttribute($attributeCode) {
    return $this->request(
      $this->config->get('endpoints.products.attributes.individual.method'),
      str_replace('{attributeCode}', $attributeCode, $this->config->get('endpoints.products.attributes.individual.uri'))
    );
  }

  /**
   * Retrieves all the attributes of products.
   *
   * @param \Drupal\magento\Model\SearchCriteria $searchCriteria
   *   Search Criteria.
   *
   * @return array|null
   *   Response data.
   */
  public function getProductAttributes(SearchCriteria $searchCriteria) {
    return $this->request(
      $this->config->get('endpoints.products.attributes.base.method'),
      $this->config->get('endpoints.products.attributes.base.uri'), [
        'query' => $this->getSearchCriteriaQuery([$searchCriteria]),
      ]
    );
  }

  /**
   * Retrieves a single attribute set of products.
   *
   * @param string $attributeSetId
   *   Attribute Set ID.
   *
   * @return array|null
   *   Response data.
   */
  public function getProductAttributeSet($attributeSetId) {
    return $this->request(
      $this->config->get('endpoints.products.attribute_sets.individual.method'),
      str_replace('{attributeSetId}', $attributeSetId, $this->config->get('endpoints.products.attribute_sets.individual.uri'))
    );
  }

  /**
   * Retrieves all the attributes of an attribute set.
   *
   * @param string $attributeSetId
   *   Attribute Set ID.
   *
   * @return array|null
   *   Response data.
   */
  public function getProductAttributeSetAttributes($attributeSetId) {
    return $this->request(
      $this->config->get('endpoints.products.attribute_sets.attributes.method'),
      str_replace('{attributeSetId}', $attributeSetId, $this->config->get('endpoints.products.attribute_sets.attributes.uri'))
    );
  }

  /**
   * Retrieves all the attribute sets of products.
   *
   * @param \Drupal\magento\Model\SearchCriteria $searchCriteria
   *   Search Criteria.
   *
   * @return array|null
   *   Response data.
   */
  public function getProductAttributeSets(SearchCriteria $searchCriteria) {
    $data = $this->request(
      $this->config->get('endpoints.products.attribute_sets.base.method'),
      $this->config->get('endpoints.products.attribute_sets.base.uri'), [
        'query' => $this->getSearchCriteriaQuery([$searchCriteria]),
      ]
    );
    return $data['items'] ?? NULL;
  }

  /**
   * Retrieves a single category.
   *
   * @param int $categoryId
   *   Category ID.
   *
   * @return array|null
   *   Response data.
   */
  public function getCategory($categoryId) {
    return $this->request(
      $this->config->get('endpoints.categories.individual.method'),
      str_replace('{categoryId}', $categoryId, $this->config->get('endpoints.categories.individual.uri'))
    );
  }

  /**
   * Retrieves all the categories.
   *
   * @param array $searchCriteria
   *   Search Criteria.
   *
   * @return array
   *   Response data.
   */
  public function getCategories(array $searchCriteria = []) {
    $data = $this->request(
      $this->config->get('endpoints.categories.base.method'),
      $this->config->get('endpoints.categories.base.uri'), [
        'query' => $this->getSearchCriteriaQuery(
          empty($searchCriteria) ? [$this->getDefaultSearchCriteria()] : $searchCriteria
        ),
      ]
    );
    return $data['items'] ?? [];
  }

  /**
   * Retrieves all the categories in their hierarchy.
   *
   * @return array
   *   Response data.
   */
  public function getCategoriesHierarchy() {
    return $this->request(
      $this->config->get('endpoints.categories.hierarchy.method'),
      $this->config->get('endpoints.categories.hierarchy.uri')
    );
  }

  /**
   * Retrieves the currency code.
   *
   * @return string|false
   *   Currency Code.
   */
  public function getCurrencyCode() {
    $data = $this->request(
      $this->config->get('endpoints.currency.base.method'),
      $this->config->get('endpoints.currency.base.uri')
    );
    return $data['base_currency_code'] ?? FALSE;
  }

  /**
   * Retrieves the Magento ID of the first product.
   *
   * @return int
   *   Magento ID of the first product.
   */
  public function getFirstProductId() {
    $sortCriteria = new SortCriteria([
      'field' => 'entity_id',
      'direction' => 'ASC',
    ]);
    $requestQuery = $this->getSearchCriteriaQuery([$this->getDefaultSearchCriteria()], NULL, 1, [$sortCriteria]);
    $requestQuery['fields'] = 'items[id]';
    $data = $this->request(
      $this->config->get('endpoints.products.base.method'),
      $this->config->get('endpoints.products.base.uri'), [
        'query' => $requestQuery,
      ]
    );
    return $data['items'][0]['id'] ?? 0;
  }

  /**
   * Retrieves a single product.
   *
   * @param string $sku
   *   Product SKU.
   *
   * @return array|null
   *   Response data.
   */
  public function getProduct($sku) {
    return $this->request(
      $this->config->get('endpoints.products.individual.method'),
      str_replace('{sku}', $sku, $this->config->get('endpoints.products.individual.uri'))
    );
  }

  /**
   * Retrieves all the products list.
   *
   * @param array $searchCriteria
   *   Search Criteria.
   * @param string $fields
   *   Comma separated fields.
   * @param int $pageSize
   *   Page Size.
   * @param array $sortCriteria
   *   Sort Criteria.
   *
   * @return array
   *   Response data.
   */
  public function getProducts(array $searchCriteria, $fields = '', $pageSize = 100, array $sortCriteria = []) {
    $requestQuery = $this->getSearchCriteriaQuery($searchCriteria);
    $requestQuery['fields'] = $fields;
    $data = $this->request(
      $this->config->get('endpoints.products.base.method'),
      $this->config->get('endpoints.products.base.uri'), [
        'query' => $this->getSearchCriteriaQuery($searchCriteria, NULL, $pageSize, $sortCriteria),
      ]
    );
    return $data['items'] ?? [];
  }

  /**
   * Retrieves the count of total products.
   *
   * @return int
   *   Total count.
   */
  public function getProductsCount() {
    $data = $this->request(
      $this->config->get('endpoints.products.base.method'),
      $this->config->get('endpoints.products.base.uri'), [
        'query' => $this->getSearchCriteriaQuery([], NULL, 1),
      ]
    );
    return $data['total_count'] ?? 0;
  }

  /**
   * Retrieves the reviews of a product.
   *
   * @param int $id
   *   Magento Product ID.
   *
   * @return array
   *   Product Reviews.
   */
  public function getProductReviews($id) {
    $data = $this->request(
      $this->config->get('endpoints.products.reviews.method'),
      str_replace('{id}', $id, $this->config->get('endpoints.products.reviews.uri'))
    );
    return $data[0]['product_reviews'] ?? [];
  }

}
