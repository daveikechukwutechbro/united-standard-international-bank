"use client"

import { useState } from "react"
import Link from "next/link"
import { useQuery } from "@tanstack/react-query"
import { motion } from "framer-motion"
import { Plus, Search, Wallet, ChevronRight, Building2, SlidersHorizontal } from "lucide-react"
import { api } from "@/lib/api"
import { formatCurrency, maskAccountNumber, cn } from "@/lib/utils"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Skeleton } from "@/components/ui/skeleton"

const accountTypeColors: Record<string, { bg: string; text: string; label: string }> = {
  checking: { bg: "bg-blue-50 dark:bg-blue-900/20", text: "text-blue-600", label: "Checking" },
  savings: { bg: "bg-green-50 dark:bg-green-900/20", text: "text-green-600", label: "Savings" },
  fixed_deposit: { bg: "bg-amber-50 dark:bg-amber-900/20", text: "text-amber-600", label: "Fixed Deposit" },
  credit: { bg: "bg-purple-50 dark:bg-purple-900/20", text: "text-purple-600", label: "Credit" },
  loan: { bg: "bg-red-50 dark:bg-red-900/20", text: "text-red-600", label: "Loan" },
}

export default function AccountsPage() {
  const [search, setSearch] = useState("")
  const [filterType, setFilterType] = useState<string | null>(null)

  const { data: accounts, isLoading, error } = useQuery({
    queryKey: ["accounts"],
    queryFn: api.getAccounts,
  })

  const filtered = (accounts ?? []).filter((a) => {
    const matchesSearch = !search || 
      a.accountName.toLowerCase().includes(search.toLowerCase()) ||
      a.accountNumber.includes(search)
    const matchesType = !filterType || a.accountType === filterType
    return matchesSearch && matchesType
  })

  const types = Array.from(new Set((accounts ?? []).map((a) => a.accountType)))

  if (error) {
    return (
      <div className="flex flex-col items-center justify-center py-20">
        <p className="text-lg font-semibold mb-2">Failed to load accounts</p>
        <p className="text-muted-foreground mb-4">Please try again</p>
        <Button onClick={() => window.location.reload()}>Retry</Button>
      </div>
    )
  }

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      className="space-y-6"
    >
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Accounts</h1>
          <p className="text-sm text-muted-foreground">Manage all your bank accounts</p>
        </div>
        <Button className="gap-2">
          <Plus className="h-4 w-4" />
          Open New Account
        </Button>
      </div>

      <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
        <div className="relative flex-1 max-w-md">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search accounts..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="pl-9"
          />
        </div>
        <div className="flex items-center gap-2 overflow-x-auto">
          <Button
            variant={filterType === null ? "default" : "outline"}
            size="sm"
            onClick={() => setFilterType(null)}
          >
            All
          </Button>
          {types.map((type) => (
            <Button
              key={type}
              variant={filterType === type ? "default" : "outline"}
              size="sm"
              onClick={() => setFilterType(type)}
              className="capitalize"
            >
              {type.replace("_", " ")}
            </Button>
          ))}
          <Button variant="ghost" size="icon" className="shrink-0">
            <SlidersHorizontal className="h-4 w-4" />
          </Button>
        </div>
      </div>

      {isLoading ? (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {Array.from({ length: 4 }).map((_, i) => (
            <Card key={i}>
              <CardContent className="p-6">
                <Skeleton className="h-12 w-12 rounded-xl mb-4" />
                <Skeleton className="h-5 w-32 mb-2" />
                <Skeleton className="h-4 w-24 mb-3" />
                <Skeleton className="h-8 w-28 mb-2" />
                <Skeleton className="h-3 w-20" />
              </CardContent>
            </Card>
          ))}
        </div>
      ) : filtered.length === 0 ? (
        <div className="flex flex-col items-center py-20 text-center">
          <Building2 className="h-12 w-12 text-muted-foreground mb-4" />
          <h3 className="text-lg font-semibold mb-1">No accounts found</h3>
          <p className="text-sm text-muted-foreground mb-4">
            {search || filterType ? "Try adjusting your search or filters" : "You don't have any accounts yet"}
          </p>
          {!search && !filterType && (
            <Button className="gap-2">
              <Plus className="h-4 w-4" />
              Open Your First Account
            </Button>
          )}
        </div>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {filtered.map((account) => {
            const colors = accountTypeColors[account.accountType] || accountTypeColors.checking
            return (
              <Link key={account.id} href={`/dashboard/accounts/${account.id}`}>
                <Card className="group cursor-pointer transition-all hover:shadow-card-hover">
                  <CardContent className="p-6">
                    <div className="flex items-start justify-between mb-4">
                      <div className={cn("rounded-xl p-3", colors.bg, colors.text)}>
                        <Wallet className="h-6 w-6" />
                      </div>
                      <ChevronRight className="h-4 w-4 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100" />
                    </div>
                    <h3 className="font-semibold mb-1">{account.accountName}</h3>
                    <div className="flex items-center gap-2 mb-3">
                      <Badge variant={account.status === "active" ? "success" : "warning"}>{account.status}</Badge>
                      <Badge variant="outline">{colors.label}</Badge>
                    </div>
                    <p className="text-xl font-bold mb-1">
                      {formatCurrency(account.balance, account.currency)}
                    </p>
                    <p className="text-xs text-muted-foreground font-mono">
                      {maskAccountNumber(account.accountNumber)}
                    </p>
                  </CardContent>
                </Card>
              </Link>
            )
          })}
        </div>
      )}
    </motion.div>
  )
}
