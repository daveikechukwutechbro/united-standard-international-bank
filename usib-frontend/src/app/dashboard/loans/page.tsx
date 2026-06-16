"use client"

import { useState, useMemo, useCallback } from "react"
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query"
import {
  Landmark, Plus, Calculator, TrendingUp, Clock, CheckCircle2,
  XCircle, AlertCircle, DollarSign, Building2, Car, GraduationCap,
  Home, ChevronRight, SlidersHorizontal, Briefcase
} from "lucide-react"
import { api } from "@/lib/api"
import { formatCurrency, formatDate, cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import {
  Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter
} from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Label } from "@/components/ui/label"
import { Badge } from "@/components/ui/badge"
import { Skeleton } from "@/components/ui/skeleton"
import {
  Select, SelectGroup, SelectValue, SelectTrigger, SelectContent, SelectItem
} from "@/components/ui/select"
import {
  Dialog, DialogPortal, DialogOverlay, DialogClose, DialogTrigger,
  DialogContent, DialogHeader, DialogFooter, DialogTitle, DialogDescription
} from "@/components/ui/dialog"
import { useToast } from "@/components/ui/toast"
import type { Loan } from "@/lib/types"

const loanProducts = [
  {
    type: "personal" as const,
    name: "Personal Loan",
    description: "Flexible funding for your personal needs",
    icon: DollarSign,
    rate: "7.5% - 15% APR",
    min: 1000,
    max: 50000,
    term: "3 - 60 months",
    color: "from-blue-500 to-blue-600",
  },
  {
    type: "business" as const,
    name: "Business Loan",
    description: "Grow your business with competitive rates",
    icon: Briefcase,
    rate: "5.25% - 12% APR",
    min: 10000,
    max: 500000,
    term: "12 - 84 months",
    color: "from-emerald-500 to-emerald-600",
  },
  {
    type: "mortgage" as const,
    name: "Mortgage",
    description: "Make your dream home a reality",
    icon: Home,
    rate: "3.5% - 6% APR",
    min: 50000,
    max: 2000000,
    term: "10 - 30 years",
    color: "from-purple-500 to-purple-600",
  },
  {
    type: "auto" as const,
    name: "Auto Loan",
    description: "Drive away with the perfect ride",
    icon: Car,
    rate: "4.5% - 9% APR",
    min: 5000,
    max: 100000,
    term: "12 - 72 months",
    color: "from-amber-500 to-amber-600",
  },
  {
    type: "education" as const,
    name: "Education Loan",
    description: "Invest in your future today",
    icon: GraduationCap,
    rate: "4% - 8% APR",
    min: 2000,
    max: 100000,
    term: "12 - 120 months",
    color: "from-rose-500 to-rose-600",
  },
]

const statusVariant: Record<string, "warning" | "success" | "default" | "danger" | "secondary"> = {
  pending: "warning",
  approved: "success",
  active: "default",
  rejected: "danger",
  funded: "success",
  closed: "secondary",
  defaulted: "danger",
}

function calculateLoan(amount: number, rate: number, term: number, termUnit: "months" | "years") {
  const months = termUnit === "years" ? term * 12 : term
  const monthlyRate = rate / 100 / 12
  const monthlyPayment = amount * (monthlyRate * Math.pow(1 + monthlyRate, months)) / (Math.pow(1 + monthlyRate, months) - 1)
  const totalRepayment = monthlyPayment * months
  const totalInterest = totalRepayment - amount
  return { monthlyPayment, totalRepayment, totalInterest }
}

