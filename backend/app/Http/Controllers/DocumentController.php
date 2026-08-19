<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    // READ — all documents belonging to the logged-in user
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->documents()->latest()->get()
        );
    }

    // READ — one document
    public function show(Document $document)
    {
        return response()->json($document);
    }

    // CREATE
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'nullable|in:missing,complete',
        ]);

        $document = $request->user()->documents()->create($data);

        return response()->json($document, 201);
    }

    // UPDATE
    public function update(Request $request, Document $document)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:missing,complete',
        ]);

        $document->update($data);

        return response()->json($document);
    }

    // DELETE
    public function destroy(Document $document)
    {
        $document->delete();

        return response()->json(['message' => 'Document deleted']);
    }
}
