"use client"

import { useState } from "react"
import { useQuery, useMutation } from "@apollo/client"
import { GET_ORDERS, GET_TRADES, GET_ORDER_BOOKS } from "@/lib/graphql/queries"
import { PLACE_ORDER, CANCEL_ORDER } from "@/lib/graphql/mutations"
import { AppShell } from "@/components/layout/AppShell"

function ExchangeContent() {
  const { data: ordersData, refetch: refetchOrders } = useQuery(GET_ORDERS, { variables: { first: 50 } })
  const { data: tradesData } = useQuery(GET_TRADES, { variables: { first: 50 } })
  const { data: orderBooksData } = useQuery(GET_ORDER_BOOKS, { variables: { first: 50 } })
  const [placeOrder] = useMutation(PLACE_ORDER)
  const [cancelOrder] = useMutation(CANCEL_ORDER)

  const [form, setForm] = useState({
    base_currency: "",
    quote_currency: "",
    type: "limit",
    order_type: "buy",
    amount: "",
    price: "",
  })

  const [placing, setPlacing] = useState(false)

  const orders = ordersData?.orders?.data || []
  const trades = tradesData?.trades?.data || []
  const orderBooks = orderBooksData?.orderBooks?.data || []

  const handlePlaceOrder = async (e: React.FormEvent) => {
    e.preventDefault()
    setPlacing(true)
    try {
      await placeOrder({
        variables: {
          input: {
            base_currency: form.base_currency,
            quote_currency: form.quote_currency,
            type: form.type,
            order_type: form.order_type,
            amount: parseFloat(form.amount),
            price: form.price ? parseFloat(form.price) : null,
          },
        },
      })
      setForm({ base_currency: "", quote_currency: "", type: "limit", order_type: "buy", amount: "", price: "" })
      refetchOrders()
    } catch {
    } finally {
      setPlacing(false)
    }
  }

  const handleCancelOrder = async (orderId: string) => {
    try {
      await cancelOrder({ variables: { order_id: orderId } })
      refetchOrders()
    } catch {
    }
  }

  const statusBadge = (status: string) => {
    const styles: Record<string, string> = {
      open: "bg-green-500/10 text-green-400",
      filled: "bg-blue-500/10 text-blue-400",
      cancelled: "bg-red-500/10 text-red-400",
      pending: "bg-yellow-500/10 text-yellow-400",
    }
    return `rounded-full px-2 py-0.5 text-xs font-medium ${styles[status] || "bg-neutral-500/10 text-neutral-400"}`
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-white">Exchange</h1>
        <p className="text-sm text-neutral-400">Trade digital assets and view market data</p>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900 mb-6">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Place Order</h2>
        </div>
        <form onSubmit={handlePlaceOrder} className="p-5">
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
              <label className="mb-1.5 block text-xs font-medium text-neutral-400">Base Currency</label>
              <input
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="e.g. BTC"
                value={form.base_currency}
                onChange={(e) => setForm({ ...form, base_currency: e.target.value })}
                required
              />
            </div>
            <div>
              <label className="mb-1.5 block text-xs font-medium text-neutral-400">Quote Currency</label>
              <input
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="e.g. USDT"
                value={form.quote_currency}
                onChange={(e) => setForm({ ...form, quote_currency: e.target.value })}
                required
              />
            </div>
            <div>
              <label className="mb-1.5 block text-xs font-medium text-neutral-400">Type</label>
              <select
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                value={form.type}
                onChange={(e) => setForm({ ...form, type: e.target.value })}
              >
                <option value="market">Market</option>
                <option value="limit">Limit</option>
              </select>
            </div>
            <div>
              <label className="mb-1.5 block text-xs font-medium text-neutral-400">Order Type</label>
              <select
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                value={form.order_type}
                onChange={(e) => setForm({ ...form, order_type: e.target.value })}
              >
                <option value="buy">Buy</option>
                <option value="sell">Sell</option>
              </select>
            </div>
            <div>
              <label className="mb-1.5 block text-xs font-medium text-neutral-400">Amount</label>
              <input
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                type="number"
                step="any"
                placeholder="0.00"
                value={form.amount}
                onChange={(e) => setForm({ ...form, amount: e.target.value })}
                required
              />
            </div>
            <div>
              <label className="mb-1.5 block text-xs font-medium text-neutral-400">Price</label>
              <input
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                type="number"
                step="any"
                placeholder="0.00"
                value={form.price}
                onChange={(e) => setForm({ ...form, price: e.target.value })}
                disabled={form.type === "market"}
              />
            </div>
          </div>
          <div className="mt-4">
            <button
              type="submit"
              disabled={placing}
              className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50"
            >
              {placing ? "Placing..." : "Place Order"}
            </button>
          </div>
        </form>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900 mb-6">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Order Books</h2>
        </div>
        {orderBooks.length === 0 ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">No order books available</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-neutral-800 text-left text-xs text-neutral-400">
                  <th className="px-5 py-3 font-medium">Base</th>
                  <th className="px-5 py-3 font-medium">Quote</th>
                  <th className="px-5 py-3 font-medium">Best Bid</th>
                  <th className="px-5 py-3 font-medium">Best Ask</th>
                  <th className="px-5 py-3 font-medium">Last Price</th>
                  <th className="px-5 py-3 font-medium">Volume 24h</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-neutral-800">
                {orderBooks.map((ob: any) => (
                  <tr key={ob.id} className="text-white">
                    <td className="px-5 py-3 font-medium">{ob.base_currency}</td>
                    <td className="px-5 py-3 text-neutral-400">{ob.quote_currency}</td>
                    <td className="px-5 py-3 text-green-400">{ob.best_bid ?? "—"}</td>
                    <td className="px-5 py-3 text-red-400">{ob.best_ask ?? "—"}</td>
                    <td className="px-5 py-3">{ob.last_price ?? "—"}</td>
                    <td className="px-5 py-3 text-neutral-400">{ob.volume_24h?.toLocaleString() ?? "—"}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <div className="rounded-xl border border-neutral-800 bg-neutral-900">
          <div className="border-b border-neutral-800 px-5 py-4">
            <h2 className="text-sm font-medium text-white">Open Orders</h2>
          </div>
          {orders.filter((o: any) => o.status === "open" || o.status === "pending").length === 0 ? (
            <p className="px-5 py-8 text-center text-sm text-neutral-500">No open orders</p>
          ) : (
            <div className="divide-y divide-neutral-800">
              {orders.filter((o: any) => o.status === "open" || o.status === "pending").map((o: any) => (
                <div key={o.id} className="flex items-center justify-between px-5 py-3">
                  <div className="flex items-center gap-3">
                    <div>
                      <p className="text-sm text-white">{o.base_currency}/{o.quote_currency}</p>
                      <p className="text-xs text-neutral-500 capitalize">{o.order_type} • {o.type}</p>
                    </div>
                    <span className={statusBadge(o.status)}>{o.status}</span>
                  </div>
                  <div className="flex items-center gap-3">
                    <div className="text-right">
                      <p className="text-sm font-medium text-white">{o.amount} {o.base_currency}</p>
                      {o.price && <p className="text-xs text-neutral-400">@{o.price}</p>}
                      {o.filled_amount > 0 && (
                        <p className="text-xs text-neutral-500">{o.filled_amount} filled</p>
                      )}
                    </div>
                    <button
                      onClick={() => handleCancelOrder(o.order_id)}
                      className="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-500"
                    >
                      Cancel
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        <div className="rounded-xl border border-neutral-800 bg-neutral-900">
          <div className="border-b border-neutral-800 px-5 py-4">
            <h2 className="text-sm font-medium text-white">Recent Trades</h2>
          </div>
          {trades.length === 0 ? (
            <p className="px-5 py-8 text-center text-sm text-neutral-500">No trades yet</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-neutral-800 text-left text-xs text-neutral-400">
                    <th className="px-5 py-3 font-medium">Pair</th>
                    <th className="px-5 py-3 font-medium">Price</th>
                    <th className="px-5 py-3 font-medium">Amount</th>
                    <th className="px-5 py-3 font-medium">Value</th>
                    <th className="px-5 py-3 font-medium">Time</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-neutral-800">
                  {trades.map((t: any) => (
                    <tr key={t.id} className="text-white">
                      <td className="px-5 py-3 font-medium">{t.base_currency}/{t.quote_currency}</td>
                      <td className="px-5 py-3 text-green-400">{t.price}</td>
                      <td className="px-5 py-3">{t.amount}</td>
                      <td className="px-5 py-3 text-neutral-400">{t.value?.toLocaleString()}</td>
                      <td className="px-5 py-3 text-xs text-neutral-500">
                        {t.created_at ? new Date(t.created_at).toLocaleString() : "—"}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

export default function ExchangePage() {
  return (
    <AppShell>
      <ExchangeContent />
    </AppShell>
  )
}
