import type { Metadata } from "next"
import Link from "next/link"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import Header from "@/components/Header"
import Footer from "@/components/Footer"
import FadeInView from "@/components/FadeInView"
import {
  ArrowRight, CheckCircle, Home, Briefcase, Car, Building2,
  Calculator, Clock, ChevronRight, Percent
} from "lucide-react"

export const metadata: Metadata = {
  title: "Loans | United Standard International Bank",
  description: "Competitive loan products including personal loans, business loans, mortgages, and auto loans. Find the right financing solution for your needs.",
}

const loanProducts = [
  {
    icon: Briefcase,
    title: "Personal Loans",
    desc: "Flexible personal loans for debt consolidation, home improvement, major purchases, or unexpected expenses.",
    rate: "From 6.99% APR",
    term: "Up to 60 months",
    amount: "$1,000 - $100,000",
    features: ["No collateral required", "Fixed monthly payments", "No prepayment penalties", "Same-day funding available"],
  },
  {
    icon: Home,
    title: "Mortgages",
    desc: "Competitive rates for home purchases, refinancing, and home equity lines of credit.",
    rate: "From 3.75% APR",
    term: "15 - 30 years",
    amount: "Up to $3,000,000",
    features: ["Fixed and adjustable rates", "FHA, VA, and conventional loans", "Low down payment options", "Rate lock protection"],
  },
  {
    icon: Building2,
    title: "Business Loans",
    desc: "Capital to start, operate, or expand your business with flexible terms and competitive rates.",
    rate: "From 5.50% APR",
    term: "Up to 25 years",
    amount: "$10,000 - $5,000,000",
    features: ["Working capital financing", "Equipment financing", "Commercial real estate", "SBA loan programs"],
  },
  {
    icon: Car,
    title: "Auto Loans",
    desc: "Financing for new and used vehicles with fast approval and competitive rates.",
    rate: "From 4.25% APR",
    term: "Up to 72 months",
    amount: "$5,000 - $250,000",
    features: ["New & used car financing", "Refinancing options", "No application fee", "Pre-approval available"],
  },
]

