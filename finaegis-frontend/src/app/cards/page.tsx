"use client"

import { useState } from "react"
import { useQuery, useMutation } from "@apollo/client"
import { GET_CARDS } from "@/lib/graphql/queries"
import { PROVISION_CARD, CREATE_CARD, FREEZE_CARD } from "@/lib/graphql/mutations"
import { AppShell } from "@/components/layout/AppShell"
import { Plus, Snowflake, Flame } from "lucide-react"

function CardsContent() {
  const { data, loading, refetch } = useQuery(GET_CARDS, { variables: { first: 100 } })
  const [provisionCard] = useMutation(PROVISION_CARD)
  const [createCard] = useMutation(CREATE_CARD)
  const [freezeCard] = useMutation(FREEZE_CARD)

  const [provisionName, setProvisionName] = useState("")
  const [holderId, setHolderId] = useState("")
  const [network, setNetwork] = useState("")
  const [label, setLabel] = useState("")
  const [currency, setCurrency] = useState("")

  const cards = data?.cards || []

  const handleProvision = async (e: React.FormEvent) => {
    e.preventDefault()
    await provisionCard({
      variables: { input: { cardholder_name: provisionName } },
    })
    setProvisionName("")
    refetch()
  }

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault()
    await createCard({
      variables: {
        input: {
          cardholder_id: holderId,
          network,
          label,
          currency,
        },
      },
    })
    setHolderId("")
    setNetwork("")
    setLabel("")
    setCurrency("")
    refetch()
  }

  const handleFreezeToggle = async (card: any) => {
    await freezeCard({ variables: { id: card.id } })
    refetch()
  }

  return (
    <div className="space-y-6">
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-white">Card Management</h1>
        <p className="text-sm text-neutral-400">Provision and manage virtual cards</p>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Virtual Cards</h2>
        </div>
        {loading ? (
          <div className="px-5 py-8 text-center text-sm text-neutral-500">Loading...</div>
        ) : cards.length === 0 ? (
          <div className="px-5 py-8 text-center text-sm text-neutral-500">No cards found</div>
        ) : (
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 p-5">
            {cards.map((c: any) => (
              <div key={c.id} className="rounded-lg border border-neutral-800 bg-neutral-900 p-4 space-y-3">
                <div className="flex items-center justify-between">
                  <span className="text-sm font-medium text-white">{c.label || "Unlabeled"}</span>
                  <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                    c.status === "active" ? "bg-green-500/10 text-green-400" :
                    c.status === "frozen" ? "bg-blue-500/10 text-blue-400" :
                    "bg-neutral-700 text-neutral-400"
                  }`}>
                    {c.status}
                  </span>
                </div>
                <div className="space-y-1">
                  <div className="flex justify-between text-sm">
                    <span className="text-neutral-400">Token</span>
                    <span className="text-white font-mono text-xs">{c.card_token?.slice(0, 12)}...</span>
                  </div>
                  <div className="flex justify-between text-sm">
                    <span className="text-neutral-400">Last Four</span>
                    <span className="text-white font-mono">****{c.last_four}</span>
                  </div>
                  <div className="flex justify-between text-sm">
                    <span className="text-neutral-400">Network</span>
                    <span className="text-white capitalize">{c.network}</span>
                  </div>
                  <div className="flex justify-between text-sm">
                    <span className="text-neutral-400">Cardholder</span>
                    <span className="text-white">{c.cardholder_name}</span>
                  </div>
                </div>
                <button
                  onClick={() => handleFreezeToggle(c)}
                  className={`rounded-lg px-4 py-2 text-sm font-medium w-full flex items-center justify-center gap-2 ${
                    c.status === "frozen"
                      ? "bg-green-600 text-white hover:bg-green-500"
                      : "bg-blue-600 text-white hover:bg-blue-500"
                  }`}
                >
                  {c.status === "frozen" ? (
                    <><Flame size={14} /> Unfreeze</>
                  ) : (
                    <><Snowflake size={14} /> Freeze</>
                  )}
                </button>
              </div>
            ))}
          </div>
        )}
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <div className="rounded-xl border border-neutral-800 bg-neutral-900">
          <div className="border-b border-neutral-800 px-5 py-4">
            <h2 className="text-sm font-medium text-white">Provision Card</h2>
          </div>
          <div className="p-5">
            <form onSubmit={handleProvision} className="space-y-4">
              <div className="mb-4">
                <label className="block text-sm font-medium text-neutral-300 mb-1.5">Cardholder Name</label>
                <input
                  type="text"
                  value={provisionName}
                  onChange={(e) => setProvisionName(e.target.value)}
                  placeholder="Full name"
                  className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
              </div>
              <button
                type="submit"
                disabled={!provisionName}
                className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50 flex items-center gap-2"
              >
                <Plus size={16} />
                Provision Card
              </button>
            </form>
          </div>
        </div>

        <div className="rounded-xl border border-neutral-800 bg-neutral-900">
          <div className="border-b border-neutral-800 px-5 py-4">
            <h2 className="text-sm font-medium text-white">Create Card</h2>
          </div>
          <div className="p-5">
            <form onSubmit={handleCreate} className="space-y-4">
              <div className="mb-4">
                <label className="block text-sm font-medium text-neutral-300 mb-1.5">Cardholder ID</label>
                <input
                  type="text"
                  value={holderId}
                  onChange={(e) => setHolderId(e.target.value)}
                  placeholder="Cardholder ID"
                  className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
              </div>
              <div className="mb-4">
                <label className="block text-sm font-medium text-neutral-300 mb-1.5">Network</label>
                <input
                  type="text"
                  value={network}
                  onChange={(e) => setNetwork(e.target.value)}
                  placeholder="e.g. visa, mastercard"
                  className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
              </div>
              <div className="mb-4">
                <label className="block text-sm font-medium text-neutral-300 mb-1.5">Label</label>
                <input
                  type="text"
                  value={label}
                  onChange={(e) => setLabel(e.target.value)}
                  placeholder="Card label"
                  className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
              </div>
              <div className="mb-4">
                <label className="block text-sm font-medium text-neutral-300 mb-1.5">Currency</label>
                <input
                  type="text"
                  value={currency}
                  onChange={(e) => setCurrency(e.target.value)}
                  placeholder="e.g. USD, EUR"
                  className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
              </div>
              <button
                type="submit"
                disabled={!holderId || !network || !currency}
                className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50 flex items-center gap-2"
              >
                <Plus size={16} />
                Create Card
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  )
}

export default function CardsPage() {
  return (
    <AppShell>
      <CardsContent />
    </AppShell>
  )
}
