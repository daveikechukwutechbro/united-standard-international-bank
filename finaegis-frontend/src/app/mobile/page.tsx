"use client"

import { useQuery } from "@apollo/client"
import { GET_MOBILE_DEVICES } from "@/lib/graphql/queries"
import { AppShell } from "@/components/layout/AppShell"
import { Smartphone, CheckCircle, XCircle, ShieldCheck } from "lucide-react"

function MobileContent() {
  const { data, loading } = useQuery(GET_MOBILE_DEVICES, { variables: { first: 100 } })
  const devices = data?.mobileDevices?.data || []

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-white mb-1">Mobile Devices</h1>
        <p className="text-sm text-neutral-400 mb-6">Manage your trusted mobile devices</p>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Registered Devices</h2>
        </div>
        {loading ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">Loading...</p>
        ) : devices.length === 0 ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">No devices registered</p>
        ) : (
          <div className="divide-y divide-neutral-800">
            {devices.map((d: any) => (
              <div key={d.id} className="flex items-center justify-between px-5 py-3">
                <div className="flex items-center gap-3">
                  <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600/10 text-blue-400">
                    <Smartphone size={14} />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-white">{d.device_name || d.device_id}</p>
                    <p className="text-xs text-neutral-500 capitalize">{d.platform} &middot; {d.device_id}</p>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  {d.biometric_enabled && (
                    <span className="inline-flex items-center rounded-full bg-blue-600/10 px-2 py-0.5 text-xs font-medium text-blue-400">
                      <ShieldCheck size={10} className="mr-1" />
                      Biometric
                    </span>
                  )}
                  <span className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ${
                    d.is_trusted ? "bg-green-500/10 text-green-400" : "bg-neutral-500/10 text-neutral-400"
                  }`}>
                    {d.is_trusted ? <CheckCircle size={10} /> : <XCircle size={10} />}
                    {d.is_trusted ? "Trusted" : "Untrusted"}
                  </span>
                  <span className="text-xs text-neutral-500">
                    {d.last_active_at ? new Date(d.last_active_at).toLocaleDateString() : "Never"}
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

export default function MobilePage() {
  return (
    <AppShell>
      <MobileContent />
    </AppShell>
  )
}
