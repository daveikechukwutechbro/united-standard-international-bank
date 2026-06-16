"use client"

import { useState } from "react"
import { useQuery, useMutation } from "@apollo/client"
import { GET_ACCOUNTS, GET_BANK_ACCOUNTS, GET_BANK_TRANSFERS } from "@/lib/graphql/queries"
import { INITIATE_BANK_TRANSFER, CONNECT_BANK } from "@/lib/graphql/mutations"
import { AppShell } from "@/components/layout/AppShell"
import { Send, Landmark, History, Plus, ArrowRightLeft, Banknote } from "lucide-react"

type Tab = "send" | "bank-accounts" | "history"

function TransferContent() {
  const [activeTab, setActiveTab] = useState<Tab>("send")

  const tabs: { key: Tab; label: string; icon: React.ReactNode }[] = [
    { key: "send", label: "Send", icon: <Send size={14} /> },
    { key: "bank-accounts", label: "Bank Accounts", icon: <Landmark size={14} /> },
    { key: "history", label: "History", icon: <History size={14} /> },
  ]

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-white">Transfers</h1>
        <p className="text-sm text-neutral-400">Send money and manage bank accounts</p>
      </div>

      <div className="flex gap-1 rounded-lg border border-neutral-800 bg-neutral-900 p-1">
        {tabs.map((t) => (
          <button
            key={t.key}
            onClick={() => setActiveTab(t.key)}
            className={`flex flex-1 items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition ${
              activeTab === t.key
                ? "bg-blue-600 text-white"
                : "text-neutral-400 hover:text-white"
            }`}
          >
            {t.icon}
            {t.label}
          </button>
        ))}
      </div>

      {activeTab === "send" && <SendTab />}
      {activeTab === "bank-accounts" && <BankAccountsTab />}
      {activeTab === "history" && <HistoryTab />}
    </div>
  )
}

function SendTab() {
  const { data: accountsData } = useQuery(GET_ACCOUNTS, { variables: { first: 100 } })
  const [initiateTransfer, { loading }] = useMutation(INITIATE_BANK_TRANSFER)
  const [form, setForm] = useState({
    from_account_id: "",
    to_iban: "",
    amount: "",
    currency: "USD",
    reference: "",
  })
  const [success, setSuccess] = useState(false)

  const accounts = accountsData?.accounts?.data || []

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setSuccess(false)
    await initiateTransfer({
      variables: {
        input: {
          from_account_id: form.from_account_id,
          to_iban: form.to_iban,
          amount: parseFloat(form.amount),
          currency: form.currency,
          reference: form.reference,
        },
      },
    })
    setForm({ from_account_id: "", to_iban: "", amount: "", currency: "USD", reference: "" })
    setSuccess(true)
  }

  return (
    <div className="rounded-xl border border-neutral-800 bg-neutral-900 p-5">
      <h2 className="mb-4 text-sm font-medium text-white">Initiate Bank Transfer</h2>
      {success && (
        <div className="mb-4 rounded-lg bg-green-500/10 px-4 py-3 text-sm text-green-400">
          Transfer initiated successfully.
        </div>
      )}
      <form onSubmit={handleSubmit} className="space-y-4">
        <div>
          <label className="mb-1.5 block text-xs font-medium text-neutral-400">From Account</label>
          <select
            className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white"
            value={form.from_account_id}
            onChange={(e) => setForm({ ...form, from_account_id: e.target.value })}
            required
          >
            <option value="">Select account</option>
            {accounts.map((a: any) => (
              <option key={a.id} value={a.id}>
                {a.name} (${a.balance?.toLocaleString()})
              </option>
            ))}
          </select>
        </div>
        <div>
          <label className="mb-1.5 block text-xs font-medium text-neutral-400">Recipient IBAN</label>
          <input
            className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500"
            placeholder="GB29NWBK60161331926819"
            value={form.to_iban}
            onChange={(e) => setForm({ ...form, to_iban: e.target.value })}
            required
          />
        </div>
        <div className="grid gap-4 sm:grid-cols-3">
          <div>
            <label className="mb-1.5 block text-xs font-medium text-neutral-400">Amount</label>
            <input
              type="number"
              step="0.01"
              className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500"
              placeholder="0.00"
              value={form.amount}
              onChange={(e) => setForm({ ...form, amount: e.target.value })}
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
          <div>
            <label className="mb-1.5 block text-xs font-medium text-neutral-400">Reference</label>
            <input
              className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500"
              placeholder="Invoice #123"
              value={form.reference}
              onChange={(e) => setForm({ ...form, reference: e.target.value })}
            />
          </div>
        </div>
        <button
          type="submit"
          disabled={loading}
          className="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50"
        >
          <Send size={14} />
          {loading ? "Sending..." : "Send Transfer"}
        </button>
      </form>
    </div>
  )
}

