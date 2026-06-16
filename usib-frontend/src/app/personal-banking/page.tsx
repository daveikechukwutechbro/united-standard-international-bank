import type { Metadata } from "next"
import Link from "next/link"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import Header from "@/components/Header"
import Footer from "@/components/Footer"
import FadeInView from "@/components/FadeInView"
import {
  ArrowRight, CheckCircle, Building2, PiggyBank, Clock,
  Globe, Smartphone, Shield, CreditCard, TrendingUp,
  ChevronRight, Users, Award
} from "lucide-react"

export const metadata: Metadata = {
  title: "Personal Banking | United Standard International Bank",
  description: "Personal banking solutions including checking, savings, investments, and more. Experience premium banking designed for your financial goals.",
}

const accountTypes = [
  {
    title: "Premium Checking",
    desc: "Everyday banking with unlimited transactions, free ATM access worldwide, and premium perks.",
    features: ["No monthly maintenance fees", "Unlimited transactions", "Free global ATM access", "Overdraft protection", "Mobile check deposit", "Contactless debit card"],
    rate: "0.01% APY",
    mins: "$0 minimum",
  },
  {
    title: "High-Yield Savings",
    desc: "Earn competitive interest rates while keeping your funds accessible for life&apos;s moments.",
    features: ["4.25% APY interest", "No minimum balance", "Automatic savings plans", "Linked checking account", "FDIC insured up to $250K", "Free transfers"],
    rate: "4.25% APY",
    mins: "$100 minimum",
  },
  {
    title: "Fixed Deposit",
    desc: "Lock in guaranteed returns with flexible terms from 3 months to 5 years.",
    features: ["Guaranteed fixed returns", "Terms from 3-60 months", "Competitive interest rates", "Auto-renewal options", "Early withdrawal options", "Flexible deposit amounts"],
    rate: "5.10% APY",
    mins: "$1,000 minimum",
  },
  {
    title: "Wealth Management",
    desc: "Personalized investment strategies and financial planning for high-net-worth individuals.",
    features: ["Dedicated relationship manager", "Custom portfolio management", "Estate planning services", "Tax optimization strategies", "Retirement planning", "Alternative investments"],
    rate: "Market-based returns",
    mins: "$250,000 minimum",
  },
]

const features = [
  { icon: Smartphone, title: "Mobile Banking App", desc: "Manage your accounts, deposit checks, and transfer money from anywhere with our award-winning app." },
  { icon: Shield, title: "Advanced Security", desc: "Multi-factor authentication, biometric login, and real-time fraud monitoring keep your money safe." },
  { icon: CreditCard, title: "Premium Cards", desc: "Choose from our range of debit and credit cards with travel rewards, cashback, and exclusive benefits." },
  { icon: Globe, title: "Global Banking", desc: "Access your accounts from anywhere in the world with international wire transfers and multi-currency support." },
  { icon: TrendingUp, title: "Investment Options", desc: "Grow your wealth with mutual funds, stocks, bonds, and expert portfolio management services." },
  { icon: Users, title: "Dedicated Support", desc: "24/7 customer support with personal relationship managers for premium account holders." },
]

