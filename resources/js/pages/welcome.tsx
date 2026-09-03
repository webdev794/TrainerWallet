import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    BellRing,
    CalendarClock,
    Check,
    CreditCard,
    FileText,
    QrCode,
    Users,
} from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import MarketingLayout from '@/layouts/marketing-layout';
import { register } from '@/routes';

const features = [
    {
        icon: FileText,
        title: 'One-click invoicing',
        body: 'Turn a finished session into a branded invoice in seconds. Client details, rates and packages are remembered for you.',
    },
    {
        icon: QrCode,
        title: 'UPI + card payments',
        body: 'Every invoice carries a UPI QR code and a card checkout link. Clients pay the way they prefer, you get reconciled automatically.',
    },
    {
        icon: Users,
        title: 'Client directory',
        body: 'Keep rates, packages and payment preferences per client. Invite them to a portal to see invoices and receipts.',
    },
    {
        icon: BellRing,
        title: 'Automated reminders',
        body: 'Gentle nudges before the due date and an escalating follow-up after it — until the invoice is marked paid.',
    },
    {
        icon: CalendarClock,
        title: 'Recurring billing',
        body: 'Monthly plans and packages bill themselves on schedule so you never chase a renewal again.',
    },
    {
        icon: BarChart3,
        title: 'Payment analytics',
        body: 'See collected revenue, outstanding balances and upcoming settlements at a glance.',
    },
];

const steps = [
    {
        title: 'Log the session',
        body: 'Add the session or pick a saved package. CoachPay knows the rate.',
    },
    {
        title: 'Send the invoice',
        body: 'A branded invoice with a UPI QR and card link goes out by email.',
    },
    {
        title: 'Get paid',
        body: 'Money settles to your account. The dashboard shows “Payment received”.',
    },
];

const plans = [
    {
        name: 'Free',
        price: '$0',
        cadence: 'forever',
        highlight: false,
        features: [
            '5 invoices per month',
            'Client directory',
            'Email reminders',
            'UPI QR + card links',
            'Payment tracking',
        ],
    },
    {
        name: 'Pro',
        price: '$19',
        cadence: 'per month',
        highlight: true,
        features: [
            'Unlimited invoicing',
            'Branded PDF customisation',
            'Recurring billing automation',
            'Advanced reporting',
            'Priority support',
        ],
    },
];

const faqs = [
    {
        q: 'Do my clients need an account to pay?',
        a: 'No. They can pay straight from the emailed invoice link. If they want a history of invoices and receipts, you can invite them to the client portal.',
    },
    {
        q: 'How fast do payouts arrive?',
        a: 'UPI settlements are typically instant. Card payments settle to your linked bank account in 2–7 days depending on the gateway.',
    },
    {
        q: 'Is there a setup fee?',
        a: 'No setup fee and no hidden charges. You only pay the standard payment-gateway processing fee per transaction.',
    },
];

