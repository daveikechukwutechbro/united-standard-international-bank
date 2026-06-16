"use client"

import { useState } from "react"
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query"
import {
  CreditCard, Eye, EyeOff, Snowflake, Flame, CheckCircle2,
  AlertTriangle, Shield, Lock, Info, ChevronRight, Wifi,
  Smartphone, Globe, Copy, ArrowUpDown
} from "lucide-react"
import { api } from "@/lib/api"
import { formatCurrency, formatDate, maskCardNumber, cn } from "@/lib/utils"
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
import type { Card as CardType } from "@/lib/types"

const networkColors: Record<string, string> = {
  visa: "from-blue-600 to-blue-800",
  mastercard: "from-red-500 to-yellow-500",
  amex: "from-blue-400 to-blue-600",
}

function VirtualCardDisplay({ card, onShowDetails }: { card: CardType; onShowDetails: () => void }) {
  const gradient = networkColors[card.cardNetwork] || "from-usib-600 to-usib-800"
  return (
    <div
      className={cn(
        "relative h-48 w-full cursor-pointer overflow-hidden rounded-2xl bg-gradient-to-br p-6 text-white shadow-lg transition-transform hover:scale-[1.02]",
        gradient
      )}
      onClick={onShowDetails}
    >
      <div className="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10" />
      <div className="absolute -bottom-4 -left-4 h-32 w-32 rounded-full bg-white/5" />
      <div className="flex flex-col justify-between h-full">
        <div className="flex items-start justify-between">
          <div className="flex items-center gap-1">
            <div className="h-8 w-10 rounded-md bg-gradient-to-b from-gold-300 to-gold-500" />
            <span className="text-[10px] font-medium uppercase tracking-wider text-white/70">Debit</span>
          </div>
          <div className="flex items-center gap-1">
            {card.cardNetwork === "visa" && (
              <span className="text-lg font-bold italic tracking-tight">VISA</span>
            )}
            {card.cardNetwork === "mastercard" && (
              <div className="flex">
                <div className="h-6 w-6 rounded-full bg-red-500 opacity-80" />
                <div className="-ml-3 h-6 w-6 rounded-full bg-yellow-500 opacity-80" />
              </div>
            )}
            {card.cardNetwork === "amex" && (
              <span className="text-sm font-bold tracking-tight">AMEX</span>
            )}
          </div>
        </div>
        <div>
          <p className="text-lg tracking-widest">{maskCardNumber(card.cardNumber)}</p>
        </div>
        <div className="flex items-center justify-between text-xs">
          <div>
            <p className="text-white/60">Card Holder</p>
            <p className="font-medium">{card.cardHolderName}</p>
          </div>
          <div className="text-right">
            <p className="text-white/60">Expires</p>
            <p className="font-medium">{String(card.expiryMonth).padStart(2, "0")}/{card.expiryYear}</p>
          </div>
        </div>
      </div>
      {card.status === "frozen" && (
        <div className="absolute inset-0 flex items-center justify-center rounded-2xl bg-black/40 backdrop-blur-[2px]">
          <div className="flex flex-col items-center gap-1">
            <Snowflake className="h-6 w-6" />
            <span className="text-sm font-medium">Frozen</span>
          </div>
        </div>
      )}
    </div>
  )
}

