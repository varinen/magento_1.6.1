<?php

/**
 * @package 	Kiala_LocateAndSelect
 * @copyright   Copyright (c) 2012 Kiala
 * @author 		Phpro (http://www.phpro.be)
 */
class Kiala_LocateAndSelect_Adminhtml_Kiala_IndexController extends Mage_Adminhtml_Controller_Action
{

    public function _initAction()
    {
        $this->loadLayout()
                ->_setActiveMenu('kiala/children')
                ->_addContent($this->getLayout()->createBlock('locateandselect/adminhtml_orders'));
        return $this;
    }

    public function indexAction()
    {
        $this->_initAction()
                ->renderLayout();
    }

    /**
     * Retrieves file
     */
    private function _exportFile($filename, $filepattern)
    {
        Mage::log('Export to file: ' . $filename, Zend_Log::INFO, 'kiala.log');

        /** @var Kiala_LocateAndSelect_Model_Orders $model */
        $orderIds = $this->getRequest()->getParam('order_ids');
        $model = Mage::getModel('locateandselect/orders');

        Mage::log($orderIds, Zend_Log::INFO, 'kiala.log');
        $ups = ($filepattern == '' ? true : false);
        $result = $model->toCsv($filename, $ups, $orderIds);

        // Show errors, if present
        if (!empty($result['error'])) {
            $errors = '';
            foreach ($result['error'] as $orderId) {
                $errors .= $orderId . ', ';
            }
            $errors = substr($errors, 0, -2);
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('locateandselect')->__('Could not export the following orders:') . ' ' . $errors . ' ' . Mage::helper('locateandselect')->__('Please check the log for more details.'));
        }

        if ($result['csv']) {
            // Offer as download via downloadCsvAction
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('locateandselect')->__('Successfully exported order(s). Download the file here:') .' <a href="' . Mage::helper('adminhtml')->getUrl
            ('*/*/downloadCsv',array('file_id'=>$filepattern)) . '">' . $filename . '</a>');
            
            Mage::getSingleton('adminhtml/session')->setKialaDownloadFile($filename);
            Mage::getSingleton('adminhtml/session')->setKialaDownloadFilePattern($filepattern);
            
            $this->_redirect('*/*/index');
        } else {
            Mage::getSingleton('adminhtml/session')->addNotice(Mage::helper('locateandselect')->__('Nothing to export.'));
            $this->_redirect('*/*/index');
        }
    }

    /**
     * Exports unexported or selected order in a csv.
     * Export type: Pack&Ship Desktop
     * File: orders-[Ymd-His].csv
     */
    public function exportCsvAction()
    {
        $filepattern = date('Ymd-His');
        $filename = 'orders-' . $filepattern . '.csv';

        $this->_exportFile($filename, $filepattern);
    }

    /**
     * Exports unprocessed or selected orders to UPS csv file
     * Export type: UPS Worldship CSV Export
     * File: exportworldship.csv
     */
    public function exportUpsCsvAction()
    {
        $this->_exportFile('exportworldship.csv', '');
    }

    /**
     * Creates tracking codes for the selected orders from the Kiala webservice.
     */
    public function trackingCodeAction()
    {
        $orderIds = $this->getRequest()->getParam('order_ids');
        $result = Mage::getModel('locateandselect/orders')->createTrackingCode($orderIds);

        if ($result == 'success') {
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('locateandselect')->__('Tracking code(s) successfully created!'));
        } else if (is_array($result)) {
            $orderIds = '';
            foreach ($result as $orderId => $value)
            {
                if (!$value) {
                    $orderIds .= $orderId . ' ';
                }
            }
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('locateandselect')->__('The tracking codes for the following orders were not created: %s. These might have been requested already. Please check the log for details.',$orderIds));
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('locateandselect')->__('Could not create tracking code(s), might have been requested already. Please check the log for details.'));
        }

        $this->_initAction()
                ->renderLayout();
    }

    /**
     * Grid action for AJAX request
     */
    public function gridAction()
    {
        $this->getResponse()->setBody(
                $this->getLayout()->createBlock('locateandselect/adminhtml_orders_grid')->toHtml()
        );
    }

    /**
     * Download generated file
     */
    public function downloadCsvAction()
    {
        $filepattern = $this->getRequest()->getParam('file_id');
        
        if($filepattern == Mage::getSingleton('adminhtml/session')->getKialaDownloadFilePattern()) {
            $filename = Mage::getSingleton('adminhtml/session')->getKialaDownloadFile();
            $path = Mage::getBaseDir('var').DS.'export'.DS.'kiala'.DS;
            
            $this->_prepareDownloadResponse($filename, file_get_contents($path.$filename), 'text/csv');
        } else {
            Mage::getSingleton('adminhtml/session')->addError('Export file expired');
            
            $this->_redirect('*/*/index');
        }
    }

    /**
     * Download last generated csv file
     */
    public function downloadLatestCsvAction() 
    {
        $this->downloadLatestFile(false);
    }

    /**
     * Download last generated UPS csv file
     */
    public function downloadLatestUpsCsvAction()
    {
        $this->downloadLatestFile(true);
    }

    /**
     * Download last generated file for
     *
     * @param bool $ups
     */
    private function downloadLatestFile($ups)
    {
        $io = new Varien_Io_File();
        $path = Mage::getBaseDir('var').DS.'export'.DS.'kiala'.DS;

        if(is_dir($path)){
            $io->cd($path);

            $files = array();

            foreach($io->ls('files_only') as $file) {
                if ($ups) {
                    if ($file['text'] == 'exportworldship.csv') {
                        $files[] = $file;
                        break;
                    }
                }
                elseif ($file['text'] != 'exportworldship.csv') {
                    $files[] = $file;
                }
            }

            if(count($files)) {
                //Sort returned array
                function compareFilesArray($a,$b) {
                    return strcmp($a['text'], $b['text']);
                }
                usort($files, 'compareFilesArray');

                //Go to end
                $file = end($files);

                //Return response (file download link)
                $this->_prepareDownloadResponse($file['text'], file_get_contents($path.$file['text']), 'text/csv');
            } else {
                Mage::getSingleton('adminhtml/session')->addError('Directory is empty');
                $this->_redirect('*/*/index');
            }
        } else {
            Mage::getSingleton('adminhtml/session')->addError('Directory is empty');
            $this->_redirect('*/*/index');
        }
    }
}
