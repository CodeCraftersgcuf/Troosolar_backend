<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CartLinkEmail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    /** @var \Illuminate\Support\Collection|array<int, mixed> */
    public $cartItems;

    public string $cartLink;

    public string $orderType;

    public ?string $customMessage;

    public float $summaryTotal;

    /** @var array<int, array{name: string, description?: string, price: float, quantity: int}> */
    public array $customItemsForDisplay;

    public string $headline;

    public string $ctaLabel;

    /**
     * @param  \Illuminate\Support\Collection|array<int, mixed>  $cartItems
     * @param  array<int, array<string, mixed>>  $customItemsForDisplay
     */
    public function __construct(
        User $user,
        $cartItems,
        string $cartLink,
        string $orderType,
        ?string $customMessage = null,
        ?float $summaryTotal = null,
        array $customItemsForDisplay = []
    ) {
        $this->user = $user;
        $this->cartItems = $cartItems;
        $this->cartLink = $cartLink;
        $this->orderType = $orderType;
        $this->customMessage = $customMessage;
        $this->customItemsForDisplay = $customItemsForDisplay;

        $itemsCollection = is_array($cartItems) ? collect($cartItems) : $cartItems;
        $fromCart = (float) $itemsCollection->sum(fn ($row) => (float) ($row->subtotal ?? 0));
        $fromCustom = collect($customItemsForDisplay)->reduce(function ($carry, $row) {
            $price = (float) ($row['price'] ?? 0);
            $qty = max(1, (int) ($row['quantity'] ?? 1));

            return $carry + ($price * $qty);
        }, 0.0);

        $this->summaryTotal = $summaryTotal !== null
            ? (float) $summaryTotal
            : round($fromCart + $fromCustom, 2);

        $isBnpl = $orderType === 'bnpl';
        $this->headline = $isBnpl
            ? 'Your BNPL custom order is ready'
            : 'Your custom order is ready';
        $this->ctaLabel = $isBnpl
            ? 'Continue BNPL application'
            : 'View cart & checkout';
    }

    public function build()
    {
        $isBnpl = $this->orderType === 'bnpl';
        $subject = $isBnpl
            ? 'Your BNPL custom order — next steps (Troosolar)'
            : 'Your custom order is ready — complete checkout (Troosolar)';

        $mailable = $this->subject($subject)
            ->view('emails.cart_link')
            ->with([
                'user' => $this->user,
                'cartItems' => $this->cartItems,
                'cartLink' => $this->cartLink,
                'orderType' => $this->orderType,
                'customMessage' => $this->customMessage,
                'summaryTotal' => $this->summaryTotal,
                'customItemsForDisplay' => $this->customItemsForDisplay,
                'headline' => $this->headline,
                'ctaLabel' => $this->ctaLabel,
            ]);

        $fromAddress = config('mail.from.address');
        if (!empty($fromAddress)) {
            $mailable->replyTo($fromAddress, config('mail.from.name'));
        }

        return $mailable;
    }
}