function BankAccountsTab() {
  const { data: bankAccountsData, loading } = useQuery(GET_BANK_ACCOUNTS, { variables: { first: 100 } })
  const [connectBank, { loading: connecting }] = useMutation(CONNECT_BANK, {
    refetchQueries: [{ query: GET_BANK_ACCOUNTS, variables: { first: 100 } }],
  })
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState({ bank_code: "", account_number: "", iban: "", swift: "" })

  const bankAccounts = bankAccountsData?.bankAccounts?.data || []

  const handleConnect = async (e: React.FormEvent) => {
    e.preventDefault()
    await connectBank({ variables: { input: { bank_code: form.bank_code, account_number: form.account_number, iban: form.iban, swift: form.swift } } })
    setForm({ bank_code: "", account_number: "", iban: "", swift: "" })
    setShowForm(false)
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <p className="text-sm text-neutral-400">{bankAccounts.length} linked bank account(s)</p>
        <button
          onClick={() => setShowForm(!showForm)}
          className="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500"
        >
          <Plus size={14} />
          Connect Bank
        </button>
      </div>

      {showForm && (
        <form onSubmit={handleConnect} className="rounded-xl border border-neutral-800 bg-neutral-900 p-5 space-y-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-1.5 block text-xs font-medium text-neutral-400">Bank Code</label>
              <input
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500"
                placeholder="e.g. CHASUS33"
                value={form.bank_code}
                onChange={(e) => setForm({ ...form, bank_code: e.target.value })}
                required
              />
            </div>
            <div>
              <label className="mb-1.5 block text-xs font-medium text-neutral-400">Account Number</label>
              <input
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500"
                placeholder="e.g. 12345678"
                value={form.account_number}
                onChange={(e) => setForm({ ...form, account_number: e.target.value })}
                required
              />
            </div>
            <div>
              <label className="mb-1.5 block text-xs font-medium text-neutral-400">IBAN</label>
              <input
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500"
                placeholder="GB29NWBK60161331926819"
                value={form.iban}
                onChange={(e) => setForm({ ...form, iban: e.target.value })}
              />
            </div>
            <div>
              <label className="mb-1.5 block text-xs font-medium text-neutral-400">SWIFT / BIC</label>
              <input
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500"
                placeholder="NWBKGB2L"
                value={form.swift}
                onChange={(e) => setForm({ ...form, swift: e.target.value })}
              />
            </div>
          </div>
          <div className="flex gap-3">
            <button
              type="submit"
              disabled={connecting}
              className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50"
            >
              {connecting ? "Connecting..." : "Connect"}
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
          <h2 className="text-sm font-medium text-white">Linked Bank Accounts</h2>
        </div>
        {loading ? (
          <div className="flex justify-center py-12">
            <div className="h-6 w-6 animate-spin rounded-full border-2 border-blue-500 border-t-transparent" />
          </div>
        ) : bankAccounts.length === 0 ? (
          <p className="px-5 py-12 text-center text-sm text-neutral-500">No bank accounts linked</p>
        ) : (
          <div className="divide-y divide-neutral-800">
            {bankAccounts.map((b: any) => (
              <div key={b.id} className="flex items-center justify-between px-5 py-4">
                <div className="flex items-center gap-3">
                  <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600/10 text-blue-400">
                    <Landmark size={16} />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-white">{b.bank_code}</p>
                    <p className="text-xs text-neutral-500">****{b.account_number?.slice(-4)}</p>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <span className="text-xs text-neutral-500">{b.currency}</span>
                  <span
                    className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                      b.status === "active" || b.status === "verified"
                        ? "bg-green-500/10 text-green-400"
                        : "bg-yellow-500/10 text-yellow-400"
                    }`}
                  >
                    {b.status}
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

function HistoryTab() {
  const { data: transfersData, loading } = useQuery(GET_BANK_TRANSFERS, { variables: { first: 100 } })

  const transfers = transfersData?.bankTransfers || []

  const statusBadge = (status: string) => {
    const styles: Record<string, string> = {
      completed: "bg-green-500/10 text-green-400",
      pending: "bg-yellow-500/10 text-yellow-400",
      failed: "bg-red-500/10 text-red-400",
      cancelled: "bg-neutral-500/10 text-neutral-400",
    }
    return `rounded-full px-2 py-0.5 text-xs font-medium ${styles[status] || "bg-neutral-500/10 text-neutral-400"}`
  }

  return (
    <div className="rounded-xl border border-neutral-800 bg-neutral-900">
      <div className="border-b border-neutral-800 px-5 py-4">
        <h2 className="text-sm font-medium text-white">Transfer History</h2>
      </div>
      {loading ? (
        <div className="flex justify-center py-12">
          <div className="h-6 w-6 animate-spin rounded-full border-2 border-blue-500 border-t-transparent" />
        </div>
      ) : transfers.length === 0 ? (
        <p className="px-5 py-12 text-center text-sm text-neutral-500">No transfers yet</p>
      ) : (
        <div className="divide-y divide-neutral-800">
          {transfers.map((t: any) => (
            <div key={t.id} className="flex items-center justify-between px-5 py-4">
              <div className="flex items-center gap-3">
                <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600/10 text-blue-400">
                  <ArrowRightLeft size={16} />
                </div>
                <div>
                  <p className="text-sm text-white">
                    {t.from_account_id?.slice(0, 8)}... → {t.to_account_id?.slice(0, 8)}...
                  </p>
                  <p className="text-xs text-neutral-500">
                    {t.reference || new Date(t.created_at).toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" })}
                  </p>
                </div>
              </div>
              <div className="flex items-center gap-4">
                <div className="text-right">
                  <p className="text-sm font-medium text-white">
                    {t.currency} {parseFloat(t.amount).toLocaleString("en-US", { minimumFractionDigits: 2 })}
                  </p>
                </div>
                <span className={statusBadge(t.status)}>{t.status}</span>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}

export default function TransfersPage() {
  return (
    <AppShell>
      <TransferContent />
    </AppShell>
  )
}
