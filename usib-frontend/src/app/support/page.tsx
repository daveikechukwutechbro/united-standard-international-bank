import type { Metadata } from "next"
import Link from "next/link"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import Header from "@/components/Header"
import Footer from "@/components/Footer"
import FadeInView from "@/components/FadeInView"
import {
  ArrowRight, Phone, Mail, MessageCircle, HelpCircle,
  FileText, CreditCard, Shield, Smartphone, ChevronRight,
  Clock, Users, Building2, Globe
} from "lucide-react"

export const metadata: Metadata = {
  title: "Support | United Standard International Bank",
  description: "Get help with your accounts, cards, loans, and more. Contact USIB support via phone, email, or live chat. 24/7 customer service.",
}

const categories = [
  {
    icon: CreditCard,
    title: "Cards",
    desc: "Lost or stolen cards, card limits, PIN changes, and card activation.",
    href: "/faq",
    color: "from-blue-500 to-blue-600",
  },
  {
    icon: FileText,
    title: "Accounts",
    desc: "Account opening, statements, direct deposits, and account closures.",
    href: "/faq",
    color: "from-navy-500 to-navy-600",
  },
  {
    icon: Shield,
    title: "Security",
    desc: "Fraud alerts, suspicious activity, password reset, and phishing reports.",
    href: "/security",
    color: "from-gold-500 to-gold-600",
  },
  {
    icon: Smartphone,
    title: "Mobile & Online Banking",
    desc: "App downloads, login issues, mobile deposit, and bill pay questions.",
    href: "/faq",
    color: "from-green-500 to-green-600",
  },
  {
    icon: Globe,
    title: "International",
    desc: "Wire transfers, currency exchange, SWIFT codes, and foreign travel.",
    href: "/international",
    color: "from-purple-500 to-purple-600",
  },
  {
    icon: Building2,
    title: "Business Banking",
    desc: "Business accounts, merchant services, payroll, and commercial lending.",
    href: "/business-banking",
    color: "from-red-500 to-red-600",
  },
  {
    icon: Users,
    title: "Wealth Management",
    desc: "Investment accounts, portfolio questions, and financial planning.",
    href: "/personal-banking",
    color: "from-teal-500 to-teal-600",
  },
  {
    icon: FileText,
    title: "Loans & Mortgages",
    desc: "Loan applications, payment questions, refinancing, and rate inquiries.",
    href: "/loans",
    color: "from-indigo-500 to-indigo-600",
  },
]

const contactOptions = [
  {
    icon: Phone,
    title: "Phone Support",
    desc: "Available 24/7 for urgent inquiries",
    action: "+1 (800) 555-0199",
    href: "tel:+18005550199",
    label: "Call Now",
  },
  {
    icon: Mail,
    title: "Email Support",
    desc: "We respond within 24 hours",
    action: "support@usib.com",
    href: "mailto:support@usib.com",
    label: "Send Email",
  },
  {
    icon: MessageCircle,
    title: "Live Chat",
    desc: "Chat with a representative in real-time",
    action: "Available 24/7",
    href: "#",
    label: "Start Chat",
  },
]

