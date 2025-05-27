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
    $orders = Order::with('orderItems.menuItem', 'table','payment')->get();

    return response()->json(['orders' => $orders], 200);
}
      
    public function show($id)
{
    // Find the order by ID and include related order items and menu items
    $order = Order::with('orderItems.menuItem', 'table','payment')->find($id);

    // If the order is not found, return a 404 response
    if (!$order) {
        return response()->json(['error' => 'Order not found'], 404);
    }

    // Return the order details
    return response()->json(['order' => $order], 200);
}

    // Store a new order for logged in users
public function storeForLoggedInUser(Request $request)
{
    $validatedData = $request->validate([
        'table_number' => $request->order_type === 'dine-in' ? 'required|integer|exists:tables,table_number' : 'nullable|integer|exists:tables,table_number',
        'order_items' => 'required|array',
        'order_items.*.menu_item_id' => 'required|exists:menu_items,id',
        'order_items.*.quantity' => 'required|integer|min:1',
        'order_items.*.excluded_ingredients' => 'nullable|array',
        'order_items.*.excluded_ingredients.*' => 'exists:ingredients,id',
        'order_type' => 'required|in:dine-in,remote',
    ]);

    $table = null;

    if (!empty($validatedData['table_number'])) {
        $table = Table::where('table_number', $validatedData['table_number'])->first();

        if ($table->table_status === 'occupied') {
            return response()->json(['error' => 'The selected table is already occupied'], 400);
        }
    }

    $customerId = auth()->id();

    $order = Order::create([
        'customer_id' => $customerId,
        'table_id' => $table ? $table->id : null,
        'order_date_time' => now(),
        'order_status' => 'pending',
        'total_price' => 0,
        'order_type' => $validatedData['order_type'],
        'payment_status' => 'pending',
    ]);

    $totalAmount = 0;
    foreach ($validatedData['order_items'] as $item) {
        $menuItem = MenuItem::findOrFail($item['menu_item_id']);
        $orderItem = new OrderItem([
            'menu_item_id' => $item['menu_item_id'],
            'quantity' => $item['quantity'],
            'total_price' => $menuItem->price * $item['quantity'],
            'excluded_ingredients' => isset($item['excluded_ingredients']) ? json_encode($item['excluded_ingredients']) : null,
        ]);
        $order->orderItems()->save($orderItem);
        $totalAmount += $menuItem->price * $item['quantity'];
    }

    $order->update(['total_price' => $totalAmount]);

    if ($table) {
        $table->update(['table_status' => 'occupied']);
    }

    return response()->json([
        'message' => 'Order placed successfully for logged-in user.',
        'order' => $order->load('orderItems.menuItem'),
    ], 201);
}

        // Store a new order for guest users
