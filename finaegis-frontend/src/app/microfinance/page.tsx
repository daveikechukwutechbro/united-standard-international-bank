"use client"

import { useQuery } from "@apollo/client"
import { GET_MFI_GROUPS } from "@/lib/graphql/queries"
import { AppShell } from "@/components/layout/AppShell"
import { Users } from "lucide-react"

const statusBadge: Record<string, string> = {
  active: "bg-green-500/10 text-green-400",
  pending: "bg-yellow-500/10 text-yellow-400",
  inactive: "bg-neutral-500/10 text-neutral-400",
  closed: "bg-red-500/10 text-red-400",
}

function MicrofinanceContent() {
  const { data, loading } = useQuery(GET_MFI_GROUPS, { variables: { first: 100 } })
  const groups = data?.mfiGroups?.data || []

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-white mb-1">Microfinance</h1>
        <p className="text-sm text-neutral-400 mb-6">Microfinance institution group management</p>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">MFI Groups</h2>
        </div>
        {loading ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">Loading...</p>
        ) : groups.length === 0 ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">No MFI groups</p>
        ) : (
          <div className="divide-y divide-neutral-800">
            {groups.map((g: any) => (
              <div key={g.id} className="flex items-center justify-between px-5 py-3">
                <div className="flex items-center gap-3">
                  <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600/10 text-blue-400">
                    <Users size={14} />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-white">{g.name}</p>
                    <p className="text-xs text-neutral-500 capitalize">{g.meeting_frequency?.replace(/_/g, " ")} meetings</p>
                  </div>
                </div>
                <span
                  className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${
                    statusBadge[g.status] || "bg-neutral-500/10 text-neutral-400"
                  }`}
                >
                  {g.status}
                </span>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}

export default function MicrofinancePage() {
  return (
    <AppShell>
      <MicrofinanceContent />
    </AppShell>
  )
}
