<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TableController extends Controller
{
    // Retrieve all tables
    public function index()
    {
        try {
            $tables = Table::all(['table_number', 'qr_code', 'table_status']);
            return response()->json($tables, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching tables: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching tables',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    // Store new tables
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'table_number' => 'required|integer|min:1',
            'base_link' => 'required|url',
            'table_status' => 'nullable|in:free,occupied',
        ]);

        $lastTable = Table::orderBy('table_number', 'desc')->first();
        $startNumber = $lastTable ? $lastTable->table_number + 1 : 1;

        $tableAmount = (int) $validatedData['table_number'];
        $baseLink = rtrim($validatedData['base_link'], '/');
        $tableStatus = $validatedData['status'] ?? 'free';

        $tables = [];
        for ($i = 0; $i < $tableAmount; $i++) {
            $tables[] = Table::create([
                'table_number' => $startNumber + $i,
                'qr_code' => url("{$baseLink}/menu/" . ($startNumber + $i)),
                'table_status' => $tableStatus,
            ]);
        }

        return response()->json(['message' => 'Tables created successfully', 'tables' => $tables], 201);
    }

    // Store tables in a range
    public function storeByRange(Request $request)
    {
        $validatedData = $request->validate([
            'start_table_number' => 'required|integer|min:1',
            'end_table_number' => 'required|integer|min:1|gte:start_table_number',
            'base_link' => 'required|url',
            'table_status' => 'nullable|in:free,occupied',
     ]);

         $baseLink = rtrim($validatedData['base_link'], '/');
        $tableStatus = $validatedData['table_status'] ?? 'free';

        $tablesToCreate = [];
        for ($i = $validatedData['start_table_number']; $i <= $validatedData['end_table_number']; $i++) {
         // Check if the table number already exists
            if (!Table::where('table_number', $i)->exists()) {
                $tablesToCreate[] = [
                    'table_number' => $i,
                    'qr_code' => url("{$baseLink}/menu/{$i}"),
                    'table_status' => $tableStatus,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

    // Bulk insert the new tables
    if (!empty($tablesToCreate)) {
        Table::insert($tablesToCreate);
    }

    return response()->json([
        'message' => count($tablesToCreate) . " tables added successfully",
        'skipped_tables' => array_diff(
            range($validatedData['start_table_number'], $validatedData['end_table_number']),
            array_column($tablesToCreate, 'table_number')
        ),
    ], 201);
}

    // Retrieve a single table
    public function show($id)
    {
        $table = Table::find($id);
        if (!$table) {
            return response()->json(['message' => 'Table not found'], 404);
        }
        return response()->json($table, 200);
    }

    // Update a table
    public function update(Request $request, $id)
    {
        $table = Table::find($id);
        if (!$table) {
            return response()->json(['message' => 'Table not found'], 404);
        }

        $validatedData = $request->validate([
            'table_number' => 'required|integer|min:1',
            'qr_code' => 'required|url',
            'table_status' => 'nullable|in:free,occupied',
        ]);

        $table->update($validatedData);

        return response()->json(['message' => 'Table updated successfully', 'table' => $table], 200);
    }

    // Delete a table
    public function destroy($tableNumber)
    {
        $table = Table::where('table_number', $tableNumber)->first();
        if (!$table) {
            return response()->json(['message' => 'Table not found'], 404);
        }
        if ($table->table_status === 'occupied') {
            return response()->json(['message' => 'Cannot delete an occupied table'], 400);
        }

        $table->delete();

        return response()->json(['message' => 'Table deleted successfully'], 200);
    }

    //delete in batch


public function destroyBatchByRange(Request $request)
{
    $validatedData = $request->validate([
        'start_table_number' => 'required|integer|min:1',
        'end_table_number' => 'required|integer|min:1|gte:start_table_number',
    ]);

    // Fetch tables in the specified range
    $tables = Table::whereBetween('table_number', [
        $validatedData['start_table_number'],
        $validatedData['end_table_number']
    ])->get();

    // Check if any table is occupied
    $occupiedTables = $tables->where('table_status', 'occupied');

    if ($occupiedTables->isNotEmpty()) {
        return response()->json([
            'message' => 'Cannot delete tables because some are occupied',
            'occupied_tables' => $occupiedTables->pluck('table_number'),
        ], 400);
    }

    // Delete the tables that are not occupied
    $deletedCount = Table::whereBetween('table_number', [
        $validatedData['start_table_number'],
        $validatedData['end_table_number']
    ])->where('table_status', '!=', 'occupied')->delete();

    if ($deletedCount === 0) {
        return response()->json([
            'message' => 'No tables found in the specified range',
        ], 404);
    }

    return response()->json([
        'message' => "{$deletedCount} tables deleted successfully",
    ], 200);
}

//delete all tables

public function destroyAll()
{
    $deletedCount = Table::count();
    Table::query()->delete(); // Delete all tables

    return response()->json([
        'message' => "All {$deletedCount} tables deleted successfully",
    ], 200);
}

public function freeTable($tableNumber)
{
    // Find the table by its table number
    $table = Table::where('table_number', $tableNumber)->first();

    // If the table is not found, return a 404 response
    if (!$table) {
        return response()->json(['message' => 'Table not found'], 404);
    }

    // If the table is already free, return a message
    if ($table->table_status === 'free') {
        return response()->json(['message' => 'Table is already free'], 400);
    }

    // Update the table status to free
    $table->update(['table_status' => 'free']);

    return response()->json(['message' => 'Table freed successfully', 'table' => $table], 200);
}
}
