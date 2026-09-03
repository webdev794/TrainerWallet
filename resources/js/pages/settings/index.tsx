import { Head, router, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

import { InputError } from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import * as settings from '@/routes/settings';
import * as stripeConnect from '@/routes/settings/stripe';

type Profile = {
    business_name: string;
    currency: string;
    upi_vpa: string | null;
    invoice_prefix: string;
    address: string | null;
    tax_id: string | null;
    logo_url: string | null;
    plan: string;
    stripe_connect_status: string | null;
    stripe_connected: boolean;
    paypal_merchant_id: string | null;
};

export default function SettingsIndex({
    profile,
    connectedGateways,
    currencies,
}: {
    profile: Profile;
    connectedGateways: string[];
    currencies: string[];
}) {
    const form = useForm<{
        business_name: string;
        currency: string;
        upi_vpa: string;
        invoice_prefix: string;
        address: string;
        tax_id: string;
        paypal_merchant_id: string;
        logo: File | null;
    }>({
        business_name: profile.business_name,
        currency: profile.currency,
        upi_vpa: profile.upi_vpa ?? '',
        invoice_prefix: profile.invoice_prefix,
        address: profile.address ?? '',
        tax_id: profile.tax_id ?? '',
        paypal_merchant_id: profile.paypal_merchant_id ?? '',
        logo: null,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(settings.update().url, { forceFormData: true });
    };

    return (
        <AppLayout
            title="Settings"
            actions={
                <Badge
                    variant={profile.plan === 'pro' ? 'default' : 'secondary'}
                >
                    {profile.plan === 'pro' ? 'Pro plan' : 'Free plan'}
                </Badge>
            }
        >
            <Head title="Settings" />

            <div className="grid gap-6 lg:grid-cols-3">
                <form onSubmit={submit} className="space-y-6 lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Business profile</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2 sm:col-span-2">
                                <Label htmlFor="business_name">
                                    Business name
                                </Label>
                                <Input
                                    id="business_name"
                                    value={form.data.business_name}
                                    onChange={(e) =>
                                        form.setData(
                                            'business_name',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.business_name}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="currency">Currency</Label>
                                <Select
                                    id="currency"
                                    value={form.data.currency}
                                    onChange={(e) =>
                                        form.setData('currency', e.target.value)
                                    }
                                >
                                    {currencies.map((code) => (
                                        <option key={code} value={code}>
                                            {code}
                                        </option>
                                    ))}
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="invoice_prefix">
                                    Invoice prefix
                                </Label>
                                <Input
                                    id="invoice_prefix"
                                    value={form.data.invoice_prefix}
                                    onChange={(e) =>
                                        form.setData(
                                            'invoice_prefix',
                                            e.target.value.toUpperCase(),
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.invoice_prefix}
                                />
                            </div>
                            <div className="space-y-2 sm:col-span-2">
                                <Label htmlFor="address">Address</Label>
                                <Textarea
                                    id="address"
                                    value={form.data.address}
                                    onChange={(e) =>
                                        form.setData('address', e.target.value)
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="tax_id">Tax ID</Label>
                                <Input
                                    id="tax_id"
                                    value={form.data.tax_id}
                                    onChange={(e) =>
                                        form.setData('tax_id', e.target.value)
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="logo">Logo</Label>
                                <Input
                                    id="logo"
                                    type="file"
                                    accept="image/*"
                                    onChange={(e) =>
                                        form.setData(
                                            'logo',
                                            e.target.files?.[0] ?? null,
                                        )
                                    }
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Payout details</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="upi_vpa">UPI ID</Label>
                                <Input
                                    id="upi_vpa"
                                    placeholder="you@okhdfcbank"
                                    value={form.data.upi_vpa}
                                    onChange={(e) =>
                                        form.setData('upi_vpa', e.target.value)
                                    }
                                />
                                <InputError message={form.errors.upi_vpa} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="paypal_merchant_id">
                                    PayPal merchant ID
                                </Label>
                                <Input
                                    id="paypal_merchant_id"
                                    value={form.data.paypal_merchant_id}
                                    onChange={(e) =>
                                        form.setData(
                                            'paypal_merchant_id',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.paypal_merchant_id}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Button type="submit" disabled={form.processing}>
                        Save settings
                    </Button>
                </form>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Payment connections</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 text-sm">
                            <div className="flex items-center justify-between">
                                <span>Stripe (cards)</span>
                                {profile.stripe_connected ? (
                                    <Badge
                                        variant={
                                            profile.stripe_connect_status ===
                                            'active'
                                                ? 'success'
                                                : 'warning'
                                        }
                                    >
                                        {profile.stripe_connect_status ??
                                            'pending'}
                                    </Badge>
                                ) : (
                                    <Badge variant="outline">
                                        Not connected
                                    </Badge>
                                )}
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                className="w-full"
                                onClick={() =>
                                    router.post(stripeConnect.connect().url)
                                }
                            >
                                {profile.stripe_connected
                                    ? 'Continue Stripe onboarding'
                                    : 'Connect Stripe'}
                            </Button>

                            <div className="flex items-center justify-between border-t pt-4">
                                <span>PayPal</span>
                                <Badge
                                    variant={
                                        connectedGateways.includes('paypal')
                                            ? 'success'
                                            : 'outline'
                                    }
                                >
                                    {connectedGateways.includes('paypal')
                                        ? 'Ready'
                                        : 'Add merchant ID'}
                                </Badge>
                            </div>
                            <div className="flex items-center justify-between border-t pt-4">
                                <span>UPI</span>
                                <Badge
                                    variant={
                                        connectedGateways.includes('upi_manual')
                                            ? 'success'
                                            : 'outline'
                                    }
                                >
                                    {connectedGateways.includes('upi_manual')
                                        ? 'Ready'
                                        : 'Add UPI ID'}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