export default function Welcome() {
    return (
        <MarketingLayout>
            <Head title="Get paid faster, coach more" />

            {/* Hero */}
            <section className="mx-auto grid w-full max-w-6xl gap-12 px-4 py-20 lg:grid-cols-2 lg:items-center lg:py-28">
                <div className="space-y-6">
                    <span className="bg-accent text-accent-foreground inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium">
                        3-minute onboarding to your first invoice
                    </span>
                    <h1 className="text-4xl font-bold tracking-tight sm:text-5xl">
                        Get paid faster, spend your time coaching
                    </h1>
                    <p className="text-muted-foreground text-lg">
                        CoachPay automates invoicing, reminders and
                        reconciliation for personal trainers. Bill for a
                        session, and the payment is handled in the background —
                        UPI or card.
                    </p>
                    <div className="flex flex-wrap items-center gap-3">
                        <Button asChild size="lg">
                            <Link href={register.url()}>
                                Start free trial <ArrowRight />
                            </Link>
                        </Button>
                        <Button asChild size="lg" variant="outline">
                            <a href="#how">See how it works</a>
                        </Button>
                    </div>
                    <p className="text-muted-foreground text-sm">
                        No credit card required &middot;{' '}
                        <span className="text-foreground font-medium">
                            87% faster
                        </span>{' '}
                        payment collection on average
                    </p>
                </div>

                <Card className="border-2">
                    <CardHeader>
                        <CardDescription>Invoice #INV-0042</CardDescription>
                        <CardTitle className="text-2xl">
                            ₹1,000.00 &middot; Strength session
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4 text-sm">
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground">
                                Client
                            </span>
                            <span className="font-medium">Priya S.</span>
                        </div>
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground">Due</span>
                            <span className="font-medium">On receipt</span>
                        </div>
                        <div className="bg-muted flex items-center gap-3 rounded-md p-3">
                            <QrCode className="text-primary size-10" />
                            <div>
                                <p className="font-medium">
                                    Scan to pay by UPI
                                </p>
                                <p className="text-muted-foreground">
                                    or tap the card link
                                </p>
                            </div>
                        </div>
                        <div className="bg-success/10 text-success flex items-center gap-2 rounded-md p-3">
                            <CreditCard className="size-4" /> Payment received —
                            receipt sent
                        </div>
                    </CardContent>
                </Card>
            </section>

            {/* Features */}
            <section id="features" className="bg-muted/30 border-t py-20">
                <div className="mx-auto w-full max-w-6xl px-4">
                    <div className="mx-auto max-w-2xl text-center">
                        <h2 className="text-3xl font-bold tracking-tight">
                            Everything between the session and the settlement
                        </h2>
                        <p className="text-muted-foreground mt-3">
                            Stop stitching together spreadsheets, reminder texts
                            and payment apps.
                        </p>
                    </div>
                    <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {features.map((feature) => (
                            <Card key={feature.title}>
                                <CardHeader>
                                    <feature.icon className="text-primary size-8" />
                                    <CardTitle className="mt-2">
                                        {feature.title}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="text-muted-foreground text-sm">
                                    {feature.body}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>
            </section>

            {/* How it works */}
            <section id="how" className="py-20">
                <div className="mx-auto w-full max-w-6xl px-4">
                    <div className="mx-auto max-w-2xl text-center">
                        <h2 className="text-3xl font-bold tracking-tight">
                            Three steps from workout to wallet
                        </h2>
                    </div>
                    <div className="mt-12 grid gap-8 md:grid-cols-3">
                        {steps.map((step, index) => (
                            <div key={step.title} className="space-y-3">
                                <div className="bg-primary text-primary-foreground flex size-10 items-center justify-center rounded-full text-lg font-semibold">
                                    {index + 1}
                                </div>
                                <h3 className="text-lg font-semibold">
                                    {step.title}
                                </h3>
                                <p className="text-muted-foreground text-sm">
                                    {step.body}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Pricing */}
            <section id="pricing" className="bg-muted/30 border-t py-20">
                <div className="mx-auto w-full max-w-4xl px-4">
                    <div className="mx-auto max-w-2xl text-center">
                        <h2 className="text-3xl font-bold tracking-tight">
                            Simple pricing
                        </h2>
                        <p className="text-muted-foreground mt-3">
                            Start free. Upgrade when your practice grows.
                        </p>
                    </div>
                    <div className="mt-12 grid gap-6 sm:grid-cols-2">
                        {plans.map((plan) => (
                            <Card
                                key={plan.name}
                                className={
                                    plan.highlight
                                        ? 'border-primary border-2 shadow-md'
                                        : ''
                                }
                            >
                                <CardHeader>
                                    <CardTitle>{plan.name}</CardTitle>
                                    <div className="flex items-baseline gap-1">
                                        <span className="text-3xl font-bold">
                                            {plan.price}
                                        </span>
                                        <span className="text-muted-foreground text-sm">
                                            {plan.cadence}
                                        </span>
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <ul className="space-y-2 text-sm">
                                        {plan.features.map((item) => (
                                            <li
                                                key={item}
                                                className="flex items-center gap-2"
                                            >
                                                <Check className="text-primary size-4" />
                                                {item}
                                            </li>
                                        ))}
                                    </ul>
                                    <Button
                                        asChild
                                        className="w-full"
                                        variant={
                                            plan.highlight
                                                ? 'default'
                                                : 'outline'
                                        }
                                    >
                                        <Link href={register.url()}>
                                            {plan.highlight
                                                ? 'Upgrade now'
                                                : 'Start free'}
                                        </Link>
                                    </Button>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>
            </section>

            {/* FAQ */}
            <section className="py-20">
                <div className="mx-auto w-full max-w-3xl px-4">
                    <h2 className="text-center text-3xl font-bold tracking-tight">
                        Questions, answered
                    </h2>
                    <div className="mt-10 space-y-6">
                        {faqs.map((faq) => (
                            <div key={faq.q}>
                                <h3 className="font-semibold">{faq.q}</h3>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {faq.a}
                                </p>
                            </div>
                        ))}
                    </div>
                    <div className="bg-sidebar text-sidebar-foreground mt-12 flex flex-col items-center gap-4 rounded-lg p-10 text-center">
                        <h3 className="text-2xl font-semibold">
                            Ready to stop chasing payments?
                        </h3>
                        <Button asChild size="lg">
                            <Link href={register.url()}>
                                Start your free trial <ArrowRight />
                            </Link>
                        </Button>
                    </div>
                </div>
            </section>
        </MarketingLayout>
    );
}
