<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobApplicationController extends Controller
{
    public function store(Request $request, $jobId)
    {
        if ($request->user()->role !== 'worker') {
            return response()->json(['message' => 'Only workers can apply to jobs.'], 403);
        }

        $existing = DB::select(
            "SELECT * FROM job_applications WHERE job_listing_id = ? AND applicant_id = ?",
            [$jobId, $request->user()->id]
        );
        if (count($existing) > 0) {
            return response()->json(['message' => 'You already applied to this job.'], 409);
        }

        $data = $request->validate([
            'note' => 'nullable|string|max:1000',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $path = null;
        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('applications', 'public');
        }

        $now = now();
        $id = DB::table('job_applications')->insertGetId([
            'job_listing_id' => $jobId,
            'applicant_id' => $request->user()->id,
            'note' => $data['note'] ?? null,
            'document_path' => $path,
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $application = DB::select("SELECT * FROM job_applications WHERE id = ?", [$id])[0];
        return response()->json($application, 201);
    }

    // READ — worker: own applications, joined with job info
    public function mine(Request $request)
    {
        $sql = "SELECT job_applications.*, jobs_listings.title AS job_title, jobs_listings.agency AS job_agency
                FROM job_applications
                JOIN jobs_listings ON job_applications.job_listing_id = jobs_listings.id
                WHERE job_applications.applicant_id = ?
                ORDER BY job_applications.created_at DESC";

        $applications = DB::select($sql, [$request->user()->id]);
        return response()->json($applications);
    }

    // READ — agency: applicants across all their posted jobs
    public function forAgency(Request $request)
    {
        if ($request->user()->role !== 'agency') {
            return response()->json(['message' => 'Only agencies can view this.'], 403);
        }

        $sql = "SELECT job_applications.*, jobs_listings.title AS job_title,
                       users.name AS applicant_name, users.email AS applicant_email, users.phone AS applicant_phone
                FROM job_applications
                JOIN jobs_listings ON job_applications.job_listing_id = jobs_listings.id
                JOIN users ON job_applications.applicant_id = users.id
                WHERE jobs_listings.creator_id = ?
                ORDER BY job_applications.created_at DESC";

        $applications = DB::select($sql, [$request->user()->id]);
        return response()->json($applications);
    }

    public function update(Request $request, $id)
    {
        $sql = "SELECT job_applications.*, jobs_listings.creator_id
                FROM job_applications
                JOIN jobs_listings ON job_applications.job_listing_id = jobs_listings.id
                WHERE job_applications.id = ?";
        $application = DB::select($sql, [$id])[0] ?? null;

        if (!$application || $application->creator_id !== $request->user()->id) {
            return response()->json(['message' => 'Not your listing.'], 403);
        }

        $data = $request->validate(['status' => 'required|in:pending,accepted,rejected']);

        DB::update(
            "UPDATE job_applications SET status = ?, updated_at = ? WHERE id = ?",
            [$data['status'], now(), $id]
        );

        $updated = DB::select("SELECT * FROM job_applications WHERE id = ?", [$id])[0];
        return response()->json($updated);
    }

    public function destroy(Request $request, $id)
    {
        $application = DB::select("SELECT * FROM job_applications WHERE id = ?", [$id])[0] ?? null;

        if (!$application || $application->applicant_id !== $request->user()->id) {
            return response()->json(['message' => 'Not your application.'], 403);
        }

        DB::delete("DELETE FROM job_applications WHERE id = ?", [$id]);
        return response()->json(['message' => 'Application withdrawn']);
    }
}
