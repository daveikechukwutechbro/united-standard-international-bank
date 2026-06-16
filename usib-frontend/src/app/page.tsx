import Link from "next/link"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import Header from "@/components/Header"
import Footer from "@/components/Footer"
import FadeInView from "@/components/FadeInView"
import {
  Shield, Lock, Fingerprint, Globe, Building2, Banknote, ArrowRight,
  Landmark, PieChart, Send, TrendingUp, Users, Award,
  ChevronRight, CheckCircle, Star, Quote, Clock
} from "lucide-react"

const trustIndicators = [
  { icon: Shield, label: "FDIC Insured", desc: "Up to $250,000" },
  { icon: Lock, label: "256-Bit Encryption", desc: "Bank-grade security" },
  { icon: Fingerprint, label: "Biometric Auth", desc: "Multi-factor protection" },
  { icon: Globe, label: "Global Reach", desc: "50+ countries" },
]

const products = [
  {
    icon: Building2,
    title: "Personal Banking",
    desc: "Checking, savings, and investment accounts tailored to your financial goals.",
    href: "/personal-banking",
  },
  {
    icon: Landmark,
    title: "Business Banking",
    desc: "Comprehensive solutions to help your business grow and manage cash flow.",
    href: "/business-banking",
  },
  {
    icon: TrendingUp,
    title: "Wealth Management",
    desc: "Expert portfolio management and financial planning for high-net-worth clients.",
    href: "/personal-banking",
  },
  {
    icon: Send,
    title: "International Transfers",
    desc: "Fast, secure cross-border payments in 30+ currencies with competitive rates.",
    href: "/international",
  },
]

const stats = [
  { value: "$847B", label: "Assets Under Management" },
  { value: "50+", label: "Countries Served" },
  { value: "12M+", label: "Customers Worldwide" },
  { value: "38", label: "Years of Excellence" },
]

const accountTypes = [
  {
    title: "Checking Accounts",
    desc: "Everyday spending with no monthly fees, free ATM access, and mobile banking.",
    features: ["No monthly maintenance fees", "Free atm network (55,000+ ATMs)", "Overdraft protection", "Mobile check deposit"],
    href: "/personal-banking",
    rate: "0.01% APY",
  },
  {
    title: "Savings Accounts",
    desc: "Grow your money with competitive interest rates and flexible access.",
    features: ["High-yield interest rates", "No minimum balance", "Automatic savings tools", "Linked checking account"],
    href: "/personal-banking",
    rate: "4.25% APY",
  },
  {
    title: "Fixed Deposits",
    desc: "Lock in guaranteed returns with flexible terms from 3 months to 5 years.",
    features: ["Guaranteed fixed returns", "Terms from 3-60 months", "Competitive interest rates", "Auto-renewal options"],
    href: "/personal-banking",
    rate: "5.10% APY",
  },
  {
    title: "Business Accounts",
    desc: "Built for businesses of all sizes with tools to manage finances efficiently.",
    features: ["Business checking & savings", "Merchant services", "Payroll integration", "Multi-user access"],
    href: "/business-banking",
    rate: "From 2.50% APY",
  },
]

const loansTeaser = [
  {
    title: "Personal Loans",
    desc: "Fund your dreams with flexible personal loans.",
    rate: "From 6.99% APR",
    href: "/loans",
    term: "Up to 60 months",
  },
  {
    title: "Business Loans",
    desc: "Capital to start, operate, or expand your business.",
    rate: "From 5.50% APR",
    href: "/loans",
    term: "Up to 25 years",
  },
  {
    title: "Mortgages",
    desc: "Competitive rates for home purchases and refinancing.",
    rate: "From 3.75% APR",
    href: "/loans",
    term: "15-30 year terms",
  },
]

const testimonials = [
  {
    quote: "United Standard International Bank transformed how we manage our global finances. Their international banking services are second to none.",
    name: "Sarah Chen",
    title: "CEO, Meridian Global Trading",
    rating: 5,
  },
  {
    quote: "The wealth management team provided exceptional guidance for my portfolio. I've seen consistent growth and their advice has been invaluable.",
    name: "James Mitchell",
    title: "Private Banking Client",
    rating: 5,
  },
  {
    quote: "As a small business owner, I appreciate the personal attention and tailored solutions. They truly understand the needs of growing businesses.",
    name: "Maria Rodriguez",
    title: "Founder, Artisan Coffee Co.",
    rating: 5,
  },
]

