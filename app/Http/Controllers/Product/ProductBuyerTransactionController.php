<?php

namespace App\Http\Controllers\Product;

use App\User;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ApiController;
use App\Transaction;
use App\Transformers\TransactionTransformer;

class ProductBuyerTransactionController extends ApiController
{
    public function __construct() {
        parent::__construct();

        $this->middleware('transform.input:' . TransactionTransformer::class)->only('store');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Product $product, User $buyer)
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        if ($buyer->id == $product->seller_id) {
            return $this->errorResponse('The buyer must be different from the seller', 409);
        }

        if (!$buyer->hasVerifiedEmail()) {
            return $this->errorResponse('Buyer must be verified user', 409);
        }

        if (!$product->seller->hasVerifiedEmail()) {
            return $this->errorResponse('Seller must be verified user', 409);
        }

        if (!$product->isAvailable()) {
            return $this->errorResponse('Product is not available', 409);
        }

        if ($product->quantity < $request->quantity) {
            return $this->errorResponse('Product does not have enough units for this transaction', 409);
        }

        return DB::transaction(function () use ($data, $product, $buyer) {
            $product->quantity -= $data['quantity'];
            $product->save();

            $transaction = Transaction::create([
                'quantity' => $data['quantity'],
                'buyer_id' => $buyer->id,
                'product_id' => $product->id,
            ]);

            return $this->showOne($transaction, 201);
        });
    }
}
