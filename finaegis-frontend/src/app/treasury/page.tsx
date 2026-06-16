"use client"

import { useState } from "react"
import { useQuery, useMutation } from "@apollo/client"
import { GET_PORTFOLIOS } from "@/lib/graphql/queries"
import { CREATE_PORTFOLIO, REBALANCE_PORTFOLIO } from "@/lib/graphql/mutations"
import { AppShell } from "@/components/layout/AppShell"
import { Plus, RotateCcw } from "lucide-react"

function TreasuryContent() {
  const { data, loading, refetch } = useQuery(GET_PORTFOLIOS, { variables: { first: 100 } })
  const [createPortfolio] = useMutation(CREATE_PORTFOLIO)
  const [rebalancePortfolio] = useMutation(REBALANCE_PORTFOLIO)

  const [assetClass, setAssetClass] = useState("")
  const [targetWeight, setTargetWeight] = useState("")
  const [targetAmount, setTargetAmount] = useState("")

  const portfolios = data?.portfolios?.data || []

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault()
    await createPortfolio({
      variables: {
        input: {
          asset_class: assetClass,
          target_weight: parseFloat(targetWeight),
          target_amount: parseFloat(targetAmount),
        },
      },
    })
    setAssetClass("")
    setTargetWeight("")
    setTargetAmount("")
    refetch()
  }

  const handleRebalance = async (id: string) => {
    await rebalancePortfolio({ variables: { input: { portfolio_id: id } } })
    refetch()
  }

  return (
    <div className="space-y-6">
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-white">Treasury Management</h1>
        <p className="text-sm text-neutral-400">Manage asset allocation portfolios</p>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Portfolios</h2>
        </div>
        {loading ? (
          <div className="px-5 py-8 text-center text-sm text-neutral-500">Loading...</div>
        ) : portfolios.length === 0 ? (
          <div className="px-5 py-8 text-center text-sm text-neutral-500">No portfolios found</div>
        ) : (
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 p-5">
            {portfolios.map((p: any) => (
              <div key={p.id} className="rounded-lg border border-neutral-800 bg-neutral-900 p-4 space-y-3">
                <div className="flex items-center justify-between">
                  <span className="text-sm font-medium text-white capitalize">{p.asset_class}</span>
                  <span className="text-xs text-neutral-500">{p.portfolio_id}</span>
                </div>
                <div className="space-y-1">
                  <div className="flex justify-between text-sm">
                    <span className="text-neutral-400">Target Weight</span>
                    <span className="text-white">{(p.target_weight * 100).toFixed(1)}%</span>
                  </div>
                  <div className="flex justify-between text-sm">
                    <span className="text-neutral-400">Current Weight</span>
                    <span className="text-white">{(p.current_weight * 100).toFixed(1)}%</span>
                  </div>
                  <div className="flex justify-between text-sm">
                    <span className="text-neutral-400">Drift</span>
                    <span className={`${p.drift > 0 ? "text-green-400" : p.drift < 0 ? "text-red-400" : "text-neutral-400"}`}>
                      {(p.drift * 100).toFixed(2)}%
                    </span>
                  </div>
                  <div className="flex justify-between text-sm">
                    <span className="text-neutral-400">Current Amount</span>
                    <span className="text-white">${p.current_amount?.toLocaleString()}</span>
                  </div>
                </div>
                <button
                  onClick={() => handleRebalance(p.id)}
                  className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50 w-full flex items-center justify-center gap-2"
                >
                  <RotateCcw size={14} />
                  Rebalance
                </button>
              </div>
            ))}
          </div>
        )}
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Create Portfolio</h2>
        </div>
        <div className="p-5">
          <form onSubmit={handleCreate} className="space-y-4">
            <div className="mb-4">
              <label className="block text-sm font-medium text-neutral-300 mb-1.5">Asset Class</label>
              <input
                type="text"
                value={assetClass}
                onChange={(e) => setAssetClass(e.target.value)}
                placeholder="e.g. equities, fixed_income"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div className="mb-4">
              <label className="block text-sm font-medium text-neutral-300 mb-1.5">Target Weight</label>
              <input
                type="number"
                step="0.01"
                value={targetWeight}
                onChange={(e) => setTargetWeight(e.target.value)}
                placeholder="0.00"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div className="mb-4">
              <label className="block text-sm font-medium text-neutral-300 mb-1.5">Target Amount</label>
              <input
                type="number"
                step="0.01"
                value={targetAmount}
                onChange={(e) => setTargetAmount(e.target.value)}
                placeholder="0.00"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <button
              type="submit"
              disabled={!assetClass || !targetWeight || !targetAmount}
              className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50 flex items-center gap-2"
            >
              <Plus size={16} />
              Create Portfolio
            </button>
          </form>
        </div>
      </div>
    </div>
  )
}

export default function TreasuryPage() {
  return (
    <AppShell>
      <TreasuryContent />
    </AppShell>
  )
}