const faqItems = [
  {
    q: "How do I open an account with USIB?",
    a: "Opening an account is simple and can be done entirely online. Visit our Create Account page, choose your account type, and complete the application. You'll need a valid ID, proof of address, and initial deposit. Most accounts are opened within 24 hours.",
  },
  {
    q: "What security measures does USIB have in place?",
    a: "We employ bank-grade 256-bit SSL encryption, multi-factor authentication, biometric login options, real-time fraud monitoring, and automatic session timeouts. Your accounts are also FDIC insured up to $250,000.",
  },
  {
    q: "How do I make international transfers?",
    a: "International transfers can be initiated through our online banking platform or mobile app. Simply add a beneficiary, select the currency pair, and confirm the transfer. We support SWIFT and SEPA transfers with competitive exchange rates.",
  },
  {
    q: "What are the fees for maintaining an account?",
    a: "Our standard checking accounts have no monthly maintenance fees. Savings accounts require a minimum daily balance of $100. Please refer to our Pricing page for a complete breakdown of all fees and charges.",
  },
  {
    q: "Does USIB offer mobile banking?",
    a: "Yes, our mobile banking app is available for iOS and Android. You can check balances, transfer funds, deposit checks, pay bills, manage cards, and contact support directly from your smartphone.",
  },
  {
    q: "How can I contact customer support?",
    a: "You can reach us 24/7 by phone at +1 (800) 555-0199, email at support@usib.com, or through our secure messaging system in online banking. We also have physical branches in major cities worldwide.",
  },
]

