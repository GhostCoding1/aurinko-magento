<?php

namespace Markup\Smartship\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
  protected $carrier;

  public function __construct(
    \Markup\Smartship\Model\Carrier $carrier
  ) {
    $this->carrier = $carrier;
  }

  /**
  * {@inheritdoc}
  */
  public function getConfig()
  {
    $config = [];
    $config['smartship'] = [];
    $config['smartship']['postipaketti_mode'] = $this->carrier->getPostipakettiMode();

    return $config;
  }
}
