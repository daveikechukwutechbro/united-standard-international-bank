"use client"

import { useState } from "react"
import { useParams, useRouter } from "next/navigation"
import { useQuery } from "@tanstack/react-query"
import {
  ArrowLeft, Calendar, DollarSign, Percent, Clock,
  CheckCircle2, Circle, Loader2, FileText, Download,
  CreditCard
} from "lucide-react"
import { api } from "@/lib/api"
import { formatCurrency, formatDate, cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import {
  Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter
} from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Skeleton } from "@/components/ui/skeleton"
import {
  Dialog, DialogContent, DialogHeader, DialogFooter, DialogTitle, DialogDescription, DialogClose
} from "@/components/ui/dialog"
import { useToast } from "@/components/ui/toast"
import type { Loan } from "@/lib/types"

const statusVariant: Record<string, "warning" | "success" | "default" | "danger" | "secondary"> = {
  pending: "warning",
  approved: "success",
  active: "default",
  rejected: "danger",
  funded: "success",
  closed: "secondary",
  defaulted: "danger",
}

const statusSteps = [
  { key: "draft", label: "Draft" },
  { key: "pending", label: "Pending Review" },
  { key: "approved", label: "Approved" },
  { key: "funded", label: "Funded" },
  { key: "active", label: "Active" },
]

function generateSchedule(loan: Loan) {
  if (!loan.monthlyPayment) return []
  const months = loan.termUnit === "years" ? loan.term * 12 : loan.term
  const schedule = []
  let balance = loan.amount
  const monthlyRate = loan.interestRate / 100 / 12
  const paidMonths = loan.amountPaid ? Math.floor(loan.amountPaid / loan.monthlyPayment) : 0

  for (let i = 1; i <= months; i++) {
    const interest = balance * monthlyRate
    const principal = loan.monthlyPayment - interest
    balance -= principal
    schedule.push({
      installment: i,
      date: new Date(Date.now() + i * 30 * 24 * 60 * 60 * 1000).toISOString(),
      amount: loan.monthlyPayment,
      principal,
      interest,
      balance: Math.max(0, balance),
      isPaid: i <= paidMonths,
      isCurrent: i === paidMonths + 1,
    })
  }
  return schedule
}

