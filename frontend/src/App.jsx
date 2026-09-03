import { useEffect, useState } from 'react'
import { Routes, Route, Navigate } from 'react-router-dom'
import { fetchMe } from './lib/api.js'
import Landing from './pages/Landing.jsx'
import Login from './pages/Login.jsx'
import Register from './pages/Register.jsx'
import Verify from './pages/Verify.jsx'
import WorkerDashboard from './pages/WorkerDashboard.jsx'
import JobSearch from './pages/JobSearch.jsx'
import Documents from './pages/Documents.jsx'
import Applications from './pages/Applications.jsx'
import Insights from './pages/Insights.jsx'

function RequireVerified({ children }) {
  const [me, setMe] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    fetchMe().then(setMe).catch(() => setMe(null)).finally(() => setLoading(false))
  }, [])

  if (loading) return null
  if (me?.role === 'worker' && me?.verification_status !== 'verified') {
    return <Navigate to="/verify" replace />
  }
  return children
}

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<Landing />} />
      <Route path="/login" element={<Login />} />
      <Route path="/register" element={<Register />} />
      <Route path="/verify" element={<Verify />} />
      <Route path="/dashboard" element={<RequireVerified><WorkerDashboard /></RequireVerified>} />
      <Route path="/jobs" element={<RequireVerified><JobSearch /></RequireVerified>} />
      <Route path="/documents" element={<RequireVerified><Documents /></RequireVerified>} />
      <Route path="/applications" element={<RequireVerified><Applications /></RequireVerified>} />
      <Route path="/payments" element={<RequireVerified><WorkerDashboard /></RequireVerified>} />
      <Route path="/complaints" element={<RequireVerified><WorkerDashboard /></RequireVerified>} />     
      <Route path="/insights" element={<RequireVerified><Insights /></RequireVerified>} />
      <Route path="*" element={<Landing />} />
    </Routes>
  )
}