export default function CardsPage() {
  const queryClient = useQueryClient()
  const { show, Toast } = useToast()
  const [selectedCard, setSelectedCard] = useState<CardType | null>(null)
  const [showDetails, setShowDetails] = useState(false)
  const [detailsDialog, setDetailsDialog] = useState(false)
  const [showFullNumber, setShowFullNumber] = useState(false)

  const { data: cards, isLoading } = useQuery({
    queryKey: ["cards"],
    queryFn: api.getCards,
  })

  const freezeMutation = useMutation({
    mutationFn: ({ cardId, freeze }: { cardId: string; freeze: boolean }) =>
      api.toggleCardFreeze(cardId, freeze),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["cards"] })
      show("Card status updated", "success")
    },
    onError: (err: Error) => {
      show(err.message, "error")
    },
  })

  if (isLoading) {
    return (
      <div className="space-y-6">
        <Skeleton className="h-8 w-48" />
        <div className="grid gap-6 md:grid-cols-2">
          {[1, 2].map(i => <Skeleton key={i} className="h-48 rounded-2xl" />)}
        </div>
      </div>
    )
  }

  const mainCard = cards?.[0]

  return (
    <div className="space-y-8">
      {Toast}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold font-display text-foreground">Cards</h1>
          <p className="text-sm text-muted-foreground">Manage your debit, credit, and virtual cards</p>
        </div>
      </div>

      {mainCard && (
        <div className="grid gap-6 md:grid-cols-2">
          <VirtualCardDisplay card={mainCard} onShowDetails={() => {
            setSelectedCard(mainCard)
            setDetailsDialog(true)
          }} />
          <Card>
            <CardHeader>
              <CardTitle>Quick Actions</CardTitle>
              <CardDescription>Manage your card settings</CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex items-center justify-between rounded-lg border p-3">
                <div className="flex items-center gap-3">
                  <div className={cn(
                    "flex h-9 w-9 items-center justify-center rounded-lg",
                    mainCard.status === "frozen" ? "bg-blue-100 text-blue-600" : "bg-green-100 text-green-600"
                  )}>
                    {mainCard.status === "frozen" ? <Snowflake className="h-4 w-4" /> : <Flame className="h-4 w-4" />}
                  </div>
                  <div>
                    <p className="text-sm font-medium text-foreground">
                      {mainCard.status === "frozen" ? "Card is Frozen" : "Card is Active"}
                    </p>
                    <p className="text-xs text-muted-foreground">
                      {mainCard.status === "frozen" ? "Unfreeze to use your card" : "Freeze if card is lost"}
                    </p>
                  </div>
                </div>
                <Button
                  variant={mainCard.status === "frozen" ? "outline" : "secondary"}
                  size="sm"
                  onClick={() => freezeMutation.mutate({
                    cardId: mainCard.id,
                    freeze: mainCard.status !== "frozen"
                  })}
                  loading={freezeMutation.isPending}
                >
                  {mainCard.status === "frozen" ? "Unfreeze" : "Freeze"}
                </Button>
              </div>
              <Button variant="outline" className="w-full justify-between" onClick={() => {
                setSelectedCard(mainCard)
                setDetailsDialog(true)
              }}>
                <span className="flex items-center gap-2">
                  <Eye className="h-4 w-4" />
                  View Card Details
                </span>
                <ChevronRight className="h-4 w-4" />
              </Button>
              <Button variant="outline" className="w-full justify-between">
                <span className="flex items-center gap-2">
                  <ArrowUpDown className="h-4 w-4" />
                  Set Spending Limits
                </span>
                <ChevronRight className="h-4 w-4" />
              </Button>
            </CardContent>
          </Card>
        </div>
      )}

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="lg:col-span-2 space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Your Cards</CardTitle>
              <CardDescription>All your active cards</CardDescription>
            </CardHeader>
            <CardContent className="p-0">
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b text-left text-xs font-medium text-muted-foreground">
                      <th className="px-6 py-3">Card</th>
                      <th className="px-6 py-3">Network</th>
                      <th className="px-6 py-3">Type</th>
                      <th className="px-6 py-3">Status</th>
                      <th className="px-6 py-3">Daily Limit</th>
                      <th className="px-6 py-3">Spent Today</th>
                      <th className="px-6 py-3"></th>
                    </tr>
                  </thead>
                  <tbody>
                    {cards?.map((card) => (
                      <tr key={card.id} className="border-b last:border-0 hover:bg-muted/50">
                        <td className="px-6 py-4">
                          <div className="flex items-center gap-3">
                            <CreditCard className="h-4 w-4 text-muted-foreground" />
                            <span className="font-medium text-foreground">{maskCardNumber(card.cardNumber)}</span>
                          </div>
                        </td>
                        <td className="px-6 py-4 capitalize text-foreground">{card.cardNetwork}</td>
                        <td className="px-6 py-4 capitalize text-foreground">{card.cardType}</td>
                        <td className="px-6 py-4">
                          <Badge variant={card.status === "active" ? "success" : card.status === "frozen" ? "warning" : "secondary"} className="capitalize">
                            {card.status}
                          </Badge>
                        </td>
                        <td className="px-6 py-4 text-foreground">{formatCurrency(card.dailyLimit)}</td>
                        <td className="px-6 py-4 text-foreground">{formatCurrency(card.spentToday)}</td>
                        <td className="px-6 py-4">
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                              setSelectedCard(card)
                              setDetailsDialog(true)
                            }}
                          >
                            <Eye className="h-4 w-4" />
                          </Button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Limits & Usage</CardTitle>
              <CardDescription>Card spending limits for {mainCard?.cardHolderName || "you"}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {mainCard && (
                <>
                  <div className="space-y-2">
                    <div className="flex items-center justify-between text-sm">
                      <span className="text-muted-foreground">Daily Limit</span>
                      <span className="font-medium text-foreground">{formatCurrency(mainCard.dailyLimit)}</span>
                    </div>
                    <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                      <div
                        className="h-full rounded-full bg-gold-500"
                        style={{ width: `${Math.min((mainCard.spentToday / mainCard.dailyLimit) * 100, 100)}%` }}
                      />
                    </div>
                    <div className="flex justify-between text-xs text-muted-foreground">
                      <span>Spent: {formatCurrency(mainCard.spentToday)}</span>
                      <span>Available: {formatCurrency(mainCard.dailyLimit - mainCard.spentToday)}</span>
                    </div>
                  </div>
                  <div className="space-y-2">
                    <div className="flex items-center justify-between text-sm">
                      <span className="text-muted-foreground">Monthly Limit</span>
                      <span className="font-medium text-foreground">{formatCurrency(mainCard.monthlyLimit)}</span>
                    </div>
                    <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                      <div
                        className="h-full rounded-full bg-gold-500"
                        style={{ width: `${Math.min((mainCard.spentThisMonth / mainCard.monthlyLimit) * 100, 100)}%` }}
                      />
                    </div>
                    <div className="flex justify-between text-xs text-muted-foreground">
                      <span>Spent: {formatCurrency(mainCard.spentThisMonth)}</span>
                      <span>Available: {formatCurrency(mainCard.monthlyLimit - mainCard.spentThisMonth)}</span>
                    </div>
                  </div>
                </>
              )}
            </CardContent>
          </Card>
        </div>

        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Security Notes</CardTitle>
              <CardDescription>Keep your cards safe</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-start gap-3">
                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                  <Shield className="h-4 w-4" />
                </div>
                <div>
                  <p className="text-sm font-medium text-foreground">Instant Freeze</p>
                  <p className="text-xs text-muted-foreground">Freeze your card instantly if lost or stolen</p>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-600">
                  <Lock className="h-4 w-4" />
                </div>
                <div>
                  <p className="text-sm font-medium text-foreground">3D Secure</p>
                  <p className="text-xs text-muted-foreground">All online transactions are 3D Secure protected</p>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
                  <Smartphone className="h-4 w-4" />
                </div>
                <div>
                  <p className="text-sm font-medium text-foreground">Mobile Wallet</p>
                  <p className="text-xs text-muted-foreground">Add to Apple Pay or Google Pay</p>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                  <Globe className="h-4 w-4" />
                </div>
                <div>
                  <p className="text-sm font-medium text-foreground">International Usage</p>
                  <p className="text-xs text-muted-foreground">Use your card in 200+ countries worldwide</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Quick Info</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3 text-sm">
              <div className="flex justify-between">
                <span className="text-muted-foreground">Total Cards</span>
                <span className="font-medium">{cards?.length || 0}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Active</span>
                <span className="font-medium">{cards?.filter(c => c.status === "active").length || 0}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Virtual Cards</span>
                <span className="font-medium">{cards?.filter(c => c.isVirtual).length || 0}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Monthly Spent</span>
                <span className="font-medium">{formatCurrency(cards?.reduce((a, c) => a + c.spentThisMonth, 0) || 0)}</span>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <Dialog open={detailsDialog} onOpenChange={(open) => {
        setDetailsDialog(open)
        if (!open) setShowFullNumber(false)
      }}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle>Card Details</DialogTitle>
            <DialogDescription>Sensitive information - keep secure</DialogDescription>
          </DialogHeader>
          {selectedCard && (
            <div className="space-y-4">
              <div className={cn(
                "relative h-40 w-full overflow-hidden rounded-2xl bg-gradient-to-br p-5 text-white",
                networkColors[selectedCard.cardNetwork] || "from-usib-600 to-usib-800"
              )}>
                <div className="absolute -right-4 -top-4 h-16 w-16 rounded-full bg-white/10" />
                <div className="flex flex-col justify-between h-full">
                  <div className="flex items-center gap-1">
                    <div className="h-6 w-8 rounded bg-gradient-to-b from-gold-300 to-gold-500" />
                  </div>
                  <div>
                    <p className="text-base tracking-widest">
                      {showFullNumber ? selectedCard.cardNumber.match(/.{4}/g)?.join(" ") : maskCardNumber(selectedCard.cardNumber)}
                    </p>
                  </div>
                  <div className="flex items-center justify-between text-[10px]">
                    <div className="flex gap-4">
                      <div>
                        <p className="text-white/60">EXPIRY</p>
                        <p>{String(selectedCard.expiryMonth).padStart(2, "0")}/{selectedCard.expiryYear}</p>
                      </div>
                      <div>
                        <p className="text-white/60">CVV</p>
                        <p>{showFullNumber ? selectedCard.cvv : "***"}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div className="flex gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  className="flex-1"
                  onClick={() => setShowFullNumber(!showFullNumber)}
                >
                  {showFullNumber ? (
                    <><EyeOff className="mr-2 h-4 w-4" /> Hide Details</>
                  ) : (
                    <><Eye className="mr-2 h-4 w-4" /> Show Details</>
                  )}
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  className="flex-1"
                  onClick={() => {
                    navigator.clipboard.writeText(selectedCard.cardNumber)
                    show("Card number copied", "success")
                  }}
                >
                  <Copy className="mr-2 h-4 w-4" />
                  Copy Number
                </Button>
              </div>

              <div className="rounded-lg bg-muted p-4 space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Card Network</span>
                  <span className="font-medium capitalize">{selectedCard.cardNetwork}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Card Type</span>
                  <span className="font-medium capitalize">{selectedCard.cardType}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Status</span>
                  <Badge variant={selectedCard.status === "active" ? "success" : "warning"} className="capitalize">
                    {selectedCard.status}
                  </Badge>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Issued</span>
                  <span className="font-medium">{formatDate(selectedCard.issuedAt)}</span>
                </div>
              </div>

              <div className="flex items-start gap-2 rounded-lg bg-amber-50 p-3 dark:bg-amber-900/20">
                <AlertTriangle className="h-4 w-4 shrink-0 text-amber-600 mt-0.5" />
                <p className="text-xs text-amber-800 dark:text-amber-200">
                  Never share your card details with anyone. USIB will never ask for your full card number, CVV, or PIN.
                </p>
              </div>
            </div>
          )}
          <DialogFooter>
            <DialogClose asChild>
              <Button variant="outline">Close</Button>
            </DialogClose>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}
