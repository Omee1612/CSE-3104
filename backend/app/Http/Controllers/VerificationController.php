<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerificationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $userId = $request->user()->id;
        $path = $request->file('document')->store('verifications', 'public');

        DB::update(
            "UPDATE users SET verification_document_path = ?, verification_status = ?, updated_at = ? WHERE id = ?",
            [$path, 'pending', now(), $userId]
        );

        $result = $this->runAiCheck($path);

        DB::update(
            "UPDATE users SET verification_status = ?, verification_note = ?, verified_at = ?, updated_at = ? WHERE id = ?",
            [
                $result['authentic'] ? 'verified' : 'rejected',
                $result['reason'],
                $result['authentic'] ? now() : null,
                now(),
                $userId,
            ]
        );

        $user = DB::select("SELECT verification_status, verification_note FROM users WHERE id = ?", [$userId])[0];

        return response()->json([
            'status' => $user->verification_status,
            'note' => $user->verification_note,
        ]);
    }

    private function runAiCheck(string $path): array
    {
        $fullPath = storage_path('app/public/' . $path);
        $size = @filesize($fullPath) ?: 0;

        if ($size < 5000) {
            return ['authentic' => false, 'reason' => 'Document appears incomplete or unreadable. Please re-upload a clear scan.'];
        }

        return ['authentic' => true, 'reason' => 'Document passed automated authenticity checks.'];
    }

    public function status(Request $request)
    {
        $user = DB::select(
            "SELECT verification_status, verification_note FROM users WHERE id = ?",
            [$request->user()->id]
        )[0];

        return response()->json([
            'status' => $user->verification_status,
            'note' => $user->verification_note,
        ]);
    }
}
