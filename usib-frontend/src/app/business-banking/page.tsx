import type { Metadata } from "next"
import Link from "next/link"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import Header from "@/components/Header"
import Footer from "@/components/Footer"
import FadeInView from "@/components/FadeInView"
import {
  ArrowRight, CheckCircle, Building2, TrendingUp, Globe,
  Smartphone, Shield, Users, BarChart3, Briefcase,
  ChevronRight, PiggyBank, CreditCard, Receipt, DollarSign
} from "lucide-react"

export const metadata: Metadata = {
  title: "Business Banking | United Standard International Bank",
  description: "Comprehensive business banking solutions including accounts, loans, treasury management, and international trade services.",
}

const businessAccounts = [
  {
    title: "Business Checking",
    desc: "Everyday account built for businesses with high transaction volumes and cash flow needs.",
    features: ["Unlimited transactions", "No monthly fees", "Remote deposit capture", "Multi-user access", "ACH processing", "Business debit cards"],
    rate: "From $0/month",
  },
  {
    title: "Business Savings",
    desc: "Earn interest on your operating reserves while maintaining liquidity.",
    features: ["Competitive interest rates", "No minimum balance", "Automatic transfers", "Linked checking account", "FDIC insured", "Monthly statements"],
    rate: "2.50% APY",
  },
  {
    title: "Business Money Market",
    desc: "Higher yields with check-writing privileges and flexible access.",
    features: ["Tiered interest rates", "Check-writing capability", "ATM access", "Online transfers", "Monthly compounding", "Relationship rewards"],
    rate: "3.75% APY",
  },
  {
    title: "Non-Profit Accounts",
    desc: "Specialized banking solutions for non-profit organizations with fee waivers.",
    features: ["Fee-free banking", "Grant management tools", "Donor receipt tracking", "Board reporting", "Compliance support", "Dedicated advisor"],
    rate: "From $0/month",
  },
]

const services = [
  { icon: BarChart3, title: "Cash Management", desc: "Optimize your cash flow with advanced liquidity management, automated collections, and payment solutions tailored to your business." },
  { icon: Globe, title: "International Trade", desc: "Facilitate global commerce with letters of credit, trade financing, foreign exchange, and cross-border payment solutions." },
  { icon: Briefcase, title: "Commercial Lending", desc: "Access capital for expansion, equipment, working capital, and real estate with competitive rates and flexible terms." },
  { icon: Users, title: "Payroll Services", desc: "Streamline payroll processing with direct deposit, tax filing, and integrated HR solutions for businesses of all sizes." },
  { icon: CreditCard, title: "Merchant Services", desc: "Accept payments anywhere with POS systems, online payment gateways, mobile payments, and recurring billing solutions." },
  { icon: Shield, title: "Fraud Protection", desc: "Protect your business with advanced fraud detection, positive pay services, account alerts, and dedicated security support." },
]

const treasuryFeatures = [
  { title: "Automated Collections", desc: "Accelerate receivables with lockbox services, ACH collections, and electronic invoice presentment." },
  { title: "Payment Solutions", desc: "Streamline payables with batch payments, wire transfers, positive pay, and virtual card programs." },
  { title: "Liquidity Management", desc: "Optimize interest income with sweeps, target balance accounts, and multi-currency pooling." },
  { title: "Reporting & Analytics", desc: "Gain insights with real-time dashboards, custom reports, and API integration with your ERP systems." },
]

