<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use App\Models\JobListing;
use Illuminate\Http\Request;

class JobApplicationController extends Controller
{
    // CREATE — worker applies, with optional document upload
    public function store(Request $request, JobListing $job)
    {
        if ($request->user()->role !== 'worker') {
            return response()->json(['message' => 'Only workers can apply to jobs.'], 403);
        }

        $existing = $job->applications()->where('applicant_id', $request->user()->id)->first();
        if ($existing) {
            return response()->json(['message' => 'You already applied to this job.'], 409);
        }

        $data = $request->validate([
            'note' => 'nullable|string|max:1000',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB
        ]);

        $path = null;
        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('applications', 'public');
        }

        $application = $job->applications()->create([
            'applicant_id' => $request->user()->id,
            'note' => $data['note'] ?? null,
            'document_path' => $path,
        ]);

        return response()->json($application, 201);
    }

    // READ — worker: all of their own applications, with job info + status
    public function mine(Request $request)
    {
        return response()->json(
            $request->user()->applications()->with('job')->latest()->get()
        );
    }

    // READ — agency: every application across all of their posted jobs
    public function forAgency(Request $request)
    {
        if ($request->user()->role !== 'agency') {
            return response()->json(['message' => 'Only agencies can view this.'], 403);
        }

        $applications = JobApplication::whereHas('job', function ($q) use ($request) {
            $q->where('creator_id', $request->user()->id);
        })
            ->with(['job:id,title,agency', 'applicant:id,name,email,phone'])
            ->latest()
            ->get();

        return response()->json($applications);
    }

    // UPDATE — agency accepts/rejects
    public function update(Request $request, JobApplication $application)
    {
        if ($application->job->creator_id !== $request->user()->id) {
            return response()->json(['message' => 'Not your listing.'], 403);
        }

        $data = $request->validate(['status' => 'required|in:pending,accepted,rejected']);
        $application->update($data);

        return response()->json($application);
    }

    // DELETE — worker withdraws
    public function destroy(Request $request, JobApplication $application)
    {
        if ($application->applicant_id !== $request->user()->id) {
            return response()->json(['message' => 'Not your application.'], 403);
        }

        $application->delete();

        return response()->json(['message' => 'Application withdrawn']);
    }
}
