"use client"

import { useState } from "react"
import { useQuery, useLazyQuery } from "@apollo/client"
import { GET_ILP_ASSETS } from "@/lib/graphql/queries"
import { gql } from "@apollo/client"
import { AppShell } from "@/components/layout/AppShell"
import { Network } from "lucide-react"

const GET_ILP_QUOTE = gql`
  query IlpQuote($sendAsset: String!, $receiveAsset: String!, $sendAmount: String!) {
    ilpQuote(send_asset: $sendAsset, receive_asset: $receiveAsset, send_amount: $sendAmount) {
      send_asset
      send_amount
      receive_asset
      receive_amount
      exchange_rate
      fee
      expires_at
    }
  }
`

function InterledgerContent() {
  const { data: assetsData, loading: assetsLoading } = useQuery(GET_ILP_ASSETS)
  const [getQuote, { data: quoteData, loading: quoteLoading }] = useLazyQuery(GET_ILP_QUOTE)

  const [sendAsset, setSendAsset] = useState("")
  const [receiveAsset, setReceiveAsset] = useState("")
  const [sendAmount, setSendAmount] = useState("")

  const assets = assetsData?.ilpSupportedAssets || []

  const handleGetQuote = async (e: React.FormEvent) => {
    e.preventDefault()
    getQuote({
      variables: {
        sendAsset,
        receiveAsset,
        sendAmount,
      },
    })
  }

  const quote = quoteData?.ilpQuote

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-white mb-1">Interledger</h1>
        <p className="text-sm text-neutral-400 mb-6">ILP protocol assets and quoting</p>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Supported Assets</h2>
        </div>
        {assetsLoading ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">Loading...</p>
        ) : assets.length === 0 ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">No assets available</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-neutral-800 text-left text-xs text-neutral-400">
                  <th className="px-5 py-3 font-medium">Code</th>
                  <th className="px-5 py-3 font-medium">Scale</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-neutral-800">
                {assets.map((a: any, i: number) => (
                  <tr key={i} className="text-white">
                    <td className="px-5 py-3 font-medium">{a.code}</td>
                    <td className="px-5 py-3 text-neutral-400">{a.scale}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Get Quote</h2>
        </div>
        <form onSubmit={handleGetQuote} className="p-5">
          <div className="grid gap-4 sm:grid-cols-3 mb-4">
            <div>
              <label className="mb-1.5 block text-xs font-medium text-neutral-400">Send Asset</label>
              <input
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="e.g. USD"
                value={sendAsset}
                onChange={(e) => setSendAsset(e.target.value)}
                required
              />
            </div>
            <div>
              <label className="mb-1.5 block text-xs font-medium text-neutral-400">Receive Asset</label>
              <input
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="e.g. EUR"
                value={receiveAsset}
                onChange={(e) => setReceiveAsset(e.target.value)}
                required
              />
            </div>
            <div>
              <label className="mb-1.5 block text-xs font-medium text-neutral-400">Send Amount</label>
              <input
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                type="number"
                step="any"
                placeholder="100.00"
                value={sendAmount}
                onChange={(e) => setSendAmount(e.target.value)}
                required
              />
            </div>
          </div>
          <button
            type="submit"
            disabled={quoteLoading || !sendAsset || !receiveAsset || !sendAmount}
            className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50 flex items-center gap-2"
          >
            <Network size={16} />
            {quoteLoading ? "Getting Quote..." : "Get Quote"}
          </button>
        </form>

        {quote && (
          <div className="border-t border-neutral-800 px-5 py-4 space-y-2">
            <h3 className="text-sm font-medium text-white">Quote Result</h3>
            <div className="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span className="text-neutral-400">Send</span>
                <p className="text-white">{quote.send_amount} {quote.send_asset}</p>
              </div>
              <div>
                <span className="text-neutral-400">Receive</span>
                <p className="text-white">{quote.receive_amount} {quote.receive_asset}</p>
              </div>
              <div>
                <span className="text-neutral-400">Exchange Rate</span>
                <p className="text-white">{quote.exchange_rate}</p>
              </div>
              <div>
                <span className="text-neutral-400">Fee</span>
                <p className="text-white">{quote.fee}</p>
              </div>
              <div>
                <span className="text-neutral-400">Expires</span>
                <p className="text-white">{quote.expires_at ? new Date(quote.expires_at).toLocaleString() : "—"}</p>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}

export default function InterledgerPage() {
  return (
    <AppShell>
      <InterledgerContent />
    </AppShell>
  )
}
