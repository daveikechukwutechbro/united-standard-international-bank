import Link from "next/link"

export default function AuthLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-navy-900 via-navy-800 to-navy-950 p-4">
      <div className="w-full max-w-md mx-auto">
        <div className="text-center mb-8">
          <Link href="/" className="inline-block">
            <h1 className="font-display text-2xl font-bold text-gold-500 tracking-tight">
              United Standard
            </h1>
            <p className="font-display text-sm tracking-[0.2em] text-gold-400/80 -mt-1">
              INTERNATIONAL BANK
            </p>
          </Link>
        </div>
        {children}
        <p className="text-center mt-8 text-xs text-navy-300/60">
          &copy; {new Date().getFullYear()} United Standard International Bank. All rights reserved.
        </p>
      </div>
    </div>
  )
}
