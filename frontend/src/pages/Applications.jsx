import { useEffect, useState } from 'react'
import Sidebar from '../components/Sidebar.jsx'
import StatusBadge from '../components/StatusBadge.jsx'
import {
  fetchMe,
  fetchMyApplications,
  fetchAgencyApplications,
  updateApplicationStatus,
} from '../lib/api.js'

const statusLevel = {
  pending: 'warning',
  accepted: 'verified',
  rejected: 'alert',
}

export default function Applications() {
  const [me, setMe] = useState(null)
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    fetchMe().then(setMe).catch(() => {})
  }, [])

  const load = () => {
    if (!me) return

    setLoading(true)

    const fetcher =
      me.role === 'agency'
        ? fetchAgencyApplications
        : fetchMyApplications

    fetcher()
      .then(setItems)
      .catch(() => setError('Failed to load applications.'))
      .finally(() => setLoading(false))
  }

  useEffect(load, [me])

  const handleDecision = async (id, status) => {
    await updateApplicationStatus(id, status)
    load()
  }

  return (
    <div className="min-h-screen flex bg-paper">
      <Sidebar />

      <main className="flex-1 px-6 md:px-10 py-8 max-w-4xl">
        <header>
          <h1 className="font-display text-3xl text-navy">
            Applications
          </h1>

          <p className="text-sm text-navy/60 mt-1">
            {me?.role === 'agency'
              ? 'Review applicants to your job listings.'
              : "Track the status of jobs you've applied to."}
          </p>
        </header>

        {error && (
          <p className="mt-4 text-sm text-alert bg-alert/10 border border-alert/30 rounded-card px-3.5 py-2.5">
            {error}
          </p>
        )}

        <div className="mt-6 flex flex-col gap-3">
          {loading && (
            <p className="text-sm text-navy/50">
              Loading…
            </p>
          )}

          {!loading && items.length === 0 && (
            <p className="text-sm text-navy/50 py-10 text-center">
              Nothing here yet.
            </p>
          )}

          {/* Worker applications */}
          {!loading &&
            me?.role === 'worker' &&
            items.map((app) => (
              <div
                key={app.id}
                className="bg-white rounded-card border border-navy/10 p-5 flex items-center justify-between"
              >
                <div>
                  <p className="font-display text-lg text-navy">
                    {app.job?.title}
                  </p>

                  <p className="text-sm text-navy/60">
                    {app.job?.agency}
                  </p>
                </div>

                <StatusBadge level={statusLevel[app.status]}>
                  {app.status}
                </StatusBadge>
              </div>
            ))}

          {/* Agency applications */}
          {!loading &&
            me?.role === 'agency' &&
            items.map((app) => (
              <div
                key={app.id}
                className="bg-white rounded-card border border-navy/10 p-5 flex items-center justify-between"
              >
                <div>
                  <p className="font-display text-lg text-navy">
                    {app.applicant?.name}
                  </p>

                  <p className="text-sm text-navy/60">
                    Applied to {app.job?.title} · {app.applicant?.email}
                  </p>

                  {app.note && (
                    <p className="text-xs text-navy/50 mt-1">
                      "{app.note}"
                    </p>
                  )}

                  {app.document_path && (
                    <a
                      href={`http://localhost:8000/storage/${app.document_path}`}
                      target="_blank"
                      rel="noreferrer"
                      className="text-xs text-navy underline underline-offset-2 mt-1 inline-block"
                    >
                      View document
                    </a>
                  )}
                </div>

                <div className="flex items-center gap-3">
                  <StatusBadge level={statusLevel[app.status]}>
                    {app.status}
                  </StatusBadge>

                  {app.status === 'pending' && (
                    <>
                      <button
                        onClick={() =>
                          handleDecision(app.id, 'accepted')
                        }
                        className="text-xs text-verified underline underline-offset-2"
                      >
                        Accept
                      </button>

                      <button
                        onClick={() =>
                          handleDecision(app.id, 'rejected')
                        }
                        className="text-xs text-alert underline underline-offset-2"
                      >
                        Reject
                      </button>
                    </>
                  )}
                </div>
              </div>
            ))}
        </div>
      </main>
    </div>
  )
}