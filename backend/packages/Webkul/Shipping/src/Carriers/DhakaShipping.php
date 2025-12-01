<?php

namespace Webkul\Shipping\Carriers;

use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\CartShippingRate;

class DhakaShipping extends AbstractShipping
{
    /**
     * Shipping method carrier code.
     *
     * @var string
     */
    protected $code = 'dhaka_shipping';

    /**
     * Calculate rate for Dhaka shipping.
     *
     * @return array|false
     */
    public function calculate()
    {
        if (! $this->isAvailable()) {
            return false;
        }

        return [
            $this->getInsideDhakaRate(),
            $this->getOutsideDhakaRate()
        ];
    }

    /**
     * Get Inside Dhaka shipping rate.
     *
     * @return \Webkul\Checkout\Models\CartShippingRate
     */
    public function getInsideDhakaRate(): \Webkul\Checkout\Models\CartShippingRate
    {
        $cart = Cart::getCart();
        $cartShippingRate = new CartShippingRate;

        $cartShippingRate->carrier = $this->getCode();
        $cartShippingRate->carrier_title = 'Dhaka Shipping';
        $cartShippingRate->method = 'inside_dhaka';
        $cartShippingRate->method_title = 'Inside Dhaka';
        $cartShippingRate->method_description = 'Delivery inside Dhaka city (1-2 days)';
        
        // Free shipping for orders over 3000
        if ($cart->sub_total >= 3000) {
            $cartShippingRate->price = 0;
            $cartShippingRate->base_price = 0;
        } else {
            $cartShippingRate->price = core()->convertPrice(70);
            $cartShippingRate->base_price = 70;
        }

        return $cartShippingRate;
    }

    /**
     * Get Outside Dhaka shipping rate.
     *
     * @return \Webkul\Checkout\Models\CartShippingRate
     */
    public function getOutsideDhakaRate(): \Webkul\Checkout\Models\CartShippingRate
    {
        $cart = Cart::getCart();
        $cartShippingRate = new CartShippingRate;

        $cartShippingRate->carrier = $this->getCode();
        $cartShippingRate->carrier_title = 'Dhaka Shipping';
        $cartShippingRate->method = 'outside_dhaka';
        $cartShippingRate->method_title = 'Outside Dhaka';
        $cartShippingRate->method_description = 'Delivery outside Dhaka (3-5 days)';
        
        // Free shipping for orders over 3000
        if ($cart->sub_total >= 3000) {
            $cartShippingRate->price = 0;
            $cartShippingRate->base_price = 0;
        } else {
            $cartShippingRate->price = core()->convertPrice(130);
            $cartShippingRate->base_price = 130;
        }

        return $cartShippingRate;
    }
}
