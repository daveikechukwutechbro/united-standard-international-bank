"use client"

import { useState } from "react"
import { useQuery, useMutation } from "@apollo/client"
import { GET_LOAN_APPLICATIONS } from "@/lib/graphql/queries"
import { APPLY_FOR_LOAN, APPROVE_LOAN } from "@/lib/graphql/mutations"
import { AppShell } from "@/components/layout/AppShell"
import { HandCoins } from "lucide-react"

const statusBadge: Record<string, string> = {
  approved: "bg-green-500/10 text-green-400",
  pending: "bg-yellow-500/10 text-yellow-400",
  rejected: "bg-red-500/10 text-red-400",
  completed: "bg-green-500/10 text-green-400",
  failed: "bg-red-500/10 text-red-400",
}

function LendingContent() {
  const { data, loading, refetch } = useQuery(GET_LOAN_APPLICATIONS, { variables: { first: 100 } })
  const [applyForLoan] = useMutation(APPLY_FOR_LOAN)
  const [approveLoan] = useMutation(APPROVE_LOAN)

  const [showForm, setShowForm] = useState(false)
  const [amount, setAmount] = useState("")
  const [term, setTerm] = useState("")
  const [purpose, setPurpose] = useState("")

  const [approvingId, setApprovingId] = useState<string | null>(null)
  const [approveAmount, setApproveAmount] = useState("")
  const [approveRate, setApproveRate] = useState("")

  const loans = data?.loanApplications?.data || []

  const handleApply = async (e: React.FormEvent) => {
    e.preventDefault()
    await applyForLoan({
      variables: {
        input: {
          requested_amount: parseFloat(amount),
          term_months: parseInt(term),
          purpose,
        },
      },
    })
    setAmount("")
    setTerm("")
    setPurpose("")
    setShowForm(false)
    refetch()
  }

  const handleApprove = async (loanId: string) => {
    await approveLoan({
      variables: {
        input: {
          id: loanId,
          approved_amount: parseFloat(approveAmount),
          interest_rate: parseFloat(approveRate),
        },
      },
    })
    setApprovingId(null)
    setApproveAmount("")
    setApproveRate("")
    refetch()
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold text-white mb-1">Lending</h1>
          <p className="text-sm text-neutral-400 mb-6">Manage loan applications and approvals</p>
        </div>
        <button
          onClick={() => setShowForm(!showForm)}
          className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50"
        >
          Apply for Loan
        </button>
      </div>

      {showForm && (
        <form onSubmit={handleApply} className="rounded-xl border border-neutral-800 bg-neutral-900 p-5 space-y-4">
          <h2 className="text-sm font-medium text-white">New Loan Application</h2>
          <div className="grid gap-4 sm:grid-cols-3">
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Requested Amount</label>
              <input
                type="number"
                value={amount}
                onChange={(e) => setAmount(e.target.value)}
                placeholder="0.00"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Term (months)</label>
              <input
                type="number"
                value={term}
                onChange={(e) => setTerm(e.target.value)}
                placeholder="12"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Purpose</label>
              <input
                type="text"
                value={purpose}
                onChange={(e) => setPurpose(e.target.value)}
                placeholder="Business expansion"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
          </div>
          <div className="flex gap-2">
            <button type="submit" className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50">
              Submit Application
            </button>
            <button type="button" onClick={() => setShowForm(false)} className="rounded-lg border border-neutral-700 px-4 py-2 text-sm font-medium text-neutral-300 hover:bg-neutral-800">
              Cancel
            </button>
          </div>
        </form>
      )}

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Loan Applications</h2>
        </div>
        {loading ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">Loading...</p>
        ) : loans.length === 0 ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">No loan applications</p>
        ) : (
          <div className="divide-y divide-neutral-800">
            {loans.map((loan: any) => (
              <div key={loan.id} className="px-5 py-4">
                <div className="flex items-center justify-between mb-3">
                  <div className="flex items-center gap-3">
                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600/10 text-blue-400">
                      <HandCoins size={14} />
                    </div>
                    <div>
                      <p className="text-sm font-medium text-white">
                        ${loan.requested_amount?.toLocaleString()}
                      </p>
                      <p className="text-xs text-neutral-500">{loan.term_months} months &middot; {loan.purpose}</p>
                    </div>
                  </div>
                  <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${statusBadge[loan.status] || "bg-neutral-500/10 text-neutral-400"}`}>
                    {loan.status}
                  </span>
                </div>
                <div className="grid grid-cols-4 gap-4 text-xs">
                  <div>
                    <span className="text-neutral-500">Credit Score</span>
                    <p className="text-white font-medium">{loan.credit_score ?? "—"}</p>
                  </div>
                  <div>
                    <span className="text-neutral-500">Risk Rating</span>
                    <p className="text-white font-medium capitalize">{loan.risk_rating ?? "—"}</p>
                  </div>
                  <div>
                    <span className="text-neutral-500">Interest Rate</span>
                    <p className="text-white font-medium">{loan.interest_rate ? `${loan.interest_rate}%` : "—"}</p>
                  </div>
                  <div>
                    <span className="text-neutral-500">Approved Amount</span>
                    <p className="text-white font-medium">{loan.approved_amount ? `$${loan.approved_amount.toLocaleString()}` : "—"}</p>
                  </div>
                </div>
                {loan.status === "pending" && (
                  <div className="mt-3">
                    {approvingId === loan.id ? (
                      <form
                        onSubmit={(e) => { e.preventDefault(); handleApprove(loan.id) }}
                        className="flex items-end gap-3"
                      >
                        <div>
                          <label className="block text-xs text-neutral-400 mb-1">Approved Amount</label>
                          <input
                            type="number"
                            value={approveAmount}
                            onChange={(e) => setApproveAmount(e.target.value)}
                            placeholder="0.00"
                            className="w-36 rounded-lg border border-neutral-700 bg-neutral-800 px-3 py-1.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                          />
                        </div>
                        <div>
                          <label className="block text-xs text-neutral-400 mb-1">Interest Rate %</label>
                          <input
                            type="number"
                            step="0.01"
                            value={approveRate}
                            onChange={(e) => setApproveRate(e.target.value)}
                            placeholder="5.5"
                            className="w-28 rounded-lg border border-neutral-700 bg-neutral-800 px-3 py-1.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                          />
                        </div>
                        <button type="submit" className="rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-500">
                          Confirm
                        </button>
                        <button type="button" onClick={() => setApprovingId(null)} className="text-xs text-neutral-400 hover:text-white">
                          Cancel
                        </button>
                      </form>
                    ) : (
                      <button
                        onClick={() => setApprovingId(loan.id)}
                        className="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-500"
                      >
                        Approve Loan
                      </button>
                    )}
                  </div>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}

export default function LendingPage() {
  return (
    <AppShell>
      <LendingContent />
    </AppShell>
  )
}
