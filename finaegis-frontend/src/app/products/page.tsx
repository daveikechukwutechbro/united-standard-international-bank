"use client"

import { useQuery } from "@apollo/client"
import { GET_PRODUCTS } from "@/lib/graphql/queries"
import { AppShell } from "@/components/layout/AppShell"
import { Package } from "lucide-react"

const statusBadge: Record<string, string> = {
  active: "bg-green-500/10 text-green-400",
  pending: "bg-yellow-500/10 text-yellow-400",
  draft: "bg-neutral-500/10 text-neutral-400",
  archived: "bg-red-500/10 text-red-400",
}

function ProductsContent() {
  const { data, loading } = useQuery(GET_PRODUCTS, { variables: { first: 100 } })
  const products = data?.products?.data || []

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-white mb-1">Products</h1>
        <p className="text-sm text-neutral-400 mb-6">Banking product catalog</p>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">All Products</h2>
        </div>
        {loading ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">Loading...</p>
        ) : products.length === 0 ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">No products found</p>
        ) : (
          <div className="divide-y divide-neutral-800">
            {products.map((p: any) => (
              <div key={p.id} className="flex items-start justify-between px-5 py-3">
                <div className="flex items-start gap-3 min-w-0">
                  <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600/10 text-blue-400 shrink-0">
                    <Package size={14} />
                  </div>
                  <div className="min-w-0">
                    <p className="text-sm font-medium text-white">{p.name}</p>
                    <p className="text-xs text-neutral-500 mt-0.5">{p.description}</p>
                    <div className="flex items-center gap-2 mt-1">
                      <span className="text-xs text-neutral-500 capitalize">{p.category}</span>
                      <span className="text-neutral-600">&middot;</span>
                      <span className="text-xs text-neutral-500 capitalize">{p.type}</span>
                      {p.features && p.features.length > 0 && (
                        <>
                          <span className="text-neutral-600">&middot;</span>
                          <span className="text-xs text-neutral-500">{p.features.join(", ")}</span>
                        </>
                      )}
                    </div>
                  </div>
                </div>
                <span
                  className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize shrink-0 ${
                    statusBadge[p.status] || "bg-neutral-500/10 text-neutral-400"
                  }`}
                >
                  {p.status}
                </span>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}

export default function ProductsPage() {
  return (
    <AppShell>
      <ProductsContent />
    </AppShell>
  )
}
