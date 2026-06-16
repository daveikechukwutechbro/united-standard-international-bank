"use client"

import { useState } from "react"
import { useParams, useRouter } from "next/navigation"
import Link from "next/link"
import { useQuery } from "@tanstack/react-query"
import { motion } from "framer-motion"
import {
  ArrowLeft, Download, ArrowUpRight, ArrowDownRight, Search,
  ChevronDown, Send, Download as DownloadIcon,
  AlertTriangle
} from "lucide-react"
import { api } from "@/lib/api"
import { formatCurrency, formatDate, maskAccountNumber, cn } from "@/lib/utils"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Skeleton } from "@/components/ui/skeleton"

const statusVariant: Record<string, "success" | "warning" | "danger" | "outline"> = {
  active: "success",
  frozen: "warning",
  closed: "danger",
  pending: "outline",
}

const txnStatusVariant: Record<string, "success" | "warning" | "danger" | "outline"> = {
  completed: "success",
  pending: "warning",
  failed: "danger",
  cancelled: "outline",
  reversed: "outline",
}

export default function AccountDetailPage() {
  const params = useParams()
  const router = useRouter()
  const [txnSearch, setTxnSearch] = useState("")
  const [txnFilter, setTxnFilter] = useState<string | null>(null)

  const { data: account, isLoading: accountLoading, error: accountError } = useQuery({
    queryKey: ["account", params.id],
    queryFn: () => api.getAccount(params.id as string),
  })

  const { data: transactions, isLoading: txnLoading } = useQuery({
    queryKey: ["transactions", params.id],
    queryFn: () => api.getTransactions(params.id as string),
  })

  if (accountLoading) {
    return (
      <div className="space-y-6">
        <Skeleton className="h-8 w-24" />
        <Card>
          <CardContent className="p-6 space-y-4">
            <Skeleton className="h-6 w-48" />
            <Skeleton className="h-10 w-36" />
            <Skeleton className="h-4 w-64" />
          </CardContent>
        </Card>
        <Skeleton className="h-64 w-full" />
      </div>
    )
  }

  if (accountError || !account) {
    return (
      <div className="flex flex-col items-center justify-center py-20">
        <AlertTriangle className="h-12 w-12 text-red-400 mb-4" />
        <h2 className="text-xl font-semibold mb-2">Account Not Found</h2>
        <p className="text-muted-foreground mb-4">The account you&apos;re looking for doesn&apos;t exist</p>
        <Button onClick={() => router.push("/dashboard/accounts")}>
          Back to Accounts
        </Button>
      </div>
    )
  }

  const filtered = (transactions ?? []).filter((t) => {
    const matchesSearch = !txnSearch ||
      t.description.toLowerCase().includes(txnSearch.toLowerCase()) ||
      t.reference.includes(txnSearch)
    const matchesType = !txnFilter || t.type === txnFilter
    return matchesSearch && matchesType
  })

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      className="space-y-6"
    >
      <div className="flex items-center gap-3">
        <Link href="/dashboard/accounts">
          <Button variant="ghost" size="icon">
            <ArrowLeft className="h-5 w-5" />
          </Button>
        </Link>
        <div>
          <h1 className="text-2xl font-bold tracking-tight">{account.accountName}</h1>
          <p className="text-sm text-muted-foreground">
            {account.accountType.replace("_", " ")} &middot; {maskAccountNumber(account.accountNumber)}
          </p>
        </div>
      </div>

      <Card className="bg-gradient-to-br from-usib-500 to-usib-700 text-white">
        <CardContent className="p-6 sm:p-8">
          <div className="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div className="space-y-2">
              <p className="text-sm text-white/70">Current Balance</p>
              <p className="text-4xl font-bold">{formatCurrency(account.balance, account.currency)}</p>
              <div className="flex items-center gap-3 text-sm text-white/60">
                <span>Available: {formatCurrency(account.availableBalance, account.currency)}</span>
                <span>Ledger: {formatCurrency(account.ledgerBalance, account.currency)}</span>
              </div>
            </div>
            <div className="flex flex-col items-start gap-2 sm:items-end">
              <Badge variant={statusVariant[account.status] || "outline"} className="bg-white/20 text-white border-white/30">
                {account.status}
              </Badge>
              <p className="text-xs text-white/60">Opened {formatDate(account.openedAt, "long")}</p>
              {account.interestRate != null && (
                <p className="text-xs text-white/60">Interest Rate: {account.interestRate}%</p>
              )}
            </div>
          </div>
        </CardContent>
      </Card>

      <div className="flex flex-wrap items-center gap-3">
        <Button className="gap-2">
          <Send className="h-4 w-4" />
          Transfer
        </Button>
        <Button variant="outline" className="gap-2">
          <DownloadIcon className="h-4 w-4" />
          Deposit
        </Button>
        <Button variant="outline" className="gap-2">
          <Download className="h-4 w-4" />
          Download Statement
        </Button>
      </div>

      <Card>
        <CardHeader className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <CardTitle>Transactions</CardTitle>
            <CardDescription>All transactions for this account</CardDescription>
          </div>
          <div className="flex items-center gap-2">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                placeholder="Search..."
                value={txnSearch}
                onChange={(e) => setTxnSearch(e.target.value)}
                className="pl-9 h-9 w-48"
              />
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div className="flex items-center gap-2 mb-4 overflow-x-auto">
            {[null, "credit", "debit", "transfer", "payment", "fee"].map((f) => (
              <Button
                key={f ?? "all"}
                variant={txnFilter === f ? "default" : "outline"}
                size="sm"
                onClick={() => setTxnFilter(f)}
                className="capitalize whitespace-nowrap"
              >
                {f ?? "All"}
              </Button>
            ))}
          </div>

          {txnLoading ? (
            <div className="space-y-3">
              {Array.from({ length: 5 }).map((_, i) => (
                <Skeleton key={i} className="h-14 w-full" />
              ))}
            </div>
          ) : filtered.length === 0 ? (
            <div className="flex flex-col items-center py-12 text-center">
              <ArrowDownRight className="h-10 w-10 text-muted-foreground mb-3" />
              <p className="text-sm font-medium">No transactions found</p>
              <p className="text-xs text-muted-foreground">
                {txnSearch || txnFilter ? "Try adjusting your search" : "No transactions recorded yet"}
              </p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left text-xs text-muted-foreground">
                    <th className="pb-3 font-medium">Date</th>
                    <th className="pb-3 font-medium">Description</th>
                    <th className="pb-3 font-medium">Type</th>
                    <th className="pb-3 font-medium">Amount</th>
                    <th className="pb-3 font-medium">Fee</th>
                    <th className="pb-3 font-medium">Status</th>
                    <th className="pb-3 font-medium">Reference</th>
                    <th className="pb-3 font-medium">Balance After</th>
                  </tr>
                </thead>
                <tbody>
                  {filtered.map((txn) => (
                    <tr key={txn.id} className="border-b last:border-0">
                      <td className="py-3 text-muted-foreground whitespace-nowrap">
                        {formatDate(txn.createdAt, "short")}
                      </td>
                      <td className="py-3 font-medium">{txn.description}</td>
                      <td className="py-3">
                        <div className="flex items-center gap-1.5">
                          {txn.type === "credit" || txn.type === "deposit" ? (
                            <ArrowDownRight className="h-3.5 w-3.5 text-green-500" />
                          ) : (
                            <ArrowUpRight className="h-3.5 w-3.5 text-red-500" />
                          )}
                          <span className="capitalize">{txn.type}</span>
                        </div>
                      </td>
                      <td className={cn(
                        "py-3 font-medium whitespace-nowrap",
                        (txn.type === "credit" || txn.type === "deposit") ? "text-green-600" : "text-red-600"
                      )}>
                        {(txn.type === "credit" || txn.type === "deposit") ? "+" : "-"}
                        {formatCurrency(txn.amount, txn.currency)}
                      </td>
                      <td className="py-3 text-muted-foreground">
                        {txn.fee ? formatCurrency(txn.fee) : "—"}
                      </td>
                      <td className="py-3">
                        <Badge variant={txnStatusVariant[txn.status] || "outline"}>{txn.status}</Badge>
                      </td>
                      <td className="py-3 font-mono text-xs text-muted-foreground">
                        {txn.reference.slice(-10)}
                      </td>
                      <td className="py-3 text-muted-foreground whitespace-nowrap">
                        {txn.balanceAfter != null ? formatCurrency(txn.balanceAfter, txn.currency) : "—"}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>
    </motion.div>
  )
}