export default function LoansPage() {
  return (
    <>
      <Header />
      <main>
        <section className="relative pt-32 pb-20 lg:pt-40 lg:pb-28 overflow-hidden">
          <div className="absolute inset-0 gradient-hero" />
          <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?q=80&w=2072&auto=format&fit=crop')] bg-cover bg-center opacity-10" />
          <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="max-w-3xl">
              <Badge variant="outline" className="border-gold-400/50 text-gold-400 bg-gold-500/10 mb-4">Loan Products</Badge>
              <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold font-display text-white leading-tight">
                Financing Solutions{" "}
                <span className="text-gradient">at Great Rates</span>
              </h1>
              <p className="mt-6 text-lg text-navy-100 max-w-2xl leading-relaxed">
                Whether you&apos;re buying a home, starting a business, or pursuing a personal goal, 
                we offer competitive loan products with transparent terms and fast approvals.
              </p>
              <div className="mt-10 flex flex-col sm:flex-row gap-4">
                <Button variant="accent" size="xl" className="font-semibold text-base" asChild>
                  <Link href="#products">View Loan Options <ArrowRight className="ml-2 h-5 w-5" /></Link>
                </Button>
                <Button variant="outline" size="xl" className="text-white border-white/30 hover:bg-white/10 text-base" asChild>
                  <Link href="#calculator">Try Our Calculator</Link>
                </Button>
              </div>
            </FadeInView>
          </div>
        </section>

        <section id="products" className="py-20 lg:py-28 bg-white dark:bg-navy-900">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Loan Products</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Find the Right{" "}
                <span className="text-gold-500">Loan for You</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                Transparent rates, flexible terms, and fast approvals. All loans subject to credit approval.
              </p>
            </FadeInView>
            <div className="grid md:grid-cols-2 gap-8">
              {loanProducts.map((loan) => (
                <Card key={loan.title} className="group hover:shadow-card-hover transition-all duration-300 border border-gray-100 dark:border-navy-700 bg-white dark:bg-navy-800 overflow-hidden">
                  <div className="flex">
                    <div className="p-8 flex-1">
                      <div className="flex items-center gap-3 mb-4">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-gold-500/10">
                          <loan.icon className="h-5 w-5 text-gold-500" />
                        </div>
                        <div>
                          <h3 className="text-xl font-bold font-display text-navy-900 dark:text-white">{loan.title}</h3>
                        </div>
                      </div>
                      <p className="text-sm text-navy-400 dark:text-navy-300 mb-4">{loan.desc}</p>
                      <div className="grid grid-cols-3 gap-3 mb-6">
                        <div className="bg-gray-50 dark:bg-navy-900 rounded-xl p-3 text-center">
                          <p className="text-xs text-navy-400 mb-1">Rate</p>
                          <p className="text-sm font-bold text-gold-500">{loan.rate}</p>
                        </div>
                        <div className="bg-gray-50 dark:bg-navy-900 rounded-xl p-3 text-center">
                          <p className="text-xs text-navy-400 mb-1">Term</p>
                          <p className="text-sm font-bold text-navy-900 dark:text-white">{loan.term}</p>
                        </div>
                        <div className="bg-gray-50 dark:bg-navy-900 rounded-xl p-3 text-center">
                          <p className="text-xs text-navy-400 mb-1">Amount</p>
                          <p className="text-sm font-bold text-navy-900 dark:text-white">{loan.amount}</p>
                        </div>
                      </div>
                      <ul className="space-y-2 mb-6">
                        {loan.features.map((f) => (
                          <li key={f} className="flex items-start gap-2 text-xs text-navy-500 dark:text-navy-300">
                            <CheckCircle className="h-3.5 w-3.5 text-gold-500 flex-shrink-0 mt-0.5" />
                            {f}
                          </li>
                        ))}
                      </ul>
                      <Button variant="accent" size="sm" className="font-semibold">
                        Apply Now <ArrowRight className="ml-1 h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                </Card>
              ))}
            </div>
          </div>
        </section>

        <section id="calculator" className="py-20 lg:py-28 bg-gray-50 dark:bg-navy-950">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="grid lg:grid-cols-2 gap-12 items-center">
              <div>
                <Badge variant="secondary" className="mb-4">Loan Calculator</Badge>
                <h2 className="text-3xl sm:text-4xl font-bold font-display text-navy-900 dark:text-white leading-tight mb-6">
                  Estimate Your{" "}
                  <span className="text-gold-500">Monthly Payments</span>
                </h2>
                <p className="text-lg text-navy-400 dark:text-navy-300 mb-6">
                  Use our loan calculator to estimate your monthly payments. Enter your desired loan amount, 
                  term, and estimated interest rate to see what your payments would look like.
                </p>
                <div className="bg-white dark:bg-navy-800 rounded-2xl p-8 shadow-card border border-gray-100 dark:border-navy-700">
                  <div className="space-y-6">
                    <div>
                      <label className="text-sm font-medium text-navy-700 dark:text-navy-200 mb-2 block">Loan Amount</label>
                      <div className="relative">
                        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-navy-400 text-sm">$</span>
                        <input type="number" defaultValue={50000} className="w-full rounded-xl border border-gray-200 dark:border-navy-600 bg-transparent py-3 pl-8 pr-4 text-sm text-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gold-500" />
                      </div>
                    </div>
                    <div>
                      <label className="text-sm font-medium text-navy-700 dark:text-navy-200 mb-2 block">Interest Rate (%)</label>
                      <input type="number" defaultValue={6.99} step={0.01} className="w-full rounded-xl border border-gray-200 dark:border-navy-600 bg-transparent py-3 px-4 text-sm text-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gold-500" />
                    </div>
                    <div>
                      <label className="text-sm font-medium text-navy-700 dark:text-navy-200 mb-2 block">Loan Term (months)</label>
                      <input type="number" defaultValue={60} className="w-full rounded-xl border border-gray-200 dark:border-navy-600 bg-transparent py-3 px-4 text-sm text-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gold-500" />
                    </div>
                    <div className="pt-4 border-t border-gray-100 dark:border-navy-700">
                      <div className="flex justify-between items-center">
                        <span className="text-sm font-medium text-navy-400">Estimated Monthly Payment</span>
                        <span className="text-2xl font-bold font-display text-gold-500">$990.06</span>
                      </div>
                      <p className="text-xs text-navy-400 mt-1">* Actual rate and payment may vary based on credit approval.</p>
                    </div>
                  </div>
                </div>
              </div>
              <div className="space-y-6">
                <div className="bg-white dark:bg-navy-800 rounded-2xl p-8 shadow-card border border-gray-100 dark:border-navy-700">
                  <h3 className="text-xl font-bold font-display text-navy-900 dark:text-white mb-4">Why Choose USIB for Your Loan?</h3>
                  <div className="space-y-4">
                    {[
                      { title: "Competitive Rates", desc: "We offer some of the most competitive rates in the industry, backed by our strong financial foundation." },
                      { title: "Fast Approvals", desc: "Most loan applications are reviewed within 24 hours, with same-day funding available for qualified borrowers." },
                      { title: "Flexible Terms", desc: "Choose repayment terms that work for your budget, from short-term to long-term options." },
                      { title: "No Hidden Fees", desc: "We believe in transparent lending. No prepayment penalties, no surprise fees, no hidden charges." },
                    ].map((item) => (
                      <div key={item.title} className="flex gap-3">
                        <CheckCircle className="h-5 w-5 text-gold-500 flex-shrink-0 mt-0.5" />
                        <div>
                          <h4 className="font-semibold text-navy-900 dark:text-white text-sm">{item.title}</h4>
                          <p className="text-xs text-navy-400 dark:text-navy-300">{item.desc}</p>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            </FadeInView>
          </div>
        </section>

        <section className="py-20 gradient-brand text-white text-center relative overflow-hidden">
          <div className="absolute inset-0 opacity-10">
            <div className="absolute top-0 left-1/2 w-96 h-96 rounded-full bg-gold-500 blur-3xl -translate-x-1/2" />
          </div>
          <div className="relative mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <FadeInView>
              <Badge variant="outline" className="border-gold-400/50 text-gold-400 bg-gold-500/10 mb-6">Get Funded Today</Badge>
              <h2 className="text-3xl sm:text-4xl font-bold font-display leading-tight mb-6">
                Ready to Apply for a Loan?
              </h2>
              <p className="text-lg text-navy-100 mb-8 max-w-2xl mx-auto">
                The application process takes just minutes. Get your decision quickly and your funds fast.
              </p>
              <div className="flex flex-col sm:flex-row gap-4 justify-center">
                <Button variant="accent" size="xl" className="font-semibold text-base" asChild>
                  <Link href="#products">Apply Now <ArrowRight className="ml-2 h-5 w-5" /></Link>
                </Button>
                <Button variant="outline" size="xl" className="text-white border-white/30 hover:bg-white/10 text-base" asChild>
                  <Link href="/contact">Speak with a Loan Officer</Link>
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