export default function HomePage() {
  return (
    <>
      <Header />
      <main>
        <section className="relative min-h-screen flex items-center overflow-hidden">
          <div className="absolute inset-0 gradient-hero" />
          <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=2070&auto=format&fit=crop')] bg-cover bg-center opacity-20" />
          <div className="absolute inset-0 bg-gradient-to-r from-navy-900/90 via-navy-900/70 to-transparent" />
          <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-32 lg:py-40 w-full">
            <FadeInView>
              <Badge variant="outline" className="border-gold-400/50 text-gold-400 bg-gold-500/10 mb-6">
                Trusted since 1987
              </Badge>
              <h1 className="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-bold font-display text-white leading-tight max-w-4xl">
                Banking Without{" "}
                <span className="text-gradient">Boundaries</span>
              </h1>
              <p className="mt-6 text-lg sm:text-xl text-navy-100 max-w-2xl leading-relaxed">
                Experience premium global banking with personalized service, 
                cutting-edge security, and a commitment to your financial success. 
                Whether personal or business, we&apos;re here to help you thrive.
              </p>
              <div className="mt-10 flex flex-col sm:flex-row gap-4">
                <Button variant="accent" size="xl" className="font-semibold text-base" asChild>
                  <Link href="/personal-banking">
                    Open an Account
                    <ArrowRight className="ml-2 h-5 w-5" />
                  </Link>
                </Button>
                <Button
                  variant="outline"
                  size="xl"
                  className="text-white border-white/30 hover:bg-white/10 text-base"
                  asChild
                >
                  <Link href="/personal-banking">Explore Services</Link>
                </Button>
              </div>
            </FadeInView>
          </div>
        </section>

        <section className="relative -mt-16 z-10 pb-16">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView delay={0.3}>
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                {trustIndicators.map((item) => (
                  <div key={item.label} className="bg-white dark:bg-navy-800 rounded-2xl p-6 shadow-card border border-gray-100 dark:border-navy-700 text-center">
                    <div className="flex justify-center mb-3">
                      <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-gold-500/10">
                        <item.icon className="h-6 w-6 text-gold-500" />
                      </div>
                    </div>
                    <h3 className="text-sm font-semibold text-navy-900 dark:text-white">{item.label}</h3>
                    <p className="text-xs text-navy-400 dark:text-navy-300 mt-1">{item.desc}</p>
                  </div>
                ))}
              </div>
            </FadeInView>
          </div>
        </section>

        <section className="py-20 lg:py-28 bg-white dark:bg-navy-900">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Bank-Grade Security</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Your Security Is Our{" "}
                <span className="text-gold-500">Foundation</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                We employ the most advanced security technologies to protect your accounts and personal information.
              </p>
            </FadeInView>
            <div className="grid md:grid-cols-3 gap-8">
              {[
                { icon: Lock, title: "256-Bit Encryption", desc: "Military-grade encryption protects all your data and transactions. Every connection is secured with advanced SSL/TLS protocols.", color: "from-blue-500 to-blue-600" },
                { icon: Fingerprint, title: "Biometric Authentication", desc: "Access your accounts securely with fingerprint and facial recognition technology. Multi-factor authentication is always available.", color: "from-navy-500 to-navy-600" },
                { icon: Shield, title: "Real-Time Fraud Monitoring", desc: "Our AI-powered systems monitor transactions 24/7 to detect and prevent fraudulent activity before it affects you.", color: "from-gold-500 to-gold-600" },
              ].map((item) => (
                <Card key={item.title} className="group hover:shadow-card-hover transition-all duration-300 border-0 bg-gray-50 dark:bg-navy-800">
                  <CardContent className="p-8">
                    <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br text-white shadow-lg mb-6" style={{ background: `linear-gradient(135deg, ${item.color.includes('gold') ? '#d4a843' : item.color.includes('navy') ? '#1a2a4a' : '#3b82f6'}, ${item.color.includes('gold') ? '#fce488' : item.color.includes('navy') ? '#0f1d3a' : '#2563eb'})` }}>
                      <item.icon className="h-7 w-7" />
                    </div>
                    <h3 className="text-xl font-bold font-display text-navy-900 dark:text-white mb-3">{item.title}</h3>
                    <p className="text-navy-400 dark:text-navy-300 leading-relaxed">{item.desc}</p>
                  </CardContent>
                </Card>
              ))}
            </div>
            <FadeInView className="text-center mt-10">
              <Button variant="outline" size="lg" className="font-medium" asChild>
                <Link href="/security">
                  Learn More About Security
                  <ChevronRight className="ml-1 h-4 w-4" />
                </Link>
              </Button>
            </FadeInView>
          </div>
        </section>

        <section className="py-20 lg:py-28 bg-gray-50 dark:bg-navy-950">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Our Products</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Solutions for Every{" "}
                <span className="text-gold-500">Financial Need</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                From everyday banking to global wealth management, we offer comprehensive financial products.
              </p>
            </FadeInView>
            <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
              {products.map((product) => (
                <Link key={product.title} href={product.href} className="group">
                  <Card className="h-full hover:shadow-card-hover transition-all duration-300 border-0 bg-white dark:bg-navy-800 hover:-translate-y-1">
                    <CardContent className="p-8">
                      <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-gold-500/10 mb-6 group-hover:bg-gold-500/20 transition-colors">
                        <product.icon className="h-6 w-6 text-gold-500" />
                      </div>
                      <h3 className="text-lg font-bold font-display text-navy-900 dark:text-white mb-3">{product.title}</h3>
                      <p className="text-sm text-navy-400 dark:text-navy-300 leading-relaxed mb-6">{product.desc}</p>
                      <span className="inline-flex items-center text-sm font-semibold text-gold-500 group-hover:text-gold-400 transition-colors">
                        Learn more <ArrowRight className="ml-1 h-4 w-4" />
                      </span>
                    </CardContent>
                  </Card>
                </Link>
              ))}
            </div>
          </div>
        </section>

        <section className="py-20 lg:py-28 gradient-brand text-white relative overflow-hidden">
          <div className="absolute inset-0 opacity-5">
            <div className="absolute top-10 left-10 w-64 h-64 rounded-full bg-gold-500 blur-3xl" />
            <div className="absolute bottom-10 right-10 w-96 h-96 rounded-full bg-white blur-3xl" />
          </div>
          <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView>
              <div className="grid grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">
                {stats.map((stat) => (
                  <div key={stat.label} className="text-center">
                    <p className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-gold-400">{stat.value}</p>
                    <p className="mt-2 text-sm sm:text-base text-navy-200 font-medium">{stat.label}</p>
                  </div>
                ))}
              </div>
            </FadeInView>
          </div>
        </section>

        <section className="py-20 lg:py-28 bg-white dark:bg-navy-900">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Account Types</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Choose the Right{" "}
                <span className="text-gold-500">Account for You</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                Every account comes with premium features designed to help you manage and grow your money.
              </p>
            </FadeInView>
            <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
              {accountTypes.map((account) => (
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
                    <Button variant="outline" size="sm" className="w-full font-medium group-hover:bg-usib-500 group-hover:text-white transition-colors" asChild>
                      <Link href={account.href}>Open Account</Link>
                    </Button>
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>
        </section>

        <section className="py-20 lg:py-28 bg-gray-50 dark:bg-navy-950">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Competitive Rates</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Financing Solutions{" "}
                <span className="text-gold-500">at Great Rates</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                Whether for personal goals or business expansion, we offer competitive loan products.
              </p>
            </FadeInView>
            <div className="grid md:grid-cols-3 gap-8">
              {loansTeaser.map((loan) => (
                <Card key={loan.title} className="relative overflow-hidden hover:shadow-card-hover transition-all duration-300 border-0 bg-white dark:bg-navy-800">
                  <div className="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-gold-400 to-gold-500" />
                  <CardContent className="p-8">
                    <Badge variant="outline" className="border-gold-500/30 text-gold-500 bg-gold-500/5 mb-4">{loan.rate}</Badge>
                    <h3 className="text-xl font-bold font-display text-navy-900 dark:text-white mb-2">{loan.title}</h3>
                    <p className="text-sm text-navy-400 dark:text-navy-300 mb-4">{loan.desc}</p>
                    <div className="flex items-center gap-2 text-xs text-navy-500 dark:text-navy-300 mb-6">
                      <Clock className="h-3.5 w-3.5" />
                      {loan.term}
                    </div>
                    <Button variant="accent" size="sm" className="w-full font-semibold" asChild>
                      <Link href={loan.href}>
                        Apply Now <ArrowRight className="ml-1 h-4 w-4" />
                      </Link>
                    </Button>
                  </CardContent>
                </Card>
              ))}
            </div>
            <FadeInView className="text-center mt-10">
              <p className="text-sm text-navy-400 dark:text-navy-300 mb-4">
                Rates shown are for qualified borrowers. Your actual rate may vary based on creditworthiness.
              </p>
              <Button variant="outline" size="lg" className="font-medium" asChild>
                <Link href="/loans">View All Loan Products <ChevronRight className="ml-1 h-4 w-4" /></Link>
              </Button>
            </FadeInView>
          </div>
        </section>

        <section className="py-20 lg:py-28 bg-white dark:bg-navy-900 overflow-hidden">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="grid lg:grid-cols-2 gap-12 items-center">
              <div>
                <Badge variant="secondary" className="mb-4">Global Transfers</Badge>
                <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight mb-6">
                  Send Money Across the{" "}
                  <span className="text-gold-500">World Instantly</span>
                </h2>
                <p className="text-lg text-navy-400 dark:text-navy-300 leading-relaxed mb-8">
                  Move money internationally with competitive exchange rates, low fees, and 
                  real-time tracking. Support for SWIFT, SEPA, and wire transfers in 30+ currencies.
                </p>
                <div className="grid sm:grid-cols-3 gap-4 mb-8">
                  {["SWIFT Transfers", "SEPA Payments", "30+ Currencies"].map((feature) => (
                    <div key={feature} className="flex items-center gap-2 text-sm font-medium text-navy-700 dark:text-navy-200">
                      <CheckCircle className="h-4 w-4 text-gold-500" />
                      {feature}
                    </div>
                  ))}
                </div>
                <Button variant="accent" size="lg" className="font-semibold" asChild>
                  <Link href="/international">
                    Start a Transfer <Send className="ml-2 h-4 w-4" />
                  </Link>
                </Button>
              </div>
              <div className="relative">
                <div className="absolute inset-0 bg-gradient-to-br from-gold-500/10 to-navy-500/10 rounded-3xl" />
                <div className="relative bg-white dark:bg-navy-800 rounded-2xl shadow-elevated p-8 border border-gray-100 dark:border-navy-700">
                  <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-3">
                      <div className="flex h-10 w-10 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                        <Send className="h-5 w-5 text-green-600" />
                      </div>
                      <div>
                        <p className="text-sm font-semibold text-navy-900 dark:text-white">International Transfer</p>
                        <p className="text-xs text-navy-400">Completed - Dec 12, 2025</p>
                      </div>
                    </div>
                    <Badge variant="success" className="text-[10px]">Completed</Badge>
                  </div>
                  <div className="space-y-4">
                    <div className="flex justify-between items-center">
                      <span className="text-sm text-navy-400">You sent</span>
                      <span className="text-lg font-bold text-navy-900 dark:text-white">$50,000.00</span>
                    </div>
                    <div className="border-t border-dashed border-gray-200 dark:border-navy-700 pt-4">
                      <div className="flex justify-between items-center">
                        <span className="text-sm text-navy-400">Recipient received</span>
                        <span className="text-lg font-bold text-gold-500">€46,250.00</span>
                      </div>
                    </div>
                    <div className="flex justify-between text-xs text-navy-400 pt-2">
                      <span>Exchange rate: 1 USD = 0.925 EUR</span>
                      <span>Fee: $0.00</span>
                    </div>
                  </div>
                  <div className="mt-6 p-4 bg-gray-50 dark:bg-navy-900 rounded-xl flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <Globe className="h-4 w-4 text-gold-500" />
                      <span className="text-xs font-medium text-navy-600 dark:text-navy-200">From: USIB New York</span>
                    </div>
                    <ArrowRight className="h-4 w-4 text-navy-300" />
                    <div className="flex items-center gap-2">
                      <Globe className="h-4 w-4 text-gold-500" />
                      <span className="text-xs font-medium text-navy-600 dark:text-navy-200">To: Deutsche Bank (Frankfurt)</span>
                    </div>
                  </div>
                </div>
              </div>
            </FadeInView>
          </div>
        </section>

        <section className="py-20 lg:py-28 bg-gray-50 dark:bg-navy-950">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Testimonials</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Trusted by Thousands{" "}
                <span className="text-gold-500">Worldwide</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                Hear from our customers about their experience banking with us.
              </p>
            </FadeInView>
            <div className="grid md:grid-cols-3 gap-8">
              {testimonials.map((t) => (
                <Card key={t.name} className="bg-white dark:bg-navy-800 border border-gray-100 dark:border-navy-700 hover:shadow-card-hover transition-shadow">
                  <CardContent className="p-8">
                    <Quote className="h-8 w-8 text-gold-500/30 mb-4" />
                    <p className="text-navy-600 dark:text-navy-200 leading-relaxed mb-6">&ldquo;{t.quote}&rdquo;</p>
                    <div className="flex items-center gap-1 mb-3">
                      {Array.from({ length: t.rating }).map((_, i) => (
                        <Star key={i} className="h-4 w-4 fill-gold-500 text-gold-500" />
                      ))}
                    </div>
                    <div>
                      <p className="font-semibold text-navy-900 dark:text-white text-sm">{t.name}</p>
                      <p className="text-xs text-navy-400">{t.title}</p>
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>
        </section>

        <section className="py-20 lg:py-28 bg-white dark:bg-navy-900">
          <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center mb-16">
              <Badge variant="secondary" className="mb-4">FAQ</Badge>
              <h2 className="text-3xl sm:text-4xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Frequently Asked Questions
              </h2>
            </FadeInView>
            <div className="space-y-3">
              {faqItems.map((item) => (
                <details key={item.q} className="group bg-gray-50 dark:bg-navy-800 rounded-2xl overflow-hidden">
                  <summary className="flex items-center justify-between p-6 cursor-pointer list-none text-sm font-semibold text-navy-900 dark:text-white hover:bg-gray-100 dark:hover:bg-navy-700 transition-colors">
                    {item.q}
                    <ChevronRight className="h-5 w-5 text-gold-500 flex-shrink-0 group-open:rotate-90 transition-transform" />
                  </summary>
                  <div className="px-6 pb-6">
                    <p className="text-sm text-navy-400 dark:text-navy-300 leading-relaxed">{item.a}</p>
                  </div>
                </details>
              ))}
            </div>
            <FadeInView className="text-center mt-10">
              <Button variant="outline" size="lg" className="font-medium" asChild>
                <Link href="/faq">View All FAQs <ChevronRight className="ml-1 h-4 w-4" /></Link>
              </Button>
            </FadeInView>
          </div>
        </section>

        <section className="py-20 lg:py-28 gradient-brand text-white relative overflow-hidden">
          <div className="absolute inset-0 opacity-10">
            <div className="absolute -top-20 -right-20 w-80 h-80 rounded-full bg-gold-500 blur-3xl" />
            <div className="absolute -bottom-20 -left-20 w-80 h-80 rounded-full bg-white blur-3xl" />
          </div>
          <div className="relative mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
            <FadeInView>
              <Badge variant="outline" className="border-gold-400/50 text-gold-400 bg-gold-500/10 mb-6">
                Get Started Today
              </Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display leading-tight mb-6">
                Ready to Experience{" "}
                <span className="text-gold-400">Premium Banking</span>?
              </h2>
              <p className="text-lg text-navy-100 max-w-2xl mx-auto mb-10 leading-relaxed">
                Join millions of satisfied customers worldwide. Open your account in minutes 
                and start banking with confidence.
              </p>
              <div className="flex flex-col sm:flex-row gap-4 justify-center">
                <Button variant="accent" size="xl" className="font-semibold text-base" asChild>
                  <Link href="/personal-banking">
                    Open Your Account Today
                    <ArrowRight className="ml-2 h-5 w-5" />
                  </Link>
                </Button>
                <Button
                  variant="outline"
                  size="xl"
                  className="text-white border-white/30 hover:bg-white/10 text-base"
                  asChild
                >
                  <Link href="/contact">Talk to an Advisor</Link>
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