export default function LoansPage() {
  const queryClient = useQueryClient()
  const { show, Toast } = useToast()
  const [appDialogOpen, setAppDialogOpen] = useState(false)
  const [calcOpen, setCalcOpen] = useState(false)
  const [calcAmount, setCalcAmount] = useState(25000)
  const [calcRate, setCalcRate] = useState(7.5)
  const [calcTerm, setCalcTerm] = useState(36)
  const [calcTermUnit, setCalcTermUnit] = useState<"months" | "years">("months")

  const [formData, setFormData] = useState({
    loanType: "" as string,
    amount: "",
    currency: "USD",
    term: "",
    termUnit: "months" as "months" | "years",
    purpose: "",
    monthlyIncome: "",
    employmentStatus: "",
    employerName: "",
  })

  const { data: loans, isLoading } = useQuery({
    queryKey: ["loans"],
    queryFn: api.getLoans,
  })

  const applyMutation = useMutation({
    mutationFn: api.applyForLoan,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["loans"] })
      setAppDialogOpen(false)
      setFormData({
        loanType: "", amount: "", currency: "USD", term: "",
        termUnit: "months", purpose: "", monthlyIncome: "",
        employmentStatus: "", employerName: "",
      })
      show("Loan application submitted successfully", "success")
    },
    onError: (error: Error) => {
      show(error.message || "Failed to submit application", "error")
    },
  })

  const calcResult = useMemo(() => {
    if (!calcAmount || !calcRate || !calcTerm) return null
    return calculateLoan(calcAmount, calcRate, calcTerm, calcTermUnit)
  }, [calcAmount, calcRate, calcTerm, calcTermUnit])

  const handleSubmit = useCallback((e: React.FormEvent) => {
    e.preventDefault()
    if (!formData.loanType || !formData.amount || !formData.term || !formData.purpose) {
      show("Please fill in all required fields", "error")
      return
    }
    applyMutation.mutate({
      loanType: formData.loanType as any,
      amount: parseFloat(formData.amount),
      currency: formData.currency,
      term: parseInt(formData.term),
      termUnit: formData.termUnit,
      purpose: formData.purpose,
      monthlyIncome: formData.monthlyIncome ? parseFloat(formData.monthlyIncome) : undefined,
      employmentStatus: formData.employmentStatus || undefined,
      employerName: formData.employerName || undefined,
    })
  }, [formData, applyMutation, show])

  return (
    <div className="space-y-8">
      {Toast}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold font-display text-foreground">Loans</h1>
          <p className="text-sm text-muted-foreground">Explore loan products and manage your loans</p>
        </div>
        <div className="flex items-center gap-3">
          <Button variant="outline" onClick={() => setCalcOpen(true)}>
            <Calculator className="mr-2 h-4 w-4" />
            Calculator
          </Button>
          <Button onClick={() => setAppDialogOpen(true)}>
            <Plus className="mr-2 h-4 w-4" />
            Apply for Loan
          </Button>
        </div>
      </div>

      <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        {loanProducts.map((product) => (
          <Card key={product.type} className="group hover:shadow-card-hover transition-all duration-200">
            <CardContent className="p-6">
              <div className={cn("mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br text-white shadow-sm", product.color)}>
                <product.icon className="h-6 w-6" />
              </div>
              <h3 className="mb-1 font-semibold text-foreground">{product.name}</h3>
              <p className="mb-4 text-xs text-muted-foreground">{product.description}</p>
              <div className="space-y-2 text-sm">
                <div className="flex items-center justify-between">
                  <span className="text-muted-foreground">Rate</span>
                  <span className="font-medium text-foreground">{product.rate}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-muted-foreground">Amount</span>
                  <span className="font-medium text-foreground">{formatCurrency(product.min)} - {formatCurrency(product.max)}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-muted-foreground">Term</span>
                  <span className="font-medium text-foreground">{product.term}</span>
                </div>
              </div>
            </CardContent>
            <CardFooter className="px-6 pb-6 pt-0">
              <Button
                variant="outline"
                className="w-full"
                onClick={() => {
                  setFormData(prev => ({ ...prev, loanType: product.type }))
                  setAppDialogOpen(true)
                }}
              >
                Apply Now
                <ChevronRight className="ml-2 h-4 w-4" />
              </Button>
            </CardFooter>
          </Card>
        ))}
      </div>

      {isLoading ? (
        <div className="space-y-4">
          <Skeleton className="h-8 w-48" />
          <div className="rounded-xl border">
            {[1, 2, 3].map((i) => (
              <Skeleton key={i} className="h-16 w-full" />
            ))}
          </div>
        </div>
      ) : loans && loans.length > 0 ? (
        <Card>
          <CardHeader>
            <CardTitle>Your Loans</CardTitle>
            <CardDescription>View and manage your active loan applications</CardDescription>
          </CardHeader>
          <CardContent className="p-0">
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left text-xs font-medium text-muted-foreground">
                    <th className="px-6 py-3">Type</th>
                    <th className="px-6 py-3">Amount</th>
                    <th className="px-6 py-3">Rate</th>
                    <th className="px-6 py-3">Term</th>
                    <th className="px-6 py-3">Monthly</th>
                    <th className="px-6 py-3">Status</th>
                    <th className="px-6 py-3">Applied</th>
                    <th className="px-6 py-3"></th>
                  </tr>
                </thead>
                <tbody>
                  {loans.map((loan) => (
                    <tr key={loan.id} className="border-b last:border-0 hover:bg-muted/50 transition-colors">
                      <td className="px-6 py-4">
                        <span className="capitalize font-medium text-foreground">{loan.loanType}</span>
                      </td>
                      <td className="px-6 py-4 font-medium text-foreground">{formatCurrency(loan.amount, loan.currency)}</td>
                      <td className="px-6 py-4 text-muted-foreground">{loan.interestRate}%</td>
                      <td className="px-6 py-4 text-muted-foreground">{loan.term} {loan.termUnit}</td>
                      <td className="px-6 py-4 text-muted-foreground">{loan.monthlyPayment ? formatCurrency(loan.monthlyPayment, loan.currency) : "-"}</td>
                      <td className="px-6 py-4">
                        <Badge variant={statusVariant[loan.status] || "default"} className="capitalize">
                          {loan.status}
                        </Badge>
                      </td>
                      <td className="px-6 py-4 text-muted-foreground">{formatDate(loan.appliedAt || loan.createdAt)}</td>
                      <td className="px-6 py-4">
                        <a href={`/dashboard/loans/${loan.id}`}>
                          <Button variant="ghost" size="sm">
                            <ChevronRight className="h-4 w-4" />
                          </Button>
                        </a>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      ) : null}

      <Dialog open={calcOpen} onOpenChange={setCalcOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle>Loan Calculator</DialogTitle>
            <DialogDescription>Estimate your monthly payments and total interest</DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div className="space-y-2">
              <Label>Loan Amount</Label>
              <div className="flex items-center gap-3">
                <Input
                  type="number"
                  value={calcAmount}
                  onChange={(e) => setCalcAmount(parseFloat(e.target.value) || 0)}
                />
                <span className="text-sm text-muted-foreground w-12">USD</span>
              </div>
              <input
                type="range"
                min={1000}
                max={500000}
                step={1000}
                value={calcAmount}
                onChange={(e) => setCalcAmount(parseInt(e.target.value))}
                className="w-full accent-gold-500"
              />
              <div className="flex justify-between text-xs text-muted-foreground">
                <span>$1,000</span>
                <span>$500,000</span>
              </div>
            </div>
            <div className="space-y-2">
              <Label>Interest Rate (% APR)</Label>
              <Input
                type="number"
                step="0.1"
                value={calcRate}
                onChange={(e) => setCalcRate(parseFloat(e.target.value) || 0)}
              />
            </div>
            <div className="space-y-2">
              <Label>Term</Label>
              <div className="flex gap-2">
                <Input
                  type="number"
                  value={calcTerm}
                  onChange={(e) => setCalcTerm(parseInt(e.target.value) || 0)}
                  className="flex-1"
                />
                <Select value={calcTermUnit} onValueChange={(v: any) => setCalcTermUnit(v)}>
                  <SelectTrigger className="w-32">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="months">Months</SelectItem>
                    <SelectItem value="years">Years</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
            {calcResult && (
              <div className="rounded-xl bg-gradient-to-br from-usib-50 to-gold-50 p-4 space-y-2">
                <div className="flex items-center justify-between">
                  <span className="text-sm text-muted-foreground">Monthly Payment</span>
                  <span className="text-xl font-bold text-foreground">{formatCurrency(calcResult.monthlyPayment)}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-sm text-muted-foreground">Total Repayment</span>
                  <span className="font-semibold text-foreground">{formatCurrency(calcResult.totalRepayment)}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-sm text-muted-foreground">Total Interest</span>
                  <span className="font-semibold text-gold-500">{formatCurrency(calcResult.totalInterest)}</span>
                </div>
              </div>
            )}
          </div>
        </DialogContent>
      </Dialog>

      <Dialog open={appDialogOpen} onOpenChange={setAppDialogOpen}>
        <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Apply for a Loan</DialogTitle>
            <DialogDescription>Complete the form below to submit your loan application</DialogDescription>
          </DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="space-y-2">
              <Label required>Loan Type</Label>
              <Select
                value={formData.loanType}
                onValueChange={(v) => setFormData(prev => ({ ...prev, loanType: v }))}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select loan type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="personal">Personal Loan</SelectItem>
                  <SelectItem value="business">Business Loan</SelectItem>
                  <SelectItem value="mortgage">Mortgage</SelectItem>
                  <SelectItem value="auto">Auto Loan</SelectItem>
                  <SelectItem value="education">Education Loan</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label required>Amount</Label>
              <Input
                type="number"
                placeholder="Enter loan amount"
                value={formData.amount}
                onChange={(e) => setFormData(prev => ({ ...prev, amount: e.target.value }))}
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label required>Term</Label>
                <Input
                  type="number"
                  placeholder="Term"
                  value={formData.term}
                  onChange={(e) => setFormData(prev => ({ ...prev, term: e.target.value }))}
                />
              </div>
              <div className="space-y-2">
                <Label>Term Unit</Label>
                <Select
                  value={formData.termUnit}
                  onValueChange={(v: any) => setFormData(prev => ({ ...prev, termUnit: v }))}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="months">Months</SelectItem>
                    <SelectItem value="years">Years</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div className="space-y-2">
              <Label required>Purpose</Label>
              <Textarea
                placeholder="Describe the purpose of this loan"
                value={formData.purpose}
                onChange={(e) => setFormData(prev => ({ ...prev, purpose: e.target.value }))}
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Monthly Income</Label>
                <Input
                  type="number"
                  placeholder="Monthly income"
                  value={formData.monthlyIncome}
                  onChange={(e) => setFormData(prev => ({ ...prev, monthlyIncome: e.target.value }))}
                />
              </div>
              <div className="space-y-2">
                <Label>Employment Status</Label>
                <Select
                  value={formData.employmentStatus}
                  onValueChange={(v) => setFormData(prev => ({ ...prev, employmentStatus: v }))}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Select status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="employed">Employed</SelectItem>
                    <SelectItem value="self-employed">Self-Employed</SelectItem>
                    <SelectItem value="business-owner">Business Owner</SelectItem>
                    <SelectItem value="freelancer">Freelancer</SelectItem>
                    <SelectItem value="unemployed">Unemployed</SelectItem>
                    <SelectItem value="retired">Retired</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div className="space-y-2">
              <Label>Employer Name</Label>
              <Input
                placeholder="Company name"
                value={formData.employerName}
                onChange={(e) => setFormData(prev => ({ ...prev, employerName: e.target.value }))}
              />
            </div>
            <DialogFooter className="pt-4">
              <DialogClose asChild>
                <Button type="button" variant="outline">Cancel</Button>
              </DialogClose>
              <Button type="submit" loading={applyMutation.isPending}>
                {applyMutation.isPending ? "Submitting..." : "Submit Application"}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  )
}
