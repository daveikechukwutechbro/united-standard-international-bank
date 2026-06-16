"use client"

import { useQuery } from "@apollo/client"
import { GET_BATCH_JOBS } from "@/lib/graphql/queries"
import { AppShell } from "@/components/layout/AppShell"
import { Layers } from "lucide-react"

const statusBadge: Record<string, string> = {
  completed: "bg-green-500/10 text-green-400",
  running: "bg-blue-500/10 text-blue-400",
  pending: "bg-yellow-500/10 text-yellow-400",
  failed: "bg-red-500/10 text-red-400",
  cancelled: "bg-neutral-500/10 text-neutral-400",
}

function BatchContent() {
  const { data, loading } = useQuery(GET_BATCH_JOBS, { variables: { first: 100 } })
  const jobs = data?.batchJobs?.data || []

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-white mb-1">Batch Jobs</h1>
        <p className="text-sm text-neutral-400 mb-6">Background job processing and monitoring</p>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Jobs</h2>
        </div>
        {loading ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">Loading...</p>
        ) : jobs.length === 0 ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">No batch jobs</p>
        ) : (
          <div className="divide-y divide-neutral-800">
            {jobs.map((j: any) => {
              const total = j.total_items || 0
              const processed = j.processed_items || 0
              const progress = total > 0 ? Math.round((processed / total) * 100) : 0

              return (
                <div key={j.id} className="flex items-center justify-between px-5 py-3">
                  <div className="flex items-center gap-3 min-w-0 flex-1">
                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600/10 text-blue-400 shrink-0">
                      <Layers size={14} />
                    </div>
                    <div className="min-w-0">
                      <p className="text-sm font-medium text-white">{j.name}</p>
                      <p className="text-xs text-neutral-500 capitalize">{j.type?.replace(/_/g, " ")}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-4 shrink-0 ml-4">
                    <div className="text-right text-xs text-neutral-500">
                      <p>{j.processed_items ?? 0} / {j.total_items ?? 0}</p>
                      {(j.failed_items ?? 0) > 0 && (
                        <p className="text-red-400">{j.failed_items} failed</p>
                      )}
                    </div>
                    <div className="w-24">
                      <div className="h-1.5 rounded-full bg-neutral-800">
                        <div
                          className="h-1.5 rounded-full bg-blue-500 transition-all"
                          style={{ width: `${progress}%` }}
                        />
                      </div>
                    </div>
                    <span
                      className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${
                        statusBadge[j.status] || "bg-neutral-500/10 text-neutral-400"
                      }`}
                    >
                      {j.status}
                    </span>
                  </div>
                </div>
              )
            })}
          </div>
        )}
      </div>
    </div>
  )
}

export default function BatchPage() {
  return (
    <AppShell>
      <BatchContent />
    </AppShell>
  )
}