public function storeForGuestUser(Request $request)
{
    $validatedData = $request->validate([
        'table_number' => $request->order_type === 'dine-in' ? 'required|integer|exists:tables,table_number' : 'nullable|integer|exists:tables,table_number',
        'order_items' => 'required|array',
        'order_items.*.menu_item_id' => 'required|exists:menu_items,id',
        'order_items.*.quantity' => 'required|integer|min:1',
        'order_items.*.excluded_ingredients' => 'nullable|array',
        'order_items.*.excluded_ingredients.*' => 'exists:ingredients,id',
        'customer_ip' => 'required|ip',
        'customer_temp_id' => 'required|string|max:255',
        'order_type' => 'required|in:dine-in,remote',
    ]);

    $table = null;

    if (!empty($validatedData['table_number'])) {
        $table = Table::where('table_number', $validatedData['table_number'])->first();

        if ($table->table_status === 'occupied') {
            return response()->json(['error' => 'The selected table is already occupied'], 400);
        }
    }

    $order = Order::create([
        'customer_id' => null,
        'table_id' => $table ? $table->id : null,
        'order_date_time' => now(),
        'order_status' => 'pending',
        'total_price' => 0,
        'customer_ip' => $validatedData['customer_ip'],
        'customer_temp_id' => $validatedData['customer_temp_id'],
        'order_type' => $validatedData['order_type'],
        'payment_status' => 'pending',
    ]);

    $totalAmount = 0;
    foreach ($validatedData['order_items'] as $item) {
        $menuItem = MenuItem::findOrFail($item['menu_item_id']);
        $orderItem = new OrderItem([
            'menu_item_id' => $item['menu_item_id'],
            'quantity' => $item['quantity'],
            'total_price' => $menuItem->price * $item['quantity'],
            'excluded_ingredients' => isset($item['excluded_ingredients']) ? json_encode($item['excluded_ingredients']) : null,
        ]);
        $order->orderItems()->save($orderItem);
        $totalAmount += $menuItem->price * $item['quantity'];
    }

    $order->update(['total_price' => $totalAmount]);

    if ($table) {
        $table->update(['table_status' => 'occupied']);
    }

    return response()->json([
        'message' => 'Order placed successfully for guest user.',
        'order' => $order->load('orderItems.menuItem'),
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
        try {
            $order = Order::findOrFail($id);
    
            if ($order->order_status !== 'pending') {
                return response()->json(['error' => 'Order cannot be modified after processing'], 400);
            }
    
            $validatedData = $request->validate([
                'order_items' => 'required|array',
                'order_items.*.menu_item_id' => 'required|exists:menu_items,id',
                'order_items.*.quantity' => 'required|integer|min:1',
                'order_items.*.excluded_ingredients' => 'nullable|array', // Allow excluded ingredients as an array
                'order_items.*.excluded_ingredients.*' => 'exists:ingredients,id', // Validate each excluded ingredient
            ]);
    
            // Delete existing order items
            $order->orderItems()->delete();
            $totalAmount = 0;
    
            foreach ($validatedData['order_items'] as $item) {
                $menuItem = MenuItem::find($item['menu_item_id']);
                if (!$menuItem) {
                    return response()->json(['error' => 'Menu item not found'], 404);
                }
    
                $orderItem = new OrderItem([
                    'menu_item_id' => $item['menu_item_id'],
                    'quantity' => $item['quantity'],
                    'total_price' => $menuItem->price * $item['quantity'],
                    'excluded_ingredients' => isset($item['excluded_ingredients']) ? json_encode($item['excluded_ingredients']) : json_encode([]), // Save excluded ingredients as JSON
                ]);
                $order->orderItems()->save($orderItem);
                $totalAmount += $menuItem->price * $item['quantity'];
            }
    
            $order->update(['total_price' => $totalAmount]);
    
            return response()->json(['message' => 'Order updated successfully', 'order' => $order->load('orderItems.menuItem')], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Order not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred', 'details' => $e->getMessage()], 500);
        }
    }

    // Change order status (pending, processing, ready, completed, canceled)

public function changeStatus(Request $request, $id)
{
    $order = Order::findOrFail($id);

    // Validate new status
    $validatedData = $request->validate([
        'order_status' => 'required|in:pending,processing,ready,completed,canceled',
    ]);

    // Handle cancellation
    if ($validatedData['order_status'] === 'canceled') {
        if ($order->order_status !== 'pending') {
            return response()->json(['error' => 'Only pending orders can be canceled'], 400);
        }

        // Free the associated table
        if ($order->table_id) {
            $table = Table::find($order->table_id);
            if ($table) {
                $table->update(['table_status' => 'free']);
            }
        }

        $order->update(['order_status' => 'canceled']);

        return response()->json(['message' => 'Order canceled successfully', 'order' => $order], 200);
    }

    $payment = Payment::where('order_id', $order->id)->latest()->first();

    if (!$payment || $payment->status !== 'completed') {
        return response()->json(['error' => 'Order cannot be processed until payment is completed'], 403);
    }

    // Free table if order is completed
    if ($validatedData['order_status'] === 'completed' && $order->table_id) {
        $table = Table::find($order->table_id);
        if ($table) {
            $table->update(['table_status' => 'free']);
        }
    }

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

    if ($order->table_id) {
        $table = Table::find($order->table_id);
        if ($table) {
            $table->update(['table_status' => 'free']);
        }
    }
    // Delete the order and its associated order items
    $order->orderItems()->delete(); // Delete related order items
    $order->delete(); // Delete the order itself

    return response()->json(['message' => 'Order deleted successfully'], 200);
}

public function getOrderStatuses()
{
    // Fetch only the required fields from the orders table
    $orders = Order::select('id', 'order_status')->get();
    
    if ($orders->isEmpty()) {
        return response()->json(['message' => 'No orders found'], 404);
    }
    return response()->json(['orders' => $orders], 200);
}


public function getKitchenOrders()
{
    // Fetch orders with payment_status 'completed' and relevant order statuses
    $orders = Order::where('payment_status', 'completed')
        ->where(function ($query) {
            // For dine-in orders, only check payment status
            $query->where('order_type', 'dine-in');
            // For remote orders, check both payment status and arrival
            $query->orWhere(function ($subQuery) {
                $subQuery->where('order_type', 'remote')
                    ->where('arrived', 1); // Ensure remote orders have arrived
            });
        })
        ->whereIn('order_status', ['pending','processing','ready','completed','canceled']) // Relevant statuses for the kitchen
        ->with('orderItems.menuItem') // Include order items and menu items
        ->orderBy('order_date_time', 'asc') // Order by the time the order was placed
        ->get();

    // Format the response
    $response = $orders->map(function ($order) {
        return [
            'order_id' => $order->id,
            'order_type' => $order->order_type, // Include order type for clarity
            'order_status' => $order->order_status,
            'table_number' => $order->table ? $order->table->table_number : null, // Include table number if available
            'order_date_time' => $order->order_date_time, // Include order date and time
            'items' => $order->orderItems->map(function ($item) {
                return [
                    'menu_item_name' => $item->menuItem->name,
                    'quantity' => $item->quantity,
                    'excluded_ingredients' => $item->excluded_ingredients ? json_decode($item->excluded_ingredients) : [], // Decode excluded ingredients
                ];
            }),
        ];
    });

    return response()->json(['orders' => $response], 200);
}

public function getReadyOrders()
{
    // Fetch orders with status 'ready'
    $orders = Order::where('order_status', 'ready')
        ->with('table', 'customer') // Include table and customer details
        ->orderBy('order_date_time', 'asc') // Order by the time the order was placed
        ->get();

    // Format the response
    $response = $orders->map(function ($order) {
        return [
            'order_id' => $order->id,
            'customer_id' => $order->customer_id, // Include customer ID
            'customer_temp_id' => $order->customer_temp_id, // Include customer temp ID
            'customer_ip' => $order->customer_ip, // Include customer IP
            'customer_name' => $order->customer ? $order->customer->name : $order->customer_temp_id, // Use customer name or temp ID
            'table_number' => $order->table ? $order->table->table_number : null, // Include table number if available
            'order_status' => $order->order_status, // Status should always be 'ready'
          ];
    });

    return response()->json(['ready_orders' => $response], 200);
}
}
