<?php
namespace Rokanthemes\CustomMenu\Model\Category;
 
class DataProvider extends \Magento\Catalog\Model\Category\DataProvider
{
	public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $category = $this->getCurrentCategory();
        if ($category) {
            $categoryData = $category->getData();
            $categoryData = $this->addUseConfigSettings($categoryData);
            $categoryData = $this->filterFields($categoryData);
            $categoryData = $this->convertValues($category, $categoryData);

            $this->loadedData[$category->getId()] = $categoryData;
        }
        return $this->loadedData;
    }
	private function convertValues($category, $categoryData)
    {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$media_url = $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
		$directory = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
		
        if (isset($categoryData['image'])) {
			unset($categoryData['image']);
			$fileName = $category->getData('image');
			if($exif = @getimagesize($media_url.'catalog/category/'.$fileName)){
				if(isset($exif['mime'])){
					$categoryData['image'][0]['name'] = $fileName;
					$categoryData['image'][0]['url'] = $category->getImageUrl('image');
					$categoryData['image'][0]['size'] = filesize($directory->getPath('media').'/catalog/category/'.$fileName);
					$categoryData['image'][0]['type'] = $exif['mime'];
				}
			}
		}		
		if (isset($categoryData['rt_menu_icon_img'])) {
            unset($categoryData['rt_menu_icon_img']);
			$fileName2 = $category->getData('rt_menu_icon_img');
			if($exif2 = @getimagesize($media_url.'catalog/category/'.$fileName2)){
				if(isset($exif2['mime'])){
					$helper = $objectManager->get('Rokanthemes\CustomMenu\Helper\Data');
					$categoryData['rt_menu_icon_img'][0]['name'] = $category->getData('rt_menu_icon_img');
					$categoryData['rt_menu_icon_img'][0]['url'] = $helper->getIconimageUrl($category);
					$categoryData['rt_menu_icon_img'][0]['size'] = filesize($directory->getPath('media').'/catalog/category/'.$fileName2);
					$categoryData['rt_menu_icon_img'][0]['type'] = $exif2['mime'];
				}
			}
    	}
		if (isset($categoryData['cat_image_thumbnail'])) {
            unset($categoryData['cat_image_thumbnail']);
			$fileName3 = $category->getData('cat_image_thumbnail');			
			if($exif3 = @getimagesize($media_url.'catalog/category/'.$fileName3)){
				if(isset($exif3['mime'])){
					$helper = $objectManager->get('Rokanthemes\Categorytab\Helper\Data');
					$categoryData['cat_image_thumbnail'][0]['name'] = $category->getData('cat_image_thumbnail');
					$categoryData['cat_image_thumbnail'][0]['url'] = $helper->getThumbnailImageUrl($category);
					$categoryData['cat_image_thumbnail'][0]['size'] = filesize($directory->getPath('media').'/catalog/category/'.$fileName3);
					$categoryData['cat_image_thumbnail'][0]['type'] = $exif3['mime'];
				}
			}
    	}
		
        return $categoryData;
    }
}