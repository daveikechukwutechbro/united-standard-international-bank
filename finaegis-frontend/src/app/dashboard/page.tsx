"use client"

import { useQuery } from "@apollo/client"
import { GET_ACCOUNTS, GET_PAYMENTS, GET_ORDERS, GET_LOAN_APPLICATIONS, GET_USER_PROFILE, GET_REWARD_PROFILE } from "@/lib/graphql/queries"
import { Building2, ArrowLeftRight, TrendingUp, HandCoins, DollarSign, Wallet, Activity } from "lucide-react"
import { AppShell } from "@/components/layout/AppShell"

interface StatCardProps {
  title: string
  value: string
  subtitle: string
  icon: React.ReactNode
  trend?: string
}

function StatCard({ title, value, subtitle, icon, trend }: StatCardProps) {
  return (
    <div className="rounded-xl border border-neutral-800 bg-neutral-900 p-4 lg:p-5">
      <div className="flex items-start justify-between">
        <div>
          <p className="text-sm text-neutral-400">{title}</p>
          <p className="mt-1 text-2xl font-semibold text-white">{value}</p>
          <p className="mt-1 text-xs text-neutral-500">{subtitle}</p>
        </div>
        <div className="rounded-lg bg-blue-600/10 p-2.5 text-blue-400">
          {icon}
        </div>
      </div>
      {trend && (
        <div className="mt-3 flex items-center gap-1 text-xs text-green-400">
          <Activity size={12} />
          {trend}
        </div>
      )}
    </div>
  )
}

function DashboardContent() {
  const { data: profileData } = useQuery(GET_USER_PROFILE)
  const { data: accountsData } = useQuery(GET_ACCOUNTS, { variables: { first: 100 } })
  const { data: paymentsData } = useQuery(GET_PAYMENTS, { variables: { first: 5 } })
  const { data: ordersData } = useQuery(GET_ORDERS, { variables: { first: 5 } })
  const { data: loansData } = useQuery(GET_LOAN_APPLICATIONS, { variables: { first: 5 } })
  const { data: rewardsData } = useQuery(GET_REWARD_PROFILE)

  const accounts = accountsData?.accounts?.data || []
  const payments = paymentsData?.payments?.data || []
  const orders = ordersData?.orders?.data || []
  const loans = loansData?.loanApplications?.data || []
  const rewards = rewardsData?.rewardProfile
  const profile = profileData?.userProfile

  const totalBalance = accounts.reduce((sum: number, a: any) => sum + (a.balance || 0), 0).toLocaleString("en-US", { style: "currency", currency: "USD" })
  const activeOrders = orders.filter((o: any) => o.status === "open" || o.status === "pending").length
  const pendingLoans = loans.filter((l: any) => l.status === "pending").length

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold text-white">
            Welcome{profile?.first_name ? `, ${profile.first_name}` : ""}
          </h1>
          <p className="text-sm text-neutral-400">Here&apos;s your financial overview</p>
        </div>
        {rewards && (
          <div className="flex items-center gap-3 rounded-lg border border-neutral-800 bg-neutral-900 px-4 py-2">
            <div className="text-right">
              <p className="text-xs text-neutral-400">Level {rewards.level}</p>
              <p className="text-sm font-medium text-white">{rewards.xp} XP</p>
            </div>
            <div className="h-8 w-8 rounded-full bg-yellow-500/20 flex items-center justify-center text-yellow-400 text-sm font-bold">
              {rewards.level}
            </div>
          </div>
        )}
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard title="Total Balance" value={totalBalance} subtitle="Across all accounts" icon={<DollarSign size={20} />} />
        <StatCard title="Accounts" value={accounts.length.toString()} subtitle="Active accounts" icon={<Building2 size={20} />} />
        <StatCard title="Open Orders" value={activeOrders.toString()} subtitle={activeOrders > 0 ? "Awaiting execution" : "No open orders"} icon={<TrendingUp size={20} />} trend={activeOrders > 0 ? `${activeOrders} order(s) active` : undefined} />
        <StatCard title="Pending Loans" value={pendingLoans.toString()} subtitle={pendingLoans > 0 ? "Awaiting approval" : "No pending applications"} icon={<HandCoins size={20} />} />
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <div className="rounded-xl border border-neutral-800 bg-neutral-900">
          <div className="border-b border-neutral-800 px-5 py-4">
            <h2 className="text-sm font-medium text-white">Recent Transactions</h2>
          </div>
          <div className="divide-y divide-neutral-800">
            {payments.length === 0 ? (
              <p className="px-5 py-8 text-center text-sm text-neutral-500">No recent transactions</p>
            ) : (
              payments.map((p: any) => (
                <div key={p.id} className="flex items-center justify-between px-5 py-3">
                  <div className="flex items-center gap-3">
                    <div className={`flex h-8 w-8 items-center justify-center rounded-full ${
                      p.status === "completed" ? "bg-green-500/10 text-green-400" :
                      p.status === "failed" ? "bg-red-500/10 text-red-400" :
                      "bg-yellow-500/10 text-yellow-400"
                    }`}>
                      <ArrowLeftRight size={14} />
                    </div>
                    <div>
                      <p className="text-sm text-white capitalize">{p.type?.replace("_", " ")}</p>
                      <p className="text-xs text-neutral-500">{p.reference || p.id?.slice(0, 8)}</p>
                    </div>
                  </div>
                  <div className="text-right">
                    <p className="text-sm font-medium text-white">{p.currency} {p.amount?.toLocaleString()}</p>
                    <p className={`text-xs capitalize ${p.status === "completed" ? "text-green-400" : p.status === "failed" ? "text-red-400" : "text-yellow-400"}`}>
                      {p.status}
                    </p>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>

        <div className="rounded-xl border border-neutral-800 bg-neutral-900">
          <div className="border-b border-neutral-800 px-5 py-4">
            <h2 className="text-sm font-medium text-white">Open Orders</h2>
          </div>
          <div className="divide-y divide-neutral-800">
            {orders.filter((o: any) => o.status === "open" || o.status === "pending").length === 0 ? (
              <p className="px-5 py-8 text-center text-sm text-neutral-500">No open orders</p>
            ) : (
              orders.filter((o: any) => o.status === "open" || o.status === "pending").map((o: any) => (
                <div key={o.id} className="flex items-center justify-between px-5 py-3">
                  <div>
                    <p className="text-sm text-white">{o.base_currency}/{o.quote_currency}</p>
                    <p className="text-xs text-neutral-500 capitalize">{o.order_type} • {o.type}</p>
                  </div>
                  <div className="text-right">
                    <p className="text-sm font-medium text-white">{o.amount} {o.base_currency}</p>
                    {o.price && <p className="text-xs text-neutral-400">@{o.price}</p>}
                  </div>
                </div>
              ))
            )}
          </div>
        </div>
      </div>
    </div>
  )
}

export default function DashboardPage() {
  return (
    <AppShell>
      <DashboardContent />
    </AppShell>
  )
}
