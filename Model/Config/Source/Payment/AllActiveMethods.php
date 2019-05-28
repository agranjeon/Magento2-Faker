<?php

declare(strict_types=1);

namespace Agranjeon\Faker\Model\Config\Source\Payment;

/**
 * @author Alexandre Granjeon <alexandre.granjeon@gmail.com>
 */
class AllActiveMethods implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $_paymentData;
    /**
     * @var \Magento\Payment\Model\Config $_paymentConfig
     */
    protected $_paymentConfig;

    /**
     * AllActiveMethods constructor
     *
     * @param \Magento\Payment\Helper\Data  $paymentData
     * @param \Magento\Payment\Model\Config $paymentConfig
     */
    public function __construct(\Magento\Payment\Helper\Data $paymentData, \Magento\Payment\Model\Config $paymentConfig)
    {
        $this->_paymentData   = $paymentData;
        $this->_paymentConfig = $paymentConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $methods = [];
        $groupRelations = [];

        foreach ($this->_paymentData->getPaymentMethods() as $code => $paymentMethod) {
            if (!isset($paymentMethod['active']) || $paymentMethod['active'] != 1) {
                continue;
            }
            if (isset($paymentMethod['title'])) {
                $methods[$code] = $paymentMethod['title'];
            } else {
                $methods[$code] = $this->_paymentData->getMethodInstance($code)->getConfigData('title');
            }
            if (isset($paymentMethod['group'])) {
                $groupRelations[$code] = $paymentMethod['group'];
            }
        }
        $groups = $this->_paymentConfig->getGroups();
        foreach ($groups as $code => $title) {
            $methods[$code] = $title;
        }
        asort($methods);
        $labelValues = [];
        foreach ($methods as $code => $title) {
            $labelValues[$code] = [];
        }
        foreach ($methods as $code => $title) {
            if (isset($groups[$code])) {
                $labelValues[$code]['label'] = $title;
                if (!isset($labelValues[$code]['value'])) {
                    $labelValues[$code]['value'] = null;
                }
            } elseif (isset($groupRelations[$code])) {
                unset($labelValues[$code]);
                $labelValues[$groupRelations[$code]]['value'][$code] = ['value' => $code, 'label' => $title];
            } else {
                $labelValues[$code] = ['value' => $code, 'label' => $title];
            }
        }

        return $labelValues;
    }
}
