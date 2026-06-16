import type { Metadata } from "next"
import Link from "next/link"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import Header from "@/components/Header"
import Footer from "@/components/Footer"
import FadeInView from "@/components/FadeInView"
import {
  ArrowRight, CheckCircle, Lock, Fingerprint, Shield,
  Eye, Bell, AlertTriangle, Smartphone, ChevronRight,
  Monitor, Key, FileText
} from "lucide-react"

export const metadata: Metadata = {
  title: "Security | United Standard International Bank",
  description: "Learn how USIB protects your accounts with 256-bit encryption, biometric authentication, fraud monitoring, and secure banking practices.",
}

const securityLayers = [
  {
    icon: Lock,
    title: "256-Bit Encryption",
    desc: "All data transmitted between your device and our systems is protected using the highest level of SSL/TLS encryption, the same standard used by governments and military organizations.",
    details: ["End-to-end encryption for all transactions", "Secure socket layer (SSL) 3.0 and TLS 1.3", "Automatic encryption of all sensitive data", "Regular security audits and penetration testing"],
  },
  {
    icon: Fingerprint,
    title: "Multi-Factor Authentication",
    desc: "We require multiple forms of verification to access your accounts, ensuring that only you can authorize transactions and view sensitive information.",
    details: ["Biometric login (fingerprint & facial recognition)", "One-time passcodes via SMS or authenticator app", "Device recognition and trusted device management", "Step-up authentication for high-value transactions"],
  },
  {
    icon: Shield,
    title: "Real-Time Fraud Monitoring",
    desc: "Our AI-powered fraud detection system monitors every transaction 24/7, analyzing patterns and flagging suspicious activity in milliseconds.",
    details: ["Machine learning anomaly detection", "Real-time transaction scoring", "Geographic and behavioral analysis", "Automatic account freezing on suspicious activity"],
  },
  {
    icon: Eye,
    title: "Account Alerts & Notifications",
    desc: "Stay informed about every activity on your accounts with customizable real-time alerts sent via push notification, email, or SMS.",
    details: ["Login alerts from new devices", "Large transaction notifications", "Password change confirmations", "Daily balance summaries"],
  },
  {
    icon: Bell,
    title: "Dedicated Security Team",
    desc: "Our cybersecurity experts work around the clock to protect your accounts and respond immediately to any potential threats.",
    details: ["24/7 security operations center", "Immediate incident response", "Regular security updates", "Proactive threat hunting"],
  },
  {
    icon: Smartphone,
    title: "Secure Mobile Banking",
    desc: "Our mobile app includes additional security features to protect your banking on the go, including biometric login and device binding.",
    details: ["Face ID and Touch ID support", "App-specific security PIN", "Remote card freeze/unfreeze", "Secure messaging with encryption"],
  },
]

const tips = [
  { icon: Key, title: "Use Strong Passwords", desc: "Create unique passwords with a mix of letters, numbers, and symbols. Never reuse passwords across different services." },
  { icon: Shield, title: "Enable Two-Factor Auth", desc: "Always enable two-factor authentication for an extra layer of security on your accounts." },
  { icon: Eye, title: "Monitor Your Accounts", desc: "Regularly review your account activity and report any unauthorized transactions immediately." },
  { icon: AlertTriangle, title: "Beware of Phishing", desc: "Never click on suspicious links or provide personal information in response to unsolicited emails or calls." },
  { icon: Smartphone, title: "Keep Software Updated", desc: "Keep your devices, browsers, and apps updated with the latest security patches." },
  { icon: Monitor, title: "Use Secure Networks", desc: "Avoid using public Wi-Fi for banking transactions. Use your mobile data or a trusted VPN connection." },
]

