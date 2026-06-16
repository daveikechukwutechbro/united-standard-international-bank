"use client"

import { useState } from "react"
import { useQuery, useMutation } from "@apollo/client"
import { GET_AGENTS } from "@/lib/graphql/queries"
import { REGISTER_AGENT } from "@/lib/graphql/mutations"
import { AppShell } from "@/components/layout/AppShell"
import { Code, Webhook, Bot, Plus, ExternalLink } from "lucide-react"

const statusBadge: Record<string, string> = {
  active: "bg-green-500/10 text-green-400",
  inactive: "bg-neutral-500/10 text-neutral-400",
  error: "bg-red-500/10 text-red-400",
}

function DevelopersContent() {
  const { data, loading, refetch } = useQuery(GET_AGENTS, { variables: { first: 100 } })
  const [registerAgent, { loading: registering }] = useMutation(REGISTER_AGENT)
  const [showForm, setShowForm] = useState(false)
  const [name, setName] = useState("")
  const [type, setType] = useState("")
  const [capabilities, setCapabilities] = useState("")

  const agents = data?.agents?.data || []

  const handleRegister = async (e: React.FormEvent) => {
    e.preventDefault()
    await registerAgent({
      variables: {
        input: {
          name,
          type,
          capabilities: capabilities.split(",").map((c) => c.trim()).filter(Boolean),
        },
      },
    })
    setName("")
    setType("")
    setCapabilities("")
    setShowForm(false)
    refetch()
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-white mb-1">Developers</h1>
        <p className="text-sm text-neutral-400 mb-6">API documentation, webhooks, and AI agent management</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a
          href="#"
          className="rounded-xl border border-neutral-800 bg-neutral-900 p-5 hover:border-neutral-700 transition-colors"
        >
          <div className="flex items-center gap-3 mb-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600/10 text-blue-400">
              <Code size={20} />
            </div>
            <div>
              <h3 className="text-sm font-medium text-white">API Reference</h3>
              <p className="text-xs text-neutral-400">REST & GraphQL</p>
            </div>
          </div>
          <p className="text-xs text-neutral-500">Explore our API endpoints, authentication, and rate limits.</p>
        </a>

        <a
          href="#"
          className="rounded-xl border border-neutral-800 bg-neutral-900 p-5 hover:border-neutral-700 transition-colors"
        >
          <div className="flex items-center gap-3 mb-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600/10 text-blue-400">
              <Webhook size={20} />
            </div>
            <div>
              <h3 className="text-sm font-medium text-white">Webhooks</h3>
              <p className="text-xs text-neutral-400">Event-driven</p>
            </div>
          </div>
          <p className="text-xs text-neutral-500">Receive real-time events for transactions, alerts, and more.</p>
        </a>

        <a
          href="#"
          className="rounded-xl border border-neutral-800 bg-neutral-900 p-5 hover:border-neutral-700 transition-colors"
        >
          <div className="flex items-center gap-3 mb-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600/10 text-blue-400">
              <Bot size={20} />
            </div>
            <div>
              <h3 className="text-sm font-medium text-white">AI Agents</h3>
              <p className="text-xs text-neutral-400">Autonomous</p>
            </div>
          </div>
          <p className="text-xs text-neutral-500">Deploy and manage AI agents for automated financial operations.</p>
        </a>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4 flex items-center justify-between">
          <h2 className="text-sm font-medium text-white">Registered Agents</h2>
          <button
            onClick={() => setShowForm(!showForm)}
            className="flex items-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500"
          >
            <Plus size={14} />
            Register Agent
          </button>
        </div>

        {showForm && (
          <form onSubmit={handleRegister} className="border-b border-neutral-800 p-5 space-y-4">
            <div className="grid gap-4 sm:grid-cols-3">
              <div>
                <label className="block text-xs text-neutral-400 mb-1.5">Name</label>
                <input
                  type="text"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  placeholder="My Agent"
                  required
                  className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block text-xs text-neutral-400 mb-1.5">Type</label>
                <input
                  type="text"
                  value={type}
                  onChange={(e) => setType(e.target.value)}
                  placeholder="trading"
                  required
                  className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block text-xs text-neutral-400 mb-1.5">Capabilities</label>
                <input
                  type="text"
                  value={capabilities}
                  onChange={(e) => setCapabilities(e.target.value)}
                  placeholder="analytics, trading"
                  className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
              </div>
            </div>
            <div className="flex gap-2">
              <button
                type="submit"
                disabled={registering}
                className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50"
              >
                {registering ? "Registering..." : "Register"}
              </button>
              <button
                type="button"
                onClick={() => setShowForm(false)}
                className="rounded-lg border border-neutral-700 px-4 py-2 text-sm font-medium text-neutral-300 hover:bg-neutral-800"
              >
                Cancel
              </button>
            </div>
          </form>
        )}

        {loading ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">Loading...</p>
        ) : agents.length === 0 ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">No agents registered</p>
        ) : (
          <div className="divide-y divide-neutral-800">
            {agents.map((a: any) => (
              <div key={a.id} className="flex items-center justify-between px-5 py-3">
                <div className="flex items-center gap-3">
                  <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600/10 text-blue-400">
                    <Bot size={14} />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-white">{a.name}</p>
                    <p className="text-xs text-neutral-500">{a.type} &middot; Agent #{a.agent_id}</p>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  {a.capabilities?.length > 0 && (
                    <div className="hidden sm:flex gap-1">
                      {a.capabilities.slice(0, 2).map((c: string, i: number) => (
                        <span key={i} className="inline-flex items-center rounded-full bg-neutral-800 px-2 py-0.5 text-xs font-medium text-neutral-300">
                          {c}
                        </span>
                      ))}
                      {a.capabilities.length > 2 && (
                        <span className="inline-flex items-center rounded-full bg-neutral-800 px-2 py-0.5 text-xs font-medium text-neutral-500">
                          +{a.capabilities.length - 2}
                        </span>
                      )}
                    </div>
                  )}
                  {a.relay_score != null && (
                    <span className="text-xs text-neutral-400">{a.relay_score.toFixed(2)}</span>
                  )}
                  <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${statusBadge[a.status] || "bg-neutral-500/10 text-neutral-400"}`}>
                    {a.status}
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

export default function DevelopersPage() {
  return (
    <AppShell>
      <DevelopersContent />
    </AppShell>
  )
}
