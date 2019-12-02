<?php

namespace Technooze\Timage\Helper;

use Magento\Framework\Filesystem;
use Magento\Framework\Image\AdapterFactory;
use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;

class Resizer extends \Magento\Framework\App\Helper\AbstractHelper
{
    /* @var Filesystem */
    private $_filesystem;

    /* @var StoreManagerInterface */
    private $storeManager;

    /* @var AdapterFactory */
    private $imageFactory;

    /* @var \Magento\Framework\Filesystem\Directory\WriteInterface */
    private $_directory;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        AdapterFactory $imageFactory,
        Filesystem $filesystem
    )
    {
        $this->storeManager = $storeManager;
        $this->imageFactory = $imageFactory;
        $this->_filesystem = $filesystem;
        $this->_directory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        parent::__construct($context);
    }

    /**
     * @param $img string (relative to media folder /catalog/product/g/c/giftcard.jpg)
     * @param $width int
     * @param $height int
     * @return string
     */
    public function resizeImage($img, $width = 780, $height = 505)
    {
        $mediaDir = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $img = $mediaDir->getAbsolutePath(null) . $img;

        try {
            if (!$this->_directory->isFile($img) || !$this->_directory->isExist($img)) {
                return false;
            }
            /* Target directory path where our resized image will be save */
            $targetDir = $mediaDir->getAbsolutePath('technooze/cache/' . $width . 'x' . $height);
            $pathTargetDir = $this->_directory->getRelativePath($targetDir);
            /* If Directory not available, create it */
            if (!$this->_directory->isExist($pathTargetDir)) {
                $this->_directory->create($pathTargetDir);
            }
            if (!$this->_directory->isExist($pathTargetDir)) {
                return false;
            }

            $image = $this->imageFactory->create();
            $image->open($img);
            $image->keepAspectRatio(true);
            $image->resize($width, $height);
            $imageName = pathinfo($img, PATHINFO_BASENAME);
            $dest = $targetDir . '/' . $imageName;
            $image->save($dest);
            if ($this->_directory->isFile($this->_directory->getRelativePath($dest))) {
                return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'technooze/cache/' . $width . 'x' . $height . '/' . $imageName;
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
        return false;
    }
}