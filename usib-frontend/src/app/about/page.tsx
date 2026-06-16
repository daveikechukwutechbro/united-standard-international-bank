import type { Metadata } from "next"
import Link from "next/link"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import Header from "@/components/Header"
import Footer from "@/components/Footer"
import FadeInView from "@/components/FadeInView"
import {
  ArrowRight, CheckCircle, Globe, Shield, Users,
  Award, Target, Heart, ChevronRight, Quote,
  Building2, Landmark
} from "lucide-react"

export const metadata: Metadata = {
  title: "About Us | United Standard International Bank",
  description: "Learn about USIB's mission, history, leadership team, global presence, and core values. Trusted since 1987.",
}

const values = [
  { icon: Shield, title: "Trust & Integrity", desc: "We uphold the highest standards of honesty and transparency in every interaction." },
  { icon: Users, title: "Client Focus", desc: "Every decision we make starts with understanding and serving our clients' needs." },
  { icon: Award, title: "Excellence", desc: "We strive for excellence in everything we do, from service to innovation." },
  { icon: Target, title: "Innovation", desc: "We continuously evolve our technology and services to better serve our clients." },
  { icon: Globe, title: "Global Perspective", desc: "We bring a worldwide view to banking, connecting clients to opportunities globally." },
  { icon: Heart, title: "Community", desc: "We are committed to making a positive impact in the communities we serve." },
]

const timeline = [
  { year: "1987", event: "Founded in New York City as a regional commercial bank with a single branch." },
  { year: "1995", event: "Expanded nationally with 50 branches across the United States." },
  { year: "2003", event: "Launched international banking division and opened first overseas office in London." },
  { year: "2010", event: "Reached $100 billion in assets under management and expanded to 20 countries." },
  { year: "2015", event: "Launched award-winning mobile banking platform and digital services." },
  { year: "2020", event: "Opened 50 additional international branches across Asia, Europe, and Middle East." },
  { year: "2024", event: "Surpassed $847 billion in assets under management serving 12 million clients globally." },
]

