import { useEffect, useState } from 'react'
import Sidebar from '../components/Sidebar.jsx'
import { fetchInsights } from '../lib/api.js'

const sections = [
  { key: 'applications_with_details', label: 'JOIN — Applications with job & applicant details' },
  { key: 'jobs_with_application_count', label: 'LEFT JOIN — Every job, including ones with no applicants' },
  { key: 'agencies_and_their_jobs', label: 'RIGHT JOIN — Every agency, including ones with no postings' },
  { key: 'application_status_breakdown', label: 'GROUP BY + AGGREGATE — Applications by status' },
  { key: 'jobs_per_country', label: 'GROUP BY + AGGREGATE — Jobs posted per country' },
  { key: 'above_average_applicants', label: 'SUBQUERY — Applicants above the average application count' },
]

export default function Insights() {
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    fetchInsights().then(setData).finally(() => setLoading(false))
  }, [])

  if (loading) return <div className="min-h-screen flex bg-paper"><Sidebar /><main className="flex-1 p-8 text-navy/50 text-sm">Loading…</main></div>

  return (
    <div className="min-h-screen flex bg-paper">
      <Sidebar />
      <main className="flex-1 px-6 md:px-10 py-8 max-w-5xl">
        <h1 className="font-display text-3xl text-navy">Insights</h1>
        <p className="text-sm text-navy/60 mt-1">Database query demonstrations.</p>

        {sections.map((s) => (
          <section key={s.key} className="mt-8 bg-white rounded-card border border-navy/10 p-5">
            <p className="font-display text-lg text-navy mb-3">{s.label}</p>
            <pre className="text-xs font-mono text-navy/70 overflow-x-auto bg-paper rounded-card p-3">
              {JSON.stringify(data[s.key], null, 2)}
            </pre>
          </section>
        ))}
      </main>
    </div>
  )
}