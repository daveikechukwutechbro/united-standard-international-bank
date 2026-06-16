"use client"

import { useState } from "react"
import { useQuery, useMutation } from "@apollo/client"
import { GET_BRIDGE_TRANSACTIONS } from "@/lib/graphql/queries"
import { INITIATE_BRIDGE_TRANSFER } from "@/lib/graphql/mutations"
import { AppShell } from "@/components/layout/AppShell"
import { ArrowRightLeft } from "lucide-react"

const statusBadge: Record<string, string> = {
  completed: "bg-green-500/10 text-green-400",
  pending: "bg-yellow-500/10 text-yellow-400",
  failed: "bg-red-500/10 text-red-400",
  processing: "bg-blue-500/10 text-blue-400",
}

function CrossChainContent() {
  const { data, loading, refetch } = useQuery(GET_BRIDGE_TRANSACTIONS, { variables: { first: 100 } })
  const [initiateBridgeTransfer] = useMutation(INITIATE_BRIDGE_TRANSFER)

  const [showForm, setShowForm] = useState(false)
  const [sourceChain, setSourceChain] = useState("")
  const [destChain, setDestChain] = useState("")
  const [token, setToken] = useState("")
  const [amount, setAmount] = useState("")
  const [recipientAddress, setRecipientAddress] = useState("")
  const [provider, setProvider] = useState("")

  const txs = data?.bridgeTransactions?.data || []

  const handleInitiate = async (e: React.FormEvent) => {
    e.preventDefault()
    await initiateBridgeTransfer({
      variables: {
        input: {
          source_chain: sourceChain,
          dest_chain: destChain,
          token,
          amount: parseFloat(amount),
          recipient_address: recipientAddress,
          provider,
        },
      },
    })
    setSourceChain("")
    setDestChain("")
    setToken("")
    setAmount("")
    setRecipientAddress("")
    setProvider("")
    setShowForm(false)
    refetch()
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold text-white mb-1">Cross-Chain Bridge</h1>
          <p className="text-sm text-neutral-400 mb-6">Bridge assets across supported blockchains</p>
        </div>
        <button
          onClick={() => setShowForm(!showForm)}
          className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50"
        >
          Initiate Bridge Transfer
        </button>
      </div>

      {showForm && (
        <form onSubmit={handleInitiate} className="rounded-xl border border-neutral-800 bg-neutral-900 p-5 space-y-4">
          <h2 className="text-sm font-medium text-white">New Bridge Transfer</h2>
          <div className="grid gap-4 sm:grid-cols-3">
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Source Chain</label>
              <input
                type="text"
                value={sourceChain}
                onChange={(e) => setSourceChain(e.target.value)}
                placeholder="e.g. ethereum"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Destination Chain</label>
              <input
                type="text"
                value={destChain}
                onChange={(e) => setDestChain(e.target.value)}
                placeholder="e.g. polygon"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Token</label>
              <input
                type="text"
                value={token}
                onChange={(e) => setToken(e.target.value)}
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
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Recipient Address</label>
              <input
                type="text"
                value={recipientAddress}
                onChange={(e) => setRecipientAddress(e.target.value)}
                placeholder="0x..."
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Provider</label>
              <input
                type="text"
                value={provider}
                onChange={(e) => setProvider(e.target.value)}
                placeholder="e.g. layerzero"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
          </div>
          <div className="flex gap-2">
            <button type="submit" className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50">
              Submit Transfer
            </button>
            <button type="button" onClick={() => setShowForm(false)} className="rounded-lg border border-neutral-700 px-4 py-2 text-sm font-medium text-neutral-300 hover:bg-neutral-800">
              Cancel
            </button>
          </div>
        </form>
      )}

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Bridge Transactions</h2>
        </div>
        {loading ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">Loading...</p>
        ) : txs.length === 0 ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">No bridge transactions</p>
        ) : (
          <div className="divide-y divide-neutral-800">
            {txs.map((tx: any) => (
              <div key={tx.id} className="px-5 py-4">
                <div className="flex items-center justify-between mb-3">
                  <div className="flex items-center gap-3">
                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600/10 text-blue-400">
                      <ArrowRightLeft size={14} />
                    </div>
                    <div>
                      <p className="text-sm font-medium text-white">{tx.token} &middot; {tx.amount}</p>
                      <p className="text-xs text-neutral-500">{tx.source_chain} → {tx.dest_chain}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    <span className="text-xs text-neutral-500">{tx.provider}</span>
                    <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${statusBadge[tx.status] || "bg-neutral-500/10 text-neutral-400"}`}>
                      {tx.status}
                    </span>
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-4 text-xs">
                  <div>
                    <span className="text-neutral-500">Source Tx</span>
                    <p className="text-white font-medium font-mono">{tx.source_tx_hash ? `${tx.source_tx_hash.slice(0, 10)}...` : "—"}</p>
                  </div>
                  <div>
                    <span className="text-neutral-500">Destination Tx</span>
                    <p className="text-white font-medium font-mono">{tx.dest_tx_hash ? `${tx.dest_tx_hash.slice(0, 10)}...` : "—"}</p>
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

export default function CrossChainPage() {
  return (
    <AppShell>
      <CrossChainContent />
    </AppShell>
  )
}
