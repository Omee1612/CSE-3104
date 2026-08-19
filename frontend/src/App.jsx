import { Routes, Route } from 'react-router-dom'
import Landing from './pages/Landing.jsx'
import Login from './pages/Login.jsx'
import Register from './pages/Register.jsx'
import WorkerDashboard from './pages/WorkerDashboard.jsx'
import JobSearch from './pages/JobSearch.jsx'
import Contracts from './pages/Contracts.jsx'
import Documents from './pages/Documents.jsx'
import Applications from './pages/Applications.jsx'

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<Landing />} />
      <Route path="/login" element={<Login />} />
      <Route path="/register" element={<Register />} />
      <Route path="/dashboard" element={<WorkerDashboard />} />
      <Route path="/jobs" element={<JobSearch />} />
      <Route path="/payments" element={<WorkerDashboard />} />
      <Route path="/complaints" element={<WorkerDashboard />} />
      <Route path="*" element={<Landing />} />
      <Route path="/contracts" element={<Contracts />} />
      <Route path="/documents" element={<Documents />} />
      <Route path="/applications" element={<Applications />} />
    </Routes>
  )
}