export default function BusinessBankingPage() {
  return (
    <>
      <Header />
      <main>
        <section className="relative pt-32 pb-20 lg:pt-40 lg:pb-28 overflow-hidden">
          <div className="absolute inset-0 gradient-hero" />
          <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=2070&auto=format&fit=crop')] bg-cover bg-center opacity-10" />
          <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="max-w-3xl">
              <Badge variant="outline" className="border-gold-400/50 text-gold-400 bg-gold-500/10 mb-4">Business Banking</Badge>
              <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold font-display text-white leading-tight">
                Banking That{" "}
                <span className="text-gradient">Means Business</span>
              </h1>
              <p className="mt-6 text-lg text-navy-100 max-w-2xl leading-relaxed">
                From startups to enterprises, our business banking solutions provide the financial tools, 
                expertise, and support you need to grow and succeed in today&apos;s competitive landscape.
              </p>
              <div className="mt-10 flex flex-col sm:flex-row gap-4">
                <Button variant="accent" size="xl" className="font-semibold text-base" asChild>
                  <Link href="#accounts">Open a Business Account <ArrowRight className="ml-2 h-5 w-5" /></Link>
                </Button>
                <Button variant="outline" size="xl" className="text-white border-white/30 hover:bg-white/10 text-base" asChild>
                  <Link href="#services">Explore Services</Link>
                </Button>
              </div>
            </FadeInView>
          </div>
        </section>

        <section id="accounts" className="py-20 lg:py-28 bg-white dark:bg-navy-900">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Business Accounts</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Built for{" "}
                <span className="text-gold-500">Business Success</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                Flexible account options designed to meet the unique needs of your business.
              </p>
            </FadeInView>
            <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
              {businessAccounts.map((account) => (
                <Card key={account.title} className="group hover:shadow-card-hover transition-all duration-300 border border-gray-100 dark:border-navy-700 bg-white dark:bg-navy-800">
                  <CardContent className="p-6">
                    <Badge variant="success" className="mb-3">{account.rate}</Badge>
                    <h3 className="text-lg font-bold font-display text-navy-900 dark:text-white mb-2">{account.title}</h3>
                    <p className="text-sm text-navy-400 dark:text-navy-300 mb-4">{account.desc}</p>
                    <ul className="space-y-2 mb-6">
                      {account.features.map((f) => (
                        <li key={f} className="flex items-start gap-2 text-xs text-navy-500 dark:text-navy-300">
                          <CheckCircle className="h-3.5 w-3.5 text-gold-500 flex-shrink-0 mt-0.5" />
                          {f}
                        </li>
                      ))}
                    </ul>
                    <Button variant="accent" size="sm" className="w-full font-semibold">
                      Open Account
                    </Button>
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>
        </section>

        <section id="services" className="py-20 lg:py-28 bg-gray-50 dark:bg-navy-950">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Business Services</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Comprehensive{" "}
                <span className="text-gold-500">Business Solutions</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                Everything your business needs to manage finances, grow operations, and compete globally.
              </p>
            </FadeInView>
            <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
              {services.map((service) => (
                <Card key={service.title} className="bg-white dark:bg-navy-800 border border-gray-100 dark:border-navy-700 hover:shadow-card-hover transition-all duration-300">
                  <CardContent className="p-8">
                    <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-gold-500/10 mb-5">
                      <service.icon className="h-6 w-6 text-gold-500" />
                    </div>
                    <h3 className="text-lg font-bold font-display text-navy-900 dark:text-white mb-3">{service.title}</h3>
                    <p className="text-sm text-navy-400 dark:text-navy-300 leading-relaxed">{service.desc}</p>
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>
        </section>

        <section className="py-20 lg:py-28 bg-white dark:bg-navy-900">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Treasury Management</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Optimize Your{" "}
                <span className="text-gold-500">Cash Flow</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                Advanced treasury solutions to maximize liquidity, reduce risk, and improve operational efficiency.
              </p>
            </FadeInView>
            <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
              {treasuryFeatures.map((feature) => (
                <Card key={feature.title} className="group hover:shadow-card-hover transition-all duration-300 border border-gray-100 dark:border-navy-700 bg-white dark:bg-navy-800">
                  <CardContent className="p-6">
                    <h3 className="text-lg font-bold font-display text-navy-900 dark:text-white mb-3">{feature.title}</h3>
                    <p className="text-sm text-navy-400 dark:text-navy-300">{feature.desc}</p>
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
                Empower Your Business with USIB
              </h2>
              <p className="text-lg text-navy-100 mb-8 max-w-2xl mx-auto">
                Join thousands of businesses that trust USIB for their banking needs. Speak with a business banking specialist today.
              </p>
              <div className="flex flex-col sm:flex-row gap-4 justify-center">
                <Button variant="accent" size="xl" className="font-semibold text-base" asChild>
                  <Link href="/contact">Talk to a Specialist <ArrowRight className="ml-2 h-5 w-5" /></Link>
                </Button>
                <Button variant="outline" size="xl" className="text-white border-white/30 hover:bg-white/10 text-base" asChild>
                  <Link href="/business-banking">Learn More</Link>
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
