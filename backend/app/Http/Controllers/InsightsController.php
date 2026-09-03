<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class InsightsController extends Controller
{
    public function index()
    {
        return response()->json([

            // JOIN — every application with its job title and applicant name
            'applications_with_details' => DB::select("
                SELECT job_applications.id, job_applications.status,
                       jobs_listings.title AS job_title,
                       users.name AS applicant_name
                FROM job_applications
                JOIN jobs_listings ON job_applications.job_listing_id = jobs_listings.id
                JOIN users ON job_applications.applicant_id = users.id
            "),

            // LEFT JOIN — every job listing, including ones with zero applications
            'jobs_with_application_count' => DB::select("
                SELECT jobs_listings.id, jobs_listings.title,
                       COUNT(job_applications.id) AS application_count
                FROM jobs_listings
                LEFT JOIN job_applications ON job_applications.job_listing_id = jobs_listings.id
                GROUP BY jobs_listings.id, jobs_listings.title
            "),

            // RIGHT JOIN — every user, including agencies who haven't posted a job yet
            // (SQLite 3.39+ supports RIGHT JOIN natively)
            'agencies_and_their_jobs' => DB::select("
                SELECT users.name AS agency_name, jobs_listings.title AS job_title
                FROM jobs_listings
                RIGHT JOIN users ON jobs_listings.creator_id = users.id
                WHERE users.role = 'agency'
            "),

            // AGGREGATE + GROUP BY — application count per status
            'application_status_breakdown' => DB::select("
                SELECT status, COUNT(*) AS total
                FROM job_applications
                GROUP BY status
            "),

            // AGGREGATE + GROUP BY — number of jobs posted per country
            'jobs_per_country' => DB::select("
                SELECT country, COUNT(*) AS total_jobs
                FROM jobs_listings
                GROUP BY country
            "),

            // SUBQUERY — workers who have applied to more jobs than average
            'above_average_applicants' => DB::select("
                SELECT users.name, COUNT(job_applications.id) AS application_count
                FROM users
                JOIN job_applications ON job_applications.applicant_id = users.id
                GROUP BY users.id, users.name
                HAVING COUNT(job_applications.id) > (
                    SELECT AVG(sub_count) FROM (
                        SELECT COUNT(*) AS sub_count
                        FROM job_applications
                        GROUP BY applicant_id
                    )
                )
            "),

        ]);
    }
}
