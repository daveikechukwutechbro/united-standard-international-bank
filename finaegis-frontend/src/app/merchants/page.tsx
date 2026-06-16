"use client"

import { useQuery } from "@apollo/client"
import { GET_MERCHANTS } from "@/lib/graphql/queries"
import { AppShell } from "@/components/layout/AppShell"
import { Store } from "lucide-react"

const statusBadge: Record<string, string> = {
  active: "bg-green-500/10 text-green-400",
  pending: "bg-yellow-500/10 text-yellow-400",
  suspended: "bg-red-500/10 text-red-400",
  inactive: "bg-neutral-500/10 text-neutral-400",
}

function MerchantsContent() {
  const { data, loading } = useQuery(GET_MERCHANTS, { variables: { first: 100 } })
  const merchants = data?.merchants?.data || []

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-white mb-1">Merchants</h1>
        <p className="text-sm text-neutral-400 mb-6">Manage merchant accounts</p>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">All Merchants</h2>
        </div>
        {loading ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">Loading...</p>
        ) : merchants.length === 0 ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">No merchants found</p>
        ) : (
          <div className="divide-y divide-neutral-800">
            {merchants.map((m: any) => (
              <div key={m.id} className="flex items-center justify-between px-5 py-3">
                <div className="flex items-center gap-3">
                  <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600/10 text-blue-400">
                    <Store size={14} />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-white">{m.display_name}</p>
                    <p className="text-xs text-neutral-500 font-mono">{m.public_id}</p>
                  </div>
                </div>
                <span
                  className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${
                    statusBadge[m.status] || "bg-neutral-500/10 text-neutral-400"
                  }`}
                >
                  {m.status}
                </span>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}

export default function MerchantsPage() {
  return (
    <AppShell>
      <MerchantsContent />
    </AppShell>
  )
}
