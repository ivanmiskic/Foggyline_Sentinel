<?php
/**
 * Copyright © 2016 Foggyline. All rights reserved.
 */

namespace Foggyline\Sentinel\Model;

class CurlLogRepository implements \Foggyline\Sentinel\Api\CurlLogRepositoryInterface
{
    /**
     * @var \Foggyline\Sentinel\Model\ResourceModel\CurlLog
     */
    protected $resource;

    /**
     * @var \Foggyline\Sentinel\Model\CurlLogFactory
     */
    protected $logFactory;

    /**
     * @var \Foggyline\Sentinel\Model\ResourceModel\CurlLog\CollectionFactory
     */
    protected $logCollectionFactory;

    /**
     * @var \Foggyline\Sentinel\Api\Data\CurlLogSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var \Foggyline\Sentinel\Api\Data\CurlLogInterfaceFactory
     */
    protected $dataCurlLogFactory;

    /**
     * @var \Magento\Framework\Reflection\DataObjectProcessor
     */
    protected $dataObjectProcessor;

    public function __construct(
        \Foggyline\Sentinel\Model\ResourceModel\CurlLog $resource,
        \Foggyline\Sentinel\Model\CurlLogFactory $logFactory,
        \Foggyline\Sentinel\Api\Data\CurlLogInterfaceFactory $dataCurlLogFactory,
        \Foggyline\Sentinel\Model\ResourceModel\CurlLog\CollectionFactory $logCollectionFactory,
        \Foggyline\Sentinel\Api\Data\CurlLogSearchResultsInterfaceFactory $searchResultsFactory,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor
    ) {
        $this->resource = $resource;
        $this->logFactory = $logFactory;
        $this->logCollectionFactory = $logCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataCurlLogFactory = $dataCurlLogFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
    }

    /**
     * @param \Foggyline\Sentinel\Api\Data\CurlLogInterface $log
     * @return \Foggyline\Sentinel\Api\Data\CurlLogInterface
     * @throws CouldNotSaveException
     */
    public function save(\Foggyline\Sentinel\Api\Data\CurlLogInterface $log)
    {
        try {
            $this->resource->save($log);
        } catch (\Exception $exception) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(__($exception->getMessage()));
        }
        return $log;
    }

    /**
     * @param int $logId
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($logId)
    {
        $log = $this->logFactory->create();
        $this->resource->load($log, $logId);
        if (!$log->getId()) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(__('CurlLog with id "%1" does not exist.', $logId));
        }
        return $log;
    }

    /**
     * Retrieve log entities matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return \Foggyline\Sentinel\Api\Data\CurlLogSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $criteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        /* @var $collection \Foggyline\Sentinel\Model\ResourceModel\CurlLog\Collection */
        $collection = $this->logCollectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                if ($filter->getField() === 'store_id') {
                    $collection->addFieldToFilter('store', ['in' => $filter->getValue()]);
                    continue;
                }
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }
        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
        $logs = [];
        /** @var \Foggyline\Sentinel\Model\CurlLog $logModel */
        foreach ($collection as $logModel) {
            $logData = $this->dataCurlLogFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $logData,
                $logModel->getData(),
                '\Foggyline\Sentinel\Api\Data\CurlLogInterface'
            );
            $logs[] = $this->dataObjectProcessor->buildOutputDataArray(
                $logData,
                '\Foggyline\Sentinel\Api\Data\CurlLogInterface'
            );
        }
        $searchResults->setItems($logs);
        return $searchResults;
    }

    /**
     * @param \Foggyline\Sentinel\Api\Data\CurlLogInterface $log
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(\Foggyline\Sentinel\Api\Data\CurlLogInterface $log)
    {
        try {
            $this->resource->delete($log);
        } catch (\Exception $exception) {
            throw new \Magento\Framework\Exception\CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * @param int $logId
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteById($logId)
    {
        return $this->delete($this->getById($logId));
    }
}
