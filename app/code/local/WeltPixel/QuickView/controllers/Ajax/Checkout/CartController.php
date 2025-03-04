<?php

require_once Mage::getModuleDir('controllers', 'Mage_Checkout').DS.'CartController.php';

class WeltPixel_QuickView_Ajax_Checkout_CartController extends Mage_Checkout_CartController
{
    public function addAction()
    {

        if (!$this->_validateFormKey()) {
            return;
        }
        $cart   = $this->_getCart();
        $params = $this->getRequest()->getParams();
        try {
            if (isset($params['qty'])) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            $product = $this->_initProduct();
            $related = $this->getRequest()->getParam('related_product');

            if (!$product) {
                return;
            }
            if(isset($params['renttime'])){
          //    $product->setData('fromdate',$params['fromdate']);
          //    $product->setData('todate',$params['todate']);
          //  $diff=date_create($params['fromdate'])->diff(date_create($params['todate']))->days;
        //    Mage::log($product->debug(),Zend_log::INFO,'layout.log',true);
            //  Mage::log($product->getData('mintime'),Zend_log::INFO,'layout.log',true);

              if($params['renttime'] < $product->getData('mintime')){
                  $product->setData('renttime',$product->getData('mintime'));
              }else{
                  $product->setData('renttime',$params['renttime']);
              }
              $params['renttime']=  $product->getData('renttime');

                $product->save();
            }

            $cart->addProduct($product, $params);
            if (!empty($related)) {
                $cart->addProductsByIds(explode(',', $related));
            }
            Mage::log("in cart controller",Zend_log::INFO,'layout.log',true);

        //    Mage::log($quote->debug(),Zend_log::INFO,'layout.log',true);
            $cart->save();
          //
            Mage::log("in cart saved",Zend_log::INFO,'layout.log',true);
          //  Mage::log($cart->debug(),Zend_log::INFO,'layout.log',true);
            $this->_getSession()->setCartWasUpdated(true);

            Mage::dispatchEvent('checkout_cart_add_product_complete',
                array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
            );

            if (!$cart->getQuote()->getHasError()) {
                $message = $this->__(
                    '<span><strong>%s</strong> was added to your shopping cart.</span><br /><p><a class="simple-button" href="%s">Continue Shopping</a><span> or </span><a class="button" href="%s">Checkout</a></p>',
                    Mage::helper('core')->escapeHtml($product->getName()),
                    'javascript:weltpixel.lightbox.close()',
                    Mage::helper('checkout/url')->getCheckoutUrl()
                );
            }
        } catch (Mage_Core_Exception $e) {
            $message = Mage::helper('core')->escapeHtml($e->getMessage());
        } catch (Exception $e) {
            $message = $this->__('Cannot add the item to shopping cart.');
            Mage::logException($e);
        }

        $this->loadLayout();
        $body = array(
            'message' => $message,
            'blocks' => array()
        );
        if ($this->getLayout()->getBlock('cart_sidebar')) {
            $body['blocks']['cart_sidebar'] = array(
                'class' => Mage::helper('weltpixel_quickview')->isMageEnterprise() ? 'top-cart' : 'block-cart',
                'content' => preg_replace('/\/uenc\/[^\/]*/', '', $this->getLayout()->getBlock('cart_sidebar')->toHtml()),
            );
        }
        if ($this->getLayout()->getBlock('quick_access')) {
            $body['blocks']['quick_access'] = array(
                'id' => 'quick-access',
                'content' => preg_replace('/\/uenc\/[^\/]*/', '', $this->getLayout()->getBlock('quick_access')->toHtml()),
            );
        }

        $this->getResponse()
            ->setHeader('Content-Type', 'application/json', true)
            ->setBody(Mage::helper('core')->jsonEncode($body));
    }
}
