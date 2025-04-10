<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\MenuItem;
use Illuminate\Http\Request;

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
            'customer_generated_id' => 'required|string|max:255',
            'order_type' => 'required|in:dine-in,remote', // Specify order type
        ]);

        $table = null;
        if ($validatedData['order_type'] === 'dine-in') {
            $table = Table::where('table_number', $validatedData['table_number'])->firstOrFail();
        }

        $order = Order::create([
            'table_id' => $table ? $table->id : null,
            'order_date' => now(),
            'order_status' => 'pending',
            'total_amount' => 0,
            'customer_ip' => $validatedData['customer_ip'],
            'customer_generated_id' => $validatedData['customer_generated_id'],
            'order_type' => $validatedData['order_type'],
            'payment_status' => 'pending', // Payment is handled separately
        ]);

        $totalAmount = 0;
        foreach ($validatedData['order_items'] as $item) {
            $menuItem = MenuItem::findOrFail($item['menu_item_id']);
            $orderItem = new OrderItem([
                'menu_item_id' => $item['menu_item_id'],
                'quantity' => $item['quantity'],
                'price' => $menuItem->price,
            ]);
            $order->orderItems()->save($orderItem);
            $totalAmount += $menuItem->price * $item['quantity'];
        }

        $order->update(['total_amount' => $totalAmount]);

        if ($table) {
            $table->update(['table_status' => 'occupied']);
        }

        return response()->json([
            'message' => 'Order placed successfully',
            'order' => $order->load('orderItems.menuItem'),
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
                'price' => $menuItem->price,
            ]);
            $order->orderItems()->save($orderItem);
            $totalAmount += $menuItem->price * $item['quantity'];
        }

        $order->update(['total_amount' => $totalAmount]);

        return response()->json(['message' => 'Order updated successfully', 'order' => $order->load('orderItems.menuItem')]);
    }

    // Change order status (only if paid)
    public function changeStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if ($order->payment_status !== 'paid') {
            return response()->json(['error' => 'Order cannot be processed until paid'], 403);
        }

        $validatedData = $request->validate(['order_status' => 'required|in:pending,processing,preparing,ready,completed']);

        $order->update(['order_status' => $validatedData['order_status']]);

        return response()->json(['message' => 'Order status updated', 'order' => $order]);
    }

    // Get orders by customer IP and ID
    public function getUserOrders(Request $request)
    {
        $customerIp = $request->query('customer_ip');
        $customerGeneratedId = $request->query('customer_generated_id');

        $query = Order::query();
        if ($customerIp) $query->where('customer_ip', $customerIp);
        if ($customerGeneratedId) $query->where('customer_generated_id', $customerGeneratedId);

        return response()->json(['orders' => $query->with('orderItems.menuItem')->get()]);
    }
}
