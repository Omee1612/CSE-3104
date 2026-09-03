<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
    // READ — all contracts belonging to the logged-in user
    public function index(Request $request)
    {
        $contracts = DB::select(
            "SELECT * FROM contracts WHERE user_id = ? ORDER BY created_at DESC",
            [$request->user()->id]
        );
        return response()->json($contracts);
    }

    // READ — one contract
    public function show($id)
    {
        $contract = DB::select("SELECT * FROM contracts WHERE id = ?", [$id])[0] ?? null;
        return response()->json($contract);
    }

    // CREATE
    public function store(Request $request)
    {
        $data = $request->validate([
            'job_title' => 'required|string|max:255',
            'destination_country' => 'required|string|max:255',
            'agency_name' => 'required|string|max:255',
            'salary_amount' => 'required|numeric',
            'salary_currency' => 'nullable|string|max:10',
        ]);

        $now = now();
        $id = DB::table('contracts')->insertGetId([
            'user_id' => $request->user()->id,
            'job_title' => $data['job_title'],
            'destination_country' => $data['destination_country'],
            'agency_name' => $data['agency_name'],
            'salary_amount' => $data['salary_amount'],
            'salary_currency' => $data['salary_currency'] ?? null,
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $contract = DB::select("SELECT * FROM contracts WHERE id = ?", [$id])[0];
        return response()->json($contract, 201);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'job_title' => 'sometimes|string|max:255',
            'destination_country' => 'sometimes|string|max:255',
            'agency_name' => 'sometimes|string|max:255',
            'salary_amount' => 'sometimes|numeric',
            'salary_currency' => 'sometimes|string|max:10',
            'status' => 'sometimes|in:pending,verified,rejected',
        ]);

        $data['updated_at'] = now();
        DB::table('contracts')->where('id', $id)->update($data);

        $contract = DB::select("SELECT * FROM contracts WHERE id = ?", [$id])[0];
        return response()->json($contract);
    }

    // DELETE
    public function destroy($id)
    {
        DB::delete("DELETE FROM contracts WHERE id = ?", [$id]);
        return response()->json(['message' => 'Contract deleted']);
    }
}
