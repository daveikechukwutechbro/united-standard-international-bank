"use client"

import { useState } from "react"
import { useQuery, useMutation } from "@apollo/client"
import { GET_STABLECOIN_RESERVES } from "@/lib/graphql/queries"
import { MINT_STABLECOIN, REDEEM_STABLECOIN } from "@/lib/graphql/mutations"
import { AppShell } from "@/components/layout/AppShell"
import { Coins, ArrowDownToLine, ArrowUpFromLine } from "lucide-react"

const statusBadge: Record<string, string> = {
  active: "bg-green-500/10 text-green-400",
  pending: "bg-yellow-500/10 text-yellow-400",
  completed: "bg-green-500/10 text-green-400",
  failed: "bg-red-500/10 text-red-400",
  frozen: "bg-red-500/10 text-red-400",
}

function StablecoinContent() {
  const { data, loading, refetch } = useQuery(GET_STABLECOIN_RESERVES, { variables: { first: 100 } })
  const [mintStablecoin] = useMutation(MINT_STABLECOIN)
  const [redeemStablecoin] = useMutation(REDEEM_STABLECOIN)

  const [showMint, setShowMint] = useState(false)
  const [showRedeem, setShowRedeem] = useState(false)
  const [mintCode, setMintCode] = useState("")
  const [mintAmount, setMintAmount] = useState("")
  const [poolId, setPoolId] = useState("")
  const [redeemReserveId, setRedeemReserveId] = useState("")
  const [redeemAmount, setRedeemAmount] = useState("")

  const reserves = data?.stablecoinReserves?.data || []

  const handleMint = async (e: React.FormEvent) => {
    e.preventDefault()
    await mintStablecoin({
      variables: {
        input: {
          stablecoin_code: mintCode,
          amount: parseFloat(mintAmount),
          pool_id: poolId,
        },
      },
    })
    setMintCode("")
    setMintAmount("")
    setPoolId("")
    setShowMint(false)
    refetch()
  }

  const handleRedeem = async (e: React.FormEvent) => {
    e.preventDefault()
    await redeemStablecoin({
      variables: {
        input: {
          reserve_id: redeemReserveId,
          amount: parseFloat(redeemAmount),
        },
      },
    })
    setRedeemReserveId("")
    setRedeemAmount("")
    setShowRedeem(false)
    refetch()
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-white mb-1">Stablecoin</h1>
        <p className="text-sm text-neutral-400 mb-6">Reserve management, minting, and redemption</p>
      </div>

      <div className="flex gap-2">
        <button
          onClick={() => { setShowMint(!showMint); setShowRedeem(false) }}
          className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50 flex items-center gap-2"
        >
          <ArrowDownToLine size={14} />
          Mint
        </button>
        <button
          onClick={() => { setShowRedeem(!showRedeem); setShowMint(false) }}
          className="rounded-lg border border-neutral-700 px-4 py-2 text-sm font-medium text-neutral-300 hover:bg-neutral-800 flex items-center gap-2"
        >
          <ArrowUpFromLine size={14} />
          Redeem
        </button>
      </div>

      {showMint && (
        <form onSubmit={handleMint} className="rounded-xl border border-neutral-800 bg-neutral-900 p-5 space-y-4">
          <h2 className="text-sm font-medium text-white">Mint Stablecoin</h2>
          <div className="grid gap-4 sm:grid-cols-3">
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Stablecoin Code</label>
              <input
                type="text"
                value={mintCode}
                onChange={(e) => setMintCode(e.target.value)}
                placeholder="USDC"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Amount</label>
              <input
                type="number"
                value={mintAmount}
                onChange={(e) => setMintAmount(e.target.value)}
                placeholder="10000"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Pool ID</label>
              <input
                type="text"
                value={poolId}
                onChange={(e) => setPoolId(e.target.value)}
                placeholder="pool_001"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
          </div>
          <div className="flex gap-2">
            <button type="submit" className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50">
              Mint
            </button>
            <button type="button" onClick={() => setShowMint(false)} className="rounded-lg border border-neutral-700 px-4 py-2 text-sm font-medium text-neutral-300 hover:bg-neutral-800">
              Cancel
            </button>
          </div>
        </form>
      )}

      {showRedeem && (
        <form onSubmit={handleRedeem} className="rounded-xl border border-neutral-800 bg-neutral-900 p-5 space-y-4">
          <h2 className="text-sm font-medium text-white">Redeem Stablecoin</h2>
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Reserve ID</label>
              <input
                type="text"
                value={redeemReserveId}
                onChange={(e) => setRedeemReserveId(e.target.value)}
                placeholder="reserve_001"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Amount</label>
              <input
                type="number"
                value={redeemAmount}
                onChange={(e) => setRedeemAmount(e.target.value)}
                placeholder="5000"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
          </div>
          <div className="flex gap-2">
            <button type="submit" className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50">
              Redeem
            </button>
            <button type="button" onClick={() => setShowRedeem(false)} className="rounded-lg border border-neutral-700 px-4 py-2 text-sm font-medium text-neutral-300 hover:bg-neutral-800">
              Cancel
            </button>
          </div>
        </form>
      )}

      {loading ? (
        <p className="text-center text-sm text-neutral-500 py-8">Loading reserves...</p>
      ) : reserves.length === 0 ? (
        <p className="text-center text-sm text-neutral-500 py-8">No reserves found</p>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {reserves.map((r: any) => (
            <div key={r.id} className="rounded-xl border border-neutral-800 bg-neutral-900 p-5">
              <div className="flex items-center justify-between mb-4">
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600/10 text-blue-400">
                    <Coins size={18} />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-white">{r.stablecoin_code}</p>
                    <p className="text-xs text-neutral-500">{r.asset_code}</p>
                  </div>
                </div>
                <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${statusBadge[r.status] || "bg-neutral-500/10 text-neutral-400"}`}>
                  {r.status}
                </span>
              </div>
              <div className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-neutral-400">Value (USD)</span>
                  <span className="text-white font-medium">${r.value_usd?.toLocaleString()}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-neutral-400">Allocation</span>
                  <span className="text-white font-medium">{r.allocation_percentage}%</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-neutral-400">Custodian</span>
                  <span className="text-white font-medium">{r.custodian_name}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-neutral-400">Reserve ID</span>
                  <span className="text-xs text-neutral-500">{r.reserve_id}</span>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}

export default function StablecoinPage() {
  return (
    <AppShell>
      <StablecoinContent />
    </AppShell>
  )
}
