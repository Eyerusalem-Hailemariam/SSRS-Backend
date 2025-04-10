<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Order;
use App\Models\MenuItem;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    // Get all order items for a specific order
    public function index($orderId)
    {
        $order = Order::with('orderItems.menuItem')->findOrFail($orderId);
        return response()->json($order->orderItems, 200);
    }

    // Store a new order item
    public function store(Request $request, $orderId)
    {
        $validatedData = $request->validate([
            'menu_item_id' => 'required|exists:menu_items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $order = Order::findOrFail($orderId);
        $menuItem = MenuItem::findOrFail($validatedData['menu_item_id']);
        
        $itemTotal = $menuItem->price * $validatedData['quantity'];

        $orderItem = OrderItem::create([
            'order_id' => $orderId,
            'menu_item_id' => $menuItem->id,
            'quantity' => $validatedData['quantity'],
        ]);

        $order->total_amount += $itemTotal;
        $order->save();

        return response()->json(['message' => 'Order item added successfully.', 'order_item' => $orderItem], 201);
    }

    // Show a specific order item
    public function show($id)
    {
        $orderItem = OrderItem::with('menuItem')->findOrFail($id);
        return response()->json($orderItem, 200);
    }

    // Update an order item
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $orderItem = OrderItem::findOrFail($id);
        $menuItem = $orderItem->menuItem;
        
        // Adjust order total amount
        $order = $orderItem->order;
        $order->total_amount -= ($orderItem->quantity * $menuItem->price);
        $orderItem->quantity = $validatedData['quantity'];
        $order->total_amount += ($orderItem->quantity * $menuItem->price);
        $order->save();
        
        $orderItem->save();

        return response()->json(['message' => 'Order item updated successfully.', 'order_item' => $orderItem], 200);
    }

    // Delete an order item
    public function destroy($id)
    {
        $orderItem = OrderItem::findOrFail($id);
        
        $order = $orderItem->order;
        $order->total_amount -= ($orderItem->quantity * $orderItem->menuItem->price);
        $order->save();
        
        $orderItem->delete();

        return response()->json(['message' => 'Order item deleted successfully.'], 200);
    }
}
