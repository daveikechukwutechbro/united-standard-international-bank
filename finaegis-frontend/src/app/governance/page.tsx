"use client"

import { useState } from "react"
import { useQuery, useMutation } from "@apollo/client"
import { GET_POLLS } from "@/lib/graphql/queries"
import { CREATE_POLL, CAST_VOTE } from "@/lib/graphql/mutations"
import { AppShell } from "@/components/layout/AppShell"
import { Vote } from "lucide-react"

const statusBadge: Record<string, string> = {
  active: "bg-green-500/10 text-green-400",
  pending: "bg-yellow-500/10 text-yellow-400",
  closed: "bg-neutral-500/10 text-neutral-400",
  cancelled: "bg-red-500/10 text-red-400",
}

function GovernanceContent() {
  const { data, loading, refetch } = useQuery(GET_POLLS, { variables: { first: 100 } })
  const [createPoll] = useMutation(CREATE_POLL)
  const [castVote] = useMutation(CAST_VOTE)

  const [showForm, setShowForm] = useState(false)
  const [title, setTitle] = useState("")
  const [description, setDescription] = useState("")
  const [type, setType] = useState("")
  const [options, setOptions] = useState("")
  const [startDate, setStartDate] = useState("")
  const [endDate, setEndDate] = useState("")

  const [votingPollId, setVotingPollId] = useState<string | null>(null)
  const [selectedOptions, setSelectedOptions] = useState("")

  const polls = data?.polls?.data || []

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault()
    await createPoll({
      variables: {
        input: {
          title,
          description,
          type,
          options: options.split(",").map((o) => o.trim()),
          start_date: startDate,
          end_date: endDate,
        },
      },
    })
    setTitle("")
    setDescription("")
    setType("")
    setOptions("")
    setStartDate("")
    setEndDate("")
    setShowForm(false)
    refetch()
  }

  const handleVote = async (pollId: string) => {
    await castVote({
      variables: {
        input: {
          poll_id: pollId,
          selected_options: selectedOptions.split(",").map((o) => o.trim()),
        },
      },
    })
    setVotingPollId(null)
    setSelectedOptions("")
    refetch()
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold text-white mb-1">Governance</h1>
          <p className="text-sm text-neutral-400 mb-6">Participate in on-chain governance polls</p>
        </div>
        <button
          onClick={() => setShowForm(!showForm)}
          className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50"
        >
          Create Poll
        </button>
      </div>

      {showForm && (
        <form onSubmit={handleCreate} className="rounded-xl border border-neutral-800 bg-neutral-900 p-5 space-y-4">
          <h2 className="text-sm font-medium text-white">New Poll</h2>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="sm:col-span-2">
              <label className="block text-xs text-neutral-400 mb-1.5">Title</label>
              <input
                type="text"
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                placeholder="Proposal title"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div className="sm:col-span-2">
              <label className="block text-xs text-neutral-400 mb-1.5">Description</label>
              <textarea
                value={description}
                onChange={(e) => setDescription(e.target.value)}
                placeholder="Describe the proposal"
                rows={3}
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Type</label>
              <input
                type="text"
                value={type}
                onChange={(e) => setType(e.target.value)}
                placeholder="e.g. proposal"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Options (comma-separated)</label>
              <input
                type="text"
                value={options}
                onChange={(e) => setOptions(e.target.value)}
                placeholder="For, Against, Abstain"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Start Date</label>
              <input
                type="date"
                value={startDate}
                onChange={(e) => setStartDate(e.target.value)}
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">End Date</label>
              <input
                type="date"
                value={endDate}
                onChange={(e) => setEndDate(e.target.value)}
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
          </div>
          <div className="flex gap-2">
            <button type="submit" className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50">
              Create Poll
            </button>
            <button type="button" onClick={() => setShowForm(false)} className="rounded-lg border border-neutral-700 px-4 py-2 text-sm font-medium text-neutral-300 hover:bg-neutral-800">
              Cancel
            </button>
          </div>
        </form>
      )}

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Polls</h2>
        </div>
        {loading ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">Loading...</p>
        ) : polls.length === 0 ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">No polls</p>
        ) : (
          <div className="divide-y divide-neutral-800">
            {polls.map((poll: any) => (
              <div key={poll.id} className="px-5 py-4">
                <div className="flex items-center justify-between mb-3">
                  <div className="flex items-center gap-3">
                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600/10 text-blue-400">
                      <Vote size={14} />
                    </div>
                    <div>
                      <p className="text-sm font-medium text-white">{poll.title}</p>
                      <p className="text-xs text-neutral-500">{poll.type}</p>
                    </div>
                  </div>
                  <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${statusBadge[poll.status] || "bg-neutral-500/10 text-neutral-400"}`}>
                    {poll.status}
                  </span>
                </div>
                <div className="grid grid-cols-2 gap-4 text-xs">
                  <div>
                    <span className="text-neutral-500">Start</span>
                    <p className="text-white font-medium">{poll.start_date ? new Date(poll.start_date).toLocaleDateString() : "—"}</p>
                  </div>
                  <div>
                    <span className="text-neutral-500">End</span>
                    <p className="text-white font-medium">{poll.end_date ? new Date(poll.end_date).toLocaleDateString() : "—"}</p>
                  </div>
                </div>
                {poll.status === "active" && (
                  <div className="mt-3">
                    {votingPollId === poll.uuid ? (
                      <form
                        onSubmit={(e) => { e.preventDefault(); handleVote(poll.uuid) }}
                        className="flex items-end gap-3"
                      >
                        <div className="flex-1">
                          <label className="block text-xs text-neutral-400 mb-1">Options (comma-separated)</label>
                          <input
                            type="text"
                            value={selectedOptions}
                            onChange={(e) => setSelectedOptions(e.target.value)}
                            placeholder="For"
                            className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-3 py-1.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                          />
                        </div>
                        <button type="submit" className="rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-500">
                          Submit Vote
                        </button>
                        <button type="button" onClick={() => setVotingPollId(null)} className="text-xs text-neutral-400 hover:text-white">
                          Cancel
                        </button>
                      </form>
                    ) : (
                      <button
                        onClick={() => setVotingPollId(poll.uuid)}
                        className="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-500"
                      >
                        Cast Vote
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

export default function GovernancePage() {
  return (
    <AppShell>
      <GovernanceContent />
    </AppShell>
  )
}