export default function PersonalBankingPage() {
  return (
    <>
      <Header />
      <main>
        <section className="relative pt-32 pb-20 lg:pt-40 lg:pb-28 overflow-hidden">
          <div className="absolute inset-0 gradient-hero" />
          <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1560472354-b33ff0c44a43?q=80&w=2070&auto=format&fit=crop')] bg-cover bg-center opacity-10" />
          <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="max-w-3xl">
              <Badge variant="outline" className="border-gold-400/50 text-gold-400 bg-gold-500/10 mb-4">Personal Banking</Badge>
              <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold font-display text-white leading-tight">
                Banking Designed for{" "}
                <span className="text-gradient">Your Life</span>
              </h1>
              <p className="mt-6 text-lg text-navy-100 max-w-2xl leading-relaxed">
                From everyday checking to sophisticated wealth management, our personal banking solutions 
                are built to help you achieve your financial goals with confidence and ease.
              </p>
              <div className="mt-10 flex flex-col sm:flex-row gap-4">
                <Button variant="accent" size="xl" className="font-semibold text-base" asChild>
                  <Link href="#accounts">Open an Account <ArrowRight className="ml-2 h-5 w-5" /></Link>
                </Button>
                <Button variant="outline" size="xl" className="text-white border-white/30 hover:bg-white/10 text-base" asChild>
                  <Link href="#features">Explore Features</Link>
                </Button>
              </div>
            </FadeInView>
          </div>
        </section>

        <section id="accounts" className="py-20 lg:py-28 bg-white dark:bg-navy-900">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Account Types</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Choose Your{" "}
                <span className="text-gold-500">Perfect Account</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                Every account comes with premium features, competitive rates, and the security you expect from a world-class bank.
              </p>
            </FadeInView>
            <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
              {accountTypes.map((account) => (
                <Card key={account.title} className="group hover:shadow-card-hover transition-all duration-300 border border-gray-100 dark:border-navy-700 bg-white dark:bg-navy-800">
                  <CardContent className="p-6">
                    <Badge variant="success" className="mb-3">{account.rate}</Badge>
                    <p className="text-xs text-navy-400 mb-3">{account.mins}</p>
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

        <section id="features" className="py-20 lg:py-28 bg-gray-50 dark:bg-navy-950">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Premium Features</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Everything You Need to{" "}
                <span className="text-gold-500">Manage Your Money</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                Modern banking tools combined with personalized service to help you succeed.
              </p>
            </FadeInView>
            <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
              {features.map((feature) => (
                <Card key={feature.title} className="bg-white dark:bg-navy-800 border border-gray-100 dark:border-navy-700 hover:shadow-card-hover transition-all duration-300">
                  <CardContent className="p-8">
                    <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-gold-500/10 mb-5">
                      <feature.icon className="h-6 w-6 text-gold-500" />
                    </div>
                    <h3 className="text-lg font-bold font-display text-navy-900 dark:text-white mb-3">{feature.title}</h3>
                    <p className="text-sm text-navy-400 dark:text-navy-300 leading-relaxed">{feature.desc}</p>
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>
        </section>

        <section className="py-20 lg:py-28 bg-white dark:bg-navy-900">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="grid lg:grid-cols-2 gap-12 items-center">
              <div>
                <Badge variant="secondary" className="mb-4">Why Choose USIB</Badge>
                <h2 className="text-3xl sm:text-4xl font-bold font-display text-navy-900 dark:text-white leading-tight mb-6">
                  The USIB{" "}
                  <span className="text-gold-500">Advantage</span>
                </h2>
                <div className="space-y-6">
                  {[
                    { icon: Award, title: "Award-Winning Service", desc: "Recognized as Best Consumer Bank 2024 by Global Finance for our exceptional customer experience." },
                    { icon: Shield, title: "Uncompromising Security", desc: "Your accounts are protected with military-grade encryption and 24/7 fraud monitoring." },
                    { icon: Globe, title: "Global Presence", desc: "Access your money from anywhere with branches in 50+ countries and online banking." },
                    { icon: Users, title: "Personal Relationships", desc: "Every client gets a dedicated relationship manager who understands your financial goals." },
                  ].map((item) => (
                    <div key={item.title} className="flex gap-4">
                      <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-gold-500/10 flex-shrink-0">
                        <item.icon className="h-5 w-5 text-gold-500" />
                      </div>
                      <div>
                        <h3 className="font-semibold text-navy-900 dark:text-white">{item.title}</h3>
                        <p className="text-sm text-navy-400 dark:text-navy-300">{item.desc}</p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
              <div className="relative">
                <div className="absolute inset-0 bg-gradient-to-br from-gold-500/20 to-navy-500/20 rounded-3xl" />
                <div className="relative bg-white dark:bg-navy-800 rounded-2xl shadow-elevated p-8 lg:p-10 border border-gray-100 dark:border-navy-700">
                  <h3 className="text-2xl font-bold font-display text-navy-900 dark:text-white mb-6">Open an Account in Minutes</h3>
                  <ol className="space-y-4">
                    {[
                      "Choose your account type and complete the online application",
                      "Verify your identity with a valid government ID",
                      "Fund your account with an initial deposit",
                      "Start banking with your new USIB account",
                    ].map((step, i) => (
                      <li key={step} className="flex gap-4">
                        <span className="flex h-8 w-8 items-center justify-center rounded-full bg-gold-500 text-navy-900 text-sm font-bold flex-shrink-0">{i + 1}</span>
                        <span className="text-sm text-navy-600 dark:text-navy-200 pt-1">{step}</span>
                      </li>
                    ))}
                  </ol>
                  <Button variant="accent" size="lg" className="w-full mt-8 font-semibold" asChild>
                    <Link href="/personal-banking">Get Started Now <ArrowRight className="ml-2 h-4 w-4" /></Link>
                  </Button>
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
              <h2 className="text-3xl sm:text-4xl font-bold font-display leading-tight mb-6">
                Ready to Start Your Banking Journey?
              </h2>
              <p className="text-lg text-navy-100 mb-8 max-w-2xl mx-auto">
                Join millions of satisfied customers who trust USIB for their personal banking needs.
              </p>
              <div className="flex flex-col sm:flex-row gap-4 justify-center">
                <Button variant="accent" size="xl" className="font-semibold text-base" asChild>
                  <Link href="/personal-banking">Open an Account <ArrowRight className="ml-2 h-5 w-5" /></Link>
                </Button>
                <Button variant="outline" size="xl" className="text-white border-white/30 hover:bg-white/10 text-base" asChild>
                  <Link href="/contact">Talk to Us</Link>
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
