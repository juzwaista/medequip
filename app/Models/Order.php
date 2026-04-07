<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'customer_id',
        'distributor_id',
        'status',
        'prescription_status',
        'prescription_image_path',
        'prescription_review_note',
        'prescription_reviewed_at',
        'subtotal',
        'shipping_fee',
        'discount',
        'total_amount',
        'delivery_address',
        'contact_number',
        'notes',
        'payment_method',
        'approved_at',
        'cancelled_at',
        'delivered_at',
        'received_at',
        'delivery_latitude',
        'delivery_longitude',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'delivered_at' => 'datetime',
        'received_at' => 'datetime',
        'prescription_reviewed_at' => 'datetime',
        'delivery_latitude' => 'decimal:8',
        'delivery_longitude' => 'decimal:8',
    ];

    public const PRESCRIPTION_NOT_REQUIRED = 'not_required';

    public const PRESCRIPTION_AWAITING_UPLOAD = 'awaiting_upload';

    public const PRESCRIPTION_PENDING_REVIEW = 'pending_review';

    public const PRESCRIPTION_APPROVED = 'approved';

    public const PRESCRIPTION_REJECTED = 'rejected';

    public function needsPrescriptionUpload(): bool
    {
        return $this->prescription_status === self::PRESCRIPTION_AWAITING_UPLOAD;
    }

    public function isPrescriptionPendingReview(): bool
    {
        return $this->prescription_status === self::PRESCRIPTION_PENDING_REVIEW;
    }

    public function prescriptionBlocksFulfillment(): bool
    {
        return in_array($this->prescription_status, [
            self::PRESCRIPTION_AWAITING_UPLOAD,
            self::PRESCRIPTION_PENDING_REVIEW,
        ], true);
    }

    /**
     * Get the customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the distributor
     */
    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    /**
     * Get order items
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getShopConversationForOrder(): ?Conversation
    {
        return Conversation::query()
            ->where('customer_id', $this->customer_id)
            ->where('distributor_id', $this->distributor_id)
            ->first();
    }

    public function getOrCreateShopConversation(): Conversation
    {
        return Conversation::firstOrCreate(
            [
                'customer_id' => $this->customer_id,
                'distributor_id' => $this->distributor_id,
            ],
            []
        );
    }

    /**
     * All messages in the buyer–seller thread (same as /messages for this shop).
     */
    public function chatMessages(): Builder
    {
        $c = $this->getShopConversationForOrder();
        if (! $c) {
            return ConversationMessage::query()->whereRaw('0 = 1');
        }

        return ConversationMessage::query()->where('conversation_id', $c->id)->orderBy('id');
    }

    /**
     * Get invoice
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * Get delivery
     */
    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    public function productReviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function deliveryReview(): HasOne
    {
        return $this->hasOne(DeliveryReview::class);
    }

    /**
     * Check if order can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'approved']);
    }

    /**
     * Check if buyer can confirm receipt
     */
    public function canBeConfirmedReceived(): bool
    {
        return $this->status === 'delivered' && is_null($this->received_at);
    }

    /**
     * Is this a Cash on Delivery order?
     */
    public function isCod(): bool
    {
        return $this->payment_method === 'cod';
    }

    /**
     * COD orders have no online invoice/escrow.
     */
    public function hasOnlinePayment(): bool
    {
        return ! $this->isCod() && $this->payment_method !== 'cash';
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-'.date('Ymd').'-'.str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (self::where('order_number', $number)->exists());

        return $number;
    }
}
