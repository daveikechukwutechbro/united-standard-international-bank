"use client"

import { AppShell } from "@/components/layout/AppShell"
import { ShieldCheck, Building2, Globe, FileText, CheckCircle, XCircle } from "lucide-react"

const consentTypes = [
  {
    name: "Account Information",
    description: "Read account balances and transaction history",
    scope: "accounts:read",
    status: "available",
  },
  {
    name: "Payment Initiation",
    description: "Initiate payments from your accounts",
    scope: "payments:write",
    status: "available",
  },
  {
    name: "Funds Confirmation",
    description: "Confirm available funds for a transaction",
    scope: "funds:read",
    status: "available",
  },
  {
    name: "Transaction History",
    description: "Access historical transaction data",
    scope: "transactions:read",
    status: "available",
  },
]

const regulators = [
  { name: "PSD2", region: "European Union", directive: "Payment Services Directive 2" },
  { name: "CMA", region: "United Kingdom", directive: "Competition and Markets Authority" },
  { name: "CDR", region: "Australia", directive: "Consumer Data Right" },
]

function OpenBankingContent() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-white mb-1">Open Banking</h1>
        <p className="text-sm text-neutral-400 mb-6">Manage Open Banking / PSD2 consents and integrations</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {regulators.map((reg) => (
          <div key={reg.name} className="rounded-xl border border-neutral-800 bg-neutral-900 p-5">
            <div className="flex items-center gap-3 mb-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600/10 text-blue-400">
                <Building2 size={20} />
              </div>
              <div>
                <h3 className="text-sm font-medium text-white">{reg.name}</h3>
                <p className="text-xs text-neutral-400">{reg.region}</p>
              </div>
            </div>
            <p className="text-xs text-neutral-500">{reg.directive}</p>
          </div>
        ))}
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Consent Types</h2>
        </div>
        <div className="divide-y divide-neutral-800">
          {consentTypes.map((ct) => (
            <div key={ct.scope} className="flex items-center justify-between px-5 py-3">
              <div className="flex items-center gap-3">
                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600/10 text-blue-400">
                  <FileText size={14} />
                </div>
                <div>
                  <p className="text-sm font-medium text-white">{ct.name}</p>
                  <p className="text-xs text-neutral-500">{ct.description}</p>
                </div>
              </div>
              <div className="flex items-center gap-3">
                <span className="text-xs text-neutral-500 font-mono">{ct.scope}</span>
                <span className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ${
                  ct.status === "available" ? "bg-green-500/10 text-green-400" : "bg-neutral-500/10 text-neutral-400"
                }`}>
                  {ct.status === "available" ? <CheckCircle size={10} /> : <XCircle size={10} />}
                  {ct.status}
                </span>
              </div>
            </div>
          ))}
        </div>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900 p-5">
        <div className="flex items-start gap-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600/10 text-blue-400 shrink-0">
            <ShieldCheck size={20} />
          </div>
          <div>
            <h2 className="text-sm font-medium text-white mb-1">Consent Management</h2>
            <p className="text-xs text-neutral-400">
              Open Banking consent management will be available in a future release. 
              You will be able to grant, revoke, and manage third-party access to your financial data 
              in compliance with PSD2 and other regulatory frameworks.
            </p>
            <div className="mt-4 rounded-lg bg-neutral-800 px-4 py-3 text-center text-sm text-neutral-500">
              Consent management dashboard coming soon
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default function OpenBankingPage() {
  return (
    <AppShell>
      <OpenBankingContent />
    </AppShell>
  )
}
