<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Customer country with website specified attribute source
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Customer\Model\ResourceModel\Address\Attribute\Source;

use Magento\Customer\Model\Config\Share;
use Magento\Directory\Model\AllowedCountries;
use Magento\Store\Model\ScopeInterface;

class CountryWithWebsites extends \Magento\Eav\Model\Entity\Attribute\Source\Table
{
    /**
     * @var \Magento\Directory\Model\ResourceModel\Country\CollectionFactory
     */
    private $countriesFactory;

    /**
     * @var \Magento\Directory\Model\AllowedCountries
     */
    private $allowedCountriesReader;

    /**
     * @var array
     */
    private $options;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Share
     */
    private $shareConfig;

    /**
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory $attrOptionFactory
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countriesFactory
     * @param AllowedCountries $allowedCountries
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param Share $shareConfig
     */
    public function __construct(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory $attrOptionFactory,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countriesFactory,
        \Magento\Directory\Model\AllowedCountries $allowedCountries,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Config\Share $shareConfig
    ) {
        $this->countriesFactory = $countriesFactory;
        $this->allowedCountriesReader = $allowedCountries;
        $this->storeManager = $storeManager;
        $this->shareConfig = $shareConfig;
        parent::__construct($attrOptionCollectionFactory, $attrOptionFactory);
    }

    /**
     * Retrieve all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        if (!$this->options) {
            $allowedCountries = [];
            $websiteIds = [];

            if (!$this->shareConfig->isGlobalScope()) {
                foreach ($this->storeManager->getWebsites() as $website) {
                    $countries = $this->allowedCountriesReader
                        ->getAllowedCountries(ScopeInterface::SCOPE_WEBSITE, $website->getId());
                    $allowedCountries = array_merge($allowedCountries, $countries);

                    foreach ($countries as $countryCode) {
                        $websiteIds[$countryCode][] = $website->getId();
                    }
                }
            } else {
                $allowedCountries = $this->allowedCountriesReader->getAllowedCountries();
            }

            $this->options = $this->createCountriesCollection()
                ->addFieldToFilter('country_id', ['in' => $allowedCountries])
                ->toOptionArray();

            foreach ($this->options as &$option) {
                if (isset($websiteIds[$option['value']])) {
                    $option['website_ids'] = $websiteIds[$option['value']];
                }
            }
        }

        return $this->options;
    }

    /**
     * Create Countries Collection with all countries
     *
     * @return \Magento\Directory\Model\ResourceModel\Country\Collection
     */
    private function createCountriesCollection()
    {
        return $this->countriesFactory->create();
    }
}
