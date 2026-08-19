import { useEffect, useState } from 'react'
import Sidebar from '../components/Sidebar.jsx'
import StatusBadge from '../components/StatusBadge.jsx'
import { fetchDocuments, createDocument, updateDocument, deleteDocument } from '../lib/api.js'

export default function Documents() {
  const [documents, setDocuments] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [name, setName] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const load = () => {
    setLoading(true)
    fetchDocuments()
      .then(setDocuments)
      .catch(() => setError('Failed to load documents.'))
      .finally(() => setLoading(false))
  }

  useEffect(load, [])

  const handleCreate = async (e) => {
    e.preventDefault()
    if (!name.trim()) return
    setSubmitting(true)
    try {
      await createDocument({ name })
      setName('')
      load()
    } catch {
      setError('Failed to add document.')
    } finally {
      setSubmitting(false)
    }
  }

  const handleToggle = async (doc) => {
    await updateDocument(doc.id, { status: doc.status === 'complete' ? 'missing' : 'complete' })
    load()
  }

  const handleDelete = async (id) => {
    await deleteDocument(id)
    load()
  }

  const missingCount = documents.filter((d) => d.status !== 'complete').length

  return (
    <div className="min-h-screen flex bg-paper">
      <Sidebar />
      <main className="flex-1 px-6 md:px-10 py-8 max-w-3xl">
        <header className="flex items-center justify-between">
          <div>
            <h1 className="font-display text-3xl text-navy">Documents</h1>
            <p className="text-sm text-navy/60 mt-1">Required paperwork for your recruitment case.</p>
          </div>
          {documents.length > 0 && (
            missingCount > 0
              ? <StatusBadge level="warning">{missingCount} missing</StatusBadge>
              : <StatusBadge level="verified">All complete</StatusBadge>
          )}
        </header>

        {error && (
          <p className="mt-4 text-sm text-alert bg-alert/10 border border-alert/30 rounded-card px-3.5 py-2.5">
            {error}
          </p>
        )}

        {/* CREATE */}
        <form onSubmit={handleCreate} className="mt-6 flex gap-3">
          <input
            value={name}
            onChange={(e) => setName(e.target.value)}
            placeholder="Document name, e.g. Police clearance"
            className="flex-1 rounded-card border border-navy/20 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-stamp"
          />
          <button
            type="submit"
            disabled={submitting}
            className="rounded-card bg-navy text-paper text-sm font-medium px-5 hover:bg-navy-600 transition-colors disabled:opacity-50"
          >
            Add
          </button>
        </form>

        {/* READ */}
        <div className="mt-6 bg-white rounded-card border border-navy/10 p-5">
          {loading && <p className="text-sm text-navy/50">Loading documents…</p>}
          {!loading && documents.length === 0 && (
            <p className="text-sm text-navy/50 py-6 text-center">No documents tracked yet — add one above.</p>
          )}
          <ul className="flex flex-col divide-y divide-navy/8">
            {documents.map((d) => (
              <li key={d.id} className="flex items-center justify-between py-3 first:pt-0 last:pb-0">
                <span className="text-sm text-navy/80">{d.name}</span>
                <div className="flex items-center gap-3">
                  {d.status === 'complete' ? (
                    <StatusBadge level="verified">Complete</StatusBadge>
                  ) : (
                    <StatusBadge level="alert">Missing</StatusBadge>
                  )}
                  <button onClick={() => handleToggle(d)} className="text-xs text-navy underline underline-offset-2">
                    Mark {d.status === 'complete' ? 'missing' : 'complete'}
                  </button>
                  <button onClick={() => handleDelete(d.id)} className="text-xs text-alert underline underline-offset-2">
                    Delete
                  </button>
                </div>
              </li>
            ))}
          </ul>
        </div>
      </main>
    </div>
  )
}