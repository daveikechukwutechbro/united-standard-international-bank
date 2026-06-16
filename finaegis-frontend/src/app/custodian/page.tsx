"use client"

import { useState } from "react"
import { useQuery, useMutation } from "@apollo/client"
import { GET_CUSTODIAN_ACCOUNTS } from "@/lib/graphql/queries"
import { LINK_CUSTODIAN_ACCOUNT } from "@/lib/graphql/mutations"
import { AppShell } from "@/components/layout/AppShell"
import { Plus, Building2 } from "lucide-react"

const statusBadge: Record<string, string> = {
  active: "bg-green-500/10 text-green-400",
  pending: "bg-yellow-500/10 text-yellow-400",
  inactive: "bg-neutral-500/10 text-neutral-400",
  suspended: "bg-red-500/10 text-red-400",
}

function CustodianContent() {
  const { data, loading, refetch } = useQuery(GET_CUSTODIAN_ACCOUNTS, { variables: { first: 100 } })
  const [linkCustodian] = useMutation(LINK_CUSTODIAN_ACCOUNT)

  const [showForm, setShowForm] = useState(false)
  const [accountUuid, setAccountUuid] = useState("")
  const [custodianName, setCustodianName] = useState("")
  const [custodianAccountId, setCustodianAccountId] = useState("")
  const [custodianAccountName, setCustodianAccountName] = useState("")

  const accounts = data?.custodianAccounts?.data || []

  const handleLink = async (e: React.FormEvent) => {
    e.preventDefault()
    await linkCustodian({
      variables: {
        input: {
          account_uuid: accountUuid,
          custodian_name: custodianName,
          custodian_account_id: custodianAccountId,
          custodian_account_name: custodianAccountName,
        },
      },
    })
    setAccountUuid("")
    setCustodianName("")
    setCustodianAccountId("")
    setCustodianAccountName("")
    setShowForm(false)
    refetch()
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-white mb-1">Custodian Accounts</h1>
        <p className="text-sm text-neutral-400 mb-6">Manage linked custodian accounts</p>
      </div>

      <div className="flex justify-end">
        <button
          onClick={() => setShowForm(!showForm)}
          className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50 flex items-center gap-2"
        >
          <Plus size={16} />
          Link Custodian
        </button>
      </div>

      {showForm && (
        <form onSubmit={handleLink} className="rounded-xl border border-neutral-800 bg-neutral-900 p-5 space-y-4">
          <h2 className="text-sm font-medium text-white">Link Custodian Account</h2>
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Account UUID</label>
              <input
                type="text"
                value={accountUuid}
                onChange={(e) => setAccountUuid(e.target.value)}
                placeholder="account-uuid"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Custodian Name</label>
              <input
                type="text"
                value={custodianName}
                onChange={(e) => setCustodianName(e.target.value)}
                placeholder="e.g. Coinbase, BitGo"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Custodian Account ID</label>
              <input
                type="text"
                value={custodianAccountId}
                onChange={(e) => setCustodianAccountId(e.target.value)}
                placeholder="external account ID"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-xs text-neutral-400 mb-1.5">Custodian Account Name</label>
              <input
                type="text"
                value={custodianAccountName}
                onChange={(e) => setCustodianAccountName(e.target.value)}
                placeholder="display name"
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
          </div>
          <div className="flex gap-2">
            <button type="submit" disabled={!custodianName || !custodianAccountId} className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50">
              Link Account
            </button>
            <button type="button" onClick={() => setShowForm(false)} className="rounded-lg border border-neutral-700 px-4 py-2 text-sm font-medium text-neutral-300 hover:bg-neutral-800">
              Cancel
            </button>
          </div>
        </form>
      )}

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Custodian Accounts</h2>
        </div>
        {loading ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">Loading...</p>
        ) : accounts.length === 0 ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">No custodian accounts</p>
        ) : (
          <div className="divide-y divide-neutral-800">
            {accounts.map((a: any) => (
              <div key={a.id} className="flex items-center justify-between px-5 py-3">
                <div className="flex items-center gap-3">
                  <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600/10 text-blue-400">
                    <Building2 size={14} />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-white">{a.custodian_name}</p>
                    <p className="text-xs text-neutral-500">Balance: {a.last_known_balance?.toLocaleString() ?? "—"}</p>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  {a.is_primary && (
                    <span className="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-blue-500/10 text-blue-400">
                      Primary
                    </span>
                  )}
                  <span
                    className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${
                      statusBadge[a.status] || "bg-neutral-500/10 text-neutral-400"
                    }`}
                  >
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

export default function CustodianPage() {
  return (
    <AppShell>
      <CustodianContent />
    </AppShell>
  )
}
