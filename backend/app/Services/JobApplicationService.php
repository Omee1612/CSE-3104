<?php

namespace App\Services;

use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JobApplicationService
{
    /**
     * Create a new job application.
     */
    public function apply(User $user, JobListing $job, array $data, ?UploadedFile $documentFile = null): JobApplication
    {
        $existing = $job->applications()->where('applicant_id', $user->id)->first();
        if ($existing) {
            throw ValidationException::withMessages([
                'application' => ['You have already applied to this job listing.'],
            ]);
        }

        $path = null;
        if ($documentFile) {
            $path = $documentFile->store('applications', 'public');
        }

        return $job->applications()->create([
            'applicant_id' => $user->id,
            'note' => $data['note'] ?? null,
            'document_path' => $path,
        ]);
    }

    /**
     * Retrieve all applications submitted by a worker.
     */
    public function getWorkerApplications(User $user)
    {
        return $user->applications()
            ->with([
                'job' => function ($q) {
                    $q->with('creator:id,name,agency,verification_status');
                }
            ])
            ->latest()
            ->get();
    }

    /**
     * Retrieve all applications for jobs posted by an agency.
     * Includes applicant identity and verification status.
     */
    public function getAgencyApplications(User $user)
    {
        return JobApplication::whereHas('job', function ($q) use ($user) {
            $q->where('creator_id', $user->id);
        })
            ->with([
                'job:id,title,agency,verified',
                'applicant:id,name,email,phone,verification_status',
            ])
            ->latest()
            ->get();
    }

    /**
     * Update application status (accepted / rejected).
     */
    public function updateStatus(JobApplication $application, string $status): JobApplication
    {
        $transactionStart = DB::getDriverName() === 'sqlite' ? 'BEGIN TRANSACTION' : 'START TRANSACTION';
        $lockClause = in_array(DB::getDriverName(), ['mysql', 'mariadb'], true) ? ' FOR UPDATE' : '';
        $nestedTransaction = DB::connection()->getPdo()->inTransaction();
        $savepoint = 'job_application_status';
        $commitSql = $nestedTransaction ? "RELEASE SAVEPOINT {$savepoint}" : 'COMMIT';
        $rollbackRawTransaction = static function () use ($nestedTransaction, $savepoint): void {
            if ($nestedTransaction) {
                DB::unprepared("ROLLBACK TO SAVEPOINT {$savepoint}");
                DB::unprepared("RELEASE SAVEPOINT {$savepoint}");
                return;
            }

            DB::unprepared('ROLLBACK');
        };

        DB::unprepared($nestedTransaction ? "SAVEPOINT {$savepoint}" : $transactionStart);
        try {
            $currentApplication = DB::selectOne(
                'SELECT id FROM job_applications WHERE id = ?' . $lockClause,
                [$application->id]
            );

            if (!$currentApplication) {
                throw new \RuntimeException('Application not found during status update.');
            }

            DB::update(
                'UPDATE job_applications SET status = ?, updated_at = ? WHERE id = ?',
                [$status, now()->toDateTimeString(), $application->id]
            );

            $updatedApplication = DB::selectOne(
                'SELECT * FROM job_applications WHERE id = ?',
                [$application->id]
            );
            DB::unprepared($commitSql);

            $application->setRawAttributes((array) $updatedApplication, true);
            return $application;
        } catch (\Throwable $e) {
            try {
                $rollbackRawTransaction();
            } catch (\Throwable $rollbackError) {
                report($rollbackError);
            }

            throw $e;
        }
    }

    /**
     * Withdraw / delete application.
     */
    public function withdraw(JobApplication $application): void
    {
        $application->delete();
    }
}
