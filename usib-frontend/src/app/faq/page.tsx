import type { Metadata } from "next"
import Link from "next/link"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import Header from "@/components/Header"
import Footer from "@/components/Footer"
import FadeInView from "@/components/FadeInView"
import {
  ArrowRight, ChevronRight, Search,
  CreditCard, Landmark, Globe, Shield, FileText, HelpCircle
} from "lucide-react"

export const metadata: Metadata = {
  title: "FAQ | United Standard International Bank",
  description: "Frequently asked questions about USIB's accounts, cards, loans, international banking, security, and more.",
}

const categories = [
  {
    id: "accounts",
    icon: Landmark,
    title: "Accounts",
    questions: [
      {
        q: "How do I open an account with USIB?",
        a: "Opening an account is simple and entirely online. Visit our Personal Banking page, choose your account type, and complete the application. You'll need a valid government-issued ID, proof of address (utility bill or bank statement), and your Social Security Number or Tax ID. Most accounts are approved within 24 hours.",
      },
      {
        q: "What is the minimum deposit to open an account?",
        a: "Standard checking accounts have no minimum deposit requirement. Savings accounts require a $100 minimum opening deposit. Fixed deposits start at $1,000. Business accounts have varying requirements based on the account type.",
      },
      {
        q: "Are my deposits insured?",
        a: "Yes, all deposits at USIB are FDIC insured up to $250,000 per depositor, per account ownership category. This means your money is protected by the full faith and credit of the United States government.",
      },
      {
        q: "How do I close my account?",
        a: "You can close your account by visiting any branch, calling customer support, or sending a secure message through online banking. Please ensure all pending transactions have cleared and there are no outstanding fees before closing.",
      },
      {
        q: "Can I have multiple accounts with USIB?",
        a: "Yes, you can open multiple accounts, including checking, savings, and fixed deposit accounts. Many clients have multiple accounts for different financial goals.",
      },
    ],
  },
  {
    id: "cards",
    icon: CreditCard,
    title: "Cards",
    questions: [
      {
        q: "How do I report a lost or stolen card?",
        a: "If your card is lost or stolen, contact us immediately at +1 (800) 555-0199. Our 24/7 support team will block your card instantly and issue a replacement. You can also temporarily freeze your card through the mobile app.",
      },
      {
        q: "What are the benefits of USIB credit cards?",
        a: "Our credit cards offer rewards points on every purchase, travel insurance, purchase protection, extended warranty, airport lounge access (World Elite card), and concierge service. Specific benefits vary by card tier.",
      },
      {
        q: "How do I activate my new card?",
        a: "You can activate your card through our mobile app, by calling the activation number on the sticker on your card, or at any USIB ATM. You'll need to verify your identity and set or confirm your PIN.",
      },
      {
        q: "What is a virtual card and how does it work?",
        a: "A virtual card is a digital card that exists only in your mobile wallet. You can create single-use or merchant-specific virtual cards for secure online shopping. Each card has a unique number and can be set with spending limits.",
      },
    ],
  },
  {
    id: "loans",
    icon: FileText,
    title: "Loans & Mortgages",
    questions: [
      {
        q: "What documents do I need to apply for a loan?",
        a: "For personal loans, you'll need proof of income (pay stubs or tax returns), government ID, and proof of residence. For mortgages, additional documents include bank statements, employment verification, and property details.",
      },
      {
        q: "How long does loan approval take?",
        a: "Personal and auto loans are typically approved within 24 hours. Mortgage applications take 3-7 business days for initial approval and 30-45 days to close. Business loan timelines vary based on complexity.",
      },
      {
        q: "Can I pay off my loan early?",
        a: "Yes, most of our loans have no prepayment penalties. You can make extra payments or pay off your loan in full at any time without additional fees. Check your loan agreement for specific terms.",
      },
      {
        q: "What credit score do I need for a loan?",
        a: "Minimum credit score requirements vary by product: Personal loans from 660, Auto loans from 650, Conventional mortgages from 620, FHA mortgages from 580. Higher credit scores qualify for better rates.",
      },
    ],
  },
  {
    id: "international",
    icon: Globe,
    title: "International Banking",
    questions: [
      {
        q: "How do I send an international wire transfer?",
        a: "Log into online banking, navigate to Transfers, select International Wire, and enter the recipient's details including their bank name, account number, SWIFT/BIC code, and IBAN if applicable. You can track the transfer in real-time.",
      },
      {
        q: "What currencies does USIB support?",
        a: "USIB supports over 30 currencies including USD, EUR, GBP, JPY, CHF, CAD, AUD, CNY, SGD, HKD, and more. You can hold multiple currencies in your account and exchange between them at competitive rates.",
      },
      {
        q: "How long do international transfers take?",
        a: "SWIFT transfers typically arrive within 1-3 business days. SEPA transfers within the Eurozone are completed within 1 business day. USIB-to-USIB transfers are instant.",
      },
      {
        q: "What information do I need to receive an international transfer?",
        a: "Provide the sender with your full name as it appears on your account, your account number, USIB's SWIFT/BIC code (USIBUS33), and our routing number (021000021). For EUR transfers, also provide your IBAN.",
      },
    ],
  },
  {
    id: "security",
    icon: Shield,
    title: "Security",
    questions: [
      {
        q: "How does USIB protect my account?",
        a: "We use 256-bit SSL encryption, multi-factor authentication, biometric login options, real-time AI-powered fraud monitoring, and automatic session timeouts. We also offer account alerts and the ability to freeze cards instantly.",
      },
      {
        q: "What should I do if I see suspicious activity on my account?",
        a: "Contact us immediately at +1 (800) 555-0199. Our security team will investigate and take appropriate action. You can also temporarily freeze your account through the mobile app.",
      },
      {
        q: "How do I create a strong password?",
        a: "Use a minimum of 12 characters with a mix of uppercase and lowercase letters, numbers, and special characters. Avoid using personal information or common words. Never reuse passwords across different services.",
      },
      {
        q: "What is phishing and how can I avoid it?",
        a: "Phishing is when scammers send fake emails or messages that appear to be from USIB to steal your login credentials. USIB will never ask for your password, PIN, or full social security number via email or text. Always verify the sender and never click suspicious links.",
      },
    ],
  },
  {
    id: "general",
    icon: HelpCircle,
    title: "General",
    questions: [
      {
        q: "How do I access online banking?",
        a: "Visit our website and click 'Sign In' in the top right corner. You'll need your username and password. If you're a new user, click 'Enroll' to set up your online banking access. Download our mobile app from the App Store or Google Play.",
      },
      {
        q: "What are your customer service hours?",
        a: "Our phone support is available 24/7 at +1 (800) 555-0199. Live chat is also available 24/7 through our website and mobile app. Branch hours vary by location.",
      },
      {
        q: "Does USIB offer mobile check deposit?",
        a: "Yes, you can deposit checks using our mobile app. Simply endorse the check, take a photo of the front and back, and submit. Funds are typically available within 1 business day. Deposit limits apply.",
      },
      {
        q: "How do I update my personal information?",
        a: "You can update your contact information (address, phone, email) through online banking or the mobile app. Changes to your name or other sensitive information require visiting a branch with supporting documentation.",
      },
      {
        q: "How do I set up direct deposit?",
        a: "Provide your employer or payer with your USIB account number and routing number (021000021). You can find a pre-filled direct deposit form in online banking under Services > Direct Deposit.",
      },
    ],
  },
]

