"use client"

import { useState, useEffect } from "react"
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query"
import {
  CheckCircle2, XCircle, AlertCircle, Clock, FileText,
  Upload, Shield, User, MapPin, Fingerprint, Camera,
  ChevronRight, Building2, Loader2, ArrowRight, Briefcase
} from "lucide-react"
import { api } from "@/lib/api"
import { formatDate, cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import {
  Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter
} from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Label } from "@/components/ui/label"
import { Badge } from "@/components/ui/badge"
import { Skeleton } from "@/components/ui/skeleton"
import {
  Select, SelectGroup, SelectValue, SelectTrigger, SelectContent, SelectItem
} from "@/components/ui/select"
import { useToast } from "@/components/ui/toast"
import { auth } from "@/lib/auth"
import type { KycStatus } from "@/lib/types"

const steps = [
  { key: "not_submitted", label: "Not Submitted", icon: FileText },
  { key: "pending", label: "Pending Review", icon: Clock },
  { key: "under_review", label: "Under Review", icon: AlertCircle },
  { key: "approved", label: "Approved", icon: CheckCircle2 },
]

const statusConfig: Record<KycStatus, { label: string; variant: "warning" | "success" | "default" | "danger" | "secondary" }> = {
  not_submitted: { label: "Not Submitted", variant: "secondary" },
  pending: { label: "Pending", variant: "warning" },
  under_review: { label: "Under Review", variant: "default" },
  approved: { label: "Approved", variant: "success" },
  rejected: { label: "Rejected", variant: "danger" },
}

const documentTypes = [
  { value: "passport", label: "Passport" },
  { value: "drivers_license", label: "Driver's License" },
  { value: "national_id", label: "National ID" },
  { value: "utility_bill", label: "Utility Bill" },
  { value: "bank_statement", label: "Bank Statement" },
]

