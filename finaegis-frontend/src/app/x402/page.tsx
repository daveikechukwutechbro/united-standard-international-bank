"use client"

import { useQuery } from "@apollo/client"
import { GET_X402_PAYMENTS } from "@/lib/graphql/queries"
import { AppShell } from "@/components/layout/AppShell"

const statusBadge: Record<string, string> = {
  completed: "bg-green-500/10 text-green-400",
  confirmed: "bg-green-500/10 text-green-400",
  pending: "bg-yellow-500/10 text-yellow-400",
  failed: "bg-red-500/10 text-red-400",
  expired: "bg-neutral-500/10 text-neutral-400",
}

function X402Content() {
  const { data, loading } = useQuery(GET_X402_PAYMENTS, { variables: { first: 100 } })
  const payments = data?.x402Payments || []

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-white mb-1">x402 Payments</h1>
        <p className="text-sm text-neutral-400 mb-6">HTTP 402 Payment Requests</p>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Payment Requests</h2>
        </div>
        {loading ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">Loading...</p>
        ) : payments.length === 0 ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">No x402 payments</p>
        ) : (
          <div className="divide-y divide-neutral-800">
            {payments.map((p: any) => (
              <div key={p.id} className="flex items-center justify-between px-5 py-3">
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 mb-0.5">
                    <p className="text-sm font-medium text-white truncate">{p.payer_address}</p>
                    <span className="text-neutral-600">&rarr;</span>
                    <p className="text-sm text-white truncate">{p.pay_to_address}</p>
                  </div>
                  <p className="text-xs text-neutral-500">
                    {p.amount} &middot; {p.network}
                  </p>
                </div>
                <div className="flex items-center gap-3 ml-4 shrink-0">
                  {p.transaction_hash && (
                    <span className="text-xs text-neutral-500 font-mono max-w-[100px] truncate">
                      {p.transaction_hash}
                    </span>
                  )}
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

export default function X402Page() {
  return (
    <AppShell>
      <X402Content />
    </AppShell>
  )
}
