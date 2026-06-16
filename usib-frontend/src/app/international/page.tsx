import type { Metadata } from "next"
import Link from "next/link"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import Header from "@/components/Header"
import Footer from "@/components/Footer"
import FadeInView from "@/components/FadeInView"
import {
  ArrowRight, CheckCircle, Globe, Send, TrendingDown,
  Clock, Shield, RefreshCw, ChevronRight, Landmark,
  ArrowLeftRight
} from "lucide-react"

export const metadata: Metadata = {
  title: "International Banking | United Standard International Bank",
  description: "Global banking solutions including currency exchange, international wire transfers, SWIFT payments, and multi-currency accounts.",
}

const currencies = [
  { code: "USD", name: "US Dollar", buy: "1.0000", sell: "1.0000" },
  { code: "EUR", name: "Euro", buy: "0.9150", sell: "0.9350" },
  { code: "GBP", name: "British Pound", buy: "0.7820", sell: "0.8020" },
  { code: "JPY", name: "Japanese Yen", buy: "149.50", sell: "152.50" },
  { code: "CHF", name: "Swiss Franc", buy: "0.8810", sell: "0.9010" },
  { code: "CAD", name: "Canadian Dollar", buy: "1.3540", sell: "1.3740" },
  { code: "AUD", name: "Australian Dollar", buy: "1.5210", sell: "1.5410" },
  { code: "CNY", name: "Chinese Yuan", buy: "7.1950", sell: "7.2450" },
]

const benefits = [
  { icon: TrendingDown, title: "Competitive Rates", desc: "Enjoy some of the best exchange rates in the market with spreads as low as 0.2%." },
  { icon: Clock, title: "Fast Transfers", desc: "Most international transfers are completed within 1-2 business days." },
  { icon: Shield, title: "Secure Transactions", desc: "Every transfer is protected by bank-grade encryption and regulatory compliance." },
  { icon: RefreshCw, title: "Rate Alerts", desc: "Set target exchange rates and get notified when the market moves in your favor." },
]

