"use client"

import { useState } from "react"
import Link from "next/link"
import { auth } from "@/lib/api"

export default function PasswordResetPage() {
  const [email, setEmail] = useState("")
  const [sent, setSent] = useState(false)
  const [error, setError] = useState("")
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError("")
    setLoading(true)
    try {
      await auth.forgotPassword(email)
      setSent(true)
    } catch (err: any) {
      setError(err.message || "Failed to send reset email")
    } finally {
      setLoading(false)
    }
  }

  if (sent) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-neutral-950 p-4">
        <div className="w-full max-w-sm text-center">
          <h1 className="text-2xl font-semibold text-white mb-2">Check your email</h1>
          <p className="text-neutral-400 text-sm mb-6">If an account exists with that email, we&apos;ve sent password reset instructions.</p>
          <Link href="/login" className="text-blue-400 hover:text-blue-300 text-sm">Back to login</Link>
        </div>
      </div>
    )
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-neutral-950 p-4">
      <div className="w-full max-w-sm">
        <h1 className="text-2xl font-semibold text-white mb-2 text-center">Reset Password</h1>
        <p className="text-neutral-400 text-sm mb-6 text-center">Enter your email and we&apos;ll send you a reset link.</p>
        <form onSubmit={handleSubmit} className="space-y-4">
          {error && <div className="rounded-lg border border-red-500/20 bg-red-500/10 px-4 py-3 text-sm text-red-400">{error}</div>}
          <div>
            <label className="block text-sm font-medium text-neutral-300 mb-1.5">Email</label>
            <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} required
              className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" />
          </div>
          <button type="submit" disabled={loading}
            className="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50">
            {loading ? "Sending..." : "Send reset link"}
          </button>
          <p className="text-center text-sm text-neutral-400">
            <Link href="/login" className="text-blue-400 hover:text-blue-300">Back to login</Link>
          </p>
        </form>
      </div>
    </div>
  )
}
