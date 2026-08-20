import { useEffect, useState } from 'react'
import Sidebar from '../components/Sidebar.jsx'
import StatusBadge from '../components/StatusBadge.jsx'
import { fetchJobs, applyToJob, fetchMyApplications, createJob, fetchMe } from '../lib/api.js'

export default function JobSearch() {
  const [me, setMe] = useState(null)
  const [jobs, setJobs] = useState([])
  const [appliedIds, setAppliedIds] = useState(new Set())
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [query, setQuery] = useState('')
  const [verifiedOnly, setVerifiedOnly] = useState(false)
  const [applyTarget, setApplyTarget] = useState(null) // job being applied to
  const [showPostForm, setShowPostForm] = useState(false)

  useEffect(() => {
    fetchMe().then(setMe).catch(() => {})
  }, [])

  useEffect(() => {
    if (me?.role === 'worker') {
      fetchMyApplications()
        .then((apps) => setAppliedIds(new Set(apps.map((a) => a.job_listing_id))))
        .catch(() => {})
    }
  }, [me])

  const loadJobs = () => {
    setLoading(true)
    setError('')
    fetchJobs({ search: query || undefined, verified: verifiedOnly ? 1 : undefined })
      .then(setJobs)
      .catch(() => setError('Failed to load job listings.'))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    const timeout = setTimeout(loadJobs, 300)
    return () => clearTimeout(timeout)
  }, [query, verifiedOnly])

  return (
    <div className="min-h-screen flex bg-paper">
      <Sidebar />
      <main className="flex-1 px-6 md:px-10 py-8 max-w-6xl">
        <header className="flex items-start justify-between">
          <div>
            <h1 className="font-display text-3xl text-navy">Job search</h1>
            <p className="text-sm text-navy/60 mt-1">
              Every listing is cross-checked against licensed agency records.
            </p>
          </div>
          {me?.role === 'agency' && (
            <button
              onClick={() => setShowPostForm((s) => !s)}
              className="rounded-card bg-navy text-paper text-sm font-medium px-4 py-2.5 hover:bg-navy-600 transition-colors"
            >
              {showPostForm ? 'Cancel' : 'Post a job'}
            </button>
          )}
        </header>

        {me?.role === 'agency' && showPostForm && (
          <PostJobForm
            onCreated={() => { setShowPostForm(false); loadJobs() }}
            onError={setError}
          />
        )}

        {error && (
          <p className="mt-4 text-sm text-alert bg-alert/10 border border-alert/30 rounded-card px-3.5 py-2.5">
            {error}
          </p>
        )}

        <div className="mt-6 flex flex-col sm:flex-row gap-3">
          <input
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Search by role or country..."
            className="flex-1 rounded-card border border-navy/20 bg-white px-4 py-2.5 text-sm outline-none focus:border-stamp"
          />
          <label className="flex items-center gap-2 text-sm text-navy/70 bg-white border border-navy/20 rounded-card px-4 py-2.5 cursor-pointer">
            <input
              type="checkbox"
              checked={verifiedOnly}
              onChange={(e) => setVerifiedOnly(e.target.checked)}
              className="accent-stamp"
            />
            Verified only
          </label>
        </div>

        <div className="mt-6 grid sm:grid-cols-2 gap-4">
          {loading && <p className="text-sm text-navy/50 col-span-2 py-10 text-center">Loading listings…</p>}

          {!loading && jobs.map((job) => {
            const applied = appliedIds.has(job.id)
            return (
              <article key={job.id} className="bg-white rounded-card border border-navy/10 p-5 flex flex-col gap-3">
                <div className="flex items-start justify-between">
                  <div>
                    <p className="font-display text-lg text-navy">{job.title}</p>
                    <p className="text-sm text-navy/60">{job.city}, {job.country}</p>
                  </div>
                  {job.verified ? (
                    <StatusBadge level="verified">Verified</StatusBadge>
                  ) : (
                    <StatusBadge level="alert">Unverified</StatusBadge>
                  )}
                </div>

                {job.description && (
                  <p className="text-sm text-navy/70 line-clamp-2">{job.description}</p>
                )}

                <div className="flex items-center justify-between text-sm">
                  <span className="font-mono text-navy/80">{job.salary}</span>
                  <span className="text-navy/45 text-xs">{job.agency}</span>
                </div>

                <div className="flex items-center justify-between pt-3 border-t border-dashed border-navy/15">
                  <p className="text-xs text-navy/50 font-mono">#{job.id}</p>
                  {me?.role === 'worker' && (
                    <button
                      onClick={() => setApplyTarget(job)}
                      disabled={applied}
                      className="text-sm font-medium text-navy underline underline-offset-2 disabled:no-underline disabled:text-navy/40"
                    >
                      {applied ? 'Applied' : 'Apply now'}
                    </button>
                  )}
                </div>
              </article>
            )
          })}

          {!loading && jobs.length === 0 && (
            <p className="text-sm text-navy/50 col-span-2 py-10 text-center">
              No listings match your search yet.
            </p>
          )}
        </div>

        {applyTarget && (
          <ApplyModal
            job={applyTarget}
            onClose={() => setApplyTarget(null)}
            onApplied={() => {
              setAppliedIds((prev) => new Set(prev).add(applyTarget.id))
              setApplyTarget(null)
            }}
          />
        )}
      </main>
    </div>
  )
}

