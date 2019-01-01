<?php
namespace Piimega\CheckoutFinland\Model\Config\Source\Payment;

class Methods implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray(){
        
        $methods = [
            [
                'label' => __('Nordea'),
                'value' => 'nordea'
            ],
            [
                'label' => __('Osuuspankki'),
                'value' => 'osuuspankki',
            ],
            [
                'label' => __('Saastopankki'),
                'value' => 'saastopankki',
            ],
            [
                'label' => __('Omasp'),
                'value' => 'omasp',
            ],
            [
                'label' => __('Poppankki'),
                'value' => 'poppankki',
            ],
            [
                'label' => __('Aktia'),
                'value' => 'aktia',
            ],
            [
                'label' => __('Sampo'),
                'value' => 'sampo',
            ],
            [
                'label' => __('Handelsbanken'),
                'value' => 'handelsbanken'
            ],
            [
                'label' => __('Spankki'),
                'value' => 'spankki'
            ],
            [
                'label' => __('Alandsbanken'),
                'value' => 'alandsbanken'
            ],
            [
                'label' => __('Neopay'),
                'value' => 'neopay'
            ],
            [
                'label' => __('Tilisiirto'),
                'value' => 'tilisiirto'
            ],
            [
                'label' => __('Collector'),
                'value' => 'collector'
            ],
            [
                'label' => __('Jousto Osamaksu'),
                'value' => 'joustoraha'
            ],
            [
                'label' => __('Jousto Lasku'),
                'value' => 'joustoraha2'
            ],
            [
                'label' => __('Everyday.fi'),
                'value' => 'everyday'
            ],
            [
                'label' => __('Visa'),
                'value' => 'solinor'
            ],
            [
                'label' => __('Visa Electron'),
                'value' => 'solinor2'
            ],
            [
                'label' => __('Master Card'),
                'value' => 'solinor3'
            ]
        ];

        return $methods;
    }
}