export default function KycPage() {
  const queryClient = useQueryClient()
  const { show, Toast } = useToast()
  const user = auth.getUser()

  const { data: kyc, isLoading } = useQuery({
    queryKey: ["kyc-status"],
    queryFn: api.getKycStatus,
  })

  const [formData, setFormData] = useState({
    firstName: "",
    lastName: "",
    dateOfBirth: "",
    nationality: "",
    gender: "",
    phone: "",
    street: "",
    city: "",
    state: "",
    zipCode: "",
    country: "",
    occupation: "",
    sourceOfFunds: "",
  })

  useEffect(() => {
    if (kyc?.personalDetails) {
      const pd = kyc.personalDetails
      setFormData({
        firstName: pd.firstName || "",
        lastName: pd.lastName || "",
        dateOfBirth: pd.dateOfBirth || "",
        nationality: pd.nationality || "",
        gender: pd.gender || "",
        phone: pd.phone || "",
        street: pd.address?.street || "",
        city: pd.address?.city || "",
        state: pd.address?.state || "",
        zipCode: pd.address?.zipCode || "",
        country: pd.address?.country || "",
        occupation: pd.occupation || "",
        sourceOfFunds: pd.sourceOfFunds || "",
      })
    } else if (user) {
      setFormData(prev => ({
        ...prev,
        firstName: user.firstName,
        lastName: user.lastName,
        phone: user.phone,
        street: user.address?.street || "",
        city: user.address?.city || "",
        state: user.address?.state || "",
        zipCode: user.address?.zipCode || "",
        country: user.address?.country || "",
      }))
    }
  }, [kyc, user])

  const submitMutation = useMutation({
    mutationFn: () => api.submitKyc({
      firstName: formData.firstName,
      lastName: formData.lastName,
      dateOfBirth: formData.dateOfBirth,
      nationality: formData.nationality,
      gender: formData.gender,
      phone: formData.phone,
      address: {
        street: formData.street,
        city: formData.city,
        state: formData.state,
        zipCode: formData.zipCode,
        country: formData.country,
      },
      occupation: formData.occupation || undefined,
      sourceOfFunds: formData.sourceOfFunds || undefined,
    }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["kyc-status"] })
      show("KYC submitted successfully", "success")
    },
    onError: (err: Error) => {
      show(err.message || "Failed to submit KYC", "error")
    },
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    if (!formData.firstName || !formData.lastName || !formData.dateOfBirth || !formData.nationality || !formData.phone) {
      show("Please fill in all required fields", "error")
      return
    }
    submitMutation.mutate()
  }

  if (isLoading) {
    return (
      <div className="space-y-6">
        <Skeleton className="h-8 w-48" />
        <Skeleton className="h-32 w-full rounded-xl" />
        <Skeleton className="h-96 w-full rounded-xl" />
      </div>
    )
  }

  const currentStatus: KycStatus = kyc?.status || "not_submitted"
  const isApproved = currentStatus === "approved"
  const isRejected = currentStatus === "rejected"
  const isSubmitted = currentStatus !== "not_submitted"
  const currentStepIndex = steps.findIndex(s => s.key === currentStatus)

  return (
    <div className="space-y-8">
      {Toast}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold font-display text-foreground">Verification (KYC)</h1>
          <p className="text-sm text-muted-foreground">Complete your identity verification to unlock all features</p>
        </div>
        <Badge variant={statusConfig[currentStatus].variant} className="text-sm px-4 py-1.5">
          {statusConfig[currentStatus].label}
        </Badge>
      </div>

      {isApproved ? (
        <Card className="border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20">
          <CardContent className="flex items-center gap-4 p-6">
            <div className="flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
              <CheckCircle2 className="h-8 w-8 text-green-600" />
            </div>
            <div>
              <h2 className="text-lg font-bold text-green-800 dark:text-green-200">Verification Approved</h2>
              <p className="text-sm text-green-600 dark:text-green-400">
                Your identity has been verified successfully. You now have access to all banking features.
                {kyc?.reviewedAt && ` Verified on ${formatDate(kyc.reviewedAt, "long")}.`}
              </p>
            </div>
          </CardContent>
        </Card>
      ) : isRejected ? (
        <Card className="border-red-200 bg-red-50 dark:bg-red-900/20">
          <CardContent className="p-6">
            <div className="flex items-start gap-4">
              <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900">
                <XCircle className="h-6 w-6 text-red-600" />
              </div>
              <div>
                <h2 className="text-lg font-bold text-red-800 dark:text-red-200">Verification Rejected</h2>
                <p className="text-sm text-red-600 dark:text-red-400">
                  {kyc?.rejectionReason || "Your verification was not approved. Please review and resubmit."}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      ) : null}

      <div className="flex items-center justify-between rounded-xl border bg-card p-6">
        {steps.map((step, i) => {
          const isActive = currentStepIndex >= i
          const isCurrent = currentStepIndex === i
          return (
            <div key={step.key} className="flex items-center gap-3">
              <div className="flex items-center gap-3">
                <div className={cn(
                  "flex h-10 w-10 items-center justify-center rounded-full",
                  isActive ? "bg-gold-500 text-navy-900" : "bg-muted text-muted-foreground"
                )}>
                  <step.icon className="h-5 w-5" />
                </div>
                <div>
                  <p className={cn(
                    "text-sm font-medium",
                    isCurrent ? "text-gold-500" : isActive ? "text-foreground" : "text-muted-foreground"
                  )}>
                    {step.label}
                  </p>
                </div>
              </div>
              {i < steps.length - 1 && (
                <ChevronRight className={cn(
                  "h-5 w-5",
                  isActive && i < currentStepIndex ? "text-gold-500" : "text-muted"
                )} />
              )}
            </div>
          )
        })}
      </div>

      {!isApproved && (
        <Card>
          <CardHeader>
            <CardTitle>Personal Details</CardTitle>
            <CardDescription>Provide your personal information for verification</CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                  <Label required>First Name</Label>
                  <Input
                    value={formData.firstName}
                    onChange={(e) => setFormData(prev => ({ ...prev, firstName: e.target.value }))}
                    disabled={isSubmitted}
                  />
                </div>
                <div className="space-y-2">
                  <Label required>Last Name</Label>
                  <Input
                    value={formData.lastName}
                    onChange={(e) => setFormData(prev => ({ ...prev, lastName: e.target.value }))}
                    disabled={isSubmitted}
                  />
                </div>
                <div className="space-y-2">
                  <Label required>Date of Birth</Label>
                  <Input
                    type="date"
                    value={formData.dateOfBirth}
                    onChange={(e) => setFormData(prev => ({ ...prev, dateOfBirth: e.target.value }))}
                    disabled={isSubmitted}
                  />
                </div>
                <div className="space-y-2">
                  <Label required>Nationality</Label>
                  <Select
                    value={formData.nationality}
                    onValueChange={(v) => setFormData(prev => ({ ...prev, nationality: v }))}
                    disabled={isSubmitted}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Select nationality" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="US">United States</SelectItem>
                      <SelectItem value="GB">United Kingdom</SelectItem>
                      <SelectItem value="NG">Nigeria</SelectItem>
                      <SelectItem value="GH">Ghana</SelectItem>
                      <SelectItem value="KE">Kenya</SelectItem>
                      <SelectItem value="ZA">South Africa</SelectItem>
                      <SelectItem value="DE">Germany</SelectItem>
                      <SelectItem value="FR">France</SelectItem>
                      <SelectItem value="CA">Canada</SelectItem>
                      <SelectItem value="AU">Australia</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Gender</Label>
                  <Select
                    value={formData.gender}
                    onValueChange={(v) => setFormData(prev => ({ ...prev, gender: v }))}
                    disabled={isSubmitted}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Select gender" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="male">Male</SelectItem>
                      <SelectItem value="female">Female</SelectItem>
                      <SelectItem value="other">Other</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label required>Phone Number</Label>
                  <Input
                    value={formData.phone}
                    onChange={(e) => setFormData(prev => ({ ...prev, phone: e.target.value }))}
                    disabled={isSubmitted}
                  />
                </div>
              </div>

              <div>
                <h3 className="mb-4 flex items-center gap-2 text-sm font-semibold text-foreground">
                  <MapPin className="h-4 w-4" />
                  Address Information
                </h3>
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2 md:col-span-2">
                    <Label>Street Address</Label>
                    <Input
                      value={formData.street}
                      onChange={(e) => setFormData(prev => ({ ...prev, street: e.target.value }))}
                      disabled={isSubmitted}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>City</Label>
                    <Input
                      value={formData.city}
                      onChange={(e) => setFormData(prev => ({ ...prev, city: e.target.value }))}
                      disabled={isSubmitted}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>State</Label>
                    <Input
                      value={formData.state}
                      onChange={(e) => setFormData(prev => ({ ...prev, state: e.target.value }))}
                      disabled={isSubmitted}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>ZIP Code</Label>
                    <Input
                      value={formData.zipCode}
                      onChange={(e) => setFormData(prev => ({ ...prev, zipCode: e.target.value }))}
                      disabled={isSubmitted}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>Country</Label>
                    <Input
                      value={formData.country}
                      onChange={(e) => setFormData(prev => ({ ...prev, country: e.target.value }))}
                      disabled={isSubmitted}
                    />
                  </div>
                </div>
              </div>

              <div>
                <h3 className="mb-4 flex items-center gap-2 text-sm font-semibold text-foreground">
                  <Briefcase className="h-4 w-4" />
                  Additional Information
                </h3>
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label>Occupation</Label>
                    <Input
                      value={formData.occupation}
                      onChange={(e) => setFormData(prev => ({ ...prev, occupation: e.target.value }))}
                      disabled={isSubmitted}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>Source of Funds</Label>
                    <Select
                      value={formData.sourceOfFunds}
                      onValueChange={(v) => setFormData(prev => ({ ...prev, sourceOfFunds: v }))}
                      disabled={isSubmitted}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Select source" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="Employment">Employment</SelectItem>
                        <SelectItem value="Business">Business</SelectItem>
                        <SelectItem value="Investment">Investment</SelectItem>
                        <SelectItem value="Inheritance">Inheritance</SelectItem>
                        <SelectItem value="Savings">Savings</SelectItem>
                        <SelectItem value="Other">Other</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>
              </div>

              <div>
                <h3 className="mb-4 flex items-center gap-2 text-sm font-semibold text-foreground">
                  <Upload className="h-4 w-4" />
                  Document Upload
                </h3>
                <div className="grid gap-4 md:grid-cols-2">
                  {documentTypes.map((doc) => (
                    <label
                      key={doc.value}
                      className="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-muted-foreground/20 p-6 transition-colors hover:border-gold-500/50 hover:bg-gold-50/50 dark:hover:bg-gold-900/10"
                    >
                      <Upload className="mb-2 h-6 w-6 text-muted-foreground" />
                      <span className="text-sm font-medium text-foreground">{doc.label}</span>
                      <span className="text-xs text-muted-foreground">Click to upload</span>
                      <input type="file" className="hidden" accept=".pdf,.jpg,.jpeg,.png" />
                    </label>
                  ))}
                </div>
              </div>

              <div>
                <h3 className="mb-4 flex items-center gap-2 text-sm font-semibold text-foreground">
                  <Camera className="h-4 w-4" />
                  Face Verification
                </h3>
                <div className="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-muted-foreground/20 p-8">
                  <Camera className="mb-2 h-10 w-10 text-muted-foreground" />
                  <p className="text-sm font-medium text-foreground">Take a Selfie</p>
                  <p className="text-xs text-muted-foreground">Use your camera to verify your identity</p>
                  <Button variant="outline" className="mt-4" disabled={isSubmitted}>
                    Start Face Verification
                  </Button>
                </div>
              </div>

              {!isSubmitted && (
                <div className="flex justify-end gap-3 pt-4">
                  <Button type="submit" size="lg" loading={submitMutation.isPending}>
                    {submitMutation.isPending ? "Submitting..." : "Submit for Verification"}
                  </Button>
                </div>
              )}
            </form>
          </CardContent>
        </Card>
      )}

      {kyc?.documents && kyc.documents.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Uploaded Documents</CardTitle>
            <CardDescription>Documents you have submitted for verification</CardDescription>
          </CardHeader>
          <CardContent className="p-0">
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left text-xs font-medium text-muted-foreground">
                    <th className="px-6 py-3">Document</th>
                    <th className="px-6 py-3">File</th>
                    <th className="px-6 py-3">Uploaded</th>
                    <th className="px-6 py-3">Status</th>
                  </tr>
                </thead>
                <tbody>
                  {kyc.documents.map((doc) => (
                    <tr key={doc.id} className="border-b last:border-0">
                      <td className="px-6 py-4 capitalize text-foreground">{doc.type.replace("_", " ")}</td>
                      <td className="px-6 py-4 text-muted-foreground">{doc.fileName}</td>
                      <td className="px-6 py-4 text-muted-foreground">{formatDate(doc.uploadedAt)}</td>
                      <td className="px-6 py-4">
                        <Badge variant={doc.status === "approved" ? "success" : doc.status === "rejected" ? "danger" : "warning"} className="capitalize">
                          {doc.status}
                        </Badge>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  )
}
