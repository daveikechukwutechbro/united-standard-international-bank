"use client"

import { useState } from "react"
import { useQuery, useMutation } from "@apollo/client"
import { GET_INVESTMENTS } from "@/lib/graphql/queries"
import { CREATE_INVESTMENT } from "@/lib/graphql/mutations"
import { AppShell } from "@/components/layout/AppShell"
import { Plus } from "lucide-react"

const statusBadge: Record<string, string> = {
  active: "bg-green-500/10 text-green-400",
  pending: "bg-yellow-500/10 text-yellow-400",
  matured: "bg-blue-500/10 text-blue-400",
  closed: "bg-neutral-500/10 text-neutral-400",
}

function CgoContent() {
  const { data, loading, refetch } = useQuery(GET_INVESTMENTS, { variables: { first: 100 } })
  const [createInvestment] = useMutation(CREATE_INVESTMENT)

  const [showForm, setShowForm] = useState(false)
  const [amount, setAmount] = useState("")
  const [currency, setCurrency] = useState("")
  const [paymentMethod, setPaymentMethod] = useState("")

  const investments = data?.investments?.data || []

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault()
    await createInvestment({
      variables: {
        input: {
          amount: parseFloat(amount),
          currency,
          payment_method: paymentMethod,
        },
      },
    })
    setAmount("")
    setCurrency("")
    setPaymentMethod("")
    setShowForm(false)
    refetch()
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-white mb-1">Capital Growth Opportunities</h1>
        <p className="text-sm text-neutral-400 mb-6">Manage investments and CGO portfolios</p>
      </div>

      <div className="flex justify-end">
        <button
          onClick={() => setShowForm(!showForm)}
          className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50 flex items-center gap-2"
        >
          <Plus size={16} />
          Create Investment
        </button>
      </div>

      {showForm && (
        <form onSubmit={handleCreate} className="rounded-xl border border-neutral-800 bg-neutral-900 p-5 space-y-4">
          <h2 className="text-sm font-medium text-white">New Investment</h2>
          <div className="grid gap-4 sm:grid-cols-3">
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Amount</label>
              <input
                type="number"
                step="any"
                value={amount}
                onChange={(e) => setAmount(e.target.value)}
                placeholder="1000.00"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Currency</label>
              <input
                type="text"
                value={currency}
                onChange={(e) => setCurrency(e.target.value)}
                placeholder="USD"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Payment Method</label>
              <input
                type="text"
                value={paymentMethod}
                onChange={(e) => setPaymentMethod(e.target.value)}
                placeholder="bank_transfer"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
          </div>
          <div className="flex gap-2">
            <button type="submit" disabled={!amount || !currency} className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50">
              Create
            </button>
            <button type="button" onClick={() => setShowForm(false)} className="rounded-lg border border-neutral-700 px-4 py-2 text-sm font-medium text-neutral-300 hover:bg-neutral-800">
              Cancel
            </button>
          </div>
        </form>
      )}

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Investments</h2>
        </div>
        {loading ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">Loading...</p>
        ) : investments.length === 0 ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">No investments</p>
        ) : (
          <div className="divide-y divide-neutral-800">
            {investments.map((inv: any) => (
              <div key={inv.id} className="flex items-center justify-between px-5 py-3">
                <div className="flex items-center gap-3">
                  <div>
                    <p className="text-sm text-white font-medium">{inv.amount?.toLocaleString()}</p>
                    <p className="text-xs text-neutral-500">
                      {inv.tier} &middot; @ {inv.share_price}
                    </p>
                  </div>
                </div>
                <div className="flex items-center gap-4">
                  <div className="text-right">
                    <p className="text-sm text-neutral-300">{inv.shares_purchased ?? "—"} shares</p>
                  </div>
                  <span
                    className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${
                      statusBadge[inv.status] || "bg-neutral-500/10 text-neutral-400"
                    }`}
                  >
                    {inv.status}
                  </span>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}

export default function CgoPage() {
  return (
    <AppShell>
      <CgoContent />
    </AppShell>
  )
}