export default function InternationalPage() {
  return (
    <>
      <Header />
      <main>
        <section className="relative pt-32 pb-20 lg:pt-40 lg:pb-28 overflow-hidden">
          <div className="absolute inset-0 gradient-hero" />
          <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?q=80&w=2070&auto=format&fit=crop')] bg-cover bg-center opacity-10" />
          <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="max-w-3xl">
              <Badge variant="outline" className="border-gold-400/50 text-gold-400 bg-gold-500/10 mb-4">International Banking</Badge>
              <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold font-display text-white leading-tight">
                Banking Without{" "}
                <span className="text-gradient">Borders</span>
              </h1>
              <p className="mt-6 text-lg text-navy-100 max-w-2xl leading-relaxed">
                Send money globally, exchange currencies at competitive rates, and manage your international 
                finances with confidence. Fast, secure, and transparent cross-border banking.
              </p>
              <div className="mt-10 flex flex-col sm:flex-row gap-4">
                <Button variant="accent" size="xl" className="font-semibold text-base" asChild>
                  <Link href="#transfer">Start a Transfer <ArrowRight className="ml-2 h-5 w-5" /></Link>
                </Button>
                <Button variant="outline" size="xl" className="text-white border-white/30 hover:bg-white/10 text-base" asChild>
                  <Link href="#rates">View Exchange Rates</Link>
                </Button>
              </div>
            </FadeInView>
          </div>
        </section>

        <section className="py-20 lg:py-28 bg-white dark:bg-navy-900">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="grid lg:grid-cols-2 gap-12 items-center">
              <div>
                <Badge variant="secondary" className="mb-4">International Transfers</Badge>
                <h2 className="text-3xl sm:text-4xl font-bold font-display text-navy-900 dark:text-white leading-tight mb-6">
                  Send Money{" "}
                  <span className="text-gold-500">Worldwide</span>
                </h2>
                <p className="text-lg text-navy-400 dark:text-navy-300 mb-8">
                  Transfer funds to over 50 countries in 30+ currencies. Whether for business or personal 
                  needs, our international payment solutions are fast, reliable, and transparent.
                </p>
                <div className="space-y-4 mb-8">
                  {[
                    { icon: Send, title: "SWIFT Transfers", desc: "Global wire transfers to almost any bank worldwide. Secure and trackable." },
                    { icon: Globe, title: "SEPA Transfers", desc: "Fast euro transfers within the SEPA zone, typically arriving within 1 business day." },
                    { icon: Landmark, title: "Wire Transfers", desc: "Domestic and international wire transfers with same-day processing options." },
                  ].map((item) => (
                    <div key={item.title} className="flex gap-4 p-4 bg-gray-50 dark:bg-navy-800 rounded-xl">
                      <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-gold-500/10 flex-shrink-0">
                        <item.icon className="h-5 w-5 text-gold-500" />
                      </div>
                      <div>
                        <h3 className="font-semibold text-navy-900 dark:text-white text-sm">{item.title}</h3>
                        <p className="text-xs text-navy-400 dark:text-navy-300">{item.desc}</p>
                      </div>
                    </div>
                  ))}
                </div>
                <Button variant="accent" size="lg" className="font-semibold" asChild>
                  <Link href="#transfer">Start Your Transfer <ArrowRight className="ml-2 h-4 w-4" /></Link>
                </Button>
              </div>
              <div className="bg-white dark:bg-navy-800 rounded-2xl shadow-elevated p-8 border border-gray-100 dark:border-navy-700">
                <h3 className="text-lg font-bold font-display text-navy-900 dark:text-white mb-6">Send Money Abroad</h3>
                <div className="space-y-5">
                  <div>
                    <label className="text-sm font-medium text-navy-700 dark:text-navy-200 mb-2 block">You send</label>
                    <div className="flex rounded-xl border border-gray-200 dark:border-navy-600 overflow-hidden">
                      <span className="flex items-center px-3 bg-gray-50 dark:bg-navy-900 text-sm font-medium text-navy-500">USD</span>
                      <input type="number" defaultValue={10000} className="flex-1 py-3 px-4 text-sm text-navy-900 dark:text-white bg-transparent focus:outline-none" />
                    </div>
                  </div>
                  <div className="flex justify-center">
                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-gold-500/10">
                      <ArrowLeftRight className="h-5 w-5 text-gold-500" />
                    </div>
                  </div>
                  <div>
                    <label className="text-sm font-medium text-navy-700 dark:text-navy-200 mb-2 block">Recipient receives</label>
                    <div className="flex rounded-xl border border-gray-200 dark:border-navy-600 overflow-hidden">
                      <span className="flex items-center px-3 bg-gray-50 dark:bg-navy-900 text-sm font-medium text-navy-500">EUR</span>
                      <input type="number" defaultValue={9250} className="flex-1 py-3 px-4 text-sm text-navy-900 dark:text-white bg-transparent focus:outline-none" />
                    </div>
                  </div>
                  <div className="p-4 bg-gray-50 dark:bg-navy-900 rounded-xl space-y-2">
                    <div className="flex justify-between text-sm">
                      <span className="text-navy-400">Exchange rate</span>
                      <span className="font-medium text-navy-700 dark:text-navy-200">1 USD = 0.925 EUR</span>
                    </div>
                    <div className="flex justify-between text-sm">
                      <span className="text-navy-400">Transfer fee</span>
                      <span className="font-medium text-green-600">$0.00</span>
                    </div>
                    <div className="flex justify-between text-sm">
                      <span className="text-navy-400">Delivery time</span>
                      <span className="font-medium text-navy-700 dark:text-navy-200">1-2 business days</span>
                    </div>
                  </div>
                  <Button variant="accent" size="lg" className="w-full font-semibold">
                    Continue <ArrowRight className="ml-2 h-4 w-4" />
                  </Button>
                </div>
              </div>
            </FadeInView>
          </div>
        </section>

        <section id="rates" className="py-20 lg:py-28 bg-gray-50 dark:bg-navy-950">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Live Rates</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Transparent{" "}
                <span className="text-gold-500">Exchange Rates</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                No hidden markups. What you see is what you get. Rates updated in real-time.
              </p>
            </FadeInView>
            <div className="max-w-3xl mx-auto bg-white dark:bg-navy-800 rounded-2xl shadow-card border border-gray-100 dark:border-navy-700 overflow-hidden">
              <div className="grid grid-cols-3 gap-4 p-4 bg-gray-50 dark:bg-navy-900 text-xs font-semibold uppercase tracking-wider text-navy-400">
                <span>Currency</span>
                <span className="text-right">Buy</span>
                <span className="text-right">Sell</span>
              </div>
              <div className="divide-y divide-gray-100 dark:divide-navy-700">
                {currencies.map((currency) => (
                  <div key={currency.code} className="grid grid-cols-3 gap-4 px-4 py-3 text-sm">
                    <div className="flex items-center gap-2">
                      <span className="font-bold text-navy-900 dark:text-white">{currency.code}</span>
                      <span className="text-navy-400 text-xs">{currency.name}</span>
                    </div>
                    <span className="text-right font-medium text-navy-700 dark:text-navy-200">{currency.buy}</span>
                    <span className="text-right font-medium text-navy-700 dark:text-navy-200">{currency.sell}</span>
                  </div>
                ))}
              </div>
            </div>
            <p className="text-center text-xs text-navy-400 mt-4">Rates are indicative and may change. Actual rates are locked in at the time of transfer.</p>
          </div>
        </section>

        <section className="py-20 lg:py-28 bg-white dark:bg-navy-900">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Why Choose USIB</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Benefits of{" "}
                <span className="text-gold-500">Global Banking</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                We make international banking simple, transparent, and cost-effective.
              </p>
            </FadeInView>
            <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
              {benefits.map((benefit) => (
                <Card key={benefit.title} className="bg-white dark:bg-navy-800 border border-gray-100 dark:border-navy-700 text-center hover:shadow-card-hover transition-all duration-300">
                  <CardContent className="p-8">
                    <div className="flex justify-center mb-4">
                      <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-gold-500/10">
                        <benefit.icon className="h-6 w-6 text-gold-500" />
                      </div>
                    </div>
                    <h3 className="text-lg font-bold font-display text-navy-900 dark:text-white mb-3">{benefit.title}</h3>
                    <p className="text-sm text-navy-400 dark:text-navy-300">{benefit.desc}</p>
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>
        </section>

        <section className="py-20 gradient-brand text-white text-center relative overflow-hidden">
          <div className="absolute inset-0 opacity-10">
            <div className="absolute top-0 left-1/2 w-96 h-96 rounded-full bg-gold-500 blur-3xl -translate-x-1/2" />
          </div>
          <div className="relative mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <FadeInView>
              <h2 className="text-3xl sm:text-4xl font-bold font-display leading-tight mb-6">
                Ready to Send Money Globally?
              </h2>
              <p className="text-lg text-navy-100 mb-8 max-w-2xl mx-auto">
                First transfer is free. Create an account or sign in to start your international transfer.
              </p>
              <div className="flex flex-col sm:flex-row gap-4 justify-center">
                <Button variant="accent" size="xl" className="font-semibold text-base" asChild>
                  <Link href="#transfer">Start Your Transfer <ArrowRight className="ml-2 h-5 w-5" /></Link>
                </Button>
                <Button variant="outline" size="xl" className="text-white border-white/30 hover:bg-white/10 text-base" asChild>
                  <Link href="/contact">Talk to an Expert</Link>
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
