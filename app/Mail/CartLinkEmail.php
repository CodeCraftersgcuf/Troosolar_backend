<?php

namespace App\Mail;

use App\Models\User;
use App\Models\CartItem;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CartLinkEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $cartItems;
    public $cartLink;
    public $orderType;
    public $customMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, $cartItems, $cartLink, $orderType, $customMessage = null)
    {
        $this->user = $user;
        $this->cartItems = $cartItems;
        $this->cartLink = $cartLink;
        $this->orderType = $orderType;
        $this->customMessage = $customMessage;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = $this->orderType === 'bnpl' 
            ? 'Your Buy Now Pay Later Cart - TrooSolar' 
            : 'Your Cart Items - TrooSolar';

        return $this->subject($subject)
                    ->view('emails.cart_link')
                    ->with([
                        'user' => $this->user,
                        'cartItems' => $this->cartItems,
                        'cartLink' => $this->cartLink,
                        'orderType' => $this->orderType,
                        'customMessage' => $this->customMessage,
                    ]);
    }
}

