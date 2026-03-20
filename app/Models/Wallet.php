<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Exception;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'status',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Credit the wallet.
     */
    public function credit(float $amount, string $type, ?string $referenceId = null, ?string $description = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new Exception("Credit amount must be greater than zero.");
        }

        return DB::transaction(function () use ($amount, $type, $referenceId, $description) {
            // Lock the row for update
            $wallet = static::where('id', $this->id)->lockForUpdate()->first();
            
            $wallet->balance += $amount;
            $wallet->save();

            return $wallet->transactions()->create([
                'type'         => $type,
                'amount'       => $amount,
                'reference_id' => $referenceId,
                'description'  => $description,
            ]);
        });
    }

    /**
     * Debit the wallet.
     */
    public function debit(float $amount, string $type, ?string $referenceId = null, ?string $description = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new Exception("Debit amount must be greater than zero.");
        }

        return DB::transaction(function () use ($amount, $type, $referenceId, $description) {
            // Lock the row for update
            $wallet = static::where('id', $this->id)->lockForUpdate()->first();

            if ($wallet->balance < $amount) {
                throw new Exception("Insufficient wallet balance.");
            }

            $wallet->balance -= $amount;
            $wallet->save();

            return $wallet->transactions()->create([
                'type'         => $type,
                'amount'       => -$amount,
                'reference_id' => $referenceId,
                'description'  => $description,
            ]);
        });
    }
}
