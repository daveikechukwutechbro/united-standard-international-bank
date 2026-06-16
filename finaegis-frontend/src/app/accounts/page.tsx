"use client"

import { useState } from "react"
import { useQuery, useMutation } from "@apollo/client"
import { GET_ACCOUNTS } from "@/lib/graphql/queries"
import { CREATE_ACCOUNT } from "@/lib/graphql/mutations"
import { AppShell } from "@/components/layout/AppShell"
import { useRouter } from "next/navigation"
import { Plus, Building2, Snowflake } from "lucide-react"

function AccountsContent() {
  const router = useRouter()
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState({ name: "", currency: "USD" })

  const { data: accountsData, loading } = useQuery(GET_ACCOUNTS, { variables: { first: 100 } })
  const [createAccount, { loading: creating }] = useMutation(CREATE_ACCOUNT, {
    refetchQueries: [{ query: GET_ACCOUNTS, variables: { first: 100 } }],
  })

  const accounts = accountsData?.accounts?.data || []

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault()
    await createAccount({ variables: { input: { name: form.name, currency: form.currency } } })
    setForm({ name: "", currency: "USD" })
    setShowForm(false)
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold text-white">Accounts</h1>
          <p className="text-sm text-neutral-400">Manage your bank accounts</p>
        </div>
        <button
          onClick={() => setShowForm(!showForm)}
          className="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500"
        >
          <Plus size={16} />
          Create Account
        </button>
      </div>

      {showForm && (
        <form onSubmit={handleCreate} className="rounded-xl border border-neutral-800 bg-neutral-900 p-5 space-y-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-1.5 block text-xs font-medium text-neutral-400">Account Name</label>
              <input
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500"
                placeholder="e.g. Savings"
                value={form.name}
                onChange={(e) => setForm({ ...form, name: e.target.value })}
                required
              />
            </div>
            <div>
              <label className="mb-1.5 block text-xs font-medium text-neutral-400">Currency</label>
              <select
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white"
                value={form.currency}
                onChange={(e) => setForm({ ...form, currency: e.target.value })}
              >
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
                <option value="GBP">GBP</option>
              </select>
            </div>
          </div>
          <div className="flex gap-3">
            <button
              type="submit"
              disabled={creating}
              className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50"
            >
              {creating ? "Creating..." : "Create"}
            </button>
            <button
              type="button"
              onClick={() => setShowForm(false)}
              className="rounded-lg border border-neutral-700 px-4 py-2 text-sm text-neutral-300 hover:bg-neutral-800"
            >
              Cancel
            </button>
          </div>
        </form>
      )}

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">All Accounts</h2>
        </div>
        {loading ? (
          <div className="flex justify-center py-12">
            <div className="h-6 w-6 animate-spin rounded-full border-2 border-blue-500 border-t-transparent" />
          </div>
        ) : accounts.length === 0 ? (
          <p className="px-5 py-12 text-center text-sm text-neutral-500">No accounts found</p>
        ) : (
          <div className="divide-y divide-neutral-800">
            {accounts.map((a: any) => (
              <div
                key={a.id}
                onClick={() => router.push(`/accounts/${a.id}`)}
                className="flex cursor-pointer items-center justify-between px-5 py-4 transition hover:bg-neutral-800/50"
              >
                <div className="flex items-center gap-3">
                  <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600/10 text-blue-400">
                    <Building2 size={16} />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-white">{a.name}</p>
                    <p className="text-xs text-neutral-500">{a.uuid?.slice(0, 12)}...</p>
                  </div>
                </div>
                <div className="flex items-center gap-4">
                  <div className="text-right">
                    <p className="text-sm font-semibold text-white">
                      ${a.balance?.toLocaleString("en-US", { minimumFractionDigits: 2 })}
                    </p>
                  </div>
                  {a.frozen ? (
                    <span className="inline-flex items-center gap-1 rounded-full bg-red-500/10 px-2 py-0.5 text-xs font-medium text-red-400">
                      <Snowflake size={10} />
                      Frozen
                    </span>
                  ) : (
                    <span className="rounded-full bg-green-500/10 px-2 py-0.5 text-xs font-medium text-green-400">
                      Active
                    </span>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}

export default function AccountsPage() {
  return (
    <AppShell>
      <AccountsContent />
    </AppShell>
  )
}
