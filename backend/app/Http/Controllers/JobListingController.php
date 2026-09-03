<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobListingController extends Controller
{
    public function index(Request $request)
    {
        $sql = "SELECT jobs_listings.*, users.name AS creator_name, users.agency AS creator_agency
                FROM jobs_listings
                JOIN users ON jobs_listings.creator_id = users.id
                WHERE 1 = 1";
        $bindings = [];

        if ($request->filled('search')) {
            $sql .= " AND (jobs_listings.title LIKE ? OR jobs_listings.country LIKE ?)";
            $search = '%' . $request->string('search') . '%';
            $bindings[] = $search;
            $bindings[] = $search;
        }

        if ($request->boolean('verified')) {
            $sql .= " AND jobs_listings.verified = 1";
        }

        $sql .= " ORDER BY jobs_listings.created_at DESC";

        $jobs = DB::select($sql, $bindings);
        return response()->json($jobs);
    }

    public function show($id)
    {
        $sql = "SELECT jobs_listings.*, users.name AS creator_name, users.agency AS creator_agency
                FROM jobs_listings
                JOIN users ON jobs_listings.creator_id = users.id
                WHERE jobs_listings.id = ?";
        $job = DB::select($sql, [$id])[0] ?? null;
        return response()->json($job);
    }

    public function store(Request $request)
    {
        if ($request->user()->role !== 'agency') {
            return response()->json(['message' => 'Only agencies can post job listings.'], 403);
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'criteria' => 'required|string',
            'country' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'salary' => 'required|string|max:255',
            'agency' => 'required|string|max:255',
        ]);

        $now = now();
        $id = DB::table('jobs_listings')->insertGetId([
            'creator_id' => $request->user()->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'criteria' => $data['criteria'],
            'country' => $data['country'],
            'city' => $data['city'],
            'salary' => $data['salary'],
            'agency' => $data['agency'],
            'verified' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $job = DB::select("SELECT * FROM jobs_listings WHERE id = ?", [$id])[0];
        return response()->json($job, 201);
    }

    public function update(Request $request, $id)
    {
        $job = DB::select("SELECT * FROM jobs_listings WHERE id = ?", [$id])[0] ?? null;

        if (!$job || $job->creator_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only edit your own listings.'], 403);
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'country' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:255',
            'salary' => 'sometimes|string|max:255',
            'agency' => 'sometimes|string|max:255',
            'verified' => 'sometimes|boolean',
        ]);

        $data['updated_at'] = now();
        DB::table('jobs_listings')->where('id', $id)->update($data);

        $updated = DB::select("SELECT * FROM jobs_listings WHERE id = ?", [$id])[0];
        return response()->json($updated);
    }

    public function destroy(Request $request, $id)
    {
        $job = DB::select("SELECT * FROM jobs_listings WHERE id = ?", [$id])[0] ?? null;

        if (!$job || $job->creator_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only delete your own listings.'], 403);
        }

        DB::delete("DELETE FROM jobs_listings WHERE id = ?", [$id]);
        return response()->json(['message' => 'Job listing deleted']);
    }
}
