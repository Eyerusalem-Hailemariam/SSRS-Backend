<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Http\Request;

class TableController extends Controller
{
    // Retrieve all tables
    public function index()
    {
        return response()->json(Table::all(), 200);
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
    public function destroy($id)
    {
        $table = Table::find($id);
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
    Table::query()->delete(); // Use delete(), not truncate()

    return response()->json([
        'message' => "All {$deletedCount} tables deleted successfully",
    ], 200);
}
}
