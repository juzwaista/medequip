/**
 * Buyer-facing copy for order workflow (list + detail).
 */
export function customerOrderStatusMessage(status) {
    if (!status) {
        return '';
    }
    const key = String(status).toLowerCase();
    const messages = {
        pending: 'Waiting for the seller to confirm your order.',
        approved: 'The seller accepted your order and is preparing it for shipment.',
        packed: 'Packed and ready to ship.',
        shipped: 'Order has been picked up. Out for delivery.',
        delivered: 'Delivered. Confirm receipt when you have your items.',
        cancelled: 'This order was cancelled.',
        rejected: 'The seller could not fulfill this order.',
    };
    return messages[key] || '';
}