export default function LoanDetailPage() {
  const params = useParams()
  const router = useRouter()
  const { show, Toast } = useToast()
  const [payDialogOpen, setPayDialogOpen] = useState(false)

  const { data: loan, isLoading, error } = useQuery({
    queryKey: ["loans", params.id],
    queryFn: async () => {
      const loans = await api.getLoans()
      const loan = loans.find(l => l.id === params.id)
      if (!loan) throw new Error("Loan not found")
      return loan
    },
  })

  if (isLoading) {
    return (
      <div className="space-y-6">
        <Skeleton className="h-8 w-64" />
        <div className="grid gap-6 md:grid-cols-3">
          {[1, 2, 3].map(i => <Skeleton key={i} className="h-32 rounded-xl" />)}
        </div>
        <Skeleton className="h-64 rounded-xl" />
      </div>
    )
  }

  if (error || !loan) {
    return (
      <div className="flex flex-col items-center justify-center py-20">
        <p className="text-lg font-medium text-foreground">Loan not found</p>
        <p className="text-sm text-muted-foreground">The loan you are looking for does not exist.</p>
        <Button className="mt-4" onClick={() => router.back()}>Go Back</Button>
      </div>
    )
  }

  const schedule = generateSchedule(loan)
  const progress = loan.amountPaid && loan.amount ? (loan.amountPaid / loan.amount) * 100 : 0
  const currentStatusIndex = statusSteps.findIndex(s => s.key === loan.status)

  return (
    <div className="space-y-8">
      {Toast}
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="icon" onClick={() => router.push("/dashboard/loans")}>
          <ArrowLeft className="h-5 w-5" />
        </Button>
        <div>
          <div className="flex items-center gap-3">
            <h1 className="text-2xl font-bold font-display text-foreground capitalize">
              {loan.loanType} Loan
            </h1>
            <Badge variant={statusVariant[loan.status] || "default"} className="capitalize">
              {loan.status}
            </Badge>
          </div>
          <p className="text-sm text-muted-foreground">Loan ID: {loan.id}</p>
        </div>
      </div>

      <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-usib-100 text-usib-600">
                <DollarSign className="h-5 w-5" />
              </div>
              <div>
                <p className="text-xs text-muted-foreground">Loan Amount</p>
                <p className="text-lg font-bold text-foreground">{formatCurrency(loan.amount, loan.currency)}</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100 text-green-600">
                <Percent className="h-5 w-5" />
              </div>
              <div>
                <p className="text-xs text-muted-foreground">Interest Rate</p>
                <p className="text-lg font-bold text-foreground">{loan.interestRate}% APR</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-gold-100 text-gold-600">
                <Calendar className="h-5 w-5" />
              </div>
              <div>
                <p className="text-xs text-muted-foreground">Term</p>
                <p className="text-lg font-bold text-foreground">{loan.term} {loan.termUnit}</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
                <Clock className="h-5 w-5" />
              </div>
              <div>
                <p className="text-xs text-muted-foreground">Monthly Payment</p>
                <p className="text-lg font-bold text-foreground">
                  {loan.monthlyPayment ? formatCurrency(loan.monthlyPayment, loan.currency) : "-"}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>Repayment Progress</CardTitle>
            <CardDescription>Track your loan repayment progress</CardDescription>
          </CardHeader>
          <CardContent className="space-y-6">
            <div className="space-y-2">
              <div className="flex items-center justify-between text-sm">
                <span className="text-muted-foreground">Progress</span>
                <span className="font-medium text-foreground">{progress.toFixed(1)}%</span>
              </div>
              <div className="h-3 w-full overflow-hidden rounded-full bg-muted">
                <div
                  className="h-full rounded-full bg-gradient-to-r from-gold-400 to-gold-500 transition-all duration-500"
                  style={{ width: `${Math.min(progress, 100)}%` }}
                />
              </div>
              <div className="flex items-center justify-between text-xs text-muted-foreground">
                <span>Paid: {formatCurrency(loan.amountPaid || 0, loan.currency)}</span>
                <span>Remaining: {formatCurrency(loan.remainingBalance || loan.amount, loan.currency)}</span>
              </div>
            </div>

            {loan.nextPaymentDate && (
              <div className="flex items-center gap-3 rounded-lg bg-amber-50 p-4 dark:bg-amber-900/20">
                <Calendar className="h-5 w-5 text-amber-600" />
                <div>
                  <p className="text-sm font-medium text-amber-800 dark:text-amber-200">Next Payment Due</p>
                  <p className="text-xs text-amber-600 dark:text-amber-400">{formatDate(loan.nextPaymentDate, "long")}</p>
                </div>
                <Button size="sm" className="ml-auto" onClick={() => setPayDialogOpen(true)}>
                  Pay Now
                </Button>
              </div>
            )}

            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left text-xs font-medium text-muted-foreground">
                    <th className="pb-3">#</th>
                    <th className="pb-3">Date</th>
                    <th className="pb-3">Amount</th>
                    <th className="pb-3">Principal</th>
                    <th className="pb-3">Interest</th>
                    <th className="pb-3">Balance</th>
                    <th className="pb-3"></th>
                  </tr>
                </thead>
                <tbody>
                  {schedule.slice(0, 12).map((s) => (
                    <tr key={s.installment} className={cn(
                      "border-b last:border-0",
                      s.isPaid ? "text-muted-foreground" : "",
                      s.isCurrent ? "bg-usib-50 dark:bg-usib-800/30 font-medium" : ""
                    )}>
                      <td className="py-3">{s.installment}</td>
                      <td className="py-3">{formatDate(s.date)}</td>
                      <td className="py-3">{formatCurrency(s.amount, loan.currency)}</td>
                      <td className="py-3">{formatCurrency(s.principal, loan.currency)}</td>
                      <td className="py-3">{formatCurrency(s.interest, loan.currency)}</td>
                      <td className="py-3">{formatCurrency(s.balance, loan.currency)}</td>
                      <td className="py-3">
                        {s.isPaid ? (
                          <CheckCircle2 className="h-4 w-4 text-green-500" />
                        ) : s.isCurrent ? (
                          <Circle className="h-4 w-4 text-gold-500" />
                        ) : null}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
              {schedule.length > 12 && (
                <p className="mt-3 text-center text-xs text-muted-foreground">
                  Showing 12 of {schedule.length} installments
                </p>
              )}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Timeline</CardTitle>
            <CardDescription>Application status tracking</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-0">
              {statusSteps.map((step, i) => {
                const isCompleted = currentStatusIndex >= i
                const isCurrent = currentStatusIndex === i
                return (
                  <div key={step.key} className="relative flex gap-4 pb-6 last:pb-0">
                    {i < statusSteps.length - 1 && (
                      <div className={cn(
                        "absolute left-[11px] top-6 h-full w-0.5",
                        isCompleted ? "bg-gold-500" : "bg-muted"
                      )} />
                    )}
                    <div className={cn(
                      "flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2",
                      isCompleted ? "border-gold-500 bg-gold-500" : "border-muted bg-background"
                    )}>
                      {isCompleted ? (
                        <CheckCircle2 className="h-3.5 w-3.5 text-white" />
                      ) : (
                        <div className="h-2 w-2 rounded-full bg-muted" />
                      )}
                    </div>
                    <div className="pt-0.5">
                      <p className={cn(
                        "text-sm font-medium",
                        isCurrent ? "text-gold-500" : isCompleted ? "text-foreground" : "text-muted-foreground"
                      )}>
                        {step.label}
                      </p>
                      {isCurrent && loan.status === "pending" && loan.appliedAt && (
                        <p className="text-xs text-muted-foreground">{formatDate(loan.appliedAt)}</p>
                      )}
                      {isCurrent && loan.status === "approved" && loan.approvedAt && (
                        <p className="text-xs text-muted-foreground">{formatDate(loan.approvedAt)}</p>
                      )}
                      {isCurrent && loan.status === "funded" && loan.fundedAt && (
                        <p className="text-xs text-muted-foreground">{formatDate(loan.fundedAt)}</p>
                      )}
                    </div>
                  </div>
                )
              })}
            </div>
          </CardContent>
          <CardFooter className="flex-col gap-2">
            <Button className="w-full" variant="accent" onClick={() => setPayDialogOpen(true)}>
              <CreditCard className="mr-2 h-4 w-4" />
              Make a Payment
            </Button>
            <Button variant="outline" className="w-full">
              <Download className="mr-2 h-4 w-4" />
              Download Statement
            </Button>
          </CardFooter>
        </Card>
      </div>

      <Dialog open={payDialogOpen} onOpenChange={setPayDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Make a Payment</DialogTitle>
            <DialogDescription>Pay towards your loan balance</DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <div className="rounded-lg bg-muted p-4 space-y-2">
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Outstanding Balance</span>
                <span className="font-medium">{formatCurrency(loan.remainingBalance || loan.amount, loan.currency)}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Minimum Payment</span>
                <span className="font-medium">{loan.monthlyPayment ? formatCurrency(loan.monthlyPayment, loan.currency) : "-"}</span>
              </div>
            </div>
            <p className="text-xs text-muted-foreground">
              Payments are processed from your default account. A payment of {formatCurrency(loan.monthlyPayment || 0, loan.currency)} will be made.
            </p>
          </div>
          <DialogFooter>
            <DialogClose asChild>
              <Button variant="outline">Cancel</Button>
            </DialogClose>
            <Button onClick={() => {
              show("Payment processed successfully", "success")
              setPayDialogOpen(false)
            }}>
              Confirm Payment
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}
