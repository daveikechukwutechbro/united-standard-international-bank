import type { Metadata } from "next"
import Link from "next/link"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import Header from "@/components/Header"
import Footer from "@/components/Footer"
import FadeInView from "@/components/FadeInView"
import {
  ArrowRight, Phone, Mail, MapPin, Clock,
  Building2, ChevronRight, Send
} from "lucide-react"

export const metadata: Metadata = {
  title: "Contact Us | United Standard International Bank",
  description: "Get in touch with USIB. Find branch locations, contact information, business hours, and send us a message.",
}

const offices = [
  {
    city: "New York (Headquarters)",
    address: "1 Financial Square",
    address2: "New York, NY 10005",
    phone: "+1 (212) 555-0100",
    hours: "Mon-Fri: 24 hours",
    image: "https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=2070&auto=format&fit=crop",
  },
  {
    city: "London",
    address: "25 Cannon Street",
    address2: "London EC4M 5TA",
    phone: "+44 20 7555 0100",
    hours: "Mon-Fri: 8:00 AM - 6:00 PM",
    image: "https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?q=80&w=2070&auto=format&fit=crop",
  },
  {
    city: "Singapore",
    address: "9 Raffles Place",
    address2: "Singapore 048619",
    phone: "+65 6885 0100",
    hours: "Mon-Fri: 9:00 AM - 6:00 PM",
    image: "https://images.unsplash.com/photo-1565895405138-6c3a1555da6a?q=80&w=2070&auto=format&fit=crop",
  },
]

