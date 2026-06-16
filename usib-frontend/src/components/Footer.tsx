import Link from "next/link"
import { Phone, Mail, MapPin, Linkedin, Twitter, Youtube, Instagram } from "lucide-react"

const footerLinks = {
  personal: {
    title: "Personal",
    links: [
      { label: "Checking Accounts", href: "/personal-banking" },
      { label: "Savings Accounts", href: "/personal-banking" },
      { label: "Credit Cards", href: "/cards" },
      { label: "Personal Loans", href: "/loans" },
      { label: "Mortgages", href: "/loans" },
    ],
  },
  business: {
    title: "Business",
    links: [
      { label: "Business Accounts", href: "/business-banking" },
      { label: "Treasury Services", href: "/business-banking" },
      { label: "Business Loans", href: "/loans" },
      { label: "International Trade", href: "/international" },
      { label: "Merchant Services", href: "/business-banking" },
    ],
  },
  resources: {
    title: "Resources",
    links: [
      { label: "Security Center", href: "/security" },
      { label: "FAQ", href: "/faq" },
      { label: "Support", href: "/support" },
      { label: "Pricing & Fees", href: "/pricing" },
      { label: "About Us", href: "/about" },
    ],
  },
  company: {
    title: "Company",
    links: [
      { label: "About USIB", href: "/about" },
      { label: "Contact Us", href: "/contact" },
      { label: "Careers", href: "/about" },
      { label: "Press Room", href: "/about" },
      { label: "Investor Relations", href: "/about" },
    ],
  },
}

const socialLinks = [
  { icon: Linkedin, href: "#", label: "LinkedIn" },
  { icon: Twitter, href: "#", label: "Twitter" },
  { icon: Youtube, href: "#", label: "YouTube" },
  { icon: Instagram, href: "#", label: "Instagram" },
]

export default function Footer() {
  return (
    <footer className="bg-navy-900 text-white">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="py-16">
          <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-8 lg:gap-12">
            <div className="col-span-2 md:col-span-4 lg:col-span-1">
              <Link href="/" className="flex items-center gap-2 mb-4">
                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-gold-500">
                  <span className="text-lg font-bold text-navy-900">U</span>
                </div>
                <div>
                  <p className="text-sm font-bold leading-tight font-display">United Standard</p>
                  <p className="text-[10px] font-medium tracking-widest uppercase text-gold-400">International Bank</p>
                </div>
              </Link>
              <p className="text-sm text-navy-200 leading-relaxed mb-6 max-w-xs">
                Providing premium banking services globally since 1987. Secure, reliable, and trusted by millions.
              </p>
              <div className="flex gap-3">
                {socialLinks.map((social) => (
                  <a
                    key={social.label}
                    href={social.href}
                    aria-label={social.label}
                    className="flex h-9 w-9 items-center justify-center rounded-lg bg-navy-800 text-navy-300 hover:bg-gold-500 hover:text-navy-900 transition-colors"
                  >
                    <social.icon className="h-4 w-4" />
                  </a>
                ))}
              </div>
            </div>

            {Object.values(footerLinks).map((group) => (
              <div key={group.title}>
                <h4 className="text-xs font-semibold uppercase tracking-widest text-gold-400 mb-4">{group.title}</h4>
                <ul className="space-y-3">
                  {group.links.map((link) => (
                    <li key={link.label}>
                      <Link
                        href={link.href}
                        className="text-sm text-navy-200 hover:text-white transition-colors"
                      >
                        {link.label}
                      </Link>
                    </li>
                  ))}
                </ul>
              </div>
            ))}
          </div>
        </div>

        <div className="py-8 border-t border-navy-800">
          <div className="grid md:grid-cols-2 gap-6">
            <div className="space-y-3">
              <h4 className="text-xs font-semibold uppercase tracking-widest text-navy-400">Contact Us</h4>
              <div className="space-y-2">
                <div className="flex items-center gap-2 text-sm text-navy-200">
                  <Phone className="h-3.5 w-3.5 text-gold-400 flex-shrink-0" />
                  <span>+1 (800) 555-0199</span>
                </div>
                <div className="flex items-center gap-2 text-sm text-navy-200">
                  <Mail className="h-3.5 w-3.5 text-gold-400 flex-shrink-0" />
                  <span>contact@usib.com</span>
                </div>
                <div className="flex items-start gap-2 text-sm text-navy-200">
                  <MapPin className="h-3.5 w-3.5 text-gold-400 flex-shrink-0 mt-0.5" />
                  <span>1 Financial Square, New York, NY 10005, United States</span>
                </div>
              </div>
            </div>
            <div className="space-y-3">
              <h4 className="text-xs font-semibold uppercase tracking-widest text-navy-400">Regulatory</h4>
              <p className="text-xs text-navy-300 leading-relaxed">
                United Standard International Bank is a member of the FDIC. Equal Housing Lender. 
                All loans subject to credit approval. Terms and conditions apply. 
                NMLS #1234567.
              </p>
              <p className="text-xs text-navy-300 leading-relaxed">
                Routing Number: 021000021 | SWIFT: USIBUS33
              </p>
            </div>
          </div>
        </div>

        <div className="py-6 border-t border-navy-800 flex flex-col sm:flex-row items-center justify-between gap-4">
          <p className="text-xs text-navy-400">
            &copy; {new Date().getFullYear()} United Standard International Bank. All rights reserved.
          </p>
          <div className="flex flex-wrap gap-4">
            <Link href="/pricing" className="text-xs text-navy-400 hover:text-navy-200 transition-colors">
              Fees & Disclosures
            </Link>
            <Link href="#" className="text-xs text-navy-400 hover:text-navy-200 transition-colors">
              Privacy Policy
            </Link>
            <Link href="#" className="text-xs text-navy-400 hover:text-navy-200 transition-colors">
              Terms of Service
            </Link>
            <Link href="#" className="text-xs text-navy-400 hover:text-navy-200 transition-colors">
              Accessibility
            </Link>
            <Link href="#" className="text-xs text-navy-400 hover:text-navy-200 transition-colors">
              Cookie Policy
            </Link>
          </div>
        </div>
      </div>
    </footer>
  )
}
