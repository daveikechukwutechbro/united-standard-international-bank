"use client"

import { useState } from "react"
import { useQuery, useMutation } from "@apollo/client"
import { GET_KYC_VERIFICATIONS, GET_COMPLIANCE_ALERTS, GET_COMPLIANCE_CASES } from "@/lib/graphql/queries"
import { SUBMIT_KYC } from "@/lib/graphql/mutations"
import { AppShell } from "@/components/layout/AppShell"
import { ShieldCheck, Bell, FolderOpen } from "lucide-react"

const severityBadge: Record<string, string> = {
  high: "bg-red-500/10 text-red-400",
  medium: "bg-yellow-500/10 text-yellow-400",
  low: "bg-green-500/10 text-green-400",
}

const statusBadge: Record<string, string> = {
  completed: "bg-green-500/10 text-green-400",
  verified: "bg-green-500/10 text-green-400",
  active: "bg-green-500/10 text-green-400",
  pending: "bg-yellow-500/10 text-yellow-400",
  failed: "bg-red-500/10 text-red-400",
  rejected: "bg-red-500/10 text-red-400",
}

const tabs = [
  { id: "kyc", label: "KYC", icon: ShieldCheck },
  { id: "alerts", label: "Alerts", icon: Bell },
  { id: "cases", label: "Cases", icon: FolderOpen },
]

