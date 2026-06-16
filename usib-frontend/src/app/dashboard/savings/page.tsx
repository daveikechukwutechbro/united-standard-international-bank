"use client"

import { useState } from "react"
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query"
import {
  PiggyBank, Target, Plus, TrendingUp, CalendarDays,
  CheckCircle2, Clock, Building2, ArrowRight, Percent,
  ToggleLeft, ToggleRight, CircleDollarSign
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
  Dialog, DialogContent, DialogHeader, DialogFooter, DialogTitle, DialogDescription, DialogClose
} from "@/components/ui/dialog"
import { useToast } from "@/components/ui/toast"

const depositAccounts = [
  { id: "ACC-004", name: "12-Month Fixed Deposit", amount: 50000, currency: "USD", rate: 5.5, opened: "2024-08-01", maturity: "2025-08-01", status: "active" },
  { id: "ACC-005", name: "6-Month Fixed Deposit", amount: 25000, currency: "USD", rate: 4.75, opened: "2024-10-01", maturity: "2025-04-01", status: "active" },
]

export default function SavingsPage() {
  const queryClient = useQueryClient()
  const { show, Toast } = useToast()
  const [createDialog, setCreateDialog] = useState(false)
  const [formData, setFormData] = useState({
    name: "",
    targetAmount: "",
    currency: "USD",
    targetDate: "",
    autoDeposit: false,
    depositFrequency: "monthly",
    depositAmount: "",
  })

  const { data: goals, isLoading: goalsLoading } = useQuery({
    queryKey: ["savings-goals"],
    queryFn: async () => {
      const accounts = await api.getAccounts()
      return accounts.filter(a => a.accountType === "savings").map(a => ({
        id: a.id,
        name: a.accountName,
        targetAmount: 100000,
        currentAmount: a.balance,
        currency: a.currency,
        status: "active" as const,
        autoDeposit: true,
        depositFrequency: "monthly" as const,
        depositAmount: 2000,
        createdAt: a.createdAt,
      }))
    },
  })

  const createMutation = useMutation({
    mutationFn: async (data: typeof formData) => {
      await new Promise(r => setTimeout(r, 1500))
      return { id: "SVG-" + Date.now(), ...data }
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["savings-goals"] })
      setCreateDialog(false)
      setFormData({ name: "", targetAmount: "", currency: "USD", targetDate: "", autoDeposit: false, depositFrequency: "monthly", depositAmount: "" })
      show("Savings goal created successfully", "success")
    },
    onError: (err: Error) => {
      show(err.message || "Failed to create goal", "error")
    },
  })

  const handleCreate = (e: React.FormEvent) => {
    e.preventDefault()
    if (!formData.name || !formData.targetAmount) {
      show("Please fill in required fields", "error")
      return
    }
    createMutation.mutate(formData)
  }

  return (
    <div className="space-y-8">
      {Toast}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold font-display text-foreground">Savings & Deposits</h1>
          <p className="text-sm text-muted-foreground">Manage your savings goals and fixed deposits</p>
        </div>
        <Button onClick={() => setCreateDialog(true)}>
          <Plus className="mr-2 h-4 w-4" />
          New Goal
        </Button>
      </div>

      <div className="grid gap-6 md:grid-cols-3">
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-usib-100 text-usib-600">
                <PiggyBank className="h-5 w-5" />
              </div>
              <div>
                <p className="text-xs text-muted-foreground">Total Savings</p>
                <p className="text-lg font-bold text-foreground">
                  {formatCurrency(goals?.reduce((a, g) => a + g.currentAmount, 0) || 0)}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-gold-100 text-gold-600">
                <Target className="h-5 w-5" />
              </div>
              <div>
                <p className="text-xs text-muted-foreground">Total Goals Target</p>
                <p className="text-lg font-bold text-foreground">
                  {formatCurrency(goals?.reduce((a, g) => a + g.targetAmount, 0) || 0)}
                </p>
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
                <p className="text-xs text-muted-foreground">Best Savings Rate</p>
                <p className="text-lg font-bold text-foreground">5.50% APY</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Savings Goals</CardTitle>
          <CardDescription>Track your progress towards your financial goals</CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          {goalsLoading ? (
            <div className="space-y-4">
              {[1, 2].map(i => <Skeleton key={i} className="h-24 w-full rounded-xl" />)}
            </div>
          ) : goals && goals.length > 0 ? (
            goals.map((goal) => {
              const progress = (goal.currentAmount / goal.targetAmount) * 100
              return (
                <div key={goal.id} className="rounded-xl border p-5 space-y-3">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-gold-100 text-gold-600">
                        <PiggyBank className="h-5 w-5" />
                      </div>
                      <div>
                        <h3 className="font-semibold text-foreground">{goal.name}</h3>
                        <p className="text-xs text-muted-foreground">
                          Target: {formatCurrency(goal.targetAmount, goal.currency)}
                        </p>
                      </div>
                    </div>
                    <Badge variant={goal.status === "active" ? "success" : "secondary"}>
                      {goal.status}
                    </Badge>
                  </div>
                  <div className="space-y-1.5">
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
                    <div className="flex justify-between text-xs text-muted-foreground">
                      <span>Saved: {formatCurrency(goal.currentAmount, goal.currency)}</span>
                      <span>Remaining: {formatCurrency(goal.targetAmount - goal.currentAmount, goal.currency)}</span>
                    </div>
                  </div>
                  <div className="flex items-center justify-between pt-2">
                    {goal.autoDeposit ? (
                      <div className="flex items-center gap-2 text-xs text-green-600">
                        <ToggleRight className="h-4 w-4" />
                        Auto-deposit {formatCurrency(goal.depositAmount || 0, goal.currency)}/{goal.depositFrequency}
                      </div>
                    ) : (
                      <div className="flex items-center gap-2 text-xs text-muted-foreground">
                        <ToggleLeft className="h-4 w-4" />
                        Auto-deposit off
                      </div>
                    )}
                    <Button size="sm" variant="ghost">View Details</Button>
                  </div>
                </div>
              )
            })
          ) : (
            <div className="flex flex-col items-center py-12 text-center">
              <Target className="mb-2 h-10 w-10 text-muted-foreground" />
              <p className="text-sm font-medium text-foreground">No savings goals yet</p>
              <p className="text-xs text-muted-foreground">Create your first savings goal to start tracking</p>
              <Button className="mt-4" onClick={() => setCreateDialog(true)}>
                <Plus className="mr-2 h-4 w-4" />
                Create Goal
              </Button>
            </div>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Fixed Deposit Accounts</CardTitle>
          <CardDescription>Term deposits with guaranteed returns</CardDescription>
        </CardHeader>
        <CardContent className="p-0">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left text-xs font-medium text-muted-foreground">
                  <th className="px-6 py-3">Account</th>
                  <th className="px-6 py-3">Amount</th>
                  <th className="px-6 py-3">Rate</th>
                  <th className="px-6 py-3">Opened</th>
                  <th className="px-6 py-3">Maturity</th>
                  <th className="px-6 py-3">Status</th>
                  <th className="px-6 py-3"></th>
                </tr>
              </thead>
              <tbody>
                {depositAccounts.map((dep) => (
                  <tr key={dep.id} className="border-b last:border-0 hover:bg-muted/50">
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-3">
                        <Building2 className="h-4 w-4 text-muted-foreground" />
                        <span className="font-medium text-foreground">{dep.name}</span>
                      </div>
                    </td>
                    <td className="px-6 py-4 font-medium text-foreground">{formatCurrency(dep.amount, dep.currency)}</td>
                    <td className="px-6 py-4">
                      <span className="font-semibold text-green-600">{dep.rate}%</span>
                    </td>
                    <td className="px-6 py-4 text-muted-foreground">{formatDate(dep.opened)}</td>
                    <td className="px-6 py-4 text-muted-foreground">{formatDate(dep.maturity)}</td>
                    <td className="px-6 py-4">
                      <Badge variant={dep.status === "active" ? "success" : "secondary"} className="capitalize">{dep.status}</Badge>
                    </td>
                    <td className="px-6 py-4">
                      <Button variant="ghost" size="sm">View</Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <Dialog open={createDialog} onOpenChange={setCreateDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Create Savings Goal</DialogTitle>
            <DialogDescription>Set a new savings goal and start tracking your progress</DialogDescription>
          </DialogHeader>
          <form onSubmit={handleCreate} className="space-y-4">
            <div className="space-y-2">
              <Label required>Goal Name</Label>
              <Input
                placeholder="e.g., Emergency Fund"
                value={formData.name}
                onChange={(e) => setFormData(prev => ({ ...prev, name: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label required>Target Amount</Label>
              <Input
                type="number"
                placeholder="Enter target amount"
                value={formData.targetAmount}
                onChange={(e) => setFormData(prev => ({ ...prev, targetAmount: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label>Target Date</Label>
              <Input
                type="date"
                value={formData.targetDate}
                onChange={(e) => setFormData(prev => ({ ...prev, targetDate: e.target.value }))}
              />
            </div>
            <div className="flex items-center justify-between rounded-lg border p-3">
              <div>
                <p className="text-sm font-medium text-foreground">Auto-Deposit</p>
                <p className="text-xs text-muted-foreground">Automatically save regularly</p>
              </div>
              <button
                type="button"
                onClick={() => setFormData(prev => ({ ...prev, autoDeposit: !prev.autoDeposit }))}
                className={cn(
                  "relative h-6 w-11 rounded-full transition-colors",
                  formData.autoDeposit ? "bg-gold-500" : "bg-muted"
                )}
              >
                <div className={cn(
                  "absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform",
                  formData.autoDeposit ? "translate-x-[22px]" : "translate-x-0.5"
                )} />
              </button>
            </div>
            {formData.autoDeposit && (
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Frequency</Label>
                  <Select
                    value={formData.depositFrequency}
                    onValueChange={(v) => setFormData(prev => ({ ...prev, depositFrequency: v }))}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="daily">Daily</SelectItem>
                      <SelectItem value="weekly">Weekly</SelectItem>
                      <SelectItem value="monthly">Monthly</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Amount</Label>
                  <Input
                    type="number"
                    placeholder="Amount"
                    value={formData.depositAmount}
                    onChange={(e) => setFormData(prev => ({ ...prev, depositAmount: e.target.value }))}
                  />
                </div>
              </div>
            )}
            <DialogFooter className="pt-4">
              <DialogClose asChild>
                <Button type="button" variant="outline">Cancel</Button>
              </DialogClose>
              <Button type="submit" loading={createMutation.isPending}>
                {createMutation.isPending ? "Creating..." : "Create Goal"}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  )
}
