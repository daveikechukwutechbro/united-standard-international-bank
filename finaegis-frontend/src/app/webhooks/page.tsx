"use client"

import { AppShell } from "@/components/layout/AppShell"
import { Webhook, ArrowRight, Code, Shield } from "lucide-react"

const events = [
  {
    name: "payment.completed",
    description: "A payment has been successfully processed",
    version: "v1",
  },
  {
    name: "payment.failed",
    description: "A payment attempt has failed",
    version: "v1",
  },
  {
    name: "transfer.received",
    description: "An incoming bank transfer has been received",
    version: "v1",
  },
  {
    name: "account.created",
    description: "A new account has been created",
    version: "v1",
  },
  {
    name: "kyc.verified",
    description: "KYC verification has been approved",
    version: "v1",
  },
  {
    name: "alert.triggered",
    description: "A compliance or fraud alert has been triggered",
    version: "v1",
  },
]

function WebhooksContent() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-white mb-1">Webhooks</h1>
        <p className="text-sm text-neutral-400 mb-6">Configure webhook endpoints to receive real-time events</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div className="rounded-xl border border-neutral-800 bg-neutral-900 p-5">
          <div className="flex items-center gap-3 mb-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600/10 text-blue-400">
              <Webhook size={20} />
            </div>
            <h3 className="text-sm font-medium text-white">Endpoint URL</h3>
          </div>
          <p className="text-xs text-neutral-500 mb-4">
            Provide a publicly accessible HTTPS endpoint where we will send POST requests with event payloads.
          </p>
          <div className="rounded-lg bg-neutral-800 px-3 py-2 text-xs text-neutral-400 font-mono">
            https://api.yourdomain.com/webhooks/finaegis
          </div>
        </div>

        <div className="rounded-xl border border-neutral-800 bg-neutral-900 p-5">
          <div className="flex items-center gap-3 mb-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600/10 text-blue-400">
              <Shield size={20} />
            </div>
            <h3 className="text-sm font-medium text-white">Security</h3>
          </div>
          <p className="text-xs text-neutral-500 mb-4">
            Each payload includes a signature header. Verify it using your secret key to ensure authenticity.
          </p>
          <div className="rounded-lg bg-neutral-800 px-3 py-2 text-xs text-neutral-400 font-mono">
            X-Signature: sha256=...
          </div>
        </div>

        <div className="rounded-xl border border-neutral-800 bg-neutral-900 p-5">
          <div className="flex items-center gap-3 mb-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600/10 text-blue-400">
              <Code size={20} />
            </div>
            <h3 className="text-sm font-medium text-white">Retry Policy</h3>
          </div>
          <p className="text-xs text-neutral-500 mb-4">
            Failed deliveries are retried up to 3 times with exponential backoff (1min, 5min, 30min).
          </p>
          <div className="rounded-lg bg-neutral-800 px-3 py-2 text-xs text-neutral-400 font-mono">
            Max retries: 3
          </div>
        </div>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Available Events</h2>
        </div>
        <div className="divide-y divide-neutral-800">
          {events.map((ev) => (
            <div key={ev.name} className="flex items-center justify-between px-5 py-3">
              <div className="flex items-center gap-3">
                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600/10 text-blue-400">
                  <ArrowRight size={14} />
                </div>
                <div>
                  <p className="text-sm font-medium text-white font-mono">{ev.name}</p>
                  <p className="text-xs text-neutral-500">{ev.description}</p>
                </div>
              </div>
              <span className="inline-flex items-center rounded-full bg-neutral-800 px-2 py-0.5 text-xs font-medium text-neutral-400">
                {ev.version}
              </span>
            </div>
          ))}
        </div>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900 p-5">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-sm font-medium text-white">Webhook Management</h2>
            <p className="text-xs text-neutral-400 mt-1">Create and manage your webhook endpoints</p>
          </div>
          <button
            disabled
            className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50"
          >
            Add Endpoint
          </button>
        </div>
        <div className="mt-4 rounded-lg bg-neutral-800 px-4 py-3 text-center text-sm text-neutral-500">
          Webhook management will be available in the next release. Configure endpoints via API in the meantime.
        </div>
      </div>
    </div>
  )
}

export default function WebhooksPage() {
  return (
    <AppShell>
      <WebhooksContent />
    </AppShell>
  )
}
