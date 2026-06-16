import type { Metadata } from "next"
import Link from "next/link"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import Header from "@/components/Header"
import Footer from "@/components/Footer"
import FadeInView from "@/components/FadeInView"
import {
  ArrowRight, CheckCircle, ChevronRight,
  DollarSign, Percent, Globe, Landmark
} from "lucide-react"

export const metadata: Metadata = {
  title: "Pricing & Fees | United Standard International Bank",
  description: "Transparent pricing for all USIB banking services. Account fees, transfer fees, loan rates, and foreign exchange rates.",
}

const accountFees = [
  { service: "Standard Checking Account", fee: "$0", note: "No monthly maintenance fee" },
  { service: "Premium Checking Account", fee: "$15/mo", note: "Waived with $5,000 minimum balance" },
  { service: "Savings Account", fee: "$0", note: "No monthly maintenance fee" },
  { service: "Business Checking", fee: "$0", note: "No monthly maintenance fee" },
  { service: "Business Savings", fee: "$0", note: "No monthly maintenance fee" },
  { service: "Fixed Deposit Account", fee: "$0", note: "No account fees" },
  { service: "Money Market Account", fee: "$10/mo", note: "Waived with $2,500 minimum balance" },
]

const transferFees = [
  { service: "Domestic Wire Transfer (Outgoing)", fee: "$25", note: "Per transfer" },
  { service: "Domestic Wire Transfer (Incoming)", fee: "$10", note: "Per transfer" },
  { service: "International Wire Transfer (Outgoing)", fee: "$35", note: "Per transfer" },
  { service: "International Wire Transfer (Incoming)", fee: "$15", note: "Per transfer" },
  { service: "SEPA Transfer", fee: "€0", note: "Free for all accounts" },
  { service: "ACH Transfer", fee: "$0", note: "Free for all accounts" },
  { service: "Paper Statement", fee: "$3", note: "Per statement; online statements are free" },
  { service: "Stop Payment", fee: "$30", note: "Per request" },
]

const loanRates = [
  { product: "Personal Loan", rate: "6.99% - 17.99% APR", term: "12 - 60 months", fee: "$0 origination" },
  { product: "Auto Loan (New)", rate: "4.25% - 9.99% APR", term: "24 - 72 months", fee: "$0 origination" },
  { product: "Auto Loan (Used)", rate: "5.49% - 11.99% APR", term: "24 - 60 months", fee: "$0 origination" },
  { product: "Mortgage - Fixed 30yr", rate: "3.75% - 6.25% APR", term: "30 years", fee: "1% origination" },
  { product: "Mortgage - Fixed 15yr", rate: "3.25% - 5.75% APR", term: "15 years", fee: "1% origination" },
  { product: "Mortgage - Adjustable 5/1", rate: "3.50% - 6.00% APR", term: "30 years", fee: "1% origination" },
  { product: "Home Equity Line (HELOC)", rate: "4.50% - 8.99% APR", term: "10 year draw", fee: "$0 origination" },
  { product: "Business Loan", rate: "5.50% - 14.99% APR", term: "Up to 25 years", fee: "Varies" },
  { product: "SBA Loan", rate: "6.00% - 8.50% APR", term: "Up to 25 years", fee: "SBA guarantee fee applies" },
]

const forexRates = [
  { currency: "EUR (Euro)", buy: "0.9150", sell: "0.9350", spread: "2.14%" },
  { currency: "GBP (British Pound)", buy: "0.7820", sell: "0.8020", spread: "2.56%" },
  { currency: "JPY (Japanese Yen)", buy: "149.50", sell: "152.50", spread: "2.01%" },
  { currency: "CHF (Swiss Franc)", buy: "0.8810", sell: "0.9010", spread: "2.27%" },
  { currency: "CAD (Canadian Dollar)", buy: "1.3540", sell: "1.3740", spread: "1.48%" },
  { currency: "AUD (Australian Dollar)", buy: "1.5210", sell: "1.5410", spread: "1.31%" },
  { currency: "CNY (Chinese Yuan)", buy: "7.1950", sell: "7.2450", spread: "0.69%" },
  { currency: "SGD (Singapore Dollar)", buy: "1.3380", sell: "1.3580", spread: "1.49%" },
]

