<?php

namespace TinoPay\TinoPay\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $transaction_id
 * @property string $gateway
 * @property string $transactionDate
 * @property string $accountNumber
 * @property string|null $virtualAccount
 * @property string|null $code
 * @property string $content
 * @property string $transferType
 * @property int $transferAmount
 * @property string|null $referenceCode
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class TinoPayTransaction extends Model
{
    protected $table = 'tinopay_transactions';

    protected $guarded = ['id'];

    protected $casts = [
        'transferAmount' => 'integer',
    ];
}
