"use client"

import { useQuery } from "@tanstack/react-query"
import { motion } from "framer-motion"
import {
  Wallet, ArrowUpRight, ArrowDownRight, Clock, Landmark,
  Download, Receipt, RefreshCw,
  ChevronRight, CircleDollarSign, TrendingUp, Phone,
  Mail, MessageCircle, AlertTriangle, CheckCircle2,
  ArrowRight, Headphones
} from "lucide-react"
import { api } from "@/lib/api"
import { auth } from "@/lib/auth"
import { formatCurrency, formatDate, maskAccountNumber, cn } from "@/lib/utils"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Skeleton } from "@/components/ui/skeleton"
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer } from "recharts"

const spendingData = [
  { category: "Business", amount: 3170 },
  { category: "Shopping", amount: 345 },
  { category: "Entertainment", amount: 90 },
  { category: "Savings", amount: 2000 },
  { category: "Fees", amount: 5 },
]

const timelineData = [
  { id: "1", title: "Wire Transfer Sent", description: "$1,250.00 to Johnson Supplies Ltd", time: "2 hours ago", type: "debit" },
  { id: "2", title: "Salary Credited", description: "$5,200.00 from TechCorp International", time: "Yesterday", type: "credit" },
  { id: "3", title: "Card Payment", description: "$345.20 at Amazon.com", time: "2 days ago", type: "debit" },
  { id: "4", title: "Subscription Renewed", description: "$89.99 for Netflix", time: "3 days ago", type: "debit" },
  { id: "5", title: "Transfer to Savings", description: "$2,000.00 transferred", time: "5 days ago", type: "transfer" },
]

const quickActions = [
  { label: "Send Money", icon: ArrowUpRight, desc: "Transfer to any account", href: "/dashboard/transfers", color: "bg-blue-50 dark:bg-blue-900/20 text-blue-600" },
  { label: "Deposit", icon: Download, desc: "Add funds to account", href: "/dashboard/deposit", color: "bg-green-50 dark:bg-green-900/20 text-green-600" },
  { label: "Pay Bills", icon: Receipt, desc: "Pay utilities & services", href: "/dashboard/transfers", color: "bg-purple-50 dark:bg-purple-900/20 text-purple-600" },
  { label: "Exchange", icon: RefreshCw, desc: "Convert currencies", href: "/dashboard/exchange", color: "bg-amber-50 dark:bg-amber-900/20 text-amber-600" },
]

const containerVariants = {
  hidden: { opacity: 0 },
  visible: {
    opacity: 1,
    transition: { staggerChildren: 0.08 },
  },
}

const itemVariants = {
  hidden: { y: 20, opacity: 0 },
  visible: { y: 0, opacity: 1, transition: { type: "spring", stiffness: 100 } },
}

function getGreeting() {
  const hour = new Date().getHours()
  if (hour < 12) return "Good morning"
  if (hour < 18) return "Good afternoon"
  return "Good evening"
}

function StatusBadge({ status }: { status: string }) {
  const variants: Record<string, "success" | "warning" | "danger" | "outline"> = {
    completed: "success",
    pending: "warning",
    failed: "danger",
    cancelled: "outline",
    reversed: "outline",
  }
  return <Badge variant={variants[status] || "outline"}>{status}</Badge>
}

