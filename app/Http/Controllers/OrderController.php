<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    // Store a new order
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'table_number' => 'nullable|integer|exists:tables,table_number', // Nullable for remote orders
            'order_items' => 'required|array',
            'order_items.*.menu_item_id' => 'required|exists:menu_items,id',
            'order_items.*.quantity' => 'required|integer|min:1',
            'customer_ip' => 'required|ip',
            'customer_temp_id' => 'required|string|max:255',
            'order_type' => 'required|in:dine-in,remote', // Specify order type
        ]);

        $table = null;
        if ($validatedData['order_type'] === 'dine-in') {
            $table = Table::where('table_number', $validatedData['table_number'])->firstOrFail();
        }
        $tx_ref = 'CHAPA-' . Str::uuid();

        $order = Order::create([
            'table_id' => $table ? $table->id : null,
            'order_date_time' => now(),
            'order_status' => 'pending',
            'total_price' => 0,
            'customer_ip' => $validatedData['customer_ip'],
            'customer_temp_id' => $validatedData['customer_temp_id'],
            'order_type' => $validatedData['order_type'],
            'payment_status' => 'pending', // Payment is handled separately
            'tx_ref' => $tx_ref, 
        ]);

        $totalAmount = 0;
        foreach ($validatedData['order_items'] as $item) {
            $menuItem = MenuItem::findOrFail($item['menu_item_id']);
            $orderItem = new OrderItem([
                'menu_item_id' => $item['menu_item_id'],
                'quantity' => $item['quantity'],
                'total_price' => $menuItem->price* $item['quantity'],
            ]);
            $order->orderItems()->save($orderItem);
            $totalAmount += $menuItem->price * $item['quantity'];
        }


        $order->update(['total_price' => $totalAmount]);

        if ($table) {
        $table->update(['table_status' => 'occupied']);
        }

        

        Payment::create([
            'tx_ref' => $tx_ref,
            'amount' => $totalAmount,
            'currency' => 'ETB',
            'status' => 'pending',
            'email' => $request->email ?? 'guest@example.com',
            'first_name' => $request->first_name ?? 'Guest',
            'last_name' => $request->last_name ?? 'User',
            'phone_number' => $request->phone_number ?? '0000000000',
        ]);

// Add tx_ref to the response to be used in payment initialization
        return response()->json([
            'message' => 'Order placed successfully. Proceed to payment.',
            'order' => $order->load('orderItems.menuItem'),
            'tx_ref' => $tx_ref
        ], 201);



    }

    // Notify arrival for remote orders
    public function notifyArrival(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if ($order->order_type !== 'remote') {
            return response()->json(['error' => 'This order is not a remote order'], 400);
        }

        if ($order->order_status !== 'pending') {
            return response()->json(['error' => 'Cannot notify arrival for a non-pending order'], 400);
        }

        $order->update(['notified_arrival' => now()]);

        return response()->json(['message' => 'Arrival notified successfully', 'order' => $order]);
    }

    // Update order (only if pending)
    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if ($order->order_status !== 'pending') {
            return response()->json(['error' => 'Order cannot be modified after processing'], 400);
        }

        $validatedData = $request->validate([
            'order_items' => 'required|array',
            'order_items.*.menu_item_id' => 'required|exists:menu_items,id',
            'order_items.*.quantity' => 'required|integer|min:1',
        ]);

        $order->orderItems()->delete();
        $totalAmount = 0;

        foreach ($validatedData['order_items'] as $item) {
            $menuItem = MenuItem::findOrFail($item['menu_item_id']);
            $orderItem = new OrderItem([
                'menu_item_id' => $item['menu_item_id'],
                'quantity' => $item['quantity'],
                'total_price' => $menuItem->price* $item['quantity'],
            ]);
            $order->orderItems()->save($orderItem);
            $totalAmount += $menuItem->price * $item['quantity'];
        }

        $order->update(['total_price' => $totalAmount]);

        return response()->json(['message' => 'Order updated successfully', 'order' => $order->load('orderItems.menuItem')]);
    }

    // Change order status (only if paid)
    public function changeStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if (!$order->tx_ref) {
            return response()->json(['error' => 'Order does not have a valid transaction reference'], 400);
        }

        $payment = Payment::where('tx_ref', $order->tx_ref)->first(); 
        if (!$payment || $payment->status !== 'completed') {
            return response()->json(['error' => 'Order cannot be processed until paid'], 403);
}

        $validatedData = $request->validate(['order_status' => 'required|in:pending,processing,ready,completed,canceled']);

        $order->update(['order_status' => $validatedData['order_status']]);

        return response()->json(['message' => 'Order status updated', 'order' => $order]);
    }

    // Get orders by customer IP and ID
    public function getUserOrders(Request $request)
    {
        $customerIp = $request->query('customer_ip');
        $customerTempId = $request->query('customer_temp_id');

        $query = Order::query();
        if ($customerIp) $query->where('customer_ip', $customerIp);
        if ($customerTempId) $query->where('customer_temp_id', $customerTempId);

        return response()->json(['orders' => $query->with('orderItems.menuItem')->get()]);
    }
}