const leaders = [
  { name: "Robert H. Kensington", title: "Chairman & CEO", image: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=1974&auto=format&fit=crop" },
  { name: "Amanda Foster", title: "President & COO", image: "https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?q=80&w=1976&auto=format&fit=crop" },
  { name: "David S. Park", title: "Chief Financial Officer", image: "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?q=80&w=2070&auto=format&fit=crop" },
  { name: "Elena Rodriguez", title: "Chief Risk Officer", image: "https://images.unsplash.com/photo-1580489944761-15a19d654956?q=80&w=1961&auto=format&fit=crop" },
  { name: "James T. Mitchell", title: "Head of Global Banking", image: "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=1974&auto=format&fit=crop" },
  { name: "Sarah L. Chen", title: "Chief Technology Officer", image: "https://images.unsplash.com/photo-1517841905240-472988babdf9?q=80&w=1974&auto=format&fit=crop" },
]

const presence = [
  { region: "North America", countries: "United States, Canada, Mexico", offices: "120+" },
  { region: "Europe", countries: "United Kingdom, Germany, France, Switzerland, Netherlands", offices: "45+" },
  { region: "Asia Pacific", countries: "Singapore, Hong Kong, Japan, Australia, China", offices: "35+" },
  { region: "Middle East & Africa", countries: "UAE, Saudi Arabia, South Africa, Qatar", offices: "20+" },
  { region: "Latin America", countries: "Brazil, Argentina, Chile, Colombia", offices: "15+" },
]

export default function AboutPage() {
  return (
    <>
      <Header />
      <main>
        <section className="relative pt-32 pb-20 lg:pt-40 lg:pb-28 overflow-hidden">
          <div className="absolute inset-0 gradient-hero" />
          <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=2070&auto=format&fit=crop')] bg-cover bg-center opacity-10" />
          <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="max-w-3xl">
              <Badge variant="outline" className="border-gold-400/50 text-gold-400 bg-gold-500/10 mb-4">About USIB</Badge>
              <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold font-display text-white leading-tight">
                A Legacy of{" "}
                <span className="text-gradient">Trust & Excellence</span>
              </h1>
              <p className="mt-6 text-lg text-navy-100 max-w-2xl leading-relaxed">
                Since 1987, United Standard International Bank has been providing premium banking services 
                to individuals and businesses around the world. Our commitment to security, innovation, and 
                client service has made us one of the most trusted financial institutions globally.
              </p>
              <div className="mt-10 flex flex-col sm:flex-row gap-4">
                <Button variant="accent" size="xl" className="font-semibold text-base" asChild>
                  <Link href="#timeline">Our History <ArrowRight className="ml-2 h-5 w-5" /></Link>
                </Button>
                <Button variant="outline" size="xl" className="text-white border-white/30 hover:bg-white/10 text-base" asChild>
                  <Link href="#leadership">Leadership Team</Link>
                </Button>
              </div>
            </FadeInView>
          </div>
        </section>

        <section className="py-20 lg:py-28 bg-white dark:bg-navy-900">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Our Mission</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight mb-6">
                Empowering Financial{" "}
                <span className="text-gold-500">Success Worldwide</span>
              </h2>
              <p className="text-lg text-navy-400 dark:text-navy-300 max-w-2xl mx-auto leading-relaxed">
                Our mission is to provide secure, innovative, and accessible banking solutions that empower 
                individuals and businesses to achieve their financial goals. We combine global expertise with 
                personalized service to create lasting relationships with our clients.
              </p>
            </FadeInView>
          </div>
        </section>

        <section className="py-20 lg:py-28 bg-gray-50 dark:bg-navy-950">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Our Values</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                What We{" "}
                <span className="text-gold-500">Stand For</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                Our core values guide every decision we make and every relationship we build.
              </p>
            </FadeInView>
            <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
              {values.map((value) => (
                <Card key={value.title} className="bg-white dark:bg-navy-800 border border-gray-100 dark:border-navy-700 text-center hover:shadow-card-hover transition-all duration-300">
                  <CardContent className="p-8">
                    <div className="flex justify-center mb-4">
                      <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-gold-500/10">
                        <value.icon className="h-6 w-6 text-gold-500" />
                      </div>
                    </div>
                    <h3 className="text-lg font-bold font-display text-navy-900 dark:text-white mb-3">{value.title}</h3>
                    <p className="text-sm text-navy-400 dark:text-navy-300">{value.desc}</p>
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>
        </section>

        <section id="timeline" className="py-20 lg:py-28 bg-white dark:bg-navy-900">
          <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Our History</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                A Journey of{" "}
                <span className="text-gold-500">Growth</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                From a single branch to a global banking powerhouse. Key milestones in our history.
              </p>
            </FadeInView>
            <div className="relative">
              <div className="absolute left-8 top-0 bottom-0 w-px bg-gray-200 dark:bg-navy-700" />
              <div className="space-y-12">
                {timeline.map((item) => (
                  <div key={item.year} className="relative pl-20">
                    <div className="absolute left-4 top-1 flex h-10 w-10 items-center justify-center rounded-full bg-gold-500 text-navy-900 text-sm font-bold -translate-x-1/2">
                      {item.year}
                    </div>
                    <div className="bg-gray-50 dark:bg-navy-800 rounded-xl p-6">
                      <p className="text-navy-600 dark:text-navy-200">{item.event}</p>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </section>

        <section id="leadership" className="py-20 lg:py-28 bg-gray-50 dark:bg-navy-950">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Leadership</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Our Executive{" "}
                <span className="text-gold-500">Team</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                Experienced leaders committed to guiding USIB into the future of global banking.
              </p>
            </FadeInView>
            <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
              {leaders.map((leader) => (
                <Card key={leader.name} className="bg-white dark:bg-navy-800 border border-gray-100 dark:border-navy-700 hover:shadow-card-hover transition-all duration-300 overflow-hidden">
                  <div className="aspect-[4/3] overflow-hidden bg-gray-100 dark:bg-navy-700">
                    <img src={leader.image} alt={leader.name} className="w-full h-full object-cover object-center" />
                  </div>
                  <CardContent className="p-6">
                    <h3 className="text-lg font-bold font-display text-navy-900 dark:text-white">{leader.name}</h3>
                    <p className="text-sm text-navy-400 dark:text-navy-300">{leader.title}</p>
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>
        </section>

        <section className="py-20 lg:py-28 bg-white dark:bg-navy-900">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <FadeInView className="text-center max-w-3xl mx-auto mb-16">
              <Badge variant="secondary" className="mb-4">Global Presence</Badge>
              <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-navy-900 dark:text-white leading-tight">
                Worldwide{" "}
                <span className="text-gold-500">Reach</span>
              </h2>
              <p className="mt-4 text-lg text-navy-400 dark:text-navy-300">
                With offices in over 50 countries, we provide seamless banking services across the globe.
              </p>
            </FadeInView>
            <div className="grid md:grid-cols-2 lg:grid-cols-5 gap-6">
              {presence.map((region) => (
                <Card key={region.region} className="bg-gray-50 dark:bg-navy-800 border border-gray-100 dark:border-navy-700">
                  <CardContent className="p-6 text-center">
                    <h3 className="text-lg font-bold font-display text-navy-900 dark:text-white mb-2">{region.region}</h3>
                    <p className="text-xs text-navy-400 mb-3">{region.countries}</p>
                    <Badge variant="outline" className="border-gold-500/30 text-gold-500">{region.offices} offices</Badge>
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
                Join the USIB Family
              </h2>
              <p className="text-lg text-navy-100 mb-8 max-w-2xl mx-auto">
                Experience the difference of banking with a trusted global institution. Open your account today.
              </p>
              <div className="flex flex-col sm:flex-row gap-4 justify-center">
                <Button variant="accent" size="xl" className="font-semibold text-base" asChild>
                  <Link href="/personal-banking">Open an Account <ArrowRight className="ml-2 h-5 w-5" /></Link>
                </Button>
                <Button variant="outline" size="xl" className="text-white border-white/30 hover:bg-white/10 text-base" asChild>
                  <Link href="/contact">Contact Us</Link>
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