export default function ContactPage() {
  return (
    <>
      <Header />
      <main>
        <section className="relative pt-32 pb-20 lg:pt-40 lg:pb-28 overflow-hidden">
          <div className="absolute inset-0 gradient-hero" />
          <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="max-w-3xl">
              <Badge variant="outline" className="border-gold-400/50 text-gold-400 bg-gold-500/10 mb-4">Contact</Badge>
              <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold font-display text-white leading-tight">
                Get in{" "}
                <span className="text-gradient">Touch</span>
              </h1>
              <p className="mt-6 text-lg text-navy-100 max-w-2xl leading-relaxed">
                We&apos;re here to help. Whether you have a question about our services, need support, 
                or want to provide feedback, our team is ready to assist you.
              </p>
            </FadeInView>
          </div>
        </section>

        <section className="py-20 lg:py-28 bg-white dark:bg-navy-900">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="grid lg:grid-cols-2 gap-12">
              <div>
                <Badge variant="secondary" className="mb-4">Send a Message</Badge>
                <h2 className="text-3xl sm:text-4xl font-bold font-display text-navy-900 dark:text-white leading-tight mb-6">
                  We&apos;d Love to{" "}
                  <span className="text-gold-500">Hear From You</span>
                </h2>
                <p className="text-navy-400 dark:text-navy-300 mb-8">
                  Fill out the form below and a member of our team will get back to you within 24 hours.
                </p>
                <form className="space-y-6">
                  <div className="grid sm:grid-cols-2 gap-4">
                    <div>
                      <label className="text-sm font-medium text-navy-700 dark:text-navy-200 mb-2 block">First Name</label>
                      <input type="text" className="w-full rounded-xl border border-gray-200 dark:border-navy-600 bg-transparent py-3 px-4 text-sm text-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gold-500" placeholder="John" />
                    </div>
                    <div>
                      <label className="text-sm font-medium text-navy-700 dark:text-navy-200 mb-2 block">Last Name</label>
                      <input type="text" className="w-full rounded-xl border border-gray-200 dark:border-navy-600 bg-transparent py-3 px-4 text-sm text-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gold-500" placeholder="Doe" />
                    </div>
                  </div>
                  <div>
                    <label className="text-sm font-medium text-navy-700 dark:text-navy-200 mb-2 block">Email Address</label>
                    <input type="email" className="w-full rounded-xl border border-gray-200 dark:border-navy-600 bg-transparent py-3 px-4 text-sm text-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gold-500" placeholder="john@example.com" />
                  </div>
                  <div>
                    <label className="text-sm font-medium text-navy-700 dark:text-navy-200 mb-2 block">Subject</label>
                    <select className="w-full rounded-xl border border-gray-200 dark:border-navy-600 bg-transparent py-3 px-4 text-sm text-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gold-500">
                      <option>General Inquiry</option>
                      <option>Account Support</option>
                      <option>Loan Application</option>
                      <option>International Banking</option>
                      <option>Business Banking</option>
                      <option>Complaint</option>
                      <option>Other</option>
                    </select>
                  </div>
                  <div>
                    <label className="text-sm font-medium text-navy-700 dark:text-navy-200 mb-2 block">Message</label>
                    <textarea rows={5} className="w-full rounded-xl border border-gray-200 dark:border-navy-600 bg-transparent py-3 px-4 text-sm text-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gold-500 resize-none" placeholder="How can we help you?" />
                  </div>
                  <Button variant="accent" size="lg" className="font-semibold">
                    Send Message <Send className="ml-2 h-4 w-4" />
                  </Button>
                </form>
              </div>
              <div className="space-y-8">
                <div>
                  <Badge variant="secondary" className="mb-4">Contact Information</Badge>
                  <h2 className="text-3xl sm:text-4xl font-bold font-display text-navy-900 dark:text-white leading-tight mb-8">
                    Other Ways to{" "}
                    <span className="text-gold-500">Reach Us</span>
                  </h2>
                </div>
                <div className="space-y-6">
                  <Card className="bg-gray-50 dark:bg-navy-800 border border-gray-100 dark:border-navy-700">
                    <CardContent className="p-6 flex items-start gap-4">
                      <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-gold-500/10 flex-shrink-0">
                        <Phone className="h-6 w-6 text-gold-500" />
                      </div>
                      <div>
                        <h3 className="font-semibold text-navy-900 dark:text-white">Phone</h3>
                        <p className="text-sm text-navy-400">24/7 Customer Support</p>
                        <a href="tel:+18005550199" className="text-lg font-bold text-gold-500 hover:text-gold-400 transition-colors">+1 (800) 555-0199</a>
                      </div>
                    </CardContent>
                  </Card>
                  <Card className="bg-gray-50 dark:bg-navy-800 border border-gray-100 dark:border-navy-700">
                    <CardContent className="p-6 flex items-start gap-4">
                      <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-gold-500/10 flex-shrink-0">
                        <Mail className="h-6 w-6 text-gold-500" />
                      </div>
                      <div>
                        <h3 className="font-semibold text-navy-900 dark:text-white">Email</h3>
                        <p className="text-sm text-navy-400">Send us an email anytime</p>
                        <a href="mailto:support@usib.com" className="text-lg font-bold text-gold-500 hover:text-gold-400 transition-colors">support@usib.com</a>
                      </div>
                    </CardContent>
                  </Card>
                  <Card className="bg-gray-50 dark:bg-navy-800 border border-gray-100 dark:border-navy-700">
                    <CardContent className="p-6 flex items-start gap-4">
                      <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-gold-500/10 flex-shrink-0">
                        <Clock className="h-6 w-6 text-gold-500" />
                      </div>
                      <div>
                        <h3 className="font-semibold text-navy-900 dark:text-white">Business Hours</h3>
                        <div className="space-y-1 text-sm text-navy-400">
                          <p>Monday - Friday: 24 hours</p>
                          <p>Saturday: 8:00 AM - 8:00 PM EST</p>
                          <p>Sunday: 9:00 AM - 6:00 PM EST</p>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                </div>
              </div>
            </FadeInView>
          </div>
        </section>

        <section className="py-20 lg:py-28 bg-gray-50 dark:bg-navy-950">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Our Offices</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Visit Us{" "}
                <span className="text-gold-500">Worldwide</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                With offices in major financial centers around the world, we&apos;re always nearby.
              </p>
            </FadeInView>
            <div className="grid md:grid-cols-3 gap-8">
              {offices.map((office) => (
                <Card key={office.city} className="bg-white dark:bg-navy-800 border border-gray-100 dark:border-navy-700 overflow-hidden hover:shadow-card-hover transition-all duration-300">
                  <div className="h-48 overflow-hidden bg-gray-100 dark:bg-navy-700">
                    <img src={office.image} alt={office.city} className="w-full h-full object-cover" />
                  </div>
                  <CardContent className="p-6">
                    <div className="flex items-center gap-2 mb-3">
                      <Building2 className="h-4 w-4 text-gold-500" />
                      <h3 className="font-bold font-display text-navy-900 dark:text-white">{office.city}</h3>
                    </div>
                    <div className="space-y-2 mb-4">
                      <div className="flex items-start gap-2 text-sm text-navy-400">
                        <MapPin className="h-4 w-4 flex-shrink-0 mt-0.5" />
                        <span>{office.address}, {office.address2}</span>
                      </div>
                      <div className="flex items-center gap-2 text-sm text-navy-400">
                        <Phone className="h-4 w-4 flex-shrink-0" />
                        <span>{office.phone}</span>
                      </div>
                      <div className="flex items-center gap-2 text-sm text-navy-400">
                        <Clock className="h-4 w-4 flex-shrink-0" />
                        <span>{office.hours}</span>
                      </div>
                    </div>
                    <Button variant="outline" size="sm" className="w-full font-medium">
                      Get Directions <ArrowRight className="ml-1 h-4 w-4" />
                    </Button>
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
                Prefer to Bank Online?
              </h2>
              <p className="text-lg text-navy-100 mb-8 max-w-2xl mx-auto">
                Open an account from anywhere in the world. It takes just minutes to get started.
              </p>
              <div className="flex flex-col sm:flex-row gap-4 justify-center">
                <Button variant="accent" size="xl" className="font-semibold text-base" asChild>
                  <Link href="/personal-banking">Open an Account <ArrowRight className="ml-2 h-5 w-5" /></Link>
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