export default function SecurityPage() {
  return (
    <>
      <Header />
      <main>
        <section className="relative pt-32 pb-20 lg:pt-40 lg:pb-28 overflow-hidden">
          <div className="absolute inset-0 gradient-hero" />
          <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1555949963-aa79dcee981c?q=80&w=2070&auto=format&fit=crop')] bg-cover bg-center opacity-10" />
          <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="max-w-3xl">
              <Badge variant="outline" className="border-gold-400/50 text-gold-400 bg-gold-500/10 mb-4">Security</Badge>
              <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold font-display text-white leading-tight">
                Bank-Grade{" "}
                <span className="text-gradient">Security</span>
              </h1>
              <p className="mt-6 text-lg text-navy-100 max-w-2xl leading-relaxed">
                Your security is our highest priority. We employ multiple layers of advanced protection 
                to safeguard your accounts, transactions, and personal information.
              </p>
              <div className="mt-10 flex flex-col sm:flex-row gap-4">
                <Button variant="accent" size="xl" className="font-semibold text-base" asChild>
                  <Link href="#layers">Explore Security Features <ArrowRight className="ml-2 h-5 w-5" /></Link>
                </Button>
                <Button variant="outline" size="xl" className="text-white border-white/30 hover:bg-white/10 text-base" asChild>
                  <Link href="#tips">Security Tips</Link>
                </Button>
              </div>
            </FadeInView>
          </div>
        </section>

        <section id="layers" className="py-20 lg:py-28 bg-white dark:bg-navy-900">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Defense in Depth</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Multiple Layers of{" "}
                <span className="text-gold-500">Protection</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                We use a comprehensive, multi-layered security approach to protect your accounts and data.
              </p>
            </FadeInView>
            <div className="space-y-6">
              {securityLayers.map((layer) => (
                <Card key={layer.title} className="group hover:shadow-card-hover transition-all duration-300 border border-gray-100 dark:border-navy-700 bg-white dark:bg-navy-800">
                  <CardContent className="p-8">
                    <div className="flex flex-col lg:flex-row gap-6">
                      <div className="flex lg:w-64">
                        <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-gold-500/10 flex-shrink-0">
                          <layer.icon className="h-6 w-6 text-gold-500" />
                        </div>
                        <div className="ml-4">
                          <h3 className="text-xl font-bold font-display text-navy-900 dark:text-white">{layer.title}</h3>
                        </div>
                      </div>
                      <div className="flex-1">
                        <p className="text-navy-400 dark:text-navy-300 leading-relaxed mb-4">{layer.desc}</p>
                        <div className="grid sm:grid-cols-2 gap-2">
                          {layer.details.map((d) => (
                            <div key={d} className="flex items-center gap-2 text-sm text-navy-500 dark:text-navy-300">
                              <CheckCircle className="h-4 w-4 text-gold-500 flex-shrink-0" />
                              {d}
                            </div>
                          ))}
                        </div>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>
        </section>

        <section id="tips" className="py-20 lg:py-28 bg-gray-50 dark:bg-navy-950">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Security Tips</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Stay Safe{" "}
                <span className="text-gold-500">Online</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                Follow these best practices to keep your accounts and personal information secure.
              </p>
            </FadeInView>
            <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
              {tips.map((tip) => (
                <Card key={tip.title} className="bg-white dark:bg-navy-800 border border-gray-100 dark:border-navy-700 hover:shadow-card-hover transition-all duration-300">
                  <CardContent className="p-8">
                    <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-gold-500/10 mb-5">
                      <tip.icon className="h-6 w-6 text-gold-500" />
                    </div>
                    <h3 className="text-lg font-bold font-display text-navy-900 dark:text-white mb-3">{tip.title}</h3>
                    <p className="text-sm text-navy-400 dark:text-navy-300">{tip.desc}</p>
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>
        </section>

        <section className="py-20 lg:py-28 bg-white dark:bg-navy-900">
          <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="bg-gradient-to-br from-navy-800 to-navy-900 rounded-3xl p-10 lg:p-12 text-white text-center relative overflow-hidden">
              <div className="absolute inset-0 opacity-10">
                <div className="absolute top-0 right-0 w-64 h-64 rounded-full bg-gold-500 blur-3xl" />
              </div>
              <div className="relative">
                <Badge variant="outline" className="border-gold-400/50 text-gold-400 bg-gold-500/10 mb-4">Report an Issue</Badge>
                <h2 className="text-2xl sm:text-3xl font-bold font-display leading-tight mb-4">
                  Suspect Fraudulent Activity?
                </h2>
                <p className="text-navy-200 mb-6 max-w-lg mx-auto">
                  If you notice unusual activity on your account or suspect you&apos;ve been a victim 
                  of fraud, contact us immediately.
                </p>
                <div className="flex flex-col sm:flex-row gap-4 justify-center">
                  <Button variant="accent" size="lg" className="font-semibold" asChild>
                    <Link href="/contact">Contact Security Team <ArrowRight className="ml-2 h-4 w-4" /></Link>
                  </Button>
                  <Button variant="outline" size="lg" className="text-white border-white/30 hover:bg-white/10" asChild>
                    <Link href="tel:+18005550199">Call +1 (800) 555-0199</Link>
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
                Your Security Is Our Promise
              </h2>
              <p className="text-lg text-navy-100 mb-8 max-w-2xl mx-auto">
                Experience peace of mind with USIB&apos;s bank-grade security. Open an account and bank with confidence.
              </p>
              <div className="flex flex-col sm:flex-row gap-4 justify-center">
                <Button variant="accent" size="xl" className="font-semibold text-base" asChild>
                  <Link href="/personal-banking">Open an Account <ArrowRight className="ml-2 h-5 w-5" /></Link>
                </Button>
                <Button variant="outline" size="xl" className="text-white border-white/30 hover:bg-white/10 text-base" asChild>
                  <Link href="/faq">Security FAQs</Link>
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