export default function FAQPage() {
  return (
    <>
      <Header />
      <main>
        <section className="relative pt-32 pb-20 lg:pt-40 lg:pb-28 overflow-hidden">
          <div className="absolute inset-0 gradient-hero" />
          <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="max-w-3xl">
              <Badge variant="outline" className="border-gold-400/50 text-gold-400 bg-gold-500/10 mb-4">FAQ</Badge>
              <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold font-display text-white leading-tight">
                Frequently Asked{" "}
                <span className="text-gradient">Questions</span>
              </h1>
              <p className="mt-6 text-lg text-navy-100 max-w-2xl leading-relaxed">
                Find answers to common questions about our accounts, cards, loans, international banking, 
                security, and more. Can&apos;t find what you&apos;re looking for? Contact our support team.
              </p>
              <div className="mt-8">
                <div className="relative max-w-xl">
                  <Search className="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-navy-400" />
                  <input
                    type="text"
                    placeholder="Search for answers..."
                    className="w-full rounded-xl border border-white/20 bg-white/10 backdrop-blur-sm py-4 pl-12 pr-4 text-sm text-white placeholder:text-navy-300 focus:outline-none focus:ring-2 focus:ring-gold-500"
                  />
                </div>
              </div>
            </FadeInView>
          </div>
        </section>

        <section className="py-20 lg:py-28 bg-gray-50 dark:bg-navy-950">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="grid lg:grid-cols-4 gap-8">
              <div className="lg:col-span-1">
                <nav className="sticky top-28 space-y-2">
                  <p className="text-xs font-semibold uppercase tracking-widest text-navy-400 mb-4">Categories</p>
                  {categories.map((cat) => (
                    <a
                      key={cat.id}
                      href={`#${cat.id}`}
                      className="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-navy-600 dark:text-navy-300 hover:bg-white dark:hover:bg-navy-800 hover:text-navy-900 dark:hover:text-white transition-colors"
                    >
                      <cat.icon className="h-4 w-4 text-gold-500" />
                      {cat.title}
                    </a>
                  ))}
                  <div className="pt-4">
                    <Button variant="accent" size="sm" className="w-full font-semibold" asChild>
                      <Link href="/contact">Still Need Help? <ArrowRight className="ml-1 h-4 w-4" /></Link>
                    </Button>
                  </div>
                </nav>
              </div>
              <div className="lg:col-span-3 space-y-16">
                {categories.map((category) => (
                  <div key={category.id} id={category.id}>
                    <FadeInView>
                      <div className="flex items-center gap-3 mb-8">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gold-500/10">
                          <category.icon className="h-5 w-5 text-gold-500" />
                        </div>
                        <h2 className="text-2xl font-bold font-display text-navy-900 dark:text-white">{category.title}</h2>
                      </div>
                      <div className="space-y-3">
                        {category.questions.map((item) => (
                          <details key={item.q} className="group bg-white dark:bg-navy-800 rounded-2xl overflow-hidden border border-gray-100 dark:border-navy-700">
                            <summary className="flex items-center justify-between p-6 cursor-pointer list-none text-sm font-semibold text-navy-900 dark:text-white hover:bg-gray-50 dark:hover:bg-navy-700 transition-colors">
                              {item.q}
                              <ChevronRight className="h-5 w-5 text-gold-500 flex-shrink-0 group-open:rotate-90 transition-transform" />
                            </summary>
                            <div className="px-6 pb-6">
                              <p className="text-sm text-navy-400 dark:text-navy-300 leading-relaxed">{item.a}</p>
                            </div>
                          </details>
                        ))}
                      </div>
                    </FadeInView>
                  </div>
                ))}
              </div>
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
                Still Have Questions?
              </h2>
              <p className="text-lg text-navy-100 mb-8 max-w-2xl mx-auto">
                Our support team is available 24/7 to help with any questions you may have.
              </p>
              <div className="flex flex-col sm:flex-row gap-4 justify-center">
                <Button variant="accent" size="xl" className="font-semibold text-base" asChild>
                  <Link href="/contact">Contact Support <ArrowRight className="ml-2 h-5 w-5" /></Link>
                </Button>
                <Button variant="outline" size="xl" className="text-white border-white/30 hover:bg-white/10 text-base" asChild>
                  <Link href="tel:+18005550199">Call +1 (800) 555-0199</Link>
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
