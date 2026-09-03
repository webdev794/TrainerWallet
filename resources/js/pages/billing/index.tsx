import { Head, router } from '@inertiajs/react';
import { Check } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import * as billing from '@/routes/billing';

type PlanConfig = {
    price: number;
    invoice_limit_per_month: number | null;
    branded_pdf: boolean;
    recurring_invoices: boolean;
    advanced_reports: boolean;
};

const featureLabels: Record<keyof Omit<PlanConfig, 'price'>, string> = {
    invoice_limit_per_month: 'invoices per month',
    branded_pdf: 'Branded PDF customisation',
    recurring_invoices: 'Recurring billing automation',
    advanced_reports: 'Advanced reporting',
};

export default function BillingIndex({
    plan,
    plans,
    usage,
    subscription,
    billingConfigured,
    renewsAt,
}: {
    plan: string;
    plans: Record<string, PlanConfig>;
    usage: { invoices_this_month: number; invoice_limit: number | null };
    subscription: { status: string; current_period_end: string | null } | null;
    billingConfigured: boolean;
    renewsAt: string | null;
}) {
    const isPro = plan === 'pro';

    return (
        <AppLayout
            title="Billing"
            actions={
                <Badge variant={isPro ? 'default' : 'secondary'}>
                    {isPro ? 'Pro' : 'Free'}
                </Badge>
            }
        >
            <Head title="Billing" />

            <Card className="mb-6">
                <CardHeader>
                    <CardTitle>This month</CardTitle>
                </CardHeader>
                <CardContent className="text-sm">
                    <p>
                        <span className="text-2xl font-bold">
                            {usage.invoices_this_month}
                        </span>{' '}
                        {usage.invoice_limit === null
                            ? 'invoices created (unlimited)'
                            : `of ${usage.invoice_limit} invoices used`}
                    </p>
                    {subscription && (
                        <p className="text-muted-foreground mt-2">
                            Subscription {subscription.status}
                            {subscription.current_period_end
                                ? ` · renews ${subscription.current_period_end}`
                                : ''}
                        </p>
                    )}
                    {!subscription && renewsAt && (
                        <p className="text-muted-foreground mt-2">
                            Pro until {renewsAt}
                        </p>
                    )}
                </CardContent>
            </Card>

            <div className="grid gap-6 sm:grid-cols-2">
                {Object.entries(plans).map(([key, config]) => (
                    <Card
                        key={key}
                        className={
                            key === 'pro' ? 'border-primary border-2' : ''
                        }
                    >
                        <CardHeader>
                            <CardTitle className="capitalize">{key}</CardTitle>
                            <div className="flex items-baseline gap-1">
                                <span className="text-3xl font-bold">
                                    ${config.price}
                                </span>
                                <span className="text-muted-foreground text-sm">
                                    {config.price === 0
                                        ? 'forever'
                                        : 'per month'}
                                </span>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <ul className="space-y-2 text-sm">
                                <li className="flex items-center gap-2">
                                    <Check className="text-primary size-4" />
                                    {config.invoice_limit_per_month === null
                                        ? 'Unlimited invoices'
                                        : `${config.invoice_limit_per_month} ${featureLabels.invoice_limit_per_month}`}
                                </li>
                                {(
                                    [
                                        'branded_pdf',
                                        'recurring_invoices',
                                        'advanced_reports',
                                    ] as const
                                ).map((f) => (
                                    <li
                                        key={f}
                                        className={
                                            config[f]
                                                ? 'flex items-center gap-2'
                                                : 'text-muted-foreground flex items-center gap-2 line-through'
                                        }
                                    >
                                        <Check className="text-primary size-4" />
                                        {featureLabels[f]}
                                    </li>
                                ))}
                            </ul>

                            {key === 'pro' && !isPro && (
                                <Button
                                    className="w-full"
                                    disabled={!billingConfigured}
                                    onClick={() =>
                                        router.post(billing.checkout().url)
                                    }
                                >
                                    {billingConfigured
                                        ? 'Upgrade to Pro'
                                        : 'Billing not configured'}
                                </Button>
                            )}
                            {key === 'pro' && isPro && (
                                <Button
                                    variant="outline"
                                    className="w-full"
                                    onClick={() =>
                                        router.post(billing.portal().url)
                                    }
                                >
                                    Manage subscription
                                </Button>
                            )}
                            {key === 'free' && (
                                <p className="text-muted-foreground text-xs">
                                    {isPro
                                        ? 'Downgrade from the subscription portal.'
                                        : 'Your current plan.'}
                                </p>
                            )}
                        </CardContent>
                    </Card>
                ))}
            </div>
        </AppLayout>
    );
}