export default function PricingPage() {
  return (
    <>
      <Header />
      <main>
        <section className="relative pt-32 pb-20 lg:pt-40 lg:pb-28 overflow-hidden">
          <div className="absolute inset-0 gradient-hero" />
          <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="max-w-3xl">
              <Badge variant="outline" className="border-gold-400/50 text-gold-400 bg-gold-500/10 mb-4">Pricing</Badge>
              <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold font-display text-white leading-tight">
                Transparent{" "}
                <span className="text-gradient">Pricing</span>
              </h1>
              <p className="mt-6 text-lg text-navy-100 max-w-2xl leading-relaxed">
                No hidden fees, no surprises. We believe in complete transparency when it comes to 
                our pricing. Here&apos;s a comprehensive breakdown of our fees and rates.
              </p>
              <div className="mt-8 flex flex-wrap gap-3">
                <Button variant="outline" size="sm" className="text-white border-white/30 hover:bg-white/10" asChild>
                  <Link href="#accounts">Account Fees</Link>
                </Button>
                <Button variant="outline" size="sm" className="text-white border-white/30 hover:bg-white/10" asChild>
                  <Link href="#transfers">Transfer Fees</Link>
                </Button>
                <Button variant="outline" size="sm" className="text-white border-white/30 hover:bg-white/10" asChild>
                  <Link href="#loans">Loan Rates</Link>
                </Button>
                <Button variant="outline" size="sm" className="text-white border-white/30 hover:bg-white/10" asChild>
                  <Link href="#forex">Forex Rates</Link>
                </Button>
              </div>
            </FadeInView>
          </div>
        </section>

        <section id="accounts" className="py-20 lg:py-28 bg-white dark:bg-navy-900">
          <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-12">
              <Badge variant="secondary" className="mb-4">Account Fees</Badge>
              <h2 className="text-3xl sm:text-4xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Account{" "}
                <span className="text-gold-500">Fee Schedule</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                Most of our accounts have no monthly maintenance fees. Premium accounts offer fee waivers with minimum balances.
              </p>
            </FadeInView>
            <div className="bg-white dark:bg-navy-800 rounded-2xl shadow-card border border-gray-100 dark:border-navy-700 overflow-hidden">
              <div className="grid grid-cols-3 gap-4 p-4 bg-gray-50 dark:bg-navy-900 text-xs font-semibold uppercase tracking-wider text-navy-400">
                <span>Service</span>
                <span className="text-right">Fee</span>
                <span className="text-right">Note</span>
              </div>
              <div className="divide-y divide-gray-100 dark:divide-navy-700">
                {accountFees.map((item) => (
                  <div key={item.service} className="grid grid-cols-3 gap-4 px-4 py-3 text-sm">
                    <span className="text-navy-700 dark:text-navy-200">{item.service}</span>
                    <span className="text-right font-semibold text-navy-900 dark:text-white">{item.fee}</span>
                    <span className="text-right text-navy-400 text-xs self-center">{item.note}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </section>

        <section id="transfers" className="py-20 lg:py-28 bg-gray-50 dark:bg-navy-950">
          <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-12">
              <Badge variant="secondary" className="mb-4">Transfer Fees</Badge>
              <h2 className="text-3xl sm:text-4xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Transfer & Service{" "}
                <span className="text-gold-500">Fees</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                Domestic ACH and SEPA transfers are free. International wire fees are among the lowest in the industry.
              </p>
            </FadeInView>
            <div className="bg-white dark:bg-navy-800 rounded-2xl shadow-card border border-gray-100 dark:border-navy-700 overflow-hidden">
              <div className="grid grid-cols-3 gap-4 p-4 bg-gray-50 dark:bg-navy-900 text-xs font-semibold uppercase tracking-wider text-navy-400">
                <span>Service</span>
                <span className="text-right">Fee</span>
                <span className="text-right">Note</span>
              </div>
              <div className="divide-y divide-gray-100 dark:divide-navy-700">
                {transferFees.map((item) => (
                  <div key={item.service} className="grid grid-cols-3 gap-4 px-4 py-3 text-sm">
                    <span className="text-navy-700 dark:text-navy-200">{item.service}</span>
                    <span className="text-right font-semibold text-navy-900 dark:text-white">{item.fee}</span>
                    <span className="text-right text-navy-400 text-xs self-center">{item.note}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </section>

        <section id="loans" className="py-20 lg:py-28 bg-white dark:bg-navy-900">
          <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-12">
              <Badge variant="secondary" className="mb-4">Loan Rates</Badge>
              <h2 className="text-3xl sm:text-4xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Competitive{" "}
                <span className="text-gold-500">Loan Rates</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                Rates shown are for qualified borrowers. Your actual rate will depend on creditworthiness, loan amount, and term.
              </p>
            </FadeInView>
            <div className="bg-white dark:bg-navy-800 rounded-2xl shadow-card border border-gray-100 dark:border-navy-700 overflow-hidden">
              <div className="grid grid-cols-4 gap-4 p-4 bg-gray-50 dark:bg-navy-900 text-xs font-semibold uppercase tracking-wider text-navy-400">
                <span>Product</span>
                <span className="text-right">APR Range</span>
                <span className="text-right">Term</span>
                <span className="text-right">Origination Fee</span>
              </div>
              <div className="divide-y divide-gray-100 dark:divide-navy-700">
                {loanRates.map((item) => (
                  <div key={item.product} className="grid grid-cols-4 gap-4 px-4 py-3 text-sm">
                    <span className="text-navy-700 dark:text-navy-200">{item.product}</span>
                    <span className="text-right font-semibold text-navy-900 dark:text-white">{item.rate}</span>
                    <span className="text-right text-navy-400">{item.term}</span>
                    <span className="text-right text-navy-400">{item.fee}</span>
                  </div>
                ))}
              </div>
            </div>
            <p className="text-center text-xs text-navy-400 mt-4">All loans subject to credit approval. Rates and terms subject to change without notice.</p>
          </div>
        </section>

        <section id="forex" className="py-20 lg:py-28 bg-gray-50 dark:bg-navy-950">
          <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-12">
              <Badge variant="secondary" className="mb-4">Foreign Exchange</Badge>
              <h2 className="text-3xl sm:text-4xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Forex{" "}
                <span className="text-gold-500">Rates</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                Competitive exchange rates with transparent spreads. No hidden markups.
              </p>
            </FadeInView>
            <div className="bg-white dark:bg-navy-800 rounded-2xl shadow-card border border-gray-100 dark:border-navy-700 overflow-hidden">
              <div className="grid grid-cols-4 gap-4 p-4 bg-gray-50 dark:bg-navy-900 text-xs font-semibold uppercase tracking-wider text-navy-400">
                <span>Currency</span>
                <span className="text-right">Buy Rate</span>
                <span className="text-right">Sell Rate</span>
                <span className="text-right">Spread</span>
              </div>
              <div className="divide-y divide-gray-100 dark:divide-navy-700">
                {forexRates.map((item) => (
                  <div key={item.currency} className="grid grid-cols-4 gap-4 px-4 py-3 text-sm">
                    <span className="text-navy-700 dark:text-navy-200">{item.currency}</span>
                    <span className="text-right font-medium text-navy-700 dark:text-navy-200">{item.buy}</span>
                    <span className="text-right font-medium text-navy-700 dark:text-navy-200">{item.sell}</span>
                    <span className="text-right text-navy-400">{item.spread}</span>
                  </div>
                ))}
              </div>
            </div>
            <p className="text-center text-xs text-navy-400 mt-4">Rates are indicative and updated every 15 minutes. Actual rates locked at time of transaction.</p>
          </div>
        </section>

        <section className="py-20 gradient-brand text-white text-center relative overflow-hidden">
          <div className="absolute inset-0 opacity-10">
            <div className="absolute top-0 left-1/2 w-96 h-96 rounded-full bg-gold-500 blur-3xl -translate-x-1/2" />
          </div>
          <div className="relative mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <FadeInView>
              <h2 className="text-3xl sm:text-4xl font-bold font-display leading-tight mb-6">
                Have Questions About Pricing?
              </h2>
              <p className="text-lg text-navy-100 mb-8 max-w-2xl mx-auto">
                Our team is happy to explain any fees or rates in detail. We believe in complete transparency.
              </p>
              <div className="flex flex-col sm:flex-row gap-4 justify-center">
                <Button variant="accent" size="xl" className="font-semibold text-base" asChild>
                  <Link href="/contact">Contact Us <ArrowRight className="ml-2 h-5 w-5" /></Link>
                </Button>
                <Button variant="outline" size="xl" className="text-white border-white/30 hover:bg-white/10 text-base" asChild>
                  <Link href="/faq">Visit FAQ</Link>
                </Button>
              </div>
            </FadeInView>
          </div>
        </section>
      </main>
      <Footer />
    </>
  )
}
