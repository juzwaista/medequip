<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RfqController extends Controller
{
    /**
     * Buyer submits an RFQ for a product (creates or reuses the shop conversation).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id'          => 'required|exists:products,id',
            'distributor_id'      => 'required|exists:distributors,id',
            'requested_quantity'  => 'required|integer|min:1',
            'target_price'        => 'nullable|numeric|min:0',
            'note'                => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        $conversation = Conversation::firstOrCreate([
            'customer_id'    => $user->id,
            'distributor_id' => $validated['distributor_id'],
        ]);

        $product = Product::findOrFail($validated['product_id']);

        ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'user_id'         => $user->id,
            'kind'            => 'rfq_request',
            'body'            => $validated['note'] ?? "I'd like to request a quote for {$product->name}.",
            'meta'            => [
                'product_id'         => $product->id,
                'product_name'       => $product->name,
                'product_image'      => $product->image_path,
                'requested_quantity' => (int) $validated['requested_quantity'],
                'target_price'       => $validated['target_price'] ? (float) $validated['target_price'] : null,
                'rfq_status'         => 'pending', // pending | quoted | accepted | declined
            ],
        ]);

        return redirect()->route('messages.show', $conversation)
            ->with('success', 'Your quote request has been sent to the seller.');
    }

    /**
     * Seller responds to an RFQ with a formal quote.
     */
    public function respond(Request $request, ConversationMessage $message): RedirectResponse
    {
        abort_unless($message->kind === 'rfq_request', 403);

        // Ensure the responding user is the seller (distributor staff/owner) for this conversation
        $conversation = $message->conversation;
        $user = $request->user();
        $isSellerOfShop = $user->role === 'distributor'
            ? $user->distributor?->id === $conversation->distributor_id
            : ($user->role === 'staff' && $user->distributor_id === $conversation->distributor_id);
        abort_unless($isSellerOfShop, 403);

        $validated = $request->validate([
            'quoted_price'      => 'required|numeric|min:0',
            'quoted_quantity'   => 'required|integer|min:1',
            'seller_note'       => 'nullable|string|max:1000',
            'valid_until'       => 'nullable|date|after:today',
        ]);

        // Mark the original request as quoted
        $message->update(['meta' => array_merge($message->meta, ['rfq_status' => 'quoted'])]);

        ConversationMessage::create([
            'conversation_id' => $message->conversation_id,
            'user_id'         => $request->user()->id,
            'kind'            => 'rfq_quote',
            'body'            => $validated['seller_note'] ?? 'Here is our quote for your request.',
            'meta'            => [
                'rfq_request_id'  => $message->id,
                'product_id'      => $message->meta['product_id'],
                'product_name'    => $message->meta['product_name'],
                'quoted_price'    => (float) $validated['quoted_price'],
                'quoted_quantity' => (int) $validated['quoted_quantity'],
                'valid_until'     => $validated['valid_until'] ?? null,
                'rfq_status'      => 'pending_buyer', // pending_buyer | accepted | declined
            ],
        ]);

        return back()->with('success', 'Quote sent to buyer.');
    }

    /**
     * Buyer accepts a quote — adds to cart at negotiated price.
     */
    public function accept(Request $request, ConversationMessage $message): RedirectResponse
    {
        abort_unless($message->kind === 'rfq_quote', 403);
        abort_unless($message->meta['rfq_status'] === 'pending_buyer', 409);

        // Ensure the accepting user is the buyer (the conversation customer)
        $conversation = $message->conversation;
        abort_unless($request->user()->id === $conversation->customer_id, 403);

        // Mark the quote as accepted
        $productId = $message->meta['product_id'];
        $quotedQty = $message->meta['quoted_quantity'];

        $product = Product::findOrFail($productId);
        $available = \App\Services\CartService::availableStockForLine($product, null);
        if ($available < $quotedQty) {
            return back()->with('error', 'Insufficient stock available to accept this quote. Someone may have purchased the inventory.');
        }

        $message->update(['meta' => array_merge($message->meta, ['rfq_status' => 'accepted'])]);

        // Add to cart using the standard line key format so CartService can parse it
        // We tag the rfq_price in the cart item so CartService can apply the negotiated price
        $cart = session('cart', []);
        $lineKey = \App\Services\CartService::lineKey($productId); // Standard p{id} key

        $cart[$lineKey] = [
            'product_id'           => $productId,
            'product_variation_id' => null,
            'quantity'             => $message->meta['quoted_quantity'],
            'rfq_price'            => $message->meta['quoted_price'],  // CartService will use this instead of product price
            'rfq_message_id'       => $message->id,
        ];

        session(['cart' => $cart]);

        return redirect()->route('cart.index')
            ->with('success', "Quote accepted! \"{$message->meta['product_name']}\" has been added to your cart at the negotiated price.");
    }
}