function ComplianceContent() {
  const [activeTab, setActiveTab] = useState("kyc")

  const { data: kycData, loading: kycLoading, refetch: refetchKyc } = useQuery(GET_KYC_VERIFICATIONS, { variables: { first: 100 } })
  const { data: alertsData, loading: alertsLoading } = useQuery(GET_COMPLIANCE_ALERTS, { variables: { first: 100 } })
  const { data: casesData, loading: casesLoading } = useQuery(GET_COMPLIANCE_CASES, { variables: { first: 100 } })
  const [submitKyc] = useMutation(SUBMIT_KYC)

  const [showKycForm, setShowKycForm] = useState(false)
  const [kycType, setKycType] = useState("")
  const [docType, setDocType] = useState("")

  const verifications = kycData?.kycVerifications?.data || []
  const alerts = alertsData?.complianceAlerts?.data || []
  const cases = casesData?.complianceCases?.data || []

  const handleSubmitKyc = async (e: React.FormEvent) => {
    e.preventDefault()
    await submitKyc({
      variables: {
        input: {
          type: kycType,
          document_type: docType,
        },
      },
    })
    setKycType("")
    setDocType("")
    setShowKycForm(false)
    refetchKyc()
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-white mb-1">Compliance</h1>
        <p className="text-sm text-neutral-400 mb-6">KYC verifications, alerts, and case management</p>
      </div>

      <div className="flex gap-1 rounded-lg bg-neutral-800 p-1 mb-6">
        {tabs.map((tab) => {
          const Icon = tab.icon
          return (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition-colors ${
                activeTab === tab.id ? "bg-neutral-950 text-white" : "text-neutral-400 hover:text-white"
              }`}
            >
              <Icon size={14} />
              {tab.label}
            </button>
          )
        })}
      </div>

      {activeTab === "kyc" && (
        <div className="space-y-4">
          <div className="flex justify-end">
            <button
              onClick={() => setShowKycForm(!showKycForm)}
              className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50"
            >
              Submit KYC
            </button>
          </div>

          {showKycForm && (
            <form onSubmit={handleSubmitKyc} className="rounded-xl border border-neutral-800 bg-neutral-900 p-5 space-y-4">
              <h2 className="text-sm font-medium text-white">New KYC Submission</h2>
              <div className="grid gap-4 sm:grid-cols-2">
                <div>
                  <label className="block text-xs text-neutral-400 mb-1.5">Type</label>
                  <input
                    type="text"
                    value={kycType}
                    onChange={(e) => setKycType(e.target.value)}
                    placeholder="identity"
                    className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label className="block text-xs text-neutral-400 mb-1.5">Document Type</label>
                  <input
                    type="text"
                    value={docType}
                    onChange={(e) => setDocType(e.target.value)}
                    placeholder="passport"
                    className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  />
                </div>
              </div>
              <div className="flex gap-2">
                <button type="submit" className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50">
                  Submit
                </button>
                <button type="button" onClick={() => setShowKycForm(false)} className="rounded-lg border border-neutral-700 px-4 py-2 text-sm font-medium text-neutral-300 hover:bg-neutral-800">
                  Cancel
                </button>
              </div>
            </form>
          )}

          <div className="rounded-xl border border-neutral-800 bg-neutral-900">
            <div className="border-b border-neutral-800 px-5 py-4">
              <h2 className="text-sm font-medium text-white">KYC Verifications</h2>
            </div>
            {kycLoading ? (
              <p className="px-5 py-8 text-center text-sm text-neutral-500">Loading...</p>
            ) : verifications.length === 0 ? (
              <p className="px-5 py-8 text-center text-sm text-neutral-500">No verifications</p>
            ) : (
              <div className="divide-y divide-neutral-800">
                {verifications.map((v: any) => (
                  <div key={v.id} className="flex items-center justify-between px-5 py-3">
                    <div className="flex items-center gap-3">
                      <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600/10 text-blue-400">
                        <ShieldCheck size={14} />
                      </div>
                      <div>
                        <p className="text-sm text-white">{v.verification_number}</p>
                        <p className="text-xs text-neutral-500 capitalize">{v.type} &middot; {v.provider}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-3">
                      <span className="text-xs text-neutral-400">{v.document_type}</span>
                      <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${statusBadge[v.status] || "bg-neutral-500/10 text-neutral-400"}`}>
                        {v.status}
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      )}

      {activeTab === "alerts" && (
        <div className="rounded-xl border border-neutral-800 bg-neutral-900">
          <div className="border-b border-neutral-800 px-5 py-4">
            <h2 className="text-sm font-medium text-white">Compliance Alerts</h2>
          </div>
          {alertsLoading ? (
            <p className="px-5 py-8 text-center text-sm text-neutral-500">Loading...</p>
          ) : alerts.length === 0 ? (
            <p className="px-5 py-8 text-center text-sm text-neutral-500">No alerts</p>
          ) : (
            <div className="divide-y divide-neutral-800">
              {alerts.map((a: any) => (
                <div key={a.id} className="px-5 py-3">
                  <div className="flex items-center justify-between mb-1">
                    <div className="flex items-center gap-2">
                      <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${severityBadge[a.severity] || "bg-neutral-500/10 text-neutral-400"}`}>
                        {a.severity}
                      </span>
                      <p className="text-sm font-medium text-white">{a.title}</p>
                    </div>
                    <span className="text-xs text-neutral-500">{a.alert_id}</span>
                  </div>
                  <p className="text-xs text-neutral-400 ml-1">{a.description}</p>
                  <div className="flex items-center gap-3 mt-1.5 text-xs text-neutral-500">
                    <span className="capitalize">{a.type?.replace("_", " ")}</span>
                    <span>Risk: {a.risk_score}</span>
                    <span className="capitalize">{a.status}</span>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {activeTab === "cases" && (
        <div className="rounded-xl border border-neutral-800 bg-neutral-900">
          <div className="border-b border-neutral-800 px-5 py-4">
            <h2 className="text-sm font-medium text-white">Compliance Cases</h2>
          </div>
          {casesLoading ? (
            <p className="px-5 py-8 text-center text-sm text-neutral-500">Loading...</p>
          ) : cases.length === 0 ? (
            <p className="px-5 py-8 text-center text-sm text-neutral-500">No cases</p>
          ) : (
            <div className="divide-y divide-neutral-800">
              {cases.map((c: any) => (
                <div key={c.id} className="flex items-center justify-between px-5 py-3">
                  <div className="flex items-center gap-3">
                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600/10 text-blue-400">
                      <FolderOpen size={14} />
                    </div>
                    <div>
                      <p className="text-sm font-medium text-white">{c.title}</p>
                      <p className="text-xs text-neutral-500">{c.case_number} &middot; {c.type?.replace("_", " ")}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${severityBadge[c.priority] || "bg-neutral-500/10 text-neutral-400"}`}>
                      {c.priority}
                    </span>
                    <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${statusBadge[c.status] || "bg-neutral-500/10 text-neutral-400"}`}>
                      {c.status}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  )
}

export default function CompliancePage() {
  return (
    <AppShell>
      <ComplianceContent />
    </AppShell>
  )
}
