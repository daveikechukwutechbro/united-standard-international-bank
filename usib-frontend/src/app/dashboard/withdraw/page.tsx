"use client"

import { useState } from "react"
import { useQuery, useMutation } from "@tanstack/react-query"
import { motion, AnimatePresence } from "framer-motion"
import {
  Upload, CheckCircle2, DollarSign, AlertCircle,
  Clock, ArrowRight
} from "lucide-react"
import { api } from "@/lib/api"
import { formatCurrency } from "@/lib/utils"
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Badge } from "@/components/ui/badge"

export default function WithdrawPage() {
  const [step, setStep] = useState<"form" | "review" | "success">("form")
  const [accountId, setAccountId] = useState("")
  const [amount, setAmount] = useState("")
  const [destination, setDestination] = useState("")
  const [withdrawId, setWithdrawId] = useState("")
  const [fee, setFee] = useState(0)
  const [estimatedArrival, setEstimatedArrival] = useState("")

  const { data: accounts } = useQuery({
    queryKey: ["accounts"],
    queryFn: api.getAccounts,
  })

  const withdrawMutation = useMutation({
    mutationFn: () => api.initiateWithdrawal(accountId, parseFloat(amount), destination),
    onSuccess: (data) => {
      setWithdrawId(data.id)
      setFee(data.fee)
      setEstimatedArrival(data.estimatedArrival)
      setStep("success")
    },
  })

  const selectedAccount = accounts?.find((a) => a.id === accountId)
  const parsedAmount = parseFloat(amount) || 0
  const canReview = accountId && parsedAmount > 0 && destination.length > 0
  const total = parsedAmount + fee

  const handleReview = () => setStep("review")

  const handleConfirm = () => {
    withdrawMutation.mutate()
  }

  const reset = () => {
    setStep("form")
    setAccountId("")
    setAmount("")
    setDestination("")
    setWithdrawId("")
    setFee(0)
    setEstimatedArrival("")
  }

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      className="mx-auto max-w-2xl space-y-6"
    >
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Withdraw Funds</h1>
        <p className="text-sm text-muted-foreground">Withdraw money from your account</p>
      </div>

      <AnimatePresence mode="wait">
        {step === "success" ? (
          <motion.div
            key="success"
            initial={{ opacity: 0, scale: 0.95 }}
            animate={{ opacity: 1, scale: 1 }}
            exit={{ opacity: 0, scale: 0.95 }}
          >
            <Card className="text-center">
              <CardContent className="pt-12 pb-8">
                <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-50 dark:bg-green-900/20">
                  <CheckCircle2 className="h-8 w-8 text-green-500" />
                </div>
                <h2 className="text-xl font-bold mb-2">Withdrawal Initiated!</h2>
                <p className="text-muted-foreground mb-2">
                  Your withdrawal of {formatCurrency(parsedAmount)} is being processed.
                </p>
                <p className="text-sm text-muted-foreground mb-6">
                  Withdrawal ID: <span className="font-mono font-medium">{withdrawId}</span>
                </p>
                <div className="mx-auto max-w-sm rounded-lg bg-muted p-4 mb-6 text-left space-y-2">
                  <div className="flex justify-between">
                    <span className="text-sm text-muted-foreground">From</span>
                    <span className="text-sm font-medium">{selectedAccount?.accountName}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-sm text-muted-foreground">Amount</span>
                    <span className="text-sm font-bold">{formatCurrency(parsedAmount)}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-sm text-muted-foreground">Fee</span>
                    <span className="text-sm">{formatCurrency(fee)}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-sm text-muted-foreground">Total</span>
                    <span className="text-sm font-bold">{formatCurrency(total)}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-sm text-muted-foreground">Estimated Arrival</span>
                    <span className="text-sm font-medium">{estimatedArrival}</span>
                  </div>
                </div>
                <div className="flex items-center justify-center gap-3">
                  <Button onClick={reset} variant="outline">Make Another Withdrawal</Button>
                  <Button onClick={() => window.location.href = "/dashboard"}>Back to Dashboard</Button>
                </div>
              </CardContent>
            </Card>
          </motion.div>
        ) : step === "review" ? (
          <motion.div
            key="review"
            initial={{ opacity: 0, x: 20 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: -20 }}
          >
            <Card>
              <CardHeader>
                <CardTitle>Review Withdrawal</CardTitle>
                <CardDescription>Please verify the withdrawal details</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="rounded-lg bg-muted p-4 space-y-2">
                  <div className="flex justify-between">
                    <span className="text-sm text-muted-foreground">From Account</span>
                    <span className="font-medium">{selectedAccount?.accountName}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-sm text-muted-foreground">Destination</span>
                    <span className="font-medium">{destination}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-sm text-muted-foreground">Amount</span>
                    <span className="text-lg font-bold">{formatCurrency(parsedAmount)}</span>
                  </div>
                  <div className="flex justify-between pt-2 border-t">
                    <span className="text-sm text-muted-foreground">Fee</span>
                    <span className="font-medium">{formatCurrency(25)}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-sm text-muted-foreground">Estimated Arrival</span>
                    <span className="font-medium flex items-center gap-1">
                      <Clock className="h-3.5 w-3.5" />
                      2-3 business days
                    </span>
                  </div>
                  <div className="flex justify-between pt-2 border-t">
                    <span className="text-sm font-medium">Total Deducted</span>
                    <span className="text-lg font-bold text-gold-500">{formatCurrency(parsedAmount + 25)}</span>
                  </div>
                </div>
                <div className="flex items-center gap-2 text-sm text-amber-600 bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3">
                  <AlertCircle className="h-4 w-4 shrink-0" />
                  <span>Withdrawals are subject to review and may take up to 3 business days.</span>
                </div>
              </CardContent>
              <CardFooter className="flex gap-3">
                <Button variant="outline" onClick={() => setStep("form")} className="flex-1">
                  Edit
                </Button>
                <Button onClick={handleConfirm} loading={withdrawMutation.isPending} className="flex-1">
                  Confirm Withdrawal
                </Button>
              </CardFooter>
            </Card>
          </motion.div>
        ) : (
          <motion.div
            key="form"
            initial={{ opacity: 0, x: -20 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: 20 }}
          >
            <Card>
              <CardHeader>
                <CardTitle>Withdrawal Details</CardTitle>
                <CardDescription>Select the account and amount to withdraw</CardDescription>
              </CardHeader>
              <CardContent className="space-y-5">
                <div className="space-y-2">
                  <Label>From Account</Label>
                  <Select value={accountId} onValueChange={setAccountId}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select account" />
                    </SelectTrigger>
                    <SelectContent>
                      {accounts?.map((a) => (
                        <SelectItem key={a.id} value={a.id}>
                          {a.accountName} — {formatCurrency(a.balance, a.currency)}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label>Amount</Label>
                  <div className="relative">
                    <DollarSign className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                      type="number"
                      placeholder="0.00"
                      value={amount}
                      onChange={(e) => setAmount(e.target.value)}
                      className="pl-9"
                      min="0"
                      step="0.01"
                    />
                  </div>
                </div>

                <div className="space-y-2">
                  <Label>Destination</Label>
                  <Input
                    placeholder="Enter destination account or wire instructions"
                    value={destination}
                    onChange={(e) => setDestination(e.target.value)}
                  />
                  <p className="text-xs text-muted-foreground">
                    Provide the external account number or full wire instructions
                  </p>
                </div>

                {selectedAccount && parsedAmount > 0 && (
                  <div className="rounded-lg border p-4 space-y-2 text-sm">
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Withdrawing From</span>
                      <span className="font-medium">{selectedAccount.accountName}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Available Balance</span>
                      <span className="font-medium">{formatCurrency(selectedAccount.availableBalance)}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Fee</span>
                      <span className="font-medium">$25.00</span>
                    </div>
                    <div className="flex justify-between text-amber-600">
                      <span>Estimated Arrival</span>
                      <span>2-3 business days</span>
                    </div>
                    {parsedAmount > selectedAccount.availableBalance && (
                      <div className="flex items-center gap-1 text-xs text-red-500 mt-1">
                        <AlertCircle className="h-3 w-3" />
                        Insufficient funds
                      </div>
                    )}
                  </div>
                )}
              </CardContent>
              <CardFooter>
                <Button
                  className="w-full gap-2"
                  disabled={!canReview || parsedAmount > (selectedAccount?.availableBalance ?? 0)}
                  onClick={handleReview}
                >
                  Review Withdrawal
                  <ArrowRight className="h-4 w-4" />
                </Button>
              </CardFooter>
            </Card>
          </motion.div>
        )}
      </AnimatePresence>
    </motion.div>
  )
}
