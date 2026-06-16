"use client"

import { useQuery } from "@apollo/client"
import { GET_REGULATORY_REPORTS } from "@/lib/graphql/queries"
import { AppShell } from "@/components/layout/AppShell"
import { ClipboardList } from "lucide-react"

const statusBadge: Record<string, string> = {
  submitted: "bg-green-500/10 text-green-400",
  approved: "bg-green-500/10 text-green-400",
  pending: "bg-yellow-500/10 text-yellow-400",
  draft: "bg-neutral-500/10 text-neutral-400",
  rejected: "bg-red-500/10 text-red-400",
  overdue: "bg-red-500/10 text-red-400",
}

function RegulatoryContent() {
  const { data, loading } = useQuery(GET_REGULATORY_REPORTS, { variables: { first: 100 } })
  const reports = data?.regulatoryReports?.data || []

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-white mb-1">Regulatory Reports</h1>
        <p className="text-sm text-neutral-400 mb-6">Compliance reporting and filings</p>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Reports</h2>
        </div>
        {loading ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">Loading...</p>
        ) : reports.length === 0 ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">No regulatory reports</p>
        ) : (
          <div className="divide-y divide-neutral-800">
            {reports.map((r: any) => (
              <div key={r.id} className="flex items-center justify-between px-5 py-3">
                <div className="flex items-center gap-3">
                  <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600/10 text-blue-400">
                    <ClipboardList size={14} />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-white capitalize">{r.report_type?.replace(/_/g, " ")}</p>
                    <p className="text-xs text-neutral-500">
                      {r.jurisdiction} &middot; Due: {r.due_date ? new Date(r.due_date).toLocaleDateString() : "—"}
                    </p>
                  </div>
                </div>
                <span
                  className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${
                    statusBadge[r.status] || "bg-neutral-500/10 text-neutral-400"
                  }`}
                >
                  {r.status}
                </span>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}

export default function RegulatoryPage() {
  return (
    <AppShell>
      <RegulatoryContent />
    </AppShell>
  )
}
