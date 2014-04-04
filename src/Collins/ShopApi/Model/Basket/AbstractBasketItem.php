<?php
/**
 * @auther nils.droege@antevorte.org
 * (c) Antevorte GmbH & Co KG
 */

namespace Collins\ShopApi\Model\Basket;

use Collins\ShopApi\Model\ResultError;

class AbstractBasketItem extends ResultError
{
    /**
     * Additional data are transmitted to the merchant untouched.
     * If set (array not empty), a key "description" must exist. This description
     * must be a string that describes the variant. If you want to pass a different image URL,
     * you can add a key "image_url" to the $additionalData that contains the URL to the image.
     *
     * @var array $additionalData additional data for this variant
     */
    protected $additionalData;

    /** @var boolean */
    protected $isChanged = false;

    public function isChanged()
    {
        return $this->isChanged;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return isset($this->additionalData) ?
            $this->additionalData['description'] :
            null
        ;
    }

    /**
     * Additional data are transmitted to the merchant untouched.
     * If set (array not empty), a key "description" must exist. This description
     * must be a string that describes the variant. If you want to pass an image URL that
     * represents this item set,
     * you can add a key "image_url" to the $additionalData that contains the URL to the image.
     *
     * @return array|null additional data
     */
    public function getAdditionalData()
    {
        return isset($this->additionalData) ?
            $this->additionalData :
            null
        ;
    }

    /**
     * Additional data are transmitted to the merchant untouched.
     * If set (array not empty), a key "description" must exist. This description
     * must be a string that describes the variant. If you want to pass a different image URL,
     * you can add a key "image_url" to the $additionalData that contains the URL to the image.
     *
     * @param array $additionalData additional data for this variant
     *
     * @throws \InvalidArgumentException
     */
    public function setAdditionData(array $additionalData)
    {
        $this->checkAdditionData($additionalData);
        $this->isChanged = true;

        $this->additionalData = $additionalData;
    }

    protected function checkAdditionData(array $additionalData = null, $imageUrlRequired = false)
    {
        if ($additionalData) {
            if (!isset($additionalData['description'])) {
                throw new \InvalidArgumentException('description is required in additional data');
            }
            if ($imageUrlRequired && !isset($additionalData['image_url'])) {
                throw new \InvalidArgumentException('image_url is required in additional data');
            }
        }

        if (isset($additionalData['internal_infos']) && !is_array($additionalData['internal_infos'])) {
            throw new \InvalidArgumentException('internal_infos must be an array');
        }
    }
}