"use client"

import { AppShell } from "@/components/layout/AppShell"
import { Check, Star, Zap, Building2 } from "lucide-react"

const plans = [
  {
    name: "Starter",
    icon: Star,
    price: "Free",
    description: "For individuals and small-scale testing",
    features: [
      "Up to 3 accounts",
      "Basic payments & transfers",
      "Email support",
      "Standard API access",
    ],
  },
  {
    name: "Professional",
    icon: Zap,
    price: "$29/mo",
    description: "For growing businesses and power users",
    features: [
      "Up to 25 accounts",
      "All payment methods",
      "Priority support",
      "Webhook integrations",
      "API rate limit: 1000/min",
      "Basic analytics",
    ],
    popular: true,
  },
  {
    name: "Enterprise",
    icon: Building2,
    price: "Custom",
    description: "For large institutions and platforms",
    features: [
      "Unlimited accounts",
      "All features included",
      "Dedicated support",
      "Custom integrations",
      "Unlimited API rate limit",
      "Advanced analytics & reporting",
      "SLA guarantee",
      "On-premise deployment option",
    ],
  },
]

function SubscriptionsContent() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-white mb-1">Subscriptions</h1>
        <p className="text-sm text-neutral-400 mb-6">Choose the plan that fits your needs</p>
      </div>

      <div className="grid gap-4 lg:grid-cols-3">
        {plans.map((plan) => {
          const Icon = plan.icon
          return (
            <div
              key={plan.name}
              className={`rounded-xl border bg-neutral-900 p-6 ${
                plan.popular ? "border-blue-500 ring-1 ring-blue-500" : "border-neutral-800"
              }`}
            >
              {plan.popular && (
                <span className="inline-flex items-center rounded-full bg-blue-600 px-2 py-0.5 text-xs font-medium text-white mb-4">
                  Most Popular
                </span>
              )}
              <div className="flex items-center gap-3 mb-4">
                <div className={`flex h-10 w-10 items-center justify-center rounded-lg ${
                  plan.popular ? "bg-blue-600 text-white" : "bg-blue-600/10 text-blue-400"
                }`}>
                  <Icon size={20} />
                </div>
                <div>
                  <h3 className="text-sm font-medium text-white">{plan.name}</h3>
                  <p className="text-lg font-semibold text-white">{plan.price}</p>
                </div>
              </div>
              <p className="text-xs text-neutral-400 mb-5">{plan.description}</p>
              <ul className="space-y-2.5">
                {plan.features.map((f) => (
                  <li key={f} className="flex items-start gap-2 text-xs text-neutral-300">
                    <Check size={14} className="mt-0.5 shrink-0 text-green-400" />
                    {f}
                  </li>
                ))}
              </ul>
              <button
                disabled
                className="mt-6 w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50"
              >
                {plan.name === "Enterprise" ? "Contact Sales" : "Subscribe"}
              </button>
            </div>
          )
        })}
      </div>

      <p className="text-xs text-neutral-500 text-center">
        Subscription management will be available soon. Contact support for plan changes.
      </p>
    </div>
  )
}

export default function SubscriptionsPage() {
  return (
    <AppShell>
      <SubscriptionsContent />
    </AppShell>
  )
}
