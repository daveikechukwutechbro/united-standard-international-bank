"use client"

import { useState } from "react"
import { useQuery } from "@tanstack/react-query"
import { motion } from "framer-motion"
import {
  Users, Building2, ArrowUpDown, Wallet, Shield,
  Clock, CheckCircle2, XCircle, Search, Sliders,
  TrendingUp, DollarSign, AlertTriangle
} from "lucide-react"
import { api } from "@/lib/api"
import { formatCurrency, formatDate, cn } from "@/lib/utils"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Skeleton } from "@/components/ui/skeleton"

export default function AdminPage() {
  const [search, setSearch] = useState("")
  const [userTab, setUserTab] = useState<"users" | "transactions">("users")

  const { data: stats, isLoading: statsLoading } = useQuery({
    queryKey: ["admin-stats"],
    queryFn: api.getAdminStats,
  })

  const { data: users, isLoading: usersLoading } = useQuery({
    queryKey: ["admin-users"],
    queryFn: api.getAdminUsers,
  })

  const { data: allTxns } = useQuery({
    queryKey: ["transactions"],
    queryFn: () => api.getTransactions(),
  })

  const filteredUsers = users?.filter(u =>
    !search || u.firstName.toLowerCase().includes(search.toLowerCase()) ||
    u.lastName.toLowerCase().includes(search.toLowerCase()) ||
    u.email.toLowerCase().includes(search.toLowerCase())
  )

  const statCards = [
    { label: "Total Users", value: stats?.totalUsers ?? 0, icon: Users, color: "text-blue-600", bg: "bg-blue-50 dark:bg-blue-900/20" },
    { label: "Total Accounts", value: stats?.totalAccounts ?? 0, icon: Building2, color: "text-green-600", bg: "bg-green-50 dark:bg-green-900/20" },
    { label: "Total Transactions", value: stats?.totalTransactions ?? 0, icon: ArrowUpDown, color: "text-purple-600", bg: "bg-purple-50 dark:bg-purple-900/20" },
    { label: "Total Balance", value: formatCurrency(stats?.totalBalance ?? 0), icon: DollarSign, color: "text-gold-600", bg: "bg-gold-50 dark:bg-gold-900/20" },
    { label: "Pending KYC", value: stats?.pendingKyc ?? 0, icon: Shield, color: "text-amber-600", bg: "bg-amber-50 dark:bg-amber-900/20" },
    { label: "Active Loans", value: stats?.activeLoans ?? 0, icon: TrendingUp, color: "text-indigo-600", bg: "bg-indigo-50 dark:bg-indigo-900/20" },
    { label: "Pending Txns", value: stats?.pendingTransactions ?? 0, icon: Clock, color: "text-rose-600", bg: "bg-rose-50 dark:bg-rose-900/20" },
  ]

  const getStatusColor = (status: string) => {
    switch (status) {
      case "completed": return "text-green-600 bg-green-50 dark:bg-green-900/20"
      case "pending": return "text-amber-600 bg-amber-50 dark:bg-amber-900/20"
      case "failed": return "text-red-600 bg-red-50 dark:bg-red-900/20"
      default: return "text-muted-foreground bg-muted"
    }
  }

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      className="space-y-6"
    >
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Admin Panel</h1>
          <p className="text-sm text-muted-foreground">System monitoring and management</p>
        </div>
        <div className="flex items-center gap-2">
          <Badge variant="outline" className="gap-1">
            <Shield className="h-3 w-3" />
            Admin
          </Badge>
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
        {statsLoading
          ? Array.from({ length: 7 }).map((_, i) => (
              <Card key={i}><CardContent className="p-4"><Skeleton className="h-4 w-20 mb-2" /><Skeleton className="h-6 w-16" /></CardContent></Card>
            ))
          : statCards.map((stat) => (
              <Card key={stat.label}>
                <CardContent className="p-4">
                  <div className="flex items-center gap-2 mb-2">
                    <div className={cn("rounded-lg p-1.5", stat.bg)}>
                      <stat.icon className={cn("h-4 w-4", stat.color)} />
                    </div>
                    <span className="text-xs text-muted-foreground">{stat.label}</span>
                  </div>
                  <p className="text-lg font-bold">{stat.value}</p>
                </CardContent>
              </Card>
            ))
        }
      </div>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <div className="flex items-center gap-4">
            <div className="flex gap-1 rounded-lg bg-muted p-1">
              <Button
                size="sm"
                variant={userTab === "users" ? "default" : "ghost"}
                onClick={() => setUserTab("users")}
                className="text-xs"
              >
                <Users className="h-3.5 w-3.5 mr-1" />
                Users
              </Button>
              <Button
                size="sm"
                variant={userTab === "transactions" ? "default" : "ghost"}
                onClick={() => setUserTab("transactions")}
                className="text-xs"
              >
                <ArrowUpDown className="h-3.5 w-3.5 mr-1" />
                Transactions
              </Button>
            </div>
            {userTab === "users" && (
              <div className="relative">
                <Search className="absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  placeholder="Search users..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  className="h-9 w-48 pl-9 text-sm"
                />
              </div>
            )}
          </div>
        </CardHeader>
        <CardContent>
          {userTab === "users" ? (
            usersLoading ? (
              <div className="space-y-3">
                {Array.from({ length: 4 }).map((_, i) => <Skeleton key={i} className="h-14 w-full" />)}
              </div>
            ) : !filteredUsers || filteredUsers.length === 0 ? (
              <div className="flex flex-col items-center py-10 text-center">
                <Users className="h-10 w-10 text-muted-foreground mb-3" />
                <p className="text-sm font-medium">No users found</p>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b text-left text-xs text-muted-foreground">
                      <th className="pb-3 font-medium">Name</th>
                      <th className="pb-3 font-medium">Email</th>
                      <th className="pb-3 font-medium">Status</th>
                      <th className="pb-3 font-medium">2FA</th>
                      <th className="pb-3 font-medium">Accounts</th>
                      <th className="pb-3 font-medium">Total Balance</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredUsers.map((u) => (
                      <tr key={u.id} className="border-b last:border-0">
                        <td className="py-3 font-medium">{u.firstName} {u.lastName}</td>
                        <td className="py-3 text-muted-foreground">{u.email}</td>
                        <td className="py-3">
                          <Badge variant={u.kycStatus === "approved" ? "success" : "warning"}>
                            {u.kycStatus}
                          </Badge>
                        </td>
                        <td className="py-3">
                          {u.twoFactorEnabled
                            ? <CheckCircle2 className="h-4 w-4 text-green-500" />
                            : <XCircle className="h-4 w-4 text-muted-foreground" />
                          }
                        </td>
                        <td className="py-3">{u.totalAccounts}</td>
                        <td className="py-3 font-medium">{formatCurrency(u.totalBalance)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )
          ) : (
            <div className="overflow-x-auto">
              {!allTxns || allTxns.length === 0 ? (
                <div className="flex flex-col items-center py-10 text-center">
                  <ArrowUpDown className="h-10 w-10 text-muted-foreground mb-3" />
                  <p className="text-sm font-medium">No transactions</p>
                </div>
              ) : (
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b text-left text-xs text-muted-foreground">
                      <th className="pb-3 font-medium">Date</th>
                      <th className="pb-3 font-medium">Description</th>
                      <th className="pb-3 font-medium">Type</th>
                      <th className="pb-3 font-medium">Amount</th>
                      <th className="pb-3 font-medium">Status</th>
                      <th className="pb-3 font-medium">Reference</th>
                    </tr>
                  </thead>
                  <tbody>
                    {allTxns.slice(0, 20).map((txn) => (
                      <tr key={txn.id} className="border-b last:border-0">
                        <td className="py-2.5 text-muted-foreground text-xs">{formatDate(txn.createdAt)}</td>
                        <td className="py-2.5 font-medium">{txn.description}</td>
                        <td className="py-2.5">
                          <Badge variant="outline" className="capitalize">{txn.type}</Badge>
                        </td>
                        <td className={cn(
                          "py-2.5 font-medium",
                          txn.type === "credit" ? "text-green-600" : "text-red-600"
                        )}>
                          {txn.type === "credit" ? "+" : "-"}{formatCurrency(txn.amount, txn.currency)}
                        </td>
                        <td className="py-2.5">
                          <span className={cn("inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium", getStatusColor(txn.status))}>
                            {txn.status === "pending" && <Clock className="h-3 w-3" />}
                            {txn.status === "completed" && <CheckCircle2 className="h-3 w-3" />}
                            {txn.status === "failed" && <XCircle className="h-3 w-3" />}
                            {txn.status}
                          </span>
                        </td>
                        <td className="py-2.5 font-mono text-xs text-muted-foreground">{txn.reference.slice(-10)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>
          )}
        </CardContent>
      </Card>
    </motion.div>
  )
}
