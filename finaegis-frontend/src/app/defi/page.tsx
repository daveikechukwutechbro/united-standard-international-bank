"use client"

import { useState } from "react"
import { useQuery, useMutation } from "@apollo/client"
import { GET_DEFI_POSITIONS } from "@/lib/graphql/queries"
import { OPEN_DEFI_POSITION, CLOSE_DEFI_POSITION } from "@/lib/graphql/mutations"
import { AppShell } from "@/components/layout/AppShell"
import { TrendingUp } from "lucide-react"

const statusBadge: Record<string, string> = {
  active: "bg-green-500/10 text-green-400",
  closed: "bg-neutral-500/10 text-neutral-400",
  pending: "bg-yellow-500/10 text-yellow-400",
  liquidated: "bg-red-500/10 text-red-400",
}

function DeFiContent() {
  const { data, loading, refetch } = useQuery(GET_DEFI_POSITIONS, { variables: { first: 100 } })
  const [openPosition] = useMutation(OPEN_DEFI_POSITION)
  const [closePosition] = useMutation(CLOSE_DEFI_POSITION)

  const [showForm, setShowForm] = useState(false)
  const [protocol, setProtocol] = useState("")
  const [type, setType] = useState("")
  const [chain, setChain] = useState("")
  const [asset, setAsset] = useState("")
  const [amount, setAmount] = useState("")

  const positions = data?.defiPositions?.data || []

  const handleOpen = async (e: React.FormEvent) => {
    e.preventDefault()
    await openPosition({
      variables: {
        input: { protocol, type, chain, asset, amount: parseFloat(amount) },
      },
    })
    setProtocol("")
    setType("")
    setChain("")
    setAsset("")
    setAmount("")
    setShowForm(false)
    refetch()
  }

  const handleClose = async (id: string) => {
    await closePosition({ variables: { input: { id } } })
    refetch()
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold text-white mb-1">DeFi</h1>
          <p className="text-sm text-neutral-400 mb-6">Manage decentralized finance positions</p>
        </div>
        <button
          onClick={() => setShowForm(!showForm)}
          className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50"
        >
          Open Position
        </button>
      </div>

      {showForm && (
        <form onSubmit={handleOpen} className="rounded-xl border border-neutral-800 bg-neutral-900 p-5 space-y-4">
          <h2 className="text-sm font-medium text-white">New Position</h2>
          <div className="grid gap-4 sm:grid-cols-3">
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Protocol</label>
              <input
                type="text"
                value={protocol}
                onChange={(e) => setProtocol(e.target.value)}
                placeholder="e.g. aave"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Type</label>
              <input
                type="text"
                value={type}
                onChange={(e) => setType(e.target.value)}
                placeholder="e.g. supply"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Chain</label>
              <input
                type="text"
                value={chain}
                onChange={(e) => setChain(e.target.value)}
                placeholder="e.g. ethereum"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Asset</label>
              <input
                type="text"
                value={asset}
                onChange={(e) => setAsset(e.target.value)}
                placeholder="e.g. USDC"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Amount</label>
              <input
                type="number"
                value={amount}
                onChange={(e) => setAmount(e.target.value)}
                placeholder="0.00"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
          </div>
          <div className="flex gap-2">
            <button type="submit" className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50">
              Submit
            </button>
            <button type="button" onClick={() => setShowForm(false)} className="rounded-lg border border-neutral-700 px-4 py-2 text-sm font-medium text-neutral-300 hover:bg-neutral-800">
              Cancel
            </button>
          </div>
        </form>
      )}

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">DeFi Positions</h2>
        </div>
        {loading ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">Loading...</p>
        ) : positions.length === 0 ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">No positions</p>
        ) : (
          <div className="divide-y divide-neutral-800">
            {positions.map((pos: any) => (
              <div key={pos.id} className="px-5 py-4">
                <div className="flex items-center justify-between mb-3">
                  <div className="flex items-center gap-3">
                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600/10 text-blue-400">
                      <TrendingUp size={14} />
                    </div>
                    <div>
                      <p className="text-sm font-medium text-white">{pos.asset} &middot; {pos.amount}</p>
                      <p className="text-xs text-neutral-500">{pos.protocol} &middot; {pos.type} &middot; {pos.chain}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${statusBadge[pos.status] || "bg-neutral-500/10 text-neutral-400"}`}>
                      {pos.status}
                    </span>
                    {pos.status === "active" && (
                      <button
                        onClick={() => handleClose(pos.id)}
                        className="rounded-lg bg-red-600/20 px-3 py-1.5 text-xs font-medium text-red-400 hover:bg-red-600/30"
                      >
                        Close
                      </button>
                    )}
                  </div>
                </div>
                <div className="grid grid-cols-4 gap-4 text-xs">
                  <div>
                    <span className="text-neutral-500">Value USD</span>
                    <p className="text-white font-medium">${pos.value_usd?.toLocaleString()}</p>
                  </div>
                  <div>
                    <span className="text-neutral-500">APY</span>
                    <p className="text-green-400 font-medium">{pos.apy ? `${pos.apy}%` : "—"}</p>
                  </div>
                  <div>
                    <span className="text-neutral-500">Health Factor</span>
                    <p className="text-white font-medium">{pos.health_factor ?? "—"}</p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}

export default function DeFiPage() {
  return (
    <AppShell>
      <DeFiContent />
    </AppShell>
  )
}