export default function DashboardPage() {
  const user = auth.getUser()
  const today = new Date().toLocaleDateString("en-US", { weekday: "long", year: "numeric", month: "long", day: "numeric" })

  const { data: summary, isLoading: summaryLoading, error: summaryError } = useQuery({
    queryKey: ["dashboard-summary"],
    queryFn: api.getDashboardSummary,
  })

  const { data: transactions, isLoading: txnLoading } = useQuery({
    queryKey: ["transactions"],
    queryFn: () => api.getTransactions(),
  })

  if (summaryError) {
    return (
      <div className="flex flex-col items-center justify-center py-20">
        <AlertTriangle className="h-12 w-12 text-red-400 mb-4" />
        <h2 className="text-xl font-semibold mb-2">Failed to load dashboard</h2>
        <p className="text-muted-foreground mb-4">Please try refreshing the page</p>
        <Button onClick={() => window.location.reload()}>Refresh</Button>
      </div>
    )
  }

  return (
    <motion.div
      variants={containerVariants}
      initial="hidden"
      animate="visible"
      className="space-y-6"
    >
      <motion.div variants={itemVariants} className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
            {getGreeting()}, {user?.firstName || "David"}
          </h1>
          <p className="text-sm text-muted-foreground">{today}</p>
        </div>
        <div className="flex items-center gap-2 mt-2 sm:mt-0">
          <Button size="sm" variant="outline" className="gap-1.5">
            <Download className="h-4 w-4" />
            Download Statement
          </Button>
          <Button size="sm" className="gap-1.5">
            <ArrowUpRight className="h-4 w-4" />
            New Transfer
          </Button>
        </div>
      </motion.div>

      <motion.div variants={itemVariants} className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {summaryLoading ? (
          Array.from({ length: 4 }).map((_, i) => (
            <Card key={i}>
              <CardContent className="p-6">
                <Skeleton className="h-4 w-24 mb-3" />
                <Skeleton className="h-8 w-36 mb-2" />
                <Skeleton className="h-3 w-20" />
              </CardContent>
            </Card>
          ))
        ) : (
          <>
            <Card className="relative overflow-hidden">
              <div className="absolute right-0 top-0 h-24 w-24 translate-x-6 -translate-y-6 rounded-full bg-gold-500/10" />
              <CardContent className="p-6">
                <div className="flex items-center gap-2 text-muted-foreground mb-3">
                  <div className="rounded-lg bg-gold-500/10 p-2">
                    <Wallet className="h-4 w-4 text-gold-500" />
                  </div>
                  <span className="text-sm">Total Balance</span>
                </div>
                <p className="text-2xl font-bold">{formatCurrency(summary?.totalBalance ?? 0)}</p>
                <div className="mt-1 flex items-center gap-1 text-xs text-green-600">
                  <TrendingUp className="h-3 w-3" />
                  <span>+2.4% this month</span>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardContent className="p-6">
                <div className="flex items-center gap-2 text-muted-foreground mb-3">
                  <div className="rounded-lg bg-blue-50 p-2 dark:bg-blue-900/20">
                    <CircleDollarSign className="h-4 w-4 text-blue-500" />
                  </div>
                  <span className="text-sm">Available</span>
                </div>
                <p className="text-2xl font-bold">{formatCurrency(summary?.availableBalance ?? 0)}</p>
                <p className="mt-1 text-xs text-muted-foreground">Across all accounts</p>
              </CardContent>
            </Card>

            <Card>
              <CardContent className="p-6">
                <div className="flex items-center gap-2 text-muted-foreground mb-3">
                  <div className="rounded-lg bg-amber-50 p-2 dark:bg-amber-900/20">
                    <Clock className="h-4 w-4 text-amber-500" />
                  </div>
                  <span className="text-sm">Pending</span>
                </div>
                <p className="text-2xl font-bold">{summary?.pendingTransactions ?? 0}</p>
                <p className="mt-1 text-xs text-muted-foreground">Awaiting confirmation</p>
              </CardContent>
            </Card>

            <Card>
              <CardContent className="p-6">
                <div className="flex items-center gap-2 text-muted-foreground mb-3">
                  <div className="rounded-lg bg-purple-50 p-2 dark:bg-purple-900/20">
                    <Landmark className="h-4 w-4 text-purple-500" />
                  </div>
                  <span className="text-sm">Active Loans</span>
                </div>
                <p className="text-2xl font-bold">{summary?.activeLoans ?? 0}</p>
                <p className="mt-1 text-xs text-muted-foreground">
                  {summary?.activeLoans ? "Next payment: Dec 15" : "No active loans"}
                </p>
              </CardContent>
            </Card>
          </>
        )}
      </motion.div>

      <motion.div variants={itemVariants} className="grid gap-6 lg:grid-cols-3">
        <div className="lg:col-span-2 space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Your Accounts</CardTitle>
              <CardDescription>View and manage your accounts</CardDescription>
            </CardHeader>
            <CardContent>
              {summaryLoading ? (
                <div className="space-y-3">
                  {Array.from({ length: 3 }).map((_, i) => (
                    <Skeleton key={i} className="h-24 w-full" />
                  ))}
                </div>
              ) : (
                <div className="space-y-3">
                  {summary?.accounts.map((account) => (
                    <div
                      key={account.id}
                      className="group flex items-center gap-4 rounded-xl border p-4 transition-colors hover:bg-muted/50"
                    >
                      <div className={cn(
                        "flex h-12 w-12 items-center justify-center rounded-xl",
                        account.accountType === "checking" ? "bg-blue-50 text-blue-600 dark:bg-blue-900/20" :
                        account.accountType === "savings" ? "bg-green-50 text-green-600 dark:bg-green-900/20" :
                        "bg-amber-50 text-amber-600 dark:bg-amber-900/20"
                      )}>
                        <Wallet className="h-5 w-5" />
                      </div>
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2">
                          <p className="font-medium">{account.accountName}</p>
                          <Badge variant={account.status === "active" ? "success" : "warning"}>
                            {account.status}
                          </Badge>
                          <Badge variant="outline">{account.accountType}</Badge>
                        </div>
                        <p className="text-sm text-muted-foreground">
                          {maskAccountNumber(account.accountNumber)} &middot; {account.currency}
                        </p>
                      </div>
                      <div className="text-right">
                        <p className="font-semibold">{formatCurrency(account.balance, account.currency)}</p>
                        <p className="text-xs text-muted-foreground">
                          Available: {formatCurrency(account.availableBalance, account.currency)}
                        </p>
                      </div>
                      <ChevronRight className="h-5 w-5 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100" />
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <div>
                <CardTitle>Recent Transactions</CardTitle>
                <CardDescription>Your latest financial activity</CardDescription>
              </div>
              <Button variant="ghost" size="sm" className="gap-1">
                View All <ArrowRight className="h-3.5 w-3.5" />
              </Button>
            </CardHeader>
            <CardContent>
              {txnLoading ? (
                <div className="space-y-3">
                  {Array.from({ length: 5 }).map((_, i) => (
                    <Skeleton key={i} className="h-12 w-full" />
                  ))}
                </div>
              ) : !transactions || transactions.length === 0 ? (
                <div className="flex flex-col items-center py-8 text-center">
                  <Receipt className="h-10 w-10 text-muted-foreground mb-3" />
                  <p className="text-sm font-medium text-foreground">No transactions yet</p>
                  <p className="text-xs text-muted-foreground">Your transactions will appear here</p>
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
                        <th className="pb-3 font-medium">Status</th>
                        <th className="pb-3 font-medium">Ref</th>
                      </tr>
                    </thead>
                    <tbody>
                      {transactions.slice(0, 6).map((txn) => (
                        <tr key={txn.id} className="border-b last:border-0">
                          <td className="py-3 text-muted-foreground">{formatDate(txn.createdAt)}</td>
                          <td className="py-3 font-medium">{txn.description}</td>
                          <td className="py-3">
                            <div className="flex items-center gap-1.5">
                              {txn.type === "credit" ? (
                                <ArrowDownRight className="h-3.5 w-3.5 text-green-500" />
                              ) : (
                                <ArrowUpRight className="h-3.5 w-3.5 text-red-500" />
                              )}
                              <span className="capitalize">{txn.type}</span>
                            </div>
                          </td>
                          <td className={cn(
                            "py-3 font-medium",
                            txn.type === "credit" ? "text-green-600" : "text-red-600"
                          )}>
                            {txn.type === "credit" ? "+" : "-"}{formatCurrency(txn.amount, txn.currency)}
                          </td>
                          <td className="py-3"><StatusBadge status={txn.status} /></td>
                          <td className="py-3 font-mono text-xs text-muted-foreground">{txn.reference.slice(-8)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </CardContent>
          </Card>
        </div>

        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Quick Actions</CardTitle>
              <CardDescription>Common banking tasks</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-2 gap-3">
                {quickActions.map((action) => (
                  <a
                    key={action.label}
                    href={action.href}
                    className="flex flex-col items-center gap-2 rounded-xl border p-4 text-center transition-colors hover:bg-muted/50"
                  >
                    <div className={cn("rounded-lg p-2.5", action.color)}>
                      <action.icon className="h-5 w-5" />
                    </div>
                    <p className="text-sm font-medium">{action.label}</p>
                    <p className="text-[10px] text-muted-foreground leading-tight">{action.desc}</p>
                  </a>
                ))}
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Spending Overview</CardTitle>
              <CardDescription>By category this month</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="h-48">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={summary?.spendingByCategory ?? spendingData} margin={{ top: 0, right: 0, left: -20, bottom: 0 }}>
                    <XAxis dataKey="category" tick={{ fontSize: 10 }} axisLine={false} tickLine={false} />
                    <YAxis hide />
                    <Tooltip
                      contentStyle={{ borderRadius: "12px", border: "1px solid var(--border)", background: "var(--card)", fontSize: "12px" }}
                      formatter={(value: number) => [formatCurrency(value), "Amount"]}
                    />
                    <Bar dataKey="amount" fill="#d4a843" radius={[6, 6, 0, 0]} />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Activity Timeline</CardTitle>
              <CardDescription>Recent account activity</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="relative space-y-4 before:absolute before:left-[7px] before:top-2 before:h-[calc(100%-16px)] before:w-0.5 before:bg-border">
                {timelineData.map((item) => (
                  <div key={item.id} className="flex gap-3">
                    <div className={cn(
                      "relative z-10 mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2",
                      item.type === "credit" ? "border-green-500 bg-green-50 dark:bg-green-900/20" :
                      item.type === "debit" ? "border-red-500 bg-red-50 dark:bg-red-900/20" :
                      "border-blue-500 bg-blue-50 dark:bg-blue-900/20"
                    )}>
                      <div className={cn(
                        "h-1.5 w-1.5 rounded-full",
                        item.type === "credit" ? "bg-green-500" :
                        item.type === "debit" ? "bg-red-500" :
                        "bg-blue-500"
                      )} />
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="text-sm font-medium">{item.title}</p>
                      <p className="text-xs text-muted-foreground">{item.description}</p>
                      <p className="text-[10px] text-muted-foreground">{item.time}</p>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Security Status</CardTitle>
              <CardDescription>Your account protection</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                <div className="flex items-center gap-3 rounded-lg bg-green-50 p-3 dark:bg-green-900/10">
                  <CheckCircle2 className="h-5 w-5 text-green-500 shrink-0" />
                  <div>
                    <p className="text-sm font-medium">2FA Enabled</p>
                    <p className="text-xs text-muted-foreground">Your account is secured</p>
                  </div>
                </div>
                <div className="flex items-center gap-3 rounded-lg bg-green-50 p-3 dark:bg-green-900/10">
                  <CheckCircle2 className="h-5 w-5 text-green-500 shrink-0" />
                  <div>
                    <p className="text-sm font-medium">KYC Verified</p>
                    <p className="text-xs text-muted-foreground">Identity confirmed</p>
                  </div>
                </div>
                <div className="flex items-center gap-3 rounded-lg bg-amber-50 p-3 dark:bg-amber-900/10">
                  <AlertTriangle className="h-5 w-5 text-amber-500 shrink-0" />
                  <div>
                    <p className="text-sm font-medium">New Device Login</p>
                    <p className="text-xs text-muted-foreground">Reviewed recently</p>
                  </div>
                </div>
              </div>
              <Button variant="link" size="sm" className="mt-2 h-auto p-0 text-xs">
                View security details
              </Button>
            </CardContent>
          </Card>

          <Card className="bg-gradient-to-br from-usib-500 to-usib-700 text-white">
            <CardContent className="p-6">
              <div className="flex items-center gap-3 mb-4">
                <div className="rounded-lg bg-gold-500/20 p-2">
                  <Headphones className="h-5 w-5 text-gold-500" />
                </div>
                <div>
                  <p className="font-semibold">Need Help?</p>
                  <p className="text-sm text-white/70">We&apos;re here 24/7</p>
                </div>
              </div>
              <div className="space-y-2">
                <a href="tel:+15551234567" className="flex items-center gap-2 text-sm text-white/80 hover:text-white">
                  <Phone className="h-3.5 w-3.5" />
                  +1 (555) 123-4567
                </a>
                <a href="mailto:support@usib.com" className="flex items-center gap-2 text-sm text-white/80 hover:text-white">
                  <Mail className="h-3.5 w-3.5" />
                  support@usib.com
                </a>
                <a href="#" className="flex items-center gap-2 text-sm text-white/80 hover:text-white">
                  <MessageCircle className="h-3.5 w-3.5" />
                  Live Chat
                </a>
              </div>
              <Button variant="accent" size="sm" className="mt-4 w-full">
                Contact Support
              </Button>
            </CardContent>
          </Card>
        </div>
      </motion.div>
    </motion.div>
  )
}
