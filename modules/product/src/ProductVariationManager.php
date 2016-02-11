<?php

namespace Drupal\commerce_product;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Event\FilterVariationsEvent;
use Drupal\commerce_product\Event\ProductEvents;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductVariationManager implements ProductVariationManagerInterface {

  /**
   * The variation storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $variationStorage;

  /**
   * The entity query for product variation.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $entityQuery;


  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a ProductVariationManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueryFactory $query_factory, EventDispatcherInterface $event_dispatcher) {
    $this->variationStorage = $entity_type_manager->getStorage('commerce_product_variation');
    $this->entityQuery = $query_factory->get('commerce_product_variation');
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledVariations(ProductInterface $product) {
    $ids = [];
    foreach($product->variations as $variation) {
      $ids[] = $variation->target_id;
    }

    $query = $this->entityQuery
      ->condition('status', TRUE)
      ->condition('variation_id', $ids, "IN");
    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    $enabled_variations = $this->variationStorage->loadMultiple($result);
    $event = new FilterVariationsEvent($product, $enabled_variations);
    $this->eventDispatcher->dispatch(ProductEvents::FILTER_VARIATIONS, $event);
    $enabled_variations = $event->getVariations();

    return $enabled_variations;
  }

}
