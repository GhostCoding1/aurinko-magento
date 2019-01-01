<?php
/**
 * Blueskytechco
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Blueskytechco.com license that is
 * available through the world-wide-web at this URL:
 * http://www.blueskytechco.com/license-agreement.html
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category   Blueskytechco
 * @package    Rokanthemes_Brand
 * @copyright  Copyright (c) 2014 Blueskytechco (http://www.blueskytechco.com/)
 * @license    http://www.blueskytechco.com/LICENSE-1.0.html
 */
namespace Rokanthemes\Brand\Model\Source;
use Magento\Framework\Data\OptionSourceInterface;

class IsActive implements OptionSourceInterface
{
	/**
	 * @var \Rokanthemes\Brand\Model\Brand
	 */
	protected $brandModel;

	/**
     * Constructor
     *
     * @param \Rokanthemes\Brand\Model\Brand $brandModel
     */
	public function __construct(\Rokanthemes\Brand\Model\Brand $brandModel)
	{
		$this->brandModel = $brandModel;
	}

	/**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options[] = ['label' => '', 'value' => ''];
        $availableOptions = $this->brandModel->getAvailableStatuses();

        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
 
        return $options;
    }
}