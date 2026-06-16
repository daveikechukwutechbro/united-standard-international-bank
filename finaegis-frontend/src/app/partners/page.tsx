"use client"

import { useQuery } from "@apollo/client"
import { GET_PARTNERS } from "@/lib/graphql/queries"
import { AppShell } from "@/components/layout/AppShell"
import { Handshake } from "lucide-react"

const statusBadge: Record<string, string> = {
  active: "bg-green-500/10 text-green-400",
  pending: "bg-yellow-500/10 text-yellow-400",
  suspended: "bg-red-500/10 text-red-400",
  terminated: "bg-neutral-500/10 text-neutral-400",
}

const tierBadge: Record<string, string> = {
  platinum: "bg-purple-500/10 text-purple-400",
  gold: "bg-yellow-500/10 text-yellow-400",
  silver: "bg-neutral-400/10 text-neutral-400",
  bronze: "bg-orange-500/10 text-orange-400",
}

function PartnersContent() {
  const { data, loading } = useQuery(GET_PARTNERS, { variables: { first: 100 } })
  const partners = data?.partners?.data || []

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-white mb-1">FI Partners</h1>
        <p className="text-sm text-neutral-400 mb-6">Financial institution partner management</p>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Institutions</h2>
        </div>
        {loading ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">Loading...</p>
        ) : partners.length === 0 ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">No partners found</p>
        ) : (
          <div className="divide-y divide-neutral-800">
            {partners.map((p: any) => (
              <div key={p.id} className="flex items-center justify-between px-5 py-3">
                <div className="flex items-center gap-3 min-w-0">
                  <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600/10 text-blue-400 shrink-0">
                    <Handshake size={14} />
                  </div>
                  <div className="min-w-0">
                    <p className="text-sm font-medium text-white">{p.institution_name}</p>
                    <p className="text-xs text-neutral-500">
                      {p.legal_name} &middot; {p.country}
                    </p>
                    <p className="text-xs text-neutral-500 capitalize">{p.institution_type?.replace(/_/g, " ")}</p>
                  </div>
                </div>
                <div className="flex items-center gap-2 shrink-0 ml-4">
                  <span
                    className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${
                      tierBadge[p.tier] || "bg-neutral-500/10 text-neutral-400"
                    }`}
                  >
                    {p.tier}
                  </span>
                  <span
                    className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${
                      statusBadge[p.status] || "bg-neutral-500/10 text-neutral-400"
                    }`}
                  >
                    {p.status}
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

export default function PartnersPage() {
  return (
    <AppShell>
      <PartnersContent />
    </AppShell>
  )
}
