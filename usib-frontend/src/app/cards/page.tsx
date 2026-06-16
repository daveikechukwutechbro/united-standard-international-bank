import type { Metadata } from "next"
import Link from "next/link"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import Header from "@/components/Header"
import Footer from "@/components/Footer"
import FadeInView from "@/components/FadeInView"
import {
  ArrowRight, CheckCircle, CreditCard, Smartphone,
  Globe, Shield, Award, Wallet, ChevronRight, Star
} from "lucide-react"

export const metadata: Metadata = {
  title: "Cards | United Standard International Bank",
  description: "Premium debit, credit, and virtual cards with rewards, travel benefits, and advanced security features.",
}

const cardProducts = [
  {
    icon: CreditCard,
    title: "Premium Debit Card",
    desc: "Your everyday card with global ATM access, contactless payments, and real-time transaction alerts.",
    badge: "Free",
    features: ["Free global ATM withdrawals", "Contactless payments", "Real-time transaction alerts", "Mobile wallet compatible", "Zero liability protection", "Instant card freeze"],
    color: "from-navy-500 to-usib-600",
  },
  {
    icon: Star,
    title: "Signature Credit Card",
    desc: "Earn unlimited rewards on every purchase with premium travel and lifestyle benefits.",
    badge: "From $0/yr",
    features: ["3x points on travel & dining", "2x points on all purchases", "Airport lounge access", "Travel insurance included", "Concierge service", "Purchase protection"],
    color: "from-gold-500 to-gold-600",
  },
  {
    icon: Globe,
    title: "World Elite Card",
    desc: "The ultimate card for high-net-worth individuals with exclusive privileges and personalized service.",
    badge: "$495/yr",
    features: ["Unlimited rewards points", "Global concierge 24/7", "First-class lounge access", "$200 annual travel credit", "Global entry credit", "Dedicated relationship manager"],
    color: "from-navy-700 to-navy-900",
  },
  {
    icon: Smartphone,
    title: "Virtual Card",
    desc: "Create disposable virtual cards for secure online purchases with complete control over spending.",
    badge: "Instant issue",
    features: ["Instant card generation", "Single-use or multi-use", "Set spending limits", "Merchant-specific cards", "Real-time notifications", "No physical card needed"],
    color: "from-blue-500 to-blue-600",
  },
]

export default function CardsPage() {
  return (
    <>
      <Header />
      <main>
        <section className="relative pt-32 pb-20 lg:pt-40 lg:pb-28 overflow-hidden">
          <div className="absolute inset-0 gradient-hero" />
          <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?q=80&w=2070&auto=format&fit=crop')] bg-cover bg-center opacity-10" />
          <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="max-w-3xl">
              <Badge variant="outline" className="border-gold-400/50 text-gold-400 bg-gold-500/10 mb-4">Cards</Badge>
              <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold font-display text-white leading-tight">
                Premium Cards for{" "}
                <span className="text-gradient">Every Lifestyle</span>
              </h1>
              <p className="mt-6 text-lg text-navy-100 max-w-2xl leading-relaxed">
                From everyday debit to world-class credit, our range of premium cards offers rewards, 
                security, and benefits designed to enhance your financial life.
              </p>
              <div className="mt-10 flex flex-col sm:flex-row gap-4">
                <Button variant="accent" size="xl" className="font-semibold text-base" asChild>
                  <Link href="#cards">Compare Cards <ArrowRight className="ml-2 h-5 w-5" /></Link>
                </Button>
                <Button variant="outline" size="xl" className="text-white border-white/30 hover:bg-white/10 text-base" asChild>
                  <Link href="#features">Card Features</Link>
                </Button>
              </div>
            </FadeInView>
          </div>
        </section>

        <section id="cards" className="py-20 lg:py-28 bg-white dark:bg-navy-900">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Our Cards</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Choose Your{" "}
                <span className="text-gold-500">Perfect Card</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                Each card comes with premium benefits, advanced security, and exceptional rewards.
              </p>
            </FadeInView>
            <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
              {cardProducts.map((card) => (
                <Card key={card.title} className="group hover:shadow-card-hover transition-all duration-300 border border-gray-100 dark:border-navy-700 bg-white dark:bg-navy-800 overflow-hidden">
                  <div className="h-2 bg-gradient-to-r" style={{ background: `linear-gradient(90deg, ${card.color.split(' ')[0].replace('from-', '')}, ${card.color.split(' ')[1].replace('to-', '')})` }} />
                  <CardContent className="p-6">
                    <Badge variant="outline" className="border-gold-500/30 text-gold-500 bg-gold-500/5 mb-3">{card.badge}</Badge>
                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-gold-500/10 mb-4">
                      <card.icon className="h-5 w-5 text-gold-500" />
                    </div>
                    <h3 className="text-lg font-bold font-display text-navy-900 dark:text-white mb-2">{card.title}</h3>
                    <p className="text-sm text-navy-400 dark:text-navy-300 mb-4">{card.desc}</p>
                    <ul className="space-y-2 mb-6">
                      {card.features.map((f) => (
                        <li key={f} className="flex items-start gap-2 text-xs text-navy-500 dark:text-navy-300">
                          <CheckCircle className="h-3.5 w-3.5 text-gold-500 flex-shrink-0 mt-0.5" />
                          {f}
                        </li>
                      ))}
                    </ul>
                    <Button variant="accent" size="sm" className="w-full font-semibold">
                      Apply Now
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
              <Badge variant="secondary" className="mb-4">Card Benefits</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Premium Benefits{" "}
                <span className="text-gold-500">Included</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                Every USIB card comes with a comprehensive suite of benefits and protections.
              </p>
            </FadeInView>
            <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
              {[
                { icon: Shield, title: "Zero Liability", desc: "You're not responsible for unauthorized transactions when you promptly report them." },
                { icon: Award, title: "Rewards Program", desc: "Earn points on every purchase and redeem for travel, merchandise, cash back, and more." },
                { icon: Globe, title: "Travel Benefits", desc: "Enjoy travel insurance, lounge access, concierge service, and no foreign transaction fees." },
                { icon: Smartphone, title: "Mobile Wallet", desc: "Add your card to Apple Pay, Google Pay, or Samsung Pay for secure contactless payments." },
                { icon: Shield, title: "Fraud Protection", desc: "Real-time fraud monitoring, instant alerts, and the ability to freeze your card instantly." },
                { icon: CreditCard, title: "Contactless", desc: "Tap to pay anywhere contactless payments are accepted for fast, secure transactions." },
              ].map((feature) => (
                <Card key={feature.title} className="bg-white dark:bg-navy-800 border border-gray-100 dark:border-navy-700 hover:shadow-card-hover transition-all duration-300">
                  <CardContent className="p-8">
                    <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-gold-500/10 mb-5">
                      <feature.icon className="h-6 w-6 text-gold-500" />
                    </div>
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
                Get Your Premium Card Today
              </h2>
              <p className="text-lg text-navy-100 mb-8 max-w-2xl mx-auto">
                Apply online in minutes and start enjoying premium benefits, rewards, and security.
              </p>
              <div className="flex flex-col sm:flex-row gap-4 justify-center">
                <Button variant="accent" size="xl" className="font-semibold text-base" asChild>
                  <Link href="#cards">Apply for a Card <ArrowRight className="ml-2 h-5 w-5" /></Link>
                </Button>
                <Button variant="outline" size="xl" className="text-white border-white/30 hover:bg-white/10 text-base" asChild>
                  <Link href="/contact">Cardholder Support</Link>
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
