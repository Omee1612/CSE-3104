<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $documents = DB::select(
            "SELECT * FROM documents WHERE user_id = ? ORDER BY created_at DESC",
            [$request->user()->id]
        );
        return response()->json($documents);
    }

    public function show(Request $request, $id)
    {
        $document = DB::select("SELECT * FROM documents WHERE id = ?", [$id])[0] ?? null;
        return response()->json($document);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'nullable|in:missing,complete',
        ]);

        $now = now();
        $id = DB::table('documents')->insertGetId([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'status' => $data['status'] ?? 'missing',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $document = DB::select("SELECT * FROM documents WHERE id = ?", [$id])[0];
        return response()->json($document, 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:missing,complete',
        ]);

        $data['updated_at'] = now();

        DB::table('documents')->where('id', $id)->update($data);

        $document = DB::select("SELECT * FROM documents WHERE id = ?", [$id])[0];
        return response()->json($document);
    }

    public function destroy($id)
    {
        DB::delete("DELETE FROM documents WHERE id = ?", [$id]);
        return response()->json(['message' => 'Document deleted']);
    }
}
