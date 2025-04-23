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

    public function index()
{
    // Retrieve all orders with their associated order items and menu items
    $orders = Order::with('orderItems.menuItem')->get();

    return response()->json(['orders' => $orders], 200);
}
      
    public function show($id)
{
    // Find the order by ID and include related order items and menu items
    $order = Order::with('orderItems.menuItem')->find($id);

    // If the order is not found, return a 404 response
    if (!$order) {
        return response()->json(['error' => 'Order not found'], 404);
    }

    // Return the order details
    return response()->json(['order' => $order], 200);
}

    // Store a new order
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'table_number' => $request->order_type === 'dine-in' ? 'required|integer|exists:tables,table_number' : 'nullable|integer|exists:tables,table_number',
            'order_items' => 'required|array',
            'order_items.*.menu_item_id' => 'required|exists:menu_items,id',
            'order_items.*.quantity' => 'required|integer|min:1',
            'customer_ip' => 'required|ip',
            'customer_temp_id' => 'required|string|max:255',
            'order_type' => 'required|in:dine-in,remote', // Specify order type
        ]);

        $table = null;

        // If a table number is provided, validate that it is not occupied
        if (!empty($validatedData['table_number'])) {
            $table = Table::where('table_number', $validatedData['table_number'])->first();
    
            if ($table->table_status === 'occupied') {
                return response()->json(['error' => 'The selected table is already occupied'], 400);
            }
        }

        $tx_ref = 'CHAPA-' . Str::uuid();

        $customerId = auth()->check() ? auth()->id() : null;

        $order = Order::create([
            'customer_id' => $customerId, 
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

    // Ensure the order is a remote order
    if ($order->order_type !== 'remote') {
        return response()->json(['error' => 'This order is not a remote order'], 400);
    }

    // Ensure the order is in a pending state
    if ($order->order_status !== 'pending') {
        return response()->json(['error' => 'Cannot notify arrival for a non-pending order'], 400);
    }

    // Validate the table number
    $validatedData = $request->validate([
        'table_number' => 'nullable|integer|exists:tables,table_number',
    ]);

    $table = null;

    // If a table number is provided, find the table and mark it as occupied
    if (!empty($validatedData['table_number'])) {
        $table = Table::where('table_number', $validatedData['table_number'])->first();

        // Ensure the table is not already occupied
        if ($table->table_status === 'occupied') {
            return response()->json(['error' => 'The selected table is already occupied'], 400);
        }

        // Update the table status to occupied
        $table->update(['table_status' => 'occupied']);
    }

    // Update the order to associate it with the table and mark arrival
    $order->update([
        'table_id' => $table->id,
        'notified_arrival' => now(),
        'arrived' => 1, // Mark the order as arrived
    ]);

    return response()->json([
        'message' => 'Arrival notified successfully, table assigned',
        'order' => $order->load('orderItems.menuItem'),
        'table' => $table,
    ]);
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
        if (auth()->check()) {
            // Retrieve orders for the logged-in user
            $orders = Order::where('customer_id', auth()->id())
                ->with('orderItems.menuItem')
                ->get();
        } else {
            // Retrieve orders for guest users
            $customerIp = $request->query('customer_ip');
            $customerTempId = $request->query('customer_temp_id');
    
            $query = Order::query();
            if ($customerIp) $query->where('customer_ip', $customerIp);
            if ($customerTempId) $query->where('customer_temp_id', $customerTempId);
    
            $orders = $query->with('orderItems.menuItem')->get();
        }
    
        return response()->json(['orders' => $orders]);
    }

    public function destroy($id)
{
    // Find the order by ID
    $order = Order::find($id);

    // If the order is not found, return a 404 response
    if (!$order) {
        return response()->json(['error' => 'Order not found'], 404);
    }

    // Delete the order and its associated order items
    $order->orderItems()->delete(); // Delete related order items
    $order->delete(); // Delete the order itself

    return response()->json(['message' => 'Order deleted successfully'], 200);
}
}