export default function SupportPage() {
  return (
    <>
      <Header />
      <main>
        <section className="relative pt-32 pb-20 lg:pt-40 lg:pb-28 overflow-hidden">
          <div className="absolute inset-0 gradient-hero" />
          <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="max-w-3xl">
              <Badge variant="outline" className="border-gold-400/50 text-gold-400 bg-gold-500/10 mb-4">Support</Badge>
              <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold font-display text-white leading-tight">
                We&apos;re Here to{" "}
                <span className="text-gradient">Help</span>
              </h1>
              <p className="mt-6 text-lg text-navy-100 max-w-2xl leading-relaxed">
                Get the support you need, when you need it. Browse our help categories or reach out to our
                dedicated support team available 24/7.
              </p>
              <div className="mt-10 flex flex-col sm:flex-row gap-4">
                <Button variant="accent" size="xl" className="font-semibold text-base" asChild>
                  <Link href="#contact">Contact Support <ArrowRight className="ml-2 h-5 w-5" /></Link>
                </Button>
                <Button variant="outline" size="xl" className="text-white border-white/30 hover:bg-white/10 text-base" asChild>
                  <Link href="/faq">Browse FAQ</Link>
                </Button>
              </div>
            </FadeInView>
          </div>
        </section>

        <section className="py-20 lg:py-28 bg-white dark:bg-navy-900">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Help Categories</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                How Can We{" "}
                <span className="text-gold-500">Help You?</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                Choose a category below to find answers to common questions and issues.
              </p>
            </FadeInView>
            <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
              {categories.map((category) => (
                <Link key={category.title} href={category.href}>
                  <Card className="group hover:shadow-card-hover transition-all duration-300 border border-gray-100 dark:border-navy-700 bg-white dark:bg-navy-800 h-full">
                    <CardContent className="p-6">
                      <div className="flex h-12 w-12 items-center justify-center rounded-xl mb-4 text-white" style={{ background: `linear-gradient(135deg, ${category.color.split(' ')[0].replace('from-', '')}, ${category.color.split(' ')[1].replace('to-', '')})` }}>
                        <category.icon className="h-6 w-6" />
                      </div>
                      <h3 className="text-lg font-bold font-display text-navy-900 dark:text-white mb-2">{category.title}</h3>
                      <p className="text-sm text-navy-400 dark:text-navy-300">{category.desc}</p>
                    </CardContent>
                  </Card>
                </Link>
              ))}
            </div>
          </div>
        </section>

        <section id="contact" className="py-20 lg:py-28 bg-gray-50 dark:bg-navy-950">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Contact Options</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Reach Out{" "}
                <span className="text-gold-500">Anytime</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                Our support team is available 24/7 to assist you with any questions or concerns.
              </p>
            </FadeInView>
            <div className="grid md:grid-cols-3 gap-8">
              {contactOptions.map((option) => (
                <Card key={option.title} className="text-center bg-white dark:bg-navy-800 border border-gray-100 dark:border-navy-700 hover:shadow-card-hover transition-all duration-300">
                  <CardContent className="p-8">
                    <div className="flex justify-center mb-4">
                      <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-gold-500/10">
                        <option.icon className="h-7 w-7 text-gold-500" />
                      </div>
                    </div>
                    <h3 className="text-lg font-bold font-display text-navy-900 dark:text-white mb-2">{option.title}</h3>
                    <p className="text-sm text-navy-400 dark:text-navy-300 mb-4">{option.desc}</p>
                    <p className="text-sm font-semibold text-navy-700 dark:text-navy-200 mb-4">{option.action}</p>
                    <Button variant="accent" className="w-full font-semibold" asChild>
                      <Link href={option.href}>{option.label} <ArrowRight className="ml-1 h-4 w-4" /></Link>
                    </Button>
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
                <Badge variant="secondary" className="mb-4">Business Hours</Badge>
                <h2 className="text-3xl sm:text-4xl font-bold font-display text-navy-900 dark:text-white leading-tight mb-6">
                  When Can We{" "}
                  <span className="text-gold-500">Help You?</span>
                </h2>
                <p className="text-lg text-navy-400 dark:text-navy-300 mb-8">
                  Our support team is available around the clock for urgent matters. 
                  For non-urgent inquiries, our standard business hours are below.
                </p>
                <div className="space-y-4">
                  <div className="flex justify-between py-3 border-b border-gray-100 dark:border-navy-700">
                    <span className="text-navy-600 dark:text-navy-200">Monday - Friday</span>
                    <span className="font-semibold text-navy-900 dark:text-white">24 hours</span>
                  </div>
                  <div className="flex justify-between py-3 border-b border-gray-100 dark:border-navy-700">
                    <span className="text-navy-600 dark:text-navy-200">Saturday</span>
                    <span className="font-semibold text-navy-900 dark:text-white">8:00 AM - 8:00 PM EST</span>
                  </div>
                  <div className="flex justify-between py-3 border-b border-gray-100 dark:border-navy-700">
                    <span className="text-navy-600 dark:text-navy-200">Sunday</span>
                    <span className="font-semibold text-navy-900 dark:text-white">9:00 AM - 6:00 PM EST</span>
                  </div>
                  <div className="flex justify-between py-3">
                    <span className="text-navy-600 dark:text-navy-200">Holidays</span>
                    <span className="font-semibold text-navy-900 dark:text-white">Limited hours</span>
                  </div>
                </div>
              </div>
              <div className="bg-gradient-to-br from-navy-800 to-navy-900 rounded-3xl p-10 lg:p-12 text-white">
                <h3 className="text-2xl font-bold font-display mb-4">Prefer to Visit?</h3>
                <p className="text-navy-200 mb-6">
                  Visit any of our branches worldwide for in-person assistance. 
                  Schedule an appointment for priority service.
                </p>
                <div className="space-y-4 mb-8">
                  <div className="flex items-start gap-3">
                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-gold-500/20 flex-shrink-0">
                      <Building2 className="h-4 w-4 text-gold-400" />
                    </div>
                    <div>
                      <p className="font-semibold text-sm">New York (Headquarters)</p>
                      <p className="text-xs text-navy-300">1 Financial Square, New York, NY 10005</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-3">
                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-gold-500/20 flex-shrink-0">
                      <Building2 className="h-4 w-4 text-gold-400" />
                    </div>
                    <div>
                      <p className="font-semibold text-sm">London Branch</p>
                      <p className="text-xs text-navy-300">25 Cannon Street, London EC4M 5TA</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-3">
                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-gold-500/20 flex-shrink-0">
                      <Building2 className="h-4 w-4 text-gold-400" />
                    </div>
                    <div>
                      <p className="font-semibold text-sm">Singapore Branch</p>
                      <p className="text-xs text-navy-300">9 Raffles Place, Singapore 048619</p>
                    </div>
                  </div>
                </div>
                <Button variant="accent" size="lg" className="w-full font-semibold" asChild>
                  <Link href="/contact">Find a Branch <ArrowRight className="ml-2 h-4 w-4" /></Link>
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