function PostJobForm({ onCreated, onError }) {
  const [form, setForm] = useState({
    title: '', description: '', criteria: '', country: '', city: '', salary: '', agency: '',
  })
  const [submitting, setSubmitting] = useState(false)

  const handleSubmit = async (e) => {
    e.preventDefault()
    setSubmitting(true)
    try {
      await createJob(form)
      onCreated()
    } catch (err) {
      onError(err.response?.data?.message || 'Failed to post job.')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <form onSubmit={handleSubmit} className="mt-6 bg-white rounded-card border border-navy/10 p-5 grid sm:grid-cols-2 gap-4">
      <input required placeholder="Job title" value={form.title}
        onChange={(e) => setForm({ ...form, title: e.target.value })}
        className="sm:col-span-2 rounded-card border border-navy/20 px-3.5 py-2.5 text-sm outline-none focus:border-stamp" />
      <textarea required placeholder="Job description" value={form.description} rows={3}
        onChange={(e) => setForm({ ...form, description: e.target.value })}
        className="sm:col-span-2 rounded-card border border-navy/20 px-3.5 py-2.5 text-sm outline-none focus:border-stamp" />
      <textarea required placeholder="Criteria (skills, experience, documents required)" value={form.criteria} rows={2}
        onChange={(e) => setForm({ ...form, criteria: e.target.value })}
        className="sm:col-span-2 rounded-card border border-navy/20 px-3.5 py-2.5 text-sm outline-none focus:border-stamp" />
      <input required placeholder="Country" value={form.country}
        onChange={(e) => setForm({ ...form, country: e.target.value })}
        className="rounded-card border border-navy/20 px-3.5 py-2.5 text-sm outline-none focus:border-stamp" />
      <input required placeholder="City" value={form.city}
        onChange={(e) => setForm({ ...form, city: e.target.value })}
        className="rounded-card border border-navy/20 px-3.5 py-2.5 text-sm outline-none focus:border-stamp" />
      <input required placeholder="Salary, e.g. 1,800 SAR / month" value={form.salary}
        onChange={(e) => setForm({ ...form, salary: e.target.value })}
        className="rounded-card border border-navy/20 px-3.5 py-2.5 text-sm outline-none focus:border-stamp" />
      <input required placeholder="Agency name" value={form.agency}
        onChange={(e) => setForm({ ...form, agency: e.target.value })}
        className="rounded-card border border-navy/20 px-3.5 py-2.5 text-sm outline-none focus:border-stamp" />
      <button type="submit" disabled={submitting}
        className="sm:col-span-2 mt-1 rounded-card bg-navy text-paper text-sm font-medium py-2.5 hover:bg-navy-600 transition-colors disabled:opacity-50">
        {submitting ? 'Posting…' : 'Post job'}
      </button>
    </form>
  )
}

function ApplyModal({ job, onClose, onApplied }) {
  const [note, setNote] = useState('')
  const [file, setFile] = useState(null)
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState('')

  const handleSubmit = async (e) => {
    e.preventDefault()
    setSubmitting(true)
    setError('')
    try {
      const formData = new FormData()
      if (note) formData.append('note', note)
      if (file) formData.append('document', file)
      await applyToJob(job.id, formData)
      onApplied()
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to submit application.')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="fixed inset-0 bg-navy/40 flex items-center justify-center px-6 z-50">
      <div className="bg-white rounded-card max-w-md w-full p-6">
        <p className="font-display text-xl text-navy">Apply — {job.title}</p>
        <p className="text-sm text-navy/60 mt-1">{job.criteria}</p>

        {error && (
          <p className="mt-3 text-sm text-alert bg-alert/10 border border-alert/30 rounded-card px-3 py-2">
            {error}
          </p>
        )}

        <form onSubmit={handleSubmit} className="mt-4 flex flex-col gap-3">
          <textarea
            placeholder="Note to the agency (optional)"
            value={note}
            rows={3}
            onChange={(e) => setNote(e.target.value)}
            className="rounded-card border border-navy/20 px-3.5 py-2.5 text-sm outline-none focus:border-stamp"
          />
          <label className="flex flex-col gap-1.5">
            <span className="text-xs font-medium text-navy/70">Supporting document (PDF/JPG/PNG, optional)</span>
            <input
              type="file"
              accept=".pdf,.jpg,.jpeg,.png"
              onChange={(e) => setFile(e.target.files[0])}
              className="text-sm"
            />
          </label>

          <div className="flex items-center justify-end gap-3 mt-2">
            <button type="button" onClick={onClose} className="text-sm text-navy/60 underline underline-offset-2">
              Cancel
            </button>
            <button type="submit" disabled={submitting}
              className="rounded-card bg-navy text-paper text-sm font-medium px-5 py-2.5 hover:bg-navy-600 disabled:opacity-50">
              {submitting ? 'Submitting…' : 'Submit application'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}