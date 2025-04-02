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
            'status' => 'nullable|string',
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
            'table_number' => 'required|integer|max:255',
            'qr_code' => 'required|string',
            'table_status' => 'required|string|max:255',
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

        $table->delete();

        return response()->json(['message' => 'Table deleted successfully'], 200);
    }
}
