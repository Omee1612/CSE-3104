<?php

namespace App\Http\Controllers;

use App\Models\JobListing;
use Illuminate\Http\Request;

class JobListingController extends Controller
{
    // READ — public, anyone can browse
    public function index(Request $request)
    {
        $query = JobListing::with('creator:id,name,agency')->latest();

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('verified')) {
            $query->where('verified', true);
        }

        return response()->json($query->get());
    }

    public function show(JobListing $job)
    {
        return response()->json($job->load('creator:id,name,agency'));
    }

    // CREATE — only agency-role users can post jobs
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

        $job = $request->user()->postedJobs()->create($data);

        return response()->json($job, 201);
    }

    // UPDATE — only the agency that created it can edit
    public function update(Request $request, JobListing $job)
    {
        if ($job->creator_id !== $request->user()->id) {
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

        $job->update($data);

        return response()->json($job);
    }

    // DELETE — only the creator can delete
    public function destroy(Request $request, JobListing $job)
    {
        if ($job->creator_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only delete your own listings.'], 403);
        }

        $job->delete();

        return response()->json(['message' => 'Job listing deleted']);
    }
}
