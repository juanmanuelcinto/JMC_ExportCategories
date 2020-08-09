<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace JMC\Export\Console\Command\Categories;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\File\Csv;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Export Categories in a CSV file with the next format:
 * id, url_key
 * Created file location: var (Make sure you have the right permissions).
 * 
 * @author Juan Manuel Cinto <https://github.com/juanmanuelcinto>
 */
class Export extends Command
{

    /**
     * @var Csv
     */
    private $csv;

    /**
     * @var Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    private $collection;

    /**
     * @var CategoryUrlPathGenerator
     */
    private $categoryUrlPathGenerator;
    
    /**
     * @var string
     */
    private $storeId = null;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param CollectionFactory $collectionFactory
     * @param Csv $csv
     * @param CategoryUrlPathGenerator $categoryUrlPathGenerator
     * @param StoreManagerInterface $storeManager
     * @param string $name
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        Csv $csv,
        CategoryUrlPathGenerator $categoryUrlPathGenerator,
        StoreManagerInterface $storeManager,
        string $name = null
    ) {
        parent::__construct($name);
        $this->collection = $collectionFactory;
        $this->categoryUrlPathGenerator = $categoryUrlPathGenerator;
        $this->csv = $csv;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("jmc:export:categories");
        $this->setDescription("Export categories in CSV file");
        $this->addArgument('store', InputArgument::REQUIRED, __('Type a store ID'));
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $storeId = $input->getArgument('store');

        if ($storeId) {
            $this->storeId = $storeId;
        }

        $output->writeln("Exporting Categories... ");

        try {
            $this->exportCsvFile();
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
    }
    
    /**
     * Export CSV File
     */
    private function exportCsvFile()
    {
        $exportData = [];

        $categories = $this->collection->create()                              
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('is_active','1');
            
        if ($this->storeId) {
            $storeId = (int) $this->storeId;
        	$categories->setStore($this->storeManager->getStore($storeId));
        }

        if(count($categories) > 0){
            foreach ($categories as $category) {
                $exportData[] = [
                    $category->getId(),
                    $this->getUrlPathWithSuffix($category)
                ];
            }
            $this->csv->saveData("var/categories.csv" , $exportData);
        }
    }
    
    /**
     * @param \Magento\Catalog\Model\Category $category
     * @return string
     */
    private function getUrlPathWithSuffix($category)
    {
        return $this->categoryUrlPathGenerator->getUrlPathWithSuffix($category);
    }
}
