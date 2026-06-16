"use client"

import { useState } from "react"
import { useQuery, useMutation } from "@apollo/client"
import { GET_BASKETS } from "@/lib/graphql/queries"
import { CREATE_BASKET, REBALANCE_BASKET } from "@/lib/graphql/mutations"
import { AppShell } from "@/components/layout/AppShell"
import { Plus, RotateCcw } from "lucide-react"

function BasketContent() {
  const { data, loading, refetch } = useQuery(GET_BASKETS, { variables: { first: 100 } })
  const [createBasket] = useMutation(CREATE_BASKET)
  const [rebalanceBasket] = useMutation(REBALANCE_BASKET)

  const [name, setName] = useState("")
  const [description, setDescription] = useState("")
  const [type, setType] = useState("")
  const [rebalanceFrequency, setRebalanceFrequency] = useState("")

  const baskets = data?.baskets?.data || []

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault()
    await createBasket({
      variables: {
        input: {
          name,
          description,
          type,
          rebalance_frequency: rebalanceFrequency,
        },
      },
    })
    setName("")
    setDescription("")
    setType("")
    setRebalanceFrequency("")
    refetch()
  }

  const handleRebalance = async (id: string) => {
    await rebalanceBasket({ variables: { input: { basket_id: id } } })
    refetch()
  }

  return (
    <div className="space-y-6">
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-white">Basket Management</h1>
        <p className="text-sm text-neutral-400">Manage GCU and currency baskets</p>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Baskets</h2>
        </div>
        {loading ? (
          <div className="px-5 py-8 text-center text-sm text-neutral-500">Loading...</div>
        ) : baskets.length === 0 ? (
          <div className="px-5 py-8 text-center text-sm text-neutral-500">No baskets found</div>
        ) : (
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 p-5">
            {baskets.map((b: any) => (
              <div key={b.id} className="rounded-lg border border-neutral-800 bg-neutral-900 p-4 space-y-3">
                <div className="flex items-center justify-between">
                  <span className="text-sm font-medium text-white">{b.name}</span>
                  <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${b.is_active ? "bg-green-500/10 text-green-400" : "bg-neutral-700 text-neutral-400"}`}>
                    {b.is_active ? "Active" : "Inactive"}
                  </span>
                </div>
                <p className="text-xs text-neutral-500">{b.description || "No description"}</p>
                <div className="space-y-1">
                  <div className="flex justify-between text-sm">
                    <span className="text-neutral-400">Code</span>
                    <span className="text-white">{b.code}</span>
                  </div>
                  <div className="flex justify-between text-sm">
                    <span className="text-neutral-400">Type</span>
                    <span className="text-white capitalize">{b.type}</span>
                  </div>
                  <div className="flex justify-between text-sm">
                    <span className="text-neutral-400">Rebalance</span>
                    <span className="text-white capitalize">{b.rebalance_frequency?.replace("_", " ")}</span>
                  </div>
                </div>
                <button
                  onClick={() => handleRebalance(b.id)}
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
          <h2 className="text-sm font-medium text-white">Create Basket</h2>
        </div>
        <div className="p-5">
          <form onSubmit={handleCreate} className="space-y-4">
            <div className="mb-4">
              <label className="block text-sm font-medium text-neutral-300 mb-1.5">Name</label>
              <input
                type="text"
                value={name}
                onChange={(e) => setName(e.target.value)}
                placeholder="Basket name"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div className="mb-4">
              <label className="block text-sm font-medium text-neutral-300 mb-1.5">Description</label>
              <input
                type="text"
                value={description}
                onChange={(e) => setDescription(e.target.value)}
                placeholder="Optional description"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div className="mb-4">
              <label className="block text-sm font-medium text-neutral-300 mb-1.5">Type</label>
              <input
                type="text"
                value={type}
                onChange={(e) => setType(e.target.value)}
                placeholder="e.g. gcu, currency"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div className="mb-4">
              <label className="block text-sm font-medium text-neutral-300 mb-1.5">Rebalance Frequency</label>
              <input
                type="text"
                value={rebalanceFrequency}
                onChange={(e) => setRebalanceFrequency(e.target.value)}
                placeholder="e.g. monthly, quarterly"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <button
              type="submit"
              disabled={!name || !type}
              className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50 flex items-center gap-2"
            >
              <Plus size={16} />
              Create Basket
            </button>
          </form>
        </div>
      </div>
    </div>
  )
}

export default function BasketPage() {
  return (
    <AppShell>
      <BasketContent />
    </AppShell>
  )
